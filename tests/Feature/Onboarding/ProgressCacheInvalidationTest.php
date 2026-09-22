<?php

declare(strict_types=1);

use App\Models\AiConfig;
use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Onboarding\ProgressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// Phase C — ProgressCacheObserver invalidates the cache when Team/Page/
// AiConfig/Message model events fire. Wired in AppServiceProvider::boot().

function makeCacheTeam(): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Cache Team',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();
    return $team->fresh();
}

function primeProgressCache(Team $team): void
{
    app(ProgressService::class)->stepsFor($team);
}

function makeActivePageForCache(Team $team): Page
{
    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'facebook',
        'platform_user_id' => 'fb-'.Str::random(8),
        'name'             => 'T',
        'access_token'     => encrypt('t'),
        'scopes'           => ['pages_messaging'],
        'is_active'        => true,
        'connected_at'     => now(),
    ]);
    return Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => $team->id,
        'platform'             => 'facebook',
        'platform_page_id'     => 'page-'.Str::random(8),
        'name'                 => 'P',
        'page_access_token'    => encrypt('t'),
        'is_active'            => true,
    ]);
}

beforeEach(function () {
    Cache::flush();
});

it('invalidates cache when Team is updated', function () {
    $team = makeCacheTeam();
    primeProgressCache($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeTrue();

    $team->forceFill(['name' => 'Renamed'])->save();

    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeFalse();
});

it('invalidates cache when a Page is created', function () {
    $team = makeCacheTeam();
    primeProgressCache($team);

    makeActivePageForCache($team);

    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeFalse();
});

it('invalidates cache when a Page is deleted', function () {
    $team = makeCacheTeam();
    $page = makeActivePageForCache($team);
    primeProgressCache($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeTrue();

    $page->delete();

    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeFalse();
});

it('invalidates cache when an AiConfig is updated', function () {
    $team = makeCacheTeam();
    $page = makeActivePageForCache($team);
    $config = AiConfig::create([
        'team_id'       => $team->id,
        'page_id'       => $page->id,
        'system_prompt' => 'original',
    ]);
    primeProgressCache($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeTrue();

    $config->forceFill(['system_prompt' => 'updated'])->save();

    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeFalse();
});

it('invalidates cache when a Message is created', function () {
    $team = makeCacheTeam();
    $page = makeActivePageForCache($team);
    $conv = Conversation::create([
        'team_id'                  => $team->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'conv-'.Str::random(8),
        'status'                   => 'open',
    ]);
    primeProgressCache($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeTrue();

    Message::create([
        'conversation_id' => $conv->id,
        'direction'       => 'outbound',
        'sender_type'     => 'user',
        'content_type'    => 'text',
        'content'         => 'hi',
    ]);

    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeFalse();
});
