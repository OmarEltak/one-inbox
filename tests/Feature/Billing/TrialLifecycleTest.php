<?php

declare(strict_types=1);

use App\Jobs\TrialExpiryCheck;
use App\Livewire\Onboarding\PickYourPlan;
use App\Mail\PaymentOverdue;
use App\Mail\TrialExpiredPaymentDue;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\PlanLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeBillingUser(): array
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Billing Test Co',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();
    return [$user->fresh(), $team->fresh()];
}

it('pick-your-plan starts the trial clock and redirects to dashboard', function () {
    [$user, $team] = makeBillingUser();
    $this->actingAs($user);

    Livewire::test(PickYourPlan::class)
        ->call('pickPlan', 'starter')
        ->assertRedirect(route('dashboard'));

    $team->refresh();
    expect($team->plan_status)->toBe('trial');
    expect($team->plan_trial_started_at)->not->toBeNull();
    expect($team->subscription_plan)->toBe('starter');
});

it('pick-your-plan does not re-show once trial has started', function () {
    [$user, $team] = makeBillingUser();
    $team->update([
        'plan_status'          => 'trial',
        'plan_trial_started_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    Livewire::test(PickYourPlan::class)
        ->assertRedirect(route('dashboard'));
});

it('walks trial → pending → overdue → paid across daily jobs', function () {
    [$user, $team] = makeBillingUser();
    $lifecycle = app(PlanLifecycle::class);

    Mail::fake();

    // Day 0 — user picks a plan.
    Carbon::setTestNow(now()->startOfDay());
    $lifecycle->startTrial($team, 'starter');
    expect($team->fresh()->plan_status)->toBe('trial');

    // Day 14 — the daily job should transition to pending_payment and email.
    Carbon::setTestNow(now()->addDays(PlanLifecycle::TRIAL_DAYS + 1));
    (new TrialExpiryCheck())->handle($lifecycle);
    $team->refresh();
    expect($team->plan_status)->toBe('pending_payment');
    Mail::assertSent(TrialExpiredPaymentDue::class);

    // Day 21+ — grace elapsed, should flip to overdue and email.
    Carbon::setTestNow(now()->addDays(PlanLifecycle::PAYMENT_GRACE_DAYS + 1));
    Cache::flush(); // clear per-day idempotency guards for the new UTC day
    (new TrialExpiryCheck())->handle($lifecycle);
    $team->refresh();
    expect($team->plan_status)->toBe('overdue');
    Mail::assertSent(PaymentOverdue::class);

    // Super-admin marks paid.
    $lifecycle->markPaid($team);
    expect($team->fresh()->plan_status)->toBe('paid');

    Carbon::setTestNow(null);
});

it('overdue teams get soft-throttled to OVERDUE_DAILY_MESSAGE_CAP but never hard-blocked', function () {
    [$user, $team] = makeBillingUser();
    $team->update([
        'ai_enabled'  => true,
        'plan_status' => 'overdue',
    ]);
    $team = $team->fresh();

    // Under cap → allowed.
    expect($team->canDispatchAi())->toBeTrue();

    // Simulate having already sent the cap today.
    for ($i = 0; $i < PlanLifecycle::OVERDUE_DAILY_MESSAGE_CAP; $i++) {
        $team->recordOverdueAiSent();
    }

    expect($team->fresh()->canDispatchAi())->toBeFalse();
});

it('cancelled teams cannot dispatch AI', function () {
    [$user, $team] = makeBillingUser();
    $team->update(['ai_enabled' => true, 'plan_status' => 'cancelled']);
    expect($team->fresh()->canDispatchAi())->toBeFalse();
});

it('trial and paid and pending_payment teams all allow AI dispatch', function () {
    [$user, $team] = makeBillingUser();
    $team->update(['ai_enabled' => true]);

    foreach (['trial', 'pending_payment', 'paid'] as $status) {
        $team->update(['plan_status' => $status]);
        expect($team->fresh()->canDispatchAi())->toBeTrue("status={$status}");
    }
});

it('MeetYourAi completion redirects to pick-your-plan when trial not started', function () {
    [$user, $team] = makeBillingUser();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Onboarding\MeetYourAi::class)
        ->call('completeAndConnect')
        ->assertRedirect(route('onboarding.pick-your-plan'));
});

it('MeetYourAi completion goes to intended route when trial already started', function () {
    [$user, $team] = makeBillingUser();
    $team->update([
        'plan_status'          => 'trial',
        'plan_trial_started_at' => now(),
    ]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Onboarding\MeetYourAi::class)
        ->call('completeAndConnect')
        ->assertRedirect(route('connections.index'));
});
