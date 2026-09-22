<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Onboarding\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Phase C — progress detection service. Covers the 5-step onboarding checklist
// and percent-complete calculation, including cache behavior.

function makeProgressTeam(): array
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Progress Test Co',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team->fresh()];
}

function makeActivePage(Team $team): Page
{
    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'facebook',
        'platform_user_id' => 'fb-'.Str::random(8),
        'name'             => 'Test',
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
        'name'                 => 'Test Page',
        'page_access_token'    => encrypt('t'),
        'is_active'            => true,
    ]);
}

function makeConversationWithMessage(Team $team, string $direction = 'outbound'): Message
{
    $page = makeActivePage($team);
    $conversation = Conversation::create([
        'team_id'                  => $team->id,
        'page_id'                  => $page->id,
        'platform'                 => 'facebook',
        'platform_conversation_id' => 'conv-'.Str::random(8),
        'status'                   => 'open',
    ]);

    return Message::create([
        'conversation_id' => $conversation->id,
        'direction'       => $direction,
        'sender_type'     => 'user',
        'content_type'    => 'text',
        'content'         => 'hi',
    ]);
}

beforeEach(function () {
    // Fresh cache each test so cache hits from previous tests don't bleed in.
    Cache::flush();
});

it('reports 0% and all steps not-done for a fresh team', function () {
    [, $team] = makeProgressTeam();
    $svc = app(ProgressService::class);

    $steps = $svc->stepsFor($team);

    expect($steps)->toHaveCount(5);
    foreach ($steps as $step) {
        expect($step['done'])->toBeFalse();
    }
    expect($svc->percentComplete($team))->toBe(0);
});

it('marks Meet AI done when onboarding_completed_at is set', function () {
    [, $team] = makeProgressTeam();
    $team->forceFill(['onboarding_completed_at' => now()])->save();

    $svc = app(ProgressService::class);
    $steps = collect($svc->stepsFor($team))->keyBy('id');

    expect($steps[ProgressService::STEP_MEET_AI]['done'])->toBeTrue();
    expect($steps[ProgressService::STEP_CONFIGURE_AI]['done'])->toBeFalse();
});

it('marks Configure AI done when settings.onboarding_ai_seed is set (also flips Meet AI)', function () {
    [, $team] = makeProgressTeam();
    $team->forceFill([
        'settings' => ['onboarding_ai_seed' => ['system_prompt' => 'You are the AI for Acme.']],
    ])->save();

    $svc = app(ProgressService::class);
    $steps = collect($svc->stepsFor($team))->keyBy('id');

    expect($steps[ProgressService::STEP_CONFIGURE_AI]['done'])->toBeTrue();
    // Per source: seed present also satisfies meet-ai.
    expect($steps[ProgressService::STEP_MEET_AI]['done'])->toBeTrue();
});

it('marks Connect Page done when team has an active page', function () {
    [, $team] = makeProgressTeam();
    makeActivePage($team);

    $svc = app(ProgressService::class);
    $steps = collect($svc->stepsFor($team))->keyBy('id');

    expect($steps[ProgressService::STEP_CONNECT_PAGE]['done'])->toBeTrue();
});

it('does not mark Connect Page done for an inactive page', function () {
    [, $team] = makeProgressTeam();
    $page = makeActivePage($team);
    $page->forceFill(['is_active' => false])->save();

    $svc = app(ProgressService::class);
    $steps = collect($svc->stepsFor($team))->keyBy('id');

    expect($steps[ProgressService::STEP_CONNECT_PAGE]['done'])->toBeFalse();
});

it('marks Send Test Message done when team has any message', function () {
    [, $team] = makeProgressTeam();
    makeConversationWithMessage($team, 'outbound');

    $svc = app(ProgressService::class);
    $steps = collect($svc->stepsFor($team))->keyBy('id');

    expect($steps[ProgressService::STEP_SEND_TEST_MSG]['done'])->toBeTrue();
});

it('marks Invite Teammate done when a second member is attached', function () {
    [$owner, $team] = makeProgressTeam();
    $second = User::factory()->create();
    $team->members()->attach($second->id, ['role' => 'admin']);

    $svc = app(ProgressService::class);
    $steps = collect($svc->stepsFor($team))->keyBy('id');

    expect($steps[ProgressService::STEP_INVITE_TEAMMATE]['done'])->toBeTrue();
    expect($steps[ProgressService::STEP_INVITE_TEAMMATE]['optional'])->toBeTrue();
});

it('reports 100% when all 5 steps are done', function () {
    [$owner, $team] = makeProgressTeam();
    $team->forceFill([
        'onboarding_completed_at' => now(),
        'settings'                => ['onboarding_ai_seed' => ['system_prompt' => 'ok']],
    ])->save();
    makeConversationWithMessage($team, 'outbound');
    $second = User::factory()->create();
    $team->members()->attach($second->id, ['role' => 'admin']);

    $svc = app(ProgressService::class);
    expect($svc->percentComplete($team))->toBe(100);
});

it('reports 100% when all 4 mandatory + optional are done (weights sum properly)', function () {
    // With 4 mandatory (weight 1.0 each) + 1 optional (0.5) done: 4.5 / 4.5 = 100.
    [$owner, $team] = makeProgressTeam();
    $team->forceFill([
        'onboarding_completed_at' => now(),
        'settings'                => ['onboarding_ai_seed' => ['system_prompt' => 'ok']],
    ])->save();
    makeConversationWithMessage($team, 'outbound');
    $second = User::factory()->create();
    $team->members()->attach($second->id, ['role' => 'admin']);

    $svc = app(ProgressService::class);
    expect($svc->percentComplete($team))->toBe(100);
});

it('reports the correct weighted percent when only mandatory steps are done', function () {
    // 4 mandatory done, 1 optional not done → 4.0 / 4.5 ≈ 89%.
    [, $team] = makeProgressTeam();
    $team->forceFill([
        'onboarding_completed_at' => now(),
        'settings'                => ['onboarding_ai_seed' => ['system_prompt' => 'ok']],
    ])->save();
    makeConversationWithMessage($team, 'outbound');

    $svc = app(ProgressService::class);
    expect($svc->percentComplete($team))->toBe(89);
});

it('caches results — second call does not re-query the database', function () {
    [, $team] = makeProgressTeam();
    $svc = app(ProgressService::class);

    // First call populates cache.
    $first = $svc->stepsFor($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeTrue();

    // Mutate DB without invalidating cache (avoid observers by using raw DB).
    \Illuminate\Support\Facades\DB::table('teams')
        ->where('id', $team->id)
        ->update(['onboarding_completed_at' => now()]);

    // Second call still returns cached (Meet AI still false).
    $second = $svc->stepsFor($team->fresh());
    $meetAi = collect($second)->firstWhere('id', ProgressService::STEP_MEET_AI);
    expect($meetAi['done'])->toBeFalse();
});

it('nextStep returns the first unfinished step, or null when all done', function () {
    [, $team] = makeProgressTeam();
    $svc = app(ProgressService::class);

    // Fresh team: first unfinished is Meet AI.
    expect($svc->nextStep($team)['id'])->toBe(ProgressService::STEP_MEET_AI);

    // Complete every step.
    $team->forceFill([
        'onboarding_completed_at' => now(),
        'settings'                => ['onboarding_ai_seed' => ['system_prompt' => 'ok']],
    ])->save();
    makeConversationWithMessage($team, 'outbound');
    $second = User::factory()->create();
    $team->members()->attach($second->id, ['role' => 'admin']);
    $svc->forget($team);

    expect($svc->nextStep($team->fresh()))->toBeNull();
});

it('forget() clears the cached entry', function () {
    [, $team] = makeProgressTeam();
    $svc = app(ProgressService::class);

    $svc->stepsFor($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeTrue();

    $svc->forget($team);
    expect(Cache::has("onboarding.progress.{$team->id}"))->toBeFalse();
});
