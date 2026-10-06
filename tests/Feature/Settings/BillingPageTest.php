<?php

declare(strict_types=1);

use App\Livewire\Settings\Billing;
use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeBillingTeam(string $plan = 'starter', int $monthly = 500, int $wallet = 0): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create([
        'name'                     => 'Billing Test Co',
        'slug'                     => 'team-' . Str::random(8),
        'owner_id'                 => $user->id,
        'subscription_plan'        => $plan,
        'onboarding_completed_at'  => now(),
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    // Reset the auto-grant from Team::booted() so the test controls the balance.
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();

    $credits = app(AiCredits::class);
    if ($monthly > 0) {
        $credits->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }
    if ($wallet > 0) {
        $credits->grant($team, $wallet, AiCreditLedgerEntry::BALANCE_WALLET, 'test_seed');
    }

    return [$user->fresh(), $team->fresh()];
}

it('renders the billing page for an authenticated team member', function () {
    [$user, $team] = makeBillingTeam();

    $this->actingAs($user);

    $this->get(route('settings.billing'))
        ->assertOk()
        ->assertSeeLivewire(Billing::class);
});

it('redirects unauthenticated users away from the billing page', function () {
    $this->get(route('settings.billing'))->assertRedirect(route('login'));
});

it('shows the correct balance from AiCredits::balance', function () {
    [$user, $team] = makeBillingTeam(plan: 'starter', monthly: 342, wallet: 120);

    Livewire::actingAs($user)
        ->test(Billing::class)
        ->assertSet('rangeDays', 30)
        ->assertSeeText('342') // monthly used = 500 - 342 = 158, but we also show the "500" quota
        ->assertSeeText('120') // wallet shown
        ->assertSeeText('500'); // monthly quota
});

it('paginates the ledger table and respects date-range filtering', function () {
    [$user, $team] = makeBillingTeam(plan: 'starter', monthly: 500, wallet: 0);
    $credits = app(AiCredits::class);

    // 12 charges over the last 3 days (recent) + 3 charges 60 days ago (outside 7-day filter).
    for ($i = 0; $i < 12; $i++) {
        $credits->charge($team, 'ai_reply_outbound');
    }
    // Backdate 3 extra charges.
    AiCreditLedgerEntry::create([
        'team_id'      => $team->id,
        'delta'        => -1,
        'balance_type' => AiCreditLedgerEntry::BALANCE_MONTHLY,
        'reason'       => 'ai_reply_outbound',
        'created_at'   => now()->subDays(60),
    ]);
    AiCreditLedgerEntry::create([
        'team_id'      => $team->id,
        'delta'        => -1,
        'balance_type' => AiCreditLedgerEntry::BALANCE_MONTHLY,
        'reason'       => 'ai_reply_outbound',
        'created_at'   => now()->subDays(60),
    ]);
    AiCreditLedgerEntry::create([
        'team_id'      => $team->id,
        'delta'        => -1,
        'balance_type' => AiCreditLedgerEntry::BALANCE_MONTHLY,
        'reason'       => 'ai_reply_outbound',
        'created_at'   => now()->subDays(60),
    ]);

    // 30-day window: includes the 12 recent + 1 initial grant = 13 rows (page size 10 → 2 pages).
    $component30 = Livewire::actingAs($user)
        ->test(Billing::class, ['rangeDays' => 30]);
    expect($component30->instance()->ledger->total())->toBeGreaterThanOrEqual(13);

    // 7-day window: only the 12 recent + grant = 13; the 60-day-old ones are excluded.
    $component7 = Livewire::actingAs($user)
        ->test(Billing::class)
        ->set('rangeDays', 7);
    $totalIn7 = $component7->instance()->ledger->total();
    expect($totalIn7)->toBe(13); // 12 charges + 1 initial grant

    // 90-day window captures the backdated 3 extra entries too.
    $component90 = Livewire::actingAs($user)
        ->test(Billing::class)
        ->set('rangeDays', 90);
    expect($component90->instance()->ledger->total())->toBe(13 + 3);
});
