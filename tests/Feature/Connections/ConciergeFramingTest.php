<?php

declare(strict_types=1);

use App\Livewire\Connections\Index as ConnectionsIndex;
use App\Models\OnboardingRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Concierge framing tests — pins the behaviour described in
 * tasks/onboarding-activation-plan.md Phase B and CLAUDE.md pin #1.
 *
 * Non-negotiable: while META_APP_VERIFIED=false, no direct Facebook OAuth
 * button may appear on the Connections page for regular customers. It
 * silently dead-ends at Meta's callback.
 */

/**
 * @return array{0: User, 1: Team}
 */
function makeConciergeUser(bool $superAdmin = false): array
{
    $user = User::factory()->create(['is_super_admin' => $superAdmin]);
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team];
}

beforeEach(function () {
    // Ensure META_APP_ID is set so the "not configured" branch never wins
    // in the connect-button block — we're testing the OAuth-vs-concierge
    // branch specifically, not the missing-env branch.
    config(['services.meta.app_id' => 'test-app-id']);
});

it('hides the direct Facebook OAuth button when META_APP_VERIFIED is false', function () {
    config(['services.meta.app_verified' => false]);

    [$user] = makeConciergeUser();
    $this->actingAs($user);

    $component = Livewire::test(ConnectionsIndex::class);

    expect($component->instance()->usesConciergeFlow())->toBeTrue();

    $component
        ->assertDontSeeHtml(route('connections.facebook.redirect'))
        ->assertDontSeeHtml(route('connections.instagram-via-facebook.redirect'))
        ->assertDontSeeHtml(route('connections.instagram.redirect'))
        ->assertSee('Request Facebook connection');
});

it('shows the direct Facebook OAuth button when META_APP_VERIFIED is true', function () {
    config(['services.meta.app_verified' => true]);

    [$user] = makeConciergeUser();
    $this->actingAs($user);

    $component = Livewire::test(ConnectionsIndex::class);

    expect($component->instance()->usesConciergeFlow())->toBeFalse();

    $component
        ->assertSeeHtml(route('connections.facebook.redirect'))
        ->assertSeeHtml(route('connections.instagram-via-facebook.redirect'))
        ->assertSee('Or request concierge connection');
});

it('treats super-admin users as verified regardless of META_APP_VERIFIED', function () {
    config(['services.meta.app_verified' => false]);

    [$user] = makeConciergeUser(superAdmin: true);
    $this->actingAs($user);

    $component = Livewire::test(ConnectionsIndex::class);

    expect($component->instance()->usesConciergeFlow())->toBeFalse();

    $component->assertSeeHtml(route('connections.facebook.redirect'));
});

it('returns null median turnaround when there are zero completed requests', function () {
    config(['services.meta.app_verified' => false]);

    [$user] = makeConciergeUser();
    $this->actingAs($user);

    $component = Livewire::test(ConnectionsIndex::class);

    expect($component->instance()->medianConciergeTurnaroundMinutes())->toBeNull();

    $component->assertSee('usually under 10 min during business hours');
});

it('computes the median turnaround from completed OnboardingRequests', function () {
    config(['services.meta.app_verified' => false]);

    [$user, $team] = makeConciergeUser();
    $this->actingAs($user);

    // Three completed requests: 5 min, 10 min, 30 min → median 10 min.
    foreach ([5, 10, 30] as $minutes) {
        OnboardingRequest::create([
            'team_id'              => $team->id,
            'requested_by_user_id' => $user->id,
            'platform'             => 'facebook',
            'business_name'        => 'Sample Co',
            'contact_email'        => 'ops@example.com',
            'status'               => OnboardingRequest::STATUS_COMPLETED,
            'completed_at'         => now(),
            'created_at'           => now()->subMinutes($minutes),
            'updated_at'           => now(),
        ]);
    }

    $component = Livewire::test(ConnectionsIndex::class);

    expect($component->instance()->medianConciergeTurnaroundMinutes())->toBe(10);
});

it('still lets a customer submit a concierge request while META_APP_VERIFIED is false', function () {
    config(['services.meta.app_verified' => false]);

    [$user, $team] = makeConciergeUser();
    $this->actingAs($user);

    Livewire::test(ConnectionsIndex::class)
        ->call('openRequestForm', 'facebook')
        ->set('requestBusinessName', 'Acme Widgets')
        ->set('requestContactEmail', 'owner@acme.test')
        ->set('requestPageUrl', 'https://facebook.com/acme')
        ->call('submitOnboardingRequest');

    expect(OnboardingRequest::where('team_id', $team->id)->count())->toBe(1);
});
