<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\Ai\DeepAnalysisService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Phase D — per-chunk NaraRouter call for Deep Analysis.
 *
 * One of these runs for every BATCH_SIZE (=100) contact cohort chunk. Loading
 * Conversation rows by id (not serialising a Collection) keeps the queue
 * payload small and avoids relationship-hydration surprises on the worker.
 *
 * Idempotency: writes its summary to a dedicated cache key keyed by (deep
 * analysis id, chunk index). The finalizer reads the N keys back and
 * aggregates. Missing keys are treated as empty summaries so one failed chunk
 * never kills the whole analysis (allowFailures() on the parent batch).
 *
 * No credit charges here — DeepAnalysisService::dispatch() already debited the
 * team BEFORE the parent orchestrator job even ran.
 */
class AnalyzeDeepAnalysisBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Per-chunk timeout.
     *
     * 2026-11-13 — bumped 90 → 180. The NaraRouter text chain has 4 models,
     * each with a 25s HTTP timeout. A worst-case cascade (primary timeout →
     * fallback timeout → free safety-net timeout → final model timeout) is
     * ~100s on big deep-analysis payloads (~30 KB per 100-contact batch).
     * The old 90s cap fired the queue worker's SIGALRM mid-cascade, so the
     * chunk failed with TimeoutExceededException just as the final model
     * was about to respond. 180 gives the full cascade + response processing
     * comfortable room while staying under the 300s parent-job timeout.
     */
    public int $timeout = 180;

    /** One retry allowed — NaraRouter transient 5xx/socket failures are common. */
    public int $tries = 2;

    /** TTL for the cached chunk summary — long enough for the batch to finish, short enough to self-clean. */
    public const CHUNK_CACHE_TTL_SECONDS = 3600;

    /**
     * @param  array<int, int>  $conversationIds
     */
    public function __construct(
        public int $deepAnalysisId,
        public int $chunkIndex,
        public array $conversationIds,
        public string $mode,
    ) {
        $this->onQueue('heavy-analysis');
    }

    public function handle(DeepAnalysisService $service): void
    {
        // If the parent batch was cancelled (eg. the DeepAnalysis row was
        // manually failed by an operator), skip work early.
        if ($this->batch()?->cancelled()) {
            return;
        }

        $conversations = Conversation::query()
            ->whereIn('id', $this->conversationIds)
            ->get();

        if ($conversations->isEmpty()) {
            Log::warning('AnalyzeDeepAnalysisBatchJob: no conversations found', [
                'deep_analysis_id' => $this->deepAnalysisId,
                'chunk_index'      => $this->chunkIndex,
                'requested_ids'    => count($this->conversationIds),
            ]);

            Cache::put(self::cacheKey($this->deepAnalysisId, $this->chunkIndex), [
                'themes' => [], 'hot_leads' => [], 'objections' => [], 'summary' => '',
            ], self::CHUNK_CACHE_TTL_SECONDS);

            return;
        }

        $summary = $service->analyzeBatch($conversations, $this->mode);

        Cache::put(
            self::cacheKey($this->deepAnalysisId, $this->chunkIndex),
            $summary,
            self::CHUNK_CACHE_TTL_SECONDS,
        );
    }

    public static function cacheKey(int $deepAnalysisId, int $chunkIndex): string
    {
        return "deep-analysis:{$deepAnalysisId}:chunk:{$chunkIndex}";
    }
}
