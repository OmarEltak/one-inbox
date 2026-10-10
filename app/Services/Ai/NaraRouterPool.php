<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Self-healing model pool for NaraRouter.
 *
 * Replaces the hardcoded NARAROUTER_TEXT_MODELS / NARAROUTER_VISION_MODELS env
 * strings. Fetches the list of free models from NaraRouter's /v1/models
 * endpoint and categorizes them by capability (text / vision / reasoning).
 *
 * Chain selection follows the capability-superset rule:
 *   - Vision task  → vision-capable models only. If none available, no fallback
 *                    is possible (text-only models literally cannot see images).
 *   - Reasoning    → reasoning-capable first (bigger context first), then any
 *                    text-capable model as degraded fallback.
 *   - Text         → any model that handles text. Prefer text-only (don't waste
 *                    vision capacity on text when a text-only model is free).
 *
 * Discovery runs nightly via the `nararouter:refresh-chain` command and on-demand
 * when a request can't find any available model (empty-pool self-healing).
 *
 * Cache keys:
 *   nararouter:pool:models            — array of {id, text, vision, reasoning, ctx, pricing}
 *   nararouter:pool:refreshed_at      — ISO8601 string of last successful refresh
 *   nararouter:pool:last_error        — last refresh error message if the API went down
 *   nararouter:pool:no_vision_alerted — rate-limit for the "vision chain empty" alert email
 */
final class NaraRouterPool
{
    /** 48h TTL — if refresh fails for 2 nights straight something is seriously wrong. */
    private const POOL_TTL_HOURS = 48;

    private const CACHE_POOL           = 'nararouter:pool:models';
    private const CACHE_REFRESHED_AT   = 'nararouter:pool:refreshed_at';
    private const CACHE_LAST_ERROR     = 'nararouter:pool:last_error';
    private const CACHE_NO_VISION_LOCK = 'nararouter:pool:no_vision_alerted';

    /** Alert cadence when vision chain is empty: at most 1 email per 6h. */
    private const NO_VISION_ALERT_RATE_LIMIT_HOURS = 6;

    /** @var array<int, string> model IDs to drop even when the API lists them */
    private array $blocked;

    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private ?string $alertEmail = null,
        ?string $blockedCsv = null,
    ) {
        $csv = $blockedCsv ?? (string) (config('services.nararouter.blocked_models') ?: '');
        $this->blocked = array_values(array_filter(array_map('trim', explode(',', $csv))));
    }

    /**
     * Return the ordered chain of model IDs for a given task kind.
     *
     * @param string $kind 'text' | 'vision' | 'reasoning'
     * @return array<int, string>
     */
    public function chainFor(string $kind): array
    {
        $pool = $this->currentPool();

        $filtered = match ($kind) {
            'vision'    => array_values(array_filter($pool, fn ($m) => $m['vision'] ?? false)),
            'reasoning' => $this->reasoningChain($pool),
            'text'      => $this->textChain($pool),
            default     => $this->textChain($pool),
        };

        return array_values(array_map(fn ($m) => (string) $m['id'], $filtered));
    }

    /**
     * Reasoning-first with text-capable fallback. Prefers larger context first.
     *
     * @param array<int, array<string, mixed>> $pool
     * @return array<int, array<string, mixed>>
     */
    private function reasoningChain(array $pool): array
    {
        $reasoners = array_values(array_filter($pool, fn ($m) => $m['reasoning'] ?? false));
        usort($reasoners, fn ($a, $b) => ($b['ctx'] ?? 0) <=> ($a['ctx'] ?? 0));

        $textOnly = array_values(array_filter(
            $pool,
            fn ($m) => ! ($m['reasoning'] ?? false) && ! ($m['vision'] ?? false) && ($m['text'] ?? false),
        ));
        usort($textOnly, fn ($a, $b) => ($b['ctx'] ?? 0) <=> ($a['ctx'] ?? 0));

        return array_merge($reasoners, $textOnly);
    }

    /**
     * Text chain is ordered by context size descending.
     *
     * NOTE 2026-10-10: an earlier version of this method tried to be clever —
     * put text-only models first "to preserve vision capacity for actual vision
     * tasks". That broke live AI replies for every customer: `jev` is text-only
     * but only has a 32k context and rejects every real conversation prompt
     * with HTTP 400 "The model rejected this request". Our provider treats 400
     * as a non-retriable payload error (correct behavior for Anthropic alternation
     * failures), so once `jev` 400s on real traffic the whole chain stops.
     *
     * Lesson: pick the most-reliable biggest-context models first; a small-ctx
     * model at the END of the chain is a safety net, at the START it's a time bomb.
     *
     * Vision preservation is a non-concern because:
     *   - Vision tasks query `chainFor('vision')` which filters to vision-capable
     *     models up front, so a text task consuming a vision-capable model does
     *     NOT prevent a later vision task from using the same model.
     *   - Our per-model failover state is tracked separately per chain
     *     (nararouter:failover_state:text / :vision) so vision/text usage
     *     doesn't pollute each other's health view.
     *
     * @param array<int, array<string, mixed>> $pool
     * @return array<int, array<string, mixed>>
     */
    private function textChain(array $pool): array
    {
        $sorted = $pool;
        usort($sorted, fn ($a, $b) => ($b['ctx'] ?? 0) <=> ($a['ctx'] ?? 0));
        return $sorted;
    }

    /**
     * Fetch /v1/models from NaraRouter, filter to models with zero per-token
     * input + output cost, categorize by capability, write to Redis.
     *
     * Returns true on success, false if the API returned an error.
     */
    public function refresh(): bool
    {
        try {
            // base_url can be configured as either "…/v1" or "…" — normalize so we
            // always hit "/models" on the correct version prefix. The provider's
            // chat endpoint uses the same convention via rtrim.
            $base = rtrim($this->baseUrl, '/');
            $url  = str_ends_with($base, '/v1') ? "{$base}/models" : "{$base}/v1/models";
            $r = Http::withToken($this->apiKey)->timeout(10)->get($url);

            if ($r->failed()) {
                $err = "HTTP {$r->status()}: " . substr($r->body(), 0, 200);
                Log::warning('NaraRouterPool refresh failed', ['error' => $err]);
                Cache::put(self::CACHE_LAST_ERROR, $err, now()->addHours(self::POOL_TTL_HOURS));
                return false;
            }

            $data = $r->json()['data'] ?? [];

            $free = array_values(array_filter($data, fn ($m) => $this->isFree($m)));

            // Drop blocklisted IDs — NaraRouter sometimes lists free-tier models
            // that reject every request with HTTP 400 (e.g. jev, exo-stealh on
            // 2026-10-10). Operator sets NARAROUTER_BLOCKED_MODELS to prevent
            // them polluting the chain until Nara fixes them.
            if (! empty($this->blocked)) {
                $free = array_values(array_filter(
                    $free,
                    fn ($m) => ! in_array((string) ($m['id'] ?? ''), $this->blocked, true),
                ));
            }

            $pool = array_map(function ($m) {
                return [
                    'id'        => (string) $m['id'],
                    'text'      => true, // any model exposed via chat/completions handles text
                    'vision'    => (bool) ($m['vision'] ?? false),
                    'reasoning' => (bool) ($m['reasoning'] ?? false),
                    'ctx'       => (int) ($m['context_window'] ?? 0),
                    'name'      => (string) ($m['name'] ?? $m['id']),
                ];
            }, $free);

            // Filter out zero-context models (e.g. agnes-video-v2.0 which is video-gen only
            // and ends up in the free list but can't serve chat/completions).
            $pool = array_values(array_filter($pool, fn ($m) => $m['ctx'] > 0));

            Cache::put(self::CACHE_POOL, $pool, now()->addHours(self::POOL_TTL_HOURS));
            Cache::put(self::CACHE_REFRESHED_AT, now()->toIso8601String(), now()->addHours(self::POOL_TTL_HOURS));
            Cache::forget(self::CACHE_LAST_ERROR);

            Log::info('NaraRouterPool refreshed', [
                'free_models_count' => count($pool),
                'ids'               => array_map(fn ($m) => $m['id'], $pool),
            ]);

            $this->maybeAlertIfVisionEmpty($pool);

            return true;
        } catch (\Throwable $e) {
            Log::warning('NaraRouterPool refresh threw', ['error' => $e->getMessage()]);
            Cache::put(self::CACHE_LAST_ERROR, $e->getMessage(), now()->addHours(self::POOL_TTL_HOURS));
            return false;
        }
    }

    /**
     * The current pool from cache, triggering a refresh if the cache is empty.
     * On refresh failure the pool stays empty — provider callers handle that
     * by consulting their own .env override (also operator-set, no hardcoded
     * names). We deliberately do NOT hardcode a model-name fallback here:
     * every model in the chain must come from either the live API or an
     * explicit operator-set env variable.
     *
     * @return array<int, array<string, mixed>>
     */
    private function currentPool(): array
    {
        $pool = Cache::get(self::CACHE_POOL);
        if (! is_array($pool) || empty($pool)) {
            $this->refresh();
            $pool = Cache::get(self::CACHE_POOL) ?: [];
        }

        return is_array($pool) ? $pool : [];
    }

    /**
     * NaraRouter marks a model as free when both input and output per-token
     * prices are zero. The webapp's "Free plan" tier is broader (plan-level
     * discount applies to some priced models), but we stay strict here —
     * zero-price-only means we can't be surprised by a bill.
     *
     * @param array<string, mixed> $m
     */
    private function isFree(array $m): bool
    {
        $input  = (float) ($m['input_idr_per_1k'] ?? 999);
        $output = (float) ($m['output_idr_per_1k'] ?? 999);
        return $input === 0.0 && $output === 0.0;
    }

    /**
     * If the newly-refreshed pool has zero vision-capable models AND we have an
     * alert email configured, email the operator so they can add a vision
     * fallback. Rate-limited to once per 6h.
     *
     * @param array<int, array<string, mixed>> $pool
     */
    private function maybeAlertIfVisionEmpty(array $pool): void
    {
        if ($this->alertEmail === null || $this->alertEmail === '') {
            return;
        }

        $visionCount = count(array_filter($pool, fn ($m) => $m['vision'] ?? false));
        if ($visionCount > 0) {
            return;
        }

        // One-shot lock to rate-limit.
        if (! Cache::add(self::CACHE_NO_VISION_LOCK, 1, now()->addHours(self::NO_VISION_ALERT_RATE_LIMIT_HOURS))) {
            return;
        }

        try {
            $body = <<<TXT
NaraRouter free vision pool is empty.

Every vision-capable free model has disappeared from the /v1/models response.
This means voice-note transcription and image understanding will fail with
no fallback — text-only models cannot process images.

Current pool refresh at: {$this->currentRefreshedAt()}
Models currently in the pool: {$this->debugPoolSummary($pool)}

What to do:
- Check router.bynara.id to see if a new free vision model is available.
- If so, run: php artisan nararouter:refresh-chain to pick it up now.
- If not, consider adding a paid vision model to the fallback list.
TXT;

            Mail::raw($body, function ($msg) {
                $msg->to($this->alertEmail)
                    ->subject('[OT1] NaraRouter free vision pool is empty');
            });

            Log::warning('NaraRouterPool: alerted on empty vision pool');
        } catch (\Throwable $e) {
            Log::error('NaraRouterPool: failed to send empty-vision alert', ['error' => $e->getMessage()]);
        }
    }

    public function currentRefreshedAt(): string
    {
        return (string) (Cache::get(self::CACHE_REFRESHED_AT) ?: 'never');
    }

    public function currentLastError(): ?string
    {
        $v = Cache::get(self::CACHE_LAST_ERROR);
        return is_string($v) && $v !== '' ? $v : null;
    }

    /**
     * @param array<int, array<string, mixed>> $pool
     */
    private function debugPoolSummary(array $pool): string
    {
        if (empty($pool)) {
            return '(empty)';
        }
        return implode(', ', array_map(
            fn ($m) => $m['id'] . '(' . (($m['vision'] ?? false) ? 'V' : '') . (($m['reasoning'] ?? false) ? 'R' : '') . ')',
            $pool,
        ));
    }

    /**
     * Everything the super-admin dashboard needs to render a status card.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $pool = $this->currentPool();
        return [
            'refreshed_at'    => $this->currentRefreshedAt(),
            'last_error'      => $this->currentLastError(),
            'pool_size'       => count($pool),
            'text_chain'      => $this->chainFor('text'),
            'vision_chain'    => $this->chainFor('vision'),
            'reasoning_chain' => $this->chainFor('reasoning'),
            'pool'            => $pool,
        ];
    }
}
