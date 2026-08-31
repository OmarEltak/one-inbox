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

it('persists comment settings and stamps enabled_at on first enable', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_enabled', true)
        ->set('comment_reply_mode', AiConfig::COMMENT_REPLY_ALL)
        ->set('comment_dm_mode', AiConfig::COMMENT_DM_ALWAYS)
        ->set('comment_reply_instructions', 'keep it short')
        ->set('comment_max_replies_per_post_per_day', 30)
        ->call('saveConfig')
        ->assertHasNoErrors();

    $config = AiConfig::where('page_id', $page->id)->firstOrFail();
    $settings = $config->comment_settings;

    expect($settings['enabled'])->toBeTrue();
    expect($settings['enabled_at'])->not->toBeNull();
    expect($settings['reply_mode'])->toBe(AiConfig::COMMENT_REPLY_ALL);
    expect($settings['dm_mode'])->toBe(AiConfig::COMMENT_DM_ALWAYS);
    expect($settings['reply_instructions'])->toBe('keep it short');
    expect($settings['max_ai_replies_per_post_per_day'])->toBe(30);
});

it('preserves enabled_at across subsequent saves', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    $this->actingAs($user);

    $component = Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_enabled', true)
        ->call('saveConfig');

    $firstStamp = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['enabled_at'];
    expect($firstStamp)->not->toBeNull();

    sleep(1);
    $component->call('saveConfig');

    $secondStamp = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['enabled_at'];
    expect($secondStamp)->toBe($firstStamp);
});

it('clamps the per-post reply cap to the allowed range', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_max_replies_per_post_per_day', 999)
        ->call('saveConfig');

    expect(AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['max_ai_replies_per_post_per_day'])
        ->toBe(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_max_replies_per_post_per_day', 0)
        ->call('saveConfig');

    expect(AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['max_ai_replies_per_post_per_day'])
        ->toBe(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN);
});

it('coerces unknown enum values to safe defaults', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_reply_mode', 'yolo')
        ->set('comment_dm_mode', 'nope')
        ->set('comment_scope', 'huh')
        ->call('saveConfig');

    $settings = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings;
    expect($settings['reply_mode'])->toBe(AiConfig::COMMENT_REPLY_OFF);
    expect($settings['dm_mode'])->toBe(AiConfig::COMMENT_DM_OFF);
    expect($settings['scope'])->toBe(AiConfig::COMMENT_SCOPE_FUTURE_ONLY);
});

it('trims and drops empty keywords', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_reply_keywords', ['  price  ', '', 'cost'])
        ->set('comment_dm_keywords', [''])
        ->call('saveConfig');

    $settings = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings;
    expect($settings['reply_keywords'])->toBe(['price', 'cost']);
    expect($settings['dm_keywords'])->toBe([]);
});

it('truncates reply instructions to 500 characters', function () {
    [$user, $team, $page] = makeUserWithPage('facebook');
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_reply_instructions', str_repeat('a', 600))
        ->call('saveConfig');

    $settings = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings;
    expect(mb_strlen($settings['reply_instructions']))->toBe(500);
});
