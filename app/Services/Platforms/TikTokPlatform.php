<?php

namespace App\Services\Platforms;

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokPlatform extends AbstractPlatform
{
    protected string $apiBase = 'https://business-api.tiktok.com/open_api/v1.3';

    /**
     * Build the OAuth authorization URL.
     * Redirect users here to connect their TikTok Business account.
     */
    public function getConnectUrl(): string
    {
        $params = http_build_query([
            'client_key'    => config('services.tiktok.client_key'),
            'response_type' => 'code',
            'scope'         => 'message.list.read,message.list.send,message.list.manage',
            'redirect_uri'  => url('/connections/tiktok/callback'),
            'state'         => csrf_token(),
        ]);

        return "https://www.tiktok.com/v2/auth/authorize?{$params}";
    }

    /**
     * Exchange OAuth code for access token and store as page.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $code = $request->input('code');

        // Exchange code for access token
        $tokenResponse = Http::post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key'    => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => url('/connections/tiktok/callback'),
        ])->throw()->json();

        $accessToken  = $tokenResponse['access_token'];
        $refreshToken = $tokenResponse['refresh_token'] ?? null;
        $openId       = $tokenResponse['open_id'];

        // Fetch user info
        $userResponse = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->get('https://open.tiktokapis.com/v2/user/info/', [
            'fields' => 'open_id,display_name,avatar_url',
        ])->throw()->json();

        $userInfo = $userResponse['data']['user'] ?? [];
        $displayName = $userInfo['display_name'] ?? 'TikTok Account';
        $avatarUrl   = $userInfo['avatar_url'] ?? null;

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'tiktok',
                'platform_user_id' => $openId,
            ],
            [
                'name'          => $displayName,
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'scopes'        => ['message.list.read', 'message.list.send', 'message.list.manage'],
                'is_active'     => true,
                'connected_at'  => now(),
                'metadata'      => ['avatar_url' => $avatarUrl],
            ]
        );

        Page::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'tiktok',
                'platform_page_id' => $openId,
            ],
            [
                'connected_account_id' => $account->id,
                'name'                 => $displayName,
                'page_access_token'    => $accessToken,
                'category'             => 'tiktok_business',
                'is_active'            => true,
                'metadata'             => ['avatar_url' => $avatarUrl],
            ]
        );

        return $account;
    }

    /**
     * Send a text message via TikTok Business Messaging API.
     *
     * NOTE: Businesses can only reply — they cannot initiate conversations.
     * Max 10 business-initiated messages per 48-hour window per user.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $recipientId = $conversation->platform_conversation_id;
        $accessToken = $page->page_access_token;

        $body = [
            'recipient_user_id' => $recipientId,
            'message_type'      => 'text',
            'message'           => ['text' => $content],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->apiBase}/direct_message/send/", $body);

        if (! $response->successful()) {
            Log::error('TikTok send message failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('TikTok send message failed: ' . $response->body());
        }

        $platformMessageId = (string) ($response->json('data.message_id') ?? Str::uuid());

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $platformMessageId,
            'direction'           => 'outbound',
            'sender_type'         => 'user',
            'sender_id'           => auth()->id(),
            'content_type'        => $contentType,
            'content'             => $content,
            'platform_sent_at'    => now(),
        ]);

        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => Str::limit($content, 100),
        ]);

        return $message;
    }

    /**
     * TikTok does not provide a "list conversations" API endpoint.
     * Conversations are created from incoming webhook events.
     */
    public function fetchConversations(Page $page): Collection
    {
        return collect();
    }

    /**
     * Fetch messages for a conversation using the message.list.read scope.
     * TikTok Business Messaging API returns messages for a given conversation (sender open_id).
     */
    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        $accessToken = $page->page_access_token;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->get("{$this->apiBase}/direct_message/list/", [
            'sender_open_id' => $platformConversationId,
            'page_size'      => 20,
        ]);

        if (! $response->successful()) {
            Log::warning('TikTok fetchMessages failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return collect();
        }

        return collect($response->json('data.messages') ?? []);
    }

    public function fetchPages(ConnectedAccount $account): Collection
    {
        return $account->pages()->where('platform', 'tiktok')->get();
    }

    public function handleWebhook(Request $request): void
    {
        // Handled by TikTokWebhookController + ProcessIncomingMessage job
    }

    public function verifyWebhook(Request $request): mixed
    {
        // Handled by TikTokWebhookController
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'tiktok')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
