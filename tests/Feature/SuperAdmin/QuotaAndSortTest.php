<?php

declare(strict_types=1);

use App\Livewire\SuperAdmin\Customers;
use App\Livewire\SuperAdmin\Subscriptions;
use App\Models\Campaign;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create(['is_super_admin' => true])));

test('resetting the campaign quota restarts the rolling window', function () {
    [, $team] = makeUserWithTeam();
    // Phase RP (2026-10-06): Free plan now has a 0-campaign cap. Flip the
    // team to Starter so canCreateCampaign() can legitimately be true after
    // a reset — the free-plan branch would short-circuit before the reset
    // can be observed.
    $team->update(['subscription_plan' => 'starter']);
    Campaign::factory()->create(['team_id' => $team->id]);
    expect($team->fresh()->campaignsCreatedThisMonth())->toBe(1);

    Livewire::test(Subscriptions::class)->call('resetCampaignQuota', $team->id);

    expect($team->fresh()->campaignsCreatedThisMonth())->toBe(0)
        ->and($team->fresh()->canCreateCampaign())->toBeTrue();
});

test('customers show the sign-up date and sort by it both ways', function () {
    [$old, $oldTeam] = makeUserWithTeam();
    [$new, $newTeam] = makeUserWithTeam();
    $old->forceFill(['created_at' => now()->subDays(40)])->save();
    $oldTeam->update(['name' => 'Old Co']);
    $newTeam->update(['name' => 'New Co']);

    Livewire::test(Customers::class)
        ->assertSee('Signed up '.now()->subDays(40)->translatedFormat('j F Y'))
        ->assertSeeInOrder(['New Co', 'Old Co'])
        ->call('sortBy', 'oldest')
        ->assertSeeInOrder(['Old Co', 'New Co']);
});

test('customers page renders in Arabic', function () {
    app()->setLocale('ar');
    makeUserWithTeam();

    Livewire::test(Customers::class)
        ->assertSee('سجّل في')
        ->assertSee('الأحدث أولًا')
        ->assertDontSee('Sign-up date');
});
