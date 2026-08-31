<?php

declare(strict_types=1);

use App\Livewire\Settings\AiConfig as AiConfigComponent;
use App\Models\AiConfig;
use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

/**
 * @return array{0: User, 1: Team, 2: Page}
 */
function makeUserWithPage(string $platform = 'facebook'): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->update(['current_team_id' => $team->id]);

    $account = ConnectedAccount::factory()->create([
        'team_id'  => $team->id,
        'platform' => $platform,
    ]);

    $page = Page::factory()->create([
        'team_id'              => $team->id,
        'connected_account_id' => $account->id,
        'platform'             => $platform,
        'is_active'            => true,
    ]);

    return [$user, $team, $page];
}

it('hydrates comment settings defaults when no config exists', function () {
    [$user] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->assertSet('comment_enabled', false)
        ->assertSet('comment_reply_mode', AiConfig::COMMENT_REPLY_OFF)
        ->assertSet('comment_dm_mode', AiConfig::COMMENT_DM_OFF)
        ->assertSet('comment_scope', AiConfig::COMMENT_SCOPE_FUTURE_ONLY)
        ->assertSet('comment_max_replies_per_post_per_day', 20)
        ->assertSet('comment_reply_instructions', '');
});

it('hydrates comment settings from an existing config row', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    AiConfig::create([
        'page_id' => $page->id,
        'team_id' => $team->id,
        'business_description' => 'test business description over ten chars',
        'comment_settings' => [
            'enabled'                          => true,
            'enabled_at'                       => '2026-08-31T10:00:00+00:00',
            'reply_mode'                       => AiConfig::COMMENT_REPLY_ALL,
            'reply_keywords'                   => ['price'],
            'dm_mode'                          => AiConfig::COMMENT_DM_ALWAYS,
            'dm_keywords'                      => [],
            'reply_instructions'               => 'be brief',
            'scope'                            => AiConfig::COMMENT_SCOPE_ALL_POSTS,
            'max_ai_replies_per_post_per_day'  => 50,
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->assertSet('comment_enabled', true)
        ->assertSet('comment_reply_mode', AiConfig::COMMENT_REPLY_ALL)
        ->assertSet('comment_reply_keywords', ['price'])
        ->assertSet('comment_dm_mode', AiConfig::COMMENT_DM_ALWAYS)
        ->assertSet('comment_reply_instructions', 'be brief')
        ->assertSet('comment_scope', AiConfig::COMMENT_SCOPE_ALL_POSTS)
        ->assertSet('comment_max_replies_per_post_per_day', 50);
});

it('adds and removes comment reply keywords', function () {
    [$user] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->call('addCommentReplyKeyword')
        ->call('addCommentReplyKeyword')
        ->assertSet('comment_reply_keywords', ['', ''])
        ->set('comment_reply_keywords.0', 'price')
        ->set('comment_reply_keywords.1', 'cost')
        ->call('removeCommentReplyKeyword', 0)
        ->assertSet('comment_reply_keywords', ['cost']);
});

it('adds and removes comment DM keywords', function () {
    [$user] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->call('addCommentDmKeyword')
        ->set('comment_dm_keywords.0', 'buy')
        ->call('removeCommentDmKeyword', 0)
        ->assertSet('comment_dm_keywords', []);
});
