<?php

declare(strict_types=1);

use App\Models\AiConfig;
use App\Models\ConnectedAccount;
use App\Models\OnboardingRequest;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Analytics\OnboardingFunnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Phase E — OnboardingFunnel service unit tests.
 *
 * The funnel is cohort-scoped: a team is only counted in stage X if it
 * ALSO signed up inside the window. Everything below tests that invariant
 * plus the concrete stage detectors.
 */

function makeTeam(array $overrides = []): Team
{
    $user = User::factory()->create();
    $team = Team::create(array_merge([
        'name'     => 'Team '.Str::random(6),
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ], $overrides));
    return $team->fresh();
}

it('returns an all-zero funnel when there are no teams in the window', function () {
    $funnel = (new OnboardingFunnel())->forWindow(7);

    expect($funnel['window_days'])->toBe(7);
    expect($funnel['stages'])->toHaveCount(count(OnboardingFunnel::STAGES));

    foreach ($funnel['stages'] as $stage) {
        expect($stage['count'])->toBe(0);
        expect($stage['pct'])->toBe(0);
    }
});

it('counts only teams that signed up inside the window', function () {
    $old = makeTeam();
    $old->forceFill(['created_at' => now()->subDays(45)])->save();

    $recent = makeTeam();

    $funnel = (new OnboardingFunnel())->forWindow(7);

    $signup = collect($funnel['stages'])->firstWhere('key', 'signed_up');
    expect($signup['count'])->toBe(1);
});

it('detects onboarding_step_1 via business_type and step_3 via ai_config', function () {
    $team = makeTeam(['business_type' => 'ecommerce']);

    // ai_configs.page_id is NOT NULL on this branch (nullable migration lives
    // on a different feature branch), so seed a minimal Page for the FK.
    $account = ConnectedAccount::create([
        'team_id'          => $team->id,
        'platform'         => 'facebook',
        'platform_user_id' => 'fb-'.Str::random(6),
        'name'             => 'fb owner',
    ]);
    $page = Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => $team->id,
        'platform'             => 'facebook',
        'platform_page_id'     => 'fbp-'.Str::random(6),
        'name'                 => 'Page',
        'is_active'            => true,
    ]);

    AiConfig::create([
        'team_id'       => $team->id,
        'page_id'       => $page->id,
        'system_prompt' => 'You are the AI for Acme.',
    ]);

    $funnel = (new OnboardingFunnel())->forWindow(30);

    $byKey = collect($funnel['stages'])->keyBy('key');
    expect($byKey['onboarding_step_1']['count'])->toBe(1);
    expect($byKey['onboarding_step_3']['count'])->toBe(1);
    expect($byKey['onboarding_completed']['count'])->toBe(0);
});

it('detects onboarding_completed and plan_picked', function () {
    $team = makeTeam([
        'onboarding_completed_at' => now(),
        'plan_trial_started_at'   => now(),
        'plan_status'             => 'paid',
    ]);

    $funnel = (new OnboardingFunnel())->forWindow(30);
    $byKey = collect($funnel['stages'])->keyBy('key');

    expect($byKey['onboarding_completed']['count'])->toBe(1);
    expect($byKey['plan_picked']['count'])->toBe(1);
    expect($byKey['paid']['count'])->toBe(1);
});

it('detects connection_completed via either an onboarding_request or an active page', function () {
    $viaRequest = makeTeam();
    OnboardingRequest::create([
        'team_id'              => $viaRequest->id,
        'requested_by_user_id' => $viaRequest->owner_id,
        'platform'             => 'facebook',
        'business_name'        => 'Acme',
        'page_url'             => 'https://facebook.com/acme',
        'status'               => 'completed',
    ]);

    $viaPage = makeTeam();
    $account = ConnectedAccount::create([
        'team_id'          => $viaPage->id,
        'platform'         => 'whatsapp',
        'platform_user_id' => 'wa-user-'.Str::random(6),
        'name'             => 'WA owner',
    ]);
    Page::create([
        'connected_account_id' => $account->id,
        'team_id'              => $viaPage->id,
        'platform'             => 'whatsapp',
        'platform_page_id'     => 'wa-'.Str::random(6),
        'name'                 => 'WA line',
        'is_active'            => true,
    ]);

    $funnel = (new OnboardingFunnel())->forWindow(30);
    $byKey = collect($funnel['stages'])->keyBy('key');

    expect($byKey['connection_completed']['count'])->toBe(2);
    expect($byKey['connection_requested']['count'])->toBe(1);
});

it('reads first_real_message and first_ai_reply from team settings', function () {
    makeTeam(); // no flags — should be 0

    $active = makeTeam([
        'settings' => [
            'first_real_message_at' => now()->toIso8601String(),
            'first_ai_reply_at'     => now()->toIso8601String(),
        ],
    ]);

    $funnel = (new OnboardingFunnel())->forWindow(30);
    $byKey = collect($funnel['stages'])->keyBy('key');

    expect($byKey['first_real_message']['count'])->toBe(1);
    expect($byKey['first_ai_reply']['count'])->toBe(1);
});

it('computes drop_pct relative to the previous stage', function () {
    // Signup: 4 teams, 2 with business_type, 1 completed
    for ($i = 0; $i < 4; $i++) {
        makeTeam();
    }
    makeTeam(['business_type' => 'ecommerce']);
    makeTeam([
        'business_type'           => 'services',
        'onboarding_completed_at' => now(),
    ]);

    $funnel = (new OnboardingFunnel())->forWindow(30);
    $byKey = collect($funnel['stages'])->keyBy('key');

    // 6 signups → 2 step_1 → drop from signup ~66%
    expect($byKey['signed_up']['count'])->toBe(6);
    expect($byKey['onboarding_step_1']['count'])->toBe(2);
    expect($byKey['onboarding_step_1']['drop_pct'])->toBe(67);
    expect($byKey['onboarding_step_1']['pct'])->toBe(33);
});

it('falls back to a 30d window when given a non-positive number of days', function () {
    $funnel = (new OnboardingFunnel())->forWindow(0);
    expect($funnel['window_days'])->toBe(30);
});
