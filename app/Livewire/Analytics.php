<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\LeadScoreEvent;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Analytics extends Component
{
    public string $period = '30'; // days

    public function render()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return view('livewire.analytics', ['data' => null])
                ->layout('layouts.app', ['title' => 'Analytics']);
        }

        $teamId = $team->id;
        $period = (int) $this->period;
        $since = now()->subDays($period)->startOfDay();

        $data = Cache::remember("analytics.{$teamId}.{$period}", 300, function () use ($teamId, $since) {
            return [
                'aiVsHuman' => $this->getAiVsHumanBreakdown($teamId, $since),
                'responseTime' => $this->getResponseTimes($teamId, $since),
                'conversationVolume' => $this->getConversationVolume($teamId, $since),
                'leadDistribution' => $this->getLeadDistribution($teamId),
                'platformPerformance' => $this->getPlatformPerformance($teamId, $since),
                'topObjections' => $this->getTopObjections($teamId, $since),
                'conversionFunnel' => $this->getConversionFunnel($teamId),
                'dailyMessages' => $this->getDailyMessages($teamId, $since),
            ];
        });

        return view('livewire.analytics', ['data' => $data])
            ->layout('layouts.app', ['title' => 'Analytics']);
    }

    protected function getAiVsHumanBreakdown(int $teamId, $since): array
    {
        $counts = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.team_id', $teamId)
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

    protected function getResponseTimes(int $teamId, $since): array
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

    protected function getConversationVolume(int $teamId, $since): array
    {
        $total = Conversation::where('team_id', $teamId)
            ->where('created_at', '>=', $since)->count();

        $byStatus = Conversation::where('team_id', $teamId)
            ->where('created_at', '>=', $since)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $aiPaused = Conversation::where('team_id', $teamId)
            ->where('ai_paused', true)->count();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'ai_paused' => $aiPaused,
        ];
    }

    protected function getLeadDistribution(int $teamId): array
    {
        return Contact::where('team_id', $teamId)
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

    protected function getPlatformPerformance(int $teamId, $since): array
    {
        // Single query for all platform stats
        $platformStats = DB::table('conversations')
            ->leftJoin('messages', function ($join) use ($since) {
                $join->on('conversations.id', '=', 'messages.conversation_id')
                    ->where('messages.created_at', '>=', $since);
            })
            ->where('conversations.team_id', $teamId)
            ->where('conversations.created_at', '>=', $since)
            ->selectRaw('conversations.platform, COUNT(DISTINCT conversations.id) as conversations, COUNT(messages.id) as messages')
            ->groupBy('conversations.platform')
            ->get()
            ->keyBy('platform');

        // Single query for qualified leads per platform
        $qualifiedLeads = DB::table('contacts')
            ->join('conversations', 'contacts.id', '=', 'conversations.contact_id')
            ->where('contacts.team_id', $teamId)
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

    protected function getTopObjections(int $teamId, $since): array
    {
        // Get score events with negative scores (objections)
        $events = LeadScoreEvent::whereHas('contact', fn ($q) => $q->where('team_id', $teamId))
            ->where('created_at', '>=', $since)
            ->where('score_change', '<', 0)
            ->selectRaw('reason, count(*) as occurrences, avg(score_change) as avg_impact')
            ->groupBy('reason')
            ->orderByDesc('occurrences')
            ->limit(5)
            ->get();

        return $events->map(fn ($e) => [
            'reason' => $e->reason,
            'occurrences' => $e->occurrences,
            'avg_impact' => round($e->avg_impact, 1),
        ])->all();
    }

    protected function getConversionFunnel(int $teamId): array
    {
        $statusOrder = ['new', 'cold', 'warm', 'hot', 'converted', 'lost'];
        $counts = Contact::where('team_id', $teamId)
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

    protected function getDailyMessages(int $teamId, $since): array
    {
        $days = Message::whereHas('conversation', fn ($q) => $q->where('team_id', $teamId))
            ->where('messages.created_at', '>=', $since)
            ->selectRaw('DATE(messages.created_at) as date, sender_type, count(*) as total')
            ->groupBy('date', 'sender_type')
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
