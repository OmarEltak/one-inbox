<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp QR-gateway HTTP client.
 *
 * @deprecated The class name is a historical artifact from when this code talked
 *             to Evolution API. It now talks to Wuzapi (whatsmeow-based, Go) which
 *             pairs reliably where Evolution did not. Will be renamed to
 *             WhatsAppGatewayService in a follow-up commit; the public 8-method
 *             surface is intentionally preserved so existing call sites
 *             (SendPlatformMessage::sendViaEvolution, WhatsAppQrModal,
 *             Connections/Index disconnect/reconnect) work without changes.
 *
 * WUZAPI DOCS: https://github.com/asternic/wuzapi
 *
 * AUTH MODEL:
 *   - Admin token (config services.wuzapi.admin_token) creates per-tenant users
 *     via POST /admin/users.
 *   - Each user gets a per-user token; that token is what we store as the
 *     Page's page_access_token (semantically replaces Evolution's per-instance
 *     API key). All session + send calls use it as the `token:` header.
 *
 * METHOD MAPPING (Evolution → Wuzapi):
 *   createInstance($name)             → POST /admin/users  (then POST /session/connect)
 *   getQrCodeAsBase64($name, $tok)    → GET  /session/qr  (auto-reconnects if closed)
 *   getConnectionState($name, $tok)   → GET  /session/status  (loggedIn=open / connected=connecting / else close)
 *   fetchConnectedInstanceNames()     → GET  /admin/users → filter where jid set
 *   getInstancePhone($name)           → look up user by name, parse jid
 *   sendText($name, $tok, $to, $txt)  → POST /chat/send/text {Phone, Body}
 *   logoutInstance($name, $tok)       → POST /session/logout
 *   deleteInstance($name, $tok)       → POST /session/logout (best-effort) + DELETE /admin/users/{id}
 *
 * IF WUZAPI BREAKS:
 *   - Auth header changed         → tweak self::adminAuthHeaders() / self::userAuthHeaders()
 *   - Endpoint paths changed      → search this file for the path string and update
 *   - Webhook payload changed     → update WuzapiWebhookController + ProcessIncomingMessage::processWuzapi()
 *
 * RUNNING WUZAPI:
 *   docker compose -f docker-compose.wuzapi.yml up -d
 *   Container exposes :8082, internal port 8080.
 */
class EvolutionApiService
{
    private string $baseUrl;
    private string $adminToken;
    private string $webhookUrl;
    private string $webhookHost;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('services.wuzapi.url', ''), '/');
        $this->adminToken  = (string) config('services.wuzapi.admin_token', '');
        $this->webhookUrl  = (string) config('services.wuzapi.webhook_url', '');
        $this->webhookHost = (string) config('services.wuzapi.webhook_host', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->adminToken);
    }

    // ─── Instance Management ──────────────────────────────────────────────

    /**
     * Create a Wuzapi user (tenant) and start its session so a QR is available.
     *
     * Returns: ['instanceName' => '...', 'apikey' => '<user-token>']
     *
     * The same shape Evolution returned — call sites store `apikey` on
     * Page.page_access_token and `instanceName` in metadata.gateway_instance.
     *
     * @throws \RuntimeException if user creation or session-start fails
     */
    public function createInstance(string $instanceName): array
    {
        // Per-user token: pre-mint a 40-char hex; Wuzapi uses whatever we hand it.
        $userToken = bin2hex(random_bytes(20));

        $createResp = Http::withHeaders($this->adminAuthHeaders())
            ->post("{$this->baseUrl}/admin/users", [
                'name'      => $instanceName,
                'token'     => $userToken,
                'webhook'   => $this->webhookUrl,
                'events'    => 'Message,ReadReceipt,Connected,Disconnected,LoggedOut,QR,PairSuccess,PairError',
                'subscribe' => 'Message,ReadReceipt',
            ]);

        if (! $createResp->successful()) {
            // If the user already exists (re-creating after a partial disconnect),
            // look up its existing token and reuse it. Wuzapi returns 409 in that case.
            if ($createResp->status() === 409 || str_contains($createResp->body(), 'already exists')) {
                $existing = $this->findUserByName($instanceName);
                if ($existing && ! empty($existing['token'])) {
                    $userToken = $existing['token'];
                } else {
                    Log::error('Wuzapi: createInstance — user exists but token unreachable', [
                        'name' => $instanceName,
                        'body' => $createResp->body(),
                    ]);
                    throw new \RuntimeException('WhatsApp gateway: instance exists but its token could not be retrieved.');
                }
            } else {
                Log::error('Wuzapi: createInstance failed', ['body' => $createResp->body(), 'status' => $createResp->status()]);
                throw new \RuntimeException('Could not create WhatsApp instance: ' . $createResp->body());
            }
        }

        // Start the session so a QR code starts being generated.
        $connectResp = Http::withHeaders($this->userAuthHeaders($userToken))
            ->post("{$this->baseUrl}/session/connect", [
                'Subscribe' => ['Message', 'ReadReceipt'],
                'Immediate' => true,
            ]);

        if (! $connectResp->successful()) {
            Log::warning('Wuzapi: session/connect after createInstance returned non-2xx', [
                'name' => $instanceName,
                'body' => $connectResp->body(),
            ]);
            // Not fatal — the QR-fetch path will retry connect implicitly.
        }

        return [
            'instanceName' => $instanceName,
            'apikey'       => $userToken,
        ];
    }

    /**
     * Get the current QR code as a base64 PNG data URL.
     *
     * If the session is closed (e.g. after the 60-second QR auto-close), this
     * implicitly reconnects so the next call returns a fresh QR.
     */
    public function getQrCodeAsBase64(string $instanceName, string $instanceApiKey): ?string
    {
        $resp = Http::withHeaders($this->userAuthHeaders($instanceApiKey))
            ->get("{$this->baseUrl}/session/qr");

        if ($resp->successful()) {
            $qr = $resp->json('data.QRCode');
            if ($qr) {
                return $qr; // Already a `data:image/png;base64,...` URL
            }
        }

        // No QR available — try a fresh connect so the next poll succeeds.
        Http::withHeaders($this->userAuthHeaders($instanceApiKey))
            ->post("{$this->baseUrl}/session/connect", [
                'Subscribe' => ['Message', 'ReadReceipt'],
                'Immediate' => true,
            ]);

        return null;
    }

    /**
     * Returns one of: 'open', 'connecting', 'close', 'unknown'.
     *
     * Wuzapi gives us {connected, loggedIn} — we collapse to Evolution's vocabulary.
     */
    public function getConnectionState(string $instanceName, string $instanceApiKey): string
    {
        $resp = Http::withHeaders($this->userAuthHeaders($instanceApiKey))
            ->get("{$this->baseUrl}/session/status");

        if (! $resp->successful()) {
            return 'unknown';
        }

        $d = $resp->json('data') ?? [];
        $loggedIn  = (bool) ($d['loggedIn'] ?? false);
        $connected = (bool) ($d['connected'] ?? false);

        return match (true) {
            $loggedIn          => 'open',
            $connected         => 'connecting',
            default            => 'close',
        };
    }

    /**
     * Names of users that currently hold a paired session (jid set).
     * Used by Connections/Index::refreshWaStates() for the green/red dot.
     */
    public function fetchConnectedInstanceNames(): array
    {
        try {
            $resp = Http::timeout(3)
                ->withHeaders($this->adminAuthHeaders())
                ->get("{$this->baseUrl}/admin/users");

            if (! $resp->successful()) {
                return [];
            }

            return collect($resp->json('data') ?? [])
                ->filter(fn ($u) => ! empty($u['jid']))
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Phone number (E.164 without '+') of the paired account, or null if not paired yet.
     * Strips the device suffix from JIDs like "201026361218:27@s.whatsapp.net".
     */
    public function getInstancePhone(string $instanceName): ?string
    {
        $u = $this->findUserByName($instanceName);
        if (! $u || empty($u['jid'])) {
            return null;
        }

        $localPart = explode('@', $u['jid'])[0] ?? '';
        $phone = preg_replace('/:.*$/', '', $localPart) ?: null;

        return $phone ?: null;
    }

    // ─── Messaging ────────────────────────────────────────────────────────

    /**
     * Send a plain text message.
     *
     * @param string $to E.164 without '+', e.g. "201026361218"
     * @return string|null Wuzapi message ID, or null on failure.
     */
    public function sendText(string $instanceName, string $instanceApiKey, string $to, string $text): ?string
    {
        $resp = Http::withHeaders($this->userAuthHeaders($instanceApiKey))
            ->post("{$this->baseUrl}/chat/send/text", [
                'Phone' => $to,
                'Body'  => $text,
            ]);

        if ($resp->successful()) {
            return $resp->json('data.Id');
        }

        Log::error('Wuzapi: sendText failed', [
            'instance' => $instanceName,
            'to'       => $to,
            'status'   => $resp->status(),
            'body'     => $resp->body(),
        ]);

        return null;
    }

    // ─── Instance Lifecycle ───────────────────────────────────────────────

    /**
     * Soft logout: tear down the WhatsApp session but keep the Wuzapi user
     * record so the same name + token can re-pair later.
     */
    public function logoutInstance(string $instanceName, string $instanceApiKey): void
    {
        $resp = Http::withHeaders($this->userAuthHeaders($instanceApiKey))
            ->post("{$this->baseUrl}/session/logout");

        if (! $resp->successful()) {
            Log::warning('Wuzapi: logoutInstance non-2xx', [
                'instance' => $instanceName,
                'status'   => $resp->status(),
                'body'     => $resp->body(),
            ]);
        }
    }

    /**
     * Permanently delete the Wuzapi user (tenant). Disconnects first as a courtesy.
     */
    public function deleteInstance(string $instanceName, string $instanceApiKey): void
    {
        // Best-effort logout first; ignore failures (user may already be disconnected).
        try {
            Http::withHeaders($this->userAuthHeaders($instanceApiKey))
                ->post("{$this->baseUrl}/session/logout");
        } catch (\Throwable) {
            // ignore
        }

        $u = $this->findUserByName($instanceName);
        if (! $u || empty($u['id'])) {
            Log::info('Wuzapi: deleteInstance — no user found, nothing to delete', ['name' => $instanceName]);
            return;
        }

        $resp = Http::withHeaders($this->adminAuthHeaders())
            ->delete("{$this->baseUrl}/admin/users/{$u['id']}");

        if (! $resp->successful()) {
            Log::warning('Wuzapi: deleteInstance non-2xx', [
                'instance' => $instanceName,
                'status'   => $resp->status(),
                'body'     => $resp->body(),
            ]);
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────────────

    /**
     * Look up a user object by tenant name. Returns null if not found.
     * @return array<string,mixed>|null
     */
    private function findUserByName(string $name): ?array
    {
        $resp = Http::withHeaders($this->adminAuthHeaders())
            ->get("{$this->baseUrl}/admin/users");

        if (! $resp->successful()) {
            return null;
        }

        foreach (($resp->json('data') ?? []) as $u) {
            if (($u['name'] ?? null) === $name) {
                return $u;
            }
        }
        return null;
    }

    /** Headers for admin operations (user CRUD, list). */
    private function adminAuthHeaders(): array
    {
        return ['Authorization' => $this->adminToken];
    }

    /** Headers for per-user operations (session, send). */
    private function userAuthHeaders(string $userToken): array
    {
        return ['token' => $userToken];
    }
}
