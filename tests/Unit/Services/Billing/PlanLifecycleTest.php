<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use App\Services\Billing\PlanLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makePlanTeam(): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Plan Test Co',
        'slug'     => 'team-'.Str::random(8),
        'owner_id' => $user->id,
    ]);
    return $team->fresh();
}

it('starts a trial idempotently', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();

    $lifecycle->startTrial($team, 'starter');

    $team->refresh();
    expect($team->plan_status)->toBe('trial');
    expect($team->plan_trial_started_at)->not->toBeNull();
    expect($team->subscription_plan)->toBe('starter');

    $originalStart = $team->plan_trial_started_at->copy();

    // Second call must NOT reset the clock.
    Carbon::setTestNow(now()->addHours(2));
    $lifecycle->startTrial($team, 'pro');
    $team->refresh();
    expect($team->plan_trial_started_at->timestamp)->toBe($originalStart->timestamp);
    Carbon::setTestNow(null);
});

it('never overwrites a paid team when startTrial is called', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();
    $team->update(['plan_status' => 'paid']);

    $lifecycle->startTrial($team, 'starter');

    expect($team->fresh()->plan_status)->toBe('paid');
});

it('transitions trial → pending_payment when trial has elapsed', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();
    $lifecycle->startTrial($team, 'starter');

    Carbon::setTestNow(now()->addDays(PlanLifecycle::TRIAL_DAYS + 1));
    $lifecycle->markPendingPayment($team);
    Carbon::setTestNow(null);

    $team->refresh();
    expect($team->plan_status)->toBe('pending_payment');
    expect($team->plan_payment_due_at)->not->toBeNull();
});

it('transitions pending_payment → overdue when grace has elapsed', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();
    $lifecycle->startTrial($team, 'starter');

    Carbon::setTestNow(now()->addDays(PlanLifecycle::TRIAL_DAYS));
    $lifecycle->markPendingPayment($team);
    Carbon::setTestNow(now()->addDays(PlanLifecycle::PAYMENT_GRACE_DAYS + 1));

    $team->refresh();
    expect($lifecycle->paymentGraceHasElapsed($team))->toBeTrue();
    $lifecycle->markOverdue($team);

    expect($team->fresh()->plan_status)->toBe('overdue');
    Carbon::setTestNow(null);
});

it('markPaid clears the payment due date', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();
    $lifecycle->startTrial($team, 'starter');
    $lifecycle->markPendingPayment($team);

    expect($team->fresh()->plan_payment_due_at)->not->toBeNull();

    $lifecycle->markPaid($team);

    $team->refresh();
    expect($team->plan_status)->toBe('paid');
    expect($team->plan_payment_due_at)->toBeNull();
});

it('trialHasElapsed reflects the passage of time', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();
    $lifecycle->startTrial($team, 'starter');

    expect($lifecycle->trialHasElapsed($team))->toBeFalse();

    Carbon::setTestNow(now()->addDays(PlanLifecycle::TRIAL_DAYS + 1));
    // Reload so casts reflect Carbon::now again.
    expect($lifecycle->trialHasElapsed($team->fresh()))->toBeTrue();
    Carbon::setTestNow(null);
});

it('resetToTrial gives a fresh 14 day clock', function () {
    $lifecycle = new PlanLifecycle();
    $team = makePlanTeam();
    $team->update([
        'plan_status'          => 'overdue',
        'plan_trial_started_at' => now()->subDays(30),
    ]);

    $lifecycle->resetToTrial($team);

    $team->refresh();
    expect($team->plan_status)->toBe('trial');
    expect($team->plan_trial_started_at->diffInSeconds(now()))->toBeLessThan(5);
});
