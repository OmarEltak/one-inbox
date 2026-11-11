<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\ProcessAiChatTurn;
use App\Models\AiCommand;
use App\Models\Contact;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Page;
use App\Services\Ai\AdminChatContext;
use App\Services\Billing\AiCredits;

// Pins the 2026-11-11 fix for "AI retracts its own send confirmation on the
// next turn because the outbound Message row hadn't landed yet". The confirm
// stamps action_taken with target/message/platform/result, and the next
// ProcessAiChatTurn prepends a RECENT OPERATOR-CONFIRMED ACTIONS block so
// the AI sees authoritative evidence that the send happened.

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
});

test('RECENT OPERATOR-CONFIRMED ACTIONS block is prepended to analyticsContext when a prior turn was confirmed', function () {
    $account = ConnectedAccount::create([
        'team_id'          => $this->team->id,
        'platform'         => 'facebook',
        'platform_user_id' => 'u_' . uniqid(),
        'name'             => 'Tester',
        'access_token'     => 'tkn',
    ]);
    $page = Page::create([
        'team_id'              => $this->team->id,
        'connected_account_id' => $account->id,
        'platform'             => 'facebook',
        'platform_page_id'     => 'pg_tarek_' . uniqid(),
        'name'                 => 'OT1 Pro FB',
        'is_active'            => true,
    ]);

    $tarek = Contact::create([
        'team_id' => $this->team->id,
        'name'    => 'Tarek Nour',
    ]);

    Conversation::create([
        'team_id'                  => $this->team->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'conv_' . uniqid(),
        'contact_id'               => $tarek->id,
        'last_message_at'          => now()->subHours(13),
    ]);

    // Prior "send him" turn — confirmed, action_taken populated authoritatively.
    AiCommand::create([
        'team_id'      => $this->team->id,
        'user_id'      => $this->user->id,
        'command'      => 'send him',
        'response'     => "I'll follow up… Done: Sent to Tarek Nour (queued).",
        'status'       => 'completed',
        'action_taken' => [
            'type'         => 'send_message',
            'confirmed_at' => now()->subSeconds(10)->toIso8601String(),
            'target_label' => 'Tarek Nour',
            'message'      => 'I will follow up in English; the trial is 1 day free.',
            'platform'     => 'facebook',
            'result'       => 'Sent to Tarek Nour (queued)',
        ],
    ]);

    // New turn: "now we wait?"
    $newCmd = AiCommand::create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'command' => 'now we wait?',
        'status'  => 'pending',
    ]);

    $captured = [];
    $this->mock(AiProviderInterface::class)
        ->shouldReceive('chatWithAdmin')
        ->once()
        ->andReturnUsing(function (string $message, int $teamId, string $analyticsContext, array $history) use (&$captured) {
            $captured['analyticsContext'] = $analyticsContext;
            return 'OK — the send is confirmed on record.';
        });

    (new ProcessAiChatTurn($newCmd->id, [
        ['role' => 'user',      'content' => 'send him'],
        ['role' => 'assistant', 'content' => "Done: Sent to Tarek Nour."],
        ['role' => 'user',      'content' => 'now we wait?'],
    ]))->handle(
        app(AiProviderInterface::class),
        app(AdminChatContext::class),
        app(AiCredits::class),
    );

    expect($captured['analyticsContext'] ?? null)->toBeString()
        ->and($captured['analyticsContext'])->toContain('RECENT OPERATOR-CONFIRMED ACTIONS')
        ->and($captured['analyticsContext'])->toContain('Tarek Nour')
        ->and($captured['analyticsContext'])->toContain('send_message')
        ->and($captured['analyticsContext'])->toContain('facebook');
});

test('no RECENT OPERATOR-CONFIRMED ACTIONS block is emitted when there are no confirmed prior actions', function () {
    $newCmd = AiCommand::create([
        'team_id' => $this->team->id,
        'user_id' => $this->user->id,
        'command' => 'hello',
        'status'  => 'pending',
    ]);

    $captured = [];
    $this->mock(AiProviderInterface::class)
        ->shouldReceive('chatWithAdmin')
        ->once()
        ->andReturnUsing(function (string $message, int $teamId, string $analyticsContext, array $history) use (&$captured) {
            $captured['analyticsContext'] = $analyticsContext;
            return 'hi';
        });

    (new ProcessAiChatTurn($newCmd->id, [['role' => 'user', 'content' => 'hello']]))
        ->handle(
            app(AiProviderInterface::class),
            app(AdminChatContext::class),
            app(AiCredits::class),
        );

    expect($captured['analyticsContext'] ?? '')->not->toContain('RECENT OPERATOR-CONFIRMED ACTIONS');
});
