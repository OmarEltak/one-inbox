<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Ai\Transcription\GroqDriver;
use App\Services\Ai\Transcription\RateLimitedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.groq.api_key' => 'gsk_fake',
        'services.groq.model'   => 'whisper-large-v3',
        'services.groq.timeout' => 5,
    ]);
    Storage::fake('media');
    $this->team = Team::factory()->create();
    Storage::disk('media')->put('a/b/voice.ogg', 'bytes');
    $this->asset = MediaAsset::factory()->for($this->team)->create([
        'path' => 'a/b/voice.ogg', 'mime_type' => 'audio/ogg', 'kind' => 'audio',
    ]);
});

it('returns transcript on 200', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'Hello from Groq.']),
    ]);

    expect(app(GroqDriver::class)->transcribe($this->asset))->toBe('Hello from Groq.');
});

it('throws RateLimitedException on 429', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['error' => 'rate limited'], 429, ['Retry-After' => '30']),
    ]);

    expect(fn () => app(GroqDriver::class)->transcribe($this->asset))->toThrow(RateLimitedException::class);
});

it('returns null on 5xx', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['error' => 'oops'], 502),
    ]);

    expect(app(GroqDriver::class)->transcribe($this->asset))->toBeNull();
});

it('returns null when api key is empty', function () {
    config(['services.groq.api_key' => '']);

    expect(app(GroqDriver::class)->transcribe($this->asset))->toBeNull();
});
