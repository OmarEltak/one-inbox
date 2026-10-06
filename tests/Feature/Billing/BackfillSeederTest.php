<?php

declare(strict_types=1);

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\BackfillAiCreditLedgerSeeder;
use Illuminate\Support\Str;

function makeBackfillTeam(string $plan = 'free'): Team
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'              => 'Backfill Test Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $user->id,
        'subscription_plan' => $plan,
    ]);
    return $team->fresh();
}

it('backfills every team with one monthly grant at their plan quota', function () {
    $freeTeam    = makeBackfillTeam('free');
    $starterTeam = makeBackfillTeam('starter');
    $proTeam     = makeBackfillTeam('pro');

    (new BackfillAiCreditLedgerSeeder())->run();

    foreach ([
        [$freeTeam->id,    100],
        [$starterTeam->id, 500],
        [$proTeam->id,     3000],
    ] as [$teamId, $expected]) {
        $rows = AiCreditLedgerEntry::query()
            ->where('team_id', $teamId)
            ->where('reason', AiCreditLedgerEntry::REASON_BACKFILL)
            ->get();

        expect($rows)->toHaveCount(1, "team {$teamId} should have exactly one backfill row");
        expect((int) $rows->first()->delta)->toBe($expected);
        expect($rows->first()->balance_type)->toBe(AiCreditLedgerEntry::BALANCE_MONTHLY);
    }
});

it('is idempotent — re-running does not grant duplicate credits', function () {
    $team = makeBackfillTeam('starter');

    (new BackfillAiCreditLedgerSeeder())->run();
    (new BackfillAiCreditLedgerSeeder())->run();
    (new BackfillAiCreditLedgerSeeder())->run();

    $rows = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', AiCreditLedgerEntry::REASON_BACKFILL)
        ->get();

    expect($rows)->toHaveCount(1);
    expect((int) $rows->first()->delta)->toBe(500);
});

it('skips teams that already have a backfill row even on fresh seeder instances', function () {
    $team = makeBackfillTeam('pro');

    // Reset any auto-granted ledger entries from Team::booted() so this test
    // controls the row count explicitly.
    AiCreditLedgerEntry::query()->where('team_id', $team->id)->delete();

    AiCreditLedgerEntry::create([
        'team_id' => $team->id,
        'delta' => 1234,
        'balance_type' => AiCreditLedgerEntry::BALANCE_MONTHLY,
        'reason' => AiCreditLedgerEntry::REASON_BACKFILL,
        'created_at' => now(),
    ]);

    (new BackfillAiCreditLedgerSeeder())->run();

    $rows = AiCreditLedgerEntry::query()
        ->where('team_id', $team->id)
        ->where('reason', AiCreditLedgerEntry::REASON_BACKFILL)
        ->get();

    expect($rows)->toHaveCount(1);
    expect((int) $rows->first()->delta)->toBe(1234); // untouched
});
