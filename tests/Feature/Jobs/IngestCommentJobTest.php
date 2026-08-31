<?php

declare(strict_types=1);

use App\Jobs\ClassifyCommentJob;
use App\Jobs\IngestCommentJob;
use App\Jobs\SendAiCommentReplyJob;
use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Route the hot-path cache to the array driver during tests.
    config(['comments.hot_cache_store' => 'array']);
    Cache::store('array')->flush();
});

/**
 * @return array{Page, AiConfig}
 */
function makePageWithCommentConfig(string $platform = 'facebook', array $settingsOverrides = []): array
{
    $user    = User::factory()->create();
    $team    = Team::factory()->create(['owner_id' => $user->id]);
    $account = ConnectedAccount::factory()->create(['team_id' => $team->id, 'platform' => $platform]);
    $page    = Page::factory()->create([
        'team_id'              => $team->id,
        'connected_account_id' => $account->id,
        'platform'             => $platform,
        'platform_page_id'     => 'PAGE_' . $platform,
        'is_active'            => true,
    ]);
    $config = AiConfig::create([
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

    return [$page->refresh(), $config];
}

function fakePostCreatedTimeResponse(): void
{
    Http::fake([
        'graph.facebook.com/v21.0/*' => Http::response(['created_time' => now()->subHour()->toIso8601String()], 200),
    ]);
}

function parsedComment(string $platform = 'facebook', string $commentId = 'c1', array $overrides = []): array
{
    return array_merge([
        'platform'              => $platform,
        'platform_comment_id'   => $commentId,
        'platform_post_id'      => 'POST_1',
        'parent_comment_id'     => null,
        'commenter_platform_id' => 'USER_1',
        'commenter_name'        => 'Ada',
        'text'                  => 'How much?',
        'received_at'           => now(),
    ], $overrides);
}

function runIngest(array $parsed, string $platformPageId): void
{
    (new IngestCommentJob($parsed, $platformPageId))->handle(
        app(\App\Services\Comments\PostCreationTimeCache::class),
        app(\App\Services\Comments\CommentFilterService::class),
    );
}

it('stores a comment and dispatches SendAiCommentReplyJob when mode=all', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook');

    runIngest(parsedComment(), $page->platform_page_id);

    expect(Comment::count())->toBe(1);
    expect(Comment::first()->decision)->toBeNull();
    Queue::assertPushed(SendAiCommentReplyJob::class);
});

it('is idempotent on redelivery (dedupe)', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook');

    runIngest(parsedComment(), $page->platform_page_id);
    runIngest(parsedComment(), $page->platform_page_id);

    expect(Comment::count())->toBe(1);
});

it('filters reply-to-comment as filtered_reply', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook');

    runIngest(parsedComment(overrides: ['parent_comment_id' => 'c0']), $page->platform_page_id);

    expect(Comment::first()->decision)->toBe(Comment::DECISION_FILTERED_REPLY);
    Queue::assertNotPushed(SendAiCommentReplyJob::class);
});

it('rate-limits after cap and stores rate_limited row', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook', [
        'max_ai_replies_per_post_per_day' => 2,
    ]);

    foreach (['c1', 'c2', 'c3'] as $cid) {
        runIngest(parsedComment(commentId: $cid), $page->platform_page_id);
    }

    expect(Comment::where('decision', Comment::DECISION_RATE_LIMITED)->count())->toBe(1);
    Queue::assertPushed(SendAiCommentReplyJob::class, 2);
});

it('dispatches classifier when reply_mode is questions_and_complaints', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook', [
        'reply_mode' => AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS,
    ]);

    runIngest(parsedComment(), $page->platform_page_id);

    Queue::assertPushed(ClassifyCommentJob::class);
    Queue::assertNotPushed(SendAiCommentReplyJob::class);
});

it('filters custom_keywords miss', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook', [
        'reply_mode'     => AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS,
        'reply_keywords' => ['price'],
    ]);

    runIngest(parsedComment(overrides: ['text' => 'lovely photo']), $page->platform_page_id);

    expect(Comment::first()->decision)->toBe(Comment::DECISION_FILTERED_KEYWORD);
});
