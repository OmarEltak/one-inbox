<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\OnboardingRequest;
use App\Models\Page;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OnboardingRequests extends Component
{
    public string $statusFilter = 'open'; // open | pending | in_progress | completed | rejected | all

    /** Per-row UI state: page picker selection keyed by request_id */
    public array $selectedPageByRequest = [];

    /** Per-row UI state: rejection reason keyed by request_id */
    public array $rejectionReasonByRequest = [];

    #[Computed]
    public function requests()
    {
        $query = OnboardingRequest::query()
            ->with(['team:id,name', 'requestedBy:id,name,email', 'assignedAdmin:id,name', 'resultingPage:id,name,platform']);

        $query = match ($this->statusFilter) {
            'open'        => $query->whereIn('status', [OnboardingRequest::STATUS_PENDING, OnboardingRequest::STATUS_IN_PROGRESS]),
            'pending'     => $query->where('status', OnboardingRequest::STATUS_PENDING),
            'in_progress' => $query->where('status', OnboardingRequest::STATUS_IN_PROGRESS),
            'completed'   => $query->where('status', OnboardingRequest::STATUS_COMPLETED),
            'rejected'    => $query->where('status', OnboardingRequest::STATUS_REJECTED),
            default       => $query,
        };

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Pages currently owned by super-admin (holding) teams — candidates the admin
     * just connected via their own OAuth and now wants to hand off to the customer.
     */
    #[Computed]
    public function assignablePages()
    {
        $holdingTeamIds = Team::query()
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', true))
            ->pluck('id');

        return Page::query()
            ->whereIn('team_id', $holdingTeamIds)
            ->where('is_active', true)
            ->orderBy('platform')
            ->orderBy('name')
            ->get(['id', 'name', 'platform', 'team_id']);
    }

    public function startReview(int $requestId): void
    {
        $req = OnboardingRequest::find($requestId);
        if (! $req || ! in_array($req->status, [OnboardingRequest::STATUS_PENDING, OnboardingRequest::STATUS_IN_PROGRESS], true)) {
            return;
        }

        $req->update([
            'status'                 => OnboardingRequest::STATUS_IN_PROGRESS,
            'assigned_admin_user_id' => Auth::id(),
        ]);

        session()->flash('success', "Marked request #{$req->id} as in progress.");
    }

    public function complete(int $requestId): void
    {
        $req = OnboardingRequest::find($requestId);
        if (! $req || ! $req->isOpen()) {
            return;
        }

        $pageId = (int) ($this->selectedPageByRequest[$requestId] ?? 0);
        if ($pageId <= 0) {
            session()->flash('error', 'Select a page to assign before completing the request.');
            return;
        }

        $page = Page::find($pageId);
        if (! $page) {
            return;
        }

        $targetTeam = Team::with('owner')->find($req->team_id);
        if (! $targetTeam) {
            return;
        }

        $sourceTeamId = $page->team_id;

        DB::transaction(function () use ($page, $req, $targetTeam, $sourceTeamId) {
            // Transfer the page to the customer team and cascade.
            $page->update(['team_id' => $targetTeam->id]);
            Conversation::where('page_id', $page->id)->update(['team_id' => $targetTeam->id]);
            $contactIds = Conversation::where('page_id', $page->id)->pluck('contact_id')->unique()->filter()->all();
            if (! empty($contactIds)) {
                Contact::whereIn('id', $contactIds)
                    ->where('team_id', $sourceTeamId)
                    ->update(['team_id' => $targetTeam->id]);
            }

            $req->update([
                'status'                 => OnboardingRequest::STATUS_COMPLETED,
                'assigned_admin_user_id' => $req->assigned_admin_user_id ?? Auth::id(),
                'resulting_page_id'      => $page->id,
                'completed_at'           => now(),
            ]);
        });

        Team::find($sourceTeamId)?->clearActivePagesCache();
        $targetTeam->clearActivePagesCache();

        unset($this->selectedPageByRequest[$requestId]);

        session()->flash('success', "Assigned \"{$page->name}\" to {$targetTeam->name} and completed request #{$req->id}.");
    }

    public function reject(int $requestId): void
    {
        $req = OnboardingRequest::find($requestId);
        if (! $req || ! $req->isOpen()) {
            return;
        }

        $reason = trim($this->rejectionReasonByRequest[$requestId] ?? '');
        if ($reason === '') {
            session()->flash('error', 'Provide a rejection reason so the customer understands why.');
            return;
        }

        $req->update([
            'status'                 => OnboardingRequest::STATUS_REJECTED,
            'assigned_admin_user_id' => $req->assigned_admin_user_id ?? Auth::id(),
            'admin_notes'            => $reason,
            'completed_at'           => now(),
        ]);

        unset($this->rejectionReasonByRequest[$requestId]);

        session()->flash('success', "Rejected request #{$req->id}.");
    }

    public function render()
    {
        return view('livewire.super-admin.onboarding-requests')
            ->layout('layouts.app', ['title' => 'Onboarding Requests']);
    }
}
