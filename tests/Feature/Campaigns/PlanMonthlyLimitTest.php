<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Phase 2 of the 5-phase load-management plan (docs/OT1_LIMITS.md §11).
 *
 * Guarantees the monthly-campaign gate works per plan and the 30-day rolling
 * window resets old campaigns out.
 */

function makeLimitTeam(string $plan = 'free'): array
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'              => 'Limit Test Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $user->id,
        'subscription_plan' => $plan,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team->fresh()];
}

function makeCampaign(Team $team, int $daysAgo = 0): Campaign
{
    $c = Campaign::create([
        'team_id'          => $team->id,
        'created_by'       => $team->owner_id,
        'name'             => 'test ' . Str::random(4),
        'type'             => 'promotion',
        'platform'         => 'whatsapp',
        'message_template' => 'hi',
        'status'           => 'draft',
    ]);
    if ($daysAgo > 0) {
        Campaign::where('id', $c->id)->update(['created_at' => now()->subDays($daysAgo)]);
    }
    return $c->fresh();
}

it('defaults to free-plan cap of 1 campaign per rolling 30 days', function () {
    [, $team] = makeLimitTeam(plan: 'free');

    expect($team->monthlyCampaignLimit())->toBe(1);
    expect($team->canCreateCampaign())->toBeTrue();
    expect($team->campaignsRemainingThisMonth())->toBe(1);

    makeCampaign($team);

    expect($team->fresh()->canCreateCampaign())->toBeFalse();
    expect($team->fresh()->campaignsRemainingThisMonth())->toBe(0);
});

it('applies starter, pro, and enterprise caps from config', function () {
    foreach (['starter' => 5, 'pro' => 25, 'enterprise' => PHP_INT_MAX] as $plan => $expected) {
        [, $team] = makeLimitTeam(plan: $plan);
        expect($team->monthlyCampaignLimit())->toBe($expected);
    }
});

it('does NOT count campaigns older than 30 days (rolling window)', function () {
    [, $team] = makeLimitTeam(plan: 'free');
    makeCampaign($team, daysAgo: 31);

    // Only campaign is 31 days old — falls outside rolling 30-day window.
    expect($team->fresh()->campaignsCreatedThisMonth())->toBe(0);
    expect($team->fresh()->canCreateCampaign())->toBeTrue();
});

it('treats an unknown plan slug as free-tier fallback', function () {
    // Belt-and-suspenders: if a future plan slug ships to prod before its
    // config entry lands, we fall back to the free cap instead of throwing.
    [, $team] = makeLimitTeam(plan: 'brand-new-plan-not-in-config');

    expect($team->fresh()->monthlyCampaignLimit())->toBe(1);
});

it('pro plan holds 25 campaigns then blocks the 26th', function () {
    [, $team] = makeLimitTeam(plan: 'pro');

    for ($i = 0; $i < 25; $i++) {
        makeCampaign($team);
    }

    expect($team->fresh()->canCreateCampaign())->toBeFalse();
    expect($team->fresh()->campaignsRemainingThisMonth())->toBe(0);
});
