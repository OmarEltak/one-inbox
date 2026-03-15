<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AiSettings extends Component
{
    public bool $aiEnabled = true;

    public function mount(): void
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            $this->redirectRoute('dashboard');

            return;
        }

        $this->aiEnabled = $team->ai_enabled;
    }

    public function toggleAi(): void
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return;
        }

        $team->toggleAi(! $this->aiEnabled);
        $this->aiEnabled = ! $this->aiEnabled;

        $this->dispatch('ai-toggled', enabled: $this->aiEnabled);
    }

    public function render()
    {
        return view('livewire.settings.ai-settings')
            ->layout('layouts.app', ['title' => 'AI Settings']);
    }
}
