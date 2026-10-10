<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Exceptions\Billing\ExpensiveActionRequiresConfirmationException;
use App\Jobs\DispatchDeepAnalysisJob;
use App\Models\Conversation;
use App\Models\DeepAnalysis;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Services\Billing\AiCredits;
use Illuminate\Support\Facades\DB;

/**
 * Phase D — Deep Analysis orchestrator (spec §5).
 *
 * `quote()` is pure (no side effects) so AiChat can price a request before
 * showing the confirmation modal. `dispatch()` charges credits FIRST (so a
 * user cannot game a 50-credit action by closing the tab mid-run), then
 * creates the DeepAnalysis row and queues DispatchDeepAnalysisJob.
 *
 * Cost formula (spec §5.1):
 *   max(deep_analysis_minimum, ceil(cohort_size / 100) * deep_analysis_per_100_contacts)
 *
 * The job itself calls back into `analyzeBatch()` + `aggregate()` for chunked
 * NaraRouter calls; keeping those methods on the service means the job stays
 * thin and the AI-shaped prompt logic is testable in isolation.
 */
final class DeepAnalysisService
{
    /** Hard cap on cohort size to prevent runaway costs even with confirmation. */
    public const MAX_COHORT_SIZE = 5000;

    /**
     * Set by dispatch(): true when the last call returned a cached result, false
     * when it dispatched a fresh job. Lets callers (AiChat) show the right chat
     * message ('here is the earlier run' vs 'analysis dispatched, be patient').
     */
    public bool $wasCacheHit = false;

    public function __construct(
        private readonly AiCredits $credits,
    ) {
    }

    /**
     * Price a cohort without any side effect. Returned $cost already includes
     * the per-100 scaling + minimum floor from config/ai_costs.php.
     *
     * Mode-specific floor (Phase H): for `agent_audit`, cost is
     * `max(ai_costs.agent_audit, ceil(cohort_size/100) * deep_analysis_per_100_contacts)`.
     * That makes a 1-conversation audit as cheap as 3 credits (the agent_audit
     * floor) while a 1000-conversation audit still scales to 50 — same slope
     * as a themes analysis, since the backing NaraRouter work is comparable.
     *
     * @param  array<string, mixed>  $cohortFilter
     * @return array{cost: int, cohort_size: int, description: string}
     */
    public function quote(Team $team, array $cohortFilter, string $mode = DeepAnalysis::MODE_CUSTOMER_THEMES): array
    {
        $cohortSize = min(self::MAX_COHORT_SIZE, $this->countCohort($team, $cohortFilter));

        $per100  = max(1, (int) config('ai_costs.deep_analysis_per_100_contacts', 5));
        $themesMinimum = max(0, (int) config('ai_costs.deep_analysis_minimum', 10));

        $scaled = (int) ceil($cohortSize / 100) * $per100;

        if ($mode === DeepAnalysis::MODE_AGENT_AUDIT) {
            $auditFloor = max(0, (int) config('ai_costs.agent_audit', 3));
            $cost = (int) max($auditFloor, $scaled);
        } else {
            $cost = (int) max($themesMinimum, $scaled);
        }

        return [
            'cost'        => $cost,
            'cohort_size' => $cohortSize,
            'description' => $this->describe($team, $cohortFilter, $cohortSize),
        ];
    }

    /**
     * Charge credits + queue the job. Call sites catch
     * ExpensiveActionRequiresConfirmationException and surface the modal,
     * then re-call with meta.confirmation_token set to bypass the gate.
     *
     * @param  array<string, mixed>  $cohortFilter
     * @param  array<string, mixed>  $chargeMeta  passthrough: idempotency_key, confirmation_token
     * @throws ExpensiveActionRequiresConfirmationException
     */
    /**
     * How recent a prior analysis has to be to serve as a cache hit (hours).
     * 2026-10-10: trimmed from 24h → 2h. 24h was wrong — a workday brings in
     * new customer messages that an operator asking the same question later
     * likely wants reflected in the result. 2h still catches 'asked twice
     * within a session' which is the primary save-credits case, without
     * serving stale analysis from the morning when the operator asks again
     * after lunch.
     */
    public const CACHE_WINDOW_HOURS = 2;

    public function dispatch(
        Team $team,
        ?int $userId,
        array $cohortFilter,
        string $mode = DeepAnalysis::MODE_CUSTOMER_THEMES,
        array $chargeMeta = [],
    ): DeepAnalysis {
        // Cache check — if the same (team, mode, cohort filter shape) was run
        // in the last CACHE_WINDOW_HOURS and completed successfully, return
        // the stored row free. UI shows a 'cached · 0 credits' badge + a
        // 'Re-run with fresh data' button so operators can override when they
        // need the latest state. Caller can bypass with
        // chargeMeta['force_fresh'] = true (the Re-run button does this).
        //
        // 2026-10-10 window shortened 24h → 2h after an operator got a stale
        // 'customer_themes of 3086 contacts' from yesterday when they wanted
        // a per-contact review of today's 100 messages. The ACTUAL fix for
        // 'wrong shape' problems is intent-aware mode detection (per-contact
        // review vs theme summary should pick different modes which hash
        // differently — not yet shipped). The 2h window + opt-out are the
        // belt-and-suspenders until that lands.
        $this->wasCacheHit = false;
        if (empty($chargeMeta['force_fresh'])) {
            $cached = $this->findCachedAnalysis($team, $cohortFilter, $mode);
            if ($cached !== null) {
                $this->wasCacheHit = true;
                \App\Events\DeepAnalysisCompleted::dispatch(
                    (int) $team->id,
                    (int) $cached->id,
                    (string) $cached->mode,
                    (int) $cached->cohort_size,
                    ($cached->completed_at ?? now())->toIso8601String(),
                    true,  // success
                    null,  // error
                    true,  // cached
                );
                return $cached;
            }
        }

        $quote = $this->quote($team, $cohortFilter, $mode);
        $cohortSize = (int) $quote['cohort_size'];
        $totalCost  = (int) $quote['cost'];

        // AiCredits::charge() resolves the cost per action via config('ai_costs.X')
        // and does not natively support cohort-scaled pricing. We register a
        // one-shot config key with the exact computed cost and charge against
        // THAT key, so the ledger row's reason + amount both accurately reflect
        // what the operator paid. The scaled key is namespaced by the run's
        // cohort size so concurrent dispatches do not step on each other.
        $actionKey = 'deep_analysis_run_' . $cohortSize . '_' . bin2hex(random_bytes(4));
        config()->set("ai_costs.{$actionKey}", $totalCost);

        return DB::transaction(function () use ($team, $userId, $cohortFilter, $mode, $chargeMeta, $cohortSize, $totalCost, $actionKey) {
            // Charge FIRST — any confirmation exception propagates to the
            // caller BEFORE we persist a DeepAnalysis row.
            $this->credits->charge(
                team: $team,
                action: $actionKey,
                meta: array_merge($chargeMeta, [
                    'actor_user_id'    => $userId,
                    'cost_source_type' => DeepAnalysis::class,
                    'cohort_size'      => $cohortSize,
                    'mode'             => $mode,
                    'scaled_from'      => 'deep_analysis_per_100_contacts',
                ]),
            );

            $analysis = DeepAnalysis::create([
                'team_id'              => $team->id,
                'triggered_by_user_id' => $userId,
                'mode'                 => $mode,
                'cohort_filter'        => $cohortFilter,
                'cohort_size'          => $cohortSize,
                'credits_charged'      => $totalCost,
                'status'               => DeepAnalysis::STATUS_QUEUED,
            ]);

            // Dispatch after the row exists; the job hydrates by id so Horizon
            // retries (we set tries=1 but still) always see the row.
            \App\Jobs\DispatchDeepAnalysisJob::dispatch($analysis->id)
                ->onQueue('heavy-analysis');

            return $analysis;
        });
    }

    /**
     * Look up a recent successful analysis matching (team, mode, cohort shape).
     * The cohort hash is a canonical signature of cohort_filter — identical
     * filter shape produces identical hash regardless of key order. This is
     * what makes "top objections for last 100 contacts" asked at 10:00 reuse
     * the result of the same query asked at 09:00.
     *
     * Returns null if no cacheable result exists in the window.
     *
     * @param  array<string, mixed>  $cohortFilter
     */
    public function findCachedAnalysis(Team $team, array $cohortFilter, string $mode): ?DeepAnalysis
    {
        $hash  = self::canonicalCohortHash($cohortFilter);
        $since = now()->subHours(self::CACHE_WINDOW_HOURS);

        return DeepAnalysis::query()
            ->where('team_id', $team->id)
            ->where('mode', $mode)
            ->where('status', DeepAnalysis::STATUS_COMPLETED)
            ->where('completed_at', '>=', $since)
            // JSON column: ksort before persisting isn't guaranteed, so we
            // read-side the stored row and hash it the same way we hash the
            // incoming filter. Cheap: completed analyses in the 24h window
            // are a very small set (dozens, not thousands) per team.
            ->orderByDesc('completed_at')
            ->get()
            ->first(function (DeepAnalysis $a) use ($hash) {
                return self::canonicalCohortHash((array) ($a->cohort_filter ?? [])) === $hash;
            });
    }

    /**
     * Canonical signature of a cohort filter array — recursively sorted keys
     * so {"page":1,"limit":100} and {"limit":100,"page":1} hash identically.
     * Pure function, safe to call from static contexts in tests.
     *
     * @param  array<string, mixed>  $filter
     */
    public static function canonicalCohortHash(array $filter): string
    {
        $sort = function (&$v) use (&$sort) {
            if (is_array($v)) {
                if (array_is_list($v)) {
                    foreach ($v as &$e) {
                        $sort($e);
                    }
                } else {
                    ksort($v);
                    foreach ($v as &$e) {
                        $sort($e);
                    }
                }
            }
        };
        $sort($filter);
        return hash('sha256', json_encode($filter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Count the contacts a cohort filter would resolve to. Used by quote()
     * and the dispatched job's cohort loader. Kept DB-only (no model
     * instantiation) so pricing a 5000-contact cohort is cheap.
     *
     * @param  array<string, mixed>  $cohortFilter
     */
    public function countCohort(Team $team, array $cohortFilter): int
    {
        $query = $this->cohortQuery($team, $cohortFilter);

        return (int) $query->distinct()->count('conversations.contact_id');
    }

    /**
     * Load the actual Conversation rows for the job. Capped at MAX_COHORT_SIZE
     * even if the filter would otherwise match more.
     *
     * @param  array<string, mixed>  $cohortFilter
     * @return \Illuminate\Support\Collection<int, Conversation>
     */
    public function loadCohort(Team $team, array $cohortFilter): \Illuminate\Support\Collection
    {
        $limit = min(
            self::MAX_COHORT_SIZE,
            (int) ($cohortFilter['limit'] ?? self::MAX_COHORT_SIZE),
        );

        return $this->cohortQuery($team, $cohortFilter)
            ->orderByDesc('conversations.last_message_at')
            ->limit($limit)
            ->get()
            ->unique('contact_id')
            ->values();
    }

    /**
     * Summarize one batch of up to ~100 conversations into a structured JSON
     * block. The system prompt instructs the model to output JSON only —
     * aggregator consumes those blocks and compresses them into the final
     * result stored on the DeepAnalysis row.
     *
     * @param  array<int, Conversation>|\Illuminate\Support\Collection<int, Conversation>  $conversations
     * @return array<string, mixed>
     */
    public function analyzeBatch(iterable $conversations, string $mode): array
    {
        $transcripts = [];
        foreach ($conversations as $conversation) {
            $transcripts[] = $this->renderTranscript($conversation);
        }

        $joined = implode("\n\n---\n\n", $transcripts);

        $systemPrompt = $this->systemPromptForBatch($mode);
        $userPrompt   = "Here are the customer conversations to analyze. Return STRICTLY valid JSON (no prose, no markdown fences):\n\n" . $joined;

        $provider = app(AiProviderInterface::class);
        $raw = $provider->generateText($systemPrompt, $userPrompt);

        return $this->parseJson($raw);
    }

    /**
     * Phase H — Agent audit result for a cohort. Unlike analyzeBatch (which
     * chunks per 100 conversations), the audit is a single table-aggregated
     * SQL query plus one NaraRouter prose summary. The returned array has:
     *
     *   - stats:   array<int, array{...}> (shape documented on AgentAuditService::perAgentStats)
     *   - summary: string prose evaluation written by the model
     *
     * The job stores this directly in `deep_analyses.result_json`.
     *
     * @param  array<string, mixed>  $cohortFilter
     * @return array{stats: array<int, array<string, mixed>>, summary: string}
     */
    public function analyzeAgentAudit(Team $team, array $cohortFilter): array
    {
        /** @var AgentAuditService $auditor */
        $auditor = app(AgentAuditService::class);
        $stats   = $auditor->perAgentStats($team, $cohortFilter);

        if ($stats === []) {
            return [
                'stats'   => [],
                'summary' => 'No outbound agent messages found for this cohort — nothing to audit.',
            ];
        }

        $summary = $this->summarizeAgentAudit($stats);

        return [
            'stats'   => $stats,
            'summary' => $summary,
        ];
    }

    /**
     * Single NaraRouter call that turns the raw per-agent stats array into a
     * 3-5 paragraph prose evaluation. Keeps the agent names + the raw numbers
     * in the output so the operator can cross-reference.
     *
     * @param  array<int, array<string, mixed>>  $stats
     */
    protected function summarizeAgentAudit(array $stats): string
    {
        $systemPrompt = "You are a performance analyst for a customer-support team. Given per-agent statistics, write a 3-5 paragraph prose evaluation. Reference agents by name, cite specific numbers (messages sent, avg response time, conversion rate), and flag anything that stands out — e.g. an agent who answers fast but converts poorly, or vice versa. Do NOT invent data; stay strictly within the numbers provided. If an agent is 'Unattributed', note that it represents messages the system couldn't credit to a user and should not be analyzed as a person. End with 1-2 concrete suggestions. Plain prose only — no markdown headings, no bullet lists, no JSON.";
        $userPrompt = "Per-agent stats to evaluate:\n\n" . json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $provider = app(AiProviderInterface::class);
        $raw = $provider->generateText($systemPrompt, $userPrompt);

        $trimmed = trim($raw);

        return $trimmed === '' ? 'The AI returned no summary. Review the raw per-agent stats directly.' : $trimmed;
    }

    /**
     * Compress N batch summaries into the final result. One extra NaraRouter
     * call so the stored result is prose-ready without the operator paying
     * per-batch at follow-up time.
     *
     * @param  array<int, array<string, mixed>>  $batchSummaries
     * @return array<string, mixed>
     */
    public function aggregate(array $batchSummaries, string $mode): array
    {
        if ($batchSummaries === []) {
            return ['themes' => [], 'hot_leads' => [], 'objections' => [], 'summary' => 'No conversations analyzed.'];
        }

        if (count($batchSummaries) === 1) {
            return $batchSummaries[0];
        }

        $systemPrompt = "You consolidate multiple JSON analysis blobs into one final JSON blob with the same shape. Deduplicate themes and hot_leads by name, sum counts across blobs where appropriate, and write a 2-3 sentence 'summary' field. Return STRICTLY valid JSON — no prose, no markdown fences.";
        $userPrompt   = "Blobs to merge:\n\n" . json_encode($batchSummaries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $provider = app(AiProviderInterface::class);
        $raw = $provider->generateText($systemPrompt, $userPrompt);

        return $this->parseJson($raw);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $cohortFilter
     */
    protected function cohortQuery(Team $team, array $cohortFilter): \Illuminate\Database\Eloquent\Builder
    {
        $query = Conversation::query()
            ->where('conversations.team_id', $team->id);

        if (! empty($cohortFilter['page_id'])) {
            $query->where('conversations.page_id', (int) $cohortFilter['page_id']);
        }

        if (! empty($cohortFilter['contact_ids']) && is_array($cohortFilter['contact_ids'])) {
            $query->whereIn('conversations.contact_id', array_map('intval', $cohortFilter['contact_ids']));
        }

        if (! empty($cohortFilter['days'])) {
            $query->where('conversations.last_message_at', '>=', now()->subDays((int) $cohortFilter['days']));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $cohortFilter
     */
    protected function describe(Team $team, array $cohortFilter, int $cohortSize): string
    {
        $parts = ["Deep analysis of {$cohortSize} contacts"];

        if (! empty($cohortFilter['page_id'])) {
            $page = Page::where('team_id', $team->id)->find((int) $cohortFilter['page_id']);
            if ($page) {
                $parts[] = "for {$page->name}";
            }
        }

        if (! empty($cohortFilter['days'])) {
            $parts[] = "(last {$cohortFilter['days']} days)";
        }

        return implode(' ', $parts);
    }

    protected function renderTranscript(Conversation $conversation): string
    {
        $name = $conversation->contact?->name ?? ('Contact #' . $conversation->contact_id);
        $header = "Contact: {$name} | Platform: {$conversation->platform} | Stage: {$conversation->sales_stage}";

        $msgs = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->limit(20)
            ->get(['direction', 'content', 'sender_type']);

        $lines = [$header];
        foreach ($msgs as $m) {
            $who = $m->direction === 'inbound' ? 'CUSTOMER' : 'US';
            $content = \Illuminate\Support\Str::limit((string) $m->content, 400);
            $lines[] = "{$who}: {$content}";
        }

        return implode("\n", $lines);
    }

    protected function systemPromptForBatch(string $mode): string
    {
        if ($mode === DeepAnalysis::MODE_AGENT_AUDIT) {
            return "You audit the quality of human-agent replies in customer conversations. For the batch provided, return a JSON object with keys: {agents: [{name, response_count, avg_response_quality_1_5, example_quote}], issues: [string], recommendations: [string], summary: string}. Return STRICTLY valid JSON — no prose, no markdown fences.";
        }

        return "You analyze customer conversation transcripts and extract sales-relevant themes. For the batch provided, return a JSON object with keys: {themes: [{name, count, example_quote}], hot_leads: [{name, reason, suggested_message}], objections: [{objection, count, suggested_response}], summary: string}. Base every claim on the actual transcripts — do not invent data. Return STRICTLY valid JSON — no prose, no markdown fences.";
    }

    /**
     * Parse a model response that may wrap JSON in prose or markdown fences
     * despite the "no fences" instruction. We prefer tolerant parsing over
     * hard failure because the whole job is already paid for.
     *
     * @return array<string, mixed>
     */
    protected function parseJson(string $raw): array
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return ['summary' => 'Empty response from AI.', 'raw' => ''];
        }

        // Strip common ```json fences the model emits anyway.
        $stripped = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $trimmed) ?? $trimmed;
        $stripped = trim($stripped);

        try {
            $decoded = json_decode($stripped, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (\JsonException) {
            // Fall through to find a JSON object inside the prose.
        }

        if (preg_match('/\{[\s\S]*\}/', $stripped, $match)) {
            try {
                $decoded = json_decode($match[0], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\JsonException) {
                // give up — return raw so operators can still see something
            }
        }

        return ['summary' => 'AI returned unparseable output.', 'raw' => $stripped];
    }
}
