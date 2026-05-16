<?php

declare(strict_types=1);

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Campaign $campaign;

    public string $filter = 'all';

    public function mount(int $campaign): void
    {
        $team = Auth::user()->currentTeam;
        $this->campaign = Campaign::where('team_id', $team->id)->findOrFail($campaign);
    }

    #[Computed]
    public function counts(): array
    {
        $base = CampaignRecipient::where('campaign_id', $this->campaign->id);
        return [
            'total'        => (clone $base)->count(),
            'pending'      => (clone $base)->where('status', CampaignRecipient::STATUS_PENDING)->count(),
            'sent'         => (clone $base)->whereIn('status', [
                CampaignRecipient::STATUS_SENT,
                CampaignRecipient::STATUS_OPENED,
            ])->count(),
            'opened'       => (clone $base)->where('status', CampaignRecipient::STATUS_OPENED)->count(),
            'failed'       => (clone $base)->where('status', CampaignRecipient::STATUS_FAILED)->count(),
            'unsubscribed' => (clone $base)->where('status', CampaignRecipient::STATUS_UNSUBSCRIBED)->count(),
        ];
    }

    public function pause(): void
    {
        if ($this->campaign->status === Campaign::STATUS_ACTIVE) {
            $this->campaign->update(['status' => Campaign::STATUS_PAUSED]);
            $this->campaign->refresh();
        }
    }

    public function resume(): void
    {
        if ($this->campaign->status === Campaign::STATUS_PAUSED) {
            $this->campaign->update(['status' => Campaign::STATUS_ACTIVE]);
            $this->campaign->refresh();
        }
    }

    public function retryFailed(): void
    {
        CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('status', CampaignRecipient::STATUS_FAILED)
            ->update([
                'status'       => CampaignRecipient::STATUS_PENDING,
                'attempts'     => 0,
                'scheduled_at' => now(),
                'failed_at'    => null,
            ]);
        $this->campaign->refresh();
    }

    public function render()
    {
        $query = CampaignRecipient::where('campaign_id', $this->campaign->id);
        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }
        $recipients = $query->orderByDesc('id')->paginate(50);

        return view('livewire.campaigns.show', [
            'recipients' => $recipients,
        ])->layout('layouts.app', ['title' => $this->campaign->name]);
    }
}
