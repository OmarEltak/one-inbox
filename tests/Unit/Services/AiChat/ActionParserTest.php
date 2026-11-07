<?php

use App\Services\AiChat\ActionParser;

// Pins the AI-chat action-parser behavior that was changed after a prod bug on
// 2026-11-07, where the model emitted bare JSON ({"action":"send_message",...})
// instead of the ```pending_action-fenced block the previous regex required,
// and SendAiResponse silently dropped the action — the operator saw the AI
// say "both going out" while NO message actually left the system.

beforeEach(function () {
    $this->parser = new ActionParser();
});

// --- clean() — display-time stripper ---------------------------------------

test('clean() strips bare action JSON without executing', function () {
    $resp = "Both going out.\n\n{\"action\": \"send_message\", \"contact_id\": 5181, \"message\": \"hi\"}\n\n{\"action\": \"send_message\", \"contact_id\": 5178, \"message\": \"ho\"}";
    expect($this->parser->clean($resp))->toBe('Both going out.');
});

test('clean() strips fenced pending_action', function () {
    $resp = "Queued.\n\n```pending_action\n{\"action\": \"send_bulk_message\", \"message\": \"hi\"}\n```";
    expect($this->parser->clean($resp))->toBe('Queued.');
});

test('clean() is idempotent', function () {
    $clean = "Just a chat message, nothing special.";
    expect($this->parser->clean($clean))->toBe($clean);
    expect($this->parser->clean($this->parser->clean($clean)))->toBe($clean);
});

test('clean() leaves unrelated JSON-looking fragments alone', function () {
    $resp = 'Your pricing is {"tier": "pro", "price": 79}. Interested?';
    // Not an action — must survive.
    expect($this->parser->clean($resp))->toBe($resp);
});

// --- PRIMARY (fenced pending_action) ------------------------------------------------

test('fenced pending_action parses as pending and is stripped from text', function () {
    $resp = "Sending now.\n\n```pending_action\n{\"action\": \"send_message\", \"contact_id\": 42, \"message\": \"hi\"}\n```";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toMatchArray([
        'action' => 'send_message',
        'contact_id' => 42,
        'message' => 'hi',
    ]);
    expect($out['auto'])->toBe([]);
    expect($out['cleaned_response'])->toBe('Sending now.');
});

test('fenced action save_memory parses as auto and is stripped', function () {
    $resp = "Noted.\n\n```action\n{\"action\": \"save_memory\", \"content\": \"Omar prefers mornings\"}\n```";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toHaveCount(1);
    expect($out['auto'][0]['action'])->toBe('save_memory');
    expect($out['cleaned_response'])->toBe('Noted.');
});

// --- FALLBACK (bare JSON / plain fence) ---------------------------------------------

test('bare JSON on its own paragraph with send_message parses as pending', function () {
    $resp = "I'll send it now.\n\n{\"action\": \"send_message\", \"contact_id\": 5181, \"message\": \"Hey, following up on our chat.\"}";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->not->toBeNull();
    expect($out['pending']['action'])->toBe('send_message');
    expect($out['pending']['contact_id'])->toBe(5181);
    expect($out['cleaned_response'])->toContain("I'll send it now.");
    expect($out['cleaned_response'])->not->toContain('send_message');
});

test('plain triple-backtick fence (no language) with send_message parses as pending', function () {
    $resp = "Queued up.\n\n```\n{\"action\": \"send_bulk_message\", \"page_id\": 25, \"message\": \"Weekend sale\"}\n```";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->not->toBeNull();
    expect($out['pending']['action'])->toBe('send_bulk_message');
    expect($out['pending']['page_id'])->toBe(25);
    expect($out['cleaned_response'])->not->toContain('send_bulk_message');
});

test('json-fenced block with send_message parses as pending', function () {
    $resp = "Sending.\n\n```json\n{\"action\": \"send_message\", \"contact_id\": 7, \"message\": \"hi\"}\n```";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->not->toBeNull();
    expect($out['pending']['action'])->toBe('send_message');
    expect($out['cleaned_response'])->not->toContain('send_message');
});

// --- NEGATIVE / STRICT MATCHING -----------------------------------------------------

test('prose mentioning the word action without JSON does not falsely match', function () {
    $resp = 'I think the best action here is to wait until tomorrow. What do you want to do?';

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toBe([]);
    expect($out['cleaned_response'])->toBe($resp);
});

test('JSON with action outside the whitelist is ignored', function () {
    $resp = "Here is some config.\n\n{\"action\": \"delete_everything\", \"scope\": \"team\"}";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toBe([]);
    // The bogus JSON is not stripped because we didn't consume it.
    expect($out['cleaned_response'])->toContain('delete_everything');
});

test('JSON without an action key is ignored', function () {
    $resp = "Context: {\"user_id\": 42, \"role\": \"admin\"}.";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toBe([]);
    expect($out['cleaned_response'])->toContain('user_id');
});

test('invalid JSON that looks like an action object is ignored', function () {
    $resp = "I would send {\"action\": \"send_message\", broken json here.";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toBe([]);
});

// --- save_memory inline vs isolated -------------------------------------------------

test('save_memory bare JSON on its own paragraph auto-executes', function () {
    $resp = "Got it.\n\n{\"action\": \"save_memory\", \"content\": \"Omar likes short answers.\"}";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toHaveCount(1);
    expect($out['auto'][0]['action'])->toBe('save_memory');
    expect($out['cleaned_response'])->toContain('Got it.');
});

test('save_memory JSON embedded inline inside prose does NOT auto-execute', function () {
    // Inline (same line as surrounding prose) is treated as unsafe context so
    // save_memory is NOT auto-executed from a prose-embedded JSON fragment.
    $resp = 'I was thinking of emitting {"action": "save_memory", "content": "test"} here but let me ask first.';

    $out = $this->parser->parse($resp);

    expect($out['pending'])->toBeNull();
    expect($out['auto'])->toBe([]);
});

// --- Fenced pending_action takes precedence over fallback ---------------------------

test('when both fenced pending_action and a bare candidate exist, fenced wins', function () {
    $resp = "First try.\n\n```pending_action\n{\"action\": \"send_message\", \"contact_id\": 1, \"message\": \"A\"}\n```\n\nAlso considered: {\"action\": \"send_message\", \"contact_id\": 2, \"message\": \"B\"}";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->not->toBeNull();
    expect($out['pending']['contact_id'])->toBe(1);
});

// --- All pending action types round-trip --------------------------------------------

test('each whitelisted pending action type is accepted via bare JSON', function (string $action) {
    $resp = "ok\n\n{\"action\": \"{$action}\", \"contact_id\": 1, \"campaign_id\": 2, \"page_id\": 3, \"message\": \"x\"}";

    $out = $this->parser->parse($resp);

    expect($out['pending'])->not->toBeNull();
    expect($out['pending']['action'])->toBe($action);
})->with([
    'send_message',
    'send_bulk_message',
    'pause_ai',
    'resume_ai',
    'pause_campaign',
    'resume_campaign',
]);
