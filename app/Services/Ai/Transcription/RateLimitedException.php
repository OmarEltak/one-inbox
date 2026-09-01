<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

class RateLimitedException extends \RuntimeException
{
    public function __construct(string $message = '', public readonly int $coolSeconds = 60)
    {
        parent::__construct($message);
    }
}
