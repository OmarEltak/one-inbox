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

    public function mount(Campaign $campaign): void
    {
        // Route model-binds {campaign} → Campaign, but we still enforce team
        // ownership here so a signed-in user can't guess another team's id.
        $team = Auth::user()->currentTeam;
        abort_unless($team && $campaign->team_id === $team->id, 404);
        $this->campaign = $campaign;
    }

    /**
     * One GROUP BY query instead of 6 separate COUNTs — matters at 50k-row
     * campaigns on a small VPS. Uses the (campaign_id, status) composite index.
     */
    #[Computed]
    public function counts(): array
    {
        $rows = CampaignRecipient::query()
            ->selectRaw('status, COUNT(*) as n')
            ->where('campaign_id', $this->campaign->id)
            ->groupBy('status')
            ->pluck('n', 'status');

        $get = fn(string $s) => (int) ($rows[$s] ?? 0);
        $sent   = $get(CampaignRecipient::STATUS_SENT);
        $opened = $get(CampaignRecipient::STATUS_OPENED);

        return [
            'total'        => (int) $rows->sum(),
            'pending'      => $get(CampaignRecipient::STATUS_PENDING) + $get('queued') + $get('sending'),
            'sent'         => $sent + $opened, // opened implies sent
            'opened'       => $opened,
            'failed'       => $get(CampaignRecipient::STATUS_FAILED),
            'unsubscribed' => $get(CampaignRecipient::STATUS_UNSUBSCRIBED),
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
