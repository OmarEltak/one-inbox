<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshInstagramSubscriptionsCommand extends Command
{
    protected $signature = 'instagram:refresh-subscriptions
                            {--team= : Only refresh for a specific team ID}';

    protected $description = 'Re-subscribe Facebook-linked Instagram pages to ensure messaging webhooks are active';

    public function handle(FacebookPlatform $platform): int
    {
        $query = Page::query()
            ->where('platform', 'instagram')
            ->where('is_active', true)
            ->whereNotNull('metadata->linked_facebook_page_id');

        if ($teamId = $this->option('team')) {
            $query->where('team_id', $teamId);
        }

        $pages = $query->get();

        if ($pages->isEmpty()) {
            $this->info('No Facebook-linked Instagram pages found.');

            return self::SUCCESS;
        }

        $this->info("Found {$pages->count()} Facebook-linked Instagram page(s). Refreshing subscriptions...");

        $success = 0;
        $failed = 0;

        foreach ($pages as $page) {
            $fbPageId = $page->metadata['linked_facebook_page_id'] ?? 'unknown';
            $label = "[{$page->id}] {$page->name} (FB page: {$fbPageId})";

            try {
                $ok = $platform->refreshFacebookSubscription($page);
            } catch (\Throwable $e) {
                Log::error('instagram:refresh-subscriptions exception', [
                    'page_id' => $page->id,
                    'error'   => $e->getMessage(),
                ]);
                $ok = false;
            }

            if ($ok) {
                $this->line("  OK  {$label}");
                $success++;
            } else {
                $this->warn("  FAIL {$label}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. Success: {$success}  Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
