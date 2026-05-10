<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Make public marketing routes cacheable by Cloudflare / shared caches.
 *
 * Laravel's web middleware stack writes a session cookie and emits
 * `Cache-Control: no-cache, private` on every response, which forces
 * Cloudflare to bypass cache (`cf-cache-status: DYNAMIC`) on every request
 * and pins marketing pages to PHP origin TTFB (2–6s in production).
 *
 * For unauthenticated GET requests on whitelisted marketing paths we strip
 * the session cookie and replace the cache header with one that allows
 * the CDN to serve the response for an hour while still letting the
 * browser revalidate every 5 minutes.
 *
 * Auth-related routes (login, register, password reset) and dashboard
 * routes are intentionally excluded so CSRF tokens stay fresh per visitor.
 */
final class CachePublicMarketing
{
    private const SHARED_TTL = 3600;
    private const BROWSER_TTL = 300;

    /**
     * Exact paths that are safe to cache as-is.
     */
    private const CACHEABLE_PATHS = [
        '/',
        'about',
        'contact',
        'privacy',
        'terms',
        'pricing',
        'features',
        'whatsapp-inbox',
        'instagram-dm',
        'facebook-messenger',
        'telegram-inbox',
        'blog',
        'sitemap.xml',
    ];

    /**
     * Path prefixes (with trailing slash) that are safe to cache.
     */
    private const CACHEABLE_PREFIXES = [
        'blog/',
        'vs/',
        'industries/',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isCacheable($request, $response)) {
            return $response;
        }

        // Drop session/XSRF cookies so Cloudflare keys the cache without them.
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            $name = $cookie->getName();
            if (str_contains($name, 'session') || $name === 'XSRF-TOKEN') {
                $response->headers->removeCookie($name, $cookie->getPath(), $cookie->getDomain());
            }
        }

        $response->headers->set(
            'Cache-Control',
            'public, max-age=' . self::BROWSER_TTL . ', s-maxage=' . self::SHARED_TTL
        );

        // Vary on language so different locales don't collide in the shared cache.
        $vary = $response->headers->get('Vary');
        $response->headers->set('Vary', $vary ? $vary . ', Accept-Language' : 'Accept-Language');

        return $response;
    }

    private function isCacheable(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if (auth()->check()) {
            return false;
        }

        // A non-empty query string is unsafe to cache aggressively (?lang= is
        // already handled by Vary; tracking params would otherwise multiply
        // cache entries).
        $query = $request->query();
        if (! empty($query) && ! (count($query) === 1 && array_key_exists('lang', $query))) {
            return false;
        }

        $path = $request->path();

        if (in_array($path, self::CACHEABLE_PATHS, true)) {
            return true;
        }

        foreach (self::CACHEABLE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
