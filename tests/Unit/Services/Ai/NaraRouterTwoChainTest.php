<?php

use App\Exceptions\AiAllProvidersUnavailable;
use App\Models\Message;
use App\Services\Ai\NaraRouterProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Pins the ARCHITECTURE §4 invariants for the two-chain refactor:
//   - text/vision cache keys are independent (no cross-poisoning)
//   - text-exhausted → vision fallback runs
//   - both chains exhausted → global cooldown set + throw
//   - cooldown gate short-circuits future calls without HTTP
//   - detectMessageKind() correctly routes media vs text inbounds

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
    config()->set('services.nararouter.api_key', 'test-key');
    config()->set('services.nararouter.api_key_secondary', null);
    config()->set('services.nararouter.base_url', 'https://router.test.invalid/v1');
    config()->set('services.nararouter.model', 'text-a');
    config()->set('services.nararouter.scoring_model', 'text-a');
    config()->set('services.nararouter.text_models', 'text-a,text-b,text-c');
    config()->set('services.nararouter.vision_models', 'vis-a,vis-b');
    config()->set('services.nararouter.reset_hours', 5);
    config()->set('services.nararouter.exhaustion_cooldown_min', 30);
    config()->set('services.nararouter.alert_email', null); // silence mail

    $this->provider = new NaraRouterProvider();
});

function narareply(string $content): array
{
    return [
        'choices' => [['message' => ['role' => 'assistant', 'content' => $content]]],
    ];
}

test('detectMessageKind returns text for plain text messages', function () {
    $msg = new Message(['content_type' => 'text', 'content' => 'hi']);
    expect($this->provider->detectMessageKind($msg))->toBe(NaraRouterProvider::KIND_TEXT);
});

test('detectMessageKind returns vision for image content_type', function () {
    $msg = new Message(['content_type' => 'image', 'media_url' => 'https://x/y.jpg']);
    expect($this->provider->detectMessageKind($msg))->toBe(NaraRouterProvider::KIND_VISION);
});

test('detectMessageKind returns vision when only media_url is present', function () {
    $msg = new Message(['content_type' => 'text', 'media_url' => 'https://x/y.jpg']);
    expect($this->provider->detectMessageKind($msg))->toBe(NaraRouterProvider::KIND_VISION);
});

test('detectMessageKind returns vision for sticker/document via media_type', function () {
    $msg = new Message(['content_type' => 'text', 'media_type' => 'image/webp']);
    expect($this->provider->detectMessageKind($msg))->toBe(NaraRouterProvider::KIND_VISION);
});

test('text primary success caches text active_model only', function () {
    Http::fake([
        'router.test.invalid/*' => Http::response(narareply('hi from text-a'), 200),
    ]);

    // Access dispatch() via reflection since it's protected.
    $reply = invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'hi'],
    ]);

    expect($reply)->toBe('hi from text-a');
    expect(Cache::get('nararouter:failover_state:text')['model'] ?? null)->toBe('text-a');
    expect(Cache::get('nararouter:failover_state:vision'))->toBeNull();
});

test('vision primary success caches vision active_model only, does not touch text', function () {
    Http::fake([
        'router.test.invalid/*' => Http::response(narareply('hi from vis-a'), 200),
    ]);

    $reply = invokeDispatch($this->provider, NaraRouterProvider::KIND_VISION, [
        ['role' => 'user', 'content' => 'hi'],
    ]);

    expect($reply)->toBe('hi from vis-a');
    expect(Cache::get('nararouter:failover_state:vision')['model'] ?? null)->toBe('vis-a');
    expect(Cache::get('nararouter:failover_state:text'))->toBeNull();
});

test('text chain exhausted falls through to vision chain', function () {
    // All 3 text models return 502; vis-a returns 200.
    Http::fakeSequence('router.test.invalid/*')
        ->push('bad gateway', 502)   // text-a
        ->push('bad gateway', 502)   // text-b
        ->push('bad gateway', 502)   // text-c
        ->push(narareply('hi from vision fallback'), 200); // vis-a

    $reply = invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'hi'],
    ]);

    expect($reply)->toBe('hi from vision fallback');
    // Vision success updates vision cache; text cache stays empty
    // (no text model ever succeeded).
    expect(Cache::get('nararouter:failover_state:vision')['model'] ?? null)->toBe('vis-a');
    expect(Cache::get('nararouter:failover_state:text'))->toBeNull();
});

test('both chains exhausted sets global cooldown and throws', function () {
    // All text + all vision return 502.
    Http::fake([
        'router.test.invalid/*' => Http::response('bad gateway', 502),
    ]);

    $call = fn () => invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'hi'],
    ]);

    expect($call)->toThrow(AiAllProvidersUnavailable::class);
    expect(Cache::get('nararouter:cooldown_until'))->toBeGreaterThan(time());
    expect(Cache::get('nararouter:cooldown_until'))->toBeLessThanOrEqual(time() + (30 * 60) + 5);
});

test('cooldown gate short-circuits without any HTTP call', function () {
    Cache::put('nararouter:cooldown_until', time() + 1000, now()->addHour());
    Http::fake(); // no requests should be made; if they are, they'd 200 vacuously

    $call = fn () => invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'hi'],
    ]);

    expect($call)->toThrow(AiAllProvidersUnavailable::class);
    Http::assertNothingSent();
});

test('cached active model is used as chain start on next call', function () {
    // Simulate: prior run marked text-b as active (e.g. after a text-a cascade).
    Cache::put(
        'nararouter:failover_state:text',
        ['model' => 'text-b', 'reset_at' => time() + 3600],
        now()->addHours(6),
    );

    Http::fake([
        'router.test.invalid/*' => Http::response(narareply('from text-b'), 200),
    ]);

    $reply = invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'hi'],
    ]);

    expect($reply)->toBe('from text-b');
    // Verify only one request went out (to text-b), not text-a first.
    Http::assertSentCount(1);
    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);
        return ($body['model'] ?? null) === 'text-b';
    });
});

test('reset window is preserved across successful calls', function () {
    // First call opens the window; second call must NOT extend it.
    Http::fake([
        'router.test.invalid/*' => Http::response(narareply('ok'), 200),
    ]);

    invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'one'],
    ]);
    $firstReset = Cache::get('nararouter:failover_state:text')['reset_at'];

    sleep(1);

    invokeDispatch($this->provider, NaraRouterProvider::KIND_TEXT, [
        ['role' => 'user', 'content' => 'two'],
    ]);
    $secondReset = Cache::get('nararouter:failover_state:text')['reset_at'];

    expect($secondReset)->toBe($firstReset);
});

/**
 * Helper: invoke the protected dispatch() method for the given kind + history.
 */
function invokeDispatch(NaraRouterProvider $p, string $kind, array $history, int $maxTokens = 500): string
{
    $m = new ReflectionMethod($p, 'dispatch');
    $m->setAccessible(true);
    return $m->invoke($p, $kind, 'system prompt', $history, $maxTokens);
}
