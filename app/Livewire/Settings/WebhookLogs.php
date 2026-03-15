<?php

namespace App\Livewire\Settings;

use App\Models\WebhookLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class WebhookLogs extends Component
{
    use WithPagination;

    #[Url]
    public string $platform = '';

    #[Url]
    public string $status = '';

    public ?int $viewingId = null;

    #[Computed]
    public function log(): ?WebhookLog
    {
        if (! $this->viewingId) {
            return null;
        }

        $team = Auth::user()->currentTeam;

        return WebhookLog::where('team_id', $team->id)->find($this->viewingId);
    }

    public function updatedPlatform(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function viewLog(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeLog(): void
    {
        $this->viewingId = null;
    }

    public function render()
    {
        $team = Auth::user()->currentTeam;

        $query = WebhookLog::where('team_id', $team->id)
            ->orderByDesc('created_at');

        if ($this->platform) {
            $query->where('platform', $this->platform);
        }

        if ($this->status === 'processed') {
            $query->where('processed', true);
        } elseif ($this->status === 'failed') {
            $query->where('processed', false)->whereNotNull('error');
        } elseif ($this->status === 'pending') {
            $query->where('processed', false)->whereNull('error');
        }

        $logs = $query->paginate(25);

        return view('livewire.settings.webhook-logs', ['logs' => $logs])
            ->layout('layouts.app', ['title' => 'Webhook Logs']);
    }
}
