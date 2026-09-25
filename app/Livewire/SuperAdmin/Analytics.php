<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\Team;
use App\Services\Analytics\OnboardingFunnel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class Analytics extends Component
{
    /** Windowed metrics — dashboard is read-only, staleness ≤5 min is fine. */
    private const CACHE_TTL_WINDOWED = 300;
    /** All-time / rarely-changing metrics. */
    private const CACHE_TTL_ALL_TIME = 1800;

    private const HEALTH_ACTIVE = 'active';
    private const HEALTH_AT_RISK = 'at_risk';
    private const HEALTH_DORMANT = 'dormant';
    private const HEALTH_NEVER = 'never_activated';

    #[Url(as: 'refresh')]
    public ?string $refresh = null;

    #[Url(as: 'sort')]
    public string $sort = 'activity_desc';

    /** @var list<string> Cache-key sections owned by this component (used for bulk invalidation). */
    private const CACHE_SECTIONS = [
        'kpis',
        'funnel',
        'teams_table',
        'messages_daily',
        'platform_mix',
        'activation_funnel_7',
        'activation_funnel_30',
        'activation_rate',
        'time_to_first_ai_reply',
        'business_type_funnel',
        'retention_7d',
        'ai_dispatch_health',
    ];

    public function mount(): void
    {
        if ($this->refresh !== null) {
            foreach (self::CACHE_SECTIONS as $section) {
                Cache::forget($this->cacheKey($section));
            }
            $this->refresh = null;
        }
    }

    private function cacheKey(string $section): string
    {
        return "analytics:{$section}:v2";
    }

    /**
     * Cache-wrap with a graceful failure fallback. Any exception (timeout, missing
     * table, DB blip) returns an empty payload so the whole page doesn't 500.
     */
    private function remember(string $section, int $ttl, callable $callback, mixed $fallback = []): mixed
    {
        return Cache::remember($this->cacheKey($section), $ttl, function () use ($callback, $fallback, $section) {
            try {
                return $callback();
            } catch (Throwable $e) {
                report($e);
                if (is_array($fallback)) {
                    return $fallback + ['__error' => true, '__section' => $section];
                }
                return $fallback;
            }
        });
    }

    #[Computed]
    public function activationFunnel(): array
    {
        $service = app(OnboardingFunnel::class);

        return [
            'd7'  => $this->remember('activation_funnel_7',  self::CACHE_TTL_WINDOWED, fn () => $service->forWindow(7)),
            'd30' => $this->remember('activation_funnel_30', self::CACHE_TTL_WINDOWED, fn () => $service->forWindow(30)),
        ];
    }

    #[Computed]
    public function kpis(): array
    {
        return $this->remember('kpis', self::CACHE_TTL_WINDOWED, function (): array {
            $now = CarbonImmutable::now();
            $d7 = $now->subDays(7);
            $d14 = $now->subDays(14);
            $d30 = $now->subDays(30);

            $totalTeams = (int) DB::table('teams')->count();

            // Single grouped scan of recent messages, joined via conversations, keyed by team.
            // Replaces 2 subquery-based ->whereIn() COUNTs.
            $recentActivity = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->where('messages.created_at', '>=', $d30)
                ->select('conversations.team_id', DB::raw('MAX(messages.created_at) as last_at'), DB::raw("SUM(CASE WHEN messages.direction='inbound' THEN 1 ELSE 0 END) as inbound_c"))
                ->groupBy('conversations.team_id')
                ->get();

            $active7 = $recentActivity->filter(fn ($r) => $r->last_at >= $d7->toDateTimeString())->count();
            $active30 = $recentActivity->count();
            $teamsWithInbound7 = $recentActivity->filter(fn ($r) => $r->last_at >= $d7->toDateTimeString() && (int) $r->inbound_c > 0)->count();

            $signupsThisWeek = (int) DB::table('teams')->where('created_at', '>=', $d7)->count();
            $signupsLastWeek = (int) DB::table('teams')->whereBetween('created_at', [$d14, $d7])->count();
            $signupDelta = $signupsLastWeek === 0
                ? ($signupsThisWeek > 0 ? 100 : 0)
                : (int) round((($signupsThisWeek - $signupsLastWeek) / $signupsLastWeek) * 100);

            $aiReplies30 = (int) DB::table('messages')
                ->where('direction', 'outbound')
                ->where('sender_type', 'ai')
                ->where('created_at', '>=', $d30)
                ->count();

            return [
                'total_teams'         => $totalTeams,
                'active_7d'           => $active7,
                'active_30d'          => $active30,
                'signups_this_week'   => $signupsThisWeek,
                'signups_last_week'   => $signupsLastWeek,
                'signup_delta_pct'    => $signupDelta,
                'ai_replies_30d'      => $aiReplies30,
                'teams_inbound_7d'    => $teamsWithInbound7,
            ];
        }, fallback: [
            'total_teams' => 0, 'active_7d' => 0, 'active_30d' => 0,
            'signups_this_week' => 0, 'signups_last_week' => 0, 'signup_delta_pct' => 0,
            'ai_replies_30d' => 0, 'teams_inbound_7d' => 0,
        ]);
    }

    #[Computed]
    public function funnel(): array
    {
        return $this->remember('funnel', self::CACHE_TTL_ALL_TIME, function (): array {
            $teams = DB::table('teams')
                ->select(['id', 'owner_id', 'created_at'])
                ->get();

            if ($teams->isEmpty()) {
                return $this->emptyFunnel();
            }

            $teamIds = $teams->pluck('id')->all();
            $ownerIds = $teams->pluck('owner_id')->filter()->unique()->all();

            $verifiedOwners = DB::table('users')
                ->whereIn('id', $ownerIds)
                ->whereNotNull('email_verified_at')
                ->pluck('id')
                ->flip();

            $teamsWithActivePage = DB::table('pages')
                ->where('is_active', true)
                ->whereIn('team_id', $teamIds)
                ->distinct()
                ->pluck('team_id')
                ->flip();

            $teamsWithAiConfig = DB::table('ai_configs')
                ->whereIn('team_id', $teamIds)
                ->where(function ($q) {
                    $q->whereNotNull('business_description')
                        ->where('business_description', '!=', '')
                        ->orWhereNotNull('system_prompt');
                })
                ->distinct()
                ->pluck('team_id')
                ->flip();

            // One grouped pass: derive inbound/outbound presence + last activity per team.
            $activity = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->whereIn('conversations.team_id', $teamIds)
                ->groupBy('conversations.team_id')
                ->select([
                    'conversations.team_id',
                    DB::raw("SUM(CASE WHEN messages.direction='inbound' THEN 1 ELSE 0 END) as inbound_c"),
                    DB::raw("SUM(CASE WHEN messages.direction='outbound' THEN 1 ELSE 0 END) as outbound_c"),
                    DB::raw('MAX(messages.created_at) as last_at'),
                ])
                ->get()
                ->keyBy('team_id');

            $stages = [
                'signed_up'       => 0,
                'verified_email'  => 0,
                'connected_page'  => 0,
                'configured_ai'   => 0,
                'received_inbound'=> 0,
                'sent_outbound'   => 0,
                'retained_d7'     => 0,
            ];

            foreach ($teams as $team) {
                $stages['signed_up']++;

                if (isset($verifiedOwners[$team->owner_id])) {
                    $stages['verified_email']++;
                }
                if (isset($teamsWithActivePage[$team->id])) {
                    $stages['connected_page']++;
                }
                if (isset($teamsWithAiConfig[$team->id])) {
                    $stages['configured_ai']++;
                }

                $act = $activity->get($team->id);
                if ($act && (int) $act->inbound_c > 0) {
                    $stages['received_inbound']++;
                }
                if ($act && (int) $act->outbound_c > 0) {
                    $stages['sent_outbound']++;
                }

                $signupAt = Carbon::parse($team->created_at);
                $retentionWindowEnd = $signupAt->copy()->addDays(9);
                if ($retentionWindowEnd->isPast() && $act && $act->last_at) {
                    $lastAt = Carbon::parse($act->last_at);
                    if ($lastAt->gt($signupAt->copy()->addDays(5))) {
                        $stages['retained_d7']++;
                    }
                }
            }

            $total = $stages['signed_up'];
            $labels = [
                'signed_up'        => 'Signed up',
                'verified_email'   => 'Verified email',
                'connected_page'   => 'Connected first page',
                'configured_ai'    => 'Configured AI',
                'received_inbound' => 'Received first message',
                'sent_outbound'    => 'Sent first reply',
                'retained_d7'      => 'Retained day 7',
            ];

            $rows = [];
            foreach ($stages as $key => $count) {
                $rows[] = [
                    'key'   => $key,
                    'label' => $labels[$key],
                    'count' => $count,
                    'pct'   => $total > 0 ? (int) round(($count / $total) * 100) : 0,
                ];
            }

            return $rows;
        }, fallback: $this->emptyFunnel());
    }

    private function emptyFunnel(): array
    {
        $labels = [
            'signed_up'        => 'Signed up',
            'verified_email'   => 'Verified email',
            'connected_page'   => 'Connected first page',
            'configured_ai'    => 'Configured AI',
            'received_inbound' => 'Received first message',
            'sent_outbound'    => 'Sent first reply',
            'retained_d7'      => 'Retained day 7',
        ];

        return array_map(
            fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => 0, 'pct' => 0],
            $labels,
            array_keys($labels)
        );
    }

    #[Computed]
    public function teamsTable(): array
    {
        $rows = $this->remember('teams_table', self::CACHE_TTL_WINDOWED, function (): array {
            $d30 = CarbonImmutable::now()->subDays(30);

            $teams = Team::query()
                ->with('owner:id,email,name')
                ->get(['id', 'name', 'owner_id', 'created_at']);

            if ($teams->isEmpty()) {
                return [];
            }

            $teamIds = $teams->pluck('id')->all();

            $pagesByTeam = DB::table('pages')
                ->where('is_active', true)
                ->whereIn('team_id', $teamIds)
                ->select('team_id', 'platform')
                ->get()
                ->groupBy('team_id')
                ->map(fn ($rows) => $rows->pluck('platform')->unique()->values()->all());

            $aiConfiguredTeamIds = DB::table('ai_configs')
                ->whereIn('team_id', $teamIds)
                ->where(function ($q) {
                    $q->whereNotNull('business_description')
                        ->where('business_description', '!=', '')
                        ->orWhereNotNull('system_prompt');
                })
                ->distinct()
                ->pluck('team_id')
                ->flip();

            $messageStats = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->whereIn('conversations.team_id', $teamIds)
                ->where('messages.created_at', '>=', $d30)
                ->groupBy('conversations.team_id')
                ->select([
                    'conversations.team_id',
                    DB::raw("SUM(CASE WHEN messages.direction = 'inbound' THEN 1 ELSE 0 END) as inbound_30"),
                    DB::raw("SUM(CASE WHEN messages.direction = 'outbound' AND messages.sender_type = 'ai' THEN 1 ELSE 0 END) as ai_out_30"),
                    DB::raw("SUM(CASE WHEN messages.direction = 'outbound' AND messages.sender_type != 'ai' THEN 1 ELSE 0 END) as human_out_30"),
                    DB::raw('MAX(messages.created_at) as last_activity'),
                ])
                ->get()
                ->keyBy('team_id');

            $now = CarbonImmutable::now();
            $result = [];

            foreach ($teams as $team) {
                $stats = $messageStats->get($team->id);
                $inbound30 = (int) ($stats->inbound_30 ?? 0);
                $aiOut30 = (int) ($stats->ai_out_30 ?? 0);
                $humanOut30 = (int) ($stats->human_out_30 ?? 0);
                $lastActivity = $stats?->last_activity ? Carbon::parse($stats->last_activity) : null;
                $daysSinceActivity = $lastActivity ? (int) $lastActivity->diffInDays($now) : null;

                $platforms = $pagesByTeam->get($team->id, []);
                $hasAiConfig = isset($aiConfiguredTeamIds[$team->id]);
                $daysSinceSignup = (int) Carbon::parse($team->created_at)->diffInDays($now);

                $health = $this->healthBadge(
                    hasPage: count($platforms) > 0,
                    daysSinceSignup: $daysSinceSignup,
                    daysSinceActivity: $daysSinceActivity,
                );

                $totalOut = $aiOut30 + $humanOut30;
                $aiShare = $totalOut > 0 ? (int) round(($aiOut30 / $totalOut) * 100) : 0;

                $result[] = [
                    'id'                => $team->id,
                    'name'              => $team->name,
                    'owner_email'       => $team->owner?->email ?? '—',
                    'owner_name'        => $team->owner?->name ?? '—',
                    'signup_at'         => Carbon::parse($team->created_at)->format('Y-m-d'),
                    'days_since_signup' => $daysSinceSignup,
                    'days_since_activity' => $daysSinceActivity,
                    'platforms'         => $platforms,
                    'ai_configured'     => $hasAiConfig,
                    'inbound_30'        => $inbound30,
                    'ai_out_30'         => $aiOut30,
                    'human_out_30'      => $humanOut30,
                    'ai_share_pct'      => $aiShare,
                    'health'            => $health,
                ];
            }

            return $result;
        }, fallback: []);

        return $this->sortRows(is_array($rows) ? $rows : []);
    }

    private function sortRows(array $rows): array
    {
        // Filter out error-marker rows if fallback fired.
        $rows = array_values(array_filter($rows, fn ($r) => is_array($r) && isset($r['id'])));

        usort($rows, function ($a, $b) {
            return match ($this->sort) {
                'signup_desc'   => strcmp($b['signup_at'], $a['signup_at']),
                'signup_asc'    => strcmp($a['signup_at'], $b['signup_at']),
                'inbound_desc'  => $b['inbound_30'] <=> $a['inbound_30'],
                'ai_desc'       => $b['ai_out_30'] <=> $a['ai_out_30'],
                'name_asc'      => strcasecmp($a['name'], $b['name']),
                'activity_desc' => ($a['days_since_activity'] ?? PHP_INT_MAX) <=> ($b['days_since_activity'] ?? PHP_INT_MAX),
                default         => 0,
            };
        });

        return $rows;
    }

    private function healthBadge(bool $hasPage, int $daysSinceSignup, ?int $daysSinceActivity): string
    {
        if (! $hasPage && $daysSinceSignup > 7) {
            return self::HEALTH_NEVER;
        }
        if ($daysSinceActivity === null) {
            return $daysSinceSignup <= 7 ? self::HEALTH_ACTIVE : self::HEALTH_NEVER;
        }
        if ($daysSinceActivity <= 7) {
            return self::HEALTH_ACTIVE;
        }
        if ($daysSinceActivity <= 14) {
            return self::HEALTH_AT_RISK;
        }
        return self::HEALTH_DORMANT;
    }

    #[Computed]
    public function messagesDaily(): array
    {
        return $this->remember('messages_daily', self::CACHE_TTL_WINDOWED, function (): array {
            $d30 = CarbonImmutable::now()->subDays(30)->startOfDay();

            $rows = DB::table('messages')
                ->where('created_at', '>=', $d30)
                ->groupBy('day', 'direction', 'sender_type')
                ->select([
                    DB::raw('DATE(created_at) as day'),
                    'direction',
                    'sender_type',
                    DB::raw('COUNT(*) as c'),
                ])
                ->get();

            $days = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = CarbonImmutable::now()->subDays($i)->format('Y-m-d');
                $days[$day] = ['day' => $day, 'inbound' => 0, 'ai_out' => 0, 'human_out' => 0];
            }

            foreach ($rows as $r) {
                $day = $r->day;
                if (! isset($days[$day])) {
                    continue;
                }
                if ($r->direction === 'inbound') {
                    $days[$day]['inbound'] += (int) $r->c;
                } elseif ($r->sender_type === 'ai') {
                    $days[$day]['ai_out'] += (int) $r->c;
                } else {
                    $days[$day]['human_out'] += (int) $r->c;
                }
            }

            return array_values($days);
        }, fallback: []);
    }

    #[Computed]
    public function platformMix(): array
    {
        return $this->remember('platform_mix', self::CACHE_TTL_WINDOWED, function (): array {
            $d30 = CarbonImmutable::now()->subDays(30);

            $rows = DB::table('messages')
                ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                ->where('messages.created_at', '>=', $d30)
                ->groupBy('conversations.platform')
                ->select('conversations.platform', DB::raw('COUNT(*) as c'))
                ->orderByDesc('c')
                ->get();

            $total = (int) $rows->sum('c');

            return $rows->map(fn ($r) => [
                'platform' => $r->platform ?? 'unknown',
                'count'    => (int) $r->c,
                'pct'      => $total > 0 ? (int) round(((int) $r->c / $total) * 100) : 0,
            ])->all();
        }, fallback: []);
    }

    /**
     * Activation rate — % of teams that completed onboarding within 24h of signup.
     * Cohort: teams that signed up 24h+ ago (so they had a chance to complete).
     */
    #[Computed]
    public function activationRate(): array
    {
        return $this->remember('activation_rate', self::CACHE_TTL_WINDOWED, function (): array {
            $cutoff = CarbonImmutable::now()->subHours(24);

            $eligible = (int) DB::table('teams')
                ->where('created_at', '<=', $cutoff)
                ->count();

            if ($eligible === 0) {
                return ['pct' => 0, 'activated' => 0, 'eligible' => 0, 'has_data' => false];
            }

            // Activated within 24h of signup. Portable across MySQL and SQLite —
            // we compare `onboarding_completed_at` against `created_at + 24h`
            // computed at the app layer, not with vendor-specific TIMESTAMPDIFF.
            $activated = 0;
            DB::table('teams')
                ->where('created_at', '<=', $cutoff)
                ->whereNotNull('onboarding_completed_at')
                ->select('created_at', 'onboarding_completed_at')
                ->orderBy('id')
                ->chunk(500, function ($chunk) use (&$activated) {
                    foreach ($chunk as $t) {
                        $signup = Carbon::parse($t->created_at);
                        $done = Carbon::parse($t->onboarding_completed_at);
                        if ($done->diffInHours($signup) <= 24) {
                            $activated++;
                        }
                    }
                });

            return [
                'pct'       => (int) round(($activated / $eligible) * 100),
                'activated' => $activated,
                'eligible'  => $eligible,
                'has_data'  => true,
            ];
        }, fallback: ['pct' => 0, 'activated' => 0, 'eligible' => 0, 'has_data' => false]);
    }

    /**
     * Median seconds from team creation to first outbound AI message.
     */
    #[Computed]
    public function timeToFirstAiReply(): array
    {
        return $this->remember('time_to_first_ai_reply', self::CACHE_TTL_WINDOWED, function (): array {
            // Grab first AI outbound per team, JOIN to team created_at, compute deltas in PHP.
            $rows = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->join('teams', 'teams.id', '=', 'conversations.team_id')
                ->where('messages.direction', 'outbound')
                ->where('messages.sender_type', 'ai')
                ->groupBy('conversations.team_id', 'teams.created_at')
                ->select('conversations.team_id', 'teams.created_at as team_created_at', DB::raw('MIN(messages.created_at) as first_ai_at'))
                ->get();

            if ($rows->isEmpty()) {
                return ['median_seconds' => null, 'sample_size' => 0, 'has_data' => false];
            }

            $deltas = $rows
                ->map(fn ($r) => Carbon::parse($r->first_ai_at)->diffInSeconds(Carbon::parse($r->team_created_at)))
                ->filter(fn ($d) => $d >= 0)
                ->sort()
                ->values();

            $count = $deltas->count();
            if ($count === 0) {
                return ['median_seconds' => null, 'sample_size' => 0, 'has_data' => false];
            }

            $median = $count % 2 === 1
                ? $deltas[(int) floor($count / 2)]
                : (int) round(($deltas[$count / 2 - 1] + $deltas[$count / 2]) / 2);

            return [
                'median_seconds' => (int) $median,
                'sample_size'    => $count,
                'has_data'       => true,
            ];
        }, fallback: ['median_seconds' => null, 'sample_size' => 0, 'has_data' => false]);
    }

    /**
     * Onboarding completion rate broken down by business_type (top 5 by team count).
     */
    #[Computed]
    public function businessTypeFunnel(): array
    {
        return $this->remember('business_type_funnel', self::CACHE_TTL_WINDOWED, function (): array {
            $rows = DB::table('teams')
                ->whereNotNull('business_type')
                ->where('business_type', '!=', '')
                ->groupBy('business_type')
                ->select([
                    'business_type',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN onboarding_completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed'),
                ])
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            if ($rows->isEmpty()) {
                return ['rows' => [], 'has_data' => false];
            }

            return [
                'rows' => $rows->map(fn ($r) => [
                    'business_type' => $r->business_type,
                    'total'         => (int) $r->total,
                    'completed'     => (int) $r->completed,
                    'pct'           => (int) $r->total > 0 ? (int) round(((int) $r->completed / (int) $r->total) * 100) : 0,
                ])->all(),
                'has_data' => true,
            ];
        }, fallback: ['rows' => [], 'has_data' => false]);
    }

    /**
     * 7-day retention: of teams that signed up 7-30 days ago, how many sent/received
     * a message in the last 7 days? Filters out fresh signups (still activating) and
     * ancient teams (irrelevant).
     */
    #[Computed]
    public function retention7d(): array
    {
        return $this->remember('retention_7d', self::CACHE_TTL_WINDOWED, function (): array {
            $d7 = CarbonImmutable::now()->subDays(7);
            $d30 = CarbonImmutable::now()->subDays(30);

            $cohort = DB::table('teams')
                ->whereBetween('created_at', [$d30, $d7])
                ->pluck('id');

            if ($cohort->isEmpty()) {
                return ['pct' => 0, 'retained' => 0, 'cohort' => 0, 'has_data' => false];
            }

            $retained = (int) DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->whereIn('conversations.team_id', $cohort->all())
                ->where('messages.created_at', '>=', $d7)
                ->distinct('conversations.team_id')
                ->count('conversations.team_id');

            return [
                'pct'      => (int) round(($retained / $cohort->count()) * 100),
                'retained' => $retained,
                'cohort'   => $cohort->count(),
                'has_data' => true,
            ];
        }, fallback: ['pct' => 0, 'retained' => 0, 'cohort' => 0, 'has_data' => false]);
    }

    /**
     * AI dispatch health — % of teams that pass the fast-path dispatch preconditions
     * (ai_enabled = true AND plan_status not cancelled AND not upstream-limited).
     *
     * Approximation of Team::canDispatchAi() at aggregate scale — calling the real
     * method per-team would N+1 (each call reads a cache key). If you want a precise
     * answer, sample it via queue-driven aggregation instead.
     */
    #[Computed]
    public function aiDispatchHealth(): array
    {
        return $this->remember('ai_dispatch_health', self::CACHE_TTL_WINDOWED, function (): array {
            $total = (int) DB::table('teams')->count();
            if ($total === 0) {
                return ['pct' => 0, 'dispatchable' => 0, 'total' => 0, 'has_data' => false];
            }

            $dispatchable = (int) DB::table('teams')
                ->where('ai_enabled', true)
                ->where(function ($q) {
                    $q->whereNull('plan_status')
                        ->orWhereNotIn('plan_status', ['cancelled']);
                })
                ->count();

            return [
                'pct'          => (int) round(($dispatchable / $total) * 100),
                'dispatchable' => $dispatchable,
                'total'        => $total,
                'has_data'     => true,
            ];
        }, fallback: ['pct' => 0, 'dispatchable' => 0, 'total' => 0, 'has_data' => false]);
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
    }

    public function render()
    {
        return view('livewire.super-admin.analytics');
    }
}
