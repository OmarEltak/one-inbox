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
