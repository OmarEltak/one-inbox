<?php

namespace App\Services\Platforms;

use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SnapchatPlatform extends AbstractPlatform
{
    protected string $authBase = 'https://accounts.snapchat.com';
    protected string $apiBase  = 'https://businessapi.snapchat.com/v1';

    protected function redirectUri(): string
    {
        return config('services.snapchat.redirect') ?: url('/connections/snapchat/callback');
    }

    /**
     * Build the Marketing API OAuth authorization URL.
     * Scope: snapchat-profile-api (required for Public Profile Messaging API)
     */
    public function getConnectUrl(): string
    {
        $params = http_build_query([
            'client_id'     => config('services.snapchat.marketing_client_id'),
            'redirect_uri'  => $this->redirectUri(),
            'response_type' => 'code',
            'scope'         => 'snapchat-profile-api',
            'state'         => csrf_token(),
        ]);

        return "{$this->authBase}/login/oauth2/authorize?{$params}";
    }

    /**
     * Exchange OAuth code for tokens, discover the brand's public profile, store account.
     */
    public function handleCallback(Request $request, int $teamId): ConnectedAccount
    {
        $code = $request->input('code');

        $tokenResponse = Http::asForm()->withBasicAuth(
            config('services.snapchat.marketing_client_id'),
            config('services.snapchat.marketing_client_secret')
        )->post("{$this->authBase}/login/oauth2/access_token", [
            'code'         => $code,
            'grant_type'   => 'authorization_code',
            'redirect_uri' => $this->redirectUri(),
        ]);

        if (! $tokenResponse->successful()) {
            Log::error('Snapchat Marketing API token exchange failed', [
                'status' => $tokenResponse->status(),
                'body'   => $tokenResponse->body(),
            ]);
            throw new \RuntimeException('Snapchat token exchange failed: ' . $tokenResponse->body());
        }

        $tokenData    = $tokenResponse->json();
        $accessToken  = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;

        // Attempt to discover the brand's own public profile via username
        [$profileId, $displayName] = $this->discoverOwnProfile($accessToken);

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'snapchat',
                'platform_user_id' => $profileId ?? Str::uuid(),
            ],
            [
                'name'          => $displayName,
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'scopes'        => ['snapchat-profile-api'],
                'is_active'     => true,
                'connected_at'  => now(),
                'metadata'      => ['profile_id' => $profileId],
            ]
        );

        Page::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'snapchat',
                'platform_page_id' => $profileId ?? $account->platform_user_id,
            ],
            [
                'connected_account_id' => $account->id,
                'name'                 => $displayName,
                'page_access_token'    => $accessToken,
                'category'             => 'snapchat_business',
                'is_active'            => true,
                'metadata'             => ['profile_id' => $profileId],
            ]
        );

        return $account;
    }

    /**
     * Try to discover this account's own public profile.
     * Returns [profile_id, display_name].
     */
    protected function discoverOwnProfile(string $accessToken): array
    {
        // Try Snap user info endpoint first to get username
        $meResponse = Http::withToken($accessToken)
            ->get("{$this->authBase}/accounts/me");

        if ($meResponse->successful()) {
            $username = $meResponse->json('data.me.displayName')
                ?? $meResponse->json('username')
                ?? null;

            if ($username) {
                $discoverResponse = Http::withToken($accessToken)
                    ->get('https://businessapi.snapchat.com/public/v1/public_profiles/discover', [
                        'query' => $username,
                    ]);

                if ($discoverResponse->successful()) {
                    $profiles = $discoverResponse->json('public_profiles', []);
                    if (! empty($profiles)) {
                        $profile = $profiles[0];
                        return [
                            $profile['id'] ?? null,
                            $profile['display_name'] ?? $profile['username'] ?? 'Snapchat Business',
                        ];
                    }
                }
            }
        }

        Log::warning('Snapchat: could not auto-discover public profile_id — manual configuration may be needed');

        return [null, 'Snapchat Business'];
    }

    /**
     * Refresh an expired access token using the stored refresh token.
     */
    public function refreshToken(Page $page): ?string
    {
        $account = $page->connectedAccount;
        if (! $account?->refresh_token) {
            return null;
        }

        $response = Http::asForm()->withBasicAuth(
            config('services.snapchat.marketing_client_id'),
            config('services.snapchat.marketing_client_secret')
        )->post("{$this->authBase}/login/oauth2/access_token", [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);

        if (! $response->successful()) {
            Log::error('Snapchat token refresh failed', ['status' => $response->status()]);
            return null;
        }

        $newToken = $response->json('access_token');
        $account->update(['access_token' => $newToken]);
        $page->update(['page_access_token' => $newToken]);

        return $newToken;
    }

    /**
     * Send a text message to a creator on behalf of the brand.
     *
     * Requires: conversation already initiated via startConversation().
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $profileId      = $page->metadata['profile_id'] ?? null;
        $conversationId = $conversation->metadata['conversation_id'] ?? null;

        $platformMessageId = null;

        if ($profileId && $conversationId) {
            $token = "SnapProfileId/{$profileId}";

            $response = Http::withToken($page->page_access_token)
                ->post("{$this->apiBase}/public_profiles/{$profileId}/group_conversation_messages", [
                    'conversation_id'            => $conversationId,
                    'token'                      => $token,
                    'group_conversation_messages' => [[
                        'type'         => 'TEXT',
                        'text_message' => $content,
                        'message_id'   => (string) Str::uuid(),
                    ]],
                ]);

            if ($response->successful()) {
                $sent              = $response->json('group_conversation_messages.0.group_conversation_message');
                $platformMessageId = $sent['message_id'] ?? null;
            } else {
                Log::error('Snapchat sendMessage failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        }

        $message = Message::create([
            'conversation_id'     => $conversation->id,
            'platform_message_id' => $platformMessageId,
            'direction'           => 'outbound',
            'sender_type'         => 'user',
            'sender_id'           => auth()->id(),
            'content_type'        => 'text',
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
     * Get or create a conversation between the brand and a creator.
     * Returns the conversation_id and token for subsequent message calls.
     */
    public function startConversation(Page $page, string $creatorProfileId): ?array
    {
        $profileId = $page->metadata['profile_id'] ?? null;
        if (! $profileId) {
            return null;
        }

        $response = Http::withToken($page->page_access_token)
            ->get("{$this->apiBase}/public_profiles/{$profileId}/group_conversation", [
                'creator_profile_id' => $creatorProfileId,
            ]);

        if (! $response->successful()) {
            Log::error('Snapchat startConversation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        }

        return $response->json('conversation');
    }

    /**
     * Poll new messages for a known conversation.
     * Stores any new messages in the database.
     */
    public function pollMessages(Page $page, Conversation $conversation): void
    {
        $profileId      = $page->metadata['profile_id'] ?? null;
        $conversationId = $conversation->metadata['conversation_id'] ?? null;

        if (! $profileId || ! $conversationId) {
            return;
        }

        $token = "SnapProfileId/{$profileId}";

        $response = Http::withToken($page->page_access_token)
            ->get("{$this->apiBase}/public_profiles/{$profileId}/group_conversation_messages", [
                'conversation_id' => $conversationId,
                'token'           => $token,
                'limit'           => 50,
            ]);

        if (! $response->successful()) {
            // Try refreshing token once
            $newToken = $this->refreshToken($page);
            if (! $newToken) {
                return;
            }

            $response = Http::withToken($newToken)
                ->get("{$this->apiBase}/public_profiles/{$profileId}/group_conversation_messages", [
                    'conversation_id' => $conversationId,
                    'token'           => $token,
                    'limit'           => 50,
                ]);

            if (! $response->successful()) {
                return;
            }
        }

        $messages = $response->json('group_conversation_messages', []);

        foreach ($messages as $item) {
            $msg = $item['group_conversation_message'] ?? null;
            if (! $msg) {
                continue;
            }

            $platformMessageId = $msg['message_id'] ?? null;
            if (! $platformMessageId) {
                continue;
            }

            // Skip if already stored
            if (Message::where('platform_message_id', $platformMessageId)->exists()) {
                continue;
            }

            $text = $msg['text_message'] ?? '';

            Message::create([
                'conversation_id'     => $conversation->id,
                'platform_message_id' => $platformMessageId,
                'direction'           => 'inbound',
                'sender_type'         => 'contact',
                'sender_id'           => $conversation->contact_id,
                'content_type'        => 'text',
                'content'             => $text,
                'platform_sent_at'    => now(),
            ]);

            $conversation->update([
                'last_message_at'      => now(),
                'last_message_preview' => Str::limit($text, 100),
            ]);
        }
    }

    /**
     * Returns stored Snapchat conversations for this page (poll-based).
     */
    public function fetchConversations(Page $page): Collection
    {
        return $page->conversations()->with('contact')->get();
    }

    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        return collect();
    }

    public function fetchPages(ConnectedAccount $account): Collection
    {
        return $account->pages()->where('platform', 'snapchat')->get();
    }

    public function handleWebhook(Request $request): void
    {
        // No webhook support — polling only
    }

    public function verifyWebhook(Request $request): mixed
    {
        return null;
    }

    public function disconnect(ConnectedAccount $account): void
    {
        $account->pages()->where('platform', 'snapchat')->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }
}
