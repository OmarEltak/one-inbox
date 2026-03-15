<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->currentTeam) {
            return $next($request);
        }

        $team = $user->currentTeam;
        $planKey = $team->subscription_plan ?? 'free';
        $plan = config("stripe.plans.{$planKey}", config('stripe.plans.free'));

        // Check if subscription is past_due — allow read access but flash warning
        if ($team->subscription_status === 'past_due') {
            session()->flash('billing_warning', 'Your payment has failed. Please update your billing details to avoid service interruption.');
        }

        // Store plan limits on the request for downstream use
        $request->merge([
            '_plan_limits' => [
                'ai_credits' => $plan['ai_credits'],
                'pages' => $plan['pages'],
            ],
        ]);

        return $next($request);
    }

    /**
     * Check if the team can connect more pages.
     */
    public static function canConnectPage($team): bool
    {
        $planKey = $team->subscription_plan ?? 'free';
        $plan = config("stripe.plans.{$planKey}", config('stripe.plans.free'));

        if ($plan['pages'] === -1) {
            return true; // unlimited
        }

        $currentPages = $team->pages()->where('is_active', true)->count();

        return $currentPages < $plan['pages'];
    }

    /**
     * Check if the team has AI credits remaining.
     */
    public static function hasAiCredits($team): bool
    {
        $planKey = $team->subscription_plan ?? 'free';
        $plan = config("stripe.plans.{$planKey}", config('stripe.plans.free'));

        if ($plan['ai_credits'] === -1) {
            return true; // unlimited
        }

        return ($team->ai_credits_used ?? 0) < $plan['ai_credits'];
    }
}
