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

        // If we don't have a QR yet, fetch and render it server-side
        if (empty($this->qrDataUrl)) {
            $base64 = $evolution->getQrCodeAsBase64($this->instanceName, $this->instanceApiKey);
            if ($base64) {
                $this->qrDataUrl = $base64;
                Cache::put("evo_qr_{$this->instanceName}", $base64, 30);
            }
        }

        // Check connection state directly
        $state = $evolution->getConnectionState($this->instanceName, $this->instanceApiKey);
        if ($state === 'open') {
            $this->connectedPhone = 'Connected';
            $this->connectedName = 'WhatsApp Business';
            $this->saveConnection();
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────────────

    /**
     * Persist the WhatsApp connection as a ConnectedAccount + Page.
     * Called after Evolution API confirms state = 'open'.
     */
    private function saveConnection(): void
    {
        try {
            $team = Auth::user()->currentTeam;

            $account = ConnectedAccount::updateOrCreate(
                [
                    'team_id'          => $team->id,
                    'platform'         => 'whatsapp',
                    'platform_user_id' => $this->instanceName,
                ],
                [
                    'name'         => $this->connectedName ?: $this->connectedPhone,
                    'access_token' => $this->instanceApiKey,
                    'is_active'    => true,
                    'connected_at' => now(),
                    'metadata'     => ['gateway_mode' => true],
                ]
            );

            Page::updateOrCreate(
                [
                    'team_id'          => $team->id,
                    'platform'         => 'whatsapp',
                    'platform_page_id' => $this->instanceName,
                ],
                [
                    'connected_account_id' => $account->id,
                    'name'                 => $this->connectedName ?: $this->connectedPhone,
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
