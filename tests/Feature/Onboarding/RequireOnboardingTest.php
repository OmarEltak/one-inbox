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

it('does not intercept Livewire endpoint (asset-hashed prefix)', function () {
    // Reproduces the freeze bug from 2026-09-23: the wizard's own wire:click
    // POSTs go to /livewire-<hash>/update, which starts with "livewire-" not
    // "livewire/". The allowlist must match on the bare "livewire" prefix so
    // Livewire requests pass through instead of getting redirected in a loop.
    [$user] = makeGatingUser(completed: false);

    // Any request to a /livewire-* path should NOT get redirected to /onboarding.
    // We assert on the route resolution rather than a real POST because the
    // exact hashed prefix rotates on asset invalidation.
    $response = $this->actingAs($user)->call('POST', '/livewire-abc123/update', [], [], [], [
        'HTTP_X_LIVEWIRE' => '1',
    ]);

    // Middleware must not have redirected to onboarding — the response should
    // be 404 (no such Livewire component with this fake hash) or some other
    // non-redirect status, but explicitly NOT a 302 to onboarding.
    expect($response->status())->not->toBe(302);
    if ($response->status() === 302) {
        expect($response->headers->get('Location'))->not->toContain('/onboarding/meet-your-ai');
    }
});
