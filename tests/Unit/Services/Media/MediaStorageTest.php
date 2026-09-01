<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    $this->storage = app(MediaStorage::class);
    $this->team = Team::factory()->create();
});

it('stores a binary blob and returns a MediaAsset', function () {
    $bytes = random_bytes(256);

    $asset = $this->storage->storeBytes(
        team: $this->team,
        bytes: $bytes,
        mimeType: 'image/jpeg',
        kind: 'image',
        originalFilename: 'photo.jpg',
    );

    expect($asset)->toBeInstanceOf(MediaAsset::class)
        ->and($asset->team_id)->toBe($this->team->id)
        ->and($asset->mime_type)->toBe('image/jpeg')
        ->and($asset->kind)->toBe('image')
        ->and($asset->size_bytes)->toBe(256)
        ->and($asset->checksum_sha256)->toBe(hash('sha256', $bytes));

    Storage::disk('media')->assertExists($asset->path);
});

it('returns the existing asset when the same checksum is stored again (dedup)', function () {
    $bytes = random_bytes(128);

    $first  = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');
    $second = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');

    expect($second->id)->toBe($first->id);
    expect(MediaAsset::where('team_id', $this->team->id)->count())->toBe(1);
});

it('allows the same checksum for a different team', function () {
    $bytes = random_bytes(128);
    $otherTeam = Team::factory()->create();

    $a = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');
    $b = $this->storage->storeBytes($otherTeam, $bytes, 'image/png', 'image');

    expect($a->id)->not->toBe($b->id);
});

it('places files in team-scoped, date-partitioned paths', function () {
    $asset = $this->storage->storeBytes($this->team, random_bytes(10), 'image/png', 'image');

    expect($asset->path)->toStartWith("{$this->team->id}/".now()->format('Y/m').'/');
});

it('generates a signed URL that expires', function () {
    $asset = $this->storage->storeBytes($this->team, random_bytes(10), 'image/png', 'image');

    $url = $this->storage->streamUrl($asset);

    expect($url)->toContain('/media/'.$asset->id)
        ->and($url)->toContain('signature=');
});
