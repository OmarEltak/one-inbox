<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Contracts\AiProviderInterface;
use App\Exceptions\AiAllProvidersUnavailable;
use App\Exceptions\AiQuotaExhausted;
use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase A — "Meet Your AI" playground.
 *
 * Assembles a system prompt from the 3 onboarding answers using a fixed
 * template, then asks NaraRouter to polish it into a natural first-person
 * sales-assistant voice. On ANY router error / timeout / empty reply we save
 * the raw template unchanged and continue silently — per CLAUDE.md pin #5 we
 * never emit fallback strings like "I apologize, having a moment".
 *
 * The polished (or raw) prompt is written to $team->settings['onboarding_ai_seed']
 * so the playground chat and Phase C/D can read it back without needing an
 * AiConfig row (which is per-page and doesn't exist yet at this point in the
 * funnel).
 */
class AiSeedComposer
{
    /**
     * Business type card IDs from step 1. Kept in sync with the Livewire
     * component so the composer can look up type-appropriate hints without a
     * cross-dependency on the UI layer.
     */
    public const BUSINESS_TYPES = [
        'ecommerce',
        'services',
        'restaurant',
        'clinic',
        'real_estate',
        'other',
    ];

    /**
     * Tone chips from step 2 Q3.
     */
    public const TONES = [
        'formal',
        'friendly',
        'playful',
        'my_own',
    ];

    /**
     * Hard cap on how long we'll wait for the polish call. Longer than this
     * and the user is staring at a spinner — save the raw template and move on.
     */
    public const POLISH_TIMEOUT_SECONDS = 8;

    public function __construct(protected AiProviderInterface $ai)
    {
    }

    /**
     * Build the raw system prompt from user answers using the fixed template
     * defined in tasks/onboarding-activation-plan.md §Phase A → Prompt template.
     *
     * @param array{
     *     business_name?: string,
     *     business_type?: string,
     *     q1_offer?: string,
     *     q2_top_question?: string,
     *     q3_tone?: string,
     * } $answers
     */
    public function buildTemplate(array $answers): string
    {
        $businessName   = trim((string) ($answers['business_name']   ?? 'this business'));
        $businessType   = trim((string) ($answers['business_type']   ?? 'other'));
        $offer          = trim((string) ($answers['q1_offer']        ?? ''));
        $topQuestion    = trim((string) ($answers['q2_top_question'] ?? ''));
        $tone           = trim((string) ($answers['q3_tone']         ?? 'friendly'));

        $businessTypeLabel = $this->labelForBusinessType($businessType);
        $toneLabel         = $this->labelForTone($tone);

        return <<<PROMPT
You are the AI sales assistant for {$businessName}.
Business type: {$businessTypeLabel}.
What we sell: {$offer}.
Common customer questions: {$topQuestion}.
Voice/tone: {$toneLabel} — keep replies short (1-3 sentences), never use emoji unless the customer does first, always end with a soft next-step question when appropriate.
PROMPT;
    }

    /**
     * Compose + polish + persist the seed for the given team. Returns the
     * final prompt actually saved (polished on success, raw on any failure).
     *
     * Never throws — every branch either returns a real prompt or the raw
     * template. Callers use the return value directly.
     *
     * @param array<string, mixed> $answers See buildTemplate() for the shape.
     */
    public function composeAndSave(Team $team, array $answers): string
    {
        $template = $this->buildTemplate($answers);
        $polished = $this->polish($template, $answers);
        $finalPrompt = $polished !== '' ? $polished : $template;

        // Persist on the team (page-less at this point in the funnel). The
        // playground chat + Phase D read this back via $team->settings.
        $settings = is_array($team->settings) ? $team->settings : [];
        $settings['onboarding_ai_seed'] = [
            'system_prompt'  => $finalPrompt,
            'polished'       => $polished !== '',
            'business_type'  => (string) ($answers['business_type'] ?? 'other'),
            'answers'        => [
                'q1_offer'        => (string) ($answers['q1_offer']        ?? ''),
                'q2_top_question' => (string) ($answers['q2_top_question'] ?? ''),
                'q3_tone'         => (string) ($answers['q3_tone']         ?? ''),
            ],
            'created_at'     => now()->toIso8601String(),
        ];

        $team->settings = $settings;
        $team->save();

        // Sample log for weekly review per Phase A risk mitigation table.
        Log::info('onboarding-ai-seed', [
            'team_id'      => $team->id,
            'polished'     => $polished !== '',
            'system_prompt' => $finalPrompt,
        ]);

        return $finalPrompt;
    }

    /**
     * Ask NaraRouter to rewrite the template into a natural first-person
     * sales-assistant voice. Returns '' on any error — caller then falls back
     * to the raw template. NEVER returns a "having a moment" apology string.
     *
     * @param array<string, mixed> $answers
     */
    public function polish(string $template, array $answers): string
    {
        $businessName = trim((string) ($answers['business_name'] ?? 'this business'));

        $instruction = "You are helping a small-business owner set up an AI sales assistant.\n\n"
            . "Rewrite the system prompt below so it sounds like a natural first-person description "
            . "written by {$businessName} themselves — not like a template. Keep every fact intact "
            . "(what they sell, what customers ask, tone). Return ONLY the rewritten prompt as plain "
            . "text — no preamble, no quotes, no markdown fences.\n\n"
            . "---\n{$template}\n---";

        try {
            // Enforce the 8-second cap using PHP's own alarm-like construct:
            // NaraRouter's internal HTTP timeout is 25s which is too long for
            // the "I'm looking at a spinner" UX. We wrap the call in a hard
            // stopwatch — if we're already past budget by the time it returns,
            // discard the reply.
            $start   = microtime(true);
            $reply   = $this->ai->generateText(
                'You are a copy editor for small-business owners. Return only the rewritten prompt.',
                $instruction,
            );
            $elapsed = microtime(true) - $start;

            if ($elapsed > self::POLISH_TIMEOUT_SECONDS) {
                Log::info('AiSeedComposer polish exceeded soft timeout, discarding', [
                    'elapsed' => $elapsed,
                    'budget'  => self::POLISH_TIMEOUT_SECONDS,
                ]);
                return '';
            }

            return $this->sanitizePolishedReply((string) $reply);
        } catch (AiQuotaExhausted | AiAllProvidersUnavailable $e) {
            // Provider explicitly signaled quota/outage. Silent fallback per
            // pin #5 — the raw template is fine, user must not see an error.
            Log::info('AiSeedComposer polish unavailable, using raw template', [
                'reason' => class_basename($e),
            ]);
            return '';
        } catch (Throwable $e) {
            Log::warning('AiSeedComposer polish threw unexpectedly, using raw template', [
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Strip common LLM cruft (leading/trailing quotes, markdown fences, empty
     * lines) so what we save is presentable if the user opens it in AI Config.
     */
    protected function sanitizePolishedReply(string $reply): string
    {
        $reply = trim($reply);
        if ($reply === '') {
            return '';
        }
        // Strip leading/trailing code fences.
        $reply = preg_replace('/^```(?:\w+)?\n?/', '', $reply) ?? $reply;
        $reply = preg_replace('/\n?```$/', '', $reply) ?? $reply;
        // Strip wrapping single/double quotes.
        $reply = trim($reply);
        if ((str_starts_with($reply, '"') && str_ends_with($reply, '"'))
            || (str_starts_with($reply, '\'') && str_ends_with($reply, '\''))) {
            $reply = substr($reply, 1, -1);
        }
        return trim($reply);
    }

    protected function labelForBusinessType(string $type): string
    {
        return match ($type) {
            'ecommerce'   => 'E-commerce',
            'services'    => 'Services / Agency',
            'restaurant'  => 'Restaurant / F&B',
            'clinic'      => 'Clinic / Healthcare',
            'real_estate' => 'Real Estate',
            default       => 'Other',
        };
    }

    protected function labelForTone(string $tone): string
    {
        return match ($tone) {
            'formal'   => 'Formal and professional',
            'playful'  => 'Playful and warm',
            'my_own'   => 'The business owner\'s own voice',
            default    => 'Friendly and helpful',
        };
    }

    /**
     * Preset chip suggestions per business type — used by the Livewire
     * component for Q2 fallback chips. Public so tests can pin the copy.
     *
     * @return array<int, string>
     */
    public static function presetQuestionsFor(string $businessType): array
    {
        return match ($businessType) {
            'ecommerce' => [
                'Is this in stock?',
                'Do you ship to my area?',
                'What\'s the price?',
            ],
            'services' => [
                'Do you offer free consultations?',
                'How much do you charge?',
                'How long does it take?',
            ],
            'restaurant' => [
                'Do you deliver to my area?',
                'Are you open right now?',
                'Can I see the menu?',
            ],
            'clinic' => [
                'Do you take my insurance?',
                'Can I book an appointment?',
                'What are your opening hours?',
            ],
            'real_estate' => [
                'Is this property still available?',
                'Can I book a viewing?',
                'What\'s the payment plan?',
            ],
            default => [
                'What do you offer?',
                'How much does it cost?',
                'How do I get started?',
            ],
        };
    }
}
