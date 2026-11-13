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
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase D — Deep Analysis worker (spec §5).
 *
 * Dispatched by DeepAnalysisService::dispatch() AFTER credits have already
 * been charged (so a user can't game the price by killing the tab). Chunks
 * cohort into batches of 100, calls NaraRouter once per batch for a
 * structured-JSON summary, aggregates once more, stores the final result.
 *
 * - tries = 1: we never want to double-charge a retried run. If NaraRouter
 *   is broken, fail cleanly and refund (per spec §9 outage refund).
 * - queue  = heavy-analysis: isolated so the main queue is never starved
 *   by a 60-120s analysis run. In dev `php artisan queue:work` consumes
 *   all queues; prod requires a dedicated systemd worker (ops task).
 */
class DispatchDeepAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    /** Contact batch size for a single NaraRouter call. */
    public const BATCH_SIZE = 100;

    public function __construct(public int $deepAnalysisId)
    {
        $this->onQueue('heavy-analysis');
    }

    public function handle(DeepAnalysisService $service, AiCredits $credits): void
    {
        $analysis = DeepAnalysis::find($this->deepAnalysisId);
        if (! $analysis) {
            Log::warning('DispatchDeepAnalysisJob: missing row', ['id' => $this->deepAnalysisId]);
            return;
        }

        // Idempotency: skip if someone else already drove this to terminal.
        if (in_array($analysis->status, [DeepAnalysis::STATUS_COMPLETED, DeepAnalysis::STATUS_FAILED], true)) {
            return;
        }

        $team = Team::find($analysis->team_id);
        if (! $team) {
            $analysis->update([
                'status'        => DeepAnalysis::STATUS_FAILED,
                'error_message' => 'Team no longer exists.',
                'completed_at'  => now(),
            ]);
            return;
        }

        $analysis->update([
            'status'     => DeepAnalysis::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $mode = (string) $analysis->mode;

            if ($mode === DeepAnalysis::MODE_AGENT_AUDIT) {
                // Phase H — the agent audit is a single table aggregation plus
                // one NaraRouter prose summary. Skip the chunk loop entirely so
                // we don't pay per-100 for what is really a one-shot analysis.
                $final = $service->analyzeAgentAudit($team, $analysis->cohort_filter ?? []);

                $analysis->update([
                    'status'      => DeepAnalysis::STATUS_COMPLETED,
                    'result_json' => $final,
                    'token_usage' => [
                        'batch_count' => 1,
                        'prompt'      => null,
                        'completion'  => null,
                        'total'       => null,
                    ],
                    'completed_at' => now(),
                ]);
            } else {
                // Parallelised path (2026-11-11): per-chunk NaraRouter calls
                // fan out through Bus::batch so a 1000-contact cohort takes
                // ~15-30s instead of the old 2.5-5 minute serial loop. The
                // batch's ->then() callback dispatches FinalizeDeepAnalysisJob
                // which performs the aggregate() call and persists the result.
                //
                // allowFailures(): a single failed chunk should not kill the
                // whole run. The finalizer treats missing chunk summaries as
                // empty, and only marks the DeepAnalysis FAILED if ALL chunks
                // produced empty output (handled inside the finalizer).
                //
                // Bounded concurrency comes from the queue-worker fleet (6
                // on prod per CLAUDE.md); we do NOT cap in-code — Laravel
                // would just serialise excess jobs behind the available slots.
                $cohort = $service->loadCohort($team, $analysis->cohort_filter ?? []);

                $chunks = $cohort->chunk(self::BATCH_SIZE)->values();

                if ($chunks->isEmpty()) {
                    // Empty cohort — short-circuit to an empty completed result
                    // rather than dispatching a zero-job batch (Laravel refuses).
                    $analysis->update([
                        'status'       => DeepAnalysis::STATUS_COMPLETED,
                        'result_json'  => $service->aggregate([], $mode),
                        'token_usage'  => [
                            'batch_count' => 0,
                            'prompt'      => null,
                            'completion'  => null,
                            'total'       => null,
                        ],
                        'completed_at' => now(),
                    ]);

                    DeepAnalysisCompleted::dispatch(
                        (int) $team->id,
                        (int) $analysis->id,
                        (string) $analysis->mode,
                        (int) $analysis->cohort_size,
                        $analysis->completed_at->toIso8601String(),
                    );

                    return;
                }

                $childJobs = $chunks->map(fn ($chunk, $i) => new AnalyzeDeepAnalysisBatchJob(
                    deepAnalysisId:  $this->deepAnalysisId,
                    chunkIndex:      $i,
                    conversationIds: $chunk->pluck('id')->all(),
                    mode:            $mode,
                ))->all();

                $deepAnalysisId = $this->deepAnalysisId;
                $chunkCount     = $chunks->count();
                $teamId         = (int) $team->id;
                $cohortSize     = (int) $analysis->cohort_size;

                Bus::batch($childJobs)
                    ->allowFailures()
                    ->name("deep-analysis-{$deepAnalysisId}")
                    ->onQueue('heavy-analysis')
                    ->then(function (Batch $batch) use ($deepAnalysisId, $chunkCount, $teamId, $cohortSize) {
                        FinalizeDeepAnalysisJob::dispatch(
                            $deepAnalysisId,
                            $chunkCount,
                            $teamId,
                            $cohortSize,
                        );
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($deepAnalysisId) {
                        // With allowFailures() on, this fires only on orchestration
                        // failures (not individual chunk failures). The finalizer
                        // scheduled by then() handles the per-chunk-failed case.
                        Log::error('deep-analysis batch orchestration failed', [
                            'deep_analysis_id' => $deepAnalysisId,
                            'error'            => $e->getMessage(),
                        ]);
                    })
                    ->dispatch();

                // Orchestration-only: do NOT update status to COMPLETED here,
                // and do NOT broadcast — both happen in FinalizeDeepAnalysisJob
                // once the batch's then() callback fires. Return early.
                return;
            }

            // Agent-audit path (single-shot, see above) — broadcast completion.
            DeepAnalysisCompleted::dispatch(
                (int) $team->id,
                (int) $analysis->id,
                (string) $analysis->mode,
                (int) $analysis->cohort_size,
                $analysis->completed_at->toIso8601String(),
            );
        } catch (\Throwable $e) {
            Log::error('DispatchDeepAnalysisJob failed', [
                'deep_analysis_id' => $analysis->id,
                'team_id'          => $team->id,
                'error'            => $e->getMessage(),
                'at'               => $e->getFile() . ':' . $e->getLine(),
            ]);

            $analysis->update([
                'status'        => DeepAnalysis::STATUS_FAILED,
                'error_message' => \Illuminate\Support\Str::limit($e->getMessage(), 2000),
                'completed_at'  => now(),
            ]);

            $this->refundCharge($team, $analysis, $credits);

            // Broadcast on failure too, with success=false. Before 2026-10-09
            // only the success path broadcast, which left the AiChat 'Deep
            // Analysis running in the background' banner stuck on screen after
            // a NaraRouter outage failure (no event → no re-render → stale).
            // The AiChat listener (handleDeepAnalysisCompleted) checks the
            // success flag and either shows '✅ complete' or a friendly
            // 'the analysis failed, your credits were refunded' message.
            DeepAnalysisCompleted::dispatch(
                (int) $team->id,
                (int) $analysis->id,
                (string) $analysis->mode,
                (int) $analysis->cohort_size,
                $analysis->completed_at->toIso8601String(),
                false,
                \Illuminate\Support\Str::limit($e->getMessage(), 500),
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        $analysis = DeepAnalysis::find($this->deepAnalysisId);
        if (! $analysis) {
            return;
        }

        if ($analysis->status === DeepAnalysis::STATUS_FAILED) {
            // handle() already recorded + refunded.
            return;
        }

        $team = Team::find($analysis->team_id);
        $analysis->update([
            'status'        => DeepAnalysis::STATUS_FAILED,
            'error_message' => \Illuminate\Support\Str::limit($e->getMessage(), 2000),
            'completed_at'  => now(),
        ]);

        if ($team) {
            $this->refundCharge($team, $analysis, app(AiCredits::class));
        }
    }

    /**
     * Terminal-failure refund (spec §9). We reconstruct the receipt from the
     * ledger: find the most recent charge row(s) for this team whose metadata
     * references this DeepAnalysis via cost_source. The actual balance_type
     * split is reproduced so the refund lands in the originally-drained
     * balances (per AiCredits::refund invariant).
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
            Log::warning('DispatchDeepAnalysisJob: no ledger entries found to refund', [
                'deep_analysis_id' => $analysis->id,
                'team_id'          => $team->id,
            ]);
            return;
        }

        $breakdown  = [];
        $ledgerIds  = [];
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
                'reason'           => 'job_failed',
            ],
        );

        $credits->refund($receipt, 'deep_analysis_failed');
    }
}
