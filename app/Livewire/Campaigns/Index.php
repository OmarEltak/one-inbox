<?php

namespace App\Livewire\Campaigns;

use App\Jobs\ProcessCampaign;
use App\Models\Campaign;
use App\Models\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public ?int $pageId = null;
    public string $messageTemplate = '';
    public string $leadStatus = '';
    public string $languageCode = 'en';
    public int $delaySeconds = 10;

    #[Computed]
    public function campaigns()
    {
        $team = Auth::user()->currentTeam;

        return Campaign::where('team_id', $team->id)
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function whatsappPages()
    {
        $team = Auth::user()->currentTeam;

        return Page::where('team_id', $team->id)
            ->where('platform', 'whatsapp')
            ->where('is_active', true)
            ->get();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'name', 'pageId', 'messageTemplate', 'leadStatus', 'languageCode', 'delaySeconds']);
        $this->languageCode = 'en';
        $this->delaySeconds = 10;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'            => 'required|string|max:100',
            'pageId'          => 'required|integer',
            'messageTemplate' => 'required|string|max:100',
            'languageCode'    => 'required|string|max:10',
        ]);

        $team = Auth::user()->currentTeam;

        $criteria = [
            'page_id'        => $this->pageId,
            'language_code'  => $this->languageCode,
            'delay_seconds'  => $this->delaySeconds,
        ];
        if ($this->leadStatus) {
            $criteria['lead_status'] = $this->leadStatus;
        }

        Campaign::create([
            'team_id'          => $team->id,
            'created_by'       => Auth::id(),
            'name'             => $this->name,
            'type'             => 'promotion',
            'message_template' => $this->messageTemplate,
            'target_criteria'  => $criteria,
            'status'           => 'draft',
        ]);

        $this->showModal = false;
        unset($this->campaigns);
        session()->flash('success', 'Campaign created.');
    }

    public function launch(int $id): void
    {
        $team = Auth::user()->currentTeam;
        $campaign = Campaign::where('team_id', $team->id)->findOrFail($id);

        if (! in_array($campaign->status, ['draft', 'paused'])) {
            return;
        }

        $campaign->update(['status' => 'active']);
        ProcessCampaign::dispatch($campaign->id);
        unset($this->campaigns);
        session()->flash('success', 'Campaign launched — sending in progress.');
    }

    public function pause(int $id): void
    {
        $team = Auth::user()->currentTeam;
        $campaign = Campaign::where('team_id', $team->id)->findOrFail($id);

        if ($campaign->status === 'active') {
            $campaign->update(['status' => 'paused']);
            unset($this->campaigns);
            session()->flash('success', 'Campaign paused. It will stop after the current message.');
        }
    }

    public function delete(int $id): void
    {
        $team = Auth::user()->currentTeam;
        Campaign::where('team_id', $team->id)->findOrFail($id)->delete();
        unset($this->campaigns);
        session()->flash('success', 'Campaign deleted.');
    }

    public function render()
    {
        return view('livewire.campaigns.index')
            ->layout('layouts.app', ['title' => 'Campaigns']);
    }
}
