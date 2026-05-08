<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPlatformMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $messageId
    ) {}

    public function handle(): void
    {
        $message = Message::with('conversation.page')->find($this->messageId);

        if (! $message || ! $message->conversation || ! $message->conversation->page) {
            return;
        }

        $conversation = $message->conversation;
        $page = $conversation->page;
        $platform = $conversation->platform;
        $recipientId = $conversation->platform_conversation_id;

        try {
            $platformMessageId = match ($platform) {
                'facebook', 'instagram' => $this->sendViaMetaMessenger($page, $recipientId, $message),
                'whatsapp' => $this->sendViaWhatsApp($page, $recipientId, $message),
                'telegram' => $this->sendViaTelegram($page, $recipientId, $message),
                'email' => $this->sendViaEmail($page, $message),
                'webchat' => $this->sendViaWebChat($message),
                'slack' => $this->sendViaSlack($page, $recipientId, $message),
                'discord' => $this->sendViaDiscord($page, $recipientId, $message),
                default => null,
            };

            $message->update([
                'platform_message_id' => $platformMessageId,
                'platform_sent_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send message {$this->messageId} via {$platform}", [
                'error' => $e->getMessage(),
            ]);

            // Stamp failure on every attempt so the inbox UI can show the latest error
            // without waiting for all retries to be exhausted.
            $current = Message::find($this->messageId);
            if ($current) {
                $meta = $current->metadata ?? [];
                $meta['send_status'] = 'failing';
                $meta['send_error']  = $this->humanizeSendError($e->getMessage());
                $meta['send_error_raw'] = $e->getMessage();
                $current->update(['metadata' => $meta]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $message = Message::find($this->messageId);
        if ($message) {
            $meta = $message->metadata ?? [];
            $meta['send_status']   = 'failed';
            $meta['send_error']    = $this->humanizeSendError($e->getMessage());
            $meta['send_error_raw'] = $e->getMessage();
            $message->update(['metadata' => $meta]);
        }
    }

    /**
     * Translate Meta's verbose / Arabic error strings into a short English hint
     * so the inbox UI can show something the user actually understands.
     */
    protected function humanizeSendError(string $raw): string
    {
        // 24-hour messaging window violation (Instagram + Facebook Messenger)
        if (str_contains($raw, '2534022')
            || str_contains($raw, 'outside the allowed')
            || str_contains($raw, 'خارج الفترة')) {
            return 'Outside the 24-hour messaging window. Instagram only allows replies within 24 hours of the contact\'s last message.';
        }
        if (str_contains($raw, '10303') || str_contains($raw, 'message_tag')) {
            return 'Outside messaging window — a valid message tag is required.';
        }
        if (str_contains($raw, '2018278') || str_contains($raw, 'support inbox messaging')) {
            return 'This Instagram account does not have messaging enabled (must be Business / Creator).';
        }
        if (str_contains($raw, '190') || str_contains($raw, 'expired') || str_contains($raw, 'OAuthException')) {
            return 'Access token expired. Reconnect this account in Connections.';
        }
        return $raw;
    }

    protected function sendViaEmail($page, Message $message): ?string
    {
        $conversation = $message->conversation;
        $meta         = $page->metadata ?? [];
        $fromEmail    = $page->platform_page_id;
        $password     = decrypt($page->page_access_token);

        $toEmail   = $conversation->metadata['contact_email'] ?? null;
        $subject   = $conversation->metadata['subject'] ?? 'Re: (no subject)';
        $inReplyTo = $conversation->metadata['last_message_id'] ?? null;

        if (! $toEmail) {
            Log::error("sendViaEmail: no contact_email for conversation {$conversation->id}");
            return null;
        }

        config([
            'mail.mailers.email_platform' => [
                'transport'  => 'smtp',
                'host'       => $meta['smtp_host'] ?? 'smtp.gmail.com',
                'port'       => (int) ($meta['smtp_port'] ?? 587),
                'encryption' => $meta['smtp_encryption'] ?? 'tls',
                'username'   => $fromEmail,
                'password'   => $password,
            ],
        ]);

        $headers = [];
        if ($inReplyTo) {
            $headers['In-Reply-To'] = $inReplyTo;
            $headers['References']  = $inReplyTo;
        }

        Mail::mailer('email_platform')->raw(
            $message->content ?? '',
            function ($msg) use ($fromEmail, $toEmail, $subject, $headers) {
                $msg->from($fromEmail)->to($toEmail)->subject($subject);
                foreach ($headers as $name => $value) {
                    $msg->getHeaders()->addTextHeader($name, $value);
                }
            }
        );

        Log::info("sendViaEmail: sent from {$fromEmail} to {$toEmail} subject='{$subject}'");

        return null; // SMTP does not return a message ID
    }

    protected function sendViaMetaMessenger($page, string $recipientId, Message $message): ?string
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';

        if ($isInstagramBusiness) {
            // IG Business Login → graph.instagram.com.
            // Use /me/messages — the access token determines the sender, no ID guessing needed.
            // This sidesteps the IGBID-vs-legacy-id confusion that has caused send failures.
            $url = "https://graph.instagram.com/{$version}/me/messages";
        } elseif ($page->platform === 'instagram') {
            // IG via "Add via Meta" / FB Login. Must POST to the linked Facebook Page,
            // NOT the IG account ID. Endpoint shape: /{FB_PAGE_ID}/messages.
            $fbPageId = $page->metadata['linked_facebook_page_id'] ?? null;
            if (! $fbPageId) {
                throw new \RuntimeException('IG send (FB Login) requires metadata.linked_facebook_page_id on page '.$page->id);
            }
            $url = "https://graph.facebook.com/{$version}/{$fbPageId}/messages";
        } else {
            // Facebook Messenger.
            $url = "https://graph.facebook.com/{$version}/{$page->platform_page_id}/messages";
        }

        $payload = [
            'recipient' => ['id' => $recipientId],
            'messaging_type' => 'RESPONSE',
        ];

        if ($message->media_url) {
            $isImage = $message->content_type === 'image' || str_starts_with($message->media_type ?? '', 'image/');
            $payload['message'] = [
                'attachment' => [
                    'type' => $isImage ? 'image' : 'file',
                    'payload' => ['url' => $message->media_url, 'is_reusable' => true],
                ],
            ];
        } else {
            $payload['message'] = ['text' => $message->content];
        }

        $response = Http::withToken($page->page_access_token)->post($url, $payload);

        if ($response->successful()) {
            return $response->json('message_id');
        }

        Log::error('Meta send failed', [
            'status' => $response->status(),
            'body' => $response->body(),
            'url' => $url,
            'recipient' => $recipientId,
            'page_id' => $page->id,
        ]);
        $err = $response->json('error') ?? [];
        $code = $err['code'] ?? 'unknown';
        $sub = $err['error_subcode'] ?? '-';
        $msg = $err['message'] ?? 'Send failed';
        throw new \RuntimeException("Send failed (code {$code}/{$sub}): {$msg}");
    }

    protected function sendViaWhatsApp($page, string $recipientId, Message $message): ?string
    {
        // Route to Evolution API (QR gateway) if this page uses gateway mode
        // gateway_mode is set in Page.metadata when connected via QR scan
        if (! empty($page->metadata['gateway_mode'])) {
            return $this->sendViaEvolution($page, $recipientId, $message);
        }

        // Meta Cloud API (standard WhatsApp Business API)
        $version = config('services.meta.graph_api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$page->platform_page_id}/messages";

        if ($message->media_url) {
            $isImage = $message->content_type === 'image' || str_starts_with($message->media_type ?? '', 'image/');
            $type = $isImage ? 'image' : 'document';
            $mediaPayload = ['link' => $message->media_url];
            if ($message->content) {
                $mediaPayload['caption'] = $message->content;
            }

            $response = Http::withToken($page->page_access_token)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $recipientId,
                'type' => $type,
                $type => $mediaPayload,
            ]);
        } else {
            $response = Http::withToken($page->page_access_token)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $recipientId,
                'type' => 'text',
                'text' => ['body' => $message->content],
            ]);
        }

        if ($response->successful()) {
            return $response->json('messages.0.id');
        }

        Log::error('WhatsApp send failed', ['body' => $response->body()]);

        return null;
    }

    /**
     * Send via Evolution API (WhatsApp QR gateway mode).
     *
     * Instance name comes from metadata.gateway_instance (new connections use phone as platform_page_id).
     * Falls back to platform_page_id for legacy connections where instance name was stored there.
     * IF Evolution API send endpoint changes → update EvolutionApiService::sendText()
     */
    protected function sendViaEvolution($page, string $recipientId, Message $message): ?string
    {
        $instanceName   = $page->metadata['gateway_instance'] ?? $page->platform_page_id;
        $instanceApiKey = $page->page_access_token; // decrypted by Eloquent cast

        $evolution = app(EvolutionApiService::class);

        // Text-only for now; media send can be added to EvolutionApiService later
        $text = $message->content ?? '';
        if (empty($text) && $message->media_url) {
            $text = $message->media_url; // fallback: send URL as text
        }

        $messageId = $evolution->sendText($instanceName, $instanceApiKey, $recipientId, $text);

        if (! $messageId) {
            Log::error('Evolution send failed', ['instance' => $instanceName, 'to' => $recipientId]);
        }

        return $messageId;
    }

    /**
     * WebChat — no external API. The reply is already persisted in our DB; the
     * widget polls GET /api/webchat/{widget}/messages?since= and picks it up.
     * We just mint a stable platform_message_id so dedupe works.
     */
    protected function sendViaWebChat(Message $message): string
    {
        return 'wc_' . $message->id . '_' . now()->timestamp;
    }

    /**
     * Slack — POST chat.postMessage. recipientId is the Slack channel_id (DM, group, or channel).
     */
    protected function sendViaSlack($page, string $channelId, Message $message): ?string
    {
        $botToken = $page->page_access_token;

        $resp = Http::withToken($botToken)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => $channelId,
                'text'    => $message->content ?? '',
            ])->json();

        if (! ($resp['ok'] ?? false)) {
            Log::error('Slack send failed', ['channel' => $channelId, 'error' => $resp['error'] ?? null]);
            return null;
        }
        return (string) ($resp['ts'] ?? '');
    }

    /**
     * Discord — DM the user via REST.
     * Step 1: POST /users/@me/channels {recipient_id} → returns DM channel id
     * Step 2: POST /channels/{id}/messages {content}
     * recipientId here is the Discord user_id (Conversation.platform_conversation_id).
     */
    protected function sendViaDiscord($page, string $userId, Message $message): ?string
    {
        $botToken = $page->page_access_token;

        // Open (or reuse) the DM channel for this user.
        $dmResp = Http::withHeaders(['Authorization' => 'Bot ' . $botToken])
            ->post('https://discord.com/api/v10/users/@me/channels', [
                'recipient_id' => $userId,
            ]);

        if (! $dmResp->successful()) {
            Log::error('Discord DM channel open failed', ['user' => $userId, 'body' => $dmResp->body()]);
            return null;
        }
        $dmChannelId = (string) ($dmResp->json('id') ?? '');
        if ($dmChannelId === '') {
            return null;
        }

        $msgResp = Http::withHeaders(['Authorization' => 'Bot ' . $botToken])
            ->post("https://discord.com/api/v10/channels/{$dmChannelId}/messages", [
                'content' => $message->content ?? '',
            ]);

        if (! $msgResp->successful()) {
            Log::error('Discord DM send failed', ['user' => $userId, 'body' => $msgResp->body()]);
            return null;
        }

        return (string) ($msgResp->json('id') ?? '');
    }

    protected function sendViaTelegram($page, string $chatId, Message $message): ?string
    {
        $botToken = $page->page_access_token;

        if ($message->media_url) {
            $isImage = $message->content_type === 'image' || str_starts_with($message->media_type ?? '', 'image/');

            if ($isImage) {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => $message->media_url,
                    'caption' => $message->content,
                ]);
            } else {
                $response = Http::post("https://api.telegram.org/bot{$botToken}/sendDocument", [
                    'chat_id' => $chatId,
                    'document' => $message->media_url,
                    'caption' => $message->content,
                ]);
            }
        } else {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message->content,
            ]);
        }

        if ($response->successful()) {
            return (string) $response->json('result.message_id');
        }

        Log::error('Telegram send failed', ['body' => $response->body()]);

        return null;
    }
}
