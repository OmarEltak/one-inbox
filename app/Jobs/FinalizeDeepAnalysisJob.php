<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\DeepAnalysisCompleted;
use App\Models\AiCreditLedgerEntry;
use App\Models\DeepAnalysis;
use App\Models\Team;
use App\Services\Ai\DeepAnalysisService;
use App\Services\Billing\AiCredits;
use App\Services\Billing\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Phase D — final aggregation step for a parallel Deep Analysis run.
 *
 * Dispatched from the Bus::batch(...)->then() callback in
 * DispatchDeepAnalysisJob. Reads every per-chunk summary from cache,
 * aggregates them via DeepAnalysisService::aggregate(), persists the final
 * result on the DeepAnalysis row, and broadcasts DeepAnalysisCompleted.
 *
 * tries=1: same invariant as the parent — never re-run aggregation (which
 * would trigger another NaraRouter call and double-spend). If the finalizer
 * blows up, the catch branch marks the row FAILED and refunds.
 */
class FinalizeDeepAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(
        public int $deepAnalysisId,
        public int $chunkCount,
        public int $teamId,
        public int $cohortSize,
    ) {
        $this->onQueue('heavy-analysis');
    }

    public function handle(DeepAnalysisService $service, AiCredits $credits): void
    {
        $analysis = DeepAnalysis::find($this->deepAnalysisId);
        if (! $analysis) {
            Log::warning('FinalizeDeepAnalysisJob: missing row', ['id' => $this->deepAnalysisId]);
            return;
        }

        // Idempotency: if someone already drove this to terminal, bail.
        if (in_array($analysis->status, [DeepAnalysis::STATUS_COMPLETED, DeepAnalysis::STATUS_FAILED], true)) {
            $this->cleanupChunkCache();
            return;
        }

        $team = Team::find($this->teamId);

        try {
            $batchSummaries = $this->collectChunkSummaries();

            // If EVERY chunk came back empty/missing, that means all per-chunk
            // jobs permanently failed (allowFailures swallowed them). Refund
            // and mark the run failed rather than claim success with no data.
            $allEmpty = $batchSummaries !== [] && collect($batchSummaries)->every(
                fn ($s) => ($s['summary'] ?? '') === ''
                    && ($s['themes'] ?? []) === []
                    && ($s['hot_leads'] ?? []) === []
                    && ($s['objections'] ?? []) === [],
            );

            if ($allEmpty) {
                throw new \RuntimeException('All deep-analysis chunk jobs failed; no summaries produced.');
            }

            $final = $service->aggregate($batchSummaries, (string) $analysis->mode);

            $analysis->update([
                'status'       => DeepAnalysis::STATUS_COMPLETED,
                'result_json'  => $final,
                'token_usage'  => [
                    'batch_count' => $this->chunkCount,
                    'prompt'      => null,
                    'completion'  => null,
                    'total'       => null,
                ],
                'completed_at' => now(),
            ]);

            DeepAnalysisCompleted::dispatch(
                (int) $analysis->team_id,
                (int) $analysis->id,
                (string) $analysis->mode,
                (int) $analysis->cohort_size,
                $analysis->completed_at->toIso8601String(),
            );
        } catch (\Throwable $e) {
            Log::error('FinalizeDeepAnalysisJob failed', [
                'deep_analysis_id' => $analysis->id,
                'team_id'          => $this->teamId,
                'error'            => $e->getMessage(),
                'at'               => $e->getFile() . ':' . $e->getLine(),
            ]);

            $analysis->update([
                'status'        => DeepAnalysis::STATUS_FAILED,
                'error_message' => Str::limit($e->getMessage(), 2000),
                'completed_at'  => now(),
            ]);

            if ($team) {
                $this->refundCharge($team, $analysis, $credits);
            }

            // Mirror parent-job failure branch — AiChat listener needs the
            // event so the 'running in background' banner clears.
            DeepAnalysisCompleted::dispatch(
                (int) ($team?->id ?? $this->teamId),
                (int) $analysis->id,
                (string) $analysis->mode,
                (int) $analysis->cohort_size,
                $analysis->completed_at->toIso8601String(),
                false,
                Str::limit($e->getMessage(), 500),
            );
        } finally {
            $this->cleanupChunkCache();
        }
    }

    /**
     * Read every per-chunk summary. Missing or malformed entries become
     * empty-shape summaries so aggregate() doesn't crash on nulls.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function collectChunkSummaries(): array
    {
        $summaries = [];
        for ($i = 0; $i < $this->chunkCount; $i++) {
            $cached = Cache::get(AnalyzeDeepAnalysisBatchJob::cacheKey($this->deepAnalysisId, $i));
            $summaries[] = is_array($cached) ? $cached : [
                'themes' => [], 'hot_leads' => [], 'objections' => [], 'summary' => '',
            ];
        }
        return $summaries;
    }

    protected function cleanupChunkCache(): void
    {
        for ($i = 0; $i < $this->chunkCount; $i++) {
            Cache::forget(AnalyzeDeepAnalysisBatchJob::cacheKey($this->deepAnalysisId, $i));
        }
    }

    /**
     * Terminal-failure refund — same shape as DispatchDeepAnalysisJob::refundCharge().
     * Kept duplicated (not extracted into a trait) so the two jobs stay independently
     * reviewable; refund semantics are small enough that a trait would obscure more
     * than it dedupes.
     */
    protected function refundCharge(Team $team, DeepAnalysis $analysis, AiCredits $credits): void
    {
        $entries = AiCreditLedgerEntry::query()
            ->where('team_id', $team->id)
            ->where('cost_source_type', DeepAnalysis::class)
            ->where('delta', '<', 0)
            ->whereJsonContains('metadata->scaled_from', 'deep_analysis_per_100_contacts')
            ->orderByDesc('id')
            ->limit(2)
            ->get()
            ->filter(fn ($e) => (int) ($e->metadata['cohort_size'] ?? 0) === (int) $analysis->cohort_size);

        if ($entries->isEmpty()) {
            Log::warning('FinalizeDeepAnalysisJob: no ledger entries found to refund', [
                'deep_analysis_id' => $analysis->id,
                'team_id'          => $team->id,
            ]);
            return;
        }

        $breakdown = [];
        $ledgerIds = [];
        foreach ($entries as $entry) {
            $breakdown[(string) $entry->balance_type] = ($breakdown[(string) $entry->balance_type] ?? 0) + abs((int) $entry->delta);
            $ledgerIds[] = (int) $entry->id;
        }

        $receipt = new Receipt(
            teamId: (int) $team->id,
            action: 'deep_analysis_failed',
            cost: (int) $analysis->credits_charged,
            ledgerEntryIds: $ledgerIds,
            breakdown: $breakdown,
            idempotencyKey: null,
            metadata: [
                'deep_analysis_id' => $analysis->id,
                'reason'           => 'finalizer_failed',
            ],
        );

        $credits->refund($receipt, 'deep_analysis_failed');
    }
}
