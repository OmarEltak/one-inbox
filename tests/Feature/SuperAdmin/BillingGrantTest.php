<?php

declare(strict_types=1);

use App\Livewire\SuperAdmin\Billing;
use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['is_super_admin' => true]);

    // The /super-admin/* routes sit behind the global `team` middleware
    // (EnsureHasTeam) — a user without a current_team_id is bounced to
    // teams.create. Give the super-admin an owned team so page loads succeed.
    $adminTeam = Team::create([
        'name'     => 'OT Staff',
        'slug'     => 'ot-staff-' . Illuminate\Support\Str::random(6),
        'owner_id' => $this->superAdmin->id,
    ]);
    $this->superAdmin->teams()->attach($adminTeam->id, ['role' => 'admin']);
    $this->superAdmin->forceFill(['current_team_id' => $adminTeam->id])->save();
    $this->superAdmin = $this->superAdmin->fresh();
});

test('non-super-admin cannot load the super-admin billing page', function () {
    [$user] = makeUserWithTeam();
    $this->actingAs($user);

    $this->get('/super-admin/billing')->assertStatus(403);
});

test('guest is redirected to login', function () {
    $this->get('/super-admin/billing')->assertRedirect('/login');
});

test('super-admin can load the billing page', function () {
    $this->actingAs($this->superAdmin);
    [, $team] = makeUserWithTeam();
    $team->update(['name' => 'Acme Co']);

    $this->get('/super-admin/billing')
        ->assertStatus(200)
        ->assertSee('Grant AI credits')
        ->assertSee('Acme Co');
});

test('granting wallet credits writes a ledger row with correct metadata', function () {
    $this->actingAs($this->superAdmin);
    [, $team] = makeUserWithTeam();

    Livewire::test(Billing::class)
        ->set('teamId', $team->id)
        ->set('action', 'grant_wallet')
        ->set('amount', 500)
        ->set('paymentReference', 'PP-9F2K-0CT05')
        ->set('note', 'PayPal $10 — pack purchase')
        ->call('submit')
        ->assertHasNoErrors();

    $entry = AiCreditLedgerEntry::where('team_id', $team->id)->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->delta)->toBe(500)
        ->and($entry->balance_type)->toBe(AiCreditLedgerEntry::BALANCE_WALLET)
        ->and($entry->reason)->toBe(AiCreditLedgerEntry::REASON_MANUAL_GRANT)
        ->and($entry->actor_user_id)->toBe($this->superAdmin->id)
        ->and($entry->metadata['payment_reference'] ?? null)->toBe('PP-9F2K-0CT05')
        ->and($entry->metadata['note'] ?? null)->toBe('PayPal $10 — pack purchase');
});

test('granting monthly bonus writes a monthly row with manual_bonus reason', function () {
    $this->actingAs($this->superAdmin);
    [, $team] = makeUserWithTeam();

    Livewire::test(Billing::class)
        ->set('teamId', $team->id)
        ->set('action', 'grant_monthly_bonus')
        ->set('amount', 200)
        ->set('note', 'goodwill for outage')
        ->call('submit')
        ->assertHasNoErrors();

    $entry = AiCreditLedgerEntry::where('team_id', $team->id)
        ->where('reason', 'manual_bonus')
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->delta)->toBe(200)
        ->and($entry->balance_type)->toBe(AiCreditLedgerEntry::BALANCE_MONTHLY);
});

test('change plan updates the team plan and writes a plan_changed audit row', function () {
    $this->actingAs($this->superAdmin);
    [, $team] = makeUserWithTeam();
    $team->update(['subscription_plan' => 'free']);

    Livewire::test(Billing::class)
        ->set('teamId', $team->id)
        ->set('action', 'change_plan')
        ->set('plan', 'pro')
        ->set('note', 'manual upgrade after bank transfer')
        ->call('submit')
        ->assertHasNoErrors();

    expect($team->fresh()->subscription_plan)->toBe('pro');

    $entry = AiCreditLedgerEntry::where('team_id', $team->id)
        ->where('reason', 'plan_changed')
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->delta)->toBe(0)
        ->and($entry->metadata['from'] ?? null)->toBe('free')
        ->and($entry->metadata['to'] ?? null)->toBe('pro')
        ->and($entry->actor_user_id)->toBe($this->superAdmin->id);
});

test('refund writes a positive row with the chosen reason', function () {
    $this->actingAs($this->superAdmin);
    [, $team] = makeUserWithTeam();

    Livewire::test(Billing::class)
        ->set('teamId', $team->id)
        ->set('action', 'refund')
        ->set('amount', 75)
        ->set('refundReason', 'outage')
        ->set('note', 'NaraRouter cooldown — 3h window')
        ->call('submit')
        ->assertHasNoErrors();

    $entry = AiCreditLedgerEntry::where('team_id', $team->id)->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->delta)->toBe(75)
        ->and($entry->metadata['refund_reason'] ?? null)->toBe('outage');
});

test('ledger table can be searched by payment reference', function () {
    $this->actingAs($this->superAdmin);
    [, $teamA] = makeUserWithTeam();
    [, $teamB] = makeUserWithTeam();
    $teamA->update(['name' => 'Alpha Team']);
    $teamB->update(['name' => 'Beta Team']);

    Livewire::test(Billing::class)
        ->set('teamId', $teamA->id)
        ->set('action', 'grant_wallet')
        ->set('amount', 100)
        ->set('paymentReference', 'ALPHAREF-123')
        ->call('submit');

    Livewire::test(Billing::class)
        ->set('teamId', $teamB->id)
        ->set('action', 'grant_wallet')
        ->set('amount', 100)
        ->set('paymentReference', 'BETAREF-456')
        ->call('submit');

    Livewire::test(Billing::class)
        ->set('search', 'ALPHAREF')
        ->assertSee('ALPHAREF-123')
        ->assertDontSee('BETAREF-456');
});

test('ledger table can be searched by team name', function () {
    $this->actingAs($this->superAdmin);
    [, $teamA] = makeUserWithTeam();
    [, $teamB] = makeUserWithTeam();
    $teamA->update(['name' => 'Mishkah University']);
    $teamB->update(['name' => 'Other Workspace']);

    Livewire::test(Billing::class)
        ->set('teamId', $teamA->id)
        ->set('action', 'grant_wallet')
        ->set('amount', 100)
        ->set('paymentReference', 'A-REF')
        ->call('submit');

    Livewire::test(Billing::class)
        ->set('teamId', $teamB->id)
        ->set('action', 'grant_wallet')
        ->set('amount', 100)
        ->set('paymentReference', 'B-REF')
        ->call('submit');

    Livewire::test(Billing::class)
        ->set('search', 'Mishkah')
        ->assertSee('A-REF')
        ->assertDontSee('B-REF');
});

test('grant form validates required fields', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(Billing::class)
        ->set('teamId', null)
        ->set('action', 'grant_wallet')
        ->set('amount', 0)
        ->call('submit')
        ->assertHasErrors(['teamId', 'amount']);
});

test('refund requires a reason', function () {
    $this->actingAs($this->superAdmin);
    [, $team] = makeUserWithTeam();

    Livewire::test(Billing::class)
        ->set('teamId', $team->id)
        ->set('action', 'refund')
        ->set('amount', 10)
        ->set('refundReason', '')
        ->call('submit')
        ->assertHasErrors('refundReason');
});
