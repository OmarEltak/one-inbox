<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Ai\Transcription\CircuitBreaker;
use App\Services\Ai\Transcription\RateLimitedException;
use App\Services\Ai\Transcription\TranscriptionDriver;
use App\Services\Ai\TranscriptionRouter;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->team  = Team::factory()->create();
    $this->asset = MediaAsset::factory()->for($this->team)->create(['kind' => 'audio']);
});

it('returns the first successful driver result', function () {
    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { return 'primary result'; }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    expect((new TranscriptionRouter([$primary, $fallback]))->transcribe($this->asset))->toBe('primary result');
});

it('falls through to next driver when primary returns null', function () {
    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { return null; }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    expect((new TranscriptionRouter([$primary, $fallback]))->transcribe($this->asset))->toBe('fallback result');
});

it('skips drivers with open circuit breakers', function () {
    (new CircuitBreaker('primary'))->recordFailure();
    (new CircuitBreaker('primary'))->recordFailure();
    (new CircuitBreaker('primary'))->recordFailure();

    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { throw new \Exception('should not be called'); }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    expect((new TranscriptionRouter([$primary, $fallback]))->transcribe($this->asset))->toBe('fallback result');
});

it('rate-limited exception cools the primary and falls through', function () {
    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { throw new RateLimitedException('429', 30); }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    $router = new TranscriptionRouter([$primary, $fallback]);
    expect($router->transcribe($this->asset))->toBe('fallback result');
    expect((new CircuitBreaker('primary'))->isOpen())->toBeTrue();
});

it('returns null when all drivers fail', function () {
    $a = new class implements TranscriptionDriver {
        public function name(): string { return 'a'; }
        public function transcribe(MediaAsset $x): ?string { return null; }
    };
    $b = new class implements TranscriptionDriver {
        public function name(): string { return 'b'; }
        public function transcribe(MediaAsset $x): ?string { return null; }
    };

    expect((new TranscriptionRouter([$a, $b]))->transcribe($this->asset))->toBeNull();
});
