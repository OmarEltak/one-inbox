<?php

namespace App\Livewire;

use App\Exceptions\Billing\ExpensiveActionRequiresConfirmationException;
use App\Jobs\SendPlatformMessage;
use App\Models\AiCommand;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\DeepAnalysis;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Contracts\AiProviderInterface;
use App\Services\Ai\AdminChatContext;
use App\Services\Ai\DeepAnalysisService;
use App\Services\Billing\AiCredits;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class AiChat extends Component
{
    use WithFileUploads;

    /** Platforms where Meta only accepts outbound within 24h of the contact's last message. */
    protected const META_WINDOW_PLATFORMS = ['facebook', 'instagram'];

    public string $message = '';

    public array $messages = [];

    public ?array $pendingAction = null;

    public string $pendingActionSummary = '';

    /**
     * Phase D — pending expensive action awaiting modal confirmation. Shape:
     * ['cost' => int, 'balance_after' => int, 'action_token' => string,
     *  'description' => string, 'cohort_filter' => array, 'mode' => string].
     */
    public ?array $pendingExpensiveAction = null;

    /** Modal checkbox — persists to teams.auto_deduct_expensive_actions on confirm. */
    public bool $autoDeductOptIn = false;

    #[Validate('nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx')]
    public $attachment = null;

    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return;
        }

        // Load the last 30 turns. Pending rows (user clicked Send then
        // navigated away before the job finished) render as a typing-dots
        // bubble so the user knows their question is still being processed —
        // the Reverb listener swaps it in-place when the job broadcasts.
        $this->messages = AiCommand::where('team_id', $team->id)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->reverse()
            ->flatMap(function (AiCommand $cmd) {
                $assistant = $cmd->status === 'pending'
                    ? ['role' => 'assistant', 'content' => '…', 'pending' => true, 'command_id' => $cmd->id]
                    : ['role' => 'assistant', 'content' => (string) $cmd->response];
                return [['role' => 'user', 'content' => $cmd->command], $assistant];
            })
            ->values()
            ->all();
    }

    /**
     * Phase D — Reverb channel wiring. On DeepAnalysisCompleted, append a
     * chat message so the operator knows the stored result is available
     * for follow-up questions on their next turn.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $teamId = Auth::user()?->currentTeam?->id;
        if (! $teamId) {
            return [];
        }

        return [
            "echo-private:team.{$teamId},DeepAnalysisCompleted" => 'handleDeepAnalysisCompleted',
            "echo-private:team.{$teamId},AiChatTurnCompleted"   => 'handleAiChatTurnCompleted',
        ];
    }

    /**
     * Reverb listener — the ProcessAiChatTurn job finished. Find the pending
     * placeholder bubble by command_id and swap it in-place for the real
     * response. If no placeholder exists (user had navigated away and is now
     * on another page or the Livewire instance was already remounted) the
     * no-op is correct: mount() will load the completed row on next visit.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleAiChatTurnCompleted(array $payload): void
    {
        $commandId = (int) ($payload['command_id'] ?? 0);
        $userId    = (int) ($payload['user_id'] ?? 0);
        $response  = (string) ($payload['response'] ?? '');

        if ($userId !== (int) Auth::id()) {
            return; // belongs to a different user on the same team
        }

        $swapped = false;
        foreach ($this->messages as $i => $m) {
            if (! empty($m['pending']) && ($m['command_id'] ?? null) === $commandId) {
                $this->messages[$i] = ['role' => 'assistant', 'content' => $response];
                $swapped = true;
                break;
            }
        }

        if (! $swapped) {
            // User came back to the chat AFTER the job finished but BEFORE the
            // Reverb event arrived (race). Append rather than lose the response.
            $this->messages[] = ['role' => 'assistant', 'content' => $response];
        }

        $this->dispatch('message-sent');
    }

    /**
     * Reverb listener — appends the "ready" message. The stored result is
     * injected into the next prompt by BuildsConversationPrompts automatically.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleDeepAnalysisCompleted(array $payload): void
    {
        $cohortSize = (int) ($payload['cohort_size'] ?? 0);

        $this->messages[] = [
            'role'    => 'assistant',
            'content' => __('✅ Deep analysis of :count contacts complete. Ask me what you want to know about them.', [
                'count' => $cohortSize,
            ]),
        ];

        // Browser notification so the user knows even if the tab is in the
        // background or they came back after closing the page. The browser
        // listener in ai-chat.blade.php picks this up and calls Notification
        // if permission was granted (falls through silently otherwise).
        $this->dispatch('deep-analysis-ready-notify', cohortSize: $cohortSize);
        $this->dispatch('message-sent');
    }

    /**
     * One-tap questions that use what the assistant can actually see (chat
     * content, reach, lead status) — real marketing work, not generic chips.
     *
     * @return array<int, array{label: string, prompt: string}>
     */
    public function suggestions(): array
    {
        return [
            ['label' => __('What customers want'), 'prompt' => __('Read my customer chats from the last 30 days. What do customers ask for most, and which products or services are most requested? Give counts and real quotes with names.')],
            ['label' => __('Top problems & objections'), 'prompt' => __('What are the biggest complaints, problems and objections in my chats? For each: how often it comes up, a real quote, and the exact reply I should use.')],
            ['label' => __('Who is ready to buy'), 'prompt' => __('Which contacts are closest to buying right now? List the top 5 with the reason (quote their message) and the exact message I should send each, in their language.')],
            ['label' => __('Win back quiet leads'), 'prompt' => __('Find interested leads who went quiet in the last 2 weeks. Write a short win-back message for them in the language and dialect they use, and tell me who can be reached on which channel.')],
            ['label' => __('Why deals are lost'), 'prompt' => __('Look at conversations that did not convert. Why did customers drop off? Give the top reasons with real examples and what to change in my offer or AI replies.')],
            ['label' => __('This week vs last week'), 'prompt' => __('How did this week go compared to last week (conversations, messages, AI vs human replies, new contacts)? Give 3 concrete actions to improve next week.')],
        ];
    }

    public function useSuggestion(int $index): void
    {
        $suggestion = $this->suggestions()[$index] ?? null;
        if (! $suggestion) {
            return;
        }

        $this->message = $suggestion['prompt'];
        $this->sendMessage();
    }

    /**
     * Operators think in names: drop "(ID: 11)", ", ID: 11", "contact ID:123"
     * the model copies from its context into prose. Action JSON is already
     * stripped by now, so this never touches what gets executed. A bare
     * "order ID: 5531" in a customer quote is left alone.
     */
    protected static function stripInternalIds(string $text): string
    {
        $text = preg_replace('/\s*\(\s*(?:(?:page|contact|campaign)[\s_]*)?id\s*[:#=]?\s*\d+\s*\)/iu', '', $text) ?? $text;
        $text = preg_replace('/,\s*(?:(?:page|contact|campaign)[\s_]*)?id\s*[:#=]\s*\d+/iu', '', $text) ?? $text;
        $text = preg_replace('/\b(?:page|contact|campaign)[\s_]*id\s*[:#=]?\s*\d+/iu', '', $text) ?? $text;

        return trim(preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text);
    }

    /**
     * Public passthrough for the async job (ProcessAiChatTurn) which runs
     * outside this component but needs the same ID-strip rules so the stored
     * response matches what sendMessage() used to persist inline.
     */
    public static function stripInternalIdsPublic(string $text): string
    {
        return self::stripInternalIds($text);
    }

    protected static function isConfirmation(string $text): bool
    {
        return (bool) preg_match(
            '/^(send|send it|yes|yep|ok|okay|confirm|go|go ahead|do it|sure|ابعت|ابعتها|ابعته|ارسل|أرسل|ارسلها|تمام|نعم|اه|آه|ايوه|أيوه|موافق|يلا)[\s.!]*$/iu',
            trim($text)
        );
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    /**
     * True when the current user has a queued/running Deep Analysis on their
     * current team. Used to idempotency-lock the chat input + surface a status
     * banner so a user who refreshes or returns in a new tab doesn't fire a
     * duplicate paid analysis. Cheap: covered by the (team_id, user_id, status)
     * index on deep_analyses.
     */
    public function hasRunningDeepAnalysis(): bool
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        if (! $team) {
            return false;
        }

        return DeepAnalysis::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [DeepAnalysis::STATUS_QUEUED, DeepAnalysis::STATUS_RUNNING])
            ->exists();
    }

    public function sendMessage(): void
    {
        $text = trim($this->message);
        $hasAttachment = $this->attachment !== null;

        if ($text === '' && ! $hasAttachment) {
            return;
        }

        // Typing "send" / "yes" / "ابعت" with an action waiting confirms it,
        // instead of going back to the AI (which used to ask for an ID again).
        if ($this->pendingAction && ! $hasAttachment && self::isConfirmation($text)) {
            $this->message = '';
            $this->messages[] = ['role' => 'user', 'content' => $text];
            $this->confirmAction();

            return;
        }

        // Deep Analysis idempotency lock. A paid async analysis is in flight for
        // this (team, user); block new turns so a reload / second tab / impatient
        // retry doesn't dispatch a duplicate paid job. Posting any text while
        // locked shows the "still processing" message and consumes nothing.
        if ($this->hasRunningDeepAnalysis()) {
            $this->message = '';
            $this->messages[] = ['role' => 'user', 'content' => $text];
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => __('Your previous analysis is still being processed in the background. You can close this page — I\'ll notify you when it\'s ready, and the result will be waiting here. Please wait before starting another analysis.'),
            ];
            $this->dispatch('message-sent');
            return;
        }

        $mediaUrl = null;
        $mediaType = null;

        if ($hasAttachment) {
            $this->validate();
            $team = Auth::user()->currentTeam;
            $teamId = $team?->id ?? 0;
            $path = $this->attachment->store("chat-media/{$teamId}", 'public');
            $mediaUrl = asset('storage/' . $path);
            $mediaType = $this->attachment->getMimeType();
            $this->attachment = null;
        }

        $this->message = '';
        $msgEntry = ['role' => 'user', 'content' => $text ?: '[Shared a file]'];
        if ($mediaUrl) {
            $msgEntry['media_url'] = $mediaUrl;
            $msgEntry['media_type'] = $mediaType;
        }
        $this->messages[] = $msgEntry;

        $team = Auth::user()->currentTeam;

        if (! $team) {
            $this->messages[] = ['role' => 'assistant', 'content' => 'No team selected.'];

            return;
        }

        // Phase D — Deep Analysis detection (spec §5.1). "analyze last 1000
        // contacts", "deep dive on brandk contacts", etc. route into the
        // paid async path INSTEAD of calling NaraRouter inline. Catches the
        // confirmation exception from AiCredits::charge and surfaces the
        // modal; auto-dispatches if cost is under the 5-credit threshold or
        // the team has opted into auto-deduct.
        if ($this->tryDispatchDeepAnalysis($team, $text)) {
            $this->dispatch('message-sent');
            return;
        }

        // 2026-10-08 UX fix: persist the AiCommand IMMEDIATELY (status=pending)
        // and dispatch ProcessAiChatTurn to do the slow NaraRouter call in the
        // queue. Before this, sendMessage() blocked 5-15s on the AI call — a
        // user who clicked Send and navigated away lost the whole request
        // because wire:submit was cancelled mid-flight. Now:
        //   - Send press → AiCommand row exists in ~50ms, user text is safe.
        //   - Job runs chatWithAdmin, writes response, broadcasts.
        //   - If user is on page: handleAiChatTurnCompleted swaps the placeholder.
        //   - If user navigated away: mount() loads the completed row next visit.
        $command = AiCommand::create([
            'team_id'  => $team->id,
            'user_id'  => Auth::id(),
            'command'  => $text,
            'response' => '',
            'status'   => 'pending',
        ]);

        // Snapshot the exact history the user saw, so the model gets the same
        // context whether this runs now or 30 seconds from now in the queue.
        $historySnapshot = collect($this->messages)
            ->filter(fn ($m) => $m['role'] === 'user' || $m['role'] === 'assistant')
            ->slice(-12)
            ->skipUntil(fn ($m) => $m['role'] === 'user')
            ->map(fn ($m) => ['role' => $m['role'], 'content' => Str::limit((string) $m['content'], 2000)])
            ->values()
            ->all();

        \App\Jobs\ProcessAiChatTurn::dispatch($command->id, $historySnapshot);

        // Optimistic placeholder — swapped by handleAiChatTurnCompleted when
        // the job finishes. The 'pending' marker lets the Blade render a
        // typing dots animation instead of an empty bubble.
        $this->messages[] = [
            'role'       => 'assistant',
            'content'    => '…',
            'pending'    => true,
            'command_id' => $command->id,
        ];

        $this->dispatch('message-sent');
    }

    /**
     * Call NaraRouter with idempotency + retry. Phase C of the AI credit
     * economy spec (§6): a double-click, re-dispatch, or page re-render used to
     * trigger two chat turns and two credit charges; the Redis idempotency key
     * dedupes within a 60s window. Retries smooth over transient 5xx/empty
     * responses before falling back to the "took too long" error path.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     *
     * @throws \Throwable  final attempt failure propagates to the caller
     */
    protected function callAiWithRetry(int $teamId, int $userId, string $operatorMessage, string $analyticsContext, array $history): string
    {
        $idempotencyKey = 'aichat:idem:' . hash('sha256', $teamId . '|' . $userId . '|' . trim($operatorMessage));

        $cached = Cache::get($idempotencyKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $provider = app(AiProviderInterface::class);
        $backoffMs = [0, 500, 2000];
        $lastThrowable = null;

        foreach ($backoffMs as $delay) {
            if ($delay > 0) {
                usleep($delay * 1000);
            }

            try {
                $response = $provider->chatWithAdmin($operatorMessage, $teamId, $analyticsContext, $history);

                if (is_string($response) && trim($response) !== '') {
                    Cache::put($idempotencyKey, $response, 60);

                    return $response;
                }
            } catch (\Throwable $e) {
                $lastThrowable = $e;
            }
        }

        if ($lastThrowable !== null) {
            throw $lastThrowable;
        }

        // All attempts returned empty. Surface as a throwable so the caller's
        // catch block takes over (which logs and shows the user-friendly msg).
        throw new \RuntimeException('AI provider returned an empty response after 3 attempts.');
    }

    public function confirmAction(): void
    {
        if (! $this->pendingAction) {
            return;
        }

        $team = Auth::user()->currentTeam;

        if (! $team) {
            return;
        }

        try {
            $result = $this->runAction($this->pendingAction, $team->id);
        } catch (\Throwable $e) {
            Log::error('AI Chat confirmed action failed', ['error' => $e->getMessage(), 'action' => $this->pendingAction]);
            $result = "Action failed: {$e->getMessage()}";
        }

        $this->pendingAction = null;
        $this->pendingActionSummary = '';

        $done = __('Done: :result', ['result' => $result]);
        $this->messages[] = ['role' => 'assistant', 'content' => $done];

        // Persist the real outcome ("queued to 2, skipped 96…") on the turn
        // that proposed it, so a reload shows what happened instead of only
        // the AI's pre-confirmation draft.
        $lastCommand = AiCommand::where('team_id', $team->id)->where('user_id', Auth::id())->latest('id')->first();
        $lastCommand?->update(['response' => trim($lastCommand->response . "\n\n" . $done)]);

        $this->dispatch('message-sent');
    }

    public function cancelAction(): void
    {
        $this->pendingAction = null;
        $this->pendingActionSummary = '';

        $this->messages[] = ['role' => 'assistant', 'content' => __('Action cancelled.')];
        $this->dispatch('message-sent');
    }

    /**
     * Phase D — detect "analyze last N contacts [for page X]" / "deep
     * analysis of ..." / "audit the last N conversations" patterns in the
     * operator's current message. When matched, quote cost → either open
     * the confirmation modal or dispatch the DeepAnalysis job immediately
     * (cost ≤ threshold OR team.auto_deduct_expensive_actions = true).
     *
     * Returns true when the detection fired (so sendMessage() short-circuits
     * the normal NaraRouter call for this turn).
     */
    protected function tryDispatchDeepAnalysis(Team $team, string $text): bool
    {
        $parsed = $this->parseDeepAnalysisRequest($team, $text);
        if ($parsed === null) {
            return false;
        }

        /** @var DeepAnalysisService $service */
        $service = app(DeepAnalysisService::class);
        $quote = $service->quote($team, $parsed['cohort_filter'], $parsed['mode']);

        if ((int) $quote['cohort_size'] <= 0) {
            $this->messages[] = ['role' => 'assistant', 'content' => __('No contacts matched that filter — nothing to analyze.')];
            return true;
        }

        // Fast path — under the threshold OR team opted in. Dispatch now.
        try {
            $analysis = $service->dispatch(
                team: $team,
                userId: (int) Auth::id(),
                cohortFilter: $parsed['cohort_filter'],
                mode: $parsed['mode'],
            );

            $this->messages[] = [
                'role'    => 'assistant',
                'content' => __('Analysis dispatched. I\'ll let you know when it\'s ready — usually 60-120 seconds. You can keep chatting about other things.'),
            ];

            $this->persistTurn($team, $text, (string) end($this->messages)['content']);

            return true;
        } catch (ExpensiveActionRequiresConfirmationException $e) {
            // Modal path — stash the pending action, re-dispatch on confirm.
            $this->pendingExpensiveAction = [
                'cost'          => $e->cost,
                'balance_after' => $e->balanceAfter,
                'action_token'  => $e->actionToken,
                'description'   => (string) $quote['description'],
                'cohort_filter' => $parsed['cohort_filter'],
                'mode'          => $parsed['mode'],
                'operator_text' => $text,
            ];

            $this->autoDeductOptIn = (bool) $team->auto_deduct_expensive_actions;

            Flux::modal('deep-analysis-confirm')->show();

            return true;
        }
    }

    /**
     * Confirmation handler — re-dispatches the pending action with the
     * confirmation_token so AiCredits::charge bypasses the gate. Persists
     * the "always auto-deduct" opt-in if the operator ticked the checkbox.
     */
    public function confirmExpensiveAction(): void
    {
        if (! $this->pendingExpensiveAction) {
            return;
        }

        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }

        if ($this->autoDeductOptIn && ! $team->auto_deduct_expensive_actions) {
            $team->update(['auto_deduct_expensive_actions' => true]);
        }

        $pending = $this->pendingExpensiveAction;
        $this->pendingExpensiveAction = null;

        try {
            /** @var DeepAnalysisService $service */
            $service = app(DeepAnalysisService::class);
            $service->dispatch(
                team: $team->fresh() ?? $team,
                userId: (int) Auth::id(),
                cohortFilter: (array) $pending['cohort_filter'],
                mode: (string) $pending['mode'],
                chargeMeta: ['confirmation_token' => (string) $pending['action_token']],
            );

            $this->messages[] = [
                'role'    => 'assistant',
                'content' => __('Analysis dispatched. I\'ll let you know when it\'s ready — usually 60-120 seconds. You can keep chatting about other things.'),
            ];
        } catch (\Throwable $e) {
            Log::error('AiChat::confirmExpensiveAction failed', ['error' => $e->getMessage()]);
            $this->messages[] = ['role' => 'assistant', 'content' => __('Could not start the analysis: :msg', ['msg' => $e->getMessage()])];
        } finally {
            Flux::modal('deep-analysis-confirm')->close();
            $this->dispatch('message-sent');
        }
    }

    public function cancelExpensiveAction(): void
    {
        $this->pendingExpensiveAction = null;
        $this->autoDeductOptIn = false;

        Flux::modal('deep-analysis-confirm')->close();

        $this->messages[] = ['role' => 'assistant', 'content' => __('Analysis cancelled.')];
        $this->dispatch('message-sent');
    }

    /**
     * Pattern-match the operator's text for Deep Analysis intent. Keeps
     * detection in one place so the quote/dispatch call sites can't drift
     * from each other.
     *
     * @return array{cohort_filter: array<string, mixed>, mode: string}|null
     */
    protected function parseDeepAnalysisRequest(Team $team, string $text): ?array
    {
        $lower = mb_strtolower($text);

        // Original strict regex — kept for "analyze last 500 contacts" style
        // phrasings where the verb directly precedes the cohort.
        $isDeep = (bool) preg_match(
            '/\b(deep\s+(analysis|dive)|analyz(e|ing)?\s+(the\s+)?(last\s+)?(all\s+)?\d*\s*(contacts|conversations|chats|customers)|analyz(e|ing)?\s+(all\s+)?contacts?\s+for)\b/iu',
            $lower
        );

        // Broader detection — fires when ANY analysis-verb AND a cohort-noun
        // both appear in the message, regardless of order. Catches the natural
        // phrasings that burned a real operator complaint on 2026-10-08:
        //   "read the 3000 contacts and analyze them"
        //   "go through all my contacts and tell me who's hot"
        //   "review my conversations and find patterns"
        // Without this, the inline NaraRouter path runs with only 25 convos in
        // context and the AI (correctly) says "I cannot read 3,000 contacts".
        if (! $isDeep) {
            $hasAnalysisVerb = (bool) preg_match(
                '/\b(analyz(e|ing|ed|es)|analysis|read|review|go\s+through|look\s+(at|through)|dive\s+(in|into)|study|check|investigate|examine|insight|summar(y|ize|ise|ies)|understand|segment|categor(y|ize|ise)|cluster|find\s+(patterns|insights|trends))\b/iu',
                $lower
            );
            $hasCohortNoun = (bool) preg_match(
                '/\b(contacts?|conversations?|chats?|customers?|leads?|threads?|messages?|convos?|all\s+(my\s+)?(data|people))\b/iu',
                $lower
            );
            $hasBigNumber = (bool) preg_match(
                '/\b([2-9]\d|\d{3,})\b/u',
                $lower
            );
            $hasAllOrEveryone = (bool) preg_match(
                '/\b(all|every|everyone|whole|entire|every single)\b/iu',
                $lower
            );

            // Trigger if (verb + noun + N≥20) OR (verb + noun + "all/every").
            // 20 is the floor — below that, the inline NaraRouter can handle it
            // from the 25-convo digest and we don't want to charge Deep Analysis.
            if ($hasAnalysisVerb && $hasCohortNoun && ($hasBigNumber || $hasAllOrEveryone)) {
                $isDeep = true;
            }
        }

        // Phase H — three distinct phrasings route into the agent_audit mode.
        // Kept as separate expressions so a reader can grep for the exact
        // operator phrase that triggered the routing.
        $isAudit = (bool) preg_match(
            '/\baudit\s+(how\s+)?(our\s+)?(agents?|moderators?|team|staff|humans)\b/iu',
            $lower
        ) || (bool) preg_match(
            '/\bhow\s+(did|does|is|have|has)\s+(our\s+)?(agents?|team|moderators?|staff)\s+(handle|handled|handling|respond|responded|responding|do)\b/iu',
            $lower
        ) || (bool) preg_match(
            '/\bevaluate\s+(our\s+)?(agents?|team|moderators?|staff)\b/iu',
            $lower
        );

        if (! $isDeep && ! $isAudit) {
            return null;
        }

        $cohortFilter = [];

        // Explicit N contacts: "analyze last 500 contacts" → limit=500.
        if (preg_match('/\b(?:last\s+)?(\d{2,5})\s*(?:contacts?|conversations?|chats?|customers?)\b/iu', $lower, $m)) {
            $cohortFilter['limit'] = (int) $m[1];
        }

        // Page name — reuse AdminChatContext's resolver so brand typos and
        // Arabic names work the same way as inline mentions.
        /** @var AdminChatContext $ctx */
        $ctx = app(AdminChatContext::class);
        $pageIds = $ctx->mentionedPageIds($team->id, $text, '');
        if (count($pageIds) === 1) {
            $cohortFilter['page_id'] = $pageIds[0];
        }

        return [
            'cohort_filter' => $cohortFilter,
            'mode'          => $isAudit ? DeepAnalysis::MODE_AGENT_AUDIT : DeepAnalysis::MODE_CUSTOMER_THEMES,
        ];
    }

    /**
     * Persist an AiCommand row for a short-circuit turn (where we don't
     * actually call NaraRouter). Keeps the chat history in sync with
     * AiCommand so a page reload shows the dispatched/queued state.
     */
    protected function persistTurn(Team $team, string $operatorText, string $responseText): void
    {
        AiCommand::create([
            'team_id'  => $team->id,
            'user_id'  => Auth::id(),
            'command'  => $operatorText,
            'response' => $responseText,
            'status'   => 'completed',
        ]);
    }

    /**
     * Parse AI response for action blocks and execute them.
     *
     * pending_action blocks: require user confirmation before executing.
     * action blocks: auto-execute immediately (save_memory only).
     */
    protected function executeActions(string &$response, int $teamId): ?string
    {
        $results = [];

        // Handle pending_action blocks — store for confirmation, do not execute yet
        if (preg_match('/```pending_action\s*(\{.+?\})\s*```/s', $response, $match)) {
            try {
                $action = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
                $this->pendingAction = $action;
                $this->pendingActionSummary = $this->describePendingAction($action, $teamId);
            } catch (\JsonException $e) {
                $results[] = 'Failed to parse pending action: invalid JSON.';
            }

            $response = trim(preg_replace('/```pending_action\s*\{.+?\}\s*```/s', '', $response));
        }

        // Handle action blocks — auto-execute (save_memory only)
        if (preg_match_all('/```action\s*(\{.+?\})\s*```/s', $response, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                try {
                    $action = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);

                    if (($action['action'] ?? null) === 'save_memory') {
                        $results[] = $this->runAction($action, $teamId);
                    }
                } catch (\JsonException $e) {
                    $results[] = 'Failed to parse action: invalid JSON.';
                } catch (\Throwable $e) {
                    Log::error('AI Chat action failed', ['error' => $e->getMessage(), 'action' => $jsonStr]);
                    $results[] = "Action failed: {$e->getMessage()}";
                }
            }

            $response = trim(preg_replace('/```action\s*\{.+?\}\s*```/s', '', $response));
        }

        return $results ? implode("\n", $results) : null;
    }

    protected function describePendingAction(array $action, int $teamId): string
    {
        return match ($action['action'] ?? '') {
            'send_message' => $this->describeSendMessage($action, $teamId),
            'send_bulk_message' => $this->describeBulkMessage($action, $teamId),
            'pause_ai' => $this->describeAiToggle($action, $teamId, 'pause'),
            'resume_ai' => $this->describeAiToggle($action, $teamId, 'resume'),
            'pause_campaign' => $this->describeCampaignToggle($action, $teamId, 'pause'),
            'resume_campaign' => $this->describeCampaignToggle($action, $teamId, 'resume'),
            default => 'Execute: ' . json_encode($action),
        };
    }

    /** Operators name people, not IDs: accept a unique contact_name too. */
    protected function contactIdFromName(array $action, int $teamId): ?int
    {
        $name = trim((string) ($action['contact_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $ids = Contact::where('team_id', $teamId)->where('name', 'like', '%' . addcslashes($name, '%_') . '%')->limit(2)->pluck('id');

        return $ids->count() === 1 ? $ids->first() : null;
    }

    protected function describeSendMessage(array $action, int $teamId): string
    {
        $contactId = $action['contact_id'] ?? $this->contactIdFromName($action, $teamId);
        $text = $action['message'] ?? '';
        $name = __('Unknown contact');

        if ($contactId) {
            $contact = Contact::where('team_id', $teamId)->find($contactId);
            $name = $contact?->name ?? __('Contact #:id', ['id' => $contactId]);
        }

        return __('Send message to :name: ":text"', ['name' => $name, 'text' => $text]);
    }

    protected function describeBulkMessage(array $action, int $teamId): string
    {
        $text = $action['message'] ?? '';
        $minScore = $action['min_score'] ?? null;
        $status = $action['status'] ?? null;
        $pageId = $action['page_id'] ?? null;
        $scheduledAtRaw = $action['scheduled_at'] ?? null;

        ['eligible' => $eligible, 'stale' => $stale] = $this->resolveBulkTargets($action, $teamId);

        $pageName = $pageId ? Page::where('team_id', $teamId)->find($pageId)?->name : null;

        $filter = $pageName ? __('page: :name', ['name' => $pageName]) : __('all pages');
        if ($minScore !== null) {
            $filter .= ', ' . __('score ≥ :score', ['score' => $minScore]);
        } elseif ($status) {
            $filter .= ', ' . __('status: :status', ['status' => $status]);
        }

        if ($scheduledAtRaw) {
            try {
                $when = \Carbon\Carbon::parse($scheduledAtRaw)->translatedFormat('M j, Y g:ia');
            } catch (\Throwable $e) {
                $when = $scheduledAtRaw; // the executor surfaces the parse error on confirm
            }

            // The Meta window is re-checked at the scheduled time, so quote the
            // full audience and say that the filter happens then.
            $total = $eligible->count() + $stale;
            $sentence = __('Schedule bulk message to up to :count contacts (:filter) [:when]: ":text"', ['count' => $total, 'filter' => $filter, 'when' => $when, 'text' => $text]);
            if ($stale > 0 || $eligible->contains(fn ($c) => in_array($c->platform, self::META_WINDOW_PLATFORMS, true))) {
                $sentence .= '  ' . __('⚠ Messenger/Instagram contacts who have not messaged within 24h of the send time will be skipped (Meta rule).');
            }

            return $sentence;
        }

        $sentence = __('Send bulk message to :count contacts (:filter) [now]: ":text"', ['count' => $eligible->count(), 'filter' => $filter, 'text' => $text]);
        if ($stale > 0) {
            $sentence .= '  ' . __('⚠ :count more on Messenger/Instagram will NOT receive it — they have not messaged within the last 24 hours and Meta blocks sends outside that window.', ['count' => $stale]);
        }

        return $sentence;
    }

    /**
     * Who a send_bulk_message would actually reach. Shared by the confirmation
     * summary and the executor so the number the operator approves is the
     * number that gets sent. Messenger/Instagram conversations whose last
     * inbound is older than 24h are split out: Meta rejects those sends with
     * error 2018278 ("outside the allowed time frame"), and counting them as
     * recipients is how "sent to 98" turned into 2 delivered.
     *
     * @return array{eligible: \Illuminate\Support\Collection<int, Conversation>, stale: int}
     */
    protected function resolveBulkTargets(array $action, int $teamId): array
    {
        $query = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereHas('contact');

        if (($action['page_id'] ?? null) !== null) {
            $query->where('page_id', $action['page_id']);
        }

        if (($action['min_score'] ?? null) !== null) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_score', '>=', $action['min_score']));
        }

        if (! empty($action['status'])) {
            $query->whereHas('contact', fn ($q) => $q->where('lead_status', $action['status']));
        }

        // Most recent conversation per contact.
        $conversations = $query->orderByDesc('last_message_at')->get()->unique('contact_id')->values();

        $metaIds = $conversations->whereIn('platform', self::META_WINDOW_PLATFORMS)->pluck('id');
        $lastInbound = $metaIds->isEmpty() ? collect() : Message::whereIn('conversation_id', $metaIds)
            ->where('direction', 'inbound')
            ->selectRaw('conversation_id, MAX(COALESCE(platform_sent_at, created_at)) as last_at')
            ->groupBy('conversation_id')
            ->pluck('last_at', 'conversation_id');

        // WhatsApp (Wuzapi session), Telegram and email have no server-side window.
        $cutoff = now()->subHours(24);
        [$eligible, $stale] = $conversations->partition(function (Conversation $c) use ($lastInbound, $cutoff) {
            if (! in_array($c->platform, self::META_WINDOW_PLATFORMS, true)) {
                return true;
            }
            $at = $lastInbound[$c->id] ?? null;

            return $at && \Carbon\Carbon::parse($at)->gt($cutoff);
        });

        return ['eligible' => $eligible->values(), 'stale' => $stale->count()];
    }

    protected function describeAiToggle(array $action, int $teamId, string $mode): string
    {
        $contactId = $action['contact_id'] ?? null;

        if ($contactId) {
            $contact = Contact::where('team_id', $teamId)->find($contactId);
            $name = $contact?->name ?? __('Contact #:id', ['id' => $contactId]);

            return $mode === 'pause'
                ? __('Pause AI responses for :name', ['name' => $name])
                : __('Resume AI responses for :name', ['name' => $name]);
        }

        return $mode === 'pause'
            ? __('Pause AI responses for all conversations')
            : __('Resume AI responses for all conversations');
    }

    protected function describeCampaignToggle(array $action, int $teamId, string $mode): string
    {
        $campaignId = $action['campaign_id'] ?? null;

        if ($campaignId) {
            $campaign = Campaign::where('team_id', $teamId)->find($campaignId);
            $name = $campaign?->name ?? __('Campaign #:id', ['id' => $campaignId]);

            return $mode === 'pause'
                ? __('Pause campaign: :name', ['name' => $name])
                : __('Resume campaign: :name', ['name' => $name]);
        }

        return $mode === 'pause' ? __('Pause campaign (unknown ID)') : __('Resume campaign (unknown ID)');
    }

    protected function runAction(array $action, int $teamId): string
    {
        $type = $action['action'] ?? null;

        return match ($type) {
            'send_message' => $this->actionSendMessage($action, $teamId),
            'send_bulk_message' => $this->actionSendBulkMessage($action, $teamId),
            'pause_ai' => $this->actionToggleAi($action, $teamId, true),
            'resume_ai' => $this->actionToggleAi($action, $teamId, false),
            'pause_campaign' => $this->actionToggleCampaign($action, $teamId, 'paused'),
            'resume_campaign' => $this->actionToggleCampaign($action, $teamId, 'active'),
            'save_memory' => $this->actionSaveMemory($action, $teamId),
            default => "Unknown action: {$type}",
        };
    }

    /**
     * Send a message to a specific contact's most recent conversation.
     */
    protected function actionSendMessage(array $action, int $teamId): string
    {
        $contactId = $action['contact_id'] ?? $this->contactIdFromName($action, $teamId);
        $text = $action['message'] ?? null;

        if (! $contactId || ! $text) {
            return 'Send message failed: could not tell which contact to message — mention them by their full name and try again.';
        }

        $conversation = Conversation::where('team_id', $teamId)
            ->where('contact_id', $contactId)
            ->orderByDesc('last_message_at')
            ->first();

        if (! $conversation) {
            return "No conversation found for contact #{$contactId}.";
        }

        return $this->sendMessageToConversation($conversation, $text);
    }

    /**
     * Send a message to multiple contacts matching criteria.
     */
    protected function actionSendBulkMessage(array $action, int $teamId): string
    {
        $text = $action['message'] ?? null;
        $status = $action['status'] ?? null;
        $scheduledAtRaw = $action['scheduled_at'] ?? null;

        if (! $text) {
            return "Bulk message failed: missing message text.";
        }

        $pageId = $action['page_id'] ?? null;

        // Scheduling path: if the AI provided a scheduled_at ISO datetime, create
        // a Campaign row in status='scheduled' rather than dispatching now. The
        // scheduler command (campaigns:dispatch-scheduled) flips it to active at
        // the scheduled time and ProcessCampaign handles the send loop with the
        // same Meta 24h filter applied at dispatch time.
        if ($scheduledAtRaw) {
            try {
                $scheduledAt = \Carbon\Carbon::parse($scheduledAtRaw);
            } catch (\Throwable $e) {
                return "Bulk message failed: could not parse scheduled_at (expected ISO datetime like 2026-09-01T14:30:00Z). Got: {$scheduledAtRaw}";
            }

            if ($scheduledAt->lt(now()->addMinute())) {
                return "Bulk message failed: scheduled_at must be at least 1 minute in the future.";
            }
            if ($scheduledAt->gt(now()->addDays(30))) {
                return "Bulk message failed: scheduled_at must be within the next 30 days.";
            }
            if (! $pageId) {
                return "Bulk message failed: scheduled bulk sends require a page_id (which page to send from).";
            }

            $page = Page::where('team_id', $teamId)->where('is_active', true)->find($pageId);
            if (! $page) {
                return "Bulk message failed: page_id {$pageId} not found or inactive on this team.";
            }

            $criteria = [
                'page_id'       => $pageId,
                'delay_seconds' => 5,
            ];
            if ($status) {
                $criteria['lead_status'] = $status;
            }
            if (in_array($page->platform, ['facebook', 'instagram'], true)) {
                $criteria['meta_24h_filter'] = true;
            }

            $campaign = \App\Models\Campaign::create([
                'team_id'          => $teamId,
                'created_by'       => \Illuminate\Support\Facades\Auth::id(),
                'name'             => 'AI Chat scheduled — ' . now()->format('M j, Y g:ia'),
                'type'             => 'promotion',
                'platform'         => $page->platform,
                'message_template' => $text,
                'target_criteria'  => $criteria,
                'status'           => 'scheduled',
                'scheduled_at'     => $scheduledAt,
            ]);

            $when = $scheduledAt->format('M j, Y g:ia');
            $windowNote = in_array($page->platform, ['facebook', 'instagram'], true)
                ? " On Messenger/Instagram, Meta will only accept sends to contacts who have replied to this Page within 24 hours OF THE SCHEDULED TIME — stale contacts are filtered out then, not now."
                : '';
            return "Campaign scheduled: '{$campaign->name}' will send at {$when} on {$page->name} ({$page->platform}).{$windowNote}";
        }

        ['eligible' => $eligible, 'stale' => $skippedStale] = $this->resolveBulkTargets($action, $teamId);

        if ($eligible->isEmpty() && $skippedStale > 0) {
            return __('Nothing sent: all :count matching contacts are on Messenger/Instagram and none has messaged within the last 24 hours. Meta blocks sends outside that window (error 2018278) — reach them on WhatsApp / Telegram / email instead, or wait until they message the Page.', ['count' => $skippedStale]);
        }

        $sent = 0;
        $failed = 0;

        foreach ($eligible as $conversation) {
            try {
                $this->sendMessageToConversation($conversation, $text);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $parts = [__('Queued message to :count contacts.', ['count' => $sent])];
        if ($skippedStale > 0) {
            $parts[] = __('Skipped :count on Messenger/Instagram because Meta will not accept messages to contacts who have not replied within the last 24 hours — this is Meta\'s rule, not ours, and sends outside it come back with error 2018278 (\'outside the allowed time frame\'). WhatsApp / Telegram / email do not have this limit; broadcasting to those platforms reaches everyone.', ['count' => $skippedStale]);
        }
        if ($failed > 0) {
            $parts[] = __(':count failed to queue.', ['count' => $failed]);
        }
        $parts[] = __('Note: \'queued\' means the send job was dispatched to our queue. Actual delivery is confirmed on the message row\'s platform_message_id — check the inbox for green checkmarks.');

        return implode(' ', $parts);
    }

    protected function sendMessageToConversation(Conversation $conversation, string $text): string
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'sender_type' => 'ai',
            'content_type' => 'text',
            'content' => $text,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($text, 100),
        ]);

        SendPlatformMessage::dispatch($message->id);

        $contactName = $conversation->contact?->name ?? 'Unknown';

        return "Sent to {$contactName}.";
    }

    protected function actionToggleAi(array $action, int $teamId, bool $pause): string
    {
        $contactId = $action['contact_id'] ?? null;

        $query = Conversation::where('team_id', $teamId);

        if ($contactId) {
            $query->where('contact_id', $contactId);
        }

        $updated = $query->update(['ai_paused' => $pause]);

        $state = $pause ? 'paused' : 'resumed';

        return "AI {$state} for {$updated} conversation(s).";
    }

    protected function actionToggleCampaign(array $action, int $teamId, string $status): string
    {
        $campaignId = $action['campaign_id'] ?? null;

        if (! $campaignId) {
            return 'Campaign action failed: missing campaign_id.';
        }

        $campaign = Campaign::where('team_id', $teamId)->find($campaignId);

        if (! $campaign) {
            return "Campaign #{$campaignId} not found.";
        }

        $campaign->update(['status' => $status]);

        $label = $status === 'paused' ? 'paused' : 'resumed';

        return "Campaign '{$campaign->name}' {$label}.";
    }

    protected function actionSaveMemory(array $action, int $teamId): string
    {
        $content = trim($action['content'] ?? '');

        if (! $content) {
            return 'Save memory failed: no content provided.';
        }

        $team = Team::find($teamId);

        if (! $team) {
            return 'Save memory failed: team not found.';
        }

        $existing = $team->ai_memory ?? '';
        $separator = $existing ? "\n" : '';
        $team->update(['ai_memory' => $existing . $separator . $content]);

        return "Saved to memory.";
    }

    protected function buildAnalyticsContext(int $teamId): string
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();

        $conversationsQuery = Conversation::where('team_id', $teamId);
        $messagesQuery = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId));
        $contactsQuery = Contact::where('team_id', $teamId);

        $lines = [];
        $lines[] = '=== BUSINESS ANALYTICS DATA ===';
        $lines[] = 'Current date/time: ' . now()->format('Y-m-d H:i');

        // Conversations
        $lines[] = "\n--- Conversations ---";
        $lines[] = 'Total conversations: ' . (clone $conversationsQuery)->count();
        $lines[] = 'Today: ' . (clone $conversationsQuery)->where('created_at', '>=', $today)->count();
        $lines[] = 'This week: ' . (clone $conversationsQuery)->where('created_at', '>=', $weekStart)->count();

        // By status
        $statuses = (clone $conversationsQuery)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        foreach ($statuses as $status => $count) {
            $lines[] = ucfirst($status) . ': ' . $count;
        }

        // Messages
        $lines[] = "\n--- Messages ---";
        $lines[] = 'Total messages: ' . (clone $messagesQuery)->count();
        $lines[] = 'Today: ' . (clone $messagesQuery)->where('messages.created_at', '>=', $today)->count();
        $lines[] = 'This week: ' . (clone $messagesQuery)->where('messages.created_at', '>=', $weekStart)->count();

        // By platform
        $lines[] = "\n--- Messages by Platform ---";
        $platformCounts = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.team_id', $teamId)
            ->selectRaw('conversations.platform, count(*) as total')
            ->groupBy('conversations.platform')
            ->pluck('total', 'platform');
        foreach ($platformCounts as $platform => $count) {
            $lines[] = ucfirst($platform) . ': ' . $count;
        }

        // AI vs human responses
        $lines[] = "\n--- Response Types ---";
        $aiCount = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'ai')->count();
        $humanCount = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'user')->count();
        $lines[] = "AI responses: {$aiCount}";
        $lines[] = "Human responses: {$humanCount}";

        // Contacts — include IDs so the AI can reference them in actions
        $lines[] = "\n--- Contacts ---";
        $lines[] = 'Total contacts: ' . (clone $contactsQuery)->count();
        $lines[] = 'New this week: ' . (clone $contactsQuery)->where('created_at', '>=', $weekStart)->count();

        // All contacts with scores (for action targeting)
        $lines[] = "\n--- All Contacts (ID, Name, Score, Status) ---";
        $allContacts = Contact::where('team_id', $teamId)
            ->orderByDesc('lead_score')
            ->limit(20)
            ->get(['id', 'name', 'lead_score', 'lead_status']);
        foreach ($allContacts as $c) {
            $lines[] = "ID:{$c->id} | {$c->name} | score {$c->lead_score} ({$c->lead_status})";
        }

        // Recent escalated conversations
        $lines[] = "\n--- Recent Escalated/Open Conversations ---";
        $escalated = Conversation::where('team_id', $teamId)
            ->where('status', 'open')
            ->with('contact:id,name')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();
        foreach ($escalated as $conv) {
            $contactName = $conv->contact?->name ?? 'Unknown';
            $lines[] = "{$contactName} ({$conv->platform}) - last message: " . ($conv->last_message_at?->diffForHumans() ?? 'N/A');
        }

        // Pages (for page_id targeting in bulk messages). Reach is spelled out
        // per page so the AI quotes what a broadcast will really hit: on
        // Messenger/Instagram only contacts who messaged within 24h can be
        // reached, and quoting the full audience is how "sent to 98" happened.
        $lines[] = "\n--- Connected Pages (ID, Name, Platform, Audience) ---";
        $pages = Page::where('team_id', $teamId)->get(['id', 'name', 'platform']);
        $audience = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->selectRaw('page_id, count(distinct contact_id) as total')
            ->groupBy('page_id')
            ->pluck('total', 'page_id');
        $windowStart = now()->subHours(24);
        $reachable = Conversation::where('team_id', $teamId)
            ->where('status', '!=', 'archived')
            ->whereIn('platform', self::META_WINDOW_PLATFORMS)
            ->whereHas('messages', fn ($q) => $q->where('direction', 'inbound')
                ->whereRaw('COALESCE(platform_sent_at, created_at) >= ?', [$windowStart]))
            ->selectRaw('page_id, count(distinct contact_id) as total')
            ->groupBy('page_id')
            ->pluck('total', 'page_id');
        foreach ($pages as $page) {
            $total = (int) ($audience[$page->id] ?? 0);
            if (in_array($page->platform, self::META_WINDOW_PLATFORMS, true)) {
                $now = (int) ($reachable[$page->id] ?? 0);
                $reach = "{$total} contacts, only {$now} reachable now (messaged within 24h); "
                    . ($total - $now) . ' outside Meta\'s 24h window and CANNOT be messaged';
            } else {
                $reach = "{$total} contacts, all reachable (no messaging window)";
            }
            $lines[] = "ID:{$page->id} | {$page->name} | {$page->platform} | {$reach}";
        }

        // Campaigns
        $lines[] = "\n--- Campaigns (ID, Name, Type, Status, Sent/Total, Replies) ---";
        $campaigns = Campaign::where('team_id', $teamId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $lines[] = 'Total campaigns: ' . $campaigns->count();
        foreach ($campaigns as $campaign) {
            $replyRate = $campaign->sent_count > 0
                ? round(($campaign->reply_count / $campaign->sent_count) * 100) . '%'
                : '0%';
            $lines[] = "ID:{$campaign->id} | {$campaign->name} | {$campaign->type} | status:{$campaign->status}"
                . " | sent:{$campaign->sent_count}/{$campaign->total_contacts} | replies:{$campaign->reply_count} ({$replyRate})"
                . ($campaign->scheduled_at ? " | scheduled:{$campaign->scheduled_at->format('Y-m-d H:i')}" : '');
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        return view('livewire.ai-chat')
            ->layout('layouts.app', ['title' => 'AI Chat', 'fullWidth' => true]);
    }
}
