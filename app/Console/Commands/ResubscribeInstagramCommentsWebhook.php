<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-shot operational command: re-subscribe existing IG-via-FB pages with
 * `subscribed_fields=messages,comments` so IG comment webhooks actually
 * arrive. Direct IG Business Login pages are still skipped by this command —
 * NOT because the scope is missing anymore (as of 2026-10-27 it is requested
 * in getInstagramConnectUrl + listed in IG_SUBSCRIBED_FIELDS), but because
 * for Direct IG Login pages the user must RECONNECT to get a token that
 * carries the new scope. subscribeInstagramPage() reruns on reconnect and
 * subscribes to `messages,comments` automatically — this command would only
 * attempt it with the OLD token which lacks the capability and would 400.
 */
class ResubscribeInstagramCommentsWebhook extends Command
{
    protected $signature = 'app:resubscribe-ig-comments {--dry-run : Print planned POSTs without executing}';

    protected $description = 'Re-subscribe IG-via-FB pages to messages,comments on graph.facebook.com.';

    // Mirror of FacebookPlatform::IG_VIA_FB_SUBSCRIBED_FIELDS. IG accepts a
    // specific field whitelist — `message_echoes` is Messenger-only and causes
    // (#100) rejection on IG.
    private const SUBSCRIBED_FIELDS = 'messages,comments';

    private const GRAPH_URL = 'https://graph.facebook.com/v23.0';

    public function handle(): int
    {
        $pages = Page::whereIn('platform', ['instagram', 'instagram_business'])
            ->where('is_active', true)
            ->get();

        if ($pages->isEmpty()) {
            $this->info('No active Instagram pages found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $failed = 0;

        foreach ($pages as $page) {
            $label = "[{$page->id}] {$page->name} ({$page->platform}, platform_page_id={$page->platform_page_id})";

            if ($page->platform === 'instagram_business') {
                $this->warn("  SKIP {$label} — direct IG, needs instagram_business_manage_comments scope first");

                continue;
            }

            $endpoint = self::GRAPH_URL."/{$page->platform_page_id}/subscribed_apps";

            if ($dryRun) {
                $this->line("  DRY  POST {$endpoint}  subscribed_fields=".self::SUBSCRIBED_FIELDS);

                continue;
            }

            $response = Http::asForm()->post($endpoint, [
                'access_token' => $page->page_access_token,
                'subscribed_fields' => self::SUBSCRIBED_FIELDS,
            ]);

            $status = $response->status();
            $body = $response->body();

            if ($response->successful()) {
                $this->info("  OK   {$label}  status={$status}  body={$body}");
            } else {
                $this->error("  FAIL {$label}  status={$status}  body={$body}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : "Done. Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
