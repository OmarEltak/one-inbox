<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\MediaAsset;
use App\Services\Ai\Transcription\CircuitBreaker;
use App\Services\Ai\Transcription\RateLimitedException;
use App\Services\Ai\Transcription\TranscriptionDriver;
use Illuminate\Support\Facades\Log;

class TranscriptionRouter
{
    /**
     * @param  array<int, TranscriptionDriver>  $drivers  ordered: primary first
     */
    public function __construct(private readonly array $drivers) {}

    public function transcribe(MediaAsset $asset): ?string
    {
        foreach ($this->drivers as $driver) {
            $breaker = new CircuitBreaker($driver->name());

            if ($breaker->isOpen()) {
                continue;
            }

            try {
                $text = $driver->transcribe($asset);
                if ($text !== null && $text !== '') {
                    $breaker->recordSuccess();
                    return $text;
                }
                // null = failure, but not exception. Record but don't cool.
                $breaker->recordFailure();
            } catch (RateLimitedException $e) {
                $breaker->cool($e->coolSeconds);
                Log::info("Transcription driver {$driver->name()} rate-limited", ['e' => $e->getMessage()]);
            } catch (\Throwable $e) {
                $breaker->recordFailure();
                Log::warning("Transcription driver {$driver->name()} threw", ['error' => $e->getMessage()]);
            }
        }

        return null;
    }
}
