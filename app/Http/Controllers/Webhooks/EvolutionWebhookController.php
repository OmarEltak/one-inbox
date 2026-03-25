<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessage;
use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming webhook events from Evolution API (WhatsApp QR gateway).
 *
 * Route: POST /api/webhooks/evolution
 *
 * ─── EVOLUTION API WEBHOOK EVENTS ───────────────────────────────────────────
 *
 * All events share this outer envelope:
 * {
 *   "event":    "MESSAGES_UPSERT" | "CONNECTION_UPDATE" | "QRCODE_UPDATED",
 *   "instance": "<instanceName>",
 *   "data":     { ... event-specific payload ... },
 *   "apikey":   "<instance-apikey>"   ← used to verify the request
 * }
 *
 * Events handled:
 *
 *  QRCODE_UPDATED  — QR code image ready for user to scan
 *    data.qrcode.base64   → PNG data URI, stored in cache for Livewire poll
 *    data.statusCode = 500 → QR limit reached (user didn't scan in time)
 *
 *  CONNECTION_UPDATE — WhatsApp session state changed
 *    data.state = 'open'      → successfully connected, save ConnectedAccount + Page
 *    data.state = 'close'     → session closed / phone offline (statusReason 428)
 *    data.state = 'refused'   → bad session, need to re-scan (statusReason 500)
 *    data.wuid                → connected phone number (e.g. "201012345678@s.whatsapp.net")
 *    data.profileName         → WhatsApp display name
 *
 *  MESSAGES_UPSERT — incoming message from a contact
 *    data.key.fromMe = true   → echo of our own send, SKIP
 *    data.key.remoteJid       → sender's JID (e.g. "201099887766@s.whatsapp.net")
 *    data.messageType         → "conversation" | "imageMessage" | "audioMessage" | etc.
 *    data.message.conversation → plain text content (when messageType = "conversation")
 *    data.pushName            → sender's WhatsApp display name
 *
 * ─── IF SOMETHING BREAKS ────────────────────────────────────────────────────
 *
 * 1. "Events not received" → check EVOLUTION_WEBHOOK_URL in .env is publicly accessible
 * 2. "Wrong event structure" → Evolution API changed payload shape → update extractors below
 * 3. "Auth failing" → apikey verification logic is in verifyRequest() below
 * 4. "QR not showing" → check QRCODE_UPDATED handler and Cache TTL (currently 30s)
 * 5. "Connected but not saved" → check CONNECTION_UPDATE 'open' handler and saveConnection()
 *
 * ────────────────────────────────────────────────────────────────────────────
 */
class EvolutionWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $instanceName = $payload['instance'] ?? null;

        if (! $event || ! $instanceName) {
            return response('Bad Request', 400);
        }

        // Verify the request came from our Evolution API instance
        if (! $this->verifyRequest($payload, $instanceName)) {
            Log::warning('Evolution webhook: auth failed', ['instance' => $instanceName]);
            return response('Forbidden', 403);
        }

        try {
            match ($event) {
                'QRCODE_UPDATED'    => $this->handleQrUpdated($instanceName, $payload['data'] ?? []),
                'CONNECTION_UPDATE' => $this->handleConnectionUpdate($instanceName, $payload['data'] ?? []),
                'MESSAGES_UPSERT'   => $this->handleMessageUpsert($instanceName, $payload),
                default             => null, // Silently ignore other events
            };
        } catch (\Throwable $e) {
            Log::error('Evolution webhook: processing failed', [
                'event'    => $event,
                'instance' => $instanceName,
                'error'    => $e->getMessage(),
            ]);
        }

        // Always return 200 immediately — never block the gateway
        return response('OK', 200);
    }

    // ─── Event Handlers ───────────────────────────────────────────────────

    /**
     * QR code image is ready. Store it in cache so the Livewire modal can display it.
     * Cache key: "evo_qr_{instanceName}" — TTL 30s (QR codes expire every ~20s)
     */
    private function handleQrUpdated(string $instanceName, array $data): void
    {
        // QR limit reached — user didn't scan in time
        if (($data['statusCode'] ?? null) === 500) {
            Cache::put("evo_qr_error_{$instanceName}", 'QR code expired. Please try again.', 60);
            Cache::forget("evo_qr_{$instanceName}");
            return;
        }

        $base64 = $data['qrcode']['base64'] ?? null;

        if ($base64) {
            Cache::put("evo_qr_{$instanceName}", $base64, 30);
        }
    }

    /**
     * WhatsApp session state changed.
     *
     * 'open'    → connected successfully → save ConnectedAccount + Page, notify Livewire
     * 'close'   → disconnected (phone offline, user logged out from phone)
     * 'refused' → session corrupted, needs fresh QR scan
     */
    private function handleConnectionUpdate(string $instanceName, array $data): void
    {
        $state = $data['state'] ?? 'unknown';

        if ($state === 'open') {
            // Strip @s.whatsapp.net suffix from JID to get plain phone number
            $rawJid = $data['wuid'] ?? '';
            $phone = str_replace('@s.whatsapp.net', '', $rawJid);
            $pushName = $data['profileName'] ?? $phone;

            // Store in cache so Livewire modal detects the connection (initial scan)
            Cache::put("evo_connected_{$instanceName}", [
                'phone' => $phone,
                'name'  => $pushName,
            ], 120);

            // Reconnection (e.g. phone came back online): mark the page active.
            // The page is keyed by phone number; the instance name is stored in metadata.
            if ($phone) {
                Page::where('platform', 'whatsapp')
                    ->where('platform_page_id', $phone)
                    ->update(['is_active' => true]);

                ConnectedAccount::where('platform', 'whatsapp')
                    ->where('platform_user_id', $phone)
                    ->update(['is_active' => true]);
            }
        }

        if ($state === 'close' || $state === 'refused') {
            // Mark as inactive — look up by gateway_instance metadata since that's what we know here
            Page::where('platform', 'whatsapp')
                ->whereJsonContains('metadata->gateway_instance', $instanceName)
                ->update(['is_active' => false]);

            ConnectedAccount::where('platform', 'whatsapp')
                ->whereJsonContains('metadata->gateway_instance', $instanceName)
                ->update(['is_active' => false]);

            // Notify Livewire modal if connection was lost during QR flow
            Cache::put("evo_qr_error_{$instanceName}", 'Connection closed. Please try again.', 60);
        }
    }

    /**
     * Incoming message from a contact.
     * Normalise into a WebhookLog and dispatch ProcessIncomingMessage.
     */
    private function handleMessageUpsert(string $instanceName, array $rawPayload): void
    {
        $data = $rawPayload['data'] ?? [];

        // Skip echo messages (messages sent by us through this app)
        if ($data['key']['fromMe'] ?? false) {
            return;
        }

        // Only handle text messages for now (media support can be added later)
        $messageType = $data['messageType'] ?? 'unknown';
        $supportedTypes = ['conversation', 'extendedTextMessage', 'imageMessage', 'audioMessage', 'videoMessage', 'documentMessage'];
        if (! in_array($messageType, $supportedTypes)) {
            return;
        }

        $log = WebhookLog::create([
            'platform'   => 'whatsapp_gateway',
            'event_type' => 'MESSAGES_UPSERT',
            'payload'    => $rawPayload, // full payload stored for debugging
        ]);

        ProcessIncomingMessage::dispatch($log->id);
    }

    // ─── Auth Verification ────────────────────────────────────────────────

    /**
     * Verify this webhook came from our Evolution API.
     *
     * Strategy: the webhook body includes "apikey" which is the instance's API key.
     * We look up the ConnectedAccount by instanceName and compare stored keys.
     * If no account exists yet (first QR scan), we trust based on instanceName prefix
     * matching the expected format "team_{id}_...".
     *
     * IF Evolution API changes how it authenticates webhooks, update this method.
     */
    private function verifyRequest(array $payload, string $instanceName): bool
    {
        // If no global API key configured, skip verification (dev mode)
        $globalKey = config('services.evolution.api_key', '');
        if (empty($globalKey)) {
            return true;
        }

        // Evolution API sends the instance apikey in the webhook body
        $sentApiKey = $payload['apikey'] ?? '';

        // During QR flow: instance exists in Evolution but not yet in our DB
        // Verify by checking the instanceName follows our naming convention: team_{id}_
        if (empty($sentApiKey)) {
            return str_starts_with($instanceName, 'team_');
        }

        // After first connection: look up stored apikey — pages/accounts are now keyed
        // by phone number, with instanceName in metadata.gateway_instance.
        $account = ConnectedAccount::where('platform', 'whatsapp')
            ->where(function ($q) use ($instanceName) {
                $q->whereJsonContains('metadata->gateway_instance', $instanceName)
                  ->orWhere('platform_user_id', $instanceName); // legacy fallback
            })
            ->first();

        if ($account) {
            return $account->access_token === $sentApiKey;
        }

        // Instance not in DB yet (initial QR scan) — trust if naming convention matches
        return str_starts_with($instanceName, 'team_');
    }
}
