<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiAllProvidersUnavailable;
use App\Exceptions\AiQuotaExhausted;
use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ══ ARCHITECTURE REFERENCE §4 ══
 * READ docs/ARCHITECTURE.md §4 (NaraRouter two-chain failover) and the
 * `nararouter-ops` + `nararouter-two-chain` skills BEFORE modifying anything
 * in this file.
 *
 * Two independent model chains (TEXT + VISION), each with its own reset window,
 * key rotation, and cached active-model pointer. Cross-chain fallback when a
 * chain is fully exhausted (text→vision, vision→text with image dropped).
 * Global 30-min cooldown when everything exhausts, so a sustained outage
 * doesn't cascade-hammer the workers on every queued message.
 *
 * LOAD-BEARING INVARIANTS (do not "optimize" these away — each has burned prod):
 *   1. markActiveModel/markActiveKey PRESERVE reset_at across successes. If you
 *      refresh reset_at on every 200, we never return to head-of-chain.
 *   2. coalesceRoles() output is string-typed content, called before every
 *      NaraRouter payload build (Anthropic Messages 400s on non-alternating).
 *   3. 400 → return '' (payload bug, retrying makes it worse).
 *      401/402/403/429 → next key, same model.
 *      404/5xx/timeout → next model, restart key rotation.
 *   4. Cache keys are per-chain suffixed (:text / :vision). Sharing them would
 *      cause text success to poison vision's starting point and vice versa.
 *   5. On full exhaustion of both chains: set nararouter:cooldown_until BEFORE
 *      throwing, so SendAiResponse can release-with-delay instead of retrying.
 */
class NaraRouterProvider implements AiProviderInterface
{
    use BuildsConversationPrompts;

    // Per-chain active-model cache (see invariant #4).
    protected const FAILOVER_STATE_TEXT   = 'nararouter:failover_state:text';
    protected const FAILOVER_STATE_VISION = 'nararouter:failover_state:vision';

    // Per-chain active-key cache. Same shape: ['index' => int, 'reset_at' => int].
    protected const KEY_STATE_TEXT   = 'nararouter:active_key_state:text';
    protected const KEY_STATE_VISION = 'nararouter:active_key_state:vision';

    // Global cooldown timestamp — when set, all callChat() invocations short-
    // circuit until this Unix seconds value passes. Set on full exhaustion of
    // both chains × both keys.
    protected const COOLDOWN_KEY = 'nararouter:cooldown_until';

    // Rate-limit for the "chain exhausted" alert email — at most one every N
    // minutes to avoid mailbox flooding during a sustained outage.
    protected const ALERT_RATE_LIMIT_MIN = 60;

    // Kind constants used throughout the provider to select which chain runs.
    public const KIND_TEXT   = 'text';
    public const KIND_VISION = 'vision';

    protected string $baseUrl;
    protected string $model;
    protected string $scoringModel;
    protected int $resetHours;
    protected int $exhaustionCooldownMin;
    protected ?string $alertEmail;

    /**
     * All API keys in preference order — primary first, secondary next.
     * Exactly one is used per request. On 401/402/403/429 from key K we
     * rotate to key K+1 for the SAME model. If every key fails on a model
     * we cascade to the next model and restart key rotation.
     *
     * @var array<int, string>
     */
    protected array $apiKeys;

    /** Legacy single-key reference, kept for BC in error messages / logs. */
    protected string $apiKey;

    /** @var array<int, string> Text-only chain. See config/services.php. */
    protected array $textChain;

    /** @var array<int, string> Vision-capable chain. See config/services.php. */
    protected array $visionChain;

    public function __construct()
    {
        $this->apiKey                = (string) config('services.nararouter.api_key', '');
        $secondary                   = (string) config('services.nararouter.api_key_secondary', '');
        $this->baseUrl               = rtrim(config('services.nararouter.base_url', 'https://router.bynara.id/v1'), '/');
        $this->model                 = config('services.nararouter.model', 'nemotron-3-ultra-free');
        $this->scoringModel          = config('services.nararouter.scoring_model', $this->model);
        $this->resetHours            = max(1, (int) config('services.nararouter.reset_hours', 5));
        $this->exhaustionCooldownMin = max(1, (int) config('services.nararouter.exhaustion_cooldown_min', 30));
        $this->alertEmail            = config('services.nararouter.alert_email');

        // Build ordered key list — primary first, secondary next if provided.
        // De-dupe in case the same key is set twice by mistake.
        $this->apiKeys = array_values(array_unique(array_filter([
            $this->apiKey,
            $secondary,
        ])));

        // Text chain: prefer NARAROUTER_TEXT_MODELS, fall back to the legacy
        // NARAROUTER_FALLBACK_MODELS for BC, then to a sensible built-in default.
        $textConfigured = config('services.nararouter.text_models');
        $legacyFallback = config('services.nararouter.fallback_models');
        $textString     = is_string($textConfigured) && $textConfigured !== ''
            ? $textConfigured
            : (is_string($legacyFallback) && $legacyFallback !== ''
                ? $legacyFallback
                : 'nemotron-3-ultra-free,nemotron-3-super-free,nemotron-3.5-lightning-free,agnes-2.5-flash');

        $this->textChain = $this->parseChain($textString);

        // Vision chain: explicit only. If not configured, default to agnes-2.5-flash
        // as the sole vision option (it's Nara's most reliable vision-capable model).
        $visionConfigured = config('services.nararouter.vision_models');
        $visionString     = is_string($visionConfigured) && $visionConfigured !== ''
            ? $visionConfigured
            : 'agnes-2.5-flash';

        $this->visionChain = $this->parseChain($visionString);
    }

    /**
     * Parse a comma-separated chain string into a deduplicated, trimmed array.
     *
     * @return array<int, string>
     */
    protected function parseChain(string $chainString): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn ($m) => trim($m), explode(',', $chainString)),
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function chainFor(string $kind): array
    {
        return $kind === self::KIND_VISION ? $this->visionChain : $this->textChain;
    }

    protected function stateCacheKey(string $kind): string
    {
        return $kind === self::KIND_VISION ? self::FAILOVER_STATE_VISION : self::FAILOVER_STATE_TEXT;
    }

    protected function keyCacheKey(string $kind): string
    {
        return $kind === self::KIND_VISION ? self::KEY_STATE_VISION : self::KEY_STATE_TEXT;
    }

    /**
     * Current model per cached failover state for the given chain. Returns the
     * head of that chain whenever the cache is empty or the N-hour reset window
     * has elapsed.
     */
    protected function currentModel(string $kind): string
    {
        $chain = $this->chainFor($kind);
        $state = Cache::get($this->stateCacheKey($kind));

        if (! $state || ($state['reset_at'] ?? 0) < time()) {
            return $chain[0] ?? $this->model;
        }

        $active = $state['model'] ?? null;
        if (in_array($active, $chain, true)) {
            return $active;
        }
        return $chain[0] ?? $this->model;
    }

    /**
     * Update the cached active model while preserving the original N-hour window.
     * If no window exists yet (first fallback event), open one now.
     */
    protected function markActiveModel(string $kind, string $model): void
    {
        $cacheKey = $this->stateCacheKey($kind);
        $state    = Cache::get($cacheKey);
        $resetAt  = ($state['reset_at'] ?? null);

        // Preserve the existing window if any; otherwise open a fresh window
        // starting from this call. This ensures the reset behaviour is
        // measured from FIRST fallback, not from every successful call.
        if (! $resetAt || $resetAt < time()) {
            $resetAt = now()->addHours($this->resetHours)->timestamp;
        }

        Cache::put(
            $cacheKey,
            ['model' => $model, 'reset_at' => $resetAt],
            now()->addHours($this->resetHours + 1),
        );
    }

    /**
     * Current API key index per cached state for the given chain. Same reset
     * semantics as currentModel(): returns 0 (primary) when cache is empty or
     * the window has elapsed, otherwise the last known good key index.
     */
    protected function currentKeyIndex(string $kind): int
    {
        if (count($this->apiKeys) <= 1) {
            return 0;
        }
        $state = Cache::get($this->keyCacheKey($kind));
        if (! $state || ($state['reset_at'] ?? 0) < time()) {
            return 0;
        }
        $idx = (int) ($state['index'] ?? 0);
        return ($idx >= 0 && $idx < count($this->apiKeys)) ? $idx : 0;
    }

    /**
     * Preserve reset_at across success events, same as markActiveModel().
     */
    protected function markActiveKey(string $kind, int $index): void
    {
        if (count($this->apiKeys) <= 1) {
            return;
        }
        $cacheKey = $this->keyCacheKey($kind);
        $state    = Cache::get($cacheKey);
        $resetAt  = ($state['reset_at'] ?? null);
        if (! $resetAt || $resetAt < time()) {
            $resetAt = now()->addHours($this->resetHours)->timestamp;
        }
        Cache::put(
            $cacheKey,
            ['index' => $index, 'reset_at' => $resetAt],
            now()->addHours($this->resetHours + 1),
        );
    }

    /**
     * True if the global cooldown is set and still in the future. When set,
     * dispatch() short-circuits without touching the network — the whole point
     * of the cooldown is protecting the workers during a sustained outage.
     */
    protected function isInCooldown(): bool
    {
        $until = (int) Cache::get(self::COOLDOWN_KEY, 0);
        return $until > time();
    }

    protected function cooldownRemainingSec(): int
    {
        $until = (int) Cache::get(self::COOLDOWN_KEY, 0);
        return max(0, $until - time());
    }

    /**
     * Set the global cooldown. Called on full exhaustion of BOTH chains ×
     * BOTH keys. SendAiResponse reads this to compute release-with-delay.
     */
    protected function setCooldown(): void
    {
        $until = now()->addMinutes($this->exhaustionCooldownMin)->timestamp;
        Cache::put(self::COOLDOWN_KEY, $until, now()->addMinutes($this->exhaustionCooldownMin + 5));
        Log::warning('NaraRouter global cooldown set', [
            'until'       => (string) now()->addMinutes($this->exhaustionCooldownMin),
            'minutes'     => $this->exhaustionCooldownMin,
        ]);
    }

    /**
     * Detect which chain an incoming message should run against. Any non-text
     * payload (image / video / audio / sticker / document) routes to vision.
     * Mirrors SendAiResponse::isMediaMessage() so the two agree.
     */
    public function detectMessageKind(Message $message): string
    {
        $contentType = (string) ($message->content_type ?? 'text');
        if ($contentType !== '' && $contentType !== 'text') {
            return self::KIND_VISION;
        }
        if (! empty($message->media_url) || ! empty($message->media_type)) {
            return self::KIND_VISION;
        }
        return self::KIND_TEXT;
    }

    /**
     * Email operator when both chains × every key attempt failed. Rate-limited
     * to at most one email per ALERT_RATE_LIMIT_MIN minutes so a sustained
     * outage doesn't flood the inbox.
     *
     * @param  array<string, array<int, string>>  $attemptsPerChain
     */
    protected function sendExhaustionAlert(array $attemptsPerChain, ?string $lastError): void
    {
        if (! $this->alertEmail) {
            return;
        }

        // One alert per hour bucket. Cache::add returns false if the key
        // already exists (atomic — safe under multi-worker races).
        $bucket  = (int) floor(time() / (self::ALERT_RATE_LIMIT_MIN * 60));
        $lockKey = "nararouter:alert_sent:{$bucket}";
        if (! Cache::add($lockKey, 1, now()->addMinutes(self::ALERT_RATE_LIMIT_MIN))) {
            return;
        }

        try {
            $lines = [
                'NaraRouter both chains fully exhausted at ' . now()->toIso8601String() . ' UTC',
                '',
                'Last error: ' . ($lastError ?? 'unknown'),
                '',
                'Keys tried: ' . count($this->apiKeys),
                'Text chain:   ' . implode(', ', $this->textChain),
                'Vision chain: ' . implode(', ', $this->visionChain),
                'Reset window: ' . $this->resetHours . 'h',
                'Global cooldown set: ' . $this->exhaustionCooldownMin . ' min',
                '',
            ];

            foreach ($attemptsPerChain as $chainName => $attempts) {
                if (empty($attempts)) {
                    continue;
                }
                $lines[] = strtoupper($chainName) . ' chain attempts (' . count($attempts) . '):';
                foreach (array_slice($attempts, -30) as $a) {
                    $lines[] = '  ' . $a;
                }
                $lines[] = '';
            }

            $lines[] = 'Action: refill NaraRouter quota, verify API keys, or wait '
                . $this->exhaustionCooldownMin . ' min for cooldown auto-clear.';

            $body = implode("\n", $lines);

            Mail::raw($body, function ($msg) {
                $msg->to($this->alertEmail)
                    ->subject('[OT1] NaraRouter both chains exhausted — AI replies stopped');
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send NaraRouter exhaustion alert email', [
                'error' => $e->getMessage(),
                'to'    => $this->alertEmail,
            ]);
        }
    }

    public function generateResponse(Conversation $conversation, Message $incomingMessage, AiConfig $config): string
    {
        $systemPrompt        = $this->buildSystemPrompt($conversation, $config);
        $conversationHistory = $this->buildConversationHistory($conversation);
        $kind                = $this->detectMessageKind($incomingMessage);

        return $this->dispatch($kind, $systemPrompt, $conversationHistory, 1000);
    }

    public function scoreMessage(Message $message, Contact $contact): array
    {
        $prompt = "Analyze this customer message and return JSON with lead scoring signals.\n\n"
            . "Customer message: \"{$message->content}\"\n"
            . "Current lead score: {$contact->lead_score}\n"
            . "Current status: {$contact->lead_status}\n\n"
            . "Return a JSON array of events. Each event has: event_type (string), score_change (int, -30 to +30), reason (string).\n"
            . "Scoring rules:\n"
            . "- Asked about pricing: +20\n"
            . "- Asked about availability: +15\n"
            . "- Asked for discount: +15\n"
            . "- Shared contact info (email/phone): +25\n"
            . "- Requested meeting/call: +30\n"
            . "- Mentioned competitor: +10\n"
            . "- Said 'not interested' or declining: -30\n"
            . "- Said 'too expensive' (objection but engaged): +5\n"
            . "- General question/interest: +5\n"
            . "- Greeting/casual: +3\n\n"
            . "Return ONLY valid JSON array, no other text.";

        $result = $this->callChat($this->scoringModel, 'You are a lead scoring AI. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");
            $events  = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);

            return is_array($events) ? $events : [];
        } catch (\JsonException $e) {
            Log::warning('AI scoring returned invalid JSON', ['response' => $result]);

            return [];
        }
    }

    public function analyzeConversation(Conversation $conversation): array
    {
        $history     = $this->buildConversationHistory($conversation);
        $historyText = collect($history)->map(fn ($m) => "{$m['role']}: {$m['content']}")->implode("\n");

        $prompt = "Analyze this sales conversation and return JSON with:\n"
            . "- summary: 1-2 sentence summary\n"
            . "- customer_intent: what the customer wants\n"
            . "- objections: array of objections raised\n"
            . "- recommended_action: what to do next\n"
            . "- sentiment: positive/neutral/negative\n\n"
            . "Conversation:\n{$historyText}\n\n"
            . "Return ONLY valid JSON, no other text.";

        $result = $this->callChat($this->scoringModel, 'You are a sales conversation analyst. Return only valid JSON.', [
            ['role' => 'user', 'content' => $prompt],
        ]);

        try {
            $cleaned = trim($result, " \t\n\r\0\x0B`json");

            return json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $e) {
            Log::warning('AI analysis returned invalid JSON', ['response' => $result]);

            return [];
        }
    }

    public function processCommand(string $command, int $teamId): array
    {
        return [
            'response' => 'Command processing will be available soon.',
            'action'   => null,
        ];
    }

    public function generateText(string $systemPrompt, string $userMessage): string
    {
        return $this->callChat($this->model, $systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ]);
    }

    /**
     * Admin Command Center chat. Not part of the AiProviderInterface contract
     * but called on the bound provider from AiChat.php.
     */
    public function chatWithAdmin(string $message, int $teamId, string $analyticsContext, array $history): string
    {
        $systemPrompt = $this->buildAdminChatSystemPrompt($teamId, $analyticsContext);

        // Use the last entries from history (already limited by caller), skip the current message (last entry)
        $conversationHistory = array_slice($history, 0, -1);
        $conversationHistory[] = ['role' => 'user', 'content' => $message];

        // Admin path: we want a visible message when things break (unlike the
        // customer path which stays silent). Catch quota + outage specifically
        // so the operator knows what happened; treat empty as a generic error.
        try {
            $response = $this->callChat($this->model, $systemPrompt, $conversationHistory, 2000);
        } catch (AiQuotaExhausted) {
            return 'The AI service is temporarily unavailable — daily quota reached. Try again after the quota resets, or upgrade your plan.';
        } catch (AiAllProvidersUnavailable) {
            return 'The AI service is temporarily unavailable — every model is returning errors. Please try again in a few minutes; if this persists, contact support.';
        }

        if ($response === '') {
            return 'The AI service is temporarily unavailable (API error). Please try again in a few minutes.';
        }

        return $response;
    }

    /**
     * BC entry point for non-message callers (scoring, analysis, admin chat,
     * generateText). Always routes through the TEXT chain — these paths have
     * no incoming Message to inspect for vision content, and they're safe to
     * cross-fall to vision if text is exhausted (vision models handle text).
     *
     * The $modelHint parameter is retained for signature compatibility but
     * only used as an escape hatch: if it names a model outside both chains,
     * we honor it as a single-model direct call (no cascade). Historically
     * this was the scoring path's way to prefer a cheap model — with the
     * new chain design, use NARAROUTER_SCORING_MODEL for that instead.
     */
    protected function callChat(string $modelHint, string $systemPrompt, array $conversationHistory, int $maxOutputTokens = 1000): string
    {
        // If the caller hinted a model outside both chains, honor it as a
        // one-shot direct call. Preserves BC for anyone who was pinning a
        // specific model outside the failover system.
        if ($modelHint !== ''
            && ! in_array($modelHint, $this->textChain, true)
            && ! in_array($modelHint, $this->visionChain, true)) {
            return $this->directCall($modelHint, $systemPrompt, $conversationHistory, $maxOutputTokens);
        }

        return $this->dispatch(self::KIND_TEXT, $systemPrompt, $conversationHistory, $maxOutputTokens);
    }

    /**
     * Top-level orchestrator: checks global cooldown, runs the primary chain
     * for the given kind, falls through to the other chain on exhaustion, and
     * on total exhaustion sets the cooldown + sends the alert + throws.
     */
    protected function dispatch(string $kind, string $systemPrompt, array $conversationHistory, int $maxOutputTokens): string
    {
        // Global cooldown gate — µs check, saves us the entire cascade during
        // a sustained outage. SendAiResponse reads the same cache key to
        // compute release-with-delay.
        if ($this->isInCooldown()) {
            throw new AiAllProvidersUnavailable(
                'NaraRouter global cooldown active — ' . $this->cooldownRemainingSec() . 's remaining'
            );
        }

        $tail = $this->coalesceRoles($conversationHistory);

        // Primary chain first.
        $primaryResult = $this->runChain($kind, $systemPrompt, $tail, $maxOutputTokens, false);

        if ($primaryResult['status'] === 'success') {
            return $primaryResult['reply'];
        }

        if ($primaryResult['status'] === 'payload_error') {
            // 400 — retrying on another chain won't help either.
            return '';
        }

        // Primary chain exhausted. Fall through to the OTHER chain in degraded
        // mode. Vision → text drops images entirely, so add a system note that
        // the model can't see them.
        $otherKind = $kind === self::KIND_VISION ? self::KIND_TEXT : self::KIND_VISION;
        $degradedSystemPrompt = $kind === self::KIND_VISION
            ? $systemPrompt . "\n\nNOTE: The customer sent an image, but the current AI model cannot process images. If they reference an image, politely ask them to describe it in text or resend."
            : $systemPrompt;

        Log::info('NaraRouter primary chain exhausted, falling through to secondary', [
            'primary_kind'   => $kind,
            'secondary_kind' => $otherKind,
        ]);

        $secondaryResult = $this->runChain($otherKind, $degradedSystemPrompt, $tail, $maxOutputTokens, true);

        if ($secondaryResult['status'] === 'success') {
            return $secondaryResult['reply'];
        }

        if ($secondaryResult['status'] === 'payload_error') {
            return '';
        }

        // Both chains exhausted. Set cooldown, email alert, throw.
        $this->setCooldown();
        $this->sendExhaustionAlert(
            [
                $kind      => $primaryResult['attempts'],
                $otherKind => $secondaryResult['attempts'],
            ],
            $secondaryResult['last_error'] ?? $primaryResult['last_error']
        );

        throw new AiAllProvidersUnavailable(
            'All NaraRouter chains × keys unavailable. Last error: '
                . ($secondaryResult['last_error'] ?? $primaryResult['last_error'] ?? 'unknown')
                . '. Global cooldown set for ' . $this->exhaustionCooldownMin . ' min.'
        );
    }

    /**
     * Iterate a single chain × all keys. Returns a status envelope:
     *   ['status' => 'success',       'reply' => '...', 'attempts' => [...], 'last_error' => null]
     *   ['status' => 'payload_error', 'reply' => '',    'attempts' => [...], 'last_error' => 'HTTP 400 ...']
     *   ['status' => 'exhausted',     'reply' => '',    'attempts' => [...], 'last_error' => 'HTTP xxx ...']
     *
     * The $isFallback flag is currently informational only — logged so the ops
     * team can see in journalctl whether a reply came from the primary or the
     * fallback chain.
     *
     * @param  array<int, array{role: string, content: string}>  $tail  already coalesced history
     * @return array{status: string, reply: string, attempts: array<int, string>, last_error: ?string}
     */
    protected function runChain(string $kind, string $systemPrompt, array $tail, int $maxOutputTokens, bool $isFallback): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        foreach ($tail as $m) {
            $messages[] = $m;
        }
        $lastRole = end($messages)['role'] ?? null;
        if ($lastRole !== 'user') {
            $messages[] = ['role' => 'user', 'content' => 'Continue the conversation naturally.'];
        }

        $chain = $this->chainFor($kind);
        if (empty($chain)) {
            return [
                'status'     => 'exhausted',
                'reply'      => '',
                'attempts'   => [],
                'last_error' => "no models configured for {$kind} chain",
            ];
        }

        $startModel = $this->currentModel($kind);
        $startIndex = array_search($startModel, $chain, true);
        $tryOrder   = $startIndex === false ? $chain : array_slice($chain, $startIndex);

        $lastError   = null;
        $attempts    = [];
        $startKeyIdx = $this->currentKeyIndex($kind);
        $keyCount    = max(1, count($this->apiKeys));

        foreach ($tryOrder as $tryModel) {
            for ($k = 0; $k < $keyCount; $k++) {
                $keyIdx = ($startKeyIdx + $k) % $keyCount;
                $key    = $this->apiKeys[$keyIdx] ?? '';

                if ($key === '') {
                    continue;
                }

                $response = Http::withToken($key)
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(5)
                    ->timeout(25)
                    ->post("{$this->baseUrl}/chat/completions", [
                        'model'       => $tryModel,
                        'messages'    => $messages,
                        'temperature' => 0.7,
                        'max_tokens'  => $maxOutputTokens,
                    ]);

                if ($response->successful()) {
                    $this->markActiveModel($kind, $tryModel);
                    $this->markActiveKey($kind, $keyIdx);
                    Log::info('NaraRouter reply', [
                        'chain'    => $kind,
                        'fallback' => $isFallback,
                        'model'    => $tryModel,
                        'key'      => $keyIdx,
                    ]);
                    return [
                        'status'     => 'success',
                        'reply'      => (string) $response->json('choices.0.message.content', ''),
                        'attempts'   => $attempts,
                        'last_error' => null,
                    ];
                }

                $status = $response->status();
                $body   = substr($response->body(), 0, 240);
                $attempts[] = "model={$tryModel} key={$keyIdx} status={$status}";
                Log::warning('NaraRouter API call failed', [
                    'chain'     => $kind,
                    'fallback'  => $isFallback,
                    'status'    => $status,
                    'model'     => $tryModel,
                    'key_index' => $keyIdx,
                    'body'      => $body,
                ]);

                // Payload bug — no key or model will fix it.
                if ($status === 400) {
                    Log::error('NaraRouter API 400 — payload error, not retrying', [
                        'model' => $tryModel,
                        'body'  => $body,
                    ]);
                    return [
                        'status'     => 'payload_error',
                        'reply'      => '',
                        'attempts'   => $attempts,
                        'last_error' => "HTTP 400 on {$tryModel} — {$body}",
                    ];
                }

                // Auth / payment / rate on THIS key → try next key, same model.
                if (in_array($status, [401, 402, 403, 429], true)) {
                    $lastError = "HTTP {$status} on {$tryModel} (key {$keyIdx})";
                    continue;
                }

                // 404 / 5xx / timeout: upstream issue with THIS model — cascade.
                $lastError = "HTTP {$status} on {$tryModel} (key {$keyIdx})";
                break;
            }
        }

        return [
            'status'     => 'exhausted',
            'reply'      => '',
            'attempts'   => $attempts,
            'last_error' => $lastError,
        ];
    }

    /**
     * One-shot direct call bypassing both chains + failover. Used only when a
     * caller passes a model hint that isn't in either configured chain (BC
     * escape hatch for hard-pinned model names). No cache updates, no cascade.
     */
    protected function directCall(string $model, string $systemPrompt, array $conversationHistory, int $maxOutputTokens): string
    {
        if ($this->isInCooldown()) {
            throw new AiAllProvidersUnavailable(
                'NaraRouter global cooldown active — ' . $this->cooldownRemainingSec() . 's remaining'
            );
        }

        $tail = $this->coalesceRoles($conversationHistory);
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($tail as $m) {
            $messages[] = $m;
        }
        $lastRole = end($messages)['role'] ?? null;
        if ($lastRole !== 'user') {
            $messages[] = ['role' => 'user', 'content' => 'Continue the conversation naturally.'];
        }

        $key = $this->apiKeys[0] ?? '';
        if ($key === '') {
            return '';
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(25)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.7,
                'max_tokens'  => $maxOutputTokens,
            ]);

        if ($response->successful()) {
            return (string) $response->json('choices.0.message.content', '');
        }

        Log::warning('NaraRouter direct call failed (out-of-chain model hint)', [
            'model'  => $model,
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 240),
        ]);
        return '';
    }

    /**
     * Collapse consecutive same-role turns into a single turn, dropping empty
     * content. The Anthropic Messages API (and NaraRouter's OpenAI-compat
     * proxy over it) rejects requests where user/assistant do not strictly
     * alternate. See ARCHITECTURE §4.
     *
     * Public to enable unit tests to pin the invariant without touching the
     * network.
     *
     * @param  array<int, array{role: string, content?: string|null}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    public function coalesceRoles(array $history): array
    {
        $out = [];
        $coalesced = 0;
        foreach ($history as $msg) {
            $role = ($msg['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $content = (string) ($msg['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $lastIdx = array_key_last($out);
            if ($lastIdx !== null && $out[$lastIdx]['role'] === $role) {
                $out[$lastIdx]['content'] .= "\n\n" . $content;
                $coalesced++;
            } else {
                $out[] = ['role' => $role, 'content' => $content];
            }
        }
        if ($coalesced > 0) {
            Log::info('NaraRouter coalesced consecutive same-role turns', ['count' => $coalesced]);
        }
        return $out;
    }
}
