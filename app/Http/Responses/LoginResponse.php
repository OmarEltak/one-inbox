<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Post-login redirect gate for the onboarding activation initiative.
 *
 * Fortify's default `home` (/dashboard) skips over Phase A's "Meet Your AI"
 * wizard whenever a returning user logs in without an intended URL. Users who
 * signed up, walked away, then logged back in were landing on /connections
 * (via require.connection middleware chain) and never seeing the wizard.
 *
 * This response gates: any authenticated user whose team has NOT completed
 * onboarding is sent to /onboarding/meet-your-ai, unless they already have
 * an intended URL from a session redirect.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();
        $team = $user?->currentTeam;

        // Explicit intended URL (came from a guarded page they were bounced off)
        // wins over onboarding gating. Users who tried to open the inbox
        // directly should end up in the inbox after auth.
        if ($intended = session()->pull('url.intended')) {
            return redirect()->to($intended);
        }

        if ($team !== null && $team->onboarding_completed_at === null) {
            return redirect()->route('onboarding.meet-your-ai');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
