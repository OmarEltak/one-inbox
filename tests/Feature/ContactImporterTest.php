<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\ContactImport;
use App\Services\Email\ContactImporter;
use App\Services\Email\SpreadsheetParser;

beforeEach(function () {
    [$this->user, $this->team] = makeUserWithTeam();
    $this->tmp = tempnam(sys_get_temp_dir(), 'imp_').'.csv';
});

afterEach(function () {
    if (isset($this->tmp) && file_exists($this->tmp)) {
        @unlink($this->tmp);
    }
});

function runImport(string $path, array $map, $user, $team): ContactImport
{
    $parser = new SpreadsheetParser($path, 'csv');
    return (new ContactImporter($parser))->import(
        teamId: $team->id,
        userId: $user->id,
        filename: $path,
        originalName: basename($path),
        map: $map,
    );
}

test('imports valid emails and upserts contacts with import tag', function () {
    file_put_contents($this->tmp, "Email,Name,Company\nalice@x.com,Alice,Acme\nbob@y.com,Bob,Beta\n");

    $import = runImport($this->tmp, [
        'email'  => 'Email',
        'name'   => 'Name',
        'custom' => ['Company'],
    ], $this->user, $this->team);

    expect($import->status)->toBe(ContactImport::STATUS_COMPLETED);
    expect($import->imported_rows)->toBe(2);
    expect($import->invalid_rows)->toBe(0);

    $alice = Contact::where('team_id', $this->team->id)->where('email', 'alice@x.com')->first();
    expect($alice)->not->toBeNull();
    expect($alice->tags)->toContain($import->tag);
    expect($alice->metadata)->toBe(['Company' => 'Acme']);
});

test('skips invalid emails and counts them as invalid', function () {
    file_put_contents($this->tmp, "Email\nnot-an-email\nalice@x.com\n\n");

    $import = runImport($this->tmp, ['email' => 'Email'], $this->user, $this->team);

    expect($import->imported_rows)->toBe(1);
    expect($import->invalid_rows)->toBe(1);
});

test('dedupes duplicate emails within the same file', function () {
    file_put_contents($this->tmp, "Email\nalice@x.com\nALICE@x.com\nalice@x.com\n");

    $import = runImport($this->tmp, ['email' => 'Email'], $this->user, $this->team);

    expect($import->imported_rows)->toBe(1);
    expect($import->skipped_rows)->toBe(2);
    expect(Contact::where('team_id', $this->team->id)->count())->toBe(1);
});

test('upserts an existing contact and merges tags + metadata', function () {
    // Pre-existing
    Contact::create([
        'team_id'  => $this->team->id,
        'email'    => 'alice@x.com',
        'name'     => 'Original',
        'tags'     => ['preset'],
        'metadata' => ['preset_field' => '1'],
    ]);

    file_put_contents($this->tmp, "Email,Name,Company\nalice@x.com,New,Acme\n");

    $import = runImport($this->tmp, [
        'email'  => 'Email',
        'name'   => 'Name',
        'custom' => ['Company'],
    ], $this->user, $this->team);

    $alice = Contact::where('team_id', $this->team->id)->where('email', 'alice@x.com')->first();

    expect(Contact::where('team_id', $this->team->id)->count())->toBe(1);
    expect($alice->name)->toBe('Original'); // existing name preserved
    expect($alice->tags)->toContain('preset', $import->tag);
    expect($alice->metadata)->toMatchArray(['preset_field' => '1', 'Company' => 'Acme']);
});
