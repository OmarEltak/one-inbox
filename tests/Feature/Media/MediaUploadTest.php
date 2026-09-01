<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->user = User::factory()->for($this->team, 'currentTeam')->create();
    $this->user->teams()->attach($this->team);
    $this->actingAs($this->user);
});

it('accepts image uploads under the size limit', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(300);

    $response = $this->postJson('/api/media/upload', [
        'file' => $file,
        'kind' => 'image',
    ]);

    $response->assertOk()->assertJsonStructure(['id', 'mime_type', 'url', 'kind']);
});

it('accepts audio uploads and enqueues webm-to-ogg conversion', function () {
    \Illuminate\Support\Facades\Bus::fake();

    $file = UploadedFile::fake()->create('voice.webm', 500, 'audio/webm');

    $response = $this->postJson('/api/media/upload', [
        'file' => $file,
        'kind' => 'audio',
    ]);

    $response->assertOk();
    \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\ConvertAudioToOgg::class);
});

it('rejects images larger than 5 MB', function () {
    $file = UploadedFile::fake()->image('big.jpg')->size(6 * 1024);

    $this->postJson('/api/media/upload', ['file' => $file, 'kind' => 'image'])
        ->assertStatus(422);
});

it('rejects audio larger than 16 MB', function () {
    $file = UploadedFile::fake()->create('big.webm', 17 * 1024, 'audio/webm');

    $this->postJson('/api/media/upload', ['file' => $file, 'kind' => 'audio'])
        ->assertStatus(422);
});

it('requires authentication', function () {
    auth()->logout();

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->postJson('/api/media/upload', ['file' => $file, 'kind' => 'image'])
        ->assertStatus(401);
});
