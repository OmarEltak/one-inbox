<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        if (! $user || ! $team) {
            return redirect()->route('login');
        }

        if ($user->isOwnerOf($team)) {
            return $next($request);
        }

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
