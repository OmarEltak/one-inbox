<?php

declare(strict_types=1);

use App\Contracts\AiProviderInterface;
use App\Jobs\SendAiCommentReplyJob;
use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\PagesPost;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['comments.hot_cache_store' => 'array']);
    Cache::store('array')->flush();
});

function makeCommentForSend(array $settingsOverrides = []): Comment
{
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'owner_id'         => $user->id,
        'ai_enabled'       => true,
        'ai_credits_used'  => 0,
        'ai_credits_limit' => 1000,
    ]);
    $account = ConnectedAccount::factory()->create(['team_id' => $team->id, 'platform' => 'facebook']);
    $page = Page::factory()->create([
        'team_id'              => $team->id,
        'connected_account_id' => $account->id,
        'platform'             => 'facebook',
        'platform_page_id'     => 'PAGE_1',
        'is_active'            => true,
    ]);
    AiConfig::create([
        'page_id'              => $page->id,
        'team_id'              => $team->id,
        'business_description' => 'valid business description over ten chars',
        'is_active'            => true,
        'is_24_7'              => true,
        'comment_settings'     => array_merge(AiConfig::defaultCommentSettings(), array_merge([
            'enabled'    => true,
            'enabled_at' => now()->subDay()->toIso8601String(),
            'reply_mode' => AiConfig::COMMENT_REPLY_ALL,
        ], $settingsOverrides)),
    ]);
    $post = PagesPost::factory()->create(['page_id' => $page->id]);

    return Comment::factory()->create([
        'page_id'       => $page->id,
        'pages_post_id' => $post->id,
        'text'          => 'How much does this cost?',
    ]);
}

it('posts the AI reply publicly and stores decision=replied', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('Great question — DM us for pricing!');
    $comment = makeCommentForSend();

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_REPLIED);
    expect($comment->reply_text)->toBe('Great question — DM us for pricing!');
    expect($comment->graph_reply_id)->toBe('REPLY_1');
});

it('stores decision=error_ai when Nara returns empty string', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('');
    $comment = makeCommentForSend();

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_AI);
    Http::assertNothingSent();
});

it('stores decision=error_graph_api on 4xx and does not retry', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['error' => ['message' => 'deleted', 'code' => 100]], 400),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('hi!');
    $comment = makeCommentForSend();

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_ERROR_GRAPH_API);
    expect($comment->graph_error)->not->toBeNull();
});

it('sends a DM when dm_mode=always', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
        'graph.facebook.com/v21.0/*/messages*' => Http::response(['message_id' => 'M_1'], 200),
    ]);
    // Two generateText calls: one for public reply text, one for DM text.
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->twice()->andReturn('public reply', 'dm text');
    $comment = makeCommentForSend([
        'dm_mode' => AiConfig::COMMENT_DM_ALWAYS,
    ]);

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    Http::assertSentCount(2);
    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_REPLIED);
    expect($comment->reply_text)->toBe('public reply');
    expect($comment->dm_sent_at)->not->toBeNull();
    expect($comment->dm_graph_message_id)->toBe('M_1');
});

it('falls back to a top-level comment on the parent post when FB nested reply hits the pages_manage_engagement bundling bug', function () {
    // 2026-10-27: Brandk-style failure. Nested-reply attempt returns 400 with
    // code 200 / "pages_read_user_content" (Meta's canonical shape for "you
    // lack pages_manage_engagement"). The job must retry against
    // POST /{post-id}/comments with the commenter's name prefixed, and set
    // graph_reply_id + graph_error.fallback_used so /comments can show the
    // "replied as top-level" badge.
    Http::fake([
        'graph.facebook.com/v21.0/COMMENT_1/comments' => Http::response([
            'error' => ['code' => 200, 'type' => 'OAuthException', 'message' => '(#200) The permission(s) pages_read_user_content are not available. It could because either they are deprecated or need to be approved by App Review.'],
        ], 400),
        'graph.facebook.com/v21.0/POST_1/comments' => Http::response(['id' => 'FALLBACK_REPLY_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('Thanks for asking!');
    $comment = makeCommentForSend();
    // Pin the IDs used by the fakes so the matcher knows which URL to serve.
    $comment->update(['platform_comment_id' => 'COMMENT_1', 'commenter_name' => 'Jane']);
    $comment->pagesPost->update(['platform_post_id' => 'POST_1']);
    $comment->load('page', 'pagesPost');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_REPLIED)
        ->and($comment->graph_reply_id)->toBe('FALLBACK_REPLY_1')
        ->and($comment->graph_error['fallback_used'] ?? null)->toBe('top_level_post_comment');
    // The fallback MUST prefix the reply with the commenter name so it reads
    // as a direct response even though it is not nested under their comment.
    Http::assertSent(fn ($r) => str_ends_with($r->url(), 'POST_1/comments')
        && str_contains($r['message'], '@Jane')
        && str_contains($r['message'], 'Thanks for asking!'));
});

it('does NOT fall back to top-level when FB nested reply fails for a non-scope-bundling reason', function () {
    // Guard: only pages_read_user_content triggers the fallback. Any other 4xx
    // (e.g. comment deleted) must surface as DECISION_ERROR_GRAPH_API, not get
    // silently re-posted as a new comment on the parent post.
    Http::fake([
        'graph.facebook.com/v21.0/COMMENT_X/comments' => Http::response([
            'error' => ['code' => 100, 'message' => '(#100) Object with ID COMMENT_X does not exist'],
        ], 400),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('hi!');
    $comment = makeCommentForSend();
    $comment->update(['platform_comment_id' => 'COMMENT_X']);
    $comment->pagesPost->update(['platform_post_id' => 'POST_X']);
    $comment->load('page', 'pagesPost');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_GRAPH_API);
    // Must NOT have made the fallback call.
    Http::assertNotSent(fn ($r) => str_ends_with($r->url(), 'POST_X/comments'));
});

it('skips the top-level fallback when the comment is itself a reply to another comment (would land on wrong thread)', function () {
    // A fallback top-level comment on the parent POST would be nowhere near
    // the thread the reply was for. Better to surface the error than lie.
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response([
            'error' => ['code' => 200, 'message' => 'pages_read_user_content'],
        ], 400),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('hi!');
    $comment = makeCommentForSend();
    $comment->update([
        'platform_comment_id' => 'NESTED_COMMENT_1',
        'parent_comment_id'   => 'SOME_OTHER_COMMENT',  // => this comment is a reply, not top-level
    ]);
    $comment->pagesPost->update(['platform_post_id' => 'POST_Z']);
    $comment->load('page', 'pagesPost');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_GRAPH_API);
    // Fallback must NOT have been attempted — only ONE call was made.
    Http::assertSentCount(1);
});

it('routes Direct IG Login comment replies to graph.instagram.com, not graph.facebook.com', function () {
    // Regression: 2026-10-27 OWOWOKWKSJF comments failed with error code 190
    // "Invalid OAuth access token - Cannot parse access token" because the
    // reply job hard-coded graph.facebook.com but Direct IG Login tokens are
    // minted by graph.instagram.com's auth server and are not interchangeable.
    // Pages with metadata.auth_type='instagram_business' must use graph.instagram.com
    // for both the public reply AND the DM — same convention as
    // FacebookPlatform::fetchAndStoreMessages (host switch at line 1261 of that file).
    Http::fake([
        'graph.instagram.com/v21.0/*/replies'  => Http::response(['id' => 'IG_REPLY_1'], 200),
        'graph.instagram.com/v21.0/*/messages*' => Http::response(['message_id' => 'IG_M_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->twice()->andReturn('public reply', 'dm text');

    $comment = makeCommentForSend([
        'dm_mode' => AiConfig::COMMENT_DM_ALWAYS,
    ]);
    // Flip the page into a Direct IG Login page by matching the convention
    // from FacebookPlatform::handleInstagramCallback (auth_type sentinel).
    $comment->page->update([
        'platform' => 'instagram',
        'metadata' => array_merge($comment->page->metadata ?? [], ['auth_type' => 'instagram_business']),
    ]);
    $comment->load('page');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    Http::assertSentCount(2);
    // Explicit negative: zero calls to graph.facebook.com (that's what prod hit by mistake).
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com'));
    expect($comment->fresh()->decision)->toBe(Comment::DECISION_REPLIED)
        ->and($comment->fresh()->graph_reply_id)->toBe('IG_REPLY_1')
        ->and($comment->fresh()->dm_graph_message_id)->toBe('IG_M_1');
});

it('skips DM when dm_mode=on_purchase_intent and no keyword match', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('reply');
    $comment = makeCommentForSend([
        'dm_mode'      => AiConfig::COMMENT_DM_ON_PURCHASE_INTENT,
        'dm_keywords'  => ['price'],
    ]);
    $comment->update(['text' => 'nice photo']);

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->dm_sent_at)->toBeNull();
});

it('stores decision=dm_only when public reply is blocked but DM succeeds', function () {
    // Real scenario: Meta blocks POST /{comment_id}/comments with the deprecated-scope
    // catch-22 (#200 pages_read_user_content), but the DM endpoint works.
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response([
            'error' => [
                'code' => 200,
                'type' => 'OAuthException',
                'message' => '(#200) The permission(s) pages_read_user_content are not available.',
            ],
        ], 400),
        'graph.facebook.com/v21.0/*/messages*' => Http::response(['message_id' => 'M_DM_1'], 200),
    ]);
    // Two generateText calls: one for public reply text, one for DM text.
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->twice()->andReturn('public: check your DM!', 'dm: hi there!');
    $comment = makeCommentForSend([
        'dm_mode' => AiConfig::COMMENT_DM_ALWAYS,
    ]);

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_DM_ONLY);
    expect($comment->dm_sent_at)->not->toBeNull();
    expect($comment->dm_graph_message_id)->toBe('M_DM_1');
    // The stored reply_text is the PUBLIC one; DM text is different and separately POSTed
    expect($comment->reply_text)->toBe('public: check your DM!');
    expect($comment->graph_reply_id)->toBeNull();
});

it('caps DM to the same commenter across posts per day', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
        'graph.facebook.com/v21.0/*/messages*' => Http::response(['message_id' => 'M_1'], 200),
    ]);
    // Pre-fill the DM cap counter for this commenter to the max so the next
    // attempt is blocked.
    $comment = makeCommentForSend([
        'dm_mode'                        => AiConfig::COMMENT_DM_ALWAYS,
        'max_dms_per_commenter_per_day'  => 1,
    ]);
    $key = "comments:dm-per-commenter:{$comment->page_id}:{$comment->commenter_platform_id}:" . now()->format('Y-m-d');
    Cache::store('array')->put($key, 1, now()->addDay()); // at cap already

    // Since DM is capped out and reply_mode=all still runs, only ONE Nara call (public).
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('generateText')->once()->andReturn('public only');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_REPLIED);
    expect($comment->dm_sent_at)->toBeNull();
    expect($comment->dm_graph_message_id)->toBeNull();
});

it('respects canDispatchAi and stores error_ai when team cannot dispatch', function () {
    $comment = makeCommentForSend();
    $comment->page->team->update(['ai_enabled' => false]);

    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldNotReceive('generateText');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_AI);
});
