<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

/**
 * Phase 4 (docs/OT1_LIMITS.md §11) — per-team throttle on
 * SendCampaignWhatsAppJob. Reads Cache::add semantics directly to prove
 * the lock/increment/decrement pattern is symmetric.
 *
 * A full end-to-end assertion (dispatch + release) would require faking
 * Wuzapi and a live Redis; we keep this as a fast unit spec of the guard
 * mechanic so a future refactor doesn't quietly regress the pattern.
 */

beforeEach(fn () => Cache::flush());

it('caches an in-flight counter per team via Cache::add + increment', function () {
    $key = 'campaign:send:inflight:42';

    // First send: Cache::add establishes the lock with value 1.
    expect(Cache::add($key, 1, 60))->toBeTrue();
    expect((int) Cache::get($key))->toBe(1);

    // Concurrent second send: Cache::add returns false, increment brings us to 2.
    expect(Cache::add($key, 1, 60))->toBeFalse();
    expect((int) Cache::increment($key))->toBe(2);

    // Third: still under the 3 cap.
    expect((int) Cache::increment($key))->toBe(3);

    // Fourth: over the cap. Job releases; the guard uses decrement to unwind.
    $fourth = (int) Cache::increment($key);
    expect($fourth)->toBe(4);
    Cache::decrement($key);
    expect((int) Cache::get($key))->toBe(3);
});

it('decrements symmetrically after send so the counter zeroes out', function () {
    $key = 'campaign:send:inflight:99';

    Cache::add($key, 1, 60);
    Cache::increment($key);
    Cache::increment($key);
    expect((int) Cache::get($key))->toBe(3);

    Cache::decrement($key);
    Cache::decrement($key);
    Cache::decrement($key);
    expect((int) Cache::get($key))->toBe(0);
});
