<?php

declare(strict_types=1);

use App\Livewire\Inbox\Index as InboxIndex;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Onboarding\ProgressService;
use Livewire\Livewire;

/**
 * Regression (2026-10-01): a team with every REQUIRED step done but no invited
 * friend sat at 89% forever, so selecting a page with no conversations showed
 * "Next: Invite a friend" instead of the empty inbox — user read it as stuck.
 */

/**
 * @return array{0: User, 1: Team, 2: Page}
 */
function makeOnboardedTeamWithEmptyPage(bool $aiConfigured = true): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create([
        'owner_id' => $user->id,
        'settings' => $aiConfigured ? ['onboarding_ai_seed' => ['system_prompt' => 'Sell bags.']] : [],
    ]);
    $team->forceFill(['onboarding_completed_at' => now()])->save();
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    $busyPage  = Page::factory()->create(['team_id' => $team->id, 'platform' => 'facebook']);
    $emptyPage = Page::factory()->create(['team_id' => $team->id, 'platform' => 'instagram']);

    $contact = Contact::create(['team_id' => $team->id, 'name' => 'Sara']);
    $conv = Conversation::create([
        'page_id' => $busyPage->id, 'team_id' => $team->id, 'platform' => 'facebook',
        'platform_conversation_id' => 'c1', 'contact_id' => $contact->id, 'status' => 'open',
    ]);
    Message::create(['conversation_id' => $conv->id, 'direction' => 'inbound', 'sender_type' => 'contact', 'content_type' => 'text', 'content' => 'hi']);

    return [$user->fresh(), $team->fresh(), $emptyPage];
}

it('treats onboarding as complete when only the optional invite step is left', function () {
    [, $team] = makeOnboardedTeamWithEmptyPage();
    $progress = app(ProgressService::class);

    expect($progress->percentComplete($team))->toBe(89)
        ->and($progress->requiredComplete($team))->toBeTrue();
});

it('shows the normal empty inbox, not the onboarding panel, for an empty page once required steps are done', function () {
    [$user, , $emptyPage] = makeOnboardedTeamWithEmptyPage();
    $this->actingAs($user);

    Livewire::test(InboxIndex::class)
        ->set('pageId', $emptyPage->id)
        ->assertSee('No conversations yet')
        ->assertDontSee('Invite a friend');
});

it('still shows the onboarding panel while a required step is unfinished', function () {
    [$user, , $emptyPage] = makeOnboardedTeamWithEmptyPage(aiConfigured: false);
    $this->actingAs($user);

    Livewire::test(InboxIndex::class)
        ->set('pageId', $emptyPage->id)
        ->assertSee('Getting started')
        ->assertDontSee('No conversations yet');
});
