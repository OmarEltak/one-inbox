<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Read-only "why aren't messages arriving for this page?" report, for super-admins.
 *
 * Dumps the raw evidence for every layer of the inbound path so a page can be
 * diagnosed from the browser without SSH: our DB rows, the webhooks we actually
 * received (incl. ones that arrived but routed to no page), and live Meta
 * checks (token validity/scopes, page-level + app-level subscriptions).
 * Never outputs tokens or secrets.
 */
class PageDiagnosticController extends Controller
{
    /** How many of the newest webhook_logs rows to scan (by id range — no sort; see prod-ops skill). */
    private const WEBHOOK_SCAN_WINDOW = 3000;

    public function __invoke(Page $page): JsonResponse
    {
        $meta = $page->metadata ?? [];
        $ids = collect([
            $page->platform_page_id,
            $meta['igbid'] ?? null,
            $meta['igsid'] ?? null,
            $meta['legacy_id'] ?? null,
            $meta['oauth_user_id'] ?? null,
            $meta['linked_facebook_page_id'] ?? null,
        ])->filter()->map(fn ($v) => (string) $v)->unique()->values()->all();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'page'         => $this->pageRow($page),
            'siblings'     => Page::where('id', '!=', $page->id)
                ->where(fn ($q) => $q->whereIn('platform_page_id', $ids))
                ->get(['id', 'team_id', 'platform', 'platform_page_id', 'is_active', 'connected_account_id', 'updated_at'])
                ->toArray(),
            'viewer' => [
                'user_id'         => auth()->id(),
                'current_team_id' => auth()->user()?->current_team_id,
                'can_see_page_in_own_inbox' => auth()->user()?->current_team_id === $page->team_id,
            ],
            'conversations' => [
                'count'           => $page->conversations()->count(),
                'last_message_at' => optional($page->conversations()->max('last_message_at'), fn ($v) => (string) $v),
                'latest'          => $this->conversationRows($page),
            ],
            'webhooks' => $this->webhooks($ids),
            'meta'     => $this->metaChecks($page, $meta),
        ];

        return response()->json($report, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function pageRow(Page $page): array
    {
        $account = $page->connectedAccount;

        return [
            'id'               => $page->id,
            'team_id'          => $page->team_id,
            'platform'         => $page->platform,
            'platform_page_id' => $page->platform_page_id,
            'name'             => $page->name,
            'is_active'        => $page->is_active,
            'has_token'        => filled($page->page_access_token),
            'metadata'         => $page->metadata,
            'updated_at'       => (string) $page->updated_at,
            'connected_account' => $account ? [
                'id'               => $account->id,
                'team_id'          => $account->team_id,
                'platform'         => $account->platform,
                'is_active'        => $account->is_active,
                'scopes'           => $account->scopes,
                'token_expires_at' => (string) $account->token_expires_at,
            ] : null,
        ];
    }

    /**
     * Latest conversations on the page, each with the reasons (if any) the
     * owning team's inbox would NOT list it — mirrors Inbox\Index::conversations().
     */
    private function conversationRows(Page $page): array
    {
        $cachedActive = \Illuminate\Support\Facades\Cache::get("team.{$page->team_id}.active_pages");

        return $page->conversations()
            ->withCount('messages')
            ->latest('last_message_at')
            ->take(5)
            ->get(['id', 'team_id', 'page_id', 'status', 'sales_stage', 'ai_paused', 'unread_count', 'last_message_at', 'last_message_preview'])
            ->map(function ($c) use ($page, $cachedActive) {
                $hidden = [];
                if ($c->team_id !== $page->team_id) {
                    $hidden[] = "conversation.team_id={$c->team_id} but page.team_id={$page->team_id}";
                }
                if ($c->status === 'archived') {
                    $hidden[] = 'status=archived';
                }
                if ($c->sales_stage === \App\Models\Conversation::STAGE_SPAM) {
                    $hidden[] = 'sales_stage=spam (only visible under the Spam filter)';
                }
                if ($cachedActive !== null && ! collect($cachedActive)->pluck('id')->contains($page->id)) {
                    $hidden[] = "page missing from team {$page->team_id}'s cached active_pages (expires ≤5 min)";
                }

                return [
                    'id'              => $c->id,
                    'team_id'         => $c->team_id,
                    'status'          => $c->status,
                    'sales_stage'     => $c->sales_stage,
                    'ai_paused'       => $c->ai_paused,
                    'unread_count'    => $c->unread_count,
                    'messages'        => $c->messages_count,
                    'last_message_at' => (string) $c->last_message_at,
                    'preview'         => Str::limit((string) $c->last_message_preview, 60),
                    'hidden_from_owner_inbox_because' => $hidden,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function webhooks(array $ids): array
    {
        $maxId = (int) WebhookLog::max('id');
        $fromId = max(0, $maxId - self::WEBHOOK_SCAN_WINDOW);

        $matches = [];
        $unrouted = [];
        $byPlatform = [];
        $oldest = null;

        WebhookLog::where('id', '>', $fromId)
            ->whereIn('platform', ['instagram', 'facebook'])
            ->select(['id', 'platform', 'event_type', 'team_id', 'processed', 'error', 'created_at', 'payload'])
            ->lazyById(500)
            ->each(function (WebhookLog $log) use ($ids, &$matches, &$unrouted, &$byPlatform, &$oldest) {
                $byPlatform[$log->platform] = ($byPlatform[$log->platform] ?? 0) + 1;
                $oldest ??= (string) $log->created_at;

                $entries = $log->payload['entry'] ?? [];
                $entryIds = array_values(array_filter(array_map(fn ($e) => isset($e['id']) ? (string) $e['id'] : null, $entries)));
                $hasMessage = collect($entries)->contains(fn ($e) => collect($e['messaging'] ?? [])->contains(fn ($m) => isset($m['message'])));

                if (array_intersect($entryIds, $ids)) {
                    $matches[] = [
                        'id'          => $log->id,
                        'platform'    => $log->platform,
                        'created_at'  => (string) $log->created_at,
                        'team_id'     => $log->team_id,
                        'processed'   => $log->processed,
                        'error'       => $log->error ? Str::limit($log->error, 300) : null,
                        'entry_ids'   => $entryIds,
                        'has_message' => $hasMessage,
                        'is_echo'     => (bool) data_get($entries, '0.messaging.0.message.is_echo', false),
                    ];
                }

                // Arrived + carried a message, but routed to no team → likely an ID mismatch.
                if ($log->platform === 'instagram' && $log->team_id === null && $hasMessage) {
                    foreach ($entryIds as $eid) {
                        $unrouted[$eid] = ($unrouted[$eid] ?? 0) + 1;
                    }
                }
            });

        arsort($unrouted);

        return [
            'scanned_window'            => ['from_id' => $fromId, 'to_id' => $maxId, 'oldest_created_at' => $oldest, 'by_platform' => $byPlatform],
            'for_this_page_count'       => count($matches),
            'for_this_page_latest'      => array_slice(array_reverse($matches), 0, 10),
            'unrouted_instagram_entry_ids' => array_slice($unrouted, 0, 10, true),
        ];
    }

    private function metaChecks(Page $page, array $meta): array
    {
        $v = config('services.meta.graph_api_version', 'v21.0');
        $token = $page->page_access_token;
        $appId = config('services.meta.app_id');
        $appToken = $appId.'|'.config('services.meta.app_secret');
        $igAppId = config('services.meta.instagram_app_id');
        $igAppToken = $igAppId.'|'.config('services.meta.instagram_app_secret');

        $checks = ['path' => null];

        if (($meta['auth_type'] ?? null) === 'instagram_business') {
            $checks['path'] = 'instagram_business_login (Instagram sub-app → /api/webhooks/meta-ig)';
            $checks['token_me'] = $this->get("https://graph.instagram.com/{$v}/me", ['fields' => 'user_id,username,account_type', 'access_token' => $token]);
            $checks['page_subscribed_apps'] = $this->get("https://graph.instagram.com/{$v}/{$page->platform_page_id}/subscribed_apps", ['access_token' => $token]);
        } else {
            $fbId = $page->platform === 'facebook' ? $page->platform_page_id : ($meta['linked_facebook_page_id'] ?? null);
            $checks['path'] = $page->platform === 'facebook'
                ? 'facebook_page (main app → /api/webhooks/meta)'
                : 'instagram_via_facebook_login (main app → /api/webhooks/meta)';
            $checks['linked_facebook_page_id'] = $fbId;
            if ($fbId) {
                $checks['fb_page'] = $this->get("https://graph.facebook.com/{$v}/{$fbId}", ['fields' => 'name,instagram_business_account{id,username}', 'access_token' => $token]);
                $checks['page_subscribed_apps'] = $this->get("https://graph.facebook.com/{$v}/{$fbId}/subscribed_apps", ['access_token' => $token]);
            }
            $debug = $this->get("https://graph.facebook.com/{$v}/debug_token", ['input_token' => $token, 'access_token' => $appToken]);
            $checks['token_debug'] = isset($debug['data']) ? array_intersect_key($debug['data'], array_flip([
                'app_id', 'type', 'is_valid', 'expires_at', 'data_access_expires_at', 'scopes', 'granular_scopes', 'profile_id', 'error',
            ])) : $debug;
        }

        $checks['app_subscriptions_main'] = $this->get("https://graph.facebook.com/{$v}/{$appId}/subscriptions", ['access_token' => $appToken]);
        if ($igAppId && $igAppId !== $appId) {
            $checks['app_subscriptions_instagram_app'] = $this->get("https://graph.facebook.com/{$v}/{$igAppId}/subscriptions", ['access_token' => $igAppToken]);
        }

        return $checks;
    }

    /**
     * GET a Graph endpoint and return its JSON (or a compact error) — never throws.
     */
    private function get(string $url, array $query): array
    {
        try {
            $resp = Http::timeout(10)->get($url, $query);
            $json = $resp->json();
            if (! is_array($json)) {
                return ['http_status' => $resp->status(), 'body' => Str::limit((string) $resp->body(), 300)];
            }
            unset($json['access_token']);

            return $resp->successful() ? $json : ['http_status' => $resp->status()] + $json;
        } catch (\Throwable $e) {
            return ['exception' => Str::limit($e->getMessage(), 300)];
        }
    }
}
