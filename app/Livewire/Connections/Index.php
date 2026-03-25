<?php

namespace App\Livewire\Connections;

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Services\EvolutionApiService;
use App\Services\Platforms\FacebookPlatform;
use App\Services\Platforms\TelegramPlatform;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    #[Computed]
    public function connectedAccounts()
    {
        $team = Auth::user()->currentTeam;

        if (! $team) {
            return collect();
        }

        return ConnectedAccount::with('pages')
            ->where('team_id', $team->id)
            ->where('is_active', true)
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

    #[On('gateway-connected')]
    public function onGatewayConnected(): void
    {
        unset($this->connectedAccounts, $this->pages);
    }

    public function render()
    {
        return view('livewire.connections.index')
            ->layout('layouts.app', ['title' => 'Connections']);
    }
}
