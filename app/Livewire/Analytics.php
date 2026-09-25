<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Analytics dashboard.
 *
 * Scoping rule (see ARCHITECTURE §2 + §15):
 *   All aggregates are scoped to conversations whose page_id is in the
 *   currently-active Page set for the team. This mirrors the sidebar's
 *   `hasAnyConnection()` gate — "currently connected" = `pages.is_active = true`.
 *   Deactivated pages (old disconnections, superseded transfers) are excluded
 *   so the dashboard reflects only live connections.
 *
 * Users can further narrow the view with the page chip selector — the
 * default selection is every active page.
 *
 * Perf (2026-09-25): copies the super-admin analytics pattern (commit 8e2b198).
 * Every metric is cache-wrapped INDIVIDUALLY with try/catch → a single failing
 * query returns an empty fallback instead of 500-ing the whole dashboard. Cache
 * keys are versioned (v2) so this deploy invalidates prior monolithic entries.
 */
class Analytics extends Component
{
    /** Windowed metrics — dashboard is read-only, staleness ≤5 min is fine. */
    private const CACHE_TTL_WINDOWED = 300;
    /** All-time / team-lifetime metrics (contact funnels, etc.). */
    private const CACHE_TTL_ALL_TIME = 1800;

    public string $period = '30'; // days

    /**
     * IDs of active pages the user has selected to view analytics for.
     * Empty on first render — mount() fills it with every active page.
     * Not persisted; each page load resets to "all active".
     */
    public array $selectedPageIds = [];

    public function mount(): void
    {
        $this->selectedPageIds = $this->activePages->pluck('id')->all();
    }

    /**
     * All currently-connected pages for the team. Deactivated pages are hidden.
     */
    #[Computed]
    public function activePages(): Collection
    {
        $team = Auth::user()?->currentTeam;

        if (! $team) {
            return collect();
        }

        return Page::query()
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->orderBy('platform')
            ->orderBy('name')
            ->get(['id', 'name', 'platform', 'avatar']);
    }

    public function togglePage(int $pageId): void
    {
        $activeIds = $this->activePages->pluck('id')->all();

        // Ignore attempts to toggle a page that isn't currently connected —
        // guards against stale UI state after a page is deactivated in another tab.
        if (! in_array($pageId, $activeIds, true)) {
            return;
        }

        if (in_array($pageId, $this->selectedPageIds, true)) {
            // Never allow deselecting the last remaining chip — empty selection
            // would produce an empty dashboard with no obvious way back.
            if (count($this->selectedPageIds) <= 1) {
                return;
            }
            $this->selectedPageIds = array_values(array_diff($this->selectedPageIds, [$pageId]));
        } else {
            $this->selectedPageIds[] = $pageId;
        }
    }

    public function selectAllPages(): void
    {
        $this->selectedPageIds = $this->activePages->pluck('id')->all();
    }

    /**
     * Build a stable cache key for a metric. Selection is hashed so cache is
     * per team + period + selected pages combination.
     */
    private function cacheKey(string $section, int $teamId, array $pageIds, ?int $period = null): string
    {
        $selectionKey = md5(implode(',', $pageIds));
        $windowKey = $period !== null ? ":{$period}" : '';

        return "analytics:{$teamId}:{$section}{$windowKey}:{$selectionKey}:v2";
    }

    /**
     * Cache-wrap with a graceful failure fallback. Any exception (timeout, missing
     * table, DB blip) returns the fallback so the whole page doesn't 500.
     */
    private function remember(string $section, int $teamId, array $pageIds, int $ttl, callable $callback, mixed $fallback, ?int $period = null): mixed
    {
        return Cache::remember(
            $this->cacheKey($section, $teamId, $pageIds, $period),
            $ttl,
            function () use ($callback, $fallback, $section) {
                try {
                    return $callback();
                } catch (Throwable $e) {
                    report($e);
                    if (is_array($fallback)) {
                        return $fallback + ['__error' => true, '__section' => $section];
                    }
                    return $fallback;
                }
            }
        );
    }

    public function render()
    {
        $team = Auth::user()?->currentTeam;

        if (! $team) {
            return view('livewire.analytics', ['data' => null, 'activePages' => collect(), 'noConnections' => false])
                ->layout('layouts.app', ['title' => 'Analytics']);
        }

        $activePages = $this->activePages;
        $activeIds = $activePages->pluck('id')->all();

        // Drop any selected IDs that no longer belong to an active page — happens
        // if a page is deactivated between mount and render (e.g. cross-tab).
        $pageIds = array_values(array_intersect($this->selectedPageIds, $activeIds));

        if (empty($pageIds)) {
            return view('livewire.analytics', [
                'data' => null,
                'activePages' => $activePages,
                'noConnections' => true,
            ])->layout('layouts.app', ['title' => 'Analytics']);
        }

        $teamId = $team->id;
        $period = (int) $this->period;
        $since = now()->subDays($period)->startOfDay();

        // Each metric is cached INDEPENDENTLY with try/catch — one slow/failing
        // query no longer 500s the whole page.
        $data = [
            'aiVsHuman' => $this->remember(
                'ai_vs_human', $teamId, $pageIds, self::CACHE_TTL_WINDOWED,
                fn () => $this->getAiVsHumanBreakdown($teamId, $since, $pageIds),
                ['ai' => 0, 'human' => 0, 'total' => 0, 'ai_percent' => 0],
                $period,
            ),
            'responseTime' => $this->remember(
                'response_time', $teamId, $pageIds, self::CACHE_TTL_WINDOWED,
                fn () => $this->getResponseTimes($teamId, $since, $pageIds),
                ['ai_avg' => null, 'human_avg' => null, 'ai_count' => 0, 'human_count' => 0],
                $period,
            ),
            'conversationVolume' => $this->remember(
                'conversation_volume', $teamId, $pageIds, self::CACHE_TTL_WINDOWED,
                fn () => $this->getConversationVolume($teamId, $since, $pageIds),
                ['total' => 0, 'by_status' => [], 'ai_paused' => 0],
                $period,
            ),
            'leadDistribution' => $this->remember(
                'lead_distribution', $teamId, $pageIds, self::CACHE_TTL_ALL_TIME,
                fn () => $this->getLeadDistribution($teamId, $pageIds),
                [],
            ),
            'platformPerformance' => $this->remember(
                'platform_performance', $teamId, $pageIds, self::CACHE_TTL_WINDOWED,
                fn () => $this->getPlatformPerformance($teamId, $since, $pageIds),
                [],
                $period,
            ),
            'topObjections' => $this->remember(
                'top_objections', $teamId, $pageIds, self::CACHE_TTL_WINDOWED,
                fn () => $this->getTopObjections($teamId, $since, $pageIds),
                [],
                $period,
            ),
            'conversionFunnel' => $this->remember(
                'conversion_funnel', $teamId, $pageIds, self::CACHE_TTL_ALL_TIME,
                fn () => $this->getConversionFunnel($teamId, $pageIds),
                ['stages' => ['new' => 0, 'cold' => 0, 'warm' => 0, 'hot' => 0, 'converted' => 0, 'lost' => 0], 'total' => 0, 'conversion_rate' => 0],
            ),
            'dailyMessages' => $this->remember(
                'daily_messages', $teamId, $pageIds, self::CACHE_TTL_WINDOWED,
                fn () => $this->getDailyMessages($teamId, $since, $pageIds),
                [],
                $period,
            ),
        ];

        // Strip internal error markers from array-shaped payloads before templating.
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                unset($data[$key]['__error'], $data[$key]['__section']);
            }
        }

        return view('livewire.analytics', [
            'data' => $data,
            'activePages' => $activePages,
            'noConnections' => false,
        ])->layout('layouts.app', ['title' => 'Analytics']);
    }

    protected function getAiVsHumanBreakdown(int $teamId, $since, array $pageIds): array
    {
        $counts = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.team_id', $teamId)
            ->whereIn('conversations.page_id', $pageIds)
            ->where('messages.direction', 'outbound')
            ->where('messages.created_at', '>=', $since)
            ->selectRaw("
                SUM(CASE WHEN messages.sender_type = 'ai' THEN 1 ELSE 0 END) as ai,
                SUM(CASE WHEN messages.sender_type = 'user' THEN 1 ELSE 0 END) as human
            ")
            ->first();

        $ai = (int) ($counts->ai ?? 0);
        $human = (int) ($counts->human ?? 0);
        $total = $ai + $human;

        return [
            'ai' => $ai,
            'human' => $human,
            'total' => $total,
            'ai_percent' => $total > 0 ? round(($ai / $total) * 100, 1) : 0,
        ];
    }

    protected function getResponseTimes(int $teamId, $since, array $pageIds): array
    {
        // Rewritten 2026-09-25 to eliminate the correlated MAX subquery that
        // was O(N*M) and 504'd the whole page. Use a window function
        // (ROW_NUMBER OVER PARTITION) to grab the latest preceding inbound
        // per outbound message in a single scan. Falls back to the old
        // correlated form on SQLite (tests) which supports both.
        //
        // Also wraps the whole thing in a per-request MySQL statement timeout
        // so even a pathological query plan cannot hang the render — the
        // outer try/catch converts it to a "no data yet" fallback.
        $driver = DB::getDriverName();

        // Hard 3-second MySQL statement timeout on this connection only.
        // No-op on SQLite (test env). Wrapped in try so an old MySQL that
        // doesn't support the syntax degrades gracefully.
        if ($driver === 'mysql') {
            try {
                DB::statement('SET SESSION MAX_EXECUTION_TIME = 3000');
            } catch (Throwable) {
                // Older MySQL, or a misconfigured proxy — proceed anyway.
            }
        }

        // Windowed approach (MySQL 8+): for each outbound message, find the
        // NEWEST preceding inbound in the same conversation in a single pass.
        // Ranking done inside a derived table, then we filter to rn=1.
        if ($driver === 'mysql') {
            $sql = <<<'SQL'
                SELECT sender_type,
                       AVG(latency_seconds) AS avg_time,
                       COUNT(*)             AS pair_count
                FROM (
                    SELECT o.sender_type,
                           TIMESTAMPDIFF(SECOND, i.created_at, o.created_at) AS latency_seconds,
                           ROW_NUMBER() OVER (
                               PARTITION BY o.id
                               ORDER BY i.created_at DESC
                           ) AS rn
                    FROM messages o
                    JOIN conversations c ON o.conversation_id = c.id
                    JOIN messages i ON i.conversation_id = o.conversation_id
                    WHERE o.direction = 'outbound'
                      AND o.created_at >= ?
                      AND i.direction = 'inbound'
                      AND i.created_at < o.created_at
                      AND TIMESTAMPDIFF(SECOND, i.created_at, o.created_at) < 86400
                      AND c.team_id = ?
                      AND c.page_id IN (SELECT * FROM (SELECT ?) x)
                ) ranked
                WHERE rn = 1
                GROUP BY sender_type
            SQL;

            // Explode pageIds into a repeated ? list. Small (< a few dozen).
            $inList = implode(',', array_fill(0, count($pageIds), '?'));
            $sql = str_replace('SELECT * FROM (SELECT ?) x', "SELECT * FROM (SELECT $inList) x", $sql);

            $bindings = array_merge([$since, $teamId], $pageIds);
            $results = collect(DB::select($sql, $bindings))->keyBy('sender_type');
        } else {
            // SQLite (test env) — keep the correlated form; volumes tiny.
            $results = DB::table('messages as outbound')
                ->join('conversations', 'outbound.conversation_id', '=', 'conversations.id')
                ->joinSub(
                    DB::table('messages')
                        ->select('conversation_id', 'created_at')
                        ->where('direction', 'inbound'),
                    'inbound',
                    function ($join) {
                        $join->on('outbound.conversation_id', '=', 'inbound.conversation_id')
                            ->whereColumn('inbound.created_at', '<', 'outbound.created_at');
                    }
                )
                ->where('conversations.team_id', $teamId)
                ->whereIn('conversations.page_id', $pageIds)
                ->where('outbound.direction', 'outbound')
                ->where('outbound.created_at', '>=', $since)
                ->whereRaw('(julianday(outbound.created_at) - julianday(inbound.created_at)) * 86400 < 86400')
                ->whereRaw('inbound.created_at = (SELECT MAX(m2.created_at) FROM messages m2 WHERE m2.conversation_id = outbound.conversation_id AND m2.direction = \'inbound\' AND m2.created_at < outbound.created_at)')
                ->selectRaw('outbound.sender_type, AVG((julianday(outbound.created_at) - julianday(inbound.created_at)) * 86400) as avg_time, COUNT(*) as pair_count')
                ->groupBy('outbound.sender_type')
                ->get()
                ->keyBy('sender_type');
        }

        return [
            'ai_avg'      => isset($results['ai'])   ? (int) round((float) $results['ai']->avg_time)   : null,
            'human_avg'   => isset($results['user']) ? (int) round((float) $results['user']->avg_time) : null,
            'ai_count'    => (int) ($results['ai']->pair_count   ?? 0),
            'human_count' => (int) ($results['user']->pair_count ?? 0),
        ];
    }

    protected function getConversationVolume(int $teamId, $since, array $pageIds): array
    {
        // Collapse 3 queries into 2: single grouped scan for total + by_status, plus
        // one all-time query for ai_paused (has no time window).
        $windowed = Conversation::where('team_id', $teamId)
            ->whereIn('page_id', $pageIds)
            ->where('created_at', '>=', $since)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $total = array_sum($windowed);

        $aiPaused = Conversation::where('team_id', $teamId)
            ->whereIn('page_id', $pageIds)
            ->where('ai_paused', true)->count();

        return [
            'total' => $total,
            'by_status' => $windowed,
            'ai_paused' => $aiPaused,
        ];
    }

    protected function getLeadDistribution(int $teamId, array $pageIds): array
    {
        // Only contacts that have at least one conversation on a currently-connected
        // page — otherwise deactivated-page contacts pollute the funnel.
        return Contact::where('team_id', $teamId)
            ->whereExists(function ($q) use ($pageIds) {
                $q->select(DB::raw(1))
                    ->from('conversations')
                    ->whereColumn('conversations.contact_id', 'contacts.id')
                    ->whereIn('conversations.page_id', $pageIds);
            })
            ->selectRaw('lead_status, count(*) as total, avg(lead_score) as avg_score')
            ->groupBy('lead_status')
            ->get()
            ->keyBy('lead_status')
            ->map(fn ($row) => [
                'count' => $row->total,
                'avg_score' => round($row->avg_score, 1),
            ])
            ->all();
    }

    protected function getPlatformPerformance(int $teamId, $since, array $pageIds): array
    {
        // Single query for all platform stats
        $platformStats = DB::table('conversations')
            ->leftJoin('messages', function ($join) use ($since) {
                $join->on('conversations.id', '=', 'messages.conversation_id')
                    ->where('messages.created_at', '>=', $since);
            })
            ->where('conversations.team_id', $teamId)
            ->whereIn('conversations.page_id', $pageIds)
            ->where('conversations.created_at', '>=', $since)
            ->selectRaw('conversations.platform, COUNT(DISTINCT conversations.id) as conversations, COUNT(messages.id) as messages')
            ->groupBy('conversations.platform')
            ->get()
            ->keyBy('platform');

        // Single query for qualified leads per platform
        $qualifiedLeads = DB::table('contacts')
            ->join('conversations', 'contacts.id', '=', 'conversations.contact_id')
            ->where('contacts.team_id', $teamId)
            ->whereIn('conversations.page_id', $pageIds)
            ->where('contacts.lead_score', '>=', 50)
            ->selectRaw('conversations.platform, COUNT(DISTINCT contacts.id) as qualified_leads')
            ->groupBy('conversations.platform')
            ->pluck('qualified_leads', 'platform');

        $result = [];
        foreach ($platformStats as $platform => $stats) {
            $result[$platform] = [
                'conversations' => $stats->conversations,
                'messages' => $stats->messages,
                'qualified_leads' => $qualifiedLeads[$platform] ?? 0,
            ];
        }

        return $result;
    }

    protected function getTopObjections(int $teamId, $since, array $pageIds): array
    {
        // JOIN instead of whereHas so we scan the (contact_id, created_at) index once
        // rather than running EXISTS(contacts) per lead_score_events row.
        // whereExists on conversations narrows to contacts on currently-active pages.
        $events = DB::table('lead_score_events')
            ->join('contacts', 'lead_score_events.contact_id', '=', 'contacts.id')
            ->where('contacts.team_id', $teamId)
            ->whereExists(function ($q) use ($pageIds) {
                $q->select(DB::raw(1))
                    ->from('conversations')
                    ->whereColumn('conversations.contact_id', 'contacts.id')
                    ->whereIn('conversations.page_id', $pageIds);
            })
            ->where('lead_score_events.created_at', '>=', $since)
            ->where('lead_score_events.score_change', '<', 0)
            ->selectRaw('lead_score_events.reason, count(*) as occurrences, avg(lead_score_events.score_change) as avg_impact')
            ->groupBy('lead_score_events.reason')
            ->orderByDesc('occurrences')
            ->limit(5)
            ->get();

        return $events->map(fn ($e) => [
            'reason' => $e->reason,
            'occurrences' => $e->occurrences,
            'avg_impact' => round($e->avg_impact, 1),
        ])->all();
    }

    protected function getConversionFunnel(int $teamId, array $pageIds): array
    {
        $statusOrder = ['new', 'cold', 'warm', 'hot', 'converted', 'lost'];
        $counts = Contact::where('team_id', $teamId)
            ->whereExists(function ($q) use ($pageIds) {
                $q->select(DB::raw(1))
                    ->from('conversations')
                    ->whereColumn('conversations.contact_id', 'contacts.id')
                    ->whereIn('conversations.page_id', $pageIds);
            })
            ->selectRaw('lead_status, count(*) as total')
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->all();

        $funnel = [];
        foreach ($statusOrder as $status) {
            $funnel[$status] = $counts[$status] ?? 0;
        }

        $totalContacts = array_sum($funnel);
        $converted = $funnel['converted'] ?? 0;

        return [
            'stages' => $funnel,
            'total' => $totalContacts,
            'conversion_rate' => $totalContacts > 0 ? round(($converted / $totalContacts) * 100, 1) : 0,
        ];
    }

    protected function getDailyMessages(int $teamId, $since, array $pageIds): array
    {
        // Drives the "Reach Across Platforms" chart (Inbound/AI/Human) — user reported
        // 7d/14d/30d/90d clicks hang. whereHas('conversation') was EXISTS-scanning every
        // messages row against conversations; JOIN by index is dramatically cheaper.
        $days = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.team_id', $teamId)
            ->whereIn('conversations.page_id', $pageIds)
            ->where('messages.created_at', '>=', $since)
            ->selectRaw('DATE(messages.created_at) as date, messages.sender_type, count(*) as total')
            ->groupBy('date', 'messages.sender_type')
            ->orderBy('date')
            ->get();

        $result = [];
        foreach ($days as $row) {
            $date = $row->date;
            if (! isset($result[$date])) {
                $result[$date] = ['date' => $date, 'ai' => 0, 'human' => 0, 'inbound' => 0];
            }
            if ($row->sender_type === 'ai') {
                $result[$date]['ai'] = $row->total;
            } elseif ($row->sender_type === 'contact') {
                $result[$date]['inbound'] = $row->total;
            } else {
                $result[$date]['human'] = $row->total;
            }
        }

        return array_values($result);
    }
}
