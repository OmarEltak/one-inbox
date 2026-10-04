<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies that each active Facebook/Instagram page's stored page access
 * token is still valid AND that our app is still subscribed to the page.
 *
 * Writes a human-readable `metadata.subscription_error` on the Page row
 * when something is wrong, which the Connections UI surfaces as a warning
 * banner with a one-click reconnect CTA. Clears the field on recovery.
 *
 * Motivation: on 2026-10-01 the "Brandk" page silently lost its
 * granular pages_messaging grant (user re-ran OAuth and only picked
 * OT1-Pro in the Pages picker). Meta then stopped delivering webhooks
 * for 3+ days with no indication in the app. This command catches that
 * failure mode within an hour.
 *
 * Scheduled hourly in routes/console.php.
 */
class PageHealthCheckCommand extends Command
{
    protected $signature = 'pages:health-check
                            {--page= : Only check a specific page ID}
                            {--platform=all : facebook|instagram|all}';

    protected $description = 'Verify Meta page tokens + subscriptions; flag broken pages in metadata.subscription_error';

    public function handle(): int
    {
        $query = Page::query()
            ->where('is_active', true)
            ->whereNotNull('page_access_token');

        $platform = $this->option('platform');
        if ($platform !== 'all') {
            $query->where('platform', $platform);
        } else {
            $query->whereIn('platform', ['facebook', 'instagram']);
        }

        if ($pageId = $this->option('page')) {
            $query->where('id', $pageId);
        }

        $pages = $query->get();

        if ($pages->isEmpty()) {
            $this->info('No active pages to check.');
            return self::SUCCESS;
        }

        $this->info("Checking {$pages->count()} page(s)...");

        $appId = (string) config('services.meta.app_id');
        $appSecret = (string) config('services.meta.app_secret');
        $version = config('services.meta.graph_api_version', 'v21.0');
        $graphUrl = "https://graph.facebook.com/{$version}";
        $appAccess = "{$appId}|{$appSecret}";

        $healthy = 0;
        $broken = 0;

        foreach ($pages as $page) {
            $result = $this->checkPage($page, $graphUrl, $appAccess);

            if ($result === null) {
                $this->line("  OK  [{$page->id}] {$page->name} ({$page->platform})");
                $this->clearError($page);
                $healthy++;
            } else {
                $this->warn("  BROKEN [{$page->id}] {$page->name} ({$page->platform}): {$result['message']}");
                $this->markError($page, $result);
                $broken++;
            }
        }

        $this->newLine();
        $this->info("Done. Healthy: {$healthy}  Broken: {$broken}");

        return self::SUCCESS;
    }

    /**
     * @return array{code: int|string, message: string, source: string}|null
     *   null = healthy; array = problem detected
     */
    private function checkPage(Page $page, string $graphUrl, string $appAccess): ?array
    {
        $token = $page->page_access_token;
        if (empty($token)) {
            return ['code' => 'no_token', 'message' => 'No page access token stored', 'source' => 'local'];
        }

        try {
            $debug = Http::timeout(10)->get("{$graphUrl}/debug_token", [
                'input_token' => $token,
                'access_token' => $appAccess,
            ])->json();
        } catch (\Throwable $e) {
            Log::warning('pages:health-check debug_token http error', [
                'page_id' => $page->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        $data = $debug['data'] ?? [];
        if (($data['is_valid'] ?? true) === false) {
            $err = $data['error'] ?? [];
            return [
                'code' => (int) ($err['code'] ?? 190),
                'message' => $this->summarize((string) ($err['message'] ?? 'Token is invalid')),
                'source' => 'debug_token',
            ];
        }

        try {
            $sub = Http::timeout(10)->withToken($token)->get(
                "{$graphUrl}/{$page->platform_page_id}/subscribed_apps"
            )->json();
        } catch (\Throwable $e) {
            return null;
        }

        if (isset($sub['error'])) {
            $err = $sub['error'];
            return [
                'code' => (int) ($err['code'] ?? 190),
                'message' => $this->summarize((string) ($err['message'] ?? 'Unknown subscription error')),
                'source' => 'subscribed_apps',
            ];
        }

        $appId = (string) config('services.meta.app_id');
        $apps = $sub['data'] ?? [];
        $ourAppSubscribed = collect($apps)->contains(fn ($a) => (string) ($a['id'] ?? '') === $appId);

        if (! $ourAppSubscribed && ! empty($appId)) {
            return [
                'code' => 'not_subscribed',
                'message' => 'This page is no longer subscribed to our app webhooks.',
                'source' => 'subscribed_apps',
            ];
        }

        return null;
    }

    private function markError(Page $page, array $result): void
    {
        $metadata = $page->metadata ?? [];
        $existing = $metadata['subscription_error'] ?? null;

        // Preserve first_seen_at across repeated checks for a persistent error
        $firstSeenAt = $existing['first_seen_at'] ?? now()->toIso8601String();

        $metadata['subscription_error'] = [
            'code' => $result['code'],
            'message' => $result['message'],
            'source' => $result['source'],
            'first_seen_at' => $firstSeenAt,
            'last_seen_at' => now()->toIso8601String(),
        ];

        $page->metadata = $metadata;
        $page->save();
    }

    private function clearError(Page $page): void
    {
        $metadata = $page->metadata ?? [];
        if (! isset($metadata['subscription_error'])) {
            return;
        }
        unset($metadata['subscription_error']);
        $page->metadata = $metadata;
        $page->save();
    }

    /**
     * Meta's error messages are verbose multi-line scope lists. Keep the
     * core sentence for display.
     */
    private function summarize(string $raw): string
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw) ?: $raw);
        return mb_strlen($raw) > 220 ? mb_substr($raw, 0, 217) . '...' : $raw;
    }
}
