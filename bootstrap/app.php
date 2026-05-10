<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies so Cloudflare Tunnel headers (X-Forwarded-Host, X-Forwarded-Proto)
        // are used for URL generation and HTTPS detection.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'team'       => \App\Http\Middleware\EnsureHasTeam::class,
            'team.set'   => \App\Http\Middleware\SetCurrentTeam::class,
            'plan.limits' => \App\Http\Middleware\EnforcePlanLimits::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SetCurrentTeam::class,
        ]);

        // Prepend so this runs LAST on the outbound response — after EncryptCookies
        // and AddQueuedCookiesToResponse have already attached session/XSRF cookies.
        // Otherwise stripped cookies get re-added downstream and the CDN still
        // can't cache the response.
        $middleware->prependToGroup('web', [
            \App\Http\Middleware\CachePublicMarketing::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
