<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\BackfillRangeJob;
use App\Models\Page;
use App\Services\PageSyncWindowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignPreviewController extends Controller
{
    public function __construct(private PageSyncWindowService $svc) {}

    /**
     * POST /api/campaigns/preview
     *
     * Body: { page_id, date_from (Y-m-d), date_to (Y-m-d) }
     *
     * Returns:
     * {
     *   ready: bool,               // true if full range is already synced
     *   gaps: [{ from, to }],      // date ranges not yet backfilled
     *   estimated_conversations: int,
     *   sync_dispatched: bool      // true when gaps were just queued
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id'   => ['required', 'integer', 'exists:pages,id'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $page = Page::find($data['page_id']);

        // Authorise: page must belong to the authenticated team
        if ($page->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        if ($page->platform !== 'instagram') {
            return response()->json([
                'ready'                   => true,
                'gaps'                    => [],
                'estimated_conversations' => 0,
                'sync_dispatched'         => false,
                'message'                 => 'Campaign preview is only supported for Instagram pages.',
            ]);
        }

        $from = Carbon::parse($data['date_from'])->startOfDay();
        $to   = Carbon::parse($data['date_to'])->endOfDay();

        $gaps            = $this->svc->gapsFor($page, $from, $to);
        $estimated       = $this->svc->estimateConversations($page, $from, $to);
        $syncDispatched  = false;

        if (! empty($gaps)) {
            foreach ($gaps as $gap) {
                BackfillRangeJob::dispatch(
                    pageId:   $page->id,
                    startsAt: $gap['from']->toIso8601String(),
                    endsAt:   $gap['to']->toIso8601String(),
                );
            }
            $syncDispatched = true;
        }

        return response()->json([
            'ready'                   => empty($gaps),
            'gaps'                    => array_map(fn ($g) => [
                'from' => $g['from']->toDateString(),
                'to'   => $g['to']->toDateString(),
            ], $gaps),
            'estimated_conversations' => $estimated,
            'sync_dispatched'         => $syncDispatched,
        ]);
    }
}
