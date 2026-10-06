<?php

declare(strict_types=1);

use App\Exceptions\Billing\ExpensiveActionRequiresConfirmationException;
use App\Exceptions\Billing\InsufficientCreditsException;
use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use App\Services\Billing\Balance;
use App\Services\Billing\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeCreditsTeam(int $monthly = 0, int $wallet = 0, bool $autoDeduct = false): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Credit Test Co',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $user->id,
    ]);
    $team->auto_deduct_expensive_actions = $autoDeduct;
    $team->save();

    // The Team::booted() hook auto-grants the plan quota on create (so
    // production AI dispatch works out of the box). For deterministic unit
    // tests we null that out first, then grant the exact balances the test
    // asked for.
    \App\Models\AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    \Illuminate\Support\Facades\Cache::flush();

    $credits = app(AiCredits::class);
    if ($monthly > 0) {
        $credits->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }
    if ($wallet > 0) {
        $credits->grant($team, $wallet, AiCreditLedgerEntry::BALANCE_WALLET, 'test_seed');
    }

    return $team->fresh();
}

beforeEach(function () {
    // Ensure config has a known cost table so costFor() is deterministic.
    config()->set('ai_costs.ai_reply_outbound', 1);
    config()->set('ai_costs.ai_chat_turn', 1);
    config()->set('ai_costs.deep_analysis_test', 50);
    config()->set('ai_costs.ai_lead_score', 0);
    Cache::flush();
});

it('charge writes a ledger row and decrements the cached balance', function () {
    $team = makeCreditsTeam(monthly: 100);
    $credits = app(AiCredits::class);

    expect($credits->balance($team)->total())->toBe(100);

    $receipt = $credits->charge($team, 'ai_reply_outbound');

    expect($receipt)->toBeInstanceOf(Receipt::class);
    expect($receipt->cost)->toBe(1);
    expect($receipt->ledgerEntryIds)->toHaveCount(1);

    // Fresh balance should reflect the decrement.
    expect($credits->balance($team->fresh())->total())->toBe(99);

    // Ledger row exists with the right shape.
    $row = AiCreditLedgerEntry::find($receipt->ledgerEntryIds[0]);
    expect($row->delta)->toBe(-1);
    expect($row->balance_type)->toBe(AiCreditLedgerEntry::BALANCE_MONTHLY);
    expect($row->reason)->toBe('ai_reply_outbound');
});

it('drains monthly before wallet (two-balance drain order)', function () {
    $team = makeCreditsTeam(monthly: 3, wallet: 10);
    $credits = app(AiCredits::class);

    // Cost 5 = full 3 monthly + 2 wallet.
    config()->set('ai_costs.split_test', 5);
    $receipt = $credits->charge($team, 'split_test');

    expect($receipt->breakdown)->toBe([
        AiCreditLedgerEntry::BALANCE_MONTHLY => 3,
        AiCreditLedgerEntry::BALANCE_WALLET  => 2,
    ]);
    expect($receipt->ledgerEntryIds)->toHaveCount(2);

    $balance = $credits->balance($team->fresh());
    expect($balance->monthly)->toBe(0);
    expect($balance->wallet)->toBe(8);
});

it('is idempotent on repeated calls with the same key within the TTL window', function () {
    $team = makeCreditsTeam(monthly: 100);
    $credits = app(AiCredits::class);

    $first  = $credits->charge($team, 'ai_reply_outbound', ['idempotency_key' => 'dup-key-1']);
    $second = $credits->charge($team, 'ai_reply_outbound', ['idempotency_key' => 'dup-key-1']);

    // Same Receipt reference semantics: both should have the same ledger IDs.
    expect($second->ledgerEntryIds)->toBe($first->ledgerEntryIds);

    // Only ONE ledger row written across the two calls.
    expect(AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', 'ai_reply_outbound')
        ->count())->toBe(1);

    // And the balance decremented exactly once.
    expect($credits->balance($team->fresh())->total())->toBe(99);
});

it('throws InsufficientCreditsException when balance is below cost', function () {
    $team = makeCreditsTeam(monthly: 0, wallet: 0);
    $credits = app(AiCredits::class);

    expect(fn () => $credits->charge($team, 'ai_reply_outbound'))
        ->toThrow(InsufficientCreditsException::class);
});

it('throws ExpensiveActionRequiresConfirmationException when cost > 5 and no confirmation', function () {
    $team = makeCreditsTeam(monthly: 200);
    $credits = app(AiCredits::class);

    $thrown = null;
    try {
        $credits->charge($team, 'deep_analysis_test');
    } catch (ExpensiveActionRequiresConfirmationException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->cost)->toBe(50);
    expect($thrown->balanceAfter)->toBe(150);
    expect($thrown->actionToken)->toStartWith('act_');

    // Payload shape matches spec §3.6.
    $payload = $thrown->toPayload();
    expect($payload['type'])->toBe('confirmation_required');
    expect($payload['cost'])->toBe(50);
    expect($payload['balance_after'])->toBe(150);

    // NO ledger row written when the gate rejected the charge.
    expect(AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', 'deep_analysis_test')
        ->count())->toBe(0);
});

it('bypasses the confirmation gate when confirmation_token is supplied', function () {
    $team = makeCreditsTeam(monthly: 200);
    $credits = app(AiCredits::class);

    $receipt = $credits->charge($team, 'deep_analysis_test', [
        'confirmation_token' => 'act_1_whatever',
    ]);

    expect($receipt->cost)->toBe(50);
    expect($credits->balance($team->fresh())->total())->toBe(150);
});

it('bypasses the confirmation gate when team.auto_deduct_expensive_actions is true', function () {
    $team = makeCreditsTeam(monthly: 200, autoDeduct: true);
    $credits = app(AiCredits::class);

    $receipt = $credits->charge($team, 'deep_analysis_test');

    expect($receipt->cost)->toBe(50);
    expect($credits->balance($team->fresh())->total())->toBe(150);
});

it('refund writes positive rows with reason=refund_outage in the same balance types', function () {
    $team = makeCreditsTeam(monthly: 3, wallet: 5);
    $credits = app(AiCredits::class);

    // Cost 4 straddles both balances (3 monthly + 1 wallet) and stays under
    // the confirmation threshold so the gate doesn't block.
    config()->set('ai_costs.split_test', 4);
    $receipt = $credits->charge($team, 'split_test');

    expect($credits->balance($team->fresh())->total())->toBe(4);

    $credits->refund($receipt, 'nararouter_cooldown');

    // Full amount restored, split across the same balance types.
    expect($credits->balance($team->fresh())->total())->toBe(8);

    $refundRows = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', AiCreditLedgerEntry::REASON_REFUND_OUTAGE)
        ->get();

    expect($refundRows)->toHaveCount(2);
    expect($refundRows->pluck('balance_type')->sort()->values()->all())
        ->toBe([AiCreditLedgerEntry::BALANCE_MONTHLY, AiCreditLedgerEntry::BALANCE_WALLET]);
    expect($refundRows->sum('delta'))->toBe(4);
});

it('grant adds credits to the requested balance_type', function () {
    $team = makeCreditsTeam();
    $credits = app(AiCredits::class);

    $credits->grant($team, 500, AiCreditLedgerEntry::BALANCE_MONTHLY, 'monthly_grant');
    $credits->grant($team, 2000, AiCreditLedgerEntry::BALANCE_WALLET, 'pack_purchase');

    $balance = $credits->balance($team->fresh());
    expect($balance->monthly)->toBe(500);
    expect($balance->wallet)->toBe(2000);
    expect($balance->total())->toBe(2500);
});

it('grant rejects non-positive amounts', function () {
    $team = makeCreditsTeam();
    $credits = app(AiCredits::class);

    expect(fn () => $credits->grant($team, 0, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $credits->grant($team, -5, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test'))
        ->toThrow(InvalidArgumentException::class);
});

it('grant rejects invalid balance_type', function () {
    $team = makeCreditsTeam();
    $credits = app(AiCredits::class);

    expect(fn () => $credits->grant($team, 10, 'bogus', 'test'))
        ->toThrow(InvalidArgumentException::class);
});

it('free (cost=0) actions return a zero-cost Receipt without writing a ledger row', function () {
    $team = makeCreditsTeam(monthly: 10);
    $credits = app(AiCredits::class);

    $receipt = $credits->charge($team, 'ai_lead_score');

    expect($receipt->cost)->toBe(0);
    expect($receipt->ledgerEntryIds)->toBe([]);
    expect($credits->balance($team->fresh())->total())->toBe(10);
});

it('missing cost key falls back to DEFAULT_COST=1', function () {
    $team = makeCreditsTeam(monthly: 10);
    $credits = app(AiCredits::class);

    $receipt = $credits->charge($team, 'completely_unknown_action');

    expect($receipt->cost)->toBe(AiCredits::DEFAULT_COST);
    expect($credits->balance($team->fresh())->total())->toBe(9);
});

it('AiCreditLedgerEntry is append-only', function () {
    $team = makeCreditsTeam(monthly: 5);
    $credits = app(AiCredits::class);
    $receipt = $credits->charge($team, 'ai_reply_outbound');
    $row = AiCreditLedgerEntry::find($receipt->ledgerEntryIds[0]);

    expect(fn () => $row->update(['delta' => 0]))->toThrow(RuntimeException::class);
    expect(fn () => $row->delete())->toThrow(RuntimeException::class);
});

it('balance() returns a Balance value object with monthly/wallet/total', function () {
    $team = makeCreditsTeam(monthly: 100, wallet: 25);
    $balance = app(AiCredits::class)->balance($team);

    expect($balance)->toBeInstanceOf(Balance::class);
    expect($balance->monthly)->toBe(100);
    expect($balance->wallet)->toBe(25);
    expect($balance->total())->toBe(125);
    expect($balance->toArray())->toBe([
        'monthly' => 100,
        'wallet' => 25,
        'total' => 125,
    ]);
});
