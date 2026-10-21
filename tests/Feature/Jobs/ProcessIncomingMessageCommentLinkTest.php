<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\ProcessIncomingMessage;
use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\ConnectedAccount;
use App\Models\Message;
use App\Models\Page;
use App\Models\PagesPost;
use App\Models\Team;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    config(['comments.hot_cache_store' => 'array']);
    Cache::store('array')->flush();
    // Prevent follow-on AI dispatches (SendAiResponse etc.) from touching the
    // DB or network; this test only cares about the Message→Comment link.
    Queue::fake();
    // Stub Meta sender-profile lookups and any other outbound Graph calls so
    // the pipeline runs without network. An empty response is enough — the
    // cascade degrades gracefully to "no name/avatar" and still writes the Message.
    Http::fake([
        'graph.facebook.com/*' => Http::response([], 200),
        'graph.instagram.com/*' => Http::response([], 200),
    ]);
});

/**
 * Spin up a facebook page + the originating Comment that already had its
 * one-shot DM dispatched by SendAiCommentReplyJob (so dm_sent_at is populated
 * and dm_graph_message_id is set).
 *
 * @return array{Page, Comment}
 */
function makePageAndCommentWithSentDm(string $commenterPsid = 'USER_PSID_123'): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'owner_id'         => $user->id,
        'ai_enabled'       => true,
        'ai_credits_used'  => 0,
        'ai_credits_limit' => 1000,
    ]);
    $account = ConnectedAccount::factory()->create([
        'team_id'  => $team->id,
        'platform' => 'facebook',
    ]);
    $page = Page::factory()->create([
        'team_id'              => $team->id,
        'connected_account_id' => $account->id,
        'platform'             => 'facebook',
        'platform_page_id'     => 'PAGE_FB_1',
        'is_active'            => true,
    ]);
    AiConfig::create([
        'page_id'              => $page->id,
        'team_id'              => $team->id,
        'business_description' => 'valid business description over ten chars',
        'is_active'            => true,
        'is_24_7'              => true,
    ]);
    $post = PagesPost::factory()->create(['page_id' => $page->id]);

    $comment = Comment::factory()->create([
        'page_id'               => $page->id,
        'pages_post_id'         => $post->id,
        'commenter_platform_id' => $commenterPsid,
        'commenter_name'        => 'Comment Ada',
        'text'                  => 'How much?',
        'decision'              => Comment::DECISION_REPLIED,
        'reply_text'            => 'Great question — DM us for pricing!',
        'graph_reply_id'        => 'REPLY_1',
        'dm_sent_at'            => now()->subMinutes(2),
        'dm_graph_message_id'   => 'm_outbound_dm_1',
    ]);

    return [$page->refresh(), $comment->refresh()];
}

/**
 * Build a Meta FB-Messenger webhook payload for an inbound DM from the given
 * sender PSID to the given page ID. `mid` is the Graph `message_id` Meta
 * assigns to the inbound reply — distinct from the outbound DM's message_id.
 */
function makeInboundDmPayload(string $pageId, string $senderPsid, string $mid, string $text = 'Thanks for the DM!'): array
{
    return [
        'object' => 'page',
        'entry'  => [[
            'id'        => $pageId,
            'time'      => now()->getTimestampMs(),
            'messaging' => [[
                'sender'    => ['id' => $senderPsid],
                'recipient' => ['id' => $pageId],
                'timestamp' => now()->getTimestampMs(),
                'message'   => [
                    'mid'  => $mid,
                    'text' => $text,
                ],
            ]],
        ]],
    ];
}

it('links an inbound FB DM to the originating comment when sender + 72h window match', function () {
    [$page, $comment] = makePageAndCommentWithSentDm('USER_PSID_123');

    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => makeInboundDmPayload('PAGE_FB_1', 'USER_PSID_123', 'MESSAGE_INBOUND_XYZ'),
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    $message = Message::where('platform_message_id', 'MESSAGE_INBOUND_XYZ')->first();

    expect($message)->not->toBeNull()
        ->and($message->direction)->toBe('inbound')
        ->and($message->sender_type)->toBe('contact')
        ->and($message->source_comment_id)->toBe($comment->id);
});

it('leaves source_comment_id null when no matching comment exists', function () {
    // Set up the page but create no triggering comment for this PSID.
    [$page, $comment] = makePageAndCommentWithSentDm('OTHER_PSID_999');

    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => makeInboundDmPayload('PAGE_FB_1', 'UNRELATED_PSID_42', 'MESSAGE_UNLINKED'),
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    $message = Message::where('platform_message_id', 'MESSAGE_UNLINKED')->first();

    expect($message)->not->toBeNull()
        ->and($message->direction)->toBe('inbound')
        ->and($message->source_comment_id)->toBeNull();
});

it('leaves source_comment_id null when the matching comment is older than 72h', function () {
    [$page, $comment] = makePageAndCommentWithSentDm('USER_PSID_STALE');
    // Push the DM out of the recency window.
    $comment->forceFill(['dm_sent_at' => now()->subHours(80)])->save();

    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => makeInboundDmPayload('PAGE_FB_1', 'USER_PSID_STALE', 'MESSAGE_STALE'),
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    $message = Message::where('platform_message_id', 'MESSAGE_STALE')->first();

    expect($message)->not->toBeNull()
        ->and($message->source_comment_id)->toBeNull();
});

it('leaves source_comment_id null when the comment has no dm_graph_message_id (DM send failed)', function () {
    [$page, $comment] = makePageAndCommentWithSentDm('USER_PSID_NO_DM');
    // Simulate a DM-send failure that left dm_graph_message_id null.
    $comment->forceFill([
        'dm_graph_message_id' => null,
        'dm_sent_at'          => null,
    ])->save();

    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => makeInboundDmPayload('PAGE_FB_1', 'USER_PSID_NO_DM', 'MESSAGE_NO_DM'),
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    $message = Message::where('platform_message_id', 'MESSAGE_NO_DM')->first();

    expect($message)->not->toBeNull()
        ->and($message->source_comment_id)->toBeNull();
});

it('exposes the Message → Comment and Comment → Message[] relationships correctly', function () {
    [$page, $comment] = makePageAndCommentWithSentDm('USER_PSID_REL');

    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => makeInboundDmPayload('PAGE_FB_1', 'USER_PSID_REL', 'MESSAGE_REL_1'),
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(AiProviderInterface::class));

    $message = Message::where('platform_message_id', 'MESSAGE_REL_1')->first();

    // Message → Comment (BelongsTo)
    expect($message->sourceComment)->not->toBeNull()
        ->and($message->sourceComment->id)->toBe($comment->id);

    // Comment → Message[] (HasMany)
    $comment->refresh();
    expect($comment->inboundMessages)->toHaveCount(1)
        ->and($comment->inboundMessages->first()->id)->toBe($message->id);

    // Comment → Message (HasOne — "the first DM reply")
    expect($comment->inboundMessage)->not->toBeNull()
        ->and($comment->inboundMessage->id)->toBe($message->id);
});
