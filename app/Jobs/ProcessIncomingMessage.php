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

            // Route to platform-specific processor
            match ($platform) {
                'facebook', 'instagram' => $this->processMetaMessenger($payload, $platform, $ai),
                'whatsapp'              => $this->processWhatsApp($payload, $ai),
                'whatsapp_gateway'      => $this->processEvolution($payload, $ai),
                'telegram'              => $this->processTelegram($payload, $ai),
                'tiktok'                => $this->processTikTok($payload, $ai),
                'snapchat'              => $this->processSnapchat($payload, $ai),
                'email'                 => $this->processEmail($payload, $ai),
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
        $senderId = $event['sender']['id'];
        $messageData = $event['message'];

        // Skip echo messages (sent by us)
        if ($messageData['is_echo'] ?? false) {
            return;
        }

        $page = Page::where('platform', $platform)
            ->where('platform_page_id', $pageId)
            ->first();

        if (! $page) {
            Log::warning("No page found for {$platform} page ID: {$pageId}");
            return;
        }

        // Update webhook log with team_id
        WebhookLog::where('id', $this->webhookLogId)->update(['team_id' => $page->team_id]);

        // Fetch sender profile from Graph API (webhook only sends PSID, no name)
        $senderData = $this->fetchMetaSenderProfile($senderId, $page);

        // Find or create contact
        $contact = $this->findOrCreateContact($page, $platform, $senderId, $senderData);

        // Find or create conversation
        $conversation = $this->findOrCreateConversation($page, $platform, $senderId, $contact);

        // Store message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'platform_message_id' => $messageData['mid'] ?? null,
            'direction' => 'inbound',
            'sender_type' => 'contact',
            'sender_id' => $contact->id,
            'content_type' => $this->detectContentType($messageData),
            'content' => $messageData['text'] ?? null,
            'media_url' => $messageData['attachments'][0]['payload']['url'] ?? null,
            'media_type' => $messageData['attachments'][0]['type'] ?? null,
            'platform_sent_at' => isset($event['timestamp']) ? \Carbon\Carbon::createFromTimestampMs($event['timestamp']) : now(),
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => \Illuminate\Support\Str::limit($message->content ?? '[Media]', 100),
            'status' => 'open',
        ]);
        $conversation->incrementUnread();

        // Always score the message (runs even when AI responses are off)
        ScoreLeadJob::dispatch($message->id, $contact->id);

        // Auto-respond if AI is enabled for this team
        $team = $page->team;
        if ($team->isAiEnabled()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        // Broadcast real-time update
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
        if ($team->isAiEnabled()) {
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

        // Find the Page by Evolution instance name
        // IF Evolution API changes instance identification → update this query
        $page = Page::where('platform', 'whatsapp')
            ->where('platform_page_id', $instanceName)
            ->where('is_active', true)
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
        if ($team->isAiEnabled()) {
            SendAiResponse::dispatch($conversation->id, $message->id)->delay(
                now()->addSeconds($page->aiConfig?->getRandomDelay() ?? 60)
            );
        }

        $this->safeBroadcast(NewMessageReceived::fromMessage($message, $conversation));
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
        if ($team->isAiEnabled()) {
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
        if ($team->isAiEnabled()) {
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
        if ($team->isAiEnabled()) {
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

                if ($response->successful()) {
                    $data = $response->json();
                    $name = $data['name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

                    return [
                        'name'   => $name ?: null,
                        'avatar' => $data['profile_pic'] ?? null,
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

        return $contact;
    }

    protected function findOrCreateConversation(Page $page, string $platform, string $participantId, Contact $contact): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'team_id' => $page->team_id,
                'platform' => $platform,
                'platform_conversation_id' => $participantId,
            ],
            [
                'page_id' => $page->id,
                'contact_id' => $contact->id,
                'status' => 'open',
                'last_message_at' => now(),
            ]
        );
    }

    protected function detectContentType(array $messageData): string
    {
        if (isset($messageData['attachments'])) {
            return $messageData['attachments'][0]['type'] ?? 'file';
        }

        return 'text';
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
        if ($team->isAiEnabled()) {
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
