<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SnapchatWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Snapchat sends GET with a challenge param for webhook verification
        if ($request->isMethod('get')) {
            $challenge = $request->query('hub_challenge');
            if ($challenge) {
                return response($challenge, 200);
            }

            return response('OK', 200);
        }

        // Verify HMAC signature
        if (! $this->verifySignature($request)) {
            Log::warning('Snapchat webhook signature mismatch');

            return response('Forbidden', 403);
        }

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? 'unknown';

        // Only process inbound direct messages
        if ($eventType !== 'direct_message') {
            return response('OK', 200);
        }

        $log = WebhookLog::create([
            'platform'   => 'snapchat',
            'event_type' => $eventType,
            'payload'    => $payload,
        ]);

        ProcessIncomingMessage::dispatch($log->id);

        return response('OK', 200);
    }

    protected function verifySignature(Request $request): bool
    {
        $secret = config('services.snapchat.webhook_secret');

        if (! $secret) {
            return true; // Skip if not configured
        }

        $signature = $request->header('X-Snapchat-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, ltrim($signature, 'sha256='));
    }
}
