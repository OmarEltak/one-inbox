<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Page;
use App\Models\PageSyncWindow;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PageSyncWindowService
{
    /**
     * Return date-range gaps between $from and $to that have no 'complete' sync window.
     * Each gap is ['from' => Carbon, 'to' => Carbon].
     */
    public function gapsFor(Page $page, Carbon $from, Carbon $to): array
    {
        $windows = PageSyncWindow::where('page_id', $page->id)
            ->where('status', 'complete')
            ->where('starts_at', '<=', $to)
            ->where('ends_at', '>=', $from)
            ->orderBy('starts_at')
            ->get();

        $gaps   = [];
        $cursor = $from->copy();

        foreach ($windows as $w) {
            if ($cursor->lt($w->starts_at)) {
                $gaps[] = ['from' => $cursor->copy(), 'to' => $w->starts_at->copy()];
            }
            if ($cursor->lt($w->ends_at)) {
                $cursor = $w->ends_at->copy();
            }
        }

        if ($cursor->lt($to)) {
            $gaps[] = ['from' => $cursor->copy(), 'to' => $to->copy()];
        }

        return $gaps;
    }

    /**
     * Record a completed sync window, merging with adjacent/overlapping windows.
     */
    public function merge(Page $page, Carbon $startsAt, Carbon $endsAt): void
    {
        $overlapping = PageSyncWindow::where('page_id', $page->id)
            ->where('status', 'complete')
            ->where('starts_at', '<=', $endsAt)
            ->where('ends_at', '>=', $startsAt)
            ->orderBy('starts_at')
            ->get();

        if ($overlapping->isEmpty()) {
            PageSyncWindow::create([
                'page_id'   => $page->id,
                'starts_at' => $startsAt,
                'ends_at'   => $endsAt,
                'status'    => 'complete',
            ]);
            return;
        }

        $merged_start = $overlapping->min('starts_at');
        $merged_end   = $overlapping->max('ends_at');

        if ($startsAt->lt($merged_start)) $merged_start = $startsAt;
        if ($endsAt->gt($merged_end))     $merged_end   = $endsAt;

        // Delete all overlapping windows and replace with one merged record
        PageSyncWindow::where('page_id', $page->id)
            ->whereIn('id', $overlapping->pluck('id'))
            ->delete();

        PageSyncWindow::create([
            'page_id'   => $page->id,
            'starts_at' => $merged_start,
            'ends_at'   => $merged_end,
            'status'    => 'complete',
        ]);
    }

    /**
     * Count conversations whose last_message_at falls within [from, to].
     */
    public function estimateConversations(Page $page, Carbon $from, Carbon $to): int
    {
        return Conversation::where('page_id', $page->id)
            ->whereBetween('last_message_at', [$from, $to])
            ->count();
    }
}
