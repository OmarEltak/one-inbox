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

class FacebookPlatform extends AbstractPlatform
{
    protected string $graphUrl;
    protected string $appId;
    protected string $appSecret;
    protected string $instagramAppId;
    protected string $instagramAppSecret;

    public function __construct()
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $this->graphUrl = "https://graph.facebook.com/{$version}";
        $this->appId = config('services.meta.app_id', '');
        $this->appSecret = config('services.meta.app_secret', '');
        $this->instagramAppId = config('services.meta.instagram_app_id', $this->appId);
        $this->instagramAppSecret = config('services.meta.instagram_app_secret', $this->appSecret);
    }

    /**
     * Build the Facebook Login OAuth URL.
     */
    public function getConnectUrl(): string
    {
        $redirectUri = route('connections.facebook.callback');

        $state = csrf_token();
        session(['meta_oauth_state' => $state]);

        return "https://www.facebook.com/{$this->graphVersion()}/dialog/oauth?"
            . http_build_query([
                'client_id' => $this->appId,
                'redirect_uri' => $redirectUri,
                'scope' => 'pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement',
                'response_type' => 'code',
                'state' => $state,
            ]);
    }

    /**
     * Build the Facebook Login OAuth URL that also requests instagram_manage_messages.
     * This detects any Instagram Business account linked to a Facebook Page and subscribes
     * it to webhooks — no app review required (0 requirements).
     */
    public function getInstagramViaFacebookConnectUrl(): string
    {
        $redirectUri = route('connections.instagram-via-facebook.callback');

        $state = csrf_token();
        session(['instagram_via_fb_oauth_state' => $state]);

        return "https://www.facebook.com/{$this->graphVersion()}/dialog/oauth?"
            . http_build_query([
                'client_id'     => $this->appId,
                'redirect_uri'  => $redirectUri,
                'scope'         => 'pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_utility_messaging,business_management,instagram_basic,instagram_manage_messages,instagram_manage_comments',
                'response_type' => 'code',
                'state'         => $state,
            ]);
    }

    /**
     * Handle the Instagram-via-Facebook OAuth callback.
     * Fetches FB pages, then detects any linked Instagram accounts per page.
     */
    public function handleInstagramViaFacebookCallback(Request $request, int $teamId): ConnectedAccount
    {
        $account = $this->handleCallback($request, $teamId, route('connections.instagram-via-facebook.callback'));

        foreach ($account->pages()->where('platform', 'facebook')->get() as $fbPage) {
            $igPage = $this->detectInstagramAccount($fbPage, $account);

            // Re-subscribe the FB page to messaging webhook fields
            Http::withToken($fbPage->page_access_token)
                ->post("{$this->graphUrl}/{$fbPage->platform_page_id}/subscribed_apps", [
                    'subscribed_fields' => 'messages,message_deliveries,message_reads,messaging_postbacks',
                ]);

            if ($igPage) {
                \App\Jobs\SyncPageConversations::dispatch($igPage);
            }
        }

        return $account;
    }

    /**
     * Build the Instagram Business Login OAuth URL.
     * Uses instagram.com/oauth/authorize — works for Instagram Business/Creator accounts
     * even without a linked Facebook page.
     */
    public function getInstagramConnectUrl(): string
    {
        $redirectUri = route('connections.instagram.callback');

        $state = csrf_token();
        session(['instagram_oauth_state' => $state]);

        $params = [
            'client_id'     => $this->instagramAppId,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'instagram_business_basic,instagram_business_manage_messages',
            'response_type' => 'code',
            'state'         => $state,
        ];

        $configId = config('services.meta.login_config_id');
        if ($configId) {
            $params['config_id'] = $configId;
        }

        return 'https://www.instagram.com/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Handle Instagram Business Login callback.
     * Exchanges code via api.instagram.com, upgrades to long-lived token via graph.instagram.com.
     */
    public function handleInstagramCallback(Request $request, int $teamId): ConnectedAccount
    {
        $code = $request->input('code');

        // Step 1: Short-lived token from api.instagram.com
        $tokenResponse = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id'     => $this->instagramAppId,
            'client_secret' => $this->instagramAppSecret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => route('connections.instagram.callback'),
            'code'          => $code,
        ])->throw()->json();

        $shortLivedToken = $tokenResponse['access_token'];
        $igUserId = (string) $tokenResponse['user_id'];
        $igUsername = $tokenResponse['username'] ?? null;

        // Step 2: Exchange for long-lived token (~60 days)
        $longLivedToken = $shortLivedToken;
        $expiresIn = $tokenResponse['expires_in'] ?? 5184000;

        try {
            $longLivedResponse = Http::get('https://graph.instagram.com/access_token', [
                'grant_type'    => 'ig_exchange_token',
                'client_id'     => $this->instagramAppId,
                'client_secret' => $this->instagramAppSecret,
                'access_token'  => $shortLivedToken,
            ])->json();

            if (isset($longLivedResponse['access_token'])) {
                $longLivedToken = $longLivedResponse['access_token'];
                $expiresIn = $longLivedResponse['expires_in'] ?? $expiresIn;
            }
        } catch (\Throwable $e) {
            Log::info('Instagram long-lived token exchange skipped', [
                'ig_user_id' => $igUserId,
                'error'      => $e->getMessage(),
            ]);
        }

        // Step 3: Fetch Instagram user profile
        $profileResp = Http::withToken($longLivedToken)
            ->get('https://graph.instagram.com/me', ['fields' => 'id,username,name,profile_picture_url']);

        $profile = $profileResp->successful() ? $profileResp->json() : [
            'id'       => $igUserId,
            'username' => $igUsername,
            'name'     => $igUsername,
        ];

        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id'          => $teamId,
                'platform'         => 'instagram',
                'platform_user_id' => $igUserId,
            ],
            [
                'name'             => $profile['name'] ?? $profile['username'] ?? 'Instagram',
                'email'            => null,
                'access_token'     => $longLivedToken,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'scopes'           => ['instagram_business_basic', 'instagram_business_manage_messages'],
                'is_active'        => true,
                'connected_at'     => now(),
            ]
        );

        $page = Page::updateOrCreate(
            [
                'team_id'          => $account->team_id,
                'platform'         => 'instagram',
                'platform_page_id' => $igUserId,
            ],
            [
                'connected_account_id' => $account->id,
                'name'                 => $profile['name'] ?? $profile['username'] ?? 'Instagram',
                'avatar'               => $profile['profile_picture_url'] ?? null,
                'page_access_token'    => $longLivedToken,
                'category'             => 'instagram_business',
                'is_active'            => true,
                'metadata'             => [
                    'username'  => $profile['username'] ?? null,
                    'auth_type' => 'instagram_business',
                    // Two-ID system for IG Business Login:
                    //   igsid = ID returned by graph.instagram.com/me — used in send URL.
                    //   igbid = canonical Instagram User ID used in webhook entry.id.
                    //          Only learned once the first webhook arrives — until then
                    //          we mirror the IGSID here so legacy lookups still work.
                    // platform_page_id is rewritten to the IGBID by self-heal in
                    // ProcessIncomingMessage when the first webhook lands.
                    'igsid'     => $profile['id'] ?? $igUserId,
                    'igbid'     => $profile['id'] ?? $igUserId,
                ],
            ]
        );

        $this->subscribeInstagramPage($page);
        \App\Jobs\SyncPageConversations::dispatch($page);

        return $account;
    }

    /**
     * Subscribe an Instagram Business Login page to webhook message events.
     */
    public function subscribeInstagramPage(Page $page): bool
    {
        $version = $this->graphVersion();

        // Use IGBID for the subscription API (graph.instagram.com expects the IGBID, not the
        // legacy Instagram User ID that webhooks use for routing). Falls back to platform_page_id
        // for accounts connected before the igbid metadata field was introduced.
        $igbid = $page->metadata['igbid'] ?? $page->platform_page_id;

        $response = Http::post("https://graph.instagram.com/{$version}/{$igbid}/subscribed_apps", [
            'subscribed_fields' => 'messages',
            'access_token'      => $page->page_access_token,
        ]);

        if ($response->failed()) {
            Log::error('Failed to subscribe Instagram user to webhooks', [
                'ig_user_id' => $page->platform_page_id,
                'error'      => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Handle OAuth callback: exchange code for tokens, fetch pages.
     */
    public function handleCallback(Request $request, int $teamId, ?string $redirectUri = null): ConnectedAccount
    {
        $code = $request->input('code');
        $redirectUri ??= route('connections.facebook.callback');

        // Exchange code for short-lived user access token
        $tokenResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ])->throw()->json();

        $shortLivedToken = $tokenResponse['access_token'];

        // Exchange for long-lived token (~60 days)
        $longLivedResponse = Http::get("{$this->graphUrl}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ])->throw()->json();

        $longLivedToken = $longLivedResponse['access_token'];
        $expiresIn = $longLivedResponse['expires_in'] ?? 5184000; // default 60 days

        // Fetch user profile
        $profile = Http::withToken($longLivedToken)
            ->get("{$this->graphUrl}/me", ['fields' => 'id,name,email'])
            ->throw()->json();

        // Create or update the connected account
        $account = ConnectedAccount::updateOrCreate(
            [
                'team_id' => $teamId,
                'platform' => 'facebook',
                'platform_user_id' => $profile['id'],
            ],
            [
                'name' => $profile['name'],
                'email' => $profile['email'] ?? null,
                'access_token' => $longLivedToken,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'scopes' => ['pages_messaging', 'pages_manage_metadata', 'pages_show_list', 'pages_read_engagement'],
                'is_active' => true,
                'connected_at' => now(),
            ]
        );

        // Fetch and store pages
        $this->fetchPages($account);

        return $account;
    }

    /**
     * Fetch all pages the user manages and store them.
     * Page access tokens derived from long-lived user tokens are permanent.
     */
    public function fetchPages(ConnectedAccount $account): Collection
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->graphUrl}/me/accounts", [
                'fields' => 'id,name,access_token,category,picture',
                'limit' => 100,
            ])->throw()->json();

        $pages = collect();

        foreach ($response['data'] ?? [] as $pageData) {
            $page = Page::updateOrCreate(
                [
                    'team_id' => $account->team_id,
                    'platform' => 'facebook',
                    'platform_page_id' => $pageData['id'],
                ],
                [
                    'connected_account_id' => $account->id,
                    'name' => $pageData['name'],
                    'avatar' => $pageData['picture']['data']['url'] ?? null,
                    'page_access_token' => $pageData['access_token'],
                    'category' => $pageData['category'] ?? null,
                    'is_active' => true,
                ]
            );

            // Subscribe page to webhook events
            $subscribed = $this->subscribePage($page);

            // If subscription failed due to 2FA, mark the page so the UI can warn the user
            if (! $subscribed) {
                $meta = $page->metadata ?? [];
                $meta['subscription_error'] = 'twofa_required';
                $page->update(['metadata' => $meta]);
            } else {
                // Clear any previous error
                $meta = $page->metadata ?? [];
                unset($meta['subscription_error']);
                $page->update(['metadata' => $meta]);
            }

            // Pull existing conversations in background to avoid timeout
            \App\Jobs\SyncPageConversations::dispatch($page);

            $pages->push($page);
        }

        return $pages;
    }

    /**
     * Re-subscribe the Facebook page linked to a Facebook-via-Instagram page to ensure
     * messaging webhook delivery remains active. Safe to call repeatedly.
     *
     * Uses the Facebook page's own token rather than the token stored on the Instagram
     * page record, so it remains correct even if token storage conventions change.
     */
    public function refreshFacebookSubscription(Page $page): bool
    {
        $fbPageId = $page->metadata['linked_facebook_page_id'] ?? null;

        if (! $fbPageId) {
            return false;
        }

        // Load the canonical FB page record to use its own token
        $fbPage = Page::where('platform', 'facebook')
            ->where('platform_page_id', $fbPageId)
            ->where('team_id', $page->team_id)
            ->first();

        $token = $fbPage?->page_access_token ?? $page->page_access_token;

        if (! $token) {
            Log::warning('refreshFacebookSubscription: no valid token found', [
                'ig_page_id' => $page->id,
                'fb_page_id' => $fbPageId,
            ]);

            return false;
        }

        $response = Http::withToken($token)
            ->post("{$this->graphUrl}/{$fbPageId}/subscribed_apps", [
                'subscribed_fields' => 'messages,message_deliveries,message_reads,messaging_postbacks',
            ]);

        if ($response->failed()) {
            Log::error('Failed to refresh Facebook subscription for Instagram page', [
                'page_id'     => $page->id,
                'fb_page_id'  => $fbPageId,
                'error'       => $response->body(),
            ]);

            $page->update(['metadata' => array_merge($page->metadata ?? [], ['subscription_error' => 'refresh_failed'])]);

            return false;
        }

        $page->update(['metadata' => array_merge(
            array_diff_key($page->metadata ?? [], ['subscription_error' => true]),
            ['subscription_refreshed_at' => now()->toISOString()]
        )]);

        return true;
    }

    /**
     * Subscribe a page to receive webhook events.
     */
    public function subscribePage(Page $page): bool
    {
        $response = Http::withToken($page->page_access_token)
            ->post("{$this->graphUrl}/{$page->platform_page_id}/subscribed_apps", [
                'subscribed_fields' => 'messages,message_deliveries,message_reads,messaging_postbacks',
            ]);

        if ($response->failed()) {
            Log::error('Failed to subscribe page to webhooks', [
                'page_id' => $page->platform_page_id,
                'error' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Detect and store linked Instagram Professional account for a FB page.
     */
    public function detectInstagramAccount(Page $fbPage, ConnectedAccount $account): ?Page
    {
        try {
            $response = Http::withToken($fbPage->page_access_token)
                ->get("{$this->graphUrl}/{$fbPage->platform_page_id}", [
                    'fields' => 'instagram_business_account{id,name,username,profile_picture_url}',
                ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();
            $igAccount = $data['instagram_business_account'] ?? null;

            if (! $igAccount) {
                return null;
            }

            // Store Instagram as a separate page linked to the same connected account
            // Uses the Facebook page access token (IG DMs route through FB)
            return Page::updateOrCreate(
                [
                    'team_id' => $account->team_id,
                    'platform' => 'instagram',
                    'platform_page_id' => $igAccount['id'],
                ],
                [
                    'connected_account_id' => $account->id,
                    'name' => $igAccount['name'] ?? $igAccount['username'] ?? 'Instagram',
                    'avatar' => $igAccount['profile_picture_url'] ?? null,
                    'page_access_token' => $fbPage->page_access_token, // Same token
                    'category' => 'instagram_business',
                    'is_active' => true,
                    'metadata' => [
                        'username' => $igAccount['username'] ?? null,
                        'linked_facebook_page_id' => $fbPage->platform_page_id,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            Log::info('No Instagram account linked to FB page', [
                'page' => $fbPage->name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a message via Facebook Messenger or Instagram DM.
     */
    public function sendMessage(Page $page, Conversation $conversation, string $content, string $contentType = 'text', ?array $media = null): Message
    {
        $recipientId = $conversation->platform_conversation_id;

        $payload = [
            'recipient' => ['id' => $recipientId],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $content],
        ];

        if ($contentType === 'image' && $media) {
            $payload['message'] = [
                'attachment' => [
                    'type' => 'image',
                    'payload' => ['url' => $media['url'], 'is_reusable' => true],
                ],
            ];
        }

        $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';
        $senderId = $isInstagramBusiness
            ? ($page->metadata['igsid'] ?? $page->platform_page_id)
            : $page->platform_page_id;
        $sendUrl = $isInstagramBusiness
            ? "https://graph.instagram.com/{$this->graphVersion()}/{$senderId}/messages"
            : "{$this->graphUrl}/{$senderId}/messages";

        $response = Http::withToken($page->page_access_token)->post($sendUrl, $payload);

        if (! $response->successful()) {
            $body = $response->body();
            Log::error('Facebook send failed', [
                'status' => $response->status(),
                'body' => $body,
                'send_url' => $sendUrl,
                'page_id' => $page->id,
                'recipient' => $recipientId,
            ]);
            $err = $response->json('error') ?? [];
            $code = $err['code'] ?? 'unknown';
            $sub = $err['error_subcode'] ?? '-';
            $msg = $err['message'] ?? 'Send failed';
            throw new \RuntimeException("Instagram send failed (code {$code}/{$sub}): {$msg}");
        }

        $platformMessageId = $response->json('message_id');

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
            'last_message_preview' => \Illuminate\Support\Str::limit($content, 100),
        ]);

        return $message;
    }

    /**
     * Fetch existing conversations from a page.
     */
    public function fetchConversations(Page $page): Collection
    {
        $platform = $page->platform === 'instagram' ? 'instagram' : 'messenger';
        $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';
        $baseUrl = $isInstagramBusiness
            ? "https://graph.instagram.com/{$this->graphVersion()}"
            : $this->graphUrl;
        $conversations = collect();
        $nextUrl = null;

        do {
            $response = $nextUrl
                ? Http::withToken($page->page_access_token)->get($nextUrl)
                : Http::withToken($page->page_access_token)
                    ->get("{$baseUrl}/{$page->platform_page_id}/conversations", [
                        'fields' => 'id,participants,updated_time,snippet',
                        'platform' => $platform,
                        'limit' => 50,
                    ]);

            if ($response->failed()) {
                Log::error('Failed to fetch conversations', ['page' => $page->name, 'body' => $response->body()]);
                break;
            }

            $pageUsername = $page->metadata['username'] ?? null;

            foreach ($response->json('data', []) as $convData) {
                $participant = collect($convData['participants']['data'] ?? [])
                    ->first(fn ($p) => $p['id'] !== $page->platform_page_id
                        && (! $pageUsername || ($p['username'] ?? null) !== $pageUsername));

                if (! $participant) {
                    continue;
                }

                $contactPlatform = ContactPlatform::where('platform', $page->platform)
                    ->where('platform_contact_id', $participant['id'])
                    ->first();

                $contact = $contactPlatform?->contact;

                if (! $contact) {
                    $displayName = $participant['name'] ?? $participant['username'] ?? null;
                    $contact = Contact::create([
                        'team_id' => $page->team_id,
                        'name' => $displayName,
                        'first_seen_at' => now(),
                        'last_interaction_at' => now(),
                    ]);

                    ContactPlatform::create([
                        'contact_id' => $contact->id,
                        'platform' => $page->platform,
                        'platform_contact_id' => $participant['id'],
                        'platform_name' => $displayName,
                    ]);
                }

                $conversation = Conversation::updateOrCreate(
                    [
                        'team_id' => $page->team_id,
                        'platform' => $page->platform,
                        'platform_conversation_id' => $participant['id'],
                    ],
                    [
                        'page_id' => $page->id,
                        'contact_id' => $contact->id,
                        'last_message_at' => $convData['updated_time'] ?? now(),
                        'last_message_preview' => $convData['snippet'] ?? null,
                        'status' => 'open',
                    ]
                );

                $conversations->push($conversation);
            }

            $nextUrl = $response->json('paging.next');

        } while ($nextUrl);

        return $conversations;
    }

    /**
     * Fetch messages for a conversation (raw API data).
     */
    public function fetchMessages(Page $page, string $platformConversationId): Collection
    {
        // For Meta, we need the conversation ID (t_xxx format), not participant ID
        // In our system, platform_conversation_id stores the participant PSID
        // We need to find the conversation first
        $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';
        $baseUrl = $isInstagramBusiness
            ? "https://graph.instagram.com/{$this->graphVersion()}"
            : $this->graphUrl;

        $response = Http::withToken($page->page_access_token)
            ->get("{$baseUrl}/{$page->platform_page_id}/conversations", [
                'fields' => 'id,messages{message,from,created_time,attachments}',
                'user_id' => $platformConversationId,
                'limit' => 1,
            ]);

        if ($response->failed()) {
            return collect();
        }

        $convData = $response->json('data.0');

        if (! $convData) {
            return collect();
        }

        return collect($convData['messages']['data'] ?? []);
    }

    /**
     * Fetch messages from Facebook API and persist them to DB.
     * Returns the number of messages stored.
     */
    public function fetchAndStoreMessages(Conversation $conversation): int
    {
        $page = $conversation->page;

        if (! $page) {
            return 0;
        }

        $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';
        $baseUrl = $isInstagramBusiness
            ? "https://graph.instagram.com/{$this->graphVersion()}"
            : $this->graphUrl;

        $response = Http::withToken($page->page_access_token)
            ->get("{$baseUrl}/{$page->platform_page_id}/conversations", [
                'fields' => 'id,messages.limit(25){message,from,created_time,attachments}',
                'user_id' => $conversation->platform_conversation_id,
                'limit' => 1,
            ]);

        if ($response->failed()) {
            Log::error('Failed to fetch messages from Facebook', [
                'conversation_id' => $conversation->id,
                'body' => $response->body(),
            ]);

            return 0;
        }

        $convData = $response->json('data.0');

        if (! $convData) {
            return 0;
        }

        $messagesData = $convData['messages']['data'] ?? [];
        $stored = 0;

        $pageUsername = $page->metadata['username'] ?? null;

        foreach ($messagesData as $msgData) {
            $platformMessageId = $msgData['id'] ?? null;

            if (! $platformMessageId) {
                continue;
            }

            // Determine direction: outbound if sent by the page owner
            $fromId = $msgData['from']['id'] ?? null;
            $fromUsername = $msgData['from']['username'] ?? null;
            $isFromPage = $fromId === $page->platform_page_id
                || ($pageUsername && $fromUsername === $pageUsername);
            $direction = $isFromPage ? 'outbound' : 'inbound';
            $senderType = $direction === 'inbound' ? 'contact' : 'user';

            // Extract attachment media if present
            $mediaUrl = null;
            $contentType = 'text';
            $mediaType = null;
            $attachments = $msgData['attachments']['data'] ?? [];
            if (! empty($attachments)) {
                $att = $attachments[0];
                if (isset($att['image_data']['url'])) {
                    $mediaUrl = $att['image_data']['url'];
                    $contentType = 'image';
                    $mediaType = $att['mime_type'] ?? 'image/jpeg';
                } elseif (isset($att['video_data']['url'])) {
                    $mediaUrl = $att['video_data']['url'];
                    $contentType = 'video';
                    $mediaType = $att['mime_type'] ?? 'video/mp4';
                } elseif (isset($att['audio_data']['url'])) {
                    $mediaUrl = $att['audio_data']['url'];
                    $contentType = 'audio';
                    $mediaType = $att['mime_type'] ?? 'audio/mpeg';
                } elseif (isset($att['file_url'])) {
                    $mediaUrl = $att['file_url'];
                    $contentType = 'file';
                    $mediaType = $att['mime_type'] ?? null;
                }
            }

            $created = Message::firstOrCreate(
                ['platform_message_id' => $platformMessageId],
                [
                    'conversation_id' => $conversation->id,
                    'direction' => $direction,
                    'sender_type' => $senderType,
                    'content_type' => $contentType,
                    'content' => $msgData['message'] ?? null,
                    'media_url' => $mediaUrl,
                    'media_type' => $mediaType,
                    'platform_sent_at' => isset($msgData['created_time'])
                        ? \Carbon\Carbon::parse($msgData['created_time'])
                        : now(),
                ]
            );

            if ($created->wasRecentlyCreated) {
                $stored++;
            }
        }

        // Mark messages as fetched in conversation metadata
        $metadata = $conversation->metadata ?? [];
        $metadata['messages_fetched'] = true;
        $conversation->update(['metadata' => $metadata]);

        return $stored;
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
        // Deactivate all pages
        $account->pages()->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }

    protected function graphVersion(): string
    {
        return config('services.meta.graph_api_version', 'v21.0');
    }
}
