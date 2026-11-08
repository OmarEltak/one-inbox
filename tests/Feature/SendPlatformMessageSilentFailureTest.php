<?php

declare(strict_types=1);

use App\Jobs\SendPlatformMessage;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Http;

// Pins the 2026-11-09 audit finding: Slack / Discord / Telegram / Email
// sender paths used to return null on failure, which handle() silently
// treated as success — stamping platform_sent_at=now() + green checkmark
// for messages that never left the server. All four now THROW on failure
// so handle()'s catch stamps metadata.send_status=failing and the inbox
// UI shows "Not delivered" instead of a lying checkmark.

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
});

function makeFakePageOn(string $platform, string $platformPageId, string $token): \App\Models\Page
{
    $account = \App\Models\ConnectedAccount::create([
        'team_id'          => test()->team->id,
        'platform'         => $platform,
        'platform_user_id' => "test-{$platform}-user",
        'name'             => "Test {$platform}",
        'access_token'     => encrypt($token),
        'scopes'           => [],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);

    return \App\Models\Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => test()->team->id,
        'platform'             => $platform,
        'platform_page_id'     => $platformPageId,
        'name'                 => "Test {$platform} Page",
        'page_access_token'    => $token,
        'is_active'            => true,
    ]);
}

function makeSlackPage(): \App\Models\Page
{
    return makeFakePageOn('slack', 'C0123456789', 'xoxb-test-token');
}

function makeDiscordPage(): \App\Models\Page
{
    return makeFakePageOn('discord', '1234567890', 'bot-test-token');
}

function makeTelegramPage(): \App\Models\Page
{
    return makeFakePageOn('telegram', '0000', '123:ABC-DEF-test-bot-token');
}

function makeOutboundMessageOn(\App\Models\Page $page, string $platformConversationId = 'U123'): Message
{
    $contact = \App\Models\Contact::create([
        'team_id'          => test()->team->id,
        'platform'         => $page->platform,
        'platform_user_id' => 'c-' . uniqid(),
        'name'             => 'Test Contact',
    ]);

    $conv = Conversation::create([
        'team_id'                  => test()->team->id,
        'page_id'                  => $page->id,
        'contact_id'               => $contact->id,
        'platform'                 => $page->platform,
        'platform_conversation_id' => $platformConversationId,
    ]);

    return Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'outbound',
        'sender_type'     => 'user',
        'content_type'    => 'text',
        'content'         => 'hello from test',
    ]);
}

// Helper: run SendPlatformMessage::handle and capture whether it threw.
// Pest's toThrow() with a string arg does a MESSAGE substring match, not
// an is-a check — so we use plain try/catch for "did it throw at all".
function expectJobToThrowAndStampFailure(int $messageId, string $expectedErrorFragment): void
{
    $threw = null;
    try {
        (new SendPlatformMessage($messageId))->handle();
    } catch (\Throwable $e) {
        $threw = $e;
    }
    expect($threw)->not->toBeNull('handle() should have thrown — silent-null safeguard is missing');

    $msg = Message::find($messageId);
    expect($msg->metadata['send_status'] ?? null)->toBe('failing');
    expect($msg->metadata['send_error_raw'] ?? '')->toContain($expectedErrorFragment);
    // Critical: platform_sent_at must NOT be stamped — that would show a green checkmark.
    expect($msg->platform_sent_at)->toBeNull();
}

test('Slack: failure stamps send_status=failing instead of silent-success', function () {
    $page = makeSlackPage();
    $message = makeOutboundMessageOn($page);

    Http::fake([
        'slack.com/api/chat.postMessage' => Http::response([
            'ok'    => false,
            'error' => 'channel_not_found',
        ], 200),
    ]);

    expectJobToThrowAndStampFailure($message->id, 'Slack send failed');
});

test('Discord: failure stamps send_status=failing instead of silent-success', function () {
    $page = makeDiscordPage();
    $message = makeOutboundMessageOn($page);

    Http::fake([
        'discord.com/api/v10/users/@me/channels' => Http::response(['code' => 50007, 'message' => 'Cannot send messages to this user'], 403),
    ]);

    expectJobToThrowAndStampFailure($message->id, 'Discord');
});

test('Telegram: failure stamps send_status=failing instead of silent-success', function () {
    $page = makeTelegramPage();
    $message = makeOutboundMessageOn($page);

    Http::fake([
        'api.telegram.org/*' => Http::response([
            'ok'          => false,
            'error_code'  => 400,
            'description' => 'Bad Request: chat not found',
        ], 400),
    ]);

    expectJobToThrowAndStampFailure($message->id, 'Telegram send failed');
});

test('Slack: success does NOT trigger the silent-failure safeguard', function () {
    $page = makeSlackPage();
    $message = makeOutboundMessageOn($page);

    Http::fake([
        'slack.com/api/chat.postMessage' => Http::response([
            'ok' => true,
            'ts' => '1699564800.123456',
        ], 200),
    ]);

    (new SendPlatformMessage($message->id))->handle();

    $message->refresh();
    expect($message->platform_message_id)->toBe('1699564800.123456');
    expect($message->platform_sent_at)->not->toBeNull();
    expect($message->metadata['send_status'] ?? null)->toBeNull();
});
