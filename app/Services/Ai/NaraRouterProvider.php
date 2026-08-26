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
 * ══ ARCHITECTURE REFERENCE §3, §4 ══
 * READ docs/ARCHITECTURE.md §4 (NaraRouter Failover Chain + 6h Reset)
 * BEFORE modifying callChat(), currentModel(), or markActiveModel().
 *
 * Load-bearing: successful calls do NOT extend the 6h reset window. If you
 * change markActiveModel() to refresh reset_at on every success, we'll
 * never return to sonnet after a fallback. That's a real regression, not
 * an optimization.
 *
 * NaraRouter provider — OpenAI-compatible chat completions router that fronts
 * multiple upstream models (Claude, GPT, Gemini, etc.). Selected via env:
 *
 *   AI_PROVIDER=nararouter
 *   NARAROUTER_MODEL=claude-sonnet-4.5
 *
 * Because the API speaks OpenAI's chat-completions format, this provider is
 * also the drop-in seed for using OpenAI, OpenRouter, Together, Groq, etc.
 * later — swap only the base URL and model name.
 */
class NaraRouterProvider implements AiProviderInterface
{
    use BuildsConversationPrompts;

    // Cache key used to remember which model is currently active on the
    // failover chain, plus a strict N-hour reset window. Structure:
    //   [ 'model' => 'mistral-medium-latest', 'reset_at' => 1719936000 ]
    protected const FAILOVER_STATE_CACHE_KEY = 'nararouter:failover_state';

    // Cache key used to remember which API key is currently active. Same shape:
    //   [ 'index' => 1, 'reset_at' => 1719936000 ]
    // Index 0 = primary, 1 = secondary. Rotation window is the same as models.
    protected const KEY_STATE_CACHE_KEY = 'nararouter:active_key_state';

    // Rate-limit for the "chain exhausted" alert email — at most one every N
    // minutes to avoid mailbox flooding during a sustained outage.
    protected const ALERT_RATE_LIMIT_MIN = 60;

    protected string $baseUrl;
    protected string $model;
    protected string $scoringModel;
    protected int $resetHours;
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

    /**
     * Ordered failover chain. Index 0 is the preferred model; we always try
     * to reset to it every FAILOVER_RESET_HOURS. When one model returns 5xx
     * we cascade to the next; when we run out, AiAllProvidersUnavailable
     * bubbles up and the customer-facing banner activates.
     *
     * Overridable via env NARAROUTER_FALLBACK_MODELS (comma-separated).
     *
     * @var array<int, string>
     */
    protected array $modelChain;

    public function __construct()
    {
        $this->apiKey       = (string) config('services.nararouter.api_key', '');
        $secondary          = (string) config('services.nararouter.api_key_secondary', '');
        $this->baseUrl      = rtrim(config('services.nararouter.base_url', 'https://router.bynara.id/v1'), '/');
        $this->model        = config('services.nararouter.model', 'agnes-2.5-flash');
        $this->scoringModel = config('services.nararouter.scoring_model', $this->model);
        $this->resetHours   = max(1, (int) config('services.nararouter.reset_hours', 5));
        $this->alertEmail   = config('services.nararouter.alert_email');

        // Build ordered key list — primary first, secondary next if provided.
        // De-dupe in case the same key is set twice by mistake.
        $this->apiKeys = array_values(array_unique(array_filter([
            $this->apiKey,
            $secondary,
        ])));

        $configuredChain = config('services.nararouter.fallback_models');
        $chainString = is_string($configuredChain) && $configuredChain !== ''
            ? $configuredChain
            : 'agnes-2.5-flash,agnes-2.0-flash,nemotron-3-ultra,qwen-3.8-max-free,deepseek-v4-flash,mistral-large';

        $this->modelChain = array_values(array_unique(array_filter(
            array_map(fn ($m) => trim($m), explode(',', $chainString)),
        )));
    }

    /**
     * Current model per cached failover state. Returns the head of the chain
     * whenever the cache is empty or the N-hour reset window has elapsed.
     */
    protected function currentModel(): string
    {
        $state = Cache::get(self::FAILOVER_STATE_CACHE_KEY);

        if (! $state || ($state['reset_at'] ?? 0) < time()) {
            return $this->modelChain[0] ?? $this->model;
        }

        $active = $state['model'] ?? null;
        if (in_array($active, $this->modelChain, true)) {
            return $active;
        }
        return $this->modelChain[0] ?? $this->model;
    }

    /**
     * Update the cached active model while preserving the original N-hour window.
     * If no window exists yet (first fallback event), open one now.
     */
    protected function markActiveModel(string $model): void
    {
        $state   = Cache::get(self::FAILOVER_STATE_CACHE_KEY);
        $resetAt = ($state['reset_at'] ?? null);

        // Preserve the existing window if any; otherwise open a fresh window
        // starting from this call. This ensures the reset behaviour is
        // measured from FIRST fallback, not from every successful call.
        if (! $resetAt || $resetAt < time()) {
            $resetAt = now()->addHours($this->resetHours)->timestamp;
        }

        Cache::put(
            self::FAILOVER_STATE_CACHE_KEY,
            ['model' => $model, 'reset_at' => $resetAt],
            now()->addHours($this->resetHours + 1),
        );
    }

    /**
     * Current API key index per cached failover state. Same reset semantics as
     * currentModel(): returns 0 (primary) when cache is empty or the window has
     * elapsed, otherwise the last known good key index.
     */
    protected function currentKeyIndex(): int
    {
        if (count($this->apiKeys) <= 1) {
            return 0;
        }
        $state = Cache::get(self::KEY_STATE_CACHE_KEY);
        if (! $state || ($state['reset_at'] ?? 0) < time()) {
            return 0;
        }
        $idx = (int) ($state['index'] ?? 0);
        return ($idx >= 0 && $idx < count($this->apiKeys)) ? $idx : 0;
    }

    /**
     * Preserve reset_at across success events, same as markActiveModel().
     */
    protected function markActiveKey(int $index): void
    {
        if (count($this->apiKeys) <= 1) {
            return;
        }
        $state   = Cache::get(self::KEY_STATE_CACHE_KEY);
        $resetAt = ($state['reset_at'] ?? null);
        if (! $resetAt || $resetAt < time()) {
            $resetAt = now()->addHours($this->resetHours)->timestamp;
        }
        Cache::put(
            self::KEY_STATE_CACHE_KEY,
            ['index' => $index, 'reset_at' => $resetAt],
            now()->addHours($this->resetHours + 1),
        );
    }

    /**
     * Email operator when the entire chain × every key attempt failed.
     * Rate-limited to at most one email per ALERT_RATE_LIMIT_MIN minutes so
     * a sustained outage doesn't flood the inbox.
     *
     * @param  array<int, string>  $attempts human-readable log lines
     */
    protected function sendExhaustionAlert(array $attempts, ?string $lastError): void
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
            $body = "NaraRouter chain fully exhausted at " . now()->toIso8601String() . " UTC\n\n"
                . "Last error: " . ($lastError ?? 'unknown') . "\n\n"
                . "Keys tried: " . count($this->apiKeys) . "\n"
                . "Chain: " . implode(', ', $this->modelChain) . "\n"
                . "Reset window: {$this->resetHours}h\n\n"
                . "Attempts (last " . min(count($attempts), 60) . " of " . count($attempts) . "):\n"
                . implode("\n", array_slice($attempts, -60)) . "\n\n"
                . "Action: refill NaraRouter quota, verify API keys, or wait for reset window.";

            Mail::raw($body, function ($msg) {
                $msg->to($this->alertEmail)
                    ->subject('[OT1] NaraRouter fully exhausted — AI replies stopped');
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

        return $this->callChat($this->model, $systemPrompt, $conversationHistory);
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
     * but called on the bound provider from AiChat.php — every provider must
     * implement it. Kept structurally identical to GeminiProvider so a straight
     * swap of AI_PROVIDER doesn't drift the admin's behavior.
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
     * OpenAI-compatible chat completions call. Prepends the system prompt as a
     * system-role message (in contrast to Gemini's separate system_instruction
     * field), and reads the reply from choices[0].message.content.
     */
    /**
     * The passed $model parameter is treated as a HINT of the caller's
     * preferred model; the actual model used is `currentModel()` at
     * dispatch time, which walks the failover chain and honors the 6h
     * reset window. On 5xx errors we cascade to the next chain entry;
     * on 429 we throw quota-exhausted; on exhaustion of the whole chain
     * we throw all-providers-unavailable. The initially-hinted model is
     * only used to decide where in the chain to start, so a scoring call
     * that prefers a cheaper model doesn't get bumped up to sonnet.
     */
    protected function callChat(string $model, string $systemPrompt, array $conversationHistory, int $maxOutputTokens = 1000): string
    {
        // Coalesce consecutive same-role turns. Anthropic's Messages API
        // (which NaraRouter proxies) requires user/assistant to strictly
        // alternate — violating it returns a generic 400 "parameter is invalid"
        // that per ARCHITECTURE §4 we intentionally do NOT cascade on. The
        // customer path can violate this whenever two outbound (AI + human)
        // or two inbound messages land in a row; the admin path violates it
        // when AiChat::confirmAction appends a "Done: …" turn after the AI
        // response. Guard here at the choke point so all four call sites
        // (generateResponse, scoreMessage, generateText, chatWithAdmin) are
        // covered and no future caller can re-introduce the bug.
        $tail = $this->coalesceRoles($conversationHistory);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        foreach ($tail as $m) {
            $messages[] = $m;
        }

        // Ensure the last user turn exists so the model has something to reply to.
        $lastRole = end($messages)['role'] ?? null;
        if ($lastRole !== 'user') {
            $messages[] = ['role' => 'user', 'content' => 'Continue the conversation naturally.'];
        }

        // Decide which model to try FIRST for this call. Prefer the cached
        // active model unless the caller hinted a specific (non-chain) model,
        // in which case honor that.
        $startModel = $this->currentModel();
        if (! in_array($startModel, $this->modelChain, true)) {
            $startModel = $this->modelChain[0] ?? $model;
        }

        // Build the try-order: start from $startModel and cascade down the
        // chain. If startModel is deep in the chain, we don't loop back up
        // (that's the point of the 6h reset — it will bring us back to top).
        $startIndex = array_search($startModel, $this->modelChain, true);
        $tryOrder = $startIndex === false
            ? $this->modelChain
            : array_slice($this->modelChain, $startIndex);

        // Nested iteration: for each model in the chain (starting at the
        // cached active model), try every configured API key in rotation
        // (starting at the cached active key). Semantics per status:
        //   200      → success. Mark model + key active. Return.
        //   400      → payload/request bug — retrying won't help. Return ''.
        //   401,402  → auth or payment on THIS key. Try next key, same model.
        //   403      → this key can't access this model. Try next key, same model.
        //   429      → rate limit / quota on THIS key. Try next key, same model.
        //   404      → model doesn't exist on any key. Cascade to next model.
        //   5xx / timeout → upstream flake. Cascade to next model.
        // Total exhaustion (every model × every key failed) → send email
        // alert + throw AiAllProvidersUnavailable.
        $lastError   = null;
        $attempts    = [];
        $startKeyIdx = $this->currentKeyIndex();
        $keyCount    = max(1, count($this->apiKeys));

        foreach ($tryOrder as $tryModel) {
            $keysExhaustedForThisModel = false;

            for ($k = 0; $k < $keyCount; $k++) {
                $keyIdx = ($startKeyIdx + $k) % $keyCount;
                $key    = $this->apiKeys[$keyIdx] ?? '';

                if ($key === '') {
                    continue;
                }

                $response = Http::withToken($key)
                    ->acceptJson()
                    ->asJson()
                    // 25s per-model attempt (not 60): a broken upstream should cascade
                    // to the next model in the chain quickly instead of eating the
                    // whole worker budget on one stuck request. connectTimeout(5) fails
                    // fast if the host itself is unreachable (DNS / TLS handshake stall).
                    ->connectTimeout(5)
                    ->timeout(25)
                    ->post("{$this->baseUrl}/chat/completions", [
                        'model'       => $tryModel,
                        'messages'    => $messages,
                        'temperature' => 0.7,
                        'max_tokens'  => $maxOutputTokens,
                    ]);

                if ($response->successful()) {
                    $this->markActiveModel($tryModel);
                    $this->markActiveKey($keyIdx);
                    return $response->json('choices.0.message.content', '');
                }

                $status = $response->status();
                $body   = substr($response->body(), 0, 240);
                $attempts[] = "model={$tryModel} key={$keyIdx} status={$status}";
                Log::warning('NaraRouter API call failed', [
                    'status'    => $status,
                    'model'     => $tryModel,
                    'key_index' => $keyIdx,
                    'body'      => $body,
                ]);

                // Payload bug — no key will fix it. Bail entirely.
                if ($status === 400) {
                    Log::error('NaraRouter API 400 — payload error, not retrying', [
                        'model' => $tryModel,
                        'body'  => $body,
                    ]);
                    return '';
                }

                // Auth / quota / rate on THIS key → try the next key with the
                // SAME model. Preserves "reliable" model chain intent — we don't
                // give up on a good model just because one key is temporarily
                // out of tokens.
                if (in_array($status, [401, 402, 403, 429], true)) {
                    $lastError = "HTTP {$status} on {$tryModel} (key {$keyIdx})";
                    if ($k === $keyCount - 1) {
                        $keysExhaustedForThisModel = true;
                    }
                    continue;
                }

                // 404 / 5xx / timeouts / other: upstream issue with THIS model.
                // Trying another key won't fix an upstream 502 — skip remaining
                // keys and cascade to the next model.
                $lastError = "HTTP {$status} on {$tryModel} (key {$keyIdx})";
                break;
            }

            // If every key was quota-exhausted for this model, keep going
            // through the chain — a lighter-weight model might still have
            // quota room. But if we truly ran through the whole chain here,
            // the outer foreach exit will trigger the exhaustion path below.
            unset($keysExhaustedForThisModel);
        }

        // Total exhaustion: every model × every key failed. Alert (rate-limited)
        // and surface as AiAllProvidersUnavailable so SendAiResponse's existing
        // handler puts the team on the 15-minute upstream-paused window with a
        // customer-facing banner.
        $this->sendExhaustionAlert($attempts, $lastError);
        throw new AiAllProvidersUnavailable(
            'All NaraRouter models × keys unavailable. Last error: ' . ($lastError ?? 'unknown')
        );
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
