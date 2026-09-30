<?php

declare(strict_types=1);

use App\Jobs\ImportEmailRecipients;
use App\Models\ContactImport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Phase 3b (docs/OT1_LIMITS.md §11) — async email recipient import.
 * Mirrors the WA-wizard tests in ImportCampaignRecipientsJobTest.
 */

function emailImportTeam(): array
{
    $u = User::factory()->create();
    $t = Team::create([
        'name'              => 'Email Import Test Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $u->id,
        'subscription_plan' => 'free',
    ]);
    $u->teams()->attach($t->id, ['role' => 'admin']);

    return [$u->fresh(), $t->fresh()];
}

function makeEmailImportRow(int $teamId, int $userId, string $storedPath): ContactImport
{
    return ContactImport::create([
        'team_id'       => $teamId,
        'user_id'       => $userId,
        'filename'      => $storedPath,
        'original_name' => 'test.csv',
        'tag'           => 'imported:test',
        'status'        => ContactImport::STATUS_PENDING,
    ]);
}

beforeEach(function () {
    Storage::fake('local');
    Cache::flush();
});

it('runs on the default queue', function () {
    $job = new ImportEmailRecipients(1, 'x.csv', 'csv', ['email' => 'email', 'name' => null, 'custom' => []]);
    expect($job->queue)->toBe('default');
});

it('marks the ContactImport row as completed with row counts', function () {
    [$u, $t] = emailImportTeam();
    $path = "imports/{$t->id}/" . Str::random(12) . '.csv';
    Storage::disk('local')->put($path, "email,name\nalice@example.com,Alice\nbob@example.com,Bob\n");

    $import = makeEmailImportRow($t->id, $u->id, $path);

    (new ImportEmailRecipients($import->id, $path, 'csv', [
        'email'  => 'email',
        'name'   => 'name',
        'custom' => [],
    ]))->handle();

    $fresh = $import->fresh();
    expect($fresh->status)->toBe(ContactImport::STATUS_COMPLETED);
    expect($fresh->imported_rows)->toBe(2);
});

it('marks the row as failed on exception (missing file)', function () {
    [$u, $t] = emailImportTeam();
    $import = makeEmailImportRow($t->id, $u->id, 'imports/does-not-exist.csv');

    (new ImportEmailRecipients($import->id, 'imports/does-not-exist.csv', 'csv', [
        'email' => 'email', 'name' => null, 'custom' => [],
    ]))->handle();

    $fresh = $import->fresh();
    expect($fresh->status)->toBe(ContactImport::STATUS_FAILED);
    expect($fresh->last_error)->not->toBeNull();
});

it('releases the per-team throttle lock after completion', function () {
    [$u, $t] = emailImportTeam();
    $path = "imports/{$t->id}/" . Str::random(12) . '.csv';
    Storage::disk('local')->put($path, "email\nx@example.com\n");
    $import = makeEmailImportRow($t->id, $u->id, $path);

    $key = "email:import:inflight:{$t->id}";
    expect(Cache::has($key))->toBeFalse();

    (new ImportEmailRecipients($import->id, $path, 'csv', [
        'email' => 'email', 'name' => null, 'custom' => [],
    ]))->handle();

    expect(Cache::has($key))->toBeFalse();
});
