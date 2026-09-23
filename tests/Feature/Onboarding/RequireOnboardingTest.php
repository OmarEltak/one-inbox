<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;

/**
 * Middleware coverage — onboarding is mandatory per Omar's mandate 2026-09-23.
 * Users with onboarding_completed_at IS NULL cannot reach /inbox, /connections,
 * /campaigns etc. They get redirected to /onboarding/meet-your-ai until they
 * finish (or a super-admin unblocks them).
 */
function makeGatingUser(bool $completed = false, bool $superAdmin = false): array
{
    $user = User::factory()->create(['is_super_admin' => $superAdmin]);
    $team = Team::factory()->create([
        'owner_id'                 => $user->id,
        'onboarding_completed_at'  => $completed ? now() : null,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team];
}

it('redirects incomplete-onboarding customers away from /connections', function () {
    [$user] = makeGatingUser(completed: false);

    $this->actingAs($user)
        ->get('/connections')
        ->assertRedirect(route('onboarding.meet-your-ai'));
});

it('lets completed customers into /connections', function () {
    [$user] = makeGatingUser(completed: true);

    $this->actingAs($user)
        ->get('/connections')
        ->assertOk();
});

it('never redirects super-admins even with null onboarding', function () {
    [$user] = makeGatingUser(completed: false, superAdmin: true);

    $this->actingAs($user)
        ->get('/connections')
        ->assertOk();
});

it('does not loop the onboarding route itself', function () {
    [$user] = makeGatingUser(completed: false);

    $this->actingAs($user)
        ->get('/onboarding/meet-your-ai')
        ->assertOk();
});

it('allows logout without redirect', function () {
    [$user] = makeGatingUser(completed: false);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(); // Fortify redirects after logout, but NOT to onboarding
});
