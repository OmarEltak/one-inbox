<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    public function handleCustomerSubscriptionCreated(array $payload): void
    {
        $this->updateTeamSubscription($payload);
    }

    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $this->updateTeamSubscription($payload);
    }

    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $team = Team::where('stripe_id', $stripeId)->first();

        if (! $team) {
            return;
        }

        $team->update([
            'subscription_plan' => 'free',
            'subscription_status' => 'cancelled',
        ]);

        // Reset limits to free plan
        $freePlan = config('stripe.plans.free');
        $team->update([
            'ai_credits_limit' => $freePlan['ai_credits'] ?? 50,
        ]);

        Log::info("Team {$team->id} subscription cancelled, downgraded to free");
    }

    public function handleInvoicePaymentFailed(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $team = Team::where('stripe_id', $stripeId)->first();

        if ($team) {
            $team->update(['subscription_status' => 'past_due']);
            Log::warning("Team {$team->id} payment failed, marked as past_due");
        }
    }

    protected function updateTeamSubscription(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? [];
        $stripeId = $subscription['customer'] ?? null;

        if (! $stripeId) {
            return;
        }

        $team = Team::where('stripe_id', $stripeId)->first();

        if (! $team) {
            return;
        }

        $stripePriceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $status = $subscription['status'] ?? 'active';

        // Find matching plan by price_id
        $planKey = 'free';
        foreach (config('stripe.plans', []) as $key => $plan) {
            if ($plan['price_id'] && $plan['price_id'] === $stripePriceId) {
                $planKey = $key;
                break;
            }
        }

        $plan = config("stripe.plans.{$planKey}");

        $team->update([
            'subscription_plan' => $planKey,
            'subscription_status' => $status,
            'ai_credits_limit' => $plan['ai_credits'] ?? 50,
        ]);

        Log::info("Team {$team->id} subscription updated to {$planKey} ({$status})");
    }
}
