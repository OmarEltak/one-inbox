<?php

declare(strict_types=1);

use App\Services\Ai\Transcription\CircuitBreaker;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
    $this->breaker = new CircuitBreaker('test');
});

it('reports healthy by default', function () {
    expect($this->breaker->isOpen())->toBeFalse();
});

it('opens after 3 failures in 60s', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeFalse();

    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeTrue();
});

it('cooling window blocks calls temporarily', function () {
    $this->breaker->cool(seconds: 60);
    expect($this->breaker->isOpen())->toBeTrue();
});

it('recording success resets state', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    $this->breaker->recordSuccess();
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeFalse();
});
