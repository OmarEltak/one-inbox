<?php

namespace App\Jobs;

use App\Models\Page;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncPageConversations implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries = 2;

    /**
     * @param int $pageId The page ID to sync conversations for
     * @param string|null $afterCursor Pagination cursor for chained calls
     * @param int $depth Current chain depth (safety limit)
     * @param string|null $stopAtIso ISO8601 timestamp to stop backfill at (30 days ago)
     */
    public function __construct(
        public int $pageId,
        public ?string $afterCursor = null,
        public int $depth = 0,
        public ?string $stopAtIso = null,
    ) {}

    public function handle(): void
    {
        $page = Page::find($this->pageId);

        if (! $page) {
            Log::warning('SyncPageConversations: page not found', ['page_id' => $this->pageId]);
            return;
        }

        $platform = app(FacebookPlatform::class);

        // Facebook Messenger keeps the legacy single-job walk untouched —
        // requirements scoped chained backfill to Instagram only.
        if ($page->platform === 'facebook') {
            $platform->fetchConversations($page);
            return;
        }

        if ($page->platform !== 'instagram') {
            return;
        }

        // Initialize stopAtIso on first run
        $stopAtIso = $this->stopAtIso ?? now()->subDays(30)->toIso8601String();

        // Hard safety stop at depth 200
        if ($this->depth >= 200) {
            Log::warning('SyncPageConversations: hit depth limit', [
                'page_id' => $page->id,
                'depth' => $this->depth,
            ]);
            $this->markBackfillComplete($page, $stopAtIso);
            return;
        }

        $result = $platform->fetchConversationsPage($page, $this->afterCursor, $stopAtIso);

        // No next cursor - pagination complete
        if (! $result['next_cursor']) {
            $this->markBackfillComplete($page, $stopAtIso);
            return;
        }

        // Stopped early because conversations are older than 30 days
        if ($result['stopped_at_iso']) {
            $this->markBackfillComplete($page, $result['stopped_at_iso']);
            return;
        }

        // Chain next page with 2-second delay for rate limiting (~30 calls/min)
        self::dispatch(
            pageId: $page->id,
            afterCursor: $result['next_cursor'],
            depth: $this->depth + 1,
            stopAtIso: $stopAtIso,
        )->delay(now()->addSeconds(2));
    }

    protected function markBackfillComplete(Page $page, string $stopAtIso): void
    {
        $page->metadata = array_merge($page->metadata ?? [], [
            'backfill_completed_at' => now()->toIso8601String(),
            'backfill_oldest_at' => $stopAtIso,
        ]);
        $page->save();

        Log::info('SyncPageConversations: backfill complete', [
            'page_id' => $page->id,
            'backfill_completed_at' => $page->metadata['backfill_completed_at'],
            'backfill_oldest_at' => $page->metadata['backfill_oldest_at'],
        ]);
    }
}
