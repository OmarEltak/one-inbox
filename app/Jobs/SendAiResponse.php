<?php

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Events\AiLimitReached;
use App\Events\AiResponseSent;
use App\Exceptions\AiAllProvidersUnavailable;
use App\Exceptions\AiQuotaExhausted;
use App\Http\Middleware\EnforcePlanLimits;
use App\Services\Ai\CaptureExtractor;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


/**
 * ══ ARCHITECTURE REFERENCE §5, §7, §9, §11, §12 ══
 * READ docs/ARCHITECTURE.md before restructuring handle(). This job is the
 * central AI dispatch site — it composes ~10 different intentional gates:
 *
 *   §5  Sales-flow state transitions (isSalesStageActive, escalate, complete)
 *   §6  Per-contact daily reply cap (canReceiveAiReply, recordAiReply)
 *   §7  Burst debounce (skip if newer inbound exists)
 *   §9  Abuse detection ([SPAM_DETECTED] marker check)
 *  §10  Captured-data extraction (runs BEFORE reply generation)
 *  §11  canDispatchAi() single-gate composition
 *  §12  Silent-skip vs fallback-string convention (empty response = no send)
 *
 * The ordering of gates in handle() matters. Do NOT reorder without
 * reading the arch doc.
 */
class SendAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 10;

    // NaraRouter provider chain + gateway HTTP send can compound to >60s
    // under provider failover. Without an explicit timeout, this job would
    // SIGKILL the worker on slow paths.
    public int $timeout = 240;

    public function __construct(
        public int $conversationId,
        public int $triggerMessageId
    ) {
        // Route to 'urgent' — AI reply latency is customer-visible.
        $this->onQueue('urgent');
    }

    public function handle(AiProviderInterface $ai): void
    {
        $conversation = Conversation::with(['page.team', 'page.aiConfig', 'contact'])->find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $team = $conversation->page->team;

        // Single gate: team toggle + plan quota. If either is off, AI must not react.
        // (Dispatch sites already check this, but the job may have been queued before
        // credits were exhausted — re-check at consume time.)
        if (! $team->canDispatchAi()) {
            return;
        }

        // Page was disconnected after this job was queued
        if (! $conversation->page->is_active) {
            return;
        }

        $aiConfig = $conversation->page->aiConfig;

        if (! $aiConfig || ! $aiConfig->is_active) {
            return;
        }

        if (! $aiConfig->isWithinWorkingHours()) {
            return;
        }

        // Conversation is not in the active sales stage (paused, escalated,
        // completed, or spam). Every dispatch site checks this too but a
        // stage transition may have happened while this job was in the queue.
        if (! $conversation->isSalesStageActive()) {
            return;
        }

        // Per-contact daily AI reply cap. Prevents a single troll or overly
        // chatty customer from burning all of your provider tokens. Rolls the
        // 24h window lazily so no cron job is required.
        $contact = $conversation->contact;
        $cap     = (int) ($aiConfig->contact_ai_reply_cap ?? 20);
        if ($contact && ! $contact->canReceiveAiReply($cap)) {
            $conversation->escalate('contact_ai_reply_cap_reached');
            broadcast(new AiLimitReached($team->id));
            return;
        }

        $triggerMessage = Message::find($this->triggerMessageId);

        if (! $triggerMessage) {
            return;
        }

        // Check if a human has already responded since the trigger message
        $humanReplied = $conversation->messages()
            ->where('id', '>', $this->triggerMessageId)
            ->where('sender_type', 'user')
            ->exists();

        if ($humanReplied) {
            return; // Human took over, don't send AI response
        }

        // Debounce burst messages. If the customer sent additional messages
        // after our trigger, the LATER-dispatched job for the most recent
        // message will fire and generate one reply that addresses the whole
        // burst via conversation history. Skipping here saves 1 API call per
        // extra rapid-fire message.
        $newerInboundExists = $conversation->messages()
            ->where('id', '>', $this->triggerMessageId)
            ->where('direction', 'inbound')
            ->exists();

        if ($newerInboundExists) {
            return;
        }

        if ($this->shouldEscalate($triggerMessage->content ?? '', $aiConfig)) {
            $conversation->escalate('escalation_keyword_matched');
            return;
        }

        // Operator-configured: hand off whenever the inbound is (or contains)
        // media — image / video / audio / sticker / document. See AiConfig
        // "escalate_on_media" toggle in the Handoff tab. Detection mirrors the
        // pattern used in resources/views/livewire/inbox/index.blade.php:579.
        if (($aiConfig->escalate_on_media ?? false) && $this->isMediaMessage($triggerMessage)) {
            $conversation->escalate('media_received');
            return;
        }

        // Operator-configured: topic groups. Any keyword hit inside a topic
        // escalates and stamps the audit reason with the topic label so
        // support can filter later (e.g. "topic_matched:Medical").
        if ($topicLabel = $this->matchedTopicLabel($triggerMessage->content ?? '', $aiConfig)) {
            $conversation->escalate('topic_matched:' . $topicLabel);
            return;
        }

        // Capture pass: try to extract any required fields from this inbound
        // BEFORE generating the reply, so the reply prompt knows what's still
        // missing and pushes for it. If everything is captured after this
        // extraction, complete the conversation and stop — the goal is met,
        // no more AI replies needed.
        if (! empty($aiConfig->required_capture_fields)) {
            $extractor = new CaptureExtractor($ai);
            $capture   = $extractor->extract($conversation->refresh(), $triggerMessage, $aiConfig);

            if ($capture['complete']) {
                $conversation->complete('all_required_fields_captured');
                return;
            }
        }

        // Track wall-clock so we can overlap the AI call with the "typing
        // indicator" delay budget instead of stacking them. Concretely:
        //   old: wait 15s in queue, then call AI (10s), then send → 25s total
        //   new: dispatch fires immediately, call AI (10s), then sleep the
        //        REMAINING typing budget (max 0, 15-10=5s), then send → 15s total
        // Slow-AI case is even better: if AI takes 20s, no extra sleep needed
        // and total lag is 20s instead of the old 15+20=35s.
        $handleStartedAt = microtime(true);
        $desiredDelaySec = (int) ($aiConfig?->getRandomDelay() ?? 0);

        // If the trigger is an image with a cached vision description (see
        // DescribeImage job), inject the description into the message content
        // in-memory so the text-only AI provider can reason about the image.
        // Not persisted — original body stays [image] / caption on disk.
        //
        // The injection is bilingual (Arabic + English) because previous English-
        // only prefixes were silently ignored by models responding in Arabic —
        // AI treated the English "[Customer sent an image...]" as noise and
        // replied "I don't see this image" to the Arabic caption. The Arabic
        // marker "[صورة العميل]" is unmistakable to any locale-tuned model.
        $triggerMessage->loadMissing('mediaAsset');
        if ($triggerMessage->mediaAsset
            && $triggerMessage->mediaAsset->kind === 'image'
            && ($desc = trim($triggerMessage->mediaAsset->metadata['ai_description'] ?? ''))
        ) {
            $originalCaption = $triggerMessage->content;
            $captionPart     = ($originalCaption && $originalCaption !== '[image]') ? "\n\nCaption / تعليق العميل: {$originalCaption}" : '';
            $triggerMessage->content = "[صورة العميل | Customer sent an image]\nVision description (respond in the customer's language): {$desc}{$captionPart}";
        }

        try {
            $responseText = $ai->generateResponse($conversation, $triggerMessage, $aiConfig);

            if (empty($responseText)) {
                return;
            }

            // Abuse detection — the reply prompt asks the AI to output the
            // [SPAM_DETECTED] marker when it judges the customer is abusive,
            // trolling, or wasting time. If we see it, mark the conversation
            // as spam and DO NOT send the reply. The marker itself is never
            // forwarded to the customer.
            if (str_contains($responseText, '[SPAM_DETECTED]')) {
                // Loop protection: if a human operator already clicked
                // "Reactivate" (which sets metadata.reactivated_at), the AI
                // MUST NOT overturn that judgment by re-flagging the same
                // conversation on the next inbound message. The history that
                // originally triggered the classifier is still present after
                // reactivation, so without this guard we'd auto-re-flag
                // forever — see ARCHITECTURE §9 "Reactivation loop".
                $reactivatedAt = data_get($conversation->metadata, 'reactivated_at');
                if ($reactivatedAt !== null) {
                    Log::info("AI wanted to re-flag conversation {$conversation->id} as spam, but a human operator reactivated it — suppressing", [
                        'reactivated_at' => $reactivatedAt,
                    ]);
                    return;
                }

                Log::info("AI flagged conversation {$conversation->id} as spam (auto)");
                $conversation->update([
                    'sales_stage' => Conversation::STAGE_SPAM,
                    'ai_paused'   => true,
                    'metadata'    => array_merge($conversation->metadata ?? [], [
                        'marked_spam_by'     => 'ai_auto',
                        'marked_spam_at'     => now()->toIso8601String(),
                        'marked_spam_reason' => 'ai_detected_abuse',
                    ]),
                ]);
                return;
            }

            // Wait out the REMAINING typing-indicator budget (if any) BEFORE
            // storing/sending, so the customer sees the reply at ~desiredDelaySec
            // after their message — same UX as before, without stacking the AI
            // call time on top.
            $elapsedSec = microtime(true) - $handleStartedAt;
            $remainingSec = $desiredDelaySec - $elapsedSec;
            if ($remainingSec > 0.5) {
                usleep((int) ($remainingSec * 1_000_000));
            }

            // Debounce check AFTER the sleep: if the customer sent another
            // inbound message during the AI call or the remaining typing
            // budget, THIS reply is now stale — the newer message's own
            // SendAiResponse job will run and generate a fresh reply that
            // sees the full context. Bail so we don't double-reply.
            $newerInboundAfterAi = $conversation->messages()
                ->where('id', '>', $this->triggerMessageId)
                ->where('direction', 'inbound')
                ->exists();

            if ($newerInboundAfterAi) {
                return;
            }

            // Store the AI message in our database
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'sender_type' => 'ai',
                'content_type' => 'text',
                'content' => $responseText,
            ]);

            // Send through the platform
            $this->sendToPlatform($conversation, $responseText, $aiMessage);

            $conversation->update([
                'last_message_at' => now(),
                'last_message_preview' => \Illuminate\Support\Str::limit($responseText, 100),
            ]);

            // Bump the per-contact daily counter — pre-check already ensured
            // we're under the cap; this is the record-keeping side.
            $contact?->recordAiReply();

            // Increment AI credits used. If this push crossed the plan limit, fire
            // AiLimitReached exactly once — subsequent dispatches are gated by
            // canDispatchAi() so this event won't repeat per-message.
            $team->increment('ai_credits_used');
            if (! EnforcePlanLimits::hasAiCredits($team)) {
                Log::info("Team {$team->id} just reached AI credit limit");
                broadcast(new AiLimitReached($team->id));
            }

            // Broadcast real-time update
            broadcast(AiResponseSent::fromMessage($aiMessage, $conversation));
        } catch (AiQuotaExhausted $e) {
            // Upstream provider is out of tokens for the day / rate limited.
            // Pause AI for this team so we stop hammering the provider, and
            // broadcast to the header banner. No message goes to the customer.
            Log::warning("AI upstream quota exhausted for team {$team->id}", ['error' => $e->getMessage()]);
            $team->markAiUpstreamPaused(null, 'quota');
            broadcast(new AiLimitReached($team->id));
            return;
        } catch (AiAllProvidersUnavailable $e) {
            // Every model in the failover chain returned 5xx. This is a
            // provider-side outage — the customer's message stays in the
            // inbox unanswered, and the operator gets a banner saying
            // "AI temporarily unavailable, please contact us". We pause on
            // a SHORT window (15 min) because outages usually recover fast;
            // when the window expires the AI resumes automatically.
            Log::error("AI all providers unavailable for team {$team->id}", ['error' => $e->getMessage()]);
            $team->markAiUpstreamPaused(new \DateInterval('PT15M'), 'outage');
            broadcast(new AiLimitReached($team->id));
            return;
        } catch (\Throwable $e) {
            Log::error("AI response failed for conversation {$this->conversationId}", [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function shouldEscalate(string $messageContent, ?\App\Models\AiConfig $config = null): bool
    {
        // Prefer the operator-configured keyword list (populated from a preset,
        // customer can add more). Fall back to the built-in English list only
        // when no custom keywords have been set, so historical configs keep
        // working without a migration seed.
        $keywords = $config?->escalation_keywords ?: [
            'talk to a human',
            'talk to human',
            'real person',
            'speak to someone',
            'speak to a person',
            'manager',
            'supervisor',
            'agent',
            'representative',
            'human please',
            'real agent',
            'live agent',
            'talk to someone',
            'speak with a human',
        ];

        $lower = mb_strtolower($messageContent);

        foreach ($keywords as $keyword) {
            $needle = mb_strtolower(trim((string) $keyword));
            if ($needle === '') {
                continue;
            }
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A message is "media" when it carries a non-text payload — image, video,
     * audio, sticker, document, file. Two signals because platforms disagree:
     * some set content_type ('image'/'video'/...), others only set media_type
     * (a MIME string like 'image/jpeg'). Mirrors the detection used in the
     * inbox blade so the UI and the AI gate agree.
     */
    protected function isMediaMessage(Message $message): bool
    {
        $contentType = (string) ($message->content_type ?? 'text');

        if ($contentType !== '' && $contentType !== 'text') {
            return true;
        }

        return ! empty($message->media_url) || ! empty($message->media_type);
    }

    /**
     * Return the topic label whose keyword list matched the message content,
     * or null. Matching is case-insensitive substring, identical to
     * shouldEscalate() so operators don't have to learn a second rule set.
     */
    protected function matchedTopicLabel(string $messageContent, ?\App\Models\AiConfig $config): ?string
    {
        $topics = $config?->escalation_topics ?? [];
        if (empty($topics)) {
            return null;
        }

        $lower = mb_strtolower($messageContent);
        if ($lower === '') {
            return null;
        }

        foreach ($topics as $topic) {
            $label = trim((string) ($topic['label'] ?? ''));
            $keywords = $topic['keywords'] ?? [];
            if ($label === '' || empty($keywords)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                $needle = mb_strtolower(trim((string) $keyword));
                if ($needle === '') {
                    continue;
                }
                if (str_contains($lower, $needle)) {
                    return $label;
                }
            }
        }

        return null;
    }

    protected function sendToPlatform(Conversation $conversation, string $text, Message $aiMessage): void
    {
        $page = $conversation->page;
        $platform = $conversation->platform;
        $recipientId = $conversation->platform_conversation_id;

        match ($platform) {
            'facebook', 'instagram' => $this->sendViaMetaMessenger($page, $recipientId, $text, $aiMessage),
            'whatsapp' => $this->sendViaWhatsApp($page, $recipientId, $text, $aiMessage),
            'telegram' => $this->sendViaTelegram($page, $recipientId, $text, $aiMessage),
            default => Log::warning("Cannot send to unknown platform: {$platform}"),
        };
    }

    protected function sendViaMetaMessenger(Page $page, string $recipientId, string $text, Message $aiMessage): void
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$page->platform_page_id}/messages";

        $response = Http::withToken($page->page_access_token)->post($url, [
            'recipient' => ['id' => $recipientId],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $text],
        ]);

        if ($response->successful()) {
            $aiMessage->update([
                'platform_message_id' => $response->json('message_id'),
                'platform_sent_at' => now(),
            ]);
        } else {
            Log::error('Meta Messenger send failed', ['body' => $response->body()]);
        }
    }

    protected function sendViaWhatsApp(Page $page, string $recipientId, string $text, Message $aiMessage): void
    {
        // QR-gateway pages (metadata.gateway_mode) route through Wuzapi.
        // Without this branch, AI replies on QR-connected numbers hit Meta's Graph
        // API with what is actually a Wuzapi per-user token, producing OAuthException
        // code 190 ("Cannot parse access token") and no message delivery. The user-
        // triggered send path (SendPlatformMessage::sendViaWhatsApp) has the same
        // gate — keep them in sync if either side changes.
        if (! empty($page->metadata['gateway_mode'])) {
            $instanceName   = $page->metadata['gateway_instance'] ?? $page->platform_page_id;
            $instanceApiKey = $page->page_access_token;

            $messageId = app(\App\Services\EvolutionApiService::class)
                ->sendText($instanceName, $instanceApiKey, $recipientId, $text);

            if ($messageId) {
                $aiMessage->update([
                    'platform_message_id' => $messageId,
                    'platform_sent_at'    => now(),
                ]);
            } else {
                Log::error('WhatsApp (Wuzapi) AI send failed', [
                    'instance' => $instanceName,
                    'to'       => $recipientId,
                ]);
                $this->stampSendFailure($aiMessage, "WhatsApp is disconnected. Re-scan the QR code on the Connections page.");
            }
            return;
        }

        // Meta Cloud API path (non-gateway, WABA-registered numbers).
        $version = config('services.meta.graph_api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$page->platform_page_id}/messages";

        $response = Http::withToken($page->page_access_token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'text',
            'text' => ['body' => $text],
        ]);

        if ($response->successful()) {
            $messageId = $response->json('messages.0.id');
            $aiMessage->update([
                'platform_message_id' => $messageId,
                'platform_sent_at' => now(),
            ]);
        } else {
            Log::error('WhatsApp send failed', ['body' => $response->body()]);
            $this->stampSendFailure($aiMessage, 'WhatsApp Cloud API rejected the send: ' . substr($response->body(), 0, 200));
        }
    }

    /**
     * Stamp an AI-generated Message row so the inbox blade surfaces the failure
     * with a red "Not delivered" indicator (blade reads metadata.send_status).
     * Mirrors what SendPlatformMessage::failed() does for user-triggered sends.
     */
    private function stampSendFailure(Message $aiMessage, string $error): void
    {
        $meta = $aiMessage->metadata ?? [];
        $meta['send_status']    = 'failed';
        $meta['send_error']     = $error;
        $meta['send_error_raw'] = $error;
        $aiMessage->update(['metadata' => $meta]);
    }

    protected function sendViaTelegram(Page $page, string $chatId, string $text, Message $aiMessage): void
    {
        $botToken = $page->page_access_token; // For Telegram, we store bot token here

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if ($response->successful()) {
            $aiMessage->update([
                'platform_message_id' => (string) $response->json('result.message_id'),
                'platform_sent_at' => now(),
            ]);
        } else {
            Log::error('Telegram send failed', ['body' => $response->body()]);
        }
    }
}
