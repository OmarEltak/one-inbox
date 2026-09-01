<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use App\Models\MediaAsset;

interface TranscriptionDriver
{
    public function name(): string;

    /**
     * @return string|null transcribed text, or null on failure
     * @throws RateLimitedException on rate-limit / circuit-trip-worthy failures
     */
    public function transcribe(MediaAsset $asset): ?string;
}
