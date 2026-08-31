<?php

declare(strict_types=1);

return [
    /*
     * Cache store for the two hot-path lookups (dedupe + rate limit).
     *
     * Prod uses 'redis' explicitly because the app-wide CACHE_STORE defaults to
     * 'database' for other cache needs but our per-webhook lookups need sub-ms
     * latency. Tests override this to 'array' via config in beforeEach().
     */
    'hot_cache_store' => env('COMMENTS_HOT_CACHE_STORE', 'redis'),
];
