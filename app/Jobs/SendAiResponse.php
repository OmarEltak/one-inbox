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

class SendAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 10;

    public function __construct(
        public int $conversationId,
        public int $triggerMessageId
    ) {}

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

        try {
            $responseText = $ai->generateResponse($conversation, $triggerMessage, $aiConfig);

            if (empty($responseText)) {
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
        }
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
