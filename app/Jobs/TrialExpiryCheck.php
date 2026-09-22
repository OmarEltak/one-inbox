<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PaymentOverdue;
use App\Mail\TrialEndingSoon;
use App\Mail\TrialExpiredPaymentDue;
use App\Models\Team;
use App\Services\Billing\PlanLifecycle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Phase D — daily lifecycle cron for trials.
 *
 * Scheduled at 09:00 UTC daily (routes/console.php). Walks all teams whose
 * plan_status is on the auto-transition machine and:
 *
 *   - day 12/13 of trial   → email "Trial ending soon" (fire once per team)
 *   - day 14+ of trial     → flip to pending_payment + email invoice
 *   - day 7+ past due      → flip to overdue (soft-throttle in canDispatchAi)
 *   - day 16+ overdue      → email founder for MANUAL review (no auto-cancel)
 *
 * Idempotent by design: cache-flag per team+event so the same email never
 * sends twice, even if the job runs twice on the same day. Never auto-cancels
 * an account — a human decides.
 */
class TrialExpiryCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * How many days after entering `overdue` do we surface the account to Omar
     * for a manual review? Combined with TRIAL_DAYS + PAYMENT_GRACE_DAYS this
     * lands on day 30 of the trial clock.
     */
    public const FOUNDER_REVIEW_DAYS_AFTER_OVERDUE = 9;

    public function handle(PlanLifecycle $lifecycle): void
    {
        Team::query()
            ->whereIn('plan_status', [
                PlanLifecycle::STATUS_TRIAL,
                PlanLifecycle::STATUS_PENDING_PAYMENT,
                PlanLifecycle::STATUS_OVERDUE,
            ])
            ->whereNotNull('plan_trial_started_at')
            ->orderBy('id')
            ->chunkById(200, function ($teams) use ($lifecycle) {
                foreach ($teams as $team) {
                    try {
                        $this->processTeam($team, $lifecycle);
                    } catch (Throwable $e) {
                        Log::warning('TrialExpiryCheck: team failed', [
                            'team_id' => $team->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    protected function processTeam(Team $team, PlanLifecycle $lifecycle): void
    {
        switch ($team->plan_status) {
            case PlanLifecycle::STATUS_TRIAL:
                $this->processTrial($team, $lifecycle);
                break;

            case PlanLifecycle::STATUS_PENDING_PAYMENT:
                $this->processPendingPayment($team, $lifecycle);
                break;

            case PlanLifecycle::STATUS_OVERDUE:
                $this->processOverdue($team);
                break;
        }
    }

    protected function processTrial(Team $team, PlanLifecycle $lifecycle): void
    {
        if ($lifecycle->trialHasElapsed($team)) {
            $lifecycle->markPendingPayment($team);
            $this->sendOnce($team, 'trial_expired', function () use ($team) {
                if (! $team->owner?->email) {
                    return;
                }
                Mail::to($team->owner->email)->send(new TrialExpiredPaymentDue($team));
            });
            return;
        }

        // Warning email 2 days before trial ends.
        $elapsed = $lifecycle->trialDaysElapsed($team) ?? 0;
        if ($elapsed >= PlanLifecycle::TRIAL_DAYS - 2 && $elapsed < PlanLifecycle::TRIAL_DAYS) {
            $this->sendOnce($team, 'trial_ending_soon', function () use ($team, $elapsed) {
                if (! $team->owner?->email) {
                    return;
                }
                $daysLeft = max(1, PlanLifecycle::TRIAL_DAYS - $elapsed);
                Mail::to($team->owner->email)->send(new TrialEndingSoon($team, $daysLeft));
            });
        }
    }

    protected function processPendingPayment(Team $team, PlanLifecycle $lifecycle): void
    {
        if ($lifecycle->paymentGraceHasElapsed($team)) {
            $lifecycle->markOverdue($team);
            $this->sendOnce($team, 'payment_overdue', function () use ($team) {
                if (! $team->owner?->email) {
                    return;
                }
                Mail::to($team->owner->email)->send(new PaymentOverdue($team));
            });
        }
    }

    protected function processOverdue(Team $team): void
    {
        // Founder review — no auto-cancellation per spec.
        $daysDue = app(PlanLifecycle::class)->daysSincePaymentDue($team) ?? 0;
        $daysOverdue = max(0, $daysDue - PlanLifecycle::PAYMENT_GRACE_DAYS);
        if ($daysOverdue < self::FOUNDER_REVIEW_DAYS_AFTER_OVERDUE) {
            return;
        }

        $this->sendOnce($team, 'founder_manual_review', function () use ($team) {
            try {
                Mail::raw(
                    "Team {$team->name} (#{$team->id}) has been overdue for {$this->daysOverdueLine($team)} days.\n\n" .
                    "Owner: " . ($team->owner?->email ?? 'unknown') . "\n" .
                    "Plan: " . ($team->subscription_plan ?? 'unknown') . "\n" .
                    "Trial started: " . optional($team->plan_trial_started_at)->toDateString() . "\n\n" .
                    "Review: " . route('super-admin.billing'),
                    fn ($m) => $m
                        ->to(config('mail.admin_address', 'omareltak7@gmail.com'))
                        ->subject("[OT1-Pro] Manual review needed — {$team->name}")
                );
            } catch (Throwable $e) {
                Log::warning('TrialExpiryCheck: founder email failed', [
                    'team_id' => $team->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        });
    }

    protected function daysOverdueLine(Team $team): int
    {
        return max(0, (app(PlanLifecycle::class)->daysSincePaymentDue($team) ?? 0) - PlanLifecycle::PAYMENT_GRACE_DAYS);
    }

    /**
     * Cache-guarded send. Second call in the same UTC day for the same team+event
     * is a silent no-op. Keeps the job re-runnable without risking spam.
     */
    protected function sendOnce(Team $team, string $event, \Closure $sender): void
    {
        $key = "trial_check.{$team->id}.{$event}." . now()->utc()->toDateString();
        if (\Illuminate\Support\Facades\Cache::has($key)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($key, 1, new \DateInterval('P2D'));
        $sender();
    }
}
