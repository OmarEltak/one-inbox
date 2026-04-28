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

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $message = Message::find($this->messageId);
        if ($message) {
            $meta = $message->metadata ?? [];
            $meta['send_status'] = 'failed';
            $meta['send_error'] = $e->getMessage();
            $message->update(['metadata' => $meta]);
        }
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
            // IG Business Login → graph.instagram.com using the IGSID (what /me returns).
            $senderId = $page->metadata['igsid'] ?? $page->metadata['igbid'] ?? $page->platform_page_id;
            $url = "https://graph.instagram.com/{$version}/{$senderId}/messages";
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
