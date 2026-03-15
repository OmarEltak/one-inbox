<?php

namespace App\Services\Platforms;

use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\ContactPlatform;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppPlatform extends AbstractPlatform
{
    protected string $graphUrl;

    public function __construct()
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $this->graphUrl = "https://graph.facebook.com/{$version}";
    }

    /**
     * WhatsApp doesn't use OAuth redirect - uses System User Token.
     * Returns empty string since we use a form-based flow instead.
     */
    public function getConnectUrl(): string
    {
        return '';
    }

    /**
     * Handle the WhatsApp connection using WABA ID + System User Token.
     * This is called from the controller after form submission.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $wabaId = $request->input('waba_id');
        $accessToken = $request->input('access_token');

        // Validate the token by fetching WABA details
        $wabaResponse = Http::withToken($accessToken)
            ->get("{$this->graphUrl}/{$wabaId}", [
                'fields' => 'id,name,currency,timezone_id,message_template_namespace',
            ])->throw()->json();

        // Create or update the connected account
        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id' => $teamId,
                'platform' => 'whatsapp',
                'platform_user_id' => $wabaId,
            ],
            [
                'name' => $wabaResponse['name'] ?? "WABA {$wabaId}",
                'access_token' => $accessToken,
                'scopes' => ['whatsapp_business_management', 'whatsapp_business_messaging'],
                'is_active' => true,
                'connected_at' => now(),
                'metadata' => [
                    'currency' => $wabaResponse['currency'] ?? null,
                    'timezone_id' => $wabaResponse['timezone_id'] ?? null,
                    'template_namespace' => $wabaResponse['message_template_namespace'] ?? null,
                ],
            ]
        );

        // Fetch and store phone numbers as pages
        $this->fetchPages($account);

        // Subscribe WABA to webhook
        $this->subscribeWebhook($account);

        return $account;
    }

    /**
     * Fetch all phone numbers registered under the WABA.
     * Each phone number becomes a "Page" in our system.
     */
    public function fetchPages(ConnectedAccount $account): Collection
    {
        $wabaId = $account->platform_user_id;

        $response = Http::withToken($account->access_token)
            ->get("{$this->graphUrl}/{$wabaId}/phone_numbers", [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,platform_type,status',
            ])->throw()->json();

        $pages = collect();

        foreach ($response['data'] ?? [] as $phoneData) {
            $page = Page::updateOrCreate(
                [
                    'team_id' => $account->team_id,
                    'platform' => 'whatsapp',
                    'platform_page_id' => $phoneData['id'], // phone_number_id
                ],
                [
                    'connected_account_id' => $account->id,
                    'name' => $phoneData['verified_name'] ?? $phoneData['display_phone_number'],
                    'page_access_token' => $account->access_token, // System User Token
                    'category' => 'whatsapp_business',
                    'is_active' => true,
                    'metadata' => [
                        'display_phone_number' => $phoneData['display_phone_number'] ?? null,
                        'quality_rating' => $phoneData['quality_rating'] ?? null,
                        'platform_type' => $phoneData['platform_type'] ?? null,
                        'status' => $phoneData['status'] ?? null,
                        'waba_id' => $account->platform_user_id,
                    ],
                ]
            );

            // Register the phone number for messaging if not already
            $this->registerPhoneNumber($page);

            $pages->push($page);
        }

        return $pages;
    }

    /**
     * Register a phone number for Cloud API messaging.
     */
    protected function registerPhoneNumber(Page $page): void
    {
        try {
            Http::withToken($page->page_access_token)
                ->post("{$this->graphUrl}/{$page->platform_page_id}/register", [
                    'messaging_product' => 'whatsapp',
                    'pin' => '000000', // Default 2FA PIN for Cloud API
                ]);
        } catch (\Throwable $e) {
            // Registration may fail if already registered - that's OK
            Log::info('WhatsApp phone registration result', [
                'phone_id' => $page->platform_page_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe WABA to receive webhook events.
     */
    protected function subscribeWebhook(ConnectedAccount $account): bool
    {
        $appId = config('services.meta.app_id');

        try {
            $response = Http::withToken($account->access_token)
                ->post("{$this->graphUrl}/{$account->platform_user_id}/subscribed_apps");

            if ($response->failed()) {
                Log::error('Failed to subscribe WABA to webhooks', [
                    'waba_id' => $account->platform_user_id,
                    'error' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WABA webhook subscription failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send a text message via WhatsApp Cloud API.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $recipientId = $conversation->platform_conversation_id;

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'text',
            'text' => ['body' => $content],
        ];

        if ($contentType === 'image' && $media) {
            $payload['type'] = 'image';
            $payload['image'] = ['link' => $media['url']];
            unset($payload['text']);
        }

        $response = Http::withToken($page->page_access_token)
            ->post("{$this->graphUrl}/{$page->platform_page_id}/messages", $payload);

        $platformMessageId = null;
        if ($response->successful()) {
            $platformMessageId = $response->json('messages.0.id');
        } else {
            Log::error('WhatsApp send failed', ['body' => $response->body()]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'platform_message_id' => $platformMessageId,
            'direction' => 'outbound',
            'sender_type' => 'user',
            'sender_id' => auth()->id(),
            'content_type' => $contentType,
            'content' => $content,
            'media_url' => $media['url'] ?? null,
            'media_type' => $contentType !== 'text' ? $contentType : null,
            'platform_sent_at' => now(),
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($content, 100),
        ]);

        return $message;
    }

    /**
     * Send a template message (for initiating conversations outside 24h window).
     */
    public function sendTemplate(Page $page, string $recipientPhone, string $templateName, string $languageCode = 'en', array $components = []): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        $response = Http::withToken($page->page_access_token)
            ->post("{$this->graphUrl}/{$page->platform_page_id}/messages", $payload);

        if ($response->successful()) {
            return $response->json('messages.0.id');
        }

        Log::error('WhatsApp template send failed', ['body' => $response->body()]);
        return null;
    }

    /**
     * Fetch message templates for a WABA.
     */
    public function fetchTemplates(ConnectedAccount $account): Collection
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->graphUrl}/{$account->platform_user_id}/message_templates", [
                'fields' => 'name,language,status,category,components',
                'limit' => 100,
            ]);

        if ($response->failed()) {
            Log::error('Failed to fetch WA templates', ['body' => $response->body()]);
            return collect();
        }

        return collect($response->json('data', []));
    }

    /**
     * WhatsApp doesn't have a traditional "fetch conversations" endpoint.
     * Conversations are created on-the-fly from incoming messages.
     */
    public function fetchConversations(Page $page): Collection
    {
        return collect();
    }

    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        return collect();
    }

    public function handleWebhook(Request $request): void
    {
        // Handled by MetaWebhookController + ProcessIncomingMessage job
    }

    public function verifyWebhook(Request $request): mixed
    {
        // Handled by MetaWebhookController
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'whatsapp')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
