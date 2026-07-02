<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        if ($team && ! $team->pages()->where('is_active', true)->exists()) {
            $this->redirectRoute('connections.index');
        }
    }

    public function render()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return view('livewire.dashboard', ['stats' => null])
                ->layout('layouts.app', ['title' => 'Dashboard']);
        }

        $teamId = $team->id;

        $stats = Cache::remember("dashboard.{$teamId}", 300, function () use ($teamId) {
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();

            // Scope all conversation/message stats to active pages only so that
            // disconnected pages don't continue appearing in the dashboard numbers.
            $activePageIds = \App\Models\Page::where('team_id', $teamId)
                ->where('is_active', true)
                ->pluck('id');

            // Combined conversation stats in a single query
            $convStats = Conversation::where('team_id', $teamId)
                ->whereIn('page_id', $activePageIds)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_week,
                    SUM(CASE WHEN unread_count > 0 THEN 1 ELSE 0 END) as unread
                ", [$today, $weekStart])
                ->first();

            $totalConversations = (int) $convStats->total;
            $newConversationsToday = (int) $convStats->today;
            $newConversationsWeek = (int) $convStats->this_week;
            $unreadCount = (int) $convStats->unread;

            // Combined message stats in a single query (join instead of whereHas)
            $msgStats = DB::table('messages')
                ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->where('conversations.team_id', $teamId)
                ->whereIn('conversations.page_id', $activePageIds)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN messages.created_at >= ? THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN messages.sender_type = 'ai' THEN 1 ELSE 0 END) as ai,
                    SUM(CASE WHEN messages.sender_type = 'user' AND messages.direction = 'outbound' THEN 1 ELSE 0 END) as human
                ", [$today])
                ->first();

            $totalMessages = (int) $msgStats->total;
            $messagesToday = (int) $msgStats->today;
            $aiMessages = (int) $msgStats->ai;
            $humanMessages = (int) $msgStats->human;

            // Materialize the set of contact IDs that actually own conversations on active
            // pages ONCE. Three downstream contact queries reuse it as a whereIn — replacing
            // three N×M `whereHas` EXISTS subqueries (each a full scan without a proper index)
            // with a single distinct pluck + three cheap whereIn lookups.
            $activeContactIds = Conversation::where('team_id', $teamId)
                ->whereIn('page_id', $activePageIds)
                ->whereNotNull('contact_id')
                ->distinct()
                ->pluck('contact_id')
                ->all();

            $contactStats = Contact::where('team_id', $teamId)
                ->whereIn('id', $activeContactIds)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_week
                ", [$weekStart])
                ->first();

            $totalContacts = (int) $contactStats->total;
            $newContactsWeek = (int) $contactStats->new_week;

            // Platform breakdown — only active pages
            $platformStats = Conversation::where('team_id', $teamId)
                ->whereIn('page_id', $activePageIds)
                ->selectRaw('platform, count(*) as total')
                ->groupBy('platform')
                ->pluck('total', 'platform')
                ->all();

            $leadStats = Contact::where('team_id', $teamId)
                ->whereIn('id', $activeContactIds)
                ->selectRaw('lead_status, count(*) as total')
                ->groupBy('lead_status')
                ->pluck('total', 'lead_status')
                ->all();

            $hotLeads = Contact::where('team_id', $teamId)
                ->whereIn('id', $activeContactIds)
                ->where('lead_score', '>=', 70)
                ->orderByDesc('lead_score')
                ->limit(5)
                ->get();

            // Recent conversations — only active pages
            $recentConversations = Conversation::where('team_id', $teamId)
                ->whereIn('page_id', $activePageIds)
                ->with('contact')
                ->orderByDesc('last_message_at')
                ->limit(5)
                ->get();

            // Connected pages count
            $connectedPages = \App\Models\Team::find($teamId)->pages()->where('is_active', true)->count();

            return compact(
                'totalConversations', 'newConversationsToday', 'newConversationsWeek',
                'totalMessages', 'messagesToday',
                'totalContacts', 'newContactsWeek',
                'unreadCount',
                'aiMessages', 'humanMessages',
                'platformStats', 'leadStats',
                'hotLeads', 'recentConversations',
                'connectedPages'
            );
        });

        return view('livewire.dashboard', ['stats' => $stats])
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
