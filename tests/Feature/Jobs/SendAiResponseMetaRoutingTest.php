<?php

declare(strict_types=1);

use App\Jobs\SendAiResponse;
use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/*
 * Pinning test for the IG Business Login send-routing bug (2026-11-14).
 *
 * SendAiResponse::sendViaMetaMessenger and SendPlatformMessage::sendViaMetaMessenger
 * are parallel code paths. Both must branch on $page->metadata.auth_type:
 *   - 'instagram_business' → graph.instagram.com/{version}/me/messages (IGAA tokens)
 *   - IG via FB Login     → graph.facebook.com/{version}/{linked_facebook_page_id}/messages
 *   - plain FB            → graph.facebook.com/{version}/{platform_page_id}/messages
 *
 * SendAiResponse was missing the auth_type branch — every auto-reply to a Direct
 * IG page POSTed the IGAA token against graph.facebook.com and got rejected with
 * code 190 "Cannot parse access token". Messages stored with platform_message_id
 * NULL, inbox UI showed "Replied by AI" that never actually left the server.
 *
 * See memory/project_whatsapp_parallel_send_paths.md for the general lesson.
 */

function buildAutoResponder(string $authType, ?string $linkedFbPageId = null): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'owner_id'         => $user->id,
        'ai_enabled'       => true,
        'ai_credits_used'  => 0,
        'ai_credits_limit' => 1000,
    ]);
    $account = ConnectedAccount::factory()->create(['team_id' => $team->id, 'platform' => 'instagram']);
    $page = Page::factory()->create([
        'team_id'              => $team->id,
        'connected_account_id' => $account->id,
        'platform'             => $authType === 'facebook' ? 'facebook' : 'instagram',
        'platform_page_id'     => 'PAGE_PLATFORM_ID_123',
        'page_access_token'    => 'IGAAUBPw_fake_token_prefix_direct_ig_business',
        'is_active'            => true,
        'metadata'             => array_filter([
            'auth_type'               => $authType === 'facebook' ? null : $authType,
            'linked_facebook_page_id' => $linkedFbPageId,
        ]),
    ]);
    $contact = Contact::factory()->create(['team_id' => $team->id]);
    $conversation = Conversation::create([
        'team_id'                  => $team->id,
        'page_id'                  => $page->id,
        'contact_id'               => $contact->id,
        'platform'                 => $page->platform,
        'platform_conversation_id' => 'RECIPIENT_PSID_999',
        'status'                   => 'open',
    ]);
    $message = Message::create([
        'conversation_id' => $conversation->id,
        'direction'       => 'outbound',
        'sender_type'     => 'ai',
        'content_type'    => 'text',
        'content'         => 'auto-reply body',
    ]);

    return [$page, $conversation, $message];
}

function invokeAutoSend(Page $page, string $recipientId, string $text, Message $aiMessage): void
{
    $job = new SendAiResponse($aiMessage->conversation_id, $aiMessage->id);
    $method = new ReflectionMethod($job, 'sendViaMetaMessenger');
    $method->setAccessible(true);
    $method->invoke($job, $page, $recipientId, $text, $aiMessage);
}

// TestCase + RefreshDatabase are auto-applied to tests/Feature/* via tests/Pest.php.

it('posts Direct IG Business Login auto-replies to graph.instagram.com/me/messages, NOT graph.facebook.com', function () {
    Http::fake([
        'graph.instagram.com/*/me/messages' => Http::response(['message_id' => 'mid.123'], 200),
        'graph.facebook.com/*'              => Http::response([
            'error' => ['message' => 'Invalid OAuth access token - Cannot parse access token', 'code' => 190],
        ], 400),
    ]);

    [$page, , $message] = buildAutoResponder(authType: 'instagram_business');

    invokeAutoSend($page, 'RECIPIENT_PSID_999', 'hi there', $message);

    Http::assertSent(fn ($req) => str_contains($req->url(), 'graph.instagram.com')
        && str_contains($req->url(), '/me/messages'));
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'graph.facebook.com'));

    $message->refresh();
    expect($message->platform_message_id)->toBe('mid.123');
    expect($message->platform_sent_at)->not->toBeNull();
});

it('posts IG-via-FB auto-replies to the linked FB page ID on graph.facebook.com', function () {
    Http::fake([
        'graph.facebook.com/*/LINKED_FB_PAGE_777/messages' => Http::response(['message_id' => 'mid.456'], 200),
    ]);

    [$page, , $message] = buildAutoResponder(authType: 'facebook_ig', linkedFbPageId: 'LINKED_FB_PAGE_777');

    invokeAutoSend($page, 'RECIPIENT_PSID_999', 'hi there', $message);

    Http::assertSent(fn ($req) => str_contains($req->url(), 'graph.facebook.com')
        && str_contains($req->url(), 'LINKED_FB_PAGE_777/messages'));
    // Must NOT hit the IG page's own platform_page_id as the endpoint — that would 404.
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'PAGE_PLATFORM_ID_123/messages'));
});

it('posts plain FB auto-replies to the FB page platform_page_id on graph.facebook.com', function () {
    Http::fake([
        'graph.facebook.com/*/PAGE_PLATFORM_ID_123/messages' => Http::response(['message_id' => 'mid.789'], 200),
    ]);

    [$page, , $message] = buildAutoResponder(authType: 'facebook');

    invokeAutoSend($page, 'RECIPIENT_PSID_999', 'hi there', $message);

    Http::assertSent(fn ($req) => str_contains($req->url(), 'graph.facebook.com')
        && str_contains($req->url(), 'PAGE_PLATFORM_ID_123/messages'));
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'graph.instagram.com'));
});

it('stamps send_status=failed and leaves platform_message_id NULL when Meta rejects the send', function () {
    Http::fake([
        '*' => Http::response([
            'error' => ['message' => 'Invalid OAuth access token - Cannot parse access token', 'code' => 190],
        ], 400),
    ]);

    [$page, , $message] = buildAutoResponder(authType: 'instagram_business');

    invokeAutoSend($page, 'RECIPIENT_PSID_999', 'hi there', $message);

    $message->refresh();
    expect($message->platform_message_id)->toBeNull();
    expect($message->metadata['send_status'] ?? null)->toBe('failed');
    expect($message->metadata['send_error'] ?? null)->toContain('Meta rejected');
});
