<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConnectedAccount;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Refreshes Direct Instagram Business Login long-lived tokens before they
 * expire. Direct-IG tokens live 60 days from issue, but Meta allows refreshing
 * once per 24h with no lifetime cap — each refresh grants a fresh 60-day
 * window. We proactively refresh any token within --days-before-expiry so a
 * transient Meta outage doesn't push a customer past the cliff.
 *
 * Scope: ONLY pages where metadata.auth_type === 'instagram_business'. FB and
 * IG-via-Facebook pages are unaffected (their tokens have different lifecycles
 * and are refreshed in-flow by the FB platform code).
 *
 * Load profile: serial processing with jittered sleep between calls. At 1,000
 * direct-IG pages, ~16 tokens enter the 14-day window per day; at 750ms per
 * call that's ~12s total. Expiries desynchronize naturally after the first
 * refresh (each push +60d), so cohorts that connected on the same day
 * disperse after the first cycle.
 *
 * Failure semantics: NEVER overwrite the existing token on failure — the
 * current token is still valid (we refresh N days early for exactly this
 * reason). Failures write metadata.refresh_error and are retried on the next
 * daily tick. If <2 days remain and the refresh still fails, we also mark
 * metadata.subscription_error so the UI shows the reconnect CTA before the
 * cliff rather than at it.
 *
 * Scheduled daily at 02:40 UTC in routes/console.php.
 */
class RefreshInstagramTokensCommand extends Command
{
    protected $signature = 'ig-direct:refresh-tokens
                            {--days-before-expiry=14 : Refresh tokens expiring within this many days}
                            {--sleep-ms=750 : Base sleep (ms) between calls; actual sleep jitters ±25%}
                            {--dry-run : Print what would be done without calling Meta or writing}
                            {--page= : Only process a specific page ID (bypasses the days-before-expiry filter)}';

    protected $description = 'Refresh long-lived Instagram Business Login tokens before they expire';

    private const CRITICAL_DAYS_LEFT = 2;

    public function handle(): int
    {
        $daysBefore = max(1, (int) $this->option('days-before-expiry'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $dryRun = (bool) $this->option('dry-run');
        $pageId = $this->option('page');

        $query = Page::query()
            ->where('platform', 'instagram')
            ->where('is_active', true)
            ->whereNotNull('page_access_token')
            // Scalar JSON path match — works on both MySQL (prod) and SQLite (tests).
            // whereJsonContains() is for arrays and misbehaves on scalar values.
            ->where('metadata->auth_type', 'instagram_business');

        if ($pageId) {
            $query->where('id', $pageId);
        } else {
            // token_expires_at lives on connected_accounts, not pages.
            // Null expiry = legacy row, treat as eligible (safer to refresh
            // and reset the clock than to let it silently expire).
            $cutoff = now()->addDays($daysBefore);
            $nowTs = now();
            $query->whereHas('connectedAccount', function ($q) use ($cutoff, $nowTs) {
                $q->where(function ($qq) use ($cutoff, $nowTs) {
                    $qq->whereNull('token_expires_at')
                        ->orWhere(function ($qqq) use ($cutoff, $nowTs) {
                            $qqq->where('token_expires_at', '<=', $cutoff)
                                ->where('token_expires_at', '>', $nowTs); // skip already-expired, reconnect required
                        });
                });
            });
        }

        $pages = $query->get();

        if ($pages->isEmpty()) {
            $this->info('No direct-IG tokens within the refresh window.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d direct-IG token(s) (threshold: %d days, sleep: %dms ±25%%)...',
            $dryRun ? '[DRY RUN] Would refresh' : 'Refreshing',
            $pages->count(),
            $daysBefore,
            $sleepMs
        ));

        $refreshed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($pages as $i => $page) {
            $expiry = $page->connectedAccount?->token_expires_at;
            $daysLeft = $expiry
                ? (int) now()->diffInDays($expiry, false)
                : null;

            $prefix = sprintf('  [%d] %s (%d days left)', $page->id, $page->name, $daysLeft ?? -1);

            if ($dryRun) {
                $this->line("{$prefix} — would refresh");
                $skipped++;
                continue;
            }

            $outcome = $this->refreshOne($page);

            if ($outcome === 'ok') {
                $this->line("  OK  {$prefix} → refreshed");
                $refreshed++;
            } else {
                $this->warn("  FAIL {$prefix}: {$outcome}");
                $failed++;
            }

            // Jittered sleep between calls, but not after the last one.
            if ($sleepMs > 0 && $i < $pages->count() - 1) {
                $jitter = (int) ($sleepMs * 0.25);
                usleep((max(0, $sleepMs + random_int(-$jitter, $jitter))) * 1000);
            }
        }

        $this->newLine();
        $this->info("Done. Refreshed: {$refreshed}  Failed: {$failed}  Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * @return string 'ok' on success, or a short failure reason suitable for logs + output
     */
    private function refreshOne(Page $page): string
    {
        $version = config('services.meta.graph_api_version', 'v21.0');
        $url = "https://graph.instagram.com/{$version}/refresh_access_token";

        try {
            $response = Http::timeout(15)->get($url, [
                'grant_type'   => 'ig_refresh_token',
                'access_token' => $page->page_access_token,
            ]);
        } catch (\Throwable $e) {
            $this->markFailure($page, 'http_error', $e->getMessage());
            return "http error: {$e->getMessage()}";
        }

        $body = $response->json() ?: [];

        if (! $response->successful() || ! isset($body['access_token'])) {
            $err = $body['error'] ?? [];
            $code = (int) ($err['code'] ?? $response->status());
            $message = (string) ($err['message'] ?? 'Unknown refresh error');
            $this->markFailure($page, (string) $code, $message);
            return "meta rejected (code {$code}): {$message}";
        }

        $newToken = (string) $body['access_token'];
        $expiresIn = (int) ($body['expires_in'] ?? 5184000); // 60 days default
        $newExpiry = now()->addSeconds($expiresIn);

        // Update BOTH the Page and the ConnectedAccount. For direct IG, the
        // user token and the page token are the same string — but they live in
        // two tables for consistency with the rest of the platform model.
        $page->update([
            'page_access_token' => $newToken,
            // The Page table doesn't have token_expires_at; expiry lives on
            // ConnectedAccount. We also drop any stale refresh_error.
            'metadata' => collect($page->metadata ?? [])
                ->except('refresh_error', 'subscription_error')
                ->all(),
        ]);

        if ($page->connected_account_id) {
            // Update via Eloquent so the `encrypted` cast serializes + encrypts
            // the token correctly. Query Builder update() bypasses casts and
            // results in a double-encoded value that reads back as the raw
            // serialize() payload.
            $account = ConnectedAccount::find($page->connected_account_id);
            if ($account) {
                $account->access_token = $newToken;
                $account->token_expires_at = $newExpiry;
                $account->save();
            }
        }

        Log::info('IG direct token refreshed', [
            'page_id'    => $page->id,
            'new_expiry' => $newExpiry->toIso8601String(),
            'days_added' => (int) now()->diffInDays($newExpiry, false),
        ]);

        return 'ok';
    }

    private function markFailure(Page $page, string $code, string $message): void
    {
        $metadata = $page->metadata ?? [];
        $expiry = $page->connectedAccount?->token_expires_at;
        $daysLeft = $expiry
            ? (int) now()->diffInDays($expiry, false)
            : null;

        $metadata['refresh_error'] = [
            'code'            => $code,
            'message'         => mb_strlen($message) > 220 ? mb_substr($message, 0, 217) . '...' : $message,
            'attempted_at'    => now()->toIso8601String(),
            'days_until_expiry' => $daysLeft,
        ];

        // If we're already within the critical margin, escalate so the UI
        // shows the reconnect CTA proactively instead of waiting for the
        // actual cliff.
        if ($daysLeft !== null && $daysLeft <= self::CRITICAL_DAYS_LEFT) {
            $metadata['subscription_error'] = [
                'code'          => $code,
                'message'       => "Instagram token refresh failed with {$daysLeft} day(s) left; please reconnect.",
                'source'        => 'ig_refresh',
                'first_seen_at' => $metadata['subscription_error']['first_seen_at']
                    ?? now()->toIso8601String(),
                'last_seen_at'  => now()->toIso8601String(),
            ];
        }

        $page->update(['metadata' => $metadata]);

        Log::warning('IG direct token refresh failed', [
            'page_id'           => $page->id,
            'code'              => $code,
            'message'           => $message,
            'days_until_expiry' => $daysLeft,
        ]);
    }
}
