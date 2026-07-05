<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PaymentRequests extends Component
{
    public string $statusFilter = 'pending';
    public ?int $notesId        = null;
    public string $notesText    = '';

    #[Computed]
    public function requests()
    {
        return PaymentRequest::query()
            ->with('team')
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->get();
    }

    public function approve(int $id): void
    {
        $req = PaymentRequest::findOrFail($id);
        $req->update(['status' => 'approved']);

        if ($req->team) {
            $req->team->update(['subscription_plan' => $req->plan]);
        }

        unset($this->requests);
        $this->dispatch('notify', message: "Approved — {$req->full_name}");
    }

    public function reject(int $id): void
    {
        PaymentRequest::findOrFail($id)->update(['status' => 'rejected']);
        unset($this->requests);
        $this->dispatch('notify', message: 'Request rejected.');
    }

    public function openNotes(int $id): void
    {
        $req = PaymentRequest::findOrFail($id);
        $this->notesId   = $id;
        $this->notesText = $req->notes ?? '';
    }

    public function saveNotes(): void
    {
        if (! $this->notesId) {
            return;
        }
        PaymentRequest::findOrFail($this->notesId)->update(['notes' => $this->notesText]);
        $this->notesId   = null;
        $this->notesText = '';
        unset($this->requests);
    }

    public function receiptUrl(string $path): string
    {
        return Storage::disk('local')->url($path);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.payment-requests');
    }
}
