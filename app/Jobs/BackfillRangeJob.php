<?php

namespace App\Jobs;

use App\Models\Page;
use App\Models\PageSyncWindow;
use App\Services\PageSyncWindowService;
use App\Services\Platforms\FacebookPlatform;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Fetches a specific date range of Instagram conversations for a page,
 * chaining itself for each pagination cursor until the range is exhausted.
 * Marks a PageSyncWindow as complete (or failed) when done.
 */
class BackfillRangeJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        public int     $pageId,
        public string  $startsAt,   // ISO8601
        public string  $endsAt,     // ISO8601
        public ?string $afterCursor = null,
        public int     $depth       = 0,
        public ?int    $windowId    = null,
    ) {}

    public function handle(FacebookPlatform $platform, PageSyncWindowService $svc): void
    {
        $page = Page::find($this->pageId);

        if (! $page || $page->platform !== 'instagram') {
            return;
        }

        // Create the tracking window on first call
        if ($this->windowId === null) {
            $window = PageSyncWindow::create([
                'page_id'   => $this->pageId,
                'starts_at' => $this->startsAt,
                'ends_at'   => $this->endsAt,
                'status'    => 'pending',
            ]);
            $windowId = $window->id;
        } else {
            $windowId = $this->windowId;
        }

        if ($this->depth >= 200) {
            $this->fail($windowId, 'hit depth limit 200');
            return;
        }

        try {
            $result = $platform->fetchConversationsPage($page, $this->afterCursor, $this->startsAt);
        } catch (\Throwable $e) {
            $this->fail($windowId, $e->getMessage());
            throw $e;
        }

        // Pagination exhausted or hit our start boundary
        if (! $result['next_cursor'] || $result['stopped_at_iso']) {
            $svc->merge(
                $page,
                Carbon::parse($this->startsAt),
                Carbon::parse($this->endsAt),
            );
            PageSyncWindow::where('id', $windowId)->update(['status' => 'complete']);
            Log::info('BackfillRangeJob: complete', ['page_id' => $page->id, 'window_id' => $windowId]);
            return;
        }

        // Chain next page
        self::dispatch(
            pageId:      $page->id,
            startsAt:    $this->startsAt,
            endsAt:      $this->endsAt,
            afterCursor: $result['next_cursor'],
            depth:       $this->depth + 1,
            windowId:    $windowId,
        )->delay(now()->addSeconds(2));
    }

    private function fail(int $windowId, string $reason): void
    {
        PageSyncWindow::where('id', $windowId)->update([
            'status'         => 'failed',
            'failure_reason' => $reason,
        ]);
        Log::error('BackfillRangeJob: failed', [
            'page_id'  => $this->pageId,
            'window_id' => $windowId,
            'reason'   => $reason,
        ]);
    }
}
