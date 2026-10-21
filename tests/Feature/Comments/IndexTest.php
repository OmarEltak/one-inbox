<?php

declare(strict_types=1);

use App\Livewire\Comments\Index;
use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

/*
 * Feature coverage for the /comments screen.
 *
 * The screen is the only visibility operators have into the AI's public
 * comment activity, so correctness here is load-bearing:
 *   - cross-tenant isolation MUST be strict (CLAUDE.md pin — teams are
 *     the primary isolation boundary)
 *   - failed public replies MUST render in-view, NOT get hidden
 *   - filter chips MUST map 1:1 to the semantics in Index::applyChip()
 */

/**
 * Mark a team's onboarding as complete so RequireOnboarding middleware
 * doesn't bounce the request to /onboarding/meet-your-ai before the
 * route we care about runs.
 */
function completeOnboarding(Team $team): Team
{
    $team->forceFill(['onboarding_completed_at' => now()])->save();
    return $team->fresh();
}

/**
 * Create an active FB page under the given team with a fresh post.
 */
function commentsPage(Team $team, string $platform = 'facebook', string $name = 'Brand Page'): Page
{
    return Page::factory()->create([
        'team_id'          => $team->id,
        'platform'         => $platform,
        'name'             => $name,
        'platform_page_id' => 'pid-' . Str::random(8),
        'is_active'        => true,
    ]);
}

function commentsPost(Page $page): PagesPost
{
    return PagesPost::factory()->create(['page_id' => $page->id]);
}

/**
 * Shortcut to make a Comment row with sensible defaults.
 */
function makeComment(Page $page, PagesPost $post, array $overrides = []): Comment
{
    return Comment::factory()->create(array_merge([
        'page_id'               => $page->id,
        'pages_post_id'         => $post->id,
        'platform_comment_id'   => 'c-' . Str::random(10),
        'commenter_name'        => 'Jane Doe',
        'commenter_platform_id' => 'u-' . Str::random(6),
        'text'                  => 'How much is this?',
        'received_at'           => now()->subMinutes(5),
    ], $overrides));
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('comments.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated user with an active FB page can visit the comments screen', function () {
    [$user, $team] = makeUserWithTeam();
    completeOnboarding($team);
    $page          = commentsPage($team);
    $post          = commentsPost($page);
    makeComment($page, $post, [
        'decision'       => Comment::DECISION_REPLIED,
        'reply_text'     => 'Thanks for asking! The price is $29.',
        'graph_reply_id' => 'reply-123',
    ]);

    $this->actingAs($user);
    $response = $this->get(route('comments.index'));

    $response->assertOk();
    $response->assertSee('Jane Doe');
    $response->assertSee('Thanks for asking!');
    $response->assertSee('How much');
});

test('team with no active FB/IG pages is redirected to connections (require.connection middleware)', function () {
    // require.connection redirects when the team has zero active pages at all,
    // once onboarding is marked complete (otherwise RequireOnboarding runs first).
    [$user, $team] = makeUserWithTeam();
    completeOnboarding($team);
    $this->actingAs($user);

    $response = $this->get(route('comments.index'));

    $response->assertRedirect(route('connections.index'));
});

test('team with an active non-FB/IG page shows the empty state (no comments) rather than other teams data', function () {
    // Team has an email page (active) so require.connection passes, but no
    // comments belong to the team's pages — screen must render empty state.
    [$user, $team] = makeUserWithTeam();
    completeOnboarding($team);
    makeEmailPage($team);

    $this->actingAs($user);
    $response = $this->get(route('comments.index'));

    $response->assertOk();
    $response->assertSee(__('No comments match these filters yet'));
});

test('public-reply-failed chip filters down to only comments with graph_error and no graph_reply_id', function () {
    [$user, $team] = makeUserWithTeam();
    $page          = commentsPage($team);
    $post          = commentsPost($page);

    $ok = makeComment($page, $post, [
        'decision'       => Comment::DECISION_REPLIED,
        'reply_text'     => 'Hi!',
        'graph_reply_id' => 'r-ok',
    ]);
    $failed = makeComment($page, $post, [
        'decision'    => Comment::DECISION_ERROR_GRAPH_API,
        'graph_error' => ['message' => '(#200) pages_read_user_content not available'],
    ]);
    $dmOnly = makeComment($page, $post, [
        'decision'   => Comment::DECISION_DM_ONLY,
        'dm_sent_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('chip', Index::CHIP_PUBLIC_FAILED)
        ->assertSee($failed->commenter_name)
        ->assertSee('pages_read_user_content')
        ->assertDontSee('r-ok');
});

test('DM-sent chip filters down to only comments with dm_sent_at not null', function () {
    [$user, $team] = makeUserWithTeam();
    $page          = commentsPage($team);
    $post          = commentsPost($page);

    $noDm = makeComment($page, $post, [
        'commenter_name' => 'NoDm Person',
        'decision'       => Comment::DECISION_REPLIED,
        'graph_reply_id' => 'r-1',
    ]);
    $withDm = makeComment($page, $post, [
        'commenter_name' => 'WithDm Person',
        'decision'       => Comment::DECISION_REPLIED,
        'graph_reply_id' => 'r-2',
        'dm_sent_at'     => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('chip', Index::CHIP_DM_SENT)
        ->assertSee('WithDm Person')
        ->assertDontSee('NoDm Person');
});

test('filtered-out chip shows filtered_* decisions only', function () {
    [$user, $team] = makeUserWithTeam();
    $page          = commentsPage($team);
    $post          = commentsPost($page);

    $filtered = makeComment($page, $post, [
        'commenter_name' => 'Filtered Phil',
        'decision'       => Comment::DECISION_FILTERED_KEYWORD,
    ]);
    $replied = makeComment($page, $post, [
        'commenter_name' => 'Replied Rita',
        'decision'       => Comment::DECISION_REPLIED,
        'graph_reply_id' => 'r-xy',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('chip', Index::CHIP_FILTERED)
        ->assertSee('Filtered Phil')
        ->assertDontSee('Replied Rita');
});

test('pagination surfaces page 2 of results', function () {
    [$user, $team] = makeUserWithTeam();
    $page          = commentsPage($team);
    $post          = commentsPost($page);

    // 30 total — 25 per page => page 2 has 5.
    for ($i = 0; $i < 30; $i++) {
        makeComment($page, $post, [
            'commenter_name' => "Commenter {$i}",
            'text'           => "Comment number {$i}",
            // Deterministic order so the oldest 5 land on page 2.
            'received_at'    => now()->subMinutes($i),
        ]);
    }

    $component = Livewire::actingAs($user)->test(Index::class);
    $component->assertSee('Commenter 0');
    // Older comments (indices 25-29) are on page 2.
    $component->call('setPage', 2)
        ->assertSee('Commenter 29')
        ->assertDontSee('Commenter 0');
});

test('comments from another team do not leak into this teams view (tenant isolation)', function () {
    [$aliceUser, $aliceTeam] = makeUserWithTeam();
    [$bobUser,   $bobTeam]   = makeUserWithTeam();

    $alicePage = commentsPage($aliceTeam, 'facebook', 'Alice Brand');
    $alicePost = commentsPost($alicePage);
    makeComment($alicePage, $alicePost, [
        'commenter_name' => 'Alice-only Commenter',
        'text'           => 'secret alice comment',
    ]);

    $bobPage = commentsPage($bobTeam, 'facebook', 'Bob Brand');
    $bobPost = commentsPost($bobPage);
    makeComment($bobPage, $bobPost, [
        'commenter_name' => 'Bob-only Commenter',
        'text'           => 'secret bob comment',
    ]);

    // Alice's view must only see Alice's comment.
    Livewire::actingAs($aliceUser)
        ->test(Index::class)
        ->assertSee('Alice-only Commenter')
        ->assertDontSee('Bob-only Commenter')
        ->assertDontSee('secret bob comment');

    // Bob's view must only see Bob's comment.
    Livewire::actingAs($bobUser)
        ->test(Index::class)
        ->assertSee('Bob-only Commenter')
        ->assertDontSee('Alice-only Commenter')
        ->assertDontSee('secret alice comment');
});

test('pageFilter guard rejects a page_id that belongs to another team (no leak via URL)', function () {
    [$aliceUser, $aliceTeam] = makeUserWithTeam();
    [$_,         $bobTeam]   = makeUserWithTeam();

    $alicePage = commentsPage($aliceTeam, 'facebook', 'Alice Brand');
    $alicePost = commentsPost($alicePage);
    makeComment($alicePage, $alicePost, ['commenter_name' => 'Alice Visible']);

    $bobPage = commentsPage($bobTeam, 'facebook', 'Bob Brand');
    $bobPost = commentsPost($bobPage);
    makeComment($bobPage, $bobPost, ['commenter_name' => 'Bob Hidden']);

    // Alice tries to filter by Bob's page id (e.g. via a copy-pasted URL).
    // The component must ignore the filter and still only show Alice's rows.
    Livewire::actingAs($aliceUser)
        ->test(Index::class)
        ->set('pageFilter', (string) $bobPage->id)
        ->assertSee('Alice Visible')
        ->assertDontSee('Bob Hidden');
});
