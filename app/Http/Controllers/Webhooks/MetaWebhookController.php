<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    /**
     * Handle both GET (verification) and POST (event) requests from Meta.
     */
    public function handle(Request $request): Response|string
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        return $this->process($request);
    }

    /**
     * Meta webhook verification challenge.
     * Meta sends: hub.mode, hub.verify_token, hub.challenge
     */
    protected function verify(Request $request): Response|string
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.meta.webhook_verify_token')) {
            Log::info('Meta webhook verified');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Process incoming webhook events.
     * Return 200 immediately, then process asynchronously.
     */
    protected function process(Request $request): Response
    {
        // Verify HMAC signature
        if (! $this->verifySignature($request)) {
            Log::warning('Meta webhook signature verification failed');

            return response('Invalid signature', 403);
        }

        // Log raw payload first
        $log = WebhookLog::create([
            'platform' => $this->detectPlatform($request),
            'event_type' => $request->input('object'),
            'payload' => $request->all(),
        ]);

        // Dispatch to queue for async processing
        ProcessIncomingMessage::dispatch($log->id);

        // Return 200 immediately (Meta retries aggressively on failure)
        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Verify X-Hub-Signature-256 HMAC header.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            config('services.meta.app_secret')
        );

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Detect which Meta platform the webhook is for.
     */
    protected function detectPlatform(Request $request): string
    {
        $object = $request->input('object');

        return match ($object) {
            'whatsapp_business_account' => 'whatsapp',
            'instagram' => 'instagram',
            default => 'facebook', // 'page' object = Messenger
        };
    }
}
