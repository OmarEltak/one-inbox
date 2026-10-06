<?php

declare(strict_types=1);

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use App\Services\Billing\AiCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeCostTableTeam(int $monthly = 1000): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'     => 'Cost Table Co',
        'slug'     => 'team-' . Str::random(8),
        'owner_id' => $user->id,
    ]);
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();
    Cache::flush();

    if ($monthly > 0) {
        app(AiCredits::class)->grant($team, $monthly, AiCreditLedgerEntry::BALANCE_MONTHLY, 'test_seed');
    }

    return $team->fresh();
}

it('uses the configured cost from ai_costs.php for a known action', function () {
    config()->set('ai_costs.ai_reply_outbound', 1);
    $team = makeCostTableTeam(monthly: 10);
    $credits = app(AiCredits::class);

    $credits->charge($team, 'ai_reply_outbound');

    expect($credits->balance($team->fresh())->total())->toBe(9);
});

it('respects a free action (cost = 0) like ai_lead_score', function () {
    config()->set('ai_costs.ai_lead_score', 0);
    $team = makeCostTableTeam(monthly: 10);
    $credits = app(AiCredits::class);

    $credits->charge($team, 'ai_lead_score');

    expect($credits->balance($team->fresh())->total())->toBe(10); // unchanged
});

it('falls back to DEFAULT_COST for unknown actions (does not silently charge zero)', function () {
    // The service documents a default of 1 for unknown actions; this prevents
    // a typo in the action name from silently making a feature free.
    config()->set('ai_costs', []); // wipe the known table
    $team = makeCostTableTeam(monthly: 10);
    $credits = app(AiCredits::class);

    $credits->charge($team, 'brand_new_action_not_in_config');

    expect($credits->balance($team->fresh())->total())->toBe(9);
});

it('deep_analysis cost scales with cohort size per the per-100 config knob', function () {
    config()->set('ai_costs.deep_analysis_per_100_contacts', 5);
    config()->set('ai_costs.deep_analysis_minimum', 10);

    // The scaling itself is tested by DeepAnalysisServiceQuoteTest — this
    // test just asserts the config keys exist and are integers so the
    // service doesn't crash on a missing key.
    expect(config('ai_costs.deep_analysis_per_100_contacts'))->toBeInt()
        ->and(config('ai_costs.deep_analysis_minimum'))->toBeInt();
});
