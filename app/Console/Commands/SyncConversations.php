<?php

namespace App\Console\Commands;

use App\Jobs\SyncPageConversations;
use App\Models\Page;
use Illuminate\Console\Command;

class SyncConversations extends Command
{
    protected $signature = 'inbox:sync-conversations
                            {--page= : Specific page ID to sync (syncs all active pages if omitted)}
                            {--team= : Specific team ID to sync pages for}';

    protected $description = 'Dispatch SyncPageConversations jobs to re-sync conversations from connected platforms';

    public function handle(): int
    {
        $query = Page::where('is_active', true)
            ->whereIn('platform', ['facebook', 'instagram']);

        if ($pageId = $this->option('page')) {
            $query->where('id', $pageId);
        }

        if ($teamId = $this->option('team')) {
            $query->where('team_id', $teamId);
        }

        $pages = $query->get();

        if ($pages->isEmpty()) {
            $this->warn('No matching active pages found.');

            return self::FAILURE;
        }

        foreach ($pages as $page) {
            SyncPageConversations::dispatch($page);
            $this->line("Dispatched sync for: [{$page->id}] {$page->name} ({$page->platform})");
        }

        $this->info("Dispatched {$pages->count()} sync job(s). Run 'php artisan queue:work' if not already running.");

        return self::SUCCESS;
    }
}
