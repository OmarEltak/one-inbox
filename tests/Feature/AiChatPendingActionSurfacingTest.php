<?php

declare(strict_types=1);

use App\Livewire\AiChat;
use App\Models\AiCommand;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

// Pins the 2026-11-09 fix for "AI chat proposes an action, UI never shows
// confirm button, nothing happens". The async ProcessAiChatTurn pipeline
// used to call ActionParser::clean() on the broadcast payload — stripping
// action JSON from DISPLAY but losing the pending action itself.
// executeActions() (the method that sets $pendingAction) was defined but
// never called from the async path — pure orphan. Now handleAiChatTurnCompleted
// calls parse() + sets state, and mount() rehydrates on refresh.

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    Auth::login($this->user);
});

/**
 * Build an AiCommand row whose response contains a valid pending_action JSON
 * block. Mirrors what ProcessAiChatTurn persists.
 */
function makeCommandWithPendingAction(string $actionJson): AiCommand
{
    return AiCommand::create([
        'team_id'  => test()->team->id,
        'user_id'  => test()->user->id,
        'command'  => 'test command',
        'response' => "I'll do it.\n\n```pending_action\n{$actionJson}\n```",
        'status'   => 'completed',
    ]);
}

test('handleAiChatTurnCompleted surfaces pendingAction from a fresh broadcast payload', function () {
    $actionJson = json_encode([
        'action'       => 'send_bulk_message',
        'page_id'      => 1,
        'status'       => 'hot',
        'message'      => 'test blast',
        'scheduled_at' => '2099-01-01T10:00:00Z',
    ]);
    $cmd = makeCommandWithPendingAction($actionJson);

    Livewire::test(AiChat::class)
        ->call('handleAiChatTurnCompleted', [
            'command_id' => $cmd->id,
            'user_id'    => test()->user->id,
            'response'   => (string) $cmd->response,
        ])
        ->assertSet('pendingAction.action', 'send_bulk_message')
        ->assertSet('pendingAction.status', 'hot')
        ->assertSet('pendingAction.message', 'test blast');
});

test('handleAiChatTurnCompleted does NOT leak pendingAction to a different user on the same team', function () {
    // Create the command under a DIFFERENT user so mount()'s rehydrate step
    // (scoped to Auth::id()) doesn't pre-populate pendingAction — otherwise
    // we'd be testing mount(), not the handler's early-return guard.
    [$otherUser, $otherTeam] = makeUserWithTeam();
    $otherCmd = AiCommand::create([
        'team_id'  => $otherTeam->id,
        'user_id'  => $otherUser->id,
        'command'  => 'test',
        'response' => "I'll do it.\n\n```pending_action\n" . json_encode([
            'action' => 'send_message',
            'contact_id' => 42,
            'message' => 'leak-check',
        ]) . "\n```",
        'status'   => 'completed',
    ]);

    // Broadcast arrives addressed to the OTHER user — must be ignored by
    // the component mounted as test()->user.
    Livewire::test(AiChat::class)
        ->call('handleAiChatTurnCompleted', [
            'command_id' => $otherCmd->id,
            'user_id'    => $otherUser->id,
            'response'   => (string) $otherCmd->response,
        ])
        ->assertSet('pendingAction', null);
});

test('mount rehydrates pendingAction when the latest completed command has an unconfirmed action', function () {
    $actionJson = json_encode([
        'action'     => 'send_bulk_message',
        'page_id'    => 7,
        'status'     => 'warm',
        'message'    => 'rehydrate me',
    ]);
    makeCommandWithPendingAction($actionJson);

    Livewire::test(AiChat::class)
        ->assertSet('pendingAction.action', 'send_bulk_message')
        ->assertSet('pendingAction.status', 'warm');
});

test('mount does NOT rehydrate pendingAction when the operator already acted on that turn', function () {
    // An AiCommand whose action_taken is populated = operator already confirmed.
    AiCommand::create([
        'team_id'      => test()->team->id,
        'user_id'      => test()->user->id,
        'command'      => 'send bulk',
        'response'     => "Done.\n\n```pending_action\n" . json_encode(['action' => 'send_bulk_message', 'message' => 'x']) . "\n```",
        'status'       => 'completed',
        'action_taken' => ['type' => 'send_bulk_message', 'done' => true],
    ]);

    Livewire::test(AiChat::class)
        ->assertSet('pendingAction', null);
});

test('mount ignores historical AiCommand rows whose response has no action block', function () {
    AiCommand::create([
        'team_id'  => test()->team->id,
        'user_id'  => test()->user->id,
        'command'  => 'what time is it',
        'response' => 'A regular conversational reply with no actions.',
        'status'   => 'completed',
    ]);

    Livewire::test(AiChat::class)
        ->assertSet('pendingAction', null);
});

test('pollPendingTurns safety-net strips action JSON from bubble + surfaces pendingAction', function () {
    // Pins the 2026-11-09 UI regression: when the Reverb broadcast was dropped
    // or arrived late, the Alpine 8s poll (syncPendingTurnsFromDb → pollPendingTurns)
    // swapped the pending-dots bubble with the RAW $row->response — which still
    // contained the pending_action fenced block. Content went in as raw, markdown
    // rendered the fence as a visible code block in the bubble, and no confirm
    // bar appeared because pendingAction was never populated on the poll path.
    // Three sync paths (mount, Reverb, poll) must all sanitize identically.

    // Simulate the race: the component is mounted WHILE a pending AiCommand is
    // in-flight. We prime the messages array via sendMessage (which appends an
    // optimistic placeholder with pending=>true), then flip the DB row to
    // completed with a raw response that contains action JSON, then trigger the
    // poll and assert that (a) bubble content is clean, (b) pendingAction fires.

    $actionJson = json_encode([
        'action'     => 'send_message',
        'contact_id' => 42,
        'message'    => 'poll-race test',
    ]);

    // Create a pending AiCommand (as if sendMessage just dispatched the job).
    $cmd = AiCommand::create([
        'team_id'  => test()->team->id,
        'user_id'  => test()->user->id,
        'command'  => 'send a note',
        'response' => '',
        'status'   => 'pending',
    ]);

    $component = Livewire::test(AiChat::class);

    // Inject the optimistic placeholder the way sendMessage() would, by
    // directly setting messages via Livewire's set() — avoids coupling the
    // test to the full sendMessage branching logic.
    $component->set('messages', [
        ['role' => 'user',      'content' => 'send a note'],
        ['role' => 'assistant', 'content' => '…', 'pending' => true, 'command_id' => $cmd->id],
    ]);

    // Now the "job" finishes — write the raw response back to the DB with the
    // action JSON embedded as a fenced pending_action block.
    $cmd->update([
        'response' => "On it — here's the draft send.\n\n```pending_action\n{$actionJson}\n```\n\n",
        'status'   => 'completed',
    ]);

    // Fire the Alpine safety-net poll (this is the exact path that was leaking
    // raw JSON into the bubble on dropped Reverb events).
    $component->call('pollPendingTurns');

    // (a) bubble content must be clean — scan every message, none may contain
    //     the raw pending_action fence or the JSON action key
    $messages = $component->get('messages');
    foreach ($messages as $m) {
        if (($m['role'] ?? null) !== 'assistant') {
            continue;
        }
        if (! empty($m['pending'])) {
            continue; // placeholder — not what we're testing
        }
        expect($m['content'] ?? '')->not->toContain('pending_action', 'bubble must not contain the action fence identifier');
        expect($m['content'] ?? '')->not->toContain('"action":', 'bubble must not contain action JSON');
    }

    // (b) pendingAction must be set so the Confirm bar renders
    $component->assertSet('pendingAction.action', 'send_message');
    $component->assertSet('pendingAction.contact_id', 42);
});
