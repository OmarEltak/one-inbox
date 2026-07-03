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
    // failover chain, plus a strict 6-hour reset window. Structure:
    //   [ 'model' => 'mistral-medium-latest', 'reset_at' => 1719936000 ]
    protected const FAILOVER_STATE_CACHE_KEY = 'nararouter:failover_state';
    protected const FAILOVER_RESET_HOURS     = 6;

    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected string $scoringModel;

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
        $this->apiKey       = config('services.nararouter.api_key', '');
        $this->baseUrl      = rtrim(config('services.nararouter.base_url', 'https://router.bynara.id/v1'), '/');
        $this->model        = config('services.nararouter.model', 'claude-sonnet-4.5');
        $this->scoringModel = config('services.nararouter.scoring_model', $this->model);

        $configuredChain = config('services.nararouter.fallback_models');
        $chainString = is_string($configuredChain) && $configuredChain !== ''
            ? $configuredChain
            : 'claude-sonnet-4.5,mistral-medium-latest,mistral-large-latest,claude-haiku-4.5';

        $this->modelChain = array_values(array_unique(array_filter(
            array_map(fn ($m) => trim($m), explode(',', $chainString)),
        )));
    }

    /**
     * Current model per cached failover state. Returns the head of the chain
     * (default: claude-sonnet-4.5) whenever the cache is empty or the 6h
     * reset window has elapsed. This is where the "reset to sonnet every
     * 6 hours" behaviour lives.
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
     * Update the cached active model while preserving the original 6h window.
     * If no window exists yet (first fallback event), open one now.
     */
    protected function markActiveModel(string $model): void
    {
        $state   = Cache::get(self::FAILOVER_STATE_CACHE_KEY);
        $resetAt = ($state['reset_at'] ?? null);

        // Preserve the existing window if any; otherwise open a fresh 6h one
        // starting from this call. This ensures the "reset every 6h" behaviour
        // is measured from FIRST fallback, not from every successful call.
        if (! $resetAt || $resetAt < time()) {
            $resetAt = now()->addHours(self::FAILOVER_RESET_HOURS)->timestamp;
        }

        Cache::put(
            self::FAILOVER_STATE_CACHE_KEY,
            ['model' => $model, 'reset_at' => $resetAt],
            now()->addHours(self::FAILOVER_RESET_HOURS + 1), // outer TTL is just cleanup
        );
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

        $lastError = null;

        foreach ($tryOrder as $tryModel) {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model'       => $tryModel,
                    'messages'    => $messages,
                    'temperature' => 0.7,
                    'max_tokens'  => $maxOutputTokens,
                ]);

            if ($response->successful()) {
                // Successful reply — remember this model as active for the
                // remainder of the 6h window so we go here directly next time.
                $this->markActiveModel($tryModel);
                return $response->json('choices.0.message.content', '');
            }

            $status = $response->status();
            Log::warning('NaraRouter API call failed, cascading', [
                'status' => $status,
                'model'  => $tryModel,
                'body'   => substr($response->body(), 0, 240),
            ]);

            // 429 = provider-wide quota / rate limit on our account. No point
            // trying other models — the account itself is blocked.
            if ($status === 429) {
                throw new AiQuotaExhausted("NaraRouter 429 on {$tryModel} (quota/rate limit).");
            }

            // Client errors on the request payload/key/permission won't be
            // fixed by trying a different model — bail immediately.
            //   400 Bad Request, 401 Unauthorized, 403 Forbidden
            // But 404 (model doesn't exist) SHOULD cascade — the next model
            // in the chain might exist.
            if (in_array($status, [400, 401, 403], true)) {
                Log::error('NaraRouter API 4xx — not cascading', ['status' => $status, 'model' => $tryModel]);
                return '';
            }

            // Cascade: 404, 5xx, timeouts.
            $lastError = "HTTP {$status} on {$tryModel}";
        }

        // Every model in the chain returned 5xx.
        throw new AiAllProvidersUnavailable('All NaraRouter models unavailable. Last error: ' . ($lastError ?? 'unknown'));
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
