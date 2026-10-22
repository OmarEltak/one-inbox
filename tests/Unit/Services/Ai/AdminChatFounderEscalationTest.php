<?php

declare(strict_types=1);

use App\Services\Ai\NaraRouterProvider;

// Pins CLAUDE.md ambient contract: /ai-chat must NEVER try to resolve bugs or
// invent fixes. Any complaint / bug / feature-request must escalate to the
// founder on BOTH channels (email + the sidebar "راسل المؤسس" button) with the
// AI empathising briefly and stopping. If this block disappears from the
// prompt, customers get fabricated support answers that erode trust.
//
// Uses reflection to call the protected buildAdminChatSystemPrompt() so the
// test doesn't need to spin up HTTP or inherit the whole provider.

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Match NaraRouterCoalesceTest — the constructor reads these.
    config()->set('services.nararouter.api_key', 'test-key');
    config()->set('services.nararouter.base_url', 'https://example.invalid/v1');
    config()->set('services.nararouter.model', 'claude-sonnet-4.5');
    config()->set('services.nararouter.fallback_models', 'claude-sonnet-4.5');

    $this->provider = new NaraRouterProvider();
});

function callAdminPrompt(NaraRouterProvider $provider): string
{
    $ref = new ReflectionClass($provider);
    $method = $ref->getMethod('buildAdminChatSystemPrompt');
    $method->setAccessible(true);

    // teamId=0 is fine — Team::find() returns null, memory block is skipped,
    // deep-analysis block is skipped. The founder-escalation copy sits in the
    // static prompt body and is unaffected.
    return (string) $method->invoke($provider, 0, '=== ANALYTICS ===\n(empty for test)');
}

test('admin chat prompt contains founder email', function () {
    $prompt = callAdminPrompt($this->provider);

    expect($prompt)->toContain('omareltak7@gmail.com');
});

test('admin chat prompt names the sidebar whatsapp button in both languages', function () {
    $prompt = callAdminPrompt($this->provider);

    expect($prompt)
        ->toContain('راسل المؤسس')
        ->toContain('Message the founder');
});

test('admin chat prompt tells the AI not to invent fixes or timelines', function () {
    $prompt = callAdminPrompt($this->provider);

    // The escalation block exists to STOP the AI from guessing causes or
    // promising fixes. If a future edit removes the "do not" rail, that
    // regression needs to surface here before it ships.
    expect($prompt)
        ->toContain('ESCALATION TO FOUNDER')
        ->toContain('Do NOT propose a pending_action')
        ->toContain('cannot');
});

test('admin chat prompt gives both english and arabic example sentences', function () {
    $prompt = callAdminPrompt($this->provider);

    expect($prompt)
        ->toContain('I hear you')
        ->toContain('آسف إن ده مضايقك');
});
