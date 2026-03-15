<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public function mount(): void
    {
        // If user already has a team, redirect to dashboard
        if (Auth::user()->current_team_id) {
            $this->redirectRoute('dashboard');
        }
    }

    public function createTeam(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        $team = Team::create([
            'name' => $this->name,
            'slug' => Str::slug($this->name) . '-' . Str::random(6),
            'owner_id' => $user->id,
        ]);

        $team->members()->attach($user->id, ['role' => 'admin']);
        $user->switchTeam($team);

        $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.teams.create')
            ->layout('layouts.auth', ['title' => 'Create Team']);
    }
}
