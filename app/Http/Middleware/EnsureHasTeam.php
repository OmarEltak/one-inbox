<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user->current_team_id && $user->teams()->exists()) {
            $firstTeam = $user->teams()->first()
                ?? $user->ownedTeams()->first();

            if ($firstTeam) {
                $user->switchTeam($firstTeam);
                $user->refresh();
            }
        }

        if (! $user->current_team_id) {
            return redirect()->route('teams.create');
        }

        return $next($request);
    }
}
