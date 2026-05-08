<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\Page;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Slack Events API endpoint.
 *
 * Two responsibilities:
 *   1. Echo url_verification challenge during initial endpoint registration.
 *   2. Verify HMAC-SHA256 signature on every event_callback and dispatch
 *      ProcessIncomingMessage for routing.
 *
 * Signature verification:
 *   sigBasestring = "v0:" + X-Slack-Request-Timestamp + ":" + raw body
 *   expected      = "v0=" + hmac_sha256(signing_secret, sigBasestring)
 *   compare to X-Slack-Signature using hash_equals (timing-safe)
 *
 * The signing secret is stored per-Page (each connected workspace has its own).
 * We resolve the workspace via the team_id in the event payload before we know
 * which secret to verify against — see resolvePage().
 */
class SlackWebhookController extends Controller
{
    /** Reject events with timestamps older than this to thwart replay attacks. */
    private const MAX_TIMESTAMP_SKEW_SECONDS = 300;

    public function handle(Request $request): Response
    {
        $payload = $request->all();

        // 1. URL verification: Slack sends this once during endpoint setup. Echo back.
        if (($payload['type'] ?? null) === 'url_verification') {
            return response($payload['challenge'] ?? '', 200);
        }

        // 2. Resolve which workspace's signing secret to verify with.
        $teamId = $payload['team_id'] ?? null;
        $page   = $teamId ? $this->resolvePage((string) $teamId) : null;

        if (! $page) {
            Log::warning('Slack event for unknown workspace', ['team_id' => $teamId]);
            return response('Unknown workspace', 404);
        }

        $signingSecret = $page->metadata['signing_secret'] ?? null;
        if (! $signingSecret) {
            Log::warning('Slack page missing signing_secret', ['page_id' => $page->id]);
            return response('Misconfigured', 400);
        }

        if (! $this->verifySignature($request, $signingSecret)) {
            Log::warning('Slack signature mismatch', ['page_id' => $page->id]);
            return response('Invalid signature', 403);
        }

        // 3. Drop our own bot's echoes so we don't loop on agent replies.
        $event = $payload['event'] ?? [];
        $botUserId = $page->metadata['bot_user_id'] ?? null;
        if ($botUserId && (($event['user'] ?? null) === $botUserId || ($event['bot_id'] ?? null))) {
            return response('OK', 200);
        }

        // 4. Persist + queue for processing.
        $log = WebhookLog::create([
            'team_id'    => $page->team_id,
            'platform'   => 'slack',
            'event_type' => $this->detectEventType($payload),
            'payload'    => $payload,
        ]);

        ProcessIncomingMessage::dispatch($log->id);

        return response('OK', 200);
    }

    private function resolvePage(string $workspaceId): ?Page
    {
        return Page::where('platform', 'slack')
            ->where('platform_page_id', $workspaceId)
            ->where('is_active', true)
            ->first();
    }

    private function verifySignature(Request $request, string $signingSecret): bool
    {
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (! $timestamp || ! $signature) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::MAX_TIMESTAMP_SKEW_SECONDS) {
            return false;
        }

        $rawBody = $request->getContent();
        $base = 'v0:' . $timestamp . ':' . $rawBody;
        $expected = 'v0=' . hash_hmac('sha256', $base, $signingSecret);

        return hash_equals($expected, $signature);
    }

    private function detectEventType(array $payload): string
    {
        $type = $payload['type'] ?? null;
        if ($type === 'event_callback') {
            return 'event_callback.' . ($payload['event']['type'] ?? 'unknown');
        }
        return (string) ($type ?? 'unknown');
    }
}
