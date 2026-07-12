<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale and persists the user's choice in a cookie.
 *
 * Priority: ?lang= query param > `locale` cookie > Accept-Language > config default.
 *
 * The cookie (not session) is the source of persistence because
 * CachePublicMarketing strips session cookies to make marketing pages
 * CDN-cacheable. A dedicated, long-lived, unencrypted cookie survives that
 * stripping and is safe to expose since the value is public (en|ar).
 */
final class SetLocale
{
    public const COOKIE_NAME = 'locale';
    private const COOKIE_LIFETIME_DAYS = 365;
    private const SUPPORTED = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale         = $this->resolveLocale($request);
        $shouldPersist  = $this->shouldPersist($request, $locale);

        app()->setLocale($locale);

        /** @var Response $response */
        $response = $next($request);

        if ($shouldPersist) {
            $response->headers->setCookie($this->buildLocaleCookie($locale));
        }

        return $response;
    }

    private function resolveLocale(Request $request): string
    {
        // 1. Explicit switch via ?lang=
        $queryLang = $request->query('lang');
        if (is_string($queryLang) && in_array($queryLang, self::SUPPORTED, true)) {
            return $queryLang;
        }

        // 2. Persistent cookie (survives the CDN cookie strip)
        $cookieLang = $request->cookie(self::COOKIE_NAME);
        if (is_string($cookieLang) && in_array($cookieLang, self::SUPPORTED, true)) {
            return $cookieLang;
        }

        // 3. Browser preference
        $preferred = $request->getPreferredLanguage(self::SUPPORTED);
        if (is_string($preferred) && $preferred !== '') {
            return $preferred;
        }

        // 4. Fallback
        return (string) config('app.locale', 'en');
    }

    /**
     * Only write the cookie when the request explicitly asks to switch,
     * or when the browser preference differs from what's already stored.
     * Avoids setting Set-Cookie on every cacheable response.
     */
    private function shouldPersist(Request $request, string $locale): bool
    {
        $queryLang = $request->query('lang');
        if (is_string($queryLang) && in_array($queryLang, self::SUPPORTED, true)) {
            return true;
        }

        $current = $request->cookie(self::COOKIE_NAME);
        if (! is_string($current) || ! in_array($current, self::SUPPORTED, true)) {
            // No cookie set yet — persist the resolved value so it sticks.
            return $locale !== (string) config('app.locale', 'en');
        }

        return false;
    }

    private function buildLocaleCookie(string $locale): Cookie
    {
        return Cookie::create(
            name:     self::COOKIE_NAME,
            value:    $locale,
            expire:   time() + (self::COOKIE_LIFETIME_DAYS * 86400),
            path:     '/',
            domain:   null,
            secure:   request()->isSecure(),
            httpOnly: false,
            raw:      false,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }
}
