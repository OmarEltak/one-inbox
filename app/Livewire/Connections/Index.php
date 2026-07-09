<?php

namespace App\Livewire\Connections;

use App\Models\ConnectedAccount;
use App\Models\OnboardingRequest;
use App\Models\Page;
use App\Services\EvolutionApiService;
use App\Services\Platforms\FacebookPlatform;
use App\Services\Platforms\TelegramPlatform;
use App\Services\Platforms\WebChatPlatform;
use App\Services\Platforms\WhatsAppPlatform;
use Flux\Flux;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * ══ ARCHITECTURE REFERENCE §1, §14 ══
 * READ docs/ARCHITECTURE.md before modifying:
 *   §1  Managed onboarding is INTENTIONAL until META_APP_VERIFIED=true.
 *       Do NOT remove the "Request connection" flow to "restore" OAuth —
 *       Meta rejects unverified apps at the OAuth callback so direct
 *       OAuth is silently broken for real customers.
 *   §14 Flux 2.x modals use Flux::modal('name')->show()/close(), NOT
 *       $this->dispatch('open-modal', name: 'X') — the dispatch is a
 *       Filament/generic-Livewire pattern that silently no-ops here.
 */
class Index extends Component
{
    /** Live Evolution API instance names, keyed for O(1) lookup. Populated in mount(). */
    public array $waInstanceStates = [];

    // Managed-onboarding request form (Facebook/Instagram while Meta app is unverified)
    public string $requestPlatform      = 'facebook';
    public string $requestBusinessName  = '';
    public string $requestPageUrl       = '';
    public string $requestContactEmail  = '';
    public string $requestContactPhone  = '';
    public string $requestNotes         = '';

    public function mount(): void
    {
        $this->refreshWaStates();
    }

    /** Fetch live WhatsApp instance names from Evolution API (one call for all QR accounts). */
    public function refreshWaStates(): void
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return;
        }

        $hasGateway = ConnectedAccount::where('team_id', $team->id)
            ->where('platform', 'whatsapp')
            ->where('is_active', true)
            ->exists();

        if ($hasGateway) {
            $names = app(EvolutionApiService::class)->fetchConnectedInstanceNames();
            $this->waInstanceStates = array_flip($names); // name => index for O(1) isset()
        }
    }

    /** Disconnect the dead instance silently and open the QR modal to reconnect. */
    public function reconnectGateway(int $accountId): void
    {
        $team = Auth::user()->currentTeam;
        $account = ConnectedAccount::where('team_id', $team->id)
            ->where('id', $accountId)
            ->where('platform', 'whatsapp')
            ->firstOrFail();

        try {
            $instanceName = $account->metadata['gateway_instance'] ?? null;
            if ($instanceName) {
                app(EvolutionApiService::class)->deleteInstance($instanceName, $account->access_token ?? '');
            }
        } catch (\Throwable) {
            // Instance already gone — fine
        }

        $this->dispatch('open-whatsapp-qr');
    }

    #[Computed]
    public function connectedAccounts()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return collect();
        }

        // Surface any account that is active OR still owns at least one active page.
        // Without the second clause, an account row that got deactivated while one of
        // its pages stayed active becomes invisible here — the user sees the page in
        // the inbox but has no way to disconnect it from the Connections screen.
        return ConnectedAccount::with('pages')
            ->where('team_id', $team->id)
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereHas('pages', fn ($pq) => $pq->where('is_active', true));
            })
            ->get();
    }

    #[Computed]
    public function pages()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return collect();
        }

        return Page::with('connectedAccount')
            ->where('team_id', $team->id)
            ->where('is_active', true)
            ->get();
    }

    #[Computed]
    public function hasFacebook(): bool
    {
        return $this->pages->contains(fn ($p) => $p->platform === 'facebook' && $p->is_active);
    }

    #[Computed]
    public function hasInstagram(): bool
    {
        return $this->pages->contains(fn ($p) => $p->platform === 'instagram' && $p->is_active);
    }

    #[Computed]
    public function hasWhatsApp(): bool
    {
        return $this->pages->contains(fn ($p) => $p->platform === 'whatsapp' && $p->is_active);
    }

    #[Computed]
    public function hasTelegram(): bool
    {
        return $this->pages->contains(fn ($p) => $p->platform === 'telegram' && $p->is_active);
    }

    #[Computed]
    public function hasWebChat(): bool
    {
        return $this->pages->contains(fn ($p) => $p->platform === 'webchat' && $p->is_active);
    }

    #[Computed]
    public function webChatPages()
    {
        return $this->pages->filter(fn ($p) => $p->platform === 'webchat' && $p->is_active)->values();
    }

    public bool $showWebChatModal = false;
    public string $newWebChatId = '';
    public string $webChatSiteName = '';

    /** Create a new web chat widget for this team and open the embed-snippet modal. */
    public function connectWebChat(): void
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return;
        }

        $name = trim($this->webChatSiteName) ?: 'My Website';

        $request = Request::create('/internal/webchat-connect', 'POST', [
            'widget_name' => $name,
        ]);

        $account = app(WebChatPlatform::class)->handleCallback($request, $team->id);
        $page = $account->pages()->where('platform', 'webchat')->latest('id')->first();

        $this->newWebChatId = $page?->platform_page_id ?? '';
        $this->showWebChatModal = true;

        unset($this->connectedAccounts, $this->pages);
        $team->clearActivePagesCache();
    }

    public function closeWebChatModal(): void
    {
        $this->showWebChatModal = false;
        $this->newWebChatId = '';
        $this->webChatSiteName = '';
    }

    public function showWebChatSnippetFor(int $pageId): void
    {
        $team = Auth::user()->currentTeam;
        $page = Page::where('team_id', $team->id)
            ->where('id', $pageId)
            ->where('platform', 'webchat')
            ->firstOrFail();

        $this->newWebChatId = $page->platform_page_id;
        $this->showWebChatModal = true;
    }

    public function disconnect(int $accountId): void
    {
        $team = Auth::user()->currentTeam;

        $account = ConnectedAccount::where('team_id', $team->id)
            ->where('id', $accountId)
            ->firstOrFail();

        // Gateway (QR) accounts need to delete the Evolution API instance
        if (! empty($account->metadata['gateway_mode'])) {
            $this->disconnectGateway($account);
        } else {
            $platform = match ($account->platform) {
                'facebook', 'instagram' => app(FacebookPlatform::class),
                'whatsapp'              => app(WhatsAppPlatform::class),
                'telegram'              => app(TelegramPlatform::class),
                default                 => null,
            };

            if ($platform) {
                $platform->disconnect($account);
            } else {
                $account->pages()->update(['is_active' => false]);
                $account->update(['is_active' => false]);
            }
        }

        $team->clearActivePagesCache();
        unset($this->connectedAccounts, $this->pages);

        session()->flash('success', "Disconnected \"{$account->name}\" successfully.");
    }

    private function disconnectGateway(ConnectedAccount $account): void
    {
        try {
            $evolution = app(EvolutionApiService::class);
            // Use gateway_instance from metadata (platform_user_id is now the phone number)
            $instanceName = $account->metadata['gateway_instance'] ?? $account->platform_user_id;
            $evolution->deleteInstance($instanceName, $account->access_token);
        } catch (\Throwable $e) {
            // Instance may already be gone — still mark as inactive
        }

        $account->pages()->update(['is_active' => false]);
        $account->update(['is_active' => false]);
    }

    public function retryPageSubscription(int $pageId): void
    {
        $team = Auth::user()->currentTeam;

        $page = Page::where('team_id', $team->id)
            ->where('id', $pageId)
            ->where('platform', 'facebook')
            ->firstOrFail();

        $fb = app(FacebookPlatform::class);
        $ok = $fb->subscribePage($page);

        $meta = $page->metadata ?? [];
        if ($ok) {
            unset($meta['subscription_error']);
            $page->update(['metadata' => $meta]);
            session()->flash('success', "\"$page->name\" is now subscribed — Messenger messages will start flowing in.");
        } else {
            $meta['subscription_error'] = 'twofa_required';
            $page->update(['metadata' => $meta]);
            session()->flash('error', 'Still failing. Make sure Two-Factor Authentication is enabled on your Facebook account, then try again.');
        }

        unset($this->pages);
    }

    #[On('gateway-connected')]
    public function onGatewayConnected(): void
    {
        unset($this->connectedAccounts, $this->pages);
        $this->refreshWaStates();
    }

    /**
     * Open requests for the current team, keyed by platform. Used in the view to swap
     * the "Connect" CTA for a "Request submitted — awaiting setup" status on platforms
     * where the user already has a pending/in-progress onboarding request.
     */
    #[Computed]
    public function openOnboardingByPlatform(): array
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return [];
        }

        return OnboardingRequest::where('team_id', $team->id)
            ->whereIn('status', [OnboardingRequest::STATUS_PENDING, OnboardingRequest::STATUS_IN_PROGRESS])
            ->get()
            ->keyBy('platform')
            ->all();
    }

    /**
     * Latest non-dismissed rejection per platform. Drives the persistent red banner
     * on the FB/IG cards so the customer sees why we couldn't complete their request.
     */
    #[Computed]
    public function rejectedByPlatform(): array
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return [];
        }

        return OnboardingRequest::where('team_id', $team->id)
            ->where('status', OnboardingRequest::STATUS_REJECTED)
            ->whereNull('customer_dismissed_at')
            ->orderByDesc('completed_at')
            ->get()
            ->groupBy('platform')
            ->map(fn ($group) => $group->first())
            ->all();
    }

    public function dismissRejection(int $requestId): void
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return;
        }

        OnboardingRequest::where('team_id', $team->id)
            ->where('id', $requestId)
            ->where('status', OnboardingRequest::STATUS_REJECTED)
            ->update(['customer_dismissed_at' => now()]);

        unset($this->rejectedByPlatform);
    }

    /**
     * Deactivate a single Page owned by this team. Used from the FB/IG card so the
     * customer can drop a page without touching the underlying ConnectedAccount
     * (which, for admin-handed-off pages, isn't even owned by their team).
     */
    public function disconnectPage(int $pageId): void
    {
        $team = Auth::user()->currentTeam;
        if (! $team) {
            return;
        }

        $page = Page::where('team_id', $team->id)
            ->where('id', $pageId)
            ->firstOrFail();

        $page->update(['is_active' => false]);
        $team->clearActivePagesCache();

        unset($this->pages, $this->connectedAccounts);
        session()->flash('success', "Disconnected \"{$page->name}\". You can request reconnection any time.");
    }

    public function openRequestForm(string $platform): void
    {
        $this->requestPlatform = in_array($platform, ['facebook', 'instagram'], true) ? $platform : 'facebook';
        Flux::modal('onboarding-request')->show();
    }

    public function submitOnboardingRequest(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $user) {
            session()->flash('error', 'Your session expired. Please refresh the page and log in again.');
            Flux::modal('onboarding-request')->close();
            return;
        }

        if (! $team) {
            try {
                Log::warning('submitOnboardingRequest: user has no currentTeam', [
                    'user_id'         => $user->id,
                    'current_team_id' => $user->current_team_id,
                ]);
            } catch (\Throwable) {}
            session()->flash('error', 'No active team on your account. Contact support so we can attach one.');
            Flux::modal('onboarding-request')->close();
            return;
        }

        try {
            $this->validate([
                'requestPlatform'     => 'required|in:facebook,instagram',
                'requestBusinessName' => 'required|string|max:120',
                'requestPageUrl'      => 'nullable|url|max:255',
                'requestContactEmail' => 'required|email|max:190',
                'requestContactPhone' => 'nullable|string|max:40',
                'requestNotes'        => 'nullable|string|max:1500',
            ], attributes: [
                'requestContactEmail' => 'contact email',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            try {
                Log::warning('submitOnboardingRequest: validation failed', [
                    'user_id' => $user->id,
                    'errors'  => $e->errors(),
                ]);
            } catch (\Throwable) {}
            throw $e;
        }

        // Prevent stacking — one open request per platform per team.
        $existing = OnboardingRequest::where('team_id', $team->id)
            ->where('platform', $this->requestPlatform)
            ->whereIn('status', [OnboardingRequest::STATUS_PENDING, OnboardingRequest::STATUS_IN_PROGRESS])
            ->first();

        if ($existing) {
            session()->flash('error', 'You already have an open request for this platform — wait for us to process it.');
            Flux::modal('onboarding-request')->close();
            return;
        }

        try {
            OnboardingRequest::create([
                'team_id'              => $team->id,
                'requested_by_user_id' => $user->id,
                'platform'             => $this->requestPlatform,
                'business_name'        => $this->requestBusinessName,
                'page_url'             => $this->requestPageUrl ?: null,
                'contact_email'        => $this->requestContactEmail,
                'contact_phone'        => $this->requestContactPhone ?: null,
                'notes'                => $this->requestNotes ?: null,
                'status'               => OnboardingRequest::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            try {
                Log::error('submitOnboardingRequest: create failed', [
                    'user_id'  => $user->id,
                    'team_id'  => $team->id,
                    'platform' => $this->requestPlatform,
                    'error'    => $e->getMessage(),
                ]);
            } catch (\Throwable) {}
            session()->flash('error', 'We could not save your request (' . class_basename($e) . '). Our team has been notified.');
            Flux::modal('onboarding-request')->close();
            return;
        }

        $this->reset(['requestBusinessName', 'requestPageUrl', 'requestContactEmail', 'requestContactPhone', 'requestNotes']);
        unset($this->openOnboardingByPlatform);

        session()->flash('success', 'Request submitted. We will set up your page within 24 hours and email you when it is ready.');
        Flux::modal('onboarding-request')->close();
    }

    public function render()
    {
        // metaVerified is a config-driven flag (NOT a Livewire property — see CLAUDE.md §1).
        // Passed here as view data so it stays in scope across every Livewire fragment,
        // whereas an inline @php block in the Blade only reaches the initial full-page
        // render — subsequent Livewire partial renders lose it and error.
        //
        // The FB/IG per-page @foreach loops are also compiled as Livewire fragments
        // (SupportCompiledWireKeys — the wire:click inside triggers it), so their
        // derived collections must be view-data too. The blade also has @php blocks
        // that redefine these to the same values — kept for redundancy; the double-
        // assign is harmless since both sides compute identical collections.
        $pages   = $this->pages;
        $accts   = $this->connectedAccounts;
        $rejects = $this->rejectedByPlatform;

        return view('livewire.connections.index', [
            'metaVerified'      => (bool) config('services.meta.app_verified') || auth()->user()?->is_super_admin,
            'facebookPages'     => $pages->where('platform', 'facebook'),
            'facebookAccounts'  => $accts->where('platform', 'facebook'),
            'fbRejected'        => $rejects['facebook'] ?? null,
            'instagramPages'    => $pages->where('platform', 'instagram'),
            'instagramAccounts' => $accts->where('platform', 'instagram'),
            'igRejected'        => $rejects['instagram'] ?? null,
        ])->layout('layouts.app', ['title' => 'Connections']);
    }
}
