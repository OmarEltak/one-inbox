<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use App\Models\MediaAsset;
use App\Services\Media\MediaStorage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqDriver implements TranscriptionDriver
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function name(): string
    {
        return 'groq';
    }

    public function transcribe(MediaAsset $asset): ?string
    {
        $apiKey  = (string) config('services.groq.api_key');
        $model   = (string) config('services.groq.model');
        $timeout = (int)    config('services.groq.timeout', 5);

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->attach('file', $this->storage->readBytes($asset), basename($asset->path))
                ->asMultipart()
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model'           => $model,
                    'response_format' => 'json',
                ]);
        } catch (ConnectionException $e) {
            Log::info('Groq connection failure', ['e' => $e->getMessage()]);
            return null;
        }

        if ($response->status() === 429) {
            $cool = (int) ($response->header('Retry-After') ?: 60);
            throw new RateLimitedException('Groq rate limited', coolSeconds: $cool);
        }

        if (! $response->successful()) {
            Log::info('Groq non-2xx', ['status' => $response->status()]);
            return null;
        }

        $text = $response->json('text');
        return is_string($text) && $text !== '' ? trim($text) : null;
    }
}
