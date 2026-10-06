<?php

namespace App\Http\Middleware;

use App\Services\Billing\AiCredits;
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
        $plan = config("plans.plans.{$planKey}", config('plans.plans.free'));

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
        $plan = config("plans.plans.{$planKey}", config('plans.plans.free'));

        if ($plan['pages'] === -1) {
            return true; // unlimited
        }

        $currentPages = $team->pages()->where('is_active', true)->count();

        return $currentPages < $plan['pages'];
    }

    /**
     * Check if the team has AI credits remaining.
     *
     * Dual-path during the Phase A → B rollout:
     *
     *   - Legacy (config.plans.use_legacy_message_counter = true):
     *     the old ai_credits_used column vs plan quota from config/stripe.php.
     *     Kept so we can roll back without a migration if the ledger shows a bug.
     *
     *   - Ledger (default, Phase A onwards): AiCredits::balance($team)->total()
     *     > 0 — the service is backed by append-only ai_credit_ledger with a
     *     60s Redis cache so this stays O(1) on the hot path.
     */
    public static function hasAiCredits($team): bool
    {
        if (config('plans.use_legacy_message_counter') === true) {
            $planKey = $team->subscription_plan ?? 'free';
            $plan = config("plans.plans.{$planKey}", config('plans.plans.free'));

            if ($plan['ai_credits'] === -1) {
                return true; // unlimited
            }

            return ($team->ai_credits_used ?? 0) < $plan['ai_credits'];
        }

        // Enterprise plans still get unlimited AI dispatch regardless of ledger.
        $planKey = $team->subscription_plan ?? 'free';
        $plan = config("plans.plans.{$planKey}", config('plans.plans.free'));
        if (($plan['ai_credits'] ?? 0) === -1) {
            return true;
        }

        return app(AiCredits::class)->balance($team)->total() > 0;
    }
}
