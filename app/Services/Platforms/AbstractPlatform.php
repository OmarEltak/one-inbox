<?php

namespace App\Services\Platforms;

use App\Contracts\MessagingPlatformInterface;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPlatform implements MessagingPlatformInterface
{
    /**
     * Log a raw webhook payload for debugging/replay.
     */
    protected function logWebhook(Request $request, string $platform, ?string $eventType = null, ?int $teamId = null): WebhookLog
    {
        return WebhookLog::create([
            'team_id' => $teamId,
            'platform' => $platform,
            'event_type' => $eventType,
            'payload' => $request->all(),
        ]);
    }

    /**
     * Make an authenticated HTTP request to a platform API.
     */
    protected function apiRequest(string $method, string $url, string $accessToken, array $data = []): array
    {
        $response = Http::withToken($accessToken)
            ->{$method}($url, $data);

        if ($response->failed()) {
            Log::error("Platform API request failed", [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $response->throw();
        }

        return $response->json();
    }
}
