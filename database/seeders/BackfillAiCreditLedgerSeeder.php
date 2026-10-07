<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Services\Billing\AiCredits;
use Illuminate\Database\Seeder;

/**
 * Phase A go-live backfill.
 *
 * Writes one +plan_quota row (balance_type=monthly, reason=backfill_go_live)
 * per team so existing customers transition to the ledger with a full
 * month's allowance intact — zero retroactive charging (spec §9, confirmed).
 *
 * Idempotent: skips teams that already have a backfill_go_live entry, so
 * re-running the seeder on prod (e.g. after a cache flush) does not grant
 * duplicate credits.
 *
 * Enterprise / unlimited plans still get a row for audit visibility — the
 * value is a conservative stand-in (10,000) that will never be hit because
 * EnforcePlanLimits::hasAiCredits short-circuits -1 plans before touching
 * the balance.
 */
class BackfillAiCreditLedgerSeeder extends Seeder
{
    private const UNLIMITED_STUB_AMOUNT = 10_000;

    public function run(): void
    {
        $credits = app(AiCredits::class);

        Team::query()->chunkById(100, function ($teams) use ($credits): void {
            foreach ($teams as $team) {
                $this->backfillTeam($team, $credits);
            }
        });
    }

    private function backfillTeam(Team $team, AiCredits $credits): void
    {
        $alreadyBackfilled = AiCreditLedgerEntry::query()
            ->where('team_id', $team->id)
            ->where('reason', AiCreditLedgerEntry::REASON_BACKFILL)
            ->exists();

        if ($alreadyBackfilled) {
            return;
        }

        $amount = $this->planQuotaFor($team);
        if ($amount <= 0) {
            return;
        }

        $credits->grant(
            team: $team,
            amount: $amount,
            balanceType: AiCreditLedgerEntry::BALANCE_MONTHLY,
            reason: AiCreditLedgerEntry::REASON_BACKFILL,
            actorUserId: null,
            meta: [
                'plan' => Team::resolvePlanSlug($team->subscription_plan ?? null),
                'raw_plan' => $team->subscription_plan ?? null,
                'note' => 'Phase A go-live backfill',
            ],
        );
    }

    private function planQuotaFor(Team $team): int
    {
        // Resolve legacy aliases ('enterprise' → 'business' etc) before lookup;
        // otherwise a backfill run grants the Free amount to teams on legacy tiers.
        $planKey = Team::resolvePlanSlug($team->subscription_plan ?? null);
        $plan = config("plans.plans.{$planKey}", config('plans.plans.free'));
        $credits = (int) ($plan['ai_credits'] ?? 0);

        return $credits === -1 ? self::UNLIMITED_STUB_AMOUNT : max(0, $credits);
    }
}
