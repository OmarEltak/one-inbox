<?php

namespace App\Livewire\Campaigns;

use App\Jobs\ProcessCampaign;
use App\Models\Campaign;
use App\Models\Conversation;
use App\Models\Page;
use App\Services\WhatsAppCloudPricing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    // Form fields
    public string $name = '';
    public string $platform = 'facebook';
    public ?int $pageId = null;
    public string $messageTemplate = '';
    public string $leadStatus = '';
    public string $languageCode = 'en';
    public int $delaySeconds = 10;

    /** WhatsApp Cloud API template category — retained for backward compatibility with
     *  any existing Cloud-API-backed WhatsApp integrations. For the current Wuzapi
     *  (personal WhatsApp Web session) path, the message is free text and this field
     *  is ignored downstream. */
    public string $messageCategory = 'marketing';

    /** 'now' (default — dispatch immediately on save) or 'schedule' (hold until
     *  scheduled_at). Kept as string for radio-button binding. */
    public string $sendMode = 'now';

    /** ISO datetime string bound from an <input type="datetime-local">. When
     *  sendMode = 'schedule', persisted to campaigns.scheduled_at; the campaign
     *  stays in status='scheduled' until the DispatchScheduledCampaigns command
     *  flips it to 'active' at the scheduled time. Capped to now + 30 days. */
    public ?string $scheduledAt = null;

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
    public function pagesForPlatform()
    {
        $team = Auth::user()->currentTeam;

        return Page::where('team_id', $team->id)
            ->where('platform', $this->platform)
            ->where('is_active', true)
            ->get();
    }

    public function updatedPlatform(): void
    {
        $this->pageId = null;
        unset($this->pagesForPlatform, $this->audiencePhones, $this->whatsappCostEstimate);
    }

    public function updatedPageId(): void
    {
        unset($this->audiencePhones, $this->whatsappCostEstimate);
    }

    public function updatedLeadStatus(): void
    {
        unset($this->audiencePhones, $this->whatsappCostEstimate);
    }

    public function updatedMessageCategory(): void
    {
        unset($this->whatsappCostEstimate);
    }

    /**
     * Resolve the phone numbers we'd actually send a WhatsApp campaign to.
     * Source: distinct platform_conversation_id (the recipient's WA number) on
     * conversations belonging to the selected page, optionally filtered by the
     * recipient contact's lead_status.
     */
    #[Computed]
    public function audiencePhones(): array
    {
        if (! $this->pageId || $this->platform !== 'whatsapp') {
            return [];
        }

        $team = Auth::user()->currentTeam;
        if (! $team) {
            return [];
        }

        $q = Conversation::query()
            ->where('team_id', $team->id)
            ->where('page_id', $this->pageId)
            ->where('platform', 'whatsapp')
            ->whereNotNull('platform_conversation_id');

        if ($this->leadStatus) {
            $q->whereHas('contact', fn ($cq) => $cq->where('lead_status', $this->leadStatus));
        }

        return $q->distinct()
            ->pluck('platform_conversation_id')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Live cost estimate for the WhatsApp campaign. Recomputed when platform,
     * pageId, leadStatus, or messageCategory changes.
     */
    #[Computed]
    public function whatsappCostEstimate(): array
    {
        if ($this->platform !== 'whatsapp' || ! $this->pageId) {
            return [
                'total_usd' => 0.0,
                'recipient_count' => 0,
                'breakdown' => [],
                'unknown_country' => 0,
                'category' => $this->messageCategory,
                'rates_last_verified' => WhatsAppCloudPricing::RATES_LAST_VERIFIED,
            ];
        }

        return WhatsAppCloudPricing::estimate($this->audiencePhones, $this->messageCategory);
    }

    public function goToWizard(): void
    {
        if ($this->platform === 'whatsapp') {
            $this->redirect(route('campaigns.whatsapp.new'), navigate: true);
            return;
        }
        if ($this->platform === 'email') {
            if (\Illuminate\Support\Facades\Route::has('campaigns.email.new')) {
                $this->redirect(route('campaigns.email.new'), navigate: true);
                return;
            }
            // Fallback path (email wizard was mounted at this URL originally).
            $this->redirect('/campaigns/email/new', navigate: true);
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'name', 'pageId', 'messageTemplate', 'leadStatus', 'languageCode', 'delaySeconds', 'platform', 'messageCategory', 'sendMode', 'scheduledAt']);
        $this->platform = 'facebook';
        $this->languageCode = 'en';
        $this->delaySeconds = 10;
        $this->messageCategory = 'marketing';
        $this->sendMode = 'now';
        $this->scheduledAt = null;
        $this->showModal = true;
        unset($this->audiencePhones, $this->whatsappCostEstimate);
    }

    public function save(): void
    {
        $team = Auth::user()->currentTeam;

        // WhatsApp via Wuzapi is now the default path — the message is plain text
        // (no template approval). We keep messageCategory available for Cloud-API
        // integrations if the operator later flips a page to Cloud API, but it is
        // not required at campaign-create time anymore.
        $rules = [
            'name'            => 'required|string|max:100',
            'platform'        => 'required|string|in:facebook,instagram,telegram,whatsapp,email',
            'pageId'          => [
                'required',
                'integer',
                Rule::exists('pages', 'id')
                    ->where('team_id', $team->id)
                    ->where('platform', $this->platform)
                    ->where('is_active', true),
            ],
            'messageTemplate' => 'required|string|max:4096',
            'sendMode'        => 'required|in:now,schedule',
            'scheduledAt'     => [
                Rule::requiredIf(fn () => $this->sendMode === 'schedule'),
                'nullable',
                'date',
                'after:' . now()->addMinute()->toIso8601String(),
                'before:' . now()->addDays(30)->toIso8601String(),
            ],
        ];

        $this->validate($rules);

        $criteria = [
            'page_id'       => $this->pageId,
            'delay_seconds' => $this->delaySeconds,
        ];

        if ($this->leadStatus) {
            $criteria['lead_status'] = $this->leadStatus;
        }

        // Snapshot the Meta 24h-window advisory into the criteria so ProcessCampaign
        // knows the operator was told about it. Load-bearing for post-run reports:
        // "we skipped N because Meta blocks stale contacts on this platform".
        if (in_array($this->platform, ['facebook', 'instagram'], true)) {
            $criteria['meta_24h_filter'] = true;
        }

        $scheduledAt = null;
        if ($this->sendMode === 'schedule' && $this->scheduledAt) {
            $scheduledAt = \Carbon\Carbon::parse($this->scheduledAt);
        }

        $campaign = Campaign::create([
            'team_id'          => $team->id,
            'created_by'       => Auth::id(),
            'name'             => $this->name,
            'type'             => 'promotion',
            'platform'         => $this->platform,
            'message_template' => $this->messageTemplate,
            'target_criteria'  => $criteria,
            'status'           => $scheduledAt ? 'scheduled' : 'draft',
            'scheduled_at'     => $scheduledAt,
        ]);

        $this->showModal = false;
        unset($this->campaigns);
        session()->flash(
            'success',
            $scheduledAt
                ? "Campaign scheduled for {$scheduledAt->format('M j, Y g:ia')}."
                : 'Campaign created — click Launch when ready.'
        );
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
