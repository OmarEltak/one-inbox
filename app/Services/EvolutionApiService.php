<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Evolution API v2 HTTP client.
 *
 * Evolution API is a self-hosted WhatsApp gateway based on Baileys.
 * It lets users connect a regular WhatsApp Business number via QR scan
 * — no Meta Business Manager setup required.
 *
 * OFFICIAL DOCS: https://doc.evolution-api.com/v2/en/get-started/introduction
 * VERSION USED:  v2.x (tested against v2.2.x – v2.3.x)
 *
 * ─── IF EVOLUTION API UPDATES AND SOMETHING BREAKS ─────────────────────────
 *
 * 1. Auth header changed
 *    → Edit: self::headers() — currently sends `apikey: <key>`
 *
 * 2. Create instance endpoint or body changed
 *    → Edit: createInstance() — POST /instance/create
 *
 * 3. QR code endpoint changed
 *    → Edit: getQrCode() — GET /instance/connect/{instance}
 *
 * 4. Connection state endpoint changed
 *    → Edit: getConnectionState() — GET /instance/connectionState/{instance}
 *
 * 5. Send text endpoint or body changed
 *    → Edit: sendText() — POST /message/sendText/{instance}
 *
 * 6. Webhook config endpoint changed
 *    → Edit: setWebhook() — POST /webhook/set/{instance}
 *
 * 7. Delete/logout endpoints changed
 *    → Edit: deleteInstance() / logoutInstance()
 *
 * ─── WEBHOOK PAYLOAD CHANGES ────────────────────────────────────────────────
 * Webhook payloads are handled in:
 *   app/Http/Controllers/Webhooks/EvolutionWebhookController.php
 *   app/Jobs/ProcessIncomingMessage.php → processEvolution()
 * ────────────────────────────────────────────────────────────────────────────
 */
class EvolutionApiService
{
    private string $baseUrl;
    private string $globalApiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.evolution.url', ''), '/');
        $this->globalApiKey = config('services.evolution.api_key', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->globalApiKey);
    }

    // ─── Instance Management ──────────────────────────────────────────────

    /**
     * Create a new WhatsApp instance and configure its webhook.
     *
     * Evolution API endpoint: POST /instance/create
     * Uses global API key for auth.
     *
     * Returns: ['instanceName' => '...', 'apikey' => '...']
     * Throws on failure.
     */
    public function createInstance(string $instanceName): array
    {
        $webhookUrl = config('services.evolution.webhook_url');

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/instance/create", [
                'instanceName' => $instanceName,
                'integration'  => 'WHATSAPP-BAILEYS',
                'qrcode'       => true,
                'webhook'      => [
                    'enabled'         => ! empty($webhookUrl),
                    'url'             => $webhookUrl,
                    'webhookByEvents' => false,
                    'webhookBase64'   => false,
                    'headers'         => $this->webhookHeaders(), // e.g. Host override for local dev
                    'events'          => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE', 'QRCODE_UPDATED'],
                ],
            ]);

        if ($response->successful()) {
            // NOTE: Evolution API v2.2.x returns hash as a plain string, not an object
            // e.g. {"instance":{...}, "hash": "5B0B1F63-...", ...}
            // IF a future version wraps it as {"hash":{"apikey":"..."}} → update this line
            return [
                'instanceName' => $response->json('instance.instanceName'),
                'apikey'       => $response->json('hash'),
            ];
        }

        $errorMsg = $response->json('response.message.0') ?? $response->body();
        Log::error('Evolution API: createInstance failed', ['body' => $response->body()]);
        throw new \RuntimeException("Could not create WhatsApp instance: {$errorMsg}");
    }

    /**
     * Get the current QR code for a pending instance as a base64 PNG data URL.
     *
     * Evolution API endpoint: GET /instance/connect/{instance}
     * Returns raw QR string code; we convert to base64 PNG using chillerlan/php-qrcode.
     *
     * Returns base64 PNG data URI string, or null if not ready / instance not found.
     *
     * IF Evolution API changes response fields → update $response->json('code') below.
     */
    public function getQrCodeAsBase64(string $instanceName, string $instanceApiKey): ?string
    {
        $response = Http::withHeaders($this->instanceHeaders($instanceApiKey))
            ->get("{$this->baseUrl}/instance/connect/{$instanceName}");

        if (! $response->successful()) {
            Log::warning('Evolution API: getQrCode failed', ['instance' => $instanceName, 'status' => $response->status()]);
            return null;
        }

        $rawCode = $response->json('code');
        if (! $rawCode) {
            return null;
        }

        // Convert raw WA QR string to base64 PNG
        // IF chillerlan/php-qrcode API changes → update this block
        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'returnResource' => false,
                'imageBase64' => true,
                'scale' => 8,
                'quietzoneSize' => 2,
            ]);

            return (new QRCode($options))->render($rawCode);
        } catch (\Throwable $e) {
            Log::warning('Evolution API: QR PNG generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get the connection state of an instance.
     *
     * Evolution API endpoint: GET /instance/connectionState/{instance}
     *
     * Returns one of: 'open', 'connecting', 'close', 'refused'
     * Returns 'unknown' on any error.
     */
    public function getConnectionState(string $instanceName, string $instanceApiKey): string
    {
        $response = Http::withHeaders($this->instanceHeaders($instanceApiKey))
            ->get("{$this->baseUrl}/instance/connectionState/{$instanceName}");

        if ($response->successful()) {
            return $response->json('instance.state', 'unknown');
        }

        return 'unknown';
    }

    /**
     * Return all live instance names from Evolution API (3-second timeout).
     * Used by the Connections page to show real-time connection status without N+1 calls.
     */
    public function fetchConnectedInstanceNames(): array
    {
        try {
            $response = Http::timeout(3)->withHeaders($this->headers())
                ->get("{$this->baseUrl}/instance/fetchInstances");

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json() ?? [])
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Return the connected phone number for an instance, or null if not yet connected.
     * Uses the global API key to read ownerJid from fetchInstances.
     */
    public function getInstancePhone(string $instanceName): ?string
    {
        $response = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/instance/fetchInstances");

        if (! $response->successful()) {
            return null;
        }

        foreach ($response->json() ?? [] as $instance) {
            if (($instance['name'] ?? '') === $instanceName && ! empty($instance['ownerJid'])) {
                return str_replace('@s.whatsapp.net', '', $instance['ownerJid']);
            }
        }

        return null;
    }

    // ─── Messaging ────────────────────────────────────────────────────────

    /**
     * Send a plain text message to a WhatsApp number.
     *
     * Evolution API endpoint: POST /message/sendText/{instance}
     *
     * @param  string $to  Phone number with country code, no '+' (e.g. "201012345678")
     * Returns the Evolution message ID string, or null on failure.
     */
    public function sendText(string $instanceName, string $instanceApiKey, string $to, string $text): ?string
    {
        $response = Http::withHeaders($this->instanceHeaders($instanceApiKey))
            ->post("{$this->baseUrl}/message/sendText/{$instanceName}", [
                'number' => $to,
                'text'   => $text,
            ]);

        if ($response->successful()) {
            // Response key.id is the message identifier
            return $response->json('key.id');
        }

        Log::error('Evolution API: sendText failed', [
            'instance' => $instanceName,
            'to'       => $to,
            'status'   => $response->status(),
            'body'     => $response->body(),
        ]);

        return null;
    }

    // ─── Instance Lifecycle ───────────────────────────────────────────────

    /**
     * Disconnect the WhatsApp session but keep the instance config.
     * Use this when the user wants to re-scan a new QR later.
     *
     * Evolution API endpoint: DELETE /instance/logout/{instance}
     */
    public function logoutInstance(string $instanceName, string $instanceApiKey): void
    {
        $response = Http::withHeaders($this->instanceHeaders($instanceApiKey))
            ->delete("{$this->baseUrl}/instance/logout/{$instanceName}");

        if ($response->failed()) {
            Log::warning('Evolution API: logoutInstance failed', ['instance' => $instanceName, 'body' => $response->body()]);
        }
    }

    /**
     * Permanently delete an instance and all its data.
     * Use this when the user disconnects from the Connections page.
     *
     * Evolution API endpoint: DELETE /instance/delete/{instance}
     */
    public function deleteInstance(string $instanceName, string $instanceApiKey): void
    {
        $response = Http::withHeaders($this->instanceHeaders($instanceApiKey))
            ->delete("{$this->baseUrl}/instance/delete/{$instanceName}");

        if ($response->failed()) {
            Log::warning('Evolution API: deleteInstance failed', ['instance' => $instanceName, 'body' => $response->body()]);
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────────────

    /**
     * Headers for requests using the global API key (instance creation, listing).
     * IF Evolution API changes its auth header name, update here.
     */
    private function headers(): array
    {
        return ['apikey' => $this->globalApiKey];
    }

    /**
     * Headers for requests scoped to a specific instance.
     * IF Evolution API changes its per-instance auth header, update here.
     */
    private function instanceHeaders(string $instanceApiKey): array
    {
        return ['apikey' => $instanceApiKey];
    }

    /**
     * Custom headers to include in webhook deliveries FROM Evolution API TO Laravel.
     * Used to set the correct Host header when Evolution API runs in Docker
     * and needs to reach Laravel via a domain name (e.g. one-inbox.test in local dev).
     *
     * In production: leave EVOLUTION_WEBHOOK_HOST empty — not needed.
     * In local dev:  set EVOLUTION_WEBHOOK_HOST=one-inbox.test
     */
    private function webhookHeaders(): array
    {
        $host = config('services.evolution.webhook_host', '');
        return $host ? ['Host' => $host] : [];
    }
}
