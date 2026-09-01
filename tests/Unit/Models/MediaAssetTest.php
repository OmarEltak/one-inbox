<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('generates a ULID as primary key', function () {
    $team = Team::factory()->create();
    $asset = MediaAsset::factory()->for($team)->create();

    expect($asset->id)->toBeString()->toHaveLength(26);
});

it('scopes checksum uniqueness per team', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    MediaAsset::factory()->for($teamA)->create(['checksum_sha256' => str_repeat('a', 64)]);

    // Same checksum on different team must succeed.
    $ok = MediaAsset::factory()->for($teamB)->create(['checksum_sha256' => str_repeat('a', 64)]);
    expect($ok->exists)->toBeTrue();

    // Same checksum on same team must fail.
    expect(fn () => MediaAsset::factory()->for($teamA)->create(['checksum_sha256' => str_repeat('a', 64)]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('casts metadata to array', function () {
    $asset = MediaAsset::factory()->create(['metadata' => ['w' => 100, 'h' => 200]]);

    expect($asset->metadata)->toBe(['w' => 100, 'h' => 200]);
});
