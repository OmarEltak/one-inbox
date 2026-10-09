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
use Illuminate\Support\Facades\Log;

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
                $cohort = $service->loadCohort($team, $analysis->cohort_filter ?? []);

                $batchSummaries = [];
                foreach ($cohort->chunk(self::BATCH_SIZE) as $chunk) {
                    $batchSummaries[] = $service->analyzeBatch($chunk, $mode);
                }

                $final = $service->aggregate($batchSummaries, $mode);

                $analysis->update([
                    'status'       => DeepAnalysis::STATUS_COMPLETED,
                    'result_json'  => $final,
                    'token_usage'  => [
                        'batch_count' => count($batchSummaries),
                        // Real token counts would require the provider to surface
                        // usage; placeholder keys so the UI/CLI has a stable shape.
                        'prompt'      => null,
                        'completion'  => null,
                        'total'       => null,
                    ],
                    'completed_at' => now(),
                ]);
            }

            // Dispatchable::dispatch() does not forward named parameters — it
            // uses func_get_args() + new static(...$args) which collapses to
            // positional. Pass positionally to match the constructor signature.
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
