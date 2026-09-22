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

class Analytics extends Component
{
    private const CACHE_TTL = 900;

    private const HEALTH_ACTIVE = 'active';
    private const HEALTH_AT_RISK = 'at_risk';
    private const HEALTH_DORMANT = 'dormant';
    private const HEALTH_NEVER = 'never_activated';

    #[Url(as: 'refresh')]
    public ?string $refresh = null;

    #[Url(as: 'sort')]
    public string $sort = 'activity_desc';

    public function mount(): void
    {
        if ($this->refresh !== null) {
            Cache::forget($this->cacheKey('kpis'));
            Cache::forget($this->cacheKey('funnel'));
            Cache::forget($this->cacheKey('teams_table'));
            Cache::forget($this->cacheKey('messages_daily'));
            Cache::forget($this->cacheKey('platform_mix'));
            Cache::forget($this->cacheKey('activation_funnel_7'));
            Cache::forget($this->cacheKey('activation_funnel_30'));
            $this->refresh = null;
        }
    }

    /**
     * Phase E — Onboarding activation funnel, DB-derived (7d + 30d windows).
     * Cached separately from the legacy `funnel` computed above.
     *
     * @return array{
     *   d7:  array{window_days:int,since:string,stages:list<array<string,mixed>>},
     *   d30: array{window_days:int,since:string,stages:list<array<string,mixed>>}
     * }
     */
    #[Computed]
    public function activationFunnel(): array
    {
        $service = app(OnboardingFunnel::class);

        return [
            'd7'  => $this->remember('activation_funnel_7',  fn () => $service->forWindow(7)),
            'd30' => $this->remember('activation_funnel_30', fn () => $service->forWindow(30)),
        ];
    }

    private function cacheKey(string $section): string
    {
        return "super_admin.analytics.{$section}";
    }

    private function remember(string $section, callable $callback): mixed
    {
        return Cache::remember($this->cacheKey($section), self::CACHE_TTL, $callback);
    }

    #[Computed]
    public function kpis(): array
    {
        return $this->remember('kpis', function (): array {
            $now = CarbonImmutable::now();
            $d7 = $now->subDays(7);
            $d14 = $now->subDays(14);
            $d30 = $now->subDays(30);

            $totalTeams = Team::count();

            $active7 = Team::whereIn('id', function ($q) use ($d7) {
                $q->select('team_id')
                    ->from('conversations')
                    ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                    ->where('messages.created_at', '>=', $d7);
            })->count();

            $active30 = Team::whereIn('id', function ($q) use ($d30) {
                $q->select('team_id')
                    ->from('conversations')
                    ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                    ->where('messages.created_at', '>=', $d30);
            })->count();

            $signupsThisWeek = Team::where('created_at', '>=', $d7)->count();
            $signupsLastWeek = Team::whereBetween('created_at', [$d14, $d7])->count();
            $signupDelta = $signupsLastWeek === 0
                ? ($signupsThisWeek > 0 ? 100 : 0)
                : (int) round((($signupsThisWeek - $signupsLastWeek) / $signupsLastWeek) * 100);

            $aiReplies30 = DB::table('messages')
                ->where('direction', 'outbound')
                ->where('sender_type', 'ai')
                ->where('created_at', '>=', $d30)
                ->count();

            $teamsWithInbound7 = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->where('messages.direction', 'inbound')
                ->where('messages.created_at', '>=', $d7)
                ->distinct('conversations.team_id')
                ->count('conversations.team_id');

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
        });
    }

    #[Computed]
    public function funnel(): array
    {
        return $this->remember('funnel', function (): array {
            $teams = Team::query()
                ->select([
                    'teams.id',
                    'teams.owner_id',
                    'teams.created_at',
                ])
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

            $teamsWithInbound = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->where('messages.direction', 'inbound')
                ->whereIn('conversations.team_id', $teamIds)
                ->distinct()
                ->pluck('conversations.team_id')
                ->flip();

            $teamsWithOutbound = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->where('messages.direction', 'outbound')
                ->whereIn('conversations.team_id', $teamIds)
                ->distinct()
                ->pluck('conversations.team_id')
                ->flip();

            $lastActivityByTeam = DB::table('conversations')
                ->join('messages', 'messages.conversation_id', '=', 'conversations.id')
                ->whereIn('conversations.team_id', $teamIds)
                ->groupBy('conversations.team_id')
                ->select('conversations.team_id', DB::raw('MAX(messages.created_at) as last_at'))
                ->get()
                ->pluck('last_at', 'team_id');

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

                if (isset($teamsWithInbound[$team->id])) {
                    $stages['received_inbound']++;
                }

                if (isset($teamsWithOutbound[$team->id])) {
                    $stages['sent_outbound']++;
                }

                $signupAt = Carbon::parse($team->created_at);
                $retentionWindowStart = $signupAt->copy()->addDays(5);
                $retentionWindowEnd = $signupAt->copy()->addDays(9);
                if ($retentionWindowEnd->isPast() && isset($lastActivityByTeam[$team->id])) {
                    $lastAt = Carbon::parse($lastActivityByTeam[$team->id]);
                    if ($lastAt->between($retentionWindowStart, $retentionWindowEnd) || $lastAt->gt($retentionWindowEnd)) {
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
        });
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
        $rows = $this->remember('teams_table', function (): array {
            $d30 = CarbonImmutable::now()->subDays(30);
            $d7 = CarbonImmutable::now()->subDays(7);

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
        });

        return $this->sortRows($rows);
    }

    private function sortRows(array $rows): array
    {
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
        return $this->remember('messages_daily', function (): array {
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
        });
    }

    #[Computed]
    public function platformMix(): array
    {
        return $this->remember('platform_mix', function (): array {
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
        });
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
