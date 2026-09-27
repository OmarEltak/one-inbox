<?php

declare(strict_types=1);

namespace App\Services\Referrals;

use App\Models\Referral;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Referral coupon operations.
 *
 * All discount values are read from config('referrals.*') at redemption
 * time and SNAPSHOTTED into the referral row so historical grants remain
 * stable across config changes.
 */
final class ReferralService
{
    /**
     * Return the team's single active referral, creating one if none exists.
     */
    public function getOrCreateForTeam(Team $team): Referral
    {
        $existing = Referral::query()
            ->active()
            ->where('referrer_team_id', $team->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Referral::query()->create([
            'code' => Referral::generateCode(),
            'referrer_team_id' => $team->id,
        ]);
    }

    /**
     * Redeem a code on behalf of $redeemingTeam.
     *
     * Enforces:
     *  - code must exist
     *  - can't redeem your own code
     *  - can't redeem a code already redeemed
     *  - can't redeem a code that has expired
     *  - each team can only redeem once (lifetime)
     *
     * On success: sets referred_team_id, redeemed_at, snapshots current
     * config discount values, and grants the reciprocal discount to the
     * referrer team via settings['pending_referral_discount_percent'].
     *
     * @return array{success: bool, message: string, discount_percent: float}
     */
    public function redeem(string $code, Team $redeemingTeam): array
    {
        $code = trim($code);

        if ($code === '') {
            return $this->failure('Please enter a referral code.');
        }

        $referral = Referral::query()->where('code', $code)->first();

        if ($referral === null) {
            return $this->failure('That referral code is not valid.');
        }

        if ($referral->referrer_team_id === $redeemingTeam->id) {
            return $this->failure("You can't redeem your own referral code.");
        }

        if ($referral->redeemed_at !== null) {
            return $this->failure('That referral code has already been redeemed.');
        }

        if ($referral->expires_at !== null && $referral->expires_at->isPast()) {
            return $this->failure('That referral code has expired.');
        }

        $alreadyRedeemed = Referral::query()
            ->where('referred_team_id', $redeemingTeam->id)
            ->whereNotNull('redeemed_at')
            ->exists();

        if ($alreadyRedeemed) {
            return $this->failure('You have already redeemed a referral code.');
        }

        $discount = (float) config('referrals.discount_percent', 25);
        $reciprocal = (float) config('referrals.reciprocal_discount_percent', 25);

        DB::transaction(function () use ($referral, $redeemingTeam, $discount, $reciprocal): void {
            $referral->update([
                'referred_team_id' => $redeemingTeam->id,
                'redeemed_at' => now(),
                'discount_percent' => $discount,
                'reciprocal_discount_percent' => $reciprocal,
            ]);

            // Grant reciprocal discount to the referrer.
            $referrer = $referral->referrerTeam()->first();
            if ($referrer !== null) {
                $settings = $referrer->settings ?? [];
                $settings['pending_referral_discount_percent'] = $reciprocal;
                $referrer->settings = $settings;
                $referrer->save();
            }

            // Grant the redeemer's discount so it applies to their first paid month.
            $settings = $redeemingTeam->settings ?? [];
            $settings['pending_referral_discount_percent'] = $discount;
            $redeemingTeam->settings = $settings;
            $redeemingTeam->save();
        });

        return [
            'success' => true,
            'message' => "Referral applied — you'll get {$discount}% off your first paid month.",
            'discount_percent' => $discount,
        ];
    }

    /**
     * @return array{success: bool, message: string, discount_percent: float}
     */
    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'discount_percent' => 0.0,
        ];
    }
}
