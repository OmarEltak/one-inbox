<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->currentTeam) {
            // Share team with all views
            view()->share('currentTeam', $request->user()->currentTeam);
        }

        return $next($request);
    }
}
