<?php

namespace App\Livewire\Settings;

use App\Models\QuickReply;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QuickReplies extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $content = '';

    #[Computed]
    public function quickReplies()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return collect();
        }

        return QuickReply::where('team_id', $team->id)
            ->orderBy('title')
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->content = '';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $team = Auth::user()->currentTeam;
        $reply = QuickReply::where('team_id', $team->id)->findOrFail($id);

        $this->editingId = $id;
        $this->title = $reply->title;
        $this->content = $reply->content;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'   => 'required|string|max:100',
            'content' => 'required|string|max:2000',
        ]);

        $team = Auth::user()->currentTeam;

        if ($this->editingId) {
            $reply = QuickReply::where('team_id', $team->id)->findOrFail($this->editingId);
            $reply->update(['title' => $this->title, 'content' => $this->content]);
        } else {
            QuickReply::create([
                'team_id' => $team->id,
                'title'   => $this->title,
                'content' => $this->content,
            ]);
        }

        $this->showModal = false;
        unset($this->quickReplies);
        session()->flash('success', $this->editingId ? 'Quick reply updated.' : 'Quick reply created.');
    }

    public function delete(int $id): void
    {
        $team = Auth::user()->currentTeam;
        QuickReply::where('team_id', $team->id)->findOrFail($id)->delete();
        unset($this->quickReplies);
        session()->flash('success', 'Quick reply deleted.');
    }

    public function render()
    {
        return view('livewire.settings.quick-replies')
            ->layout('layouts.app', ['title' => 'Quick Replies']);
    }
}
