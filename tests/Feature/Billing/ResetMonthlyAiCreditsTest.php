<?php

declare(strict_types=1);

use App\Console\Commands\ResetMonthlyAiCredits;
use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

function makeMonthlyTeam(string $plan, int $anchorDay): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'                 => 'Monthly Test Co',
        'slug'                 => 'team-' . Str::random(8),
        'owner_id'             => $user->id,
        'subscription_plan'    => $plan,
        'billing_cycle_anchor' => Carbon::create(2026, 1, $anchorDay)->toDateString(),
    ]);
    // Null out the auto-grant from Team::booted() so each test seeds its own
    // starting ledger explicitly — otherwise balance expectations drift.
    \App\Models\AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();
    return $team->fresh();
}

afterEach(function () {
    Carbon::setTestNow(null);
    Cache::flush();
});

it('triggers reset only for teams whose anchor day matches today', function () {
    // Freeze today = 2026-10-15.
    Carbon::setTestNow(Carbon::create(2026, 10, 15));

    $matching     = makeMonthlyTeam('starter', 15);
    $nonMatching  = makeMonthlyTeam('starter', 16);

    $this->artisan('credits:reset-monthly')->assertSuccessful();

    $matchingGrants = AiCreditLedgerEntry::query()
        ->where('team_id', $matching->id)
        ->where('reason', AiCreditLedgerEntry::REASON_MONTHLY_GRANT)
        ->get();

    $nonMatchingGrants = AiCreditLedgerEntry::query()
        ->where('team_id', $nonMatching->id)
        ->where('reason', AiCreditLedgerEntry::REASON_MONTHLY_GRANT)
        ->get();

    expect($matchingGrants)->toHaveCount(1);
    expect((int) $matchingGrants->first()->delta)->toBe(500); // starter quota
    expect($nonMatchingGrants)->toHaveCount(0);
});

it('zeros any unused monthly remainder before granting the new allowance', function () {
    Carbon::setTestNow(Carbon::create(2026, 10, 15));
    $team = makeMonthlyTeam('starter', 15);
    $credits = app(AiCredits::class);

    // Seed 123 unused monthly credits from the prior cycle.
    $credits->grant($team, 123, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    expect($credits->balance($team)->monthly)->toBe(123);

    $this->artisan('credits:reset-monthly')->assertSuccessful();

    // After reset: zeroing row (-123) + grant row (+500) → monthly balance = 500.
    $balance = $credits->balance($team->fresh());
    expect($balance->monthly)->toBe(500);

    $zeroing = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', AiCreditLedgerEntry::REASON_MONTHLY_ZEROING)
        ->first();
    expect($zeroing)->not->toBeNull();
    expect((int) $zeroing->delta)->toBe(-123);
});

it('is idempotent within the same UTC day', function () {
    Carbon::setTestNow(Carbon::create(2026, 10, 15));
    $team = makeMonthlyTeam('pro', 15);

    $this->artisan('credits:reset-monthly')->assertSuccessful();
    $this->artisan('credits:reset-monthly')->assertSuccessful();
    $this->artisan('credits:reset-monthly')->assertSuccessful();

    $grants = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', AiCreditLedgerEntry::REASON_MONTHLY_GRANT)
        ->get();

    expect($grants)->toHaveCount(1);
    expect((int) $grants->first()->delta)->toBe(3000);
});

it('leaves wallet balance untouched', function () {
    Carbon::setTestNow(Carbon::create(2026, 10, 15));
    $team = makeMonthlyTeam('starter', 15);
    $credits = app(AiCredits::class);

    $credits->grant($team, 777, AiCreditLedgerEntry::BALANCE_WALLET, 'pack_purchase');
    expect($credits->balance($team)->wallet)->toBe(777);

    $this->artisan('credits:reset-monthly')->assertSuccessful();

    expect($credits->balance($team->fresh())->wallet)->toBe(777);
});
