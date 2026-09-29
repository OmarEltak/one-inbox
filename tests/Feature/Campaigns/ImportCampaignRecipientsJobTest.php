<?php

declare(strict_types=1);

use App\Jobs\ImportCampaignRecipients;
use App\Models\ContactImport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Phase 3 (docs/OT1_LIMITS.md §11) — async campaign recipient import.
 *
 * Covers: dispatch onto default queue, per-team throttle release-on-conflict,
 * success + failure state transitions on the ContactImport row.
 */

function importTeam(): array
{
    $u = User::factory()->create();
    $t = Team::create([
        'name'              => 'Import Test Co',
        'slug'              => 'team-' . Str::random(8),
        'owner_id'          => $u->id,
        'subscription_plan' => 'free',
    ]);
    $u->teams()->attach($t->id, ['role' => 'admin']);

    return [$u->fresh(), $t->fresh()];
}

function makeImportRow(int $teamId, int $userId): ContactImport
{
    return ContactImport::create([
        'team_id'       => $teamId,
        'user_id'       => $userId,
        'channel'       => 'whatsapp',
        'filename'      => 'contacts.csv',
        'original_name' => 'contacts.csv',
        'tag'           => 'imported:contacts',
        'status'        => ContactImport::STATUS_PENDING,
    ]);
}

function storeFakeCsv(string $storedPath): void
{
    Storage::put($storedPath, "phone,name\n+201111111111,Alice\n+201222222222,Bob\n");
}

beforeEach(function () {
    Storage::fake('local');
    Cache::flush();
});

it('runs on the default queue', function () {
    $job = new ImportCampaignRecipients(1, 'x', 'csv', [
        'phoneColumn' => 'phone', 'defaultCountry' => 'EG',
        'nameColumn' => 'name', 'customColumns' => [],
    ]);

    // Reflect the queue set by the constructor.
    expect($job->queue)->toBe('default');
});

it('marks the ContactImport row as completed with row counts after a successful run', function () {
    [$u, $t] = importTeam();
    $import  = makeImportRow($t->id, $u->id);
    $path    = "imports/{$t->id}/" . Str::random(12) . '.csv';
    storeFakeCsv($path);

    $this->actingAs($u); // PhoneContactImporter::import uses auth()->id()

    (new ImportCampaignRecipients($import->id, $path, 'csv', [
        'phoneColumn'    => 'phone',
        'defaultCountry' => 'EG',
        'nameColumn'     => 'name',
        'customColumns'  => [],
    ]))->handle(app(\App\Services\Campaigns\PhoneContactImporter::class));

    $fresh = $import->fresh();
    expect($fresh->status)->toBe(ContactImport::STATUS_COMPLETED);
    expect($fresh->imported_rows)->toBe(2);
    expect($fresh->total_rows)->toBe(2);
});

it('marks the row as failed and stores the error message on exception', function () {
    [$u, $t] = importTeam();
    $import  = makeImportRow($t->id, $u->id);

    $this->actingAs($u);

    // Non-existent path → parser will throw.
    (new ImportCampaignRecipients($import->id, 'imports/does-not-exist.csv', 'csv', [
        'phoneColumn'    => 'phone',
        'defaultCountry' => 'EG',
        'nameColumn'     => null,
        'customColumns'  => [],
    ]))->handle(app(\App\Services\Campaigns\PhoneContactImporter::class));

    $fresh = $import->fresh();
    expect($fresh->status)->toBe(ContactImport::STATUS_FAILED);
    expect($fresh->last_error)->not->toBeNull();
});

it('releases the per-team throttle lock after completion', function () {
    [$u, $t] = importTeam();
    $import  = makeImportRow($t->id, $u->id);
    $path    = "imports/{$t->id}/" . Str::random(12) . '.csv';
    storeFakeCsv($path);

    $this->actingAs($u);
    $key = "campaign:import:inflight:{$t->id}";

    expect(Cache::has($key))->toBeFalse();

    (new ImportCampaignRecipients($import->id, $path, 'csv', [
        'phoneColumn'    => 'phone',
        'defaultCountry' => 'EG',
        'nameColumn'     => 'name',
        'customColumns'  => [],
    ]))->handle(app(\App\Services\Campaigns\PhoneContactImporter::class));

    expect(Cache::has($key))->toBeFalse();
});
