<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 3;
    private const FAILURE_WINDOW    = 60;   // seconds
    private const OPEN_DURATION     = 300;  // 5 minutes

    public function __construct(private readonly string $driverName) {}

    public function isOpen(): bool
    {
        return Cache::has($this->openKey()) || Cache::has($this->coolKey());
    }

    public function recordFailure(): void
    {
        // Atomic-ish: add() returns true only for the first hit (starts the
        // window); subsequent hits increment. `Cache::add` sets TTL only on
        // first write, so the window naturally rolls forward every 60s.
        if (Cache::add($this->countKey(), 1, self::FAILURE_WINDOW)) {
            $count = 1;
        } else {
            $count = Cache::increment($this->countKey());
        }

        if ($count >= self::FAILURE_THRESHOLD) {
            Cache::put($this->openKey(), 1, self::OPEN_DURATION);
            Cache::forget($this->countKey());
        }
    }

    public function recordSuccess(): void
    {
        Cache::forget($this->countKey());
        Cache::forget($this->openKey());
        Cache::forget($this->coolKey());
    }

    public function cool(int $seconds): void
    {
        Cache::put($this->coolKey(), 1, $seconds);
    }

    private function countKey(): string { return "cb:{$this->driverName}:fails"; }
    private function openKey(): string  { return "cb:{$this->driverName}:open"; }
    private function coolKey(): string  { return "cb:{$this->driverName}:cool"; }
}
