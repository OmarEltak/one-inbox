<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiCreditLedgerEntry;
use App\Models\Team;
use App\Services\Billing\AiCredits;
use Illuminate\Console\Command;

/**
 * Phase A — monthly credit allowance reset.
 *
 * Runs daily at 02:00 UTC (see routes/console.php). For every team whose
 * billing_cycle_anchor's day-of-month matches today's day-of-month, writes
 * two ledger rows in order:
 *
 *   1. monthly_reset_zeroing — negative delta that zeros any unused monthly
 *      remainder (spec §3.1 "use it or lose it").
 *   2. monthly_grant        — positive delta equal to the plan quota.
 *
 * Walle balance is untouched (packs don't expire). Enterprise/unlimited
 * plans still get the stub grant for audit continuity, with the actual
 * -1 bypass handled upstream in EnforcePlanLimits::hasAiCredits.
 *
 * Idempotency: a team that already received a monthly_grant row with
 * today's calendar date in metadata is skipped — safe to run twice if the
 * schedule double-fires.
 */
class ResetMonthlyAiCredits extends Command
{
    protected $signature = 'credits:reset-monthly {--team=* : Only process these team IDs (debug)}';

    protected $description = 'Reset monthly AI credit allowance for teams whose billing anchor matches today.';

    private const UNLIMITED_STUB_AMOUNT = 10_000;

    public function handle(AiCredits $credits): int
    {
        $today = now();
        $dayOfMonth = (int) $today->format('d');

        $query = Team::query();
        if ($teamIds = $this->option('team')) {
            $query->whereIn('id', $teamIds);
        }

        $processed = 0;
        $skipped = 0;

        $query->chunkById(100, function ($teams) use ($credits, $today, $dayOfMonth, &$processed, &$skipped): void {
            foreach ($teams as $team) {
                if (! $this->shouldResetToday($team, $dayOfMonth)) {
                    continue;
                }

                if ($this->alreadyGrantedToday($team, $today)) {
                    $skipped++;
                    continue;
                }

                $this->resetTeam($team, $credits, $today);
                $processed++;
            }
        });

        $this->info("Processed {$processed} team(s); skipped {$skipped} already granted today.");

        return self::SUCCESS;
    }

    private function shouldResetToday(Team $team, int $dayOfMonth): bool
    {
        $anchor = $team->billing_cycle_anchor;
        if ($anchor === null) {
            // Teams created before Phase A migration backfill — the migration
            // sets this to created_at. Fall back to created_at defensively.
            $anchor = $team->created_at;
        }
        if ($anchor === null) {
            return false;
        }

        return (int) $anchor->format('d') === $dayOfMonth;
    }

    private function alreadyGrantedToday(Team $team, \Carbon\CarbonInterface $today): bool
    {
        return AiCreditLedgerEntry::query()
            ->where('team_id', $team->id)
            ->where('reason', AiCreditLedgerEntry::REASON_MONTHLY_GRANT)
            ->whereDate('created_at', $today->toDateString())
            ->exists();
    }

    private function resetTeam(Team $team, AiCredits $credits, \Carbon\CarbonInterface $today): void
    {
        // 1. Zero any unused monthly remainder (use-it-or-lose-it).
        $credits->zeroMonthly($team, AiCreditLedgerEntry::REASON_MONTHLY_ZEROING);

        // 2. Grant the new monthly allowance.
        $amount = $this->planQuotaFor($team);
        if ($amount <= 0) {
            return;
        }

        $credits->grant(
            team: $team,
            amount: $amount,
            balanceType: AiCreditLedgerEntry::BALANCE_MONTHLY,
            reason: AiCreditLedgerEntry::REASON_MONTHLY_GRANT,
            actorUserId: null,
            meta: [
                'plan' => $team->subscription_plan ?? 'free',
                'reset_date' => $today->toDateString(),
            ],
        );
    }

    private function planQuotaFor(Team $team): int
    {
        $planKey = $team->subscription_plan ?? 'free';
        $plan = config("plans.plans.{$planKey}", config('plans.plans.free'));
        $amount = (int) ($plan['ai_credits'] ?? 0);

        return $amount === -1 ? self::UNLIMITED_STUB_AMOUNT : max(0, $amount);
    }
}
