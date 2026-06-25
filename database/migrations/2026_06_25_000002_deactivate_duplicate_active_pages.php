<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\PageTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // One-time cleanup. The Page model now enforces "at most one active row per
        // (platform_page_id, platform)" via its saved() observer, but historical rows
        // from before that invariant existed need to be reconciled.

        $duplicateGroups = DB::table('pages')
            ->select('platform', 'platform_page_id')
            ->where('is_active', true)
            ->whereNotNull('platform_page_id')
            ->groupBy('platform', 'platform_page_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isEmpty()) {
            return;
        }

        foreach ($duplicateGroups as $group) {
            $rows = Page::where('platform', $group->platform)
                ->where('platform_page_id', $group->platform_page_id)
                ->where('is_active', true)
                ->orderByDesc('created_at')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $winner = $rows->shift();

            foreach ($rows as $loser) {
                PageTransfer::create([
                    'superseded_page_id'    => $loser->id,
                    'superseded_by_page_id' => $winner->id,
                    'from_team_id'          => $loser->team_id,
                    'to_team_id'            => $winner->team_id,
                    'actor_user_id'         => null,
                    'reason'                => 'cleanup',
                    'snapshot'              => [
                        'platform'         => $loser->platform,
                        'platform_page_id' => $loser->platform_page_id,
                        'name'             => $loser->name,
                    ],
                ]);

                $loser->forceFill(['is_active' => false])->saveQuietly();

                Log::info('Page deactivated by one-time cleanup', [
                    'superseded_page_id'    => $loser->id,
                    'superseded_by_page_id' => $winner->id,
                    'from_team_id'          => $loser->team_id,
                    'to_team_id'            => $winner->team_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Not reversible. The page_transfers rows from this run can be inspected to
        // see which pages were deactivated and manually re-activated if needed.
    }
};
