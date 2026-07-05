<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Wire Transfer Payment — OT1-Pro')]
class PayWire extends Component
{
    use WithFileUploads;

    public string $full_name  = '';
    public string $email      = '';
    public string $plan       = 'starter';
    public string $bank_name  = '';
    public string $bank_country = '';
    public string $txid       = '';
    public $receipt           = null;
    public bool $submitted    = false;

    public function mount(): void
    {
        $plan = request()->query('plan', 'starter');
        $this->plan = in_array($plan, ['starter', 'pro'], true) ? $plan : 'starter';

        if (auth()->check()) {
            $this->email     = auth()->user()->email;
            $this->full_name = auth()->user()->name;
        }
    }

    public function submit(): void
    {
        $this->validate([
            'full_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'plan'         => ['required', 'in:starter,pro'],
            'bank_name'    => ['required', 'string', 'max:255'],
            'bank_country' => ['required', 'string', 'max:255'],
            'txid'         => ['nullable', 'string', 'max:255'],
            'receipt'      => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        $path = $this->receipt->store('payment-receipts', 'local');

        PaymentRequest::create([
            'team_id'      => auth()->user()?->currentTeam?->id,
            'full_name'    => $this->full_name,
            'email'        => $this->email,
            'plan'         => $this->plan,
            'bank_name'    => $this->bank_name,
            'bank_country' => $this->bank_country,
            'txid'         => $this->txid ?: null,
            'receipt_path' => $path,
        ]);

        $planLabel = $this->plan === 'pro' ? 'Pro ($79/mo)' : 'Starter ($29/mo)';

        try {
            Mail::raw(
                "New wire transfer payment request:\n\n" .
                "Name: {$this->full_name}\n" .
                "Email: {$this->email}\n" .
                "Plan: {$planLabel}\n" .
                "Bank: {$this->bank_name} ({$this->bank_country})\n" .
                "TXID: " . ($this->txid ?: 'Not provided') . "\n\n" .
                'Review at: ' . route('super-admin.payment-requests'),
                fn ($m) => $m
                    ->to(config('mail.admin_address', 'it@mishkahu.com'))
                    ->subject("Wire Transfer Request — {$this->full_name} / {$planLabel}")
            );
        } catch (\Throwable $e) {
            Log::warning('PayWire: admin mail failed', ['error' => $e->getMessage()]);
        }

        $this->submitted = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pay-wire')
            ->layout('components.layouts.marketing');
    }
}
