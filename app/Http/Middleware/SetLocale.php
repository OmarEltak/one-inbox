<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supportedLocales = ['en', 'ar', 'de', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        if ($request->has('lang') && in_array($request->query('lang'), $this->supportedLocales)) {
            session(['locale' => $request->query('lang')]);
        }

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        // 1. Query parameter (for switching)
        if ($request->has('lang') && in_array($request->query('lang'), $this->supportedLocales)) {
            return $request->query('lang');
        }

        // 2. Session
        if (session()->has('locale') && in_array(session('locale'), $this->supportedLocales)) {
            return session('locale');
        }

        // 3. Browser Accept-Language
        $preferred = $request->getPreferredLanguage($this->supportedLocales);
        if ($preferred) {
            return $preferred;
        }

        // 4. Fallback
        return config('app.locale', 'en');
    }
}
