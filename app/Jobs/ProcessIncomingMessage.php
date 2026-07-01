<?php

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Events\NewMessageReceived;
use App\Models\Contact;
use App\Models\ContactPlatform;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $webhookLogId
    ) {}

    public function handle(AiProviderInterface $ai): void
    {
        $webhookLog = WebhookLog::find($this->webhookLogId);

        if (! $webhookLog) {
            return;
        }

        try {
            $platform = $webhookLog->platform;
            $payload = $webhookLog->payload;

            // Route to platform-specific processor.
            // whatsapp_gateway covers both the legacy Evolution feed and the current
            // Wuzapi feed — branch on event_type prefix so each handler stays focused.
            $isWuzapi = str_starts_with((string) $webhookLog->event_type, 'wuzapi.');

            match ($platform) {
                'facebook', 'instagram' => $this->processMetaMessenger($payload, $platform, $ai),
                'whatsapp'              => $this->processWhatsApp($payload, $ai),
                'whatsapp_gateway'      => $isWuzapi
                                            ? $this->processWuzapi($payload, $ai)
                                            : $this->processEvolution($payload, $ai),
                'telegram'              => $this->processTelegram($payload, $ai),
                'tiktok'                => $this->processTikTok($payload, $ai),
                'snapchat'              => $this->processSnapchat($payload, $ai),
                'email'                 => $this->processEmail($payload, $ai),
                'slack'                 => $this->processSlack($payload, $ai),
                'discord'               => $this->processDiscord($payload, $ai),
                default                 => Log::warning("Unknown platform: {$platform}"),
            };

            $webhookLog->markProcessed();
        } catch (\Throwable $e) {
            Log::error("Failed to process webhook {$this->webhookLogId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $webhookLog->markFailed($e->getMessage());

            throw $e;
        }
    }

    protected function processMetaMessenger(array $payload, string $platform, AiProviderInterface $ai): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $messaging = $entry['messaging'] ?? [];

            foreach ($messaging as $event) {
                if (isset($event['message'])) {
                    $this->handleMetaMessage($event, $platform, $entry['id'], $ai);
                }
            }
        }
    }

    protected function handleMetaMessage(array $event, string $platform, string $pageId, AiProviderInterface $ai): void
    {
        $senderId    = $event['sender']['id'];
        $recipientId = $event['recipient']['id'] ?? null;
        $messageData = $event['message'];
        $isEcho      = (bool) ($messageData['is_echo'] ?? false);

        // Prefer page whose connected_account is also active. Multiple teams may have a page
        // record with the same Instagram User ID (e.g. an old defunct connection on team 4
        // alongside the live connection on team 3). Without this, Page::first() may pick the
        // wrong record purely by row order.
        // Match by platform_page_id (the IGBID/page ID Meta puts in entry.id) OR by stored
        // legacy/igsid/igbid metadata for pages connected before the IGBID-as-platform_page_id fix.
        $matchOn = function ($q) use ($pageId) {
            $q->where('platform_page_id', $pageId)
                ->orWhereJsonContains('metadata->igsid', $pageId)
                ->orWhereJsonContains('metadata->igbid', $pageId)
                ->orWhereJsonContains('metadata->legacy_id', $pageId)
                ->orWhereJsonContains('metadata->oauth_user_id', $pageId);
        };

        // The Page model enforces "at most one active row per (platform_page_id, platform)"
        // via its saved() observer. latest() is a defensive tiebreaker if that invariant
        // is ever violated (race condition, manual DB edit) — prefer the most recently
        // connected row, which matches last-write-wins ownership.
        $page = Page::where('platform', $platform)
            ->where($matchOn)
            ->where('is_active', true)
            ->whereHas('connectedAccount', fn ($q) => $q->where('is_active', true))
            ->latest()
            ->first()
            ?? Page::where('platform', $platform)
                ->where($matchOn)
                ->where('is_active', true)
                ->latest()
                ->first();

        // For non-echo events, sender == page itself is a duplicate-of-echo edge case
        // (IGBID vs IGSID forms). Skip those. Echoes themselves are handled below.
        if (! $isEcho) {
            $selfIds = array_filter([
                $page?->platform_page_id,
                $page?->metadata['igsid'] ?? null,
                $page?->metadata['igbid'] ?? null,
            ]);
            if ($selfIds && in_array($senderId, $selfIds, true)) {
                return;
            }
        }

        // Instagram Business Login: if the matchOn lookup didn't find an active page,
        // look for an inactive page that exactly matches this ID and reactivate it.
        // We DO NOT patch a random "first active IG page" anymore — with multiple connected
        // IG accounts on the same team, that mis-routes inbound messages to the wrong account.
        // After the IGBID-as-platform_page_id fix in handleInstagramCallback, every freshly
        // connected page already has the right ID, so blind self-heal is no longer needed.
        if (! $page && $platform === 'instagram') {
            $anyMatch = Page::where('platform', 'instagram')
                ->where(function ($q) use ($pageId) {
                    $q->where('platform_page_id', $pageId)
                        ->orWhereJsonContains('metadata->igsid', $pageId)
                        ->orWhereJsonContains('metadata->igbid', $pageId)
                        ->orWhereJsonContains('metadata->legacy_id', $pageId)
                        ->orWhereJsonContains('metadata->oauth_user_id', $pageId);
                })
                ->first();

            if ($anyMatch) {
                if (! $anyMatch->is_active) {
                    // Only reactivate if the connected account is still active.
                    // Both page and account inactive = intentional disconnect by the user.
                    // Reactivating would undo the disconnect and make the account reappear in the UI.
                    if ($anyMatch->connectedAccount?->is_active) {
                        $anyMatch->update(['is_active' => true]);
                        Log::info("Instagram: reactivated inactive page {$anyMatch->id} for entry.id={$pageId}");
                    } else {
                        Log::info("Instagram: skipping self-heal for intentionally disconnected page {$anyMatch->id} (connected account also inactive)");
                        $anyMatch = null;
                    }
                }
                $page = $anyMatch;
            }
        }

        if (! $page) {
            Log::warning("No page found for {$platform} page ID: {$pageId}");
            return;
        }

        // Update webhook log with team_id
        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        // For echoes (sent from native IG/Messenger app, or already-sent-by-us via API),
        // the contact is the recipient, not the sender (sender == our page).
        $contactExternalId = $isEcho ? $recipientId : $senderId;
        if (! $contactExternalId) {
            Log::warning("Meta message has no contact id (isEcho={$isEcho})", ['mid' => $messageData['mid'] ?? null]);
            return;
        }

        $platformMessageId = $messageData['mid'] ?? null;

        // De-duplicate: if we already stored this message (either from a prior webhook delivery
        // or from our own SendPlatformMessage that wrote the platform_message_id back), skip.
        if ($platformMessageId) {
            $existing = Message::where('platform_message_id', $platformMessageId)->first();
            if ($existing) {
                if ($existing->conversation_id) {
                    Conversation::where('id', $existing->conversation_id)->update(['last_message_at' => now()]);
                }
                return;
            }
        }

        // Resolve the contact and conversation from the contact-side id.
        $senderData   = $this->fetchMetaSenderProfile($contactExternalId, $page);
        $contact      = $this->findOrCreateContact($page, $platform, $contactExternalId, $senderData);
        $conversation = $this->findOrCreateConversation($page, $platform, $contactExternalId, $contact);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $platformMessageId,
            'direction'           => $isEcho ? 'outbound' : 'inbound',
            // 'external' = sent from the native IG/Messenger app (no logged-in user).
            // Distinguishes these from messages typed in our inbox UI (sender_type='user').
            'sender_type'         => $isEcho ? 'external' : 'contact',
            'sender_id'           => $isEcho ? null : $contact->id,
            'content_type'        => $this->detectContentType($messageData),
            'content'             => $this->extractMessageContent($messageData),
            'media_url'           => $messageData['attachments'][0]['payload']['url'] ?? null,
            'media_type'          => $messageData['attachments'][0]['type'] ?? null,
            'platform_sent_at'    => isset($event['timestamp']) ? \Carbon\Carbon::createFromTimestampMs($event['timestamp']) : now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($message->content ?? '[Media]', 100),
            'status'               => 'open',
        ]);

        if ($isEcho) {
            // Native-app reply by the operator counts as a human touch — pause AI on this thread
            // so it doesn't talk over them.
            if (method_exists($conversation, 'pauseAi') && ! $conversation->ai_paused) {
                $conversation->pauseAi();
            }
        } else {
            $conversation->incrementUnread();

            ScoreLeadJob::dispatch($message->id, $contact->id);

            $team = $page->team;
            if ($team->canDispatchAi()) {
                SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                    now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
                );
            }
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    protected function processWhatsApp(array $payload, AiProviderInterface $ai): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                $messages = $value['messages'] ?? [];
                $contacts = $value['contacts'] ?? [];

                foreach ($messages as $index => $waMessage) {
                    $this->handleWhatsAppMessage($waMessage, $phoneNumberId, $contacts[$index] ?? [], $ai);
                }
            }
        }
    }

    protected function handleWhatsAppMessage(array $waMessage, ?string $phoneNumberId, array $waContact, AiProviderInterface $ai): void
    {
        if (! $phoneNumberId) {
            return;
        }

        $page = Page::where('platform', 'whatsapp')
            ->where('platform_page_id', $phoneNumberId)
            ->first();

        if (! $page) {
            Log::warning("No page found for WhatsApp phone ID: {$phoneNumberId}");
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        $senderId = $waMessage['from'];
        $senderName = $waContact['profile']['name'] ?? $senderId;

        $contact = $this->findOrCreateContact($page, 'whatsapp', $senderId, [
            'name' => $senderName,
            'phone' => $senderId,
        ]);

        $conversation = $this->findOrCreateConversation($page, 'whatsapp', $senderId, $contact);

        $content = match ($waMessage['type']) {
            'text' => $waMessage['text']['body'] ?? null,
            'image' => '[Image]',
            'video' => '[Video]',
            'audio' => '[Audio]',
            'document' => '[Document]',
            'location' => '[Location]',
            default => null,
        };

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'platform_message_id' => $waMessage['id'] ?? null,
            'direction' => 'inbound',
            'sender_type' => 'contact',
            'sender_id' => $contact->id,
            'content_type' => $waMessage['type'] ?? 'text',
            'content' => $content,
            'platform_sent_at' => isset($waMessage['timestamp']) ? \Carbon\Carbon::createFromTimestamp($waMessage['timestamp']) : now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content ?? '[Media]', 100),
            'status' => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    /**
     * Process an inbound message from the Evolution API (WhatsApp QR gateway).
     *
     * Payload envelope (full $payload stored in webhook_logs.payload):
     * {
     *   "event":    "MESSAGES_UPSERT",
     *   "instance": "<instanceName>",     ← maps to Page.platform_page_id
     *   "data": {
     *     "key": {
     *       "remoteJid": "201099887766@s.whatsapp.net",  ← sender phone
     *       "fromMe": false,
     *       "id": "3EB0C767..."
     *     },
     *     "pushName":    "Ahmed",           ← sender's WhatsApp display name
     *     "messageType": "conversation",    ← text | imageMessage | audioMessage | etc.
     *     "message": {
     *       "conversation": "Hello!"        ← text content (messageType = conversation)
     *     },
     *     "messageTimestamp": 1741500000
     *   }
     * }
     *
     * IF EVOLUTION API CHANGES THIS PAYLOAD STRUCTURE → update the extractors below.
     * SUPPORTED message types: conversation, extendedTextMessage, imageMessage,
     *                          audioMessage, videoMessage, documentMessage
     */
    protected function processEvolution(array $payload, AiProviderInterface $ai): void
    {
        $instanceName = $payload['instance'] ?? null;
        $data = $payload['data'] ?? [];

        if (! $instanceName) {
            return;
        }

        // Find the Page by gateway_instance metadata (stable across reconnections).
        // Pages are keyed by phone number (platform_page_id) with instanceName stored in
        // metadata->gateway_instance. Fall back to platform_page_id match for any pages
        // created before this convention was introduced.
        $page = Page::where('platform', 'whatsapp')
            ->where('is_active', true)
            ->where(function ($q) use ($instanceName) {
                $q->whereJsonContains('metadata->gateway_instance', $instanceName)
                  ->orWhere('platform_page_id', $instanceName);
            })
            ->first();

        if (! $page) {
            Log::warning("Evolution webhook: no active page for instance '{$instanceName}'");
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        // Extract sender phone — strip "@s.whatsapp.net" JID suffix
        // IF Evolution API changes JID format → update this line
        $remoteJid = $data['key']['remoteJid'] ?? '';
        $senderPhone = str_replace('@s.whatsapp.net', '', $remoteJid);
        $senderName = $data['pushName'] ?? $senderPhone;
        $messageId = $data['key']['id'] ?? null;
        $timestamp = $data['messageTimestamp'] ?? null;

        if (! $senderPhone) {
            return;
        }

        // Extract message content based on type
        // IF Evolution API adds new message types → add cases here
        $messageType = $data['messageType'] ?? 'unknown';
        $messageBody = $data['message'] ?? [];

        [$content, $contentType] = match ($messageType) {
            'conversation'        => [$messageBody['conversation'] ?? null, 'text'],
            'extendedTextMessage' => [$messageBody['extendedTextMessage']['text'] ?? null, 'text'],
            'imageMessage'        => ['[Image]', 'image'],
            'audioMessage'        => ['[Audio]', 'audio'],
            'videoMessage'        => ['[Video]', 'video'],
            'documentMessage'     => [$messageBody['documentMessage']['fileName'] ?? '[Document]', 'file'],
            // Sticker messages (including animated thumbs-up button in WhatsApp)
            'stickerMessage'      => ['[Sticker]', 'text'],
            // Reaction messages (e.g. 👍 reacted to a specific message)
            'reactionMessage'     => [$messageBody['reactionMessage']['text'] ?? '[Reaction]', 'text'],
            default               => [null, 'text'],
        };

        $contact = $this->findOrCreateContact($page, 'whatsapp', $senderPhone, [
            'name'  => $senderName,
            'phone' => $senderPhone,
        ]);

        $conversation = $this->findOrCreateConversation($page, 'whatsapp', $senderPhone, $contact);

        $message = Message::create([
            'conversation_id'    => $conversation->id,
            'platform_message_id' => $messageId,
            'direction'          => 'inbound',
            'sender_type'        => 'contact',
            'sender_id'          => $contact->id,
            'content_type'       => $contentType,
            'content'            => $content,
            'platform_sent_at'   => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content ?? '[Media]', 100),
            'status'               => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    /**
     * Wuzapi webhook payload (whatsmeow-based). Shape:
     *   {
     *     "event":   "Message" | "ReadReceipt" | "Connected" | "Disconnected" | ...,
     *     "instance":"team_3_xyz",                       (the tenant name we set on user create)
     *     "token":   "<per-user token>",
     *     "jid":     "201026361218:27@s.whatsapp.net",   (the paired account)
     *     "data": {
     *       "Info": {
     *         "ID": "3EB0...",
     *         "IsFromMe": false,
     *         "MessageSource": { "Chat": "...", "Sender": "...", "IsGroup": false, "IsFromMe": false },
     *         "Timestamp": "2026-05-06T12:34:56Z",
     *         "PushName": "Mr Mohamed Eltak",
     *         "Type": "text" | "image" | "audio" | "video" | "document" | "sticker" | "reaction"
     *       },
     *       "Message": { "conversation": "..." }   // or imageMessage / documentMessage / etc.
     *     }
     *   }
     */
    protected function processWuzapi(array $payload, AiProviderInterface $ai): void
    {
        $event = $payload['event'] ?? null;

        // Only Message events carry a real conversation event; others are bookkeeping.
        if ($event !== 'Message') {
            return;
        }

        $instanceName = $payload['instance'] ?? null;
        $data         = $payload['data'] ?? [];
        $info         = $data['Info'] ?? [];
        $messageBody  = $data['Message'] ?? [];

        if (! $instanceName) {
            Log::warning('Wuzapi webhook: payload missing instance name', ['event' => $event]);
            return;
        }

        // Skip echoes — we already wrote our own outbound on send.
        if (! empty($info['IsFromMe']) || ! empty($info['MessageSource']['IsFromMe'])) {
            return;
        }

        // Skip group chats for now — the rest of the inbox assumes 1:1 conversations.
        if (! empty($info['MessageSource']['IsGroup'])) {
            return;
        }

        $page = Page::where('platform', 'whatsapp')
            ->where('is_active', true)
            ->where(function ($q) use ($instanceName) {
                $q->whereJsonContains('metadata->gateway_instance', $instanceName)
                  ->orWhere('platform_page_id', $instanceName);
            })
            ->first();

        if (! $page) {
            Log::warning("Wuzapi webhook: no active page for instance '{$instanceName}'");
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        $senderJid   = $info['MessageSource']['Sender'] ?? '';
        $senderPhone = preg_replace('/:.*$/', '', explode('@', $senderJid)[0] ?? '') ?: '';
        if (! $senderPhone) {
            return;
        }
        $senderName  = $info['PushName'] ?? $senderPhone;
        $messageId   = $info['ID'] ?? null;
        $timestamp   = $info['Timestamp'] ?? null;

        // De-dupe — if Wuzapi retries (or our own send wrote this id back), skip.
        if ($messageId && Message::where('platform_message_id', $messageId)->exists()) {
            return;
        }

        // Pull text or media descriptor out of the typed Message envelope.
        // Wuzapi mirrors whatsmeow's protobuf field names, e.g. messageBody.conversation,
        // messageBody.extendedTextMessage.text, messageBody.imageMessage.caption, etc.
        [$content, $contentType, $mediaUrl] = $this->extractWuzapiMessageContent($info['Type'] ?? null, $messageBody);

        $contact = $this->findOrCreateContact($page, 'whatsapp', $senderPhone, [
            'name'  => $senderName,
            'phone' => $senderPhone,
        ]);
        $conversation = $this->findOrCreateConversation($page, 'whatsapp', $senderPhone, $contact);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $messageId,
            'direction'           => 'inbound',
            'sender_type'         => 'contact',
            'sender_id'           => $contact->id,
            'content_type'        => $contentType,
            'content'             => $content,
            'media_url'           => $mediaUrl,
            'platform_sent_at'    => $timestamp ? \Carbon\Carbon::parse($timestamp) : now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content ?? '[Media]', 100),
            'status'               => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    /**
     * @return array{0: ?string, 1: string, 2: ?string}  [content, content_type, media_url]
     */
    protected function extractWuzapiMessageContent(?string $type, array $body): array
    {
        // Plain text (most common)
        if (! empty($body['conversation'])) {
            return [$body['conversation'], 'text', null];
        }
        if (! empty($body['extendedTextMessage']['text'])) {
            return [$body['extendedTextMessage']['text'], 'text', null];
        }

        // Media — Wuzapi/whatsmeow exposes media URLs through a separate /chat/media
        // download endpoint; the webhook itself only carries metadata. Surface the
        // caption (if any) and let the user fetch media on demand later.
        if (! empty($body['imageMessage'])) {
            return [$body['imageMessage']['caption'] ?? '[Image]', 'image', null];
        }
        if (! empty($body['videoMessage'])) {
            return [$body['videoMessage']['caption'] ?? '[Video]', 'video', null];
        }
        if (! empty($body['audioMessage'])) {
            return ['[Audio]', 'audio', null];
        }
        if (! empty($body['documentMessage'])) {
            return [$body['documentMessage']['fileName'] ?? '[Document]', 'file', null];
        }
        if (! empty($body['stickerMessage'])) {
            return ['[Sticker]', 'text', null];
        }
        if (! empty($body['reactionMessage'])) {
            return [$body['reactionMessage']['text'] ?? '[Reaction]', 'text', null];
        }
        if (! empty($body['locationMessage'])) {
            return ['[Location]', 'text', null];
        }
        if (! empty($body['contactMessage'])) {
            return [$body['contactMessage']['displayName'] ?? '[Contact]', 'text', null];
        }

        Log::info('Wuzapi: unhandled message body keys', ['type' => $type, 'keys' => array_keys($body)]);
        return [null, 'text', null];
    }

    protected function processTelegram(array $payload, AiProviderInterface $ai): void
    {
        $telegramMessage = $payload['message'] ?? $payload['edited_message'] ?? null;

        if (! $telegramMessage) {
            return;
        }

        $chatId = (string) $telegramMessage['chat']['id'];
        $senderId = (string) ($telegramMessage['from']['id'] ?? $chatId);
        $senderName = trim(($telegramMessage['from']['first_name'] ?? '') . ' ' . ($telegramMessage['from']['last_name'] ?? ''));

        // For Telegram, each bot is a "page" - find by bot token matching
        // We need to match the webhook URL to identify which bot received this
        // For now, find any active telegram page in the system
        $page = Page::where('platform', 'telegram')->where('is_active', true)->first();

        if (! $page) {
            Log::warning('No active Telegram page found');
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        $contact = $this->findOrCreateContact($page, 'telegram', $senderId, [
            'name' => $senderName ?: 'Telegram User',
        ]);

        $conversation = $this->findOrCreateConversation($page, 'telegram', $chatId, $contact);

        $content = $telegramMessage['text'] ?? null;
        $contentType = 'text';

        if (isset($telegramMessage['photo'])) {
            $contentType = 'image';
            $content = $telegramMessage['caption'] ?? '[Photo]';
        } elseif (isset($telegramMessage['document'])) {
            $contentType = 'file';
            $content = $telegramMessage['caption'] ?? '[Document]';
        } elseif (isset($telegramMessage['voice'])) {
            $contentType = 'audio';
            $content = '[Voice message]';
        } elseif (isset($telegramMessage['video'])) {
            $contentType = 'video';
            $content = $telegramMessage['caption'] ?? '[Video]';
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'platform_message_id' => (string) $telegramMessage['message_id'],
            'direction' => 'inbound',
            'sender_type' => 'contact',
            'sender_id' => $contact->id,
            'content_type' => $contentType,
            'content' => $content,
            'platform_sent_at' => isset($telegramMessage['date']) ? \Carbon\Carbon::createFromTimestamp($telegramMessage['date']) : now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content ?? '[Media]', 100),
            'status' => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    /**
     * Process an inbound TikTok Direct Message.
     *
     * Payload envelope (stored in webhook_logs.payload):
     * {
     *   "type": "direct_message",
     *   "event": {
     *     "message_id":   "...",
     *     "sender_id":    "<open_id of sender>",
     *     "receiver_id":  "<open_id of business>",   ← maps to Page.platform_page_id
     *     "create_time":  1741500000,
     *     "content":      "{\"message_type\":\"text\",\"text\":\"Hello!\"}",
     *     "message_type": "text"
     *   }
     * }
     *
     * IF TIKTOK API CHANGES THIS PAYLOAD STRUCTURE → update the extractors below.
     */
    protected function processTikTok(array $payload, AiProviderInterface $ai): void
    {
        $event = $payload['event'] ?? [];

        if (empty($event)) {
            return;
        }

        $receiverId = $event['receiver_id'] ?? null;
        $senderId   = $event['sender_id'] ?? null;
        $messageId  = $event['message_id'] ?? null;
        $messageType = $event['message_type'] ?? 'text';
        $timestamp  = $event['create_time'] ?? null;

        if (! $receiverId || ! $senderId) {
            return;
        }

        $page = Page::where('platform', 'tiktok')
            ->where('platform_page_id', $receiverId)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            Log::warning("TikTok webhook: no active page for receiver_id '{$receiverId}'");
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        // Parse content JSON string
        $contentRaw = $event['content'] ?? '{}';
        $contentData = is_string($contentRaw) ? json_decode($contentRaw, true) : $contentRaw;

        [$content, $contentType] = match ($messageType) {
            'text'  => [$contentData['text'] ?? null, 'text'],
            'image' => ['[Image]', 'image'],
            'video' => ['[Video]', 'video'],
            'audio' => ['[Audio]', 'audio'],
            'sticker' => ['[Sticker]', 'text'],
            default => [null, 'text'],
        };

        $contact = $this->findOrCreateContact($page, 'tiktok', $senderId, [
            'name' => 'TikTok User',
        ]);

        $conversation = $this->findOrCreateConversation($page, 'tiktok', $senderId, $contact);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $messageId,
            'direction'           => 'inbound',
            'sender_type'         => 'contact',
            'sender_id'           => $contact->id,
            'content_type'        => $contentType,
            'content'             => $content,
            'platform_sent_at'    => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content ?? '[Media]', 100),
            'status'               => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    /**
     * Process an inbound Snapchat Direct Message.
     *
     * Payload envelope (stored in webhook_logs.payload):
     * {
     *   "event_type": "direct_message",
     *   "message": {
     *     "id":           "...",
     *     "from_snap_id": "<sender snap_id>",   ← sender
     *     "to_snap_id":   "<business snap_id>", ← maps to Page.platform_page_id
     *     "content_type": "TEXT",
     *     "text":         "Hello!",
     *     "created_at":   1741500000
     *   }
     * }
     *
     * IF SNAPCHAT API CHANGES THIS PAYLOAD STRUCTURE → update the extractors below.
     */
    protected function processSnapchat(array $payload, AiProviderInterface $ai): void
    {
        $msg = $payload['message'] ?? [];

        if (empty($msg)) {
            return;
        }

        $senderId   = $msg['from_snap_id'] ?? null;
        $receiverId = $msg['to_snap_id'] ?? null;
        $messageId  = $msg['id'] ?? null;
        $contentType = strtolower($msg['content_type'] ?? 'text');
        $timestamp  = $msg['created_at'] ?? null;

        if (! $senderId || ! $receiverId) {
            return;
        }

        $page = Page::where('platform', 'snapchat')
            ->where('platform_page_id', $receiverId)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            Log::warning("Snapchat webhook: no active page for snap_id '{$receiverId}'");
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        [$content, $normalizedType] = match ($contentType) {
            'text'  => [$msg['text'] ?? null, 'text'],
            'image' => ['[Image]', 'image'],
            'video' => ['[Video]', 'video'],
            'audio' => ['[Audio]', 'audio'],
            'sticker' => ['[Sticker]', 'text'],
            default => [null, 'text'],
        };

        $contact = $this->findOrCreateContact($page, 'snapchat', $senderId, [
            'name' => 'Snapchat User',
        ]);

        $conversation = $this->findOrCreateConversation($page, 'snapchat', $senderId, $contact);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $messageId,
            'direction'           => 'inbound',
            'sender_type'         => 'contact',
            'sender_id'           => $contact->id,
            'content_type'        => $normalizedType,
            'content'             => $content,
            'platform_sent_at'    => $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content ?? '[Media]', 100),
            'status'               => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    /**
     * Slack Events API — message events from channels/DMs the bot is in.
     * Payload shape:
     *   { team_id, event: { type: 'message', user, text, ts, channel, channel_type } }
     */
    protected function processSlack(array $payload, AiProviderInterface $ai): void
    {
        $event = $payload['event'] ?? [];
        if (($event['type'] ?? null) !== 'message') {
            return;
        }
        if (! empty($event['bot_id']) || ($event['subtype'] ?? null) === 'bot_message') {
            return; // never reflect our own bot's posts back into the inbox
        }

        $workspaceId = (string) ($payload['team_id'] ?? '');
        $channelId   = (string) ($event['channel'] ?? '');
        $userId      = (string) ($event['user'] ?? '');
        $text        = (string) ($event['text'] ?? '');
        $eventTs     = (string) ($event['ts'] ?? '');

        if ($workspaceId === '' || $channelId === '' || $userId === '') {
            return;
        }

        $page = Page::where('platform', 'slack')
            ->where('platform_page_id', $workspaceId)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            Log::warning('Slack message for unknown workspace', ['team_id' => $workspaceId]);
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        // Best-effort fetch sender profile so the inbox shows a name, not a U0123…
        $senderName = $this->fetchSlackUserName($userId, $page->page_access_token);

        $contact = $this->findOrCreateContact($page, 'slack', $userId, [
            'name' => $senderName ?: 'Slack User',
        ]);

        $conversation = $this->findOrCreateConversation($page, 'slack', $channelId, $contact);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $eventTs ?: ('slk_' . \Illuminate\Support\Str::random(12)),
            'direction'           => 'inbound',
            'sender_type'         => 'contact',
            'sender_id'           => $contact->id,
            'content_type'        => 'text',
            'content'             => $text,
            'platform_sent_at'    => $eventTs ? \Carbon\Carbon::createFromTimestamp((int) $eventTs) : now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($text ?: '[Message]', 100),
            'status'               => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    private function fetchSlackUserName(string $userId, string $botToken): ?string
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($botToken)
                ->get('https://slack.com/api/users.info', ['user' => $userId])
                ->json();

            if (! ($resp['ok'] ?? false)) {
                return null;
            }
            $u = $resp['user'] ?? [];
            return $u['real_name'] ?? $u['profile']['real_name'] ?? $u['name'] ?? null;
        } catch (\Throwable $e) {
            Log::debug('Slack users.info failed', ['user' => $userId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Discord — slash command interactions captured by DiscordInteractionController.
     * Stored payload (from controller, not Discord raw):
     *   { application_id, user_id, user_name, content, dm_channel_id?, interaction_id }
     */
    protected function processDiscord(array $payload, AiProviderInterface $ai): void
    {
        $applicationId = (string) ($payload['application_id'] ?? '');
        $userId        = (string) ($payload['user_id'] ?? '');
        $userName      = (string) ($payload['user_name'] ?? 'Discord User');
        $content       = (string) ($payload['content'] ?? '');
        $interactionId = (string) ($payload['interaction_id'] ?? '');

        if ($applicationId === '' || $userId === '' || $content === '') {
            return;
        }

        $page = Page::where('platform', 'discord')
            ->where('platform_page_id', $applicationId)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            Log::warning('Discord message for unknown application', ['application_id' => $applicationId]);
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        $contact = $this->findOrCreateContact($page, 'discord', $userId, [
            'name' => $userName,
        ]);

        // Conversation key = Discord user_id; agent replies go back as DMs to that user.
        $conversation = $this->findOrCreateConversation($page, 'discord', $userId, $contact);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $interactionId ?: ('dsc_' . \Illuminate\Support\Str::random(12)),
            'direction'           => 'inbound',
            'sender_type'         => 'contact',
            'sender_id'           => $contact->id,
            'content_type'        => 'text',
            'content'             => $content,
            'platform_sent_at'    => now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($content, 100),
            'status'               => 'open',
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    protected function fetchMetaSenderProfile(string $senderId, Page $page): array
    {
        try {
            $version = config('services.meta.graph_api_version', 'v21.0');
            $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';

            if ($isInstagramBusiness) {
                // Instagram Business Login: use graph.instagram.com
                $response = \Illuminate\Support\Facades\Http::get(
                    "https://graph.instagram.com/{$version}/{$senderId}",
                    [
                        'fields'       => 'name,username,profile_picture_url',
                        'access_token' => $page->page_access_token,
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'name'   => $data['name'] ?? $data['username'] ?? null,
                        'avatar' => $data['profile_picture_url'] ?? null,
                    ];
                }
            } else {
                $response = \Illuminate\Support\Facades\Http::get(
                    "https://graph.facebook.com/{$version}/{$senderId}",
                    [
                        'fields'       => 'name,first_name,last_name,profile_pic',
                        'access_token' => $page->page_access_token,
                    ]
                );

                $data = $response->json();
                $name = $data['name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $avatar = $data['profile_pic'] ?? null;

                // Full-visibility log: catches 4xx/5xx AND the "200 with empty body" case
                // that was silently returning null before. Remove or downgrade once we know
                // which failure mode is actually hitting us in prod.
                if (! $response->successful() || (! $name && ! $avatar)) {
                    Log::warning('Meta sender profile fetch returned no usable data', [
                        'sender_id'    => $senderId,
                        'page_id'      => $page->id,
                        'platform'     => $page->platform,
                        'http_status'  => $response->status(),
                        'fb_error'     => $data['error'] ?? null,
                        'body_keys'    => is_array($data) ? array_keys($data) : gettype($data),
                        'body_sample'  => \Illuminate\Support\Str::limit((string) $response->body(), 500),
                        'token_prefix' => substr((string) $page->page_access_token, 0, 8),
                    ]);
                }

                if ($response->successful()) {
                    return [
                        'name'   => $name ?: null,
                        'avatar' => $avatar,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to fetch Meta sender profile for {$senderId}", ['error' => $e->getMessage()]);
        }

        return [];
    }

    protected function findOrCreateContact(Page $page, string $platform, string $platformContactId, array $senderData): Contact
    {
        // Check if we already know this contact on this platform
        $contactPlatform = ContactPlatform::where('platform', $platform)
            ->where('platform_contact_id', $platformContactId)
            ->first();

        if ($contactPlatform) {
            $contact = $contactPlatform->contact;

            $updates = ['last_interaction_at' => now()];

            // Backfill name/avatar if previously unknown
            if (empty($contact->name) && ! empty($senderData['name'])) {
                $updates['name'] = $senderData['name'];
            }
            if (empty($contact->avatar) && ! empty($senderData['avatar'])) {
                $updates['avatar'] = $senderData['avatar'];
            }
            $contact->update($updates);

            if (empty($contactPlatform->platform_name) && ! empty($senderData['name'])) {
                $contactPlatform->update(['platform_name' => $senderData['name']]);
            }

            return $contact;
        }

        // Create new contact
        $contact = Contact::create([
            'team_id' => $page->team_id,
            'name' => $senderData['name'] ?? null,
            'phone' => $senderData['phone'] ?? null,
            'avatar' => $senderData['avatar'] ?? null,
            'first_seen_at' => now(),
            'last_interaction_at' => now(),
        ]);

        // Link to platform
        ContactPlatform::create([
            'contact_id' => $contact->id,
            'platform' => $platform,
            'platform_contact_id' => $platformContactId,
            'platform_name' => $senderData['name'] ?? null,
        ]);

        // Schedule a lazy profile fetch if name is unavailable (e.g. IG Business returns null)
        if (in_array($platform, ['instagram', 'facebook'], true) && empty($senderData['name'])) {
            BackfillContactNameJob::dispatch($contact->id)->delay(now()->addMinutes(2));
        }

        return $contact;
    }

    protected function findOrCreateConversation(Page $page, string $platform, string $participantId, Contact $contact): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'team_id' => $page->team_id,
                'page_id' => $page->id,
                'platform' => $platform,
                'platform_conversation_id' => $participantId,
            ],
            [
                'contact_id' => $contact->id,
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );
    }

    private const META_STICKER_EMOJI = [
        369239263222822 => '👍',
        369239343222814 => '❤️',
        369239373222812 => '😆',
        369239383222811 => '😮',
        369239393222810 => '😢',
        369239413222808 => '😡',
    ];

    protected function detectContentType(array $messageData): string
    {
        if (isset($messageData['attachments'])) {
            $type = $messageData['attachments'][0]['type'] ?? 'file';
            // Sticker_id present → emoji sticker or "like" reaction, NOT a real image
            if ($type === 'image' && isset($messageData['attachments'][0]['payload']['sticker_id'])) {
                return 'reaction';
            }
            return $type;
        }

        return 'text';
    }

    protected function extractMessageContent(array $messageData): ?string
    {
        if (isset($messageData['attachments'][0]['payload']['sticker_id'])) {
            $id = $messageData['attachments'][0]['payload']['sticker_id'];
            return self::META_STICKER_EMOJI[$id] ?? '👍';
        }
        return $messageData['text'] ?? null;
    }

    /**
     * Process an inbound email fetched via IMAP by the FetchEmails command.
     *
     * Payload envelope (stored in webhook_logs.payload):
     * {
     *   "message_id":  "<abc@mail.gmail.com>",
     *   "in_reply_to": "<prev@mail.gmail.com>",
     *   "from_email":  "customer@example.com",
     *   "from_name":   "Ahmed Ali",
     *   "to":          "support@business.com",
     *   "subject":     "Question about pricing",
     *   "text":        "Hi, I wanted to ask...",
     *   "html":        "<p>Hi...</p>",
     *   "date":        1741500000,
     *   "to_page_id":  "support@business.com"
     * }
     */
    protected function processEmail(array $payload, AiProviderInterface $ai): void
    {
        $toPageId   = $payload['to_page_id'] ?? null;
        $fromEmail  = $payload['from_email'] ?? null;
        $fromName   = $payload['from_name'] ?? $fromEmail;
        $messageId  = $payload['message_id'] ?? null;
        $inReplyTo  = $payload['in_reply_to'] ?? null;
        $subject    = $payload['subject'] ?? '(no subject)';
        $text       = $payload['text'] ?? '';
        $html       = $payload['html'] ?? null;
        $date       = $payload['date'] ?? null;

        if (! $toPageId || ! $fromEmail) {
            return;
        }

        $page = Page::where('platform', 'email')
            ->where('platform_page_id', $toPageId)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            Log::warning("Email: no active page for inbox '{$toPageId}'");
            return;
        }

        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        $contact = $this->findOrCreateContact($page, 'email', $fromEmail, [
            'name' => $fromName,
        ]);

        // Store the sender email in contact metadata for reply routing
        if (empty($contact->metadata['email'])) {
            $contact->update(['metadata' => array_merge($contact->metadata ?? [], ['email' => $fromEmail])]);
        }

        // Thread by In-Reply-To chain, fall back to normalized subject hash
        $conversationId = $this->resolveEmailThread($inReplyTo, $subject, $toPageId, $page);

        $conversation = Conversation::firstOrCreate(
            [
                'team_id'                   => $page->team_id,
                'platform'                  => 'email',
                'platform_conversation_id'  => $conversationId,
            ],
            [
                'page_id'         => $page->id,
                'contact_id'      => $contact->id,
                'status'          => 'open',
                'last_message_at' => now(),
                'metadata'        => [
                    'subject'      => $subject,
                    'contact_email' => $fromEmail,
                ],
            ]
        );

        // Update subject + contact_email on conversation so replies work
        $convMeta = $conversation->metadata ?? [];
        if (empty($convMeta['contact_email'])) {
            $convMeta['contact_email'] = $fromEmail;
        }
        if (empty($convMeta['subject'])) {
            $convMeta['subject'] = 'Re: ' . $subject;
        }
        if ($messageId && empty($convMeta['last_message_id'])) {
            $convMeta['last_message_id'] = $messageId;
        }
        $conversation->update(['metadata' => $convMeta]);

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $messageId,
            'direction'           => 'inbound',
            'sender_type'         => 'contact',
            'sender_id'           => $contact->id,
            'content_type'        => 'text',
            'content'             => $text ?: strip_tags($html ?? ''),
            'platform_sent_at'    => $date ? \Carbon\Carbon::createFromTimestamp($date) : now(),
            'metadata'            => ['subject' => $subject, 'html' => $html],
        ]);

        // Always update last_message_id so replies thread correctly
        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($message->content ?? '[Email]', 100),
            'status'               => 'open',
            'metadata'             => array_merge($conversation->fresh()->metadata ?? [], [
                'last_message_id' => $messageId,
            ]),
        ]);
        $conversation->incrementUnread();

        ScoreLeadJob::dispatch($message->id, $contact->id);

        $team = $page->team;
        if ($team->canDispatchAi()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
    }

    protected function safeBroadcast(\App\Events\NewMessageReceived $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed (non-fatal): ' . $e->getMessage());
        }
    }

    /**
     * Determine the platform_conversation_id for an email.
     * Tries to match an existing message by In-Reply-To header first,
     * then falls back to a hash of the normalized subject + inbox address.
     */
    protected function resolveEmailThread(?string $inReplyTo, string $subject, string $inboxEmail, Page $page): string
    {
        if ($inReplyTo) {
            $existing = Message::where('platform_message_id', $inReplyTo)
                ->whereHas('conversation', fn ($q) => $q->where('team_id', $page->team_id)->where('platform', 'email'))
                ->first();

            if ($existing) {
                return $existing->conversation->platform_conversation_id;
            }
        }

        // Normalize subject: strip Re:/Fwd: prefixes, lowercase, trim
        $normalized = preg_replace('/^(re|fwd|fw):\s*/i', '', $subject);
        $normalized = strtolower(trim($normalized));

        return sha1($normalized . '|' . strtolower($inboxEmail));
    }
}
