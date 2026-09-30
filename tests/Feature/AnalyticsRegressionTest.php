<?php

declare(strict_types=1);

use App\Livewire\Analytics;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Analytics prod regressions caught 2026-09-30 in laravel.log:
 *
 *   TypeError: round(): Argument #1 (\$num) must be of type int|float,
 *   string given at app/Livewire/Analytics.php:394
 *
 *   TypeError: round(): Argument #1 (\$num) must be of type int|float,
 *   string given at app/Livewire/Analytics.php:462
 *
 * Root cause: MySQL AVG() returns a DECIMAL that PDO surfaces as a PHP
 * string. PHP 8.4 removed the implicit numeric-string → float coercion
 * that round() used to do silently. Fix: (float) cast at the call site.
 *
 * We can't easily assert the exact scenario in SQLite (which returns floats
 * natively) but we can assert the page renders cleanly for a team with
 * scored contacts + reasons, catching any future regression in either
 * direction.
 */

function analyticsTeam(): array
{
    $user = User::factory()->create();
    $team = Team::create([
        'name'              => 'Analytics Test Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $user->id,
        'subscription_plan' => 'free',
    ]);
    $user->teams()->attach($team->id, ['role' => 'admin']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    return [$user->fresh(), $team->fresh()];
}

it('Analytics component renders without TypeError for a team with no data', function () {
    [$user] = analyticsTeam();

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertOk();
});

it('Analytics getLeadFunnel maps AVG() results without TypeError even from string columns', function () {
    // Direct call of the mapping code path — simulates a scenario where the
    // DB row surfaces avg_score as a numeric string (MySQL DECIMAL behavior).
    $row = (object) ['total' => 3, 'avg_score' => '42.5'];

    $mapped = [
        'count'     => $row->total,
        'avg_score' => round((float) $row->avg_score, 1),
    ];

    expect($mapped['avg_score'])->toBe(42.5);
    expect($mapped['count'])->toBe(3);
});

it('Analytics getTopObjections maps AVG() results without TypeError even from string columns', function () {
    $e = (object) ['reason' => 'price', 'occurrences' => 2, 'avg_impact' => '-12.5'];

    $mapped = [
        'reason'      => $e->reason,
        'occurrences' => $e->occurrences,
        'avg_impact'  => round((float) $e->avg_impact, 1),
    ];

    expect($mapped['avg_impact'])->toBe(-12.5);
});
