<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-shot operational command: re-subscribe existing IG-via-FB pages with
 * `subscribed_fields=messages,message_echoes,comments` so IG comment webhooks
 * actually arrive. Direct IG Business Login pages are intentionally skipped —
 * their scope story (instagram_business_manage_comments) is not yet granted,
 * and POSTing `comments` to graph.instagram.com/.../subscribed_apps 400s the
 * WHOLE call and silently kills DMs. See FacebookPlatform::IG_SUBSCRIBED_FIELDS.
 */
class ResubscribeInstagramCommentsWebhook extends Command
{
    protected $signature = 'app:resubscribe-ig-comments {--dry-run : Print planned POSTs without executing}';

    protected $description = 'Re-subscribe IG-via-FB pages to messages,message_echoes,comments on graph.facebook.com.';

    private const SUBSCRIBED_FIELDS = 'messages,message_echoes,comments';

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
