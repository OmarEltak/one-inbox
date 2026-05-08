<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\Page;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Discord Interactions endpoint.
 *
 * Discord posts to this URL for slash commands, modal submits, and PING probes.
 * Every request is signed with Ed25519 and the signature MUST be validated;
 * Discord disables an endpoint that ever responds 200 to an invalid signature.
 *
 * Incoming interaction types we handle:
 *   1 = PING                (validation probe — respond PONG type=1)
 *   2 = APPLICATION_COMMAND ( /support message:<text> — capture and confirm)
 *
 * Anything else returns a generic ack so future Discord additions don't break
 * the endpoint registration.
 */
class DiscordInteractionController extends Controller
{
    public function handle(Request $request): JsonResponse|Response
    {
        // 1. Signature verification — the application's public key is on the Page
        //    record. We don't yet know which app this is for, so we look up by
        //    application_id from the JSON body (peek at the body before trusting it,
        //    only treat it as authentic AFTER signature verifies).
        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Signature-Ed25519', '');
        $timestamp = (string) $request->header('X-Signature-Timestamp', '');

        if ($signature === '' || $timestamp === '') {
            return response('missing signature headers', 401);
        }

        $body = json_decode($rawBody, true);
        $applicationId = (string) ($body['application_id'] ?? '');
        if ($applicationId === '') {
            return response('missing application_id', 400);
        }

        $page = Page::where('platform', 'discord')
            ->where('platform_page_id', $applicationId)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            return response('unknown application', 404);
        }

        $publicKey = $page->metadata['public_key'] ?? null;
        if (! $publicKey) {
            return response('app not configured', 400);
        }

        if (! $this->verifySignature($signature, $timestamp . $rawBody, $publicKey)) {
            Log::warning('Discord signature mismatch', ['app' => $applicationId]);
            return response('invalid signature', 401);
        }

        // 2. PING (type=1) — respond PONG to validate the endpoint.
        $type = (int) ($body['type'] ?? 0);
        if ($type === 1) {
            return response()->json(['type' => 1]);
        }

        // 3. APPLICATION_COMMAND (type=2) — capture /support message.
        if ($type === 2) {
            $command = strtolower((string) ($body['data']['name'] ?? ''));
            if ($command !== 'support') {
                return $this->ephemeralReply('Unknown command. Try `/support`.');
            }

            $messageText = '';
            foreach (($body['data']['options'] ?? []) as $opt) {
                if (($opt['name'] ?? null) === 'message') {
                    $messageText = (string) ($opt['value'] ?? '');
                    break;
                }
            }
            if ($messageText === '') {
                return $this->ephemeralReply('Please include a message: `/support message: <your question>`');
            }

            $user = $body['member']['user'] ?? $body['user'] ?? [];
            $userId   = (string) ($user['id'] ?? '');
            $userName = (string) ($user['global_name'] ?? $user['username'] ?? 'Discord User');
            $interactionId = (string) ($body['id'] ?? '');

            if ($userId === '') {
                return $this->ephemeralReply('Could not identify your Discord account. Please try again.');
            }

            // Persist a normalized payload — the queue handler reads from this,
            // not the raw Discord envelope, so the schema is stable across API bumps.
            $log = WebhookLog::create([
                'team_id'    => $page->team_id,
                'platform'   => 'discord',
                'event_type' => 'application_command.support',
                'payload'    => [
                    'application_id' => $applicationId,
                    'user_id'        => $userId,
                    'user_name'      => $userName,
                    'content'        => $messageText,
                    'interaction_id' => $interactionId,
                ],
            ]);

            ProcessIncomingMessage::dispatch($log->id);

            return $this->ephemeralReply('Got it — we received your message and will reply via DM shortly.');
        }

        // Other types (MODAL_SUBMIT, MESSAGE_COMPONENT, etc.) — generic ack so the
        // endpoint stays valid even though we don't act on them yet.
        return response()->json(['type' => 4, 'data' => ['content' => 'OK', 'flags' => 64]]);
    }

    private function verifySignature(string $signatureHex, string $message, string $publicKeyHex): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            Log::error('libsodium not available — cannot verify Discord signatures');
            return false;
        }
        try {
            $sigBin = sodium_hex2bin($signatureHex);
            $pkBin  = sodium_hex2bin($publicKeyHex);
            return sodium_crypto_sign_verify_detached($sigBin, $message, $pkBin);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ephemeralReply(string $text): JsonResponse
    {
        return response()->json([
            'type' => 4, // CHANNEL_MESSAGE_WITH_SOURCE
            'data' => [
                'content' => $text,
                'flags'   => 64, // EPHEMERAL — only the user who ran the command sees it
            ],
        ]);
    }
}
