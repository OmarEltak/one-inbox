<?php

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
 */
class Analytics extends Component
{
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

    public function render()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return view('livewire.analytics', ['data' => null, 'activePages' => collect()])
                ->layout('layouts.app', ['title' => 'Analytics']);
        }

        $activePages = $this->activePages;
        $activeIds = $activePages->pluck('id')->all();

        // Drop any selected IDs that no longer belong to an active page — happens
        // if a page is deactivated between mount and render (e.g. cross-tab).
        $pageIds = array_values(array_intersect($this->selectedPageIds, $activeIds));

        if (empty($pageIds)) {
            // No active connections at all → no data to aggregate. The sidebar
            // gate normally prevents reaching this route, but render defensively.
            return view('livewire.analytics', [
                'data' => null,
                'activePages' => $activePages,
                'noConnections' => true,
            ])->layout('layouts.app', ['title' => 'Analytics']);
        }

        $teamId = $team->id;
        $period = (int) $this->period;
        $since = now()->subDays($period)->startOfDay();

        // Cache key includes the selection so switching pages doesn't serve stale aggregates.
        $selectionKey = md5(implode(',', $pageIds));
        $cacheKey = "analytics.{$teamId}.{$period}.{$selectionKey}";

        $data = Cache::remember($cacheKey, 1800, function () use ($teamId, $since, $pageIds) {
            return [
                'aiVsHuman' => $this->getAiVsHumanBreakdown($teamId, $since, $pageIds),
                'responseTime' => $this->getResponseTimes($teamId, $since, $pageIds),
                'conversationVolume' => $this->getConversationVolume($teamId, $since, $pageIds),
                'leadDistribution' => $this->getLeadDistribution($teamId, $pageIds),
                'platformPerformance' => $this->getPlatformPerformance($teamId, $since, $pageIds),
                'topObjections' => $this->getTopObjections($teamId, $since, $pageIds),
                'conversionFunnel' => $this->getConversionFunnel($teamId, $pageIds),
                'dailyMessages' => $this->getDailyMessages($teamId, $since, $pageIds),
            ];
        });

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
        // Use a single query with self-join to find inbound→outbound message pairs
        // For each outbound message, find the closest preceding inbound message in the same conversation
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
            ->whereRaw(
                DB::getDriverName() === 'sqlite'
                    ? '(julianday(outbound.created_at) - julianday(inbound.created_at)) * 86400 < 86400'
                    : 'TIMESTAMPDIFF(SECOND, inbound.created_at, outbound.created_at) < 86400'
            )
            ->whereRaw('inbound.created_at = (SELECT MAX(m2.created_at) FROM messages m2 WHERE m2.conversation_id = outbound.conversation_id AND m2.direction = \'inbound\' AND m2.created_at < outbound.created_at)')
            ->selectRaw(
                DB::getDriverName() === 'sqlite'
                    ? 'outbound.sender_type, AVG((julianday(outbound.created_at) - julianday(inbound.created_at)) * 86400) as avg_time, COUNT(*) as pair_count'
                    : 'outbound.sender_type, AVG(TIMESTAMPDIFF(SECOND, inbound.created_at, outbound.created_at)) as avg_time, COUNT(*) as pair_count'
            )
            ->groupBy('outbound.sender_type')
            ->get()
            ->keyBy('sender_type');

        return [
            'ai_avg' => isset($results['ai']) ? round($results['ai']->avg_time) : null,
            'human_avg' => isset($results['user']) ? round($results['user']->avg_time) : null,
            'ai_count' => $results['ai']->pair_count ?? 0,
            'human_count' => $results['user']->pair_count ?? 0,
        ];
    }

    protected function getConversationVolume(int $teamId, $since, array $pageIds): array
    {
        $total = Conversation::where('team_id', $teamId)
            ->whereIn('page_id', $pageIds)
            ->where('created_at', '>=', $since)->count();

        $byStatus = Conversation::where('team_id', $teamId)
            ->whereIn('page_id', $pageIds)
            ->where('created_at', '>=', $since)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $aiPaused = Conversation::where('team_id', $teamId)
            ->whereIn('page_id', $pageIds)
            ->where('ai_paused', true)->count();

        return [
            'total' => $total,
            'by_status' => $byStatus,
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
