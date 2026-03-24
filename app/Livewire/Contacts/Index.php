<?php

namespace App\Livewire\Contacts;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $sortBy = 'lead_score';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public ?int $selectedContactId = null;

    public bool $showContactModal = false;

    public function selectContact(int $id): void
    {
        $this->selectedContactId = $id;
        $this->showContactModal = true;
    }

    public function closeContact(): void
    {
        $this->showContactModal = false;
        $this->selectedContactId = null;
    }

    public function setLeadStatus(int $id, string $status): void
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return;
        }

        $contact = Contact::where('team_id', $team->id)->findOrFail($id);
        $contact->update(['lead_status' => $status]);
        unset($this->selectedContact);
    }

    #[Computed]
    public function selectedContact(): ?Contact
    {
        if (! $this->selectedContactId) {
            return null;
        }

        return Contact::with(['scoreEvents' => fn ($q) => $q->latest(), 'platforms', 'conversations'])
            ->find($this->selectedContactId);
    }

    #[Computed]
    public function contacts()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return collect();
        }

        $query = Contact::with(['platforms', 'conversations' => fn ($q) => $q->with('page:id,name,platform')->select('id', 'contact_id', 'page_id', 'platform')])
            ->where('team_id', $team->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('lead_status', $this->statusFilter);
        }

        return $query->orderByDesc($this->sortBy)->paginate(25);
    }

    public function render()
    {
        return view('livewire.contacts.index')
            ->layout('layouts.app', ['title' => 'Contacts']);
    }
}
