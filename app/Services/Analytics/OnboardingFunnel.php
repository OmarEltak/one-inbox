<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Onboarding activation funnel — Phase E.
 *
 * Computes per-stage team counts and drop-off percentages for a rolling
 * time window (7d or 30d, driven by `$days`).
 *
 * Data sources (deliberately DB-derived, not event-log-derived, so the
 * funnel keeps working even if HeronSignal is unreachable):
 *
 *   - signed_up               teams.created_at
 *   - onboarding_step_1       teams.business_type IS NOT NULL
 *   - onboarding_step_3       ai_configs.system_prompt exists / non-empty
 *   - onboarding_completed    teams.onboarding_completed_at IS NOT NULL
 *   - plan_picked             teams.plan_trial_started_at IS NOT NULL
 *   - connection_requested    onboarding_requests row exists
 *   - connection_completed    onboarding_requests.status = completed OR
 *                             team owns an is_active Page
 *   - first_real_message      teams.settings->first_real_message_at IS NOT NULL
 *                             (falls back to any inbound message in period)
 *   - first_ai_reply          teams.settings->first_ai_reply_at IS NOT NULL
 *                             (falls back to any outbound ai message in period)
 *   - paid                    teams.plan_status = 'paid'
 *
 * The stage counters are cohort-scoped: a team is counted at stage X only
 * if it also signed up inside the same window. That means the funnel is
 * always "of the teams that signed up in the last N days, how many made
 * it to step X?" — the only shape that makes drop-off percentages honest.
 */
final class OnboardingFunnel
{
    /** @var list<string> Ordered stage keys. Order = funnel display order. */
    public const STAGES = [
        'signed_up',
        'onboarding_step_1',
        'onboarding_step_3',
        'onboarding_completed',
        'plan_picked',
        'connection_requested',
        'connection_completed',
        'first_real_message',
        'first_ai_reply',
        'paid',
    ];

    private const LABELS = [
        'signed_up'            => 'Signed up',
        'onboarding_step_1'    => 'Picked business type',
        'onboarding_step_3'    => 'AI configured',
        'onboarding_completed' => 'Finished onboarding',
        'plan_picked'          => 'Picked a plan',
        'connection_requested' => 'Requested connection',
        'connection_completed' => 'Connection completed',
        'first_real_message'   => 'First real inbound',
        'first_ai_reply'       => 'First real AI reply',
        'paid'                 => 'Paid',
    ];

    /**
     * @return array{
     *   window_days: int,
     *   since: string,
     *   stages: list<array{key:string,label:string,count:int,pct:int,drop_pct:int}>
     * }
     */
    public function forWindow(int $days): array
    {
        if ($days <= 0) {
            $days = 30;
        }

        $since = CarbonImmutable::now()->subDays($days)->startOfDay();

        $signupTeamIds = Team::query()
            ->where('created_at', '>=', $since)
            ->pluck('id')
            ->all();

        $counts = $this->countsForTeams($signupTeamIds);

        $signupTotal = $counts['signed_up'];
        $prev = $signupTotal;
        $stages = [];

        foreach (self::STAGES as $key) {
            $count = $counts[$key];
            $pct = $signupTotal > 0
                ? (int) round(($count / $signupTotal) * 100)
                : 0;

            // Drop-off is measured from the PREVIOUS stage, not from signup —
            // that's what makes it useful for spotting where teams die.
            $dropPct = $prev > 0
                ? (int) round((($prev - $count) / $prev) * 100)
                : 0;

            $stages[] = [
                'key'      => $key,
                'label'    => self::LABELS[$key],
                'count'    => $count,
                'pct'      => $pct,
                'drop_pct' => max(0, $dropPct),
            ];

            $prev = $count;
        }

        return [
            'window_days' => $days,
            'since'       => $since->toIso8601String(),
            'stages'      => $stages,
        ];
    }

    /**
     * @param  list<int>  $teamIds
     * @return array<string,int>
     */
    private function countsForTeams(array $teamIds): array
    {
        $zeroed = array_fill_keys(self::STAGES, 0);

        if (empty($teamIds)) {
            return $zeroed;
        }

        $signupCount = count($teamIds);

        $step1 = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->whereNotNull('business_type')
            ->count();

        $step3 = DB::table('ai_configs')
            ->whereIn('team_id', $teamIds)
            ->whereNotNull('system_prompt')
            ->where('system_prompt', '!=', '')
            ->distinct()
            ->count('team_id');

        $onboardingCompleted = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->whereNotNull('onboarding_completed_at')
            ->count();

        $planPicked = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->whereNotNull('plan_trial_started_at')
            ->count();

        $connectionRequested = DB::table('onboarding_requests')
            ->whereIn('team_id', $teamIds)
            ->distinct()
            ->count('team_id');

        // "Connection completed" = the concierge marked a request completed OR
        // the team owns any active Page (self-serve platforms like WhatsApp QR
        // never file an onboarding_request but still count as connected).
        $connectionCompletedViaRequest = DB::table('onboarding_requests')
            ->whereIn('team_id', $teamIds)
            ->where('status', 'completed')
            ->distinct()
            ->pluck('team_id')
            ->all();

        $connectionCompletedViaPage = DB::table('pages')
            ->whereIn('team_id', $teamIds)
            ->where('is_active', true)
            ->distinct()
            ->pluck('team_id')
            ->all();

        $connectionCompleted = count(array_unique(array_merge(
            $connectionCompletedViaRequest,
            $connectionCompletedViaPage
        )));

        // Once-per-team flags live in teams.settings JSON. Query with JSON
        // extract for MySQL and json_extract for SQLite (Laravel abstracts
        // both via ->whereNotNull('settings->key')).
        $firstRealMessage = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->whereNotNull('settings->first_real_message_at')
            ->count();

        $firstAiReply = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->whereNotNull('settings->first_ai_reply_at')
            ->count();

        $paid = DB::table('teams')
            ->whereIn('id', $teamIds)
            ->where('plan_status', 'paid')
            ->count();

        return [
            'signed_up'            => $signupCount,
            'onboarding_step_1'    => $step1,
            'onboarding_step_3'    => $step3,
            'onboarding_completed' => $onboardingCompleted,
            'plan_picked'          => $planPicked,
            'connection_requested' => $connectionRequested,
            'connection_completed' => $connectionCompleted,
            'first_real_message'   => $firstRealMessage,
            'first_ai_reply'       => $firstAiReply,
            'paid'                 => $paid,
        ];
    }
}
