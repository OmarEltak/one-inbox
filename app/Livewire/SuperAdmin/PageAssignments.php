<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Page;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PageAssignments extends Component
{
    /**
     * Map of page_id => target_team_id from the dropdowns.
     *
     * @var array<int, int|null>
     */
    public array $assignments = [];

    public string $search = '';
    public string $platformFilter = '';

    public function mount(): void
    {
        foreach ($this->allPages() as $page) {
            $this->assignments[$page->id] = $page->team_id;
        }
    }

    #[Computed]
    public function holdingTeamIds(): array
    {
        return Team::query()
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', true))
            ->pluck('id')
            ->all();
    }

    #[Computed]
    public function customerTeams()
    {
        return Team::query()
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function pages()
    {
        $query = Page::query()
            ->with(['team:id,name', 'connectedAccount:id,name,platform']);

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->platformFilter !== '') {
            $query->where('platform', $this->platformFilter);
        }

        return $query->orderBy('platform')->orderBy('name')->get();
    }

    public function allPages()
    {
        return Page::query()->select('id', 'team_id')->get();
    }

    public function assign(int $pageId): void
    {
        $page = Page::find($pageId);
        if (! $page) {
            return;
        }

        $targetTeamId = (int) ($this->assignments[$pageId] ?? 0);
        if ($targetTeamId <= 0 || $targetTeamId === $page->team_id) {
            return;
        }

        $targetTeam = Team::with('owner')->find($targetTeamId);
        if (! $targetTeam) {
            return;
        }

        $sourceTeamId = $page->team_id;

        DB::transaction(function () use ($page, $targetTeamId, $sourceTeamId) {
            $page->update(['team_id' => $targetTeamId]);

            Conversation::where('page_id', $page->id)->update(['team_id' => $targetTeamId]);

            $contactIds = Conversation::where('page_id', $page->id)->pluck('contact_id')->unique()->filter()->all();
            if (! empty($contactIds)) {
                Contact::whereIn('id', $contactIds)
                    ->where('team_id', $sourceTeamId)
                    ->update(['team_id' => $targetTeamId]);
            }
        });

        Team::find($sourceTeamId)?->clearActivePagesCache();
        $targetTeam->clearActivePagesCache();

        session()->flash('success', "Moved \"{$page->name}\" to {$targetTeam->name}.");
    }

    public function bringToMyTeam(int $pageId): void
    {
        $user = Auth::user();
        $myTeamId = $user->current_team_id;
        if (! $myTeamId) {
            return;
        }

        $this->assignments[$pageId] = $myTeamId;
        $this->assign($pageId);
    }

    public function render()
    {
        return view('livewire.super-admin.page-assignments')
            ->layout('layouts.app', ['title' => 'Page Assignments']);
    }
}
