<?php

declare(strict_types=1);

namespace App\Livewire\Onboarding;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiAllProvidersUnavailable;
use App\Exceptions\AiQuotaExhausted;
use App\Models\AiConfig;
use App\Models\Team;
use App\Services\Ai\NaraRouterProvider;
use App\Services\Onboarding\AiSeedComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Phase A — "Meet Your AI" playground.
 *
 * Single-page 4-step wizard shown to new signups before they see /inbox.
 * Goal: user chats with THEIR AI (in their business voice) as a fake customer
 * within 90 seconds — no page connection required. See
 * tasks/onboarding-activation-plan.md → Phase A for the full spec.
 *
 * Load-bearing:
 *   - Step 3 saves the seed to $team->settings['onboarding_ai_seed'] via
 *     AiSeedComposer, which is our fallback-safe path (raw template on any
 *     router error — CLAUDE.md pin #5).
 *   - Step 4 chat uses generateText(), which routes through NaraRouter's
 *     coalesceRoles() automatically (CLAUDE.md pin #9).
 *   - We do NOT gate on canDispatchAi() here (pin #4 is for message dispatch;
 *     this is a synchronous chat, not a queued auto-reply).
 */
#[Layout('layouts.app')]
#[Title('Meet your AI')]
class MeetYourAi extends Component
{
    // Max steps — hard-coded because Blade renders a step counter.
    public const TOTAL_STEPS = 4;

    // Chat turn cap in step 4 — after this the CTAs take over the composer area.
    public const MAX_CHAT_TURNS = 5;

    // Step state (1..4). Locked because navigation is via nextStep/goToStep only.
    #[Locked]
    public int $step = 1;

    // Step 1 — business type card.
    public string $businessType = '';

    // Step 2 — three conversational questions.
    public string $q1Offer       = '';
    public string $q2TopQuestion = '';
    public string $q3Tone        = 'friendly';
    public string $q3CustomTone  = ''; // filled only when q3Tone === 'my_own'

    // Step 2 progress inside the chat-style question stack. 1..3 → shown; 4 → all done.
    #[Locked]
    public int $questionIndex = 1;

    // Step 3 — polish state + resulting prompt.
    #[Locked]
    public bool $isGenerating = false;
    #[Locked]
    public string $generatedPrompt = '';
    #[Locked]
    public bool $polished = false;

    // Step 4 — chat state. Each turn is ['role' => 'user'|'assistant', 'content' => string].
    /** @var array<int, array{role: string, content: string}> */
    #[Locked]
    public array $chatMessages = [];

    // User's next customer message in step 4.
    public string $customerInput = '';

    // Whether the AI is currently generating a step-4 reply (drives the typing UI).
    #[Locked]
    public bool $isAiTyping = false;

    // Non-blocking soft-error banner for the chat (e.g. quota exhausted mid-demo).
    // We NEVER put an "I apologize" string into $chatMessages — this is a UI-
    // level notice, not a fake AI reply. See CLAUDE.md pin #5.
    #[Locked]
    public string $chatSoftError = '';

    public function mount(): void
    {
        if (! Auth::check() || ! Auth::user()->currentTeam) {
            $this->redirectRoute('dashboard', navigate: false);
            return;
        }

        // If already completed, skip straight to the inbox — this route is
        // one-shot for new signups. Users who want to revisit go via /settings.
        $team = Auth::user()->currentTeam;
        if ($team->onboarding_completed_at !== null) {
            $this->redirect(route('dashboard', absolute: false), navigate: false);
        }
    }

    // ── Step navigation ────────────────────────────────────────────────────

    public function pickBusinessType(string $type): void
    {
        if (! in_array($type, AiSeedComposer::BUSINESS_TYPES, true)) {
            return;
        }
        $this->businessType = $type;
        $this->step = 2;
        $this->questionIndex = 1;

        $this->dispatch('heron-event', name: 'onboarding_step_1_business_type_picked', payload: [
            'business_type' => $type,
        ]);
    }

    public function submitQuestion(): void
    {
        if ($this->questionIndex === 1) {
            $this->validate([
                'q1Offer' => 'required|string|min:5|max:500',
            ]);
            $this->questionIndex = 2;
            return;
        }

        if ($this->questionIndex === 2) {
            $this->validate([
                'q2TopQuestion' => 'required|string|min:3|max:500',
            ]);
            $this->questionIndex = 3;
            return;
        }

        if ($this->questionIndex === 3) {
            $this->validate([
                'q3Tone' => 'required|in:formal,friendly,playful,my_own',
                'q3CustomTone' => $this->q3Tone === 'my_own' ? 'required|string|min:5|max:250' : 'nullable|string|max:250',
            ]);
            // Questions done — advance to generation step.
            $this->step = 3;
            $this->dispatch('heron-event', name: 'onboarding_step_2_questions_answered');
            // Trigger the actual generation from the frontend after paint so
            // the typing animation shows during the network call.
            $this->dispatch('start-generation');
        }
    }

    /**
     * Fill Q2 with one of the preset chips seeded from businessType. Chip
     * click still lets the user edit the value before submitting.
     */
    public function useSuggestedQuestion(string $question): void
    {
        $this->q2TopQuestion = mb_substr($question, 0, 500);
    }

    // ── Step 3 — generate ──────────────────────────────────────────────────

    public function generate(AiSeedComposer $composer): void
    {
        if ($this->step !== 3 || $this->isGenerating) {
            return;
        }
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }

        $this->isGenerating = true;

        $answers = [
            'business_name'   => $team->name,
            'business_type'   => $this->businessType,
            'q1_offer'        => $this->q1Offer,
            'q2_top_question' => $this->q2TopQuestion,
            'q3_tone'         => $this->q3Tone === 'my_own' && $this->q3CustomTone !== ''
                ? $this->q3CustomTone
                : $this->q3Tone,
        ];

        // composeAndSave never throws — it either polishes or falls back to raw.
        $this->generatedPrompt = $composer->composeAndSave($team, $answers);

        // Also record the business_type on the team for Phase C/D + funnel.
        $team->business_type = $this->businessType;
        $team->save();

        // Detect whether we polished by re-reading the persisted flag.
        $this->polished = (bool) data_get($team->fresh()->settings, 'onboarding_ai_seed.polished', false);

        // Mirror the seed onto any existing AiConfig row(s) for this team so
        // the moment they connect a page (Phase B) the AI already speaks in
        // their voice. New-signup flow won't have any pages, so this is a
        // no-op for the primary path.
        $this->mirrorSeedToExistingConfigs($team, $this->generatedPrompt);

        $this->isGenerating = false;
        $this->step = 4;
        $this->seedFirstCustomerMessage();

        $this->dispatch('heron-event', name: 'onboarding_step_3_ai_generated', payload: [
            'polished' => $this->polished,
        ]);
        $this->dispatch('heron-event', name: 'onboarding_step_4_fake_chat_started');

        // Kick off the AI's first reply from the front-end after paint so the
        // "typing" indicator shows during the network call.
        $this->dispatch('ai-turn');
    }

    /**
     * Seed one inbound "customer" message auto-generated from Q2.
     */
    protected function seedFirstCustomerMessage(): void
    {
        $q = trim($this->q2TopQuestion);
        $prefix = "Hi — ";
        $message = $q !== '' ? $prefix . rtrim($q, '?!.') . '?' : "Hi — I'm curious about what you offer.";
        $this->chatMessages = [
            ['role' => 'user', 'content' => $message],
        ];
    }

    protected function mirrorSeedToExistingConfigs(Team $team, string $systemPrompt): void
    {
        // Best-effort mirror. AiConfig has `page_id` unique + (post-Phase-A
        // migration) nullable. If any existing config rows for this team are
        // still using empty system_prompt, backfill them with the seed so the
        // whole team benefits from the onboarding effort.
        try {
            AiConfig::query()
                ->where('team_id', $team->id)
                ->whereNull('system_prompt')
                ->update([
                    'system_prompt' => $systemPrompt,
                    'updated_at'    => now(),
                ]);
        } catch (Throwable $e) {
            Log::info('MeetYourAi mirror skipped', ['error' => $e->getMessage()]);
        }
    }

    // ── Step 4 — fake customer chat ───────────────────────────────────────

    public function sendCustomerMessage(AiProviderInterface $ai): void
    {
        if ($this->step !== 4) {
            return;
        }
        $input = trim($this->customerInput);
        if ($input === '') {
            return;
        }
        if ($this->userTurnCount() >= self::MAX_CHAT_TURNS) {
            return;
        }
        $this->customerInput = '';
        $this->chatMessages[] = ['role' => 'user', 'content' => mb_substr($input, 0, 1000)];
        $this->dispatch('ai-turn');
    }

    /**
     * Public so the front-end can invoke it from the ai-turn browser event
     * (letting Livewire render the "typing" state first, then round-trip for
     * the actual generation).
     */
    public function generateAiTurn(AiProviderInterface $ai): void
    {
        if ($this->step !== 4) {
            return;
        }
        // Only run when the last message is from the user — otherwise this
        // was a duplicate event and we'd generate two replies.
        $last = end($this->chatMessages) ?: null;
        if (! $last || $last['role'] !== 'user') {
            return;
        }
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }

        $this->isAiTyping = true;
        $this->chatSoftError = '';

        $systemPrompt = (string) data_get($team->settings, 'onboarding_ai_seed.system_prompt', $this->generatedPrompt);
        if ($systemPrompt === '') {
            $systemPrompt = 'You are a helpful sales assistant. Keep replies short.';
        }

        // Build a compact one-shot user turn: concatenate the last few turns
        // into a labeled transcript. NaraRouter's coalesceRoles() runs inside
        // callChat() so we don't have to worry about role alternation here.
        $transcript = collect(array_slice($this->chatMessages, -8))
            ->map(fn ($m) => ($m['role'] === 'user' ? 'Customer' : 'You') . ': ' . $m['content'])
            ->implode("\n");

        $userMessage = "Continue this chat as the sales assistant. Reply ONLY with your next message, "
            . "no labels, no preamble.\n\n" . $transcript;

        try {
            $reply = $ai->generateText($systemPrompt, $userMessage);
        } catch (AiQuotaExhausted) {
            $this->chatSoftError = 'AI is temporarily unavailable (daily quota reached). Try again shortly.';
            $this->isAiTyping = false;
            return;
        } catch (AiAllProvidersUnavailable) {
            $this->chatSoftError = 'AI is temporarily unavailable. Try again in a few minutes.';
            $this->isAiTyping = false;
            return;
        } catch (Throwable $e) {
            Log::warning('MeetYourAi step-4 chat threw', ['error' => $e->getMessage()]);
            $this->chatSoftError = 'Could not reach the AI. Try again.';
            $this->isAiTyping = false;
            return;
        }

        $reply = trim((string) $reply);

        // CLAUDE.md pin #5: provider returns '' on non-quota failures. Do NOT
        // fabricate an apology reply — surface a soft-error banner instead.
        if ($reply === '') {
            $this->chatSoftError = 'AI didn\'t reply this time. Give it another try.';
            $this->isAiTyping = false;
            return;
        }

        $this->chatMessages[] = ['role' => 'assistant', 'content' => $reply];
        $this->isAiTyping = false;

        // Fire the "first reply" telemetry once — subsequent replies don't need it.
        if ($this->assistantTurnCount() === 1) {
            $this->dispatch('heron-event', name: 'onboarding_step_4_fake_chat_first_reply');
        }
    }

    protected function userTurnCount(): int
    {
        return count(array_filter($this->chatMessages, fn ($m) => $m['role'] === 'user'));
    }

    protected function assistantTurnCount(): int
    {
        return count(array_filter($this->chatMessages, fn ($m) => $m['role'] === 'assistant'));
    }

    // ── Completion ─────────────────────────────────────────────────────────

    /**
     * Mark onboarding done and send the user to /connections (real page hookup).
     */
    public function completeAndConnect(): void
    {
        $this->markComplete();
        $this->dispatch('heron-event', name: 'onboarding_completed', payload: ['next' => 'connections']);
        $this->redirect(route('connections.index', absolute: false), navigate: true);
    }

    /**
     * Mark onboarding done and send the user to the AI config to tweak.
     */
    public function completeAndTweak(): void
    {
        $this->markComplete();
        $this->dispatch('heron-event', name: 'onboarding_completed', payload: ['next' => 'ai-config']);
        $this->redirect(route('settings.ai.config', absolute: false), navigate: true);
    }

    /**
     * "Skip for now" — record the skip but let them into /inbox. Per plan
     * acceptance criteria: onboarding_completed_at stays NULL, but skip is
     * still tracked so nudge emails don't re-trigger step 1.
     */
    public function skip(): void
    {
        $team = Auth::user()?->currentTeam;
        if ($team) {
            $settings = is_array($team->settings) ? $team->settings : [];
            $settings['onboarding_skipped_at'] = now()->toIso8601String();
            $team->settings = $settings;
            $team->save();
        }
        $this->dispatch('heron-event', name: 'onboarding_skipped', payload: ['at_step' => $this->step]);
        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    protected function markComplete(): void
    {
        $team = Auth::user()?->currentTeam;
        if (! $team) {
            return;
        }
        if ($team->onboarding_completed_at === null) {
            $team->onboarding_completed_at = now();
            $team->save();
        }
    }

    /**
     * Exposed for tests / Blade — returns the preset chip suggestions for Q2.
     *
     * @return array<int, string>
     */
    public function getSuggestedQuestionsProperty(): array
    {
        return AiSeedComposer::presetQuestionsFor($this->businessType ?: 'other');
    }

    public function render()
    {
        return view('livewire.onboarding.meet-your-ai', [
            'businessTypeOptions' => $this->businessTypeOptions(),
            'suggestedQuestions'  => $this->suggestedQuestions,
            'totalSteps'          => self::TOTAL_STEPS,
            'canSendMoreTurns'    => $this->userTurnCount() < self::MAX_CHAT_TURNS,
        ]);
    }

    /**
     * @return array<int, array{id: string, label: string, hint: string, icon: string}>
     */
    protected function businessTypeOptions(): array
    {
        return [
            ['id' => 'ecommerce',   'label' => 'E-commerce',        'hint' => 'Shopify, Instagram shop, DTC brand', 'icon' => 'shopping-bag'],
            ['id' => 'services',    'label' => 'Services / Agency', 'hint' => 'Consultancy, freelance, done-for-you', 'icon' => 'briefcase'],
            ['id' => 'restaurant',  'label' => 'Restaurant / F&B',  'hint' => 'Delivery, dine-in, bakery',           'icon' => 'cake'],
            ['id' => 'clinic',      'label' => 'Clinic / Health',   'hint' => 'Dental, aesthetic, wellness',         'icon' => 'heart'],
            ['id' => 'real_estate', 'label' => 'Real Estate',       'hint' => 'Brokers, developers, PM',             'icon' => 'home'],
            ['id' => 'other',       'label' => 'Something else',    'hint' => 'We\'ll adapt to your needs',          'icon' => 'sparkles'],
        ];
    }
}
