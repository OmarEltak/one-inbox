<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TikTokWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // TikTok sends a GET with challenge param for webhook verification
        if ($request->isMethod('get')) {
            $challenge = $request->query('challenge');
            if ($challenge) {
                return response($challenge, 200);
            }

            return response('OK', 200);
        }

        // Verify HMAC signature
        if (! $this->verifySignature($request)) {
            Log::warning('TikTok webhook signature mismatch');

            return response('Forbidden', 403);
        }

        $payload = $request->all();
        $eventType = $payload['type'] ?? 'unknown';

        // Only process incoming direct messages
        if ($eventType !== 'direct_message') {
            return response('OK', 200);
        }

        $log = WebhookLog::create([
            'platform' => 'tiktok',
            'event_type' => $eventType,
            'payload' => $payload,
        ]);

        ProcessIncomingMessage::dispatch($log->id);

        return response('OK', 200);
    }

    protected function verifySignature(Request $request): bool
    {
        $secret = config('services.tiktok.webhook_secret');

        if (! $secret) {
            return false;
        }

        $signature = $request->header('X-TikTok-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        // Strip "sha256=" prefix if present (ltrim would strip individual chars, not the prefix)
        $prefix = 'sha256=';
        $actual = str_starts_with($signature, $prefix) ? substr($signature, strlen($prefix)) : $signature;

        return hash_equals($expected, $actual);
    }
}
