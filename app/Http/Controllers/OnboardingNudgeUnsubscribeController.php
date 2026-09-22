<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Phase C — one-click unsubscribe from onboarding nudge emails.
 *
 * Public route, no login required. Access controlled via Laravel's signed
 * URL macro (30-day expiry) — invalid or expired signatures are rejected
 * by the `signed` route middleware before this controller runs.
 */
final class OnboardingNudgeUnsubscribeController
{
    public function __invoke(Request $request, Team $team): Response
    {
        $team->forceFill(['disable_onboarding_nudges' => true])->save();

        return response(
            view('emails.onboarding.unsubscribed', ['team' => $team]),
            200,
        );
    }
}
