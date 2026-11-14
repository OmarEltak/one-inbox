<?php

namespace App\Livewire\Connections;

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Services\EvolutionApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * ─── Known Wuzapi QR-pairing traps (BOTH have shipped a user-visible bug) ───
 *
 * TRAP 1 (ops): `asternic/wuzapi:latest` goes stale → WhatsApp protocol bump →
 *   `err-client-outdated` on every WebSocket connect → QR endpoint returns 200
 *   with EMPTY QRCode field → modal spinner never resolves. Nothing in this
 *   class can fix that. See `docker-compose.wuzapi.yml` (pinned digest) and
 *   skill `ot1-pro-prod-ops` "Wuzapi image bump" for the mitigation. Symptom
 *   fingerprint: Wuzapi logs show repeated `Client outdated` / `err-client-outdated`.
 *
 * TRAP 2 (code): WhatsApp's "Link a Device" flow emits TWO QRs — the second
 *   one appears only AFTER the user scans the first. Older versions of poll()
 *   had `if (empty($this->qrDataUrl))` around the `/session/qr` fetch, so we
 *   only ever showed the first QR; the user had to close and reopen the modal
 *   to see the second. Fixed by always polling and only updating qrDataUrl
 *   when the response differs. DO NOT re-add that guard "to save requests".
 *
 * Pairing runbook when this feature breaks:
 *   1. `docker logs wuzapi --tail 50` — if you see `err-client-outdated`, it's trap 1.
 *   2. Browser DevTools → Network → watch the `/livewire/update` calls while scanning;
 *      if `qrDataUrl` on the Livewire payload stays identical across the "scan again"
 *      prompt on the phone, it's trap 2.
 */
class WhatsAppQrModal extends Component
{
    public bool $show = false;

    /**
     * Status flow:
     *   idle → creating → qr_pending → connected
     *                              ↘ error
     */
    public string $status = 'idle';

    public string $instanceName = '';
    public string $instanceApiKey = '';
    public string $qrDataUrl = '';
    public string $errorMessage = '';
    public string $connectedPhone = '';
    public string $connectedName = '';

    /** Unix timestamp when QR polling started — used to enforce a timeout */
    public int $pollStartedAt = 0;

    /** Max seconds to wait for QR before giving up */
    private const QR_TIMEOUT_SECONDS = 90;

    // ─── Public Actions ───────────────────────────────────────────────────

    public function openModal(): void
    {
        $this->reset(['instanceName', 'instanceApiKey', 'qrDataUrl', 'errorMessage', 'connectedPhone', 'connectedName']);
        $this->status = 'idle';
        $this->pollStartedAt = 0;
        $this->show = true;
    }

    public function closeModal(): void
    {
        // Clean up the Evolution instance if we started one but never completed
        if (! empty($this->instanceName) && $this->status !== 'connected') {
            $this->cleanupInstance();
        }

        $this->show = false;
        $this->status = 'idle';
        $this->pollStartedAt = 0;
    }

    /**
     * Called when user clicks "Connect" in the modal.
     * Creates an Evolution API instance and waits for QR.
     */
    public function startConnection(): void
    {
        $evolution = app(EvolutionApiService::class);

        if (! $evolution->isConfigured()) {
            $this->setError('WhatsApp QR connection is not set up on this server. Please contact us.');
            return;
        }

        $this->status = 'creating';
        $this->errorMessage = '';

        try {
            $team = Auth::user()->currentTeam;
            // Unique instance name per team connection attempt
            $this->instanceName = 'team_' . $team->id . '_' . Str::random(8);

            $result = $evolution->createInstance($this->instanceName);
            $this->instanceApiKey = $result['apikey'];
            $this->pollStartedAt = time();
            $this->status = 'qr_pending';
        } catch (\Throwable $e) {
            Log::error('WhatsApp QR: createInstance failed', ['error' => $e->getMessage()]);
            $this->setError('Failed to start connection. Please try again or contact support.');
        }
    }

    /**
     * Called every 2 seconds via wire:poll while status = 'qr_pending'.
     * Reads from cache updated by EvolutionWebhookController.
     */
    public function poll(): void
    {
        if ($this->status !== 'qr_pending' || empty($this->instanceName)) {
            return;
        }

        // Timeout: stop polling after QR_TIMEOUT_SECONDS with no connection
        if ($this->pollStartedAt > 0 && (time() - $this->pollStartedAt) > self::QR_TIMEOUT_SECONDS) {
            $this->cleanupInstance();
            $this->setError('Could not generate a QR code. Please try again. If this keeps happening, please contact us.');
            return;
        }

        // Check for error pushed by webhook (QR expired, session refused)
        $error = Cache::pull("evo_qr_error_{$this->instanceName}");
        if ($error) {
            $this->setError($error . ' If this keeps happening, please contact us.');
            return;
        }

        // Check for new QR image pushed by webhook
        $qr = Cache::get("evo_qr_{$this->instanceName}");
        if ($qr) {
            $this->qrDataUrl = $qr;
        }

        // Check if connected (pushed by webhook)
        $connected = Cache::pull("evo_connected_{$this->instanceName}");
        if ($connected) {
            $this->connectedPhone = $connected['phone'] ?? '';
            $this->connectedName = $connected['name'] ?? $this->connectedPhone;
            $this->saveConnection();
            return;
        }

        // Fallback: poll Evolution API directly
        // Used when webhooks can't reach this server (local dev, firewall, etc.)
        $evolution = app(EvolutionApiService::class);

        // WhatsApp's "Link a Device" flow emits a fresh QR after the first scan
        // (the user's phone then shows "scan again" or an approval step that reads
        // a second QR). Previously this method only fetched a QR when qrDataUrl was
        // empty, so the modal froze on the first QR, the user had to close/reopen
        // the modal to get the second one, and pairing felt broken.
        // Always fetch: if Wuzapi has nothing new, getQrCodeAsBase64 returns null and
        // we keep the last-known QR displayed.
        $base64 = $evolution->getQrCodeAsBase64($this->instanceName, $this->instanceApiKey);
        if ($base64 && $base64 !== $this->qrDataUrl) {
            $this->qrDataUrl = $base64;
            Cache::put("evo_qr_{$this->instanceName}", $base64, 30);
        }

        // Check connection state directly
        $state = $evolution->getConnectionState($this->instanceName, $this->instanceApiKey);
        if ($state === 'open') {
            // Try to fetch the real phone number from Evolution API
            $phone = $evolution->getInstancePhone($this->instanceName);
            if (empty($phone)) {
                // Phone not populated yet — wait for next poll cycle (2s) or webhook cache hit.
                // Don't save with empty phone or we create a garbage record keyed by instance name.
                return;
            }
            $this->connectedPhone = $phone;
            $this->connectedName = $phone;
            $this->saveConnection();
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────────────

    /**
     * Persist the WhatsApp connection as a ConnectedAccount + Page.
     * Called after Evolution API confirms state = 'open'.
     *
     * Uses the phone number as the stable key (platform_page_id / platform_user_id)
     * so that reconnections update the same record and preserve conversation history.
     * The current instance name is stored in metadata.gateway_instance so that
     * incoming webhook messages can be routed to the right page.
     */
    private function saveConnection(): void
    {
        try {
            $team = Auth::user()->currentTeam;

            // Phone number is the stable identifier across reconnections.
            // Fall back to instance name only if phone is unavailable (should not happen in practice).
            $stableId = $this->connectedPhone ?: $this->instanceName;
            $displayName = $this->connectedName ?: $stableId;

            $account = ConnectedAccount::updateOrCreate(
                [
                    'team_id'          => $team->id,
                    'platform'         => 'whatsapp',
                    'platform_user_id' => $stableId,
                ],
                [
                    'name'         => $displayName,
                    'access_token' => $this->instanceApiKey,
                    'is_active'    => true,
                    'connected_at' => now(),
                    'metadata'     => [
                        'gateway_mode'     => true,
                        'gateway_instance' => $this->instanceName,
                    ],
                ]
            );

            Page::updateOrCreate(
                [
                    'team_id'          => $team->id,
                    'platform'         => 'whatsapp',
                    'platform_page_id' => $stableId,
                ],
                [
                    'connected_account_id' => $account->id,
                    'name'                 => $displayName,
                    'page_access_token'    => $this->instanceApiKey,
                    'is_active'            => true,
                    'metadata'             => [
                        'gateway_mode'     => true,
                        'gateway_instance' => $this->instanceName,
                        'phone_number'     => $this->connectedPhone,
                    ],
                ]
            );

            $team->clearActivePagesCache();

            $this->status = 'connected';

            // Notify parent Connections component to refresh
            $this->dispatch('gateway-connected');
        } catch (\Throwable $e) {
            Log::error('WhatsApp QR: saveConnection failed', ['error' => $e->getMessage()]);
            $this->setError('Connected to WhatsApp but failed to save. Please contact us.');
        }
    }

    private function cleanupInstance(): void
    {
        if (empty($this->instanceName) || empty($this->instanceApiKey)) {
            return;
        }

        // Bulletproof race guard: if a ConnectedAccount was persisted for THIS instance
        // within the last 90 seconds, this is a live paired session — do NOT delete it
        // even if this method was invoked with a stale Livewire snapshot showing
        // status='qr_pending'. Prior symptom: modal closed with stale snapshot after
        // saveConnection ran, cleanupInstance fired, freshly-paired tenant nuked.
        $recentlyPaired = ConnectedAccount::query()
            ->where('platform', 'whatsapp')
            ->where('connected_at', '>', now()->subSeconds(90))
            ->where('metadata->gateway_instance', $this->instanceName)
            ->exists();

        if ($recentlyPaired) {
            Log::info('WhatsApp QR: cleanupInstance skipped — instance freshly paired', [
                'instance' => $this->instanceName,
                'status'   => $this->status,
            ]);
            return;
        }

        Log::info('WhatsApp QR: cleanupInstance firing deleteInstance', [
            'instance' => $this->instanceName,
            'status'   => $this->status,
        ]);

        try {
            app(EvolutionApiService::class)->deleteInstance($this->instanceName, $this->instanceApiKey);
            Cache::forget("evo_qr_{$this->instanceName}");
            Cache::forget("evo_connected_{$this->instanceName}");
            Cache::forget("evo_qr_error_{$this->instanceName}");
        } catch (\Throwable $e) {
            Log::warning('WhatsApp QR: cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    private function setError(string $message): void
    {
        $this->status = 'error';
        $this->errorMessage = $message;
    }

    public function render()
    {
        return view('livewire.connections.whatsapp-qr-modal');
    }
}
