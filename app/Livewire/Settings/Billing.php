<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Billing extends Component
{
    #[Computed]
    public function team()
    {
        return Auth::user()->currentTeam;
    }

    #[Computed]
    public function currentPlan(): string
    {
        return $this->team?->subscription_plan ?? 'free';
    }

    #[Computed]
    public function plans(): array
    {
        return config('stripe.plans', []);
    }

    #[Computed]
    public function usage(): array
    {
        $team = $this->team;

        if (! $team) {
            return ['ai_credits_used' => 0, 'ai_credits_limit' => 50, 'pages_used' => 0, 'pages_limit' => 1];
        }

        $plan = $this->plans[$this->currentPlan] ?? $this->plans['free'];

        return [
            'ai_credits_used' => $team->ai_credits_used ?? 0,
            'ai_credits_limit' => $plan['ai_credits'],
            'pages_used' => $team->pages()->where('is_active', true)->count(),
            'pages_limit' => $plan['pages'],
        ];
    }

    #[Computed]
    public function invoices()
    {
        $team = $this->team;

        if (! $team || ! $team->hasStripeId()) {
            return collect();
        }

        try {
            return $team->invoices();
        } catch (\Throwable) {
            return collect();
        }
    }

    public function subscribe(string $planKey): mixed
    {
        $team = $this->team;

        if (! $team) {
            return null;
        }

        $plan = $this->plans[$planKey] ?? null;

        if (! $plan || ! $plan['price_id']) {
            return null;
        }

        $checkout = $team->newSubscription('default', $plan['price_id'])
            ->checkout([
                'success_url' => route('settings.billing') . '?checkout=success',
                'cancel_url' => route('settings.billing') . '?checkout=cancelled',
            ]);

        return $this->redirect($checkout->url, navigate: false);
    }

    public function manageSubscription(): mixed
    {
        $team = $this->team;

        if (! $team || ! $team->hasStripeId()) {
            return null;
        }

        $url = $team->billingPortalUrl(route('settings.billing'));

        return $this->redirect($url, navigate: false);
    }

    public function render()
    {
        return view('livewire.settings.billing');
    }
}
