<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $team = $user?->currentTeam;

        if (! $team || ! $team->hasAnyConnection()) {
            return redirect()
                ->route('connections.index')
                ->with('success', 'Connect at least one platform to unlock this page.');
        }

        return $next($request);
    }
}
