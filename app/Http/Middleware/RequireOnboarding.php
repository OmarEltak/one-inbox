<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force onboarding completion for every authenticated request.
 *
 * Per Omar's mandate on 2026-09-23: a user who signs up MUST finish the
 * Meet-Your-AI wizard before doing anything else. No skipping, no shortcuts.
 *
 * Any authenticated request whose current team has onboarding_completed_at
 * IS NULL is redirected to /onboarding/meet-your-ai — except:
 *
 *  - the onboarding route itself (or we'd infinite-loop)
 *  - logout (users must be able to escape a broken state)
 *  - the profile/settings pages needed to fix a bad account
 *  - webhooks, API, Livewire updates (never redirect POSTs from Livewire)
 *  - super-admin routes (Omar can operate the admin surface without finishing
 *    his own onboarding — otherwise he can't unblock stuck customers)
 *
 * Applied via the `require.onboarding` alias in bootstrap/app.php.
 */
class RequireOnboarding
{
    /**
     * Route name prefixes that are always allowed through, even when
     * onboarding is incomplete. Match on the FULL route name — startsWith.
     */
    protected array $allowlistNamePrefixes = [
        'onboarding.',
        'logout',
        'settings.profile',
        'settings.password',
        'super-admin.',
        'auth.',
    ];

    /**
     * Path prefixes that never redirect (Livewire's internal update endpoint,
     * webhooks, health, etc). Match on request path.
     */
    protected array $allowlistPathPrefixes = [
        'livewire/',
        'api/',
        '_ignition/',
        'health/',
        'super-admin/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        // Super-admins bypass onboarding gating entirely so they can operate
        // the admin surfaces even before finishing their own wizard.
        if ($user->is_super_admin ?? false) {
            return $next($request);
        }

        $team = $user->currentTeam;
        if ($team === null || $team->onboarding_completed_at !== null) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        foreach ($this->allowlistPathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        $routeName = $request->route()?->getName() ?? '';
        foreach ($this->allowlistNamePrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        return redirect()->route('onboarding.meet-your-ai');
    }
}
