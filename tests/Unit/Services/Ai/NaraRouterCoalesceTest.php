<?php

use App\Services\Ai\NaraRouterProvider;

// Pins the ARCHITECTURE §4 invariant that consecutive same-role turns in the
// history tail must be coalesced before hitting NaraRouter — Anthropic's
// Messages API rejects strict-alternation violations with a 400 we do not
// cascade on.

// NaraRouterProvider's constructor reads config('services.nararouter.*'), so
// the Laravel container must be booted. Pest defaults Unit tests to the raw
// PHPUnit TestCase — opt into the full framework TestCase for this file.
uses(Tests\TestCase::class);

beforeEach(function () {
    // The provider constructor reads services.nararouter.* — supply safe
    // defaults so the unit test doesn't depend on real env config.
    config()->set('services.nararouter.api_key', 'test-key');
    config()->set('services.nararouter.base_url', 'https://example.invalid/v1');
    config()->set('services.nararouter.model', 'claude-sonnet-4.5');
    config()->set('services.nararouter.fallback_models', 'claude-sonnet-4.5');

    $this->provider = new NaraRouterProvider();
});

test('alternating history is unchanged', function () {
    $out = $this->provider->coalesceRoles([
        ['role' => 'user',      'content' => 'hi'],
        ['role' => 'assistant', 'content' => 'hello'],
        ['role' => 'user',      'content' => 'ok'],
    ]);

    expect($out)->toBe([
        ['role' => 'user',      'content' => 'hi'],
        ['role' => 'assistant', 'content' => 'hello'],
        ['role' => 'user',      'content' => 'ok'],
    ]);
});

test('two consecutive assistants are merged with a blank line', function () {
    $out = $this->provider->coalesceRoles([
        ['role' => 'user',      'content' => 'send it'],
        ['role' => 'assistant', 'content' => 'This will be sent to H.'],
        ['role' => 'assistant', 'content' => 'Done: Sent to H.'],
    ]);

    expect($out)->toBe([
        ['role' => 'user',      'content' => 'send it'],
        ['role' => 'assistant', 'content' => "This will be sent to H.\n\nDone: Sent to H."],
    ]);
});

test('two consecutive users are merged (inbound burst)', function () {
    $out = $this->provider->coalesceRoles([
        ['role' => 'user', 'content' => 'hi'],
        ['role' => 'user', 'content' => 'anyone there?'],
        ['role' => 'user', 'content' => 'hello?'],
    ]);

    expect($out)->toBe([
        ['role' => 'user', 'content' => "hi\n\nanyone there?\n\nhello?"],
    ]);
});

test('model role is normalized to assistant', function () {
    // GeminiProvider used 'model' historically; the tail builder should
    // normalize it just like the pre-fix inline mapping did.
    $out = $this->provider->coalesceRoles([
        ['role' => 'user',  'content' => 'hi'],
        ['role' => 'model', 'content' => 'hello'],
    ]);

    expect($out)->toBe([
        ['role' => 'user',      'content' => 'hi'],
        ['role' => 'assistant', 'content' => 'hello'],
    ]);
});

test('empty content is dropped, not sent as empty string', function () {
    // An empty content field is also a 400 trigger on Anthropic.
    $out = $this->provider->coalesceRoles([
        ['role' => 'user',      'content' => 'hi'],
        ['role' => 'assistant', 'content' => ''],
        ['role' => 'user',      'content' => 'ok'],
    ]);

    expect($out)->toBe([
        ['role' => 'user', 'content' => "hi\n\nok"],
    ]);
});

test('null content is treated as empty and dropped', function () {
    $out = $this->provider->coalesceRoles([
        ['role' => 'user',      'content' => 'hi'],
        ['role' => 'assistant', 'content' => null],
        ['role' => 'user',      'content' => 'ok'],
    ]);

    expect($out)->toBe([
        ['role' => 'user', 'content' => "hi\n\nok"],
    ]);
});

test('empty history yields empty output', function () {
    expect($this->provider->coalesceRoles([]))->toBe([]);
});

test('all-assistant history collapses to one assistant turn', function () {
    // Not a realistic shape (callChat will add a synthetic user afterwards)
    // but the coalescer must still preserve alternation locally.
    $out = $this->provider->coalesceRoles([
        ['role' => 'assistant', 'content' => 'a'],
        ['role' => 'assistant', 'content' => 'b'],
        ['role' => 'assistant', 'content' => 'c'],
    ]);

    expect($out)->toBe([
        ['role' => 'assistant', 'content' => "a\n\nb\n\nc"],
    ]);
});
