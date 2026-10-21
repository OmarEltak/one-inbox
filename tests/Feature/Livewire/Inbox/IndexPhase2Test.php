<?php

declare(strict_types=1);

use App\Livewire\Inbox\Index;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\PagesPost;
use App\Models\Team;
use Illuminate\Support\Str;
use Livewire\Livewire;

/*
 * Phase 2 — "From comments" filter chip + comment-source banner in the inbox.
 *
 * Covers:
 *   - the filter case 'from_comments' narrows to conversations whose first
 *     inbound Message carries source_comment_id
 *   - the sourceCommentForSelected computed returns the originating Comment
 *     for the selected conversation (and null when none)
 */

function phase2Page(Team $team, string $platform = 'facebook'): Page
{
    return Page::factory()->create([
        'team_id'          => $team->id,
        'platform'         => $platform,
        'name'             => 'Brand Page',
        'platform_page_id' => 'pid-' . Str::random(8),
        'is_active'        => true,
    ]);
}

function phase2ConversationWithComment(Team $team, Page $page, bool $withComment = true): array
{
    $contact = Contact::create([
        'team_id' => $team->id,
        'name'    => 'Jane Commenter',
    ]);

    $conversation = Conversation::create([
        'page_id'                  => $page->id,
        'team_id'                  => $team->id,
        'platform'                 => $page->platform,
        'platform_conversation_id' => 'conv-' . Str::random(8),
        'contact_id'               => $contact->id,
        'status'                   => 'open',
        'last_message_at'          => now(),
    ]);

    $comment = null;
    if ($withComment) {
        $post    = PagesPost::factory()->create(['page_id' => $page->id]);
        $comment = Comment::factory()->create([
            'page_id'               => $page->id,
            'pages_post_id'         => $post->id,
            'platform_comment_id'   => 'c-' . Str::random(10),
            'commenter_name'        => 'Jane Commenter',
            'commenter_platform_id' => 'u-' . Str::random(6),
            'text'                  => 'How much is this product?',
            'received_at'           => now()->subMinutes(5),
            'decision'              => Comment::DECISION_REPLIED,
            'reply_text'            => 'Great question — DM us!',
            'graph_reply_id'        => 'reply-' . Str::random(6),
        ]);
    }

    Message::create([
        'conversation_id'   => $conversation->id,
        'direction'         => 'inbound',
        'sender_type'       => 'contact',
        'content_type'      => 'text',
        'content'           => 'Hey, saw your post!',
        'source_comment_id' => $comment?->id,
        'platform_sent_at'  => now()->subMinutes(2),
    ]);

    return [$conversation->fresh(), $comment];
}

test('from_comments filter shows only conversations whose first inbound message has a source_comment_id', function () {
    [$user, $team] = makeUserWithTeam();
    $team->forceFill(['onboarding_completed_at' => now()])->save();
    $page = phase2Page($team);

    [$linkedConv, $comment] = phase2ConversationWithComment($team, $page, withComment: true);
    [$plainConv]            = phase2ConversationWithComment($team, $page, withComment: false);

    $component = Livewire::actingAs($user->fresh())
        ->test(Index::class)
        ->set('filter', 'from_comments');

    $ids = $component->instance()->conversations->pluck('id')->all();

    expect($ids)->toContain($linkedConv->id)
        ->and($ids)->not->toContain($plainConv->id);
});

test('sourceCommentForSelected returns the originating comment for the selected conversation', function () {
    [$user, $team] = makeUserWithTeam();
    $team->forceFill(['onboarding_completed_at' => now()])->save();
    $page = phase2Page($team);
    [$conversation, $comment] = phase2ConversationWithComment($team, $page, withComment: true);

    $component = Livewire::actingAs($user->fresh())
        ->test(Index::class)
        ->set('selectedConversationId', $conversation->id);

    $resolved = $component->instance()->sourceCommentForSelected;

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($comment->id)
        ->and($resolved->reply_text)->toBe('Great question — DM us!');
});

test('sourceCommentForSelected is null when the thread has no linked comment', function () {
    [$user, $team] = makeUserWithTeam();
    $team->forceFill(['onboarding_completed_at' => now()])->save();
    $page = phase2Page($team);
    [$conversation] = phase2ConversationWithComment($team, $page, withComment: false);

    $component = Livewire::actingAs($user->fresh())
        ->test(Index::class)
        ->set('selectedConversationId', $conversation->id);

    expect($component->instance()->sourceCommentForSelected)->toBeNull();
});
