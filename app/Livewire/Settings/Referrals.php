<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Models\Referral;
use App\Services\Referrals\ReferralService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Referrals extends Component
{
    public string $redeemCode = '';

    public function redeem(ReferralService $service): void
    {
        $team = Auth::user()->currentTeam;
        if ($team === null) {
            return;
        }

        $result = $service->redeem($this->redeemCode, $team);

        if ($result['success']) {
            session()->flash('referral_success', $result['message']);
            $this->redeemCode = '';
        } else {
            $this->addError('redeemCode', $result['message']);
        }
    }

    #[Computed]
    public function referral(): Referral
    {
        $team = Auth::user()->currentTeam;
        return app(ReferralService::class)->getOrCreateForTeam($team);
    }

    #[Computed]
    public function redemptions()
    {
        $team = Auth::user()->currentTeam;
        return Referral::query()
            ->where('referrer_team_id', $team->id)
            ->whereNotNull('redeemed_at')
            ->with('referredTeam')
            ->orderByDesc('redeemed_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.settings.referrals');
    }
}
