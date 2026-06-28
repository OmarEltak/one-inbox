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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    /** Live Evolution API instance names, keyed for O(1) lookup. Populated in mount(). */
    public array $waInstanceStates = [];

    // Managed-onboarding request form (Facebook/Instagram while Meta app is unverified)
    public string $requestPlatform      = 'facebook';
    public string $requestBusinessName  = '';
    public string $requestPageUrl       = '';
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

    public function openRequestForm(string $platform): void
    {
        $this->requestPlatform = in_array($platform, ['facebook', 'instagram'], true) ? $platform : 'facebook';
        $this->dispatch('open-modal', name: 'onboarding-request');
    }

    public function submitOnboardingRequest(): void
    {
        $team = Auth::user()->currentTeam;
        $user = Auth::user();
        if (! $team || ! $user) {
            return;
        }

        $this->validate([
            'requestPlatform'     => 'required|in:facebook,instagram',
            'requestBusinessName' => 'required|string|max:120',
            'requestPageUrl'      => 'nullable|url|max:255',
            'requestContactPhone' => 'nullable|string|max:40',
            'requestNotes'        => 'nullable|string|max:1500',
        ]);

        // Prevent stacking — one open request per platform per team.
        $existing = OnboardingRequest::where('team_id', $team->id)
            ->where('platform', $this->requestPlatform)
            ->whereIn('status', [OnboardingRequest::STATUS_PENDING, OnboardingRequest::STATUS_IN_PROGRESS])
            ->first();

        if ($existing) {
            session()->flash('error', 'You already have an open request for this platform — wait for us to process it.');
            $this->dispatch('close-modal', name: 'onboarding-request');
            return;
        }

        OnboardingRequest::create([
            'team_id'              => $team->id,
            'requested_by_user_id' => $user->id,
            'platform'             => $this->requestPlatform,
            'business_name'        => $this->requestBusinessName,
            'page_url'             => $this->requestPageUrl ?: null,
            'contact_phone'        => $this->requestContactPhone ?: null,
            'notes'                => $this->requestNotes ?: null,
            'status'               => OnboardingRequest::STATUS_PENDING,
        ]);

        $this->reset(['requestBusinessName', 'requestPageUrl', 'requestContactPhone', 'requestNotes']);
        unset($this->openOnboardingByPlatform);

        session()->flash('success', 'Request submitted. We will set up your page within 24 hours and email you when it is ready.');
        $this->dispatch('close-modal', name: 'onboarding-request');
    }

    public function render()
    {
        return view('livewire.connections.index')
            ->layout('layouts.app', ['title' => 'Connections']);
    }
}
