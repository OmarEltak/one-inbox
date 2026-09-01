<?php

declare(strict_types=1);

use App\Models\Team;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->storage = app(MediaStorage::class);
});

it('streams media bytes with correct content-type when signed URL is valid', function () {
    $bytes = random_bytes(64);
    $asset = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');

    $url = $this->storage->streamUrl($asset);

    $response = $this->get($url);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('image/png');
    expect($response->streamedContent())->toBe($bytes);
});

it('returns 403 when the signature is missing', function () {
    $asset = $this->storage->storeBytes($this->team, random_bytes(10), 'image/png', 'image');

    $this->get("/media/{$asset->id}")->assertForbidden();
});

it('returns 404 when the asset does not exist', function () {
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'media.stream',
        now()->addDay(),
        ['ulid' => (string) \Illuminate\Support\Str::ulid()],
    );

    $this->get($url)->assertNotFound();
});
