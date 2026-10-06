<?php

declare(strict_types=1);

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeMeterTeam(string $plan, int $monthlyRemaining, int $wallet = 0): array
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'              => 'Meter Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $user->id,
        'subscription_plan' => $plan,
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();

    $credits = app(AiCredits::class);
    if ($monthlyRemaining > 0) {
        $credits->grant($team, $monthlyRemaining, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }
    if ($wallet > 0) {
        $credits->grant($team, $wallet, AiCreditLedgerEntry::BALANCE_WALLET, 'test_seed');
    }

    return [$user->fresh(), $team->fresh()];
}

function renderMeter(User $user): string
{
    auth()->login($user);
    Cache::flush(); // ensure 10s wrapper doesn't bleed between cases

    return (string) Blade::render('<x-ai-credit-meter />');
}

it('renders nothing when there is no authenticated team', function () {
    $output = (string) Blade::render('<x-ai-credit-meter />');

    expect(trim($output))->toBe('');
});

it('renders the emerald variant when under 60 percent used', function () {
    // starter plan = 500 quota; 450 remaining → 50 used → 10% used → emerald.
    [$user, $team] = makeMeterTeam('starter', monthlyRemaining: 450);

    $html = renderMeter($user);

    expect($html)->toContain('data-variant="emerald"')
        ->toContain('bg-emerald-50')
        ->toContain('450')
        ->toContain('500');
});

it('renders the amber variant when between 60 and 85 percent used', function () {
    // starter plan = 500; 150 remaining → 350 used → 70% → amber.
    [$user, $team] = makeMeterTeam('starter', monthlyRemaining: 150);

    $html = renderMeter($user);

    expect($html)->toContain('data-variant="amber"')
        ->toContain('bg-amber-50');
});

it('renders the red variant when over 85 percent used', function () {
    // starter plan = 500; 50 remaining → 450 used → 90% → red.
    [$user, $team] = makeMeterTeam('starter', monthlyRemaining: 50);

    $html = renderMeter($user);

    expect($html)->toContain('data-variant="red"')
        ->toContain('bg-red-50');
});

it('renders unlimited label for a plan with ai_credits = -1', function () {
    // Phase RP (2026-10-06) collapsed the ladder — there is no stock
    // "unlimited" plan anymore. Inject one at test time so the meter's
    // unlimited rendering stays regression-covered.
    config()->set('plans.plans.unlimited_test', [
        'name'       => 'Unlimited Test',
        'price_id'   => null,
        'price'      => 0,
        'ai_credits' => -1,
        'pages'      => -1,
        'limits'     => [
            'pages'                  => -1,
            'whatsapp_numbers'       => -1,
            'bulk_campaigns_monthly' => -1,
            'contacts_stored'        => -1,
        ],
    ]);
    [$user, $team] = makeMeterTeam('unlimited_test', monthlyRemaining: 10_000);

    $html = renderMeter($user);

    expect($html)->toContain('∞')
        ->not->toContain('<span class="absolute inset-y-0'); // no progress-bar fill
});

it('includes an aria-label and a resets-in-N-days tooltip', function () {
    [$user, $team] = makeMeterTeam('starter', monthlyRemaining: 450);

    $html = renderMeter($user);

    expect($html)->toContain('aria-label=')
        ->toContain('resets in'); // from the tooltip string
});
