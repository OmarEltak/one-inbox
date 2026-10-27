<?php

use App\Services\Ai\NaraRouterProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Pins the 2026-10-27 safety-net design: scoring + any hinted call falls
// through the full main chain instead of one-shot direct-calling. Prevents a
// regression where NARAROUTER_SCORING_MODEL / NARAROUTER_MODEL hints are
// restored to direct-call semantics, which would make scoring silently 429
// when free quota exhausts.

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
    config()->set('services.nararouter.api_key', 'test-key');
    config()->set('services.nararouter.api_key_secondary', null);
    config()->set('services.nararouter.base_url', 'https://router.test.invalid/v1');
    // Deprecated env vars still set — provider must IGNORE them.
    config()->set('services.nararouter.model', 'direct-only');
    config()->set('services.nararouter.scoring_model', 'direct-only');
    // The real chain operators ship: paid primary + fallback + free safety net.
    config()->set('services.nararouter.text_models', 'paid-primary,paid-fallback,free-safetynet');
    config()->set('services.nararouter.vision_models', 'paid-primary,paid-fallback');
    config()->set('services.nararouter.reset_hours', 5);
    config()->set('services.nararouter.exhaustion_cooldown_min', 30);
    config()->set('services.nararouter.alert_email', null);
});

function reply(string $content): array
{
    return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
}

function invokeCallChat(NaraRouterProvider $p, string $hint, string $sys, array $history): string
{
    $m = new ReflectionMethod($p, 'callChat');
    $m->setAccessible(true);
    return $m->invoke($p, $hint, $sys, $history, 100);
}

test('deprecated NARAROUTER_MODEL / NARAROUTER_SCORING_MODEL are ignored — internal hints are empty', function () {
    $p = new NaraRouterProvider();

    $ref = new ReflectionClass($p);
    $modelProp = $ref->getProperty('model');
    $modelProp->setAccessible(true);
    $scoringProp = $ref->getProperty('scoringModel');
    $scoringProp->setAccessible(true);

    expect($modelProp->getValue($p))->toBe('');
    expect($scoringProp->getValue($p))->toBe('');
});

test('empty hint runs the full chain, hitting paid-primary first', function () {
    Http::fake(['router.test.invalid/*' => Http::response(reply('from paid-primary'), 200)]);

    $p = new NaraRouterProvider();
    expect(invokeCallChat($p, '', 'sys', [['role' => 'user', 'content' => 'hi']]))
        ->toBe('from paid-primary');

    // Cache pointer must land on paid-primary (chain head).
    expect(Cache::get('nararouter:failover_state:text')['model'] ?? null)->toBe('paid-primary');
});

test('paid-primary + paid-fallback both 429 → free safety net rescues the call', function () {
    Http::fake(['router.test.invalid/*' => Http::sequence()
        ->push('throttled', 429)       // paid-primary 429 (first key)
        ->push(reply('from free-safetynet'), 200)
        ->whenEmpty(Http::response('end', 200)),
    ]);

    $p = new NaraRouterProvider();
    expect(invokeCallChat($p, '', 'sys', [['role' => 'user', 'content' => 'hi']]))
        ->toBe('from free-safetynet');
});

test('hinted call that 429s cascades to main chain and returns paid-primary reply', function () {
    Http::fake(['router.test.invalid/*' => Http::sequence()
        ->push('throttled', 429)                        // the hinted "legacy-model" 429s
        ->push(reply('from paid-primary'), 200),        // cascade lands here
    ]);

    $p = new NaraRouterProvider();
    // Pass an OUT-OF-CHAIN hint — simulates any caller still pinning a model.
    expect(invokeCallChat($p, 'legacy-model', 'sys', [['role' => 'user', 'content' => 'hi']]))
        ->toBe('from paid-primary');
});

test('hinted call that returns 400 does NOT cascade — payload error is terminal', function () {
    Http::fake(['router.test.invalid/*' => Http::sequence()
        ->push('bad payload', 400)
        ->whenEmpty(Http::response(reply('should not reach'), 200)),
    ]);

    $p = new NaraRouterProvider();
    expect(invokeCallChat($p, 'legacy-model', 'sys', [['role' => 'user', 'content' => 'hi']]))
        ->toBe('');
});

test('hinted call that succeeds does NOT touch the main chain', function () {
    Http::fake(['router.test.invalid/*' => Http::response(reply('from legacy-model'), 200)]);

    $p = new NaraRouterProvider();
    expect(invokeCallChat($p, 'legacy-model', 'sys', [['role' => 'user', 'content' => 'hi']]))
        ->toBe('from legacy-model');

    // Chain cache MUST remain untouched — a happy hinted call should not
    // mutate the main chain's active-model pointer.
    expect(Cache::get('nararouter:failover_state:text'))->toBeNull();
});
