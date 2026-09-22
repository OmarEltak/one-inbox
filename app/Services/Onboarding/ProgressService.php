<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use Illuminate\Support\Facades\Cache;

/**
 * Phase C — progress detection.
 *
 * Computes the 5-step onboarding checklist for a team + a percent-complete
 * used by the top-bar pill and the empty-inbox next-step panel.
 *
 * IMPORTANT: the "Configure AI knowledge base" step reads from
 * $team->settings['onboarding_ai_seed'] (Phase A persists there) — NOT from
 * AiConfig. AiConfig requires page_id and only exists after connect, so
 * checking it would leave the step permanently red.
 *
 * Cache TTL 60s, invalidated on Team::updated / Page::created / Page::deleted
 * / AiConfig::updated / Message::created via ProgressCacheObserver.
 */
final class ProgressService
{
    public const STEP_MEET_AI          = 'meet-ai';
    public const STEP_CONFIGURE_AI     = 'configure-ai';
    public const STEP_CONNECT_PAGE     = 'connect-page';
    public const STEP_SEND_TEST_MSG    = 'send-test-message';
    public const STEP_INVITE_TEAMMATE  = 'invite-teammate';

    // "Optional" steps count at half weight when computing percent.
    private const OPTIONAL_STEPS = [self::STEP_INVITE_TEAMMATE];

    public const CACHE_TTL_SECONDS = 60;

    /**
     * @return array<int, array{id: string, label: string, done: bool, url: string, optional: bool}>
     */
    public function stepsFor(Team $team): array
    {
        return Cache::remember(
            $this->cacheKey($team),
            self::CACHE_TTL_SECONDS,
            fn () => $this->computeSteps($team),
        );
    }

    public function percentComplete(Team $team): int
    {
        $steps = $this->stepsFor($team);
        if ($steps === []) {
            return 0;
        }

        $totalWeight = 0.0;
        $doneWeight  = 0.0;
        foreach ($steps as $step) {
            $weight = $step['optional'] ? 0.5 : 1.0;
            $totalWeight += $weight;
            if ($step['done']) {
                $doneWeight += $weight;
            }
        }
        if ($totalWeight <= 0.0) {
            return 0;
        }

        return (int) round(($doneWeight / $totalWeight) * 100);
    }

    /**
     * Return the first unfinished step, or null if fully done. Used by the
     * empty-inbox next-step panel to point at the highest-priority next
     * action.
     *
     * @return array{id: string, label: string, done: bool, url: string, optional: bool}|null
     */
    public function nextStep(Team $team): ?array
    {
        foreach ($this->stepsFor($team) as $step) {
            if (! $step['done']) {
                return $step;
            }
        }
        return null;
    }

    public function forget(Team $team): void
    {
        Cache::forget($this->cacheKey($team));
    }

    private function cacheKey(Team $team): string
    {
        return "onboarding.progress.{$team->id}";
    }

    /**
     * @return array<int, array{id: string, label: string, done: bool, url: string, optional: bool}>
     */
    private function computeSteps(Team $team): array
    {
        $meetAiDone       = $team->onboarding_completed_at !== null
            || $this->hasOnboardingSeed($team);
        $configureAiDone  = $this->hasOnboardingSeed($team);
        $connectPageDone  = $this->hasActivePage($team);
        $sendTestMsgDone  = $this->hasAnyMessage($team);
        $inviteTeamDone   = $this->hasSecondMember($team);

        return [
            [
                'id'       => self::STEP_MEET_AI,
                'label'    => 'Meet your AI',
                'done'     => $meetAiDone,
                'url'      => '/onboarding/meet-your-ai',
                'optional' => in_array(self::STEP_MEET_AI, self::OPTIONAL_STEPS, true),
            ],
            [
                'id'       => self::STEP_CONFIGURE_AI,
                'label'    => 'Configure your AI knowledge base',
                'done'     => $configureAiDone,
                'url'      => '/onboarding/meet-your-ai',
                'optional' => in_array(self::STEP_CONFIGURE_AI, self::OPTIONAL_STEPS, true),
            ],
            [
                'id'       => self::STEP_CONNECT_PAGE,
                'label'    => 'Connect a page',
                'done'     => $connectPageDone,
                'url'      => '/connections',
                'optional' => in_array(self::STEP_CONNECT_PAGE, self::OPTIONAL_STEPS, true),
            ],
            [
                'id'       => self::STEP_SEND_TEST_MSG,
                'label'    => 'Send a test message',
                'done'     => $sendTestMsgDone,
                'url'      => '/inbox',
                'optional' => in_array(self::STEP_SEND_TEST_MSG, self::OPTIONAL_STEPS, true),
            ],
            [
                'id'       => self::STEP_INVITE_TEAMMATE,
                'label'    => 'Invite a teammate',
                'done'     => $inviteTeamDone,
                'url'      => '/settings/admins',
                'optional' => in_array(self::STEP_INVITE_TEAMMATE, self::OPTIONAL_STEPS, true),
            ],
        ];
    }

    private function hasOnboardingSeed(Team $team): bool
    {
        $seed = data_get($team->settings, 'onboarding_ai_seed.system_prompt');
        return is_string($seed) && trim($seed) !== '';
    }

    private function hasActivePage(Team $team): bool
    {
        return Page::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->exists();
    }

    private function hasAnyMessage(Team $team): bool
    {
        return Message::query()
            ->whereIn('conversation_id', function ($q) use ($team) {
                $q->select('id')->from('conversations')->where('team_id', $team->id);
            })
            ->exists();
    }

    private function hasSecondMember(Team $team): bool
    {
        return $team->members()->count() > 1;
    }
}
