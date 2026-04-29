<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageSyncWindow;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SeedSyncWindowsFromMetadata extends Command
{
    protected $signature = 'sync-windows:seed';

    protected $description = 'Seed page_sync_windows from existing pages that have backfill_completed_at in metadata';

    public function handle(): int
    {
        $pages = Page::whereNotNull('metadata')
            ->where('is_active', true)
            ->get()
            ->filter(fn ($p) => ! empty($p->metadata['backfill_completed_at']));

        if ($pages->isEmpty()) {
            $this->info('No pages with backfill metadata found.');
            return self::SUCCESS;
        }

        $seeded = 0;

        foreach ($pages as $page) {
            $completedAt = $page->metadata['backfill_completed_at'];
            $oldestAt    = $page->metadata['backfill_oldest_at'] ?? null;

            if (! $oldestAt) {
                $this->warn("Page {$page->id}: backfill_completed_at set but no backfill_oldest_at — skipping.");
                continue;
            }

            $startsAt = Carbon::parse($oldestAt)->startOfDay();
            $endsAt   = Carbon::parse($completedAt)->endOfDay();

            $exists = PageSyncWindow::where('page_id', $page->id)
                ->where('starts_at', $startsAt)
                ->where('ends_at', $endsAt)
                ->exists();

            if ($exists) {
                $this->line("Page {$page->id}: window already exists, skipping.");
                continue;
            }

            PageSyncWindow::create([
                'page_id'   => $page->id,
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
                'status'    => 'complete',
            ]);

            $this->line("Page {$page->id} ({$page->name}): seeded [{$startsAt->toDateString()} → {$endsAt->toDateString()}]");
            $seeded++;
        }

        $this->info("Done. Seeded {$seeded} window(s).");

        return self::SUCCESS;
    }
}
