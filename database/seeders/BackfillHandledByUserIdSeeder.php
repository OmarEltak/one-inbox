<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase H — Backfill messages.handled_by_user_id for existing outbound rows.
 *
 * Attribution rules (best-effort, honest-about-unknown):
 *
 *   1. If messages.sender_id is set AND sender_type IN ('user','agent')
 *      AND direction='outbound' AND sender_id exists in users.id → copy it.
 *      This is the strongest signal — these are rows where the inbox / platform
 *      service already captured the acting user.
 *
 *   2. Otherwise, if conversations.assigned_to is set, copy that user's id into
 *      handled_by_user_id for every outbound non-AI message on that conversation.
 *      Weaker signal (assigned_to can change over time), but better than null
 *      when the inbox never captured sender_id.
 *
 *   3. Otherwise leave null — unattributed. AgentAuditService reports null as
 *      "Unattributed" rather than guessing.
 *
 * Idempotency: every pass only updates rows where handled_by_user_id IS NULL,
 * so re-running after a partial failure or after new rows are added is safe
 * and never overwrites a previously-attributed message.
 */
class BackfillHandledByUserIdSeeder extends Seeder
{
    public function run(): void
    {
        $viaSender = $this->backfillFromSenderId();
        $viaAssigned = $this->backfillFromConversationAssignee();

        $total = $viaSender + $viaAssigned;

        $this->command?->info("BackfillHandledByUserIdSeeder: updated {$total} rows (sender_id: {$viaSender}, assigned_to: {$viaAssigned}).");

        Log::info('backfill.handled_by_user_id.complete', [
            'via_sender_id'        => $viaSender,
            'via_conversation_assigned_to' => $viaAssigned,
            'total'                => $total,
        ]);
    }

    /**
     * Primary pass — copy sender_id into handled_by_user_id for outbound
     * human-origin rows that resolve to a real user.
     */
    private function backfillFromSenderId(): int
    {
        $updated = 0;

        // Chunk to keep memory flat on the ~100k-row dev DBs and the much
        // larger prod one. SQLite + MySQL both honour the subquery form.
        $userIds = DB::table('users')->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($userIds === []) {
            return 0;
        }

        DB::table('messages')
            ->whereNull('handled_by_user_id')
            ->where('direction', 'outbound')
            ->whereIn('sender_type', ['user', 'agent'])
            ->whereNotNull('sender_id')
            ->whereIn('sender_id', $userIds)
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$updated): void {
                foreach ($rows as $row) {
                    $affected = DB::table('messages')
                        ->where('id', $row->id)
                        ->whereNull('handled_by_user_id')
                        ->update(['handled_by_user_id' => $row->sender_id]);
                    $updated += $affected;
                }
            });

        return $updated;
    }

    /**
     * Secondary pass — for still-null outbound rows, fall back to the
     * conversation's assigned_to user. We only touch outbound non-AI rows so
     * we never attribute an AI reply to the human it was escalated to.
     */
    private function backfillFromConversationAssignee(): int
    {
        $updated = 0;

        DB::table('conversations')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->chunkById(500, function ($conversations) use (&$updated): void {
                foreach ($conversations as $conversation) {
                    $affected = DB::table('messages')
                        ->where('conversation_id', $conversation->id)
                        ->where('direction', 'outbound')
                        ->whereNull('handled_by_user_id')
                        ->where(function ($q) {
                            $q->whereNull('sender_type')->orWhere('sender_type', '!=', 'ai');
                        })
                        ->update(['handled_by_user_id' => $conversation->assigned_to]);
                    $updated += $affected;
                }
            });

        return $updated;
    }
}
