<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Team;
use Carbon\Carbon;

/**
 * Phase D — Trust-based plan trial state machine.
 *
 * Central place to transition a Team's `plan_status`. All state changes go
 * through here so we get one audit trail, one place to send emails, and one
 * place to reason about the invariants:
 *
 *   trial (14 days)
 *     └── pending_payment (7 more days, invoice sent)
 *           ├── paid           (super-admin marks receipt verified)
 *           └── overdue        (day 21 unpaid; soft-throttle 10 msg/day)
 *                 └── (day 30) → email founder for manual review; no auto-cancel
 *
 *   paid → paid (renewals just re-stamp payment)
 *   Any status → cancelled (super-admin action only)
 *
 * canDispatchAi() reads plan_status directly — see Team::canDispatchAi() for
 * how these states compose into the single AI dispatch gate (CLAUDE.md pin #4).
 */
class PlanLifecycle
{
    public const STATUS_TRIAL           = 'trial';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID            = 'paid';
    public const STATUS_OVERDUE         = 'overdue';
    public const STATUS_CANCELLED       = 'cancelled';

    public const ALL_STATUSES = [
        self::STATUS_TRIAL,
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    /**
     * Default trial window before we ask for payment.
     */
    public const TRIAL_DAYS = 14;

    /**
     * How long after invoicing before we throttle the account. Bank transfers
     * take time; be generous here — overdue is soft, not a block.
     */
    public const PAYMENT_GRACE_DAYS = 7;

    /**
     * Days after which we surface the account to Omar for a manual look.
     * NEVER auto-cancels — a human decides.
     */
    public const MANUAL_REVIEW_DAYS = 30;

    /**
     * Overdue accounts still get AI, but capped. Cost of blocking a real
     * customer over a billing lag is much higher than the cost of a few
     * extra AI responses to a deadbeat.
     */
    public const OVERDUE_DAILY_MESSAGE_CAP = 10;

    /**
     * Start a trial for a team that just picked a plan on the onboarding
     * plan-picker. Idempotent: calling twice does not reset the clock.
     */
    public function startTrial(Team $team, string $plan): void
    {
        // Never overwrite an already-paid team by mistake — pick-your-plan
        // renders once but a race in Livewire could double-fire.
        if ($team->plan_status === self::STATUS_PAID) {
            return;
        }

        $team->plan_status = self::STATUS_TRIAL;
        $team->plan_trial_started_at ??= now();
        $team->plan_payment_due_at = null;

        // Also mirror the picked plan onto subscription_plan so the rest of
        // the app (EnforcePlanLimits, sidebar badge, etc.) sees the intent.
        // We do NOT set subscription_ends_at here — that stays a super-admin
        // grant concept. plan_trial_started_at is the trial clock.
        if (in_array($plan, ['free', 'basic', 'starter', 'pro', 'enterprise'], true)) {
            $team->subscription_plan = $plan;
        }

        $team->save();
    }

    /**
     * Trial elapsed — flip to pending_payment and stamp the payment due date.
     * The daily TrialExpiryCheck job calls this once per team per day.
     */
    public function markPendingPayment(Team $team): void
    {
        if ($team->plan_status !== self::STATUS_TRIAL) {
            return;
        }

        $team->plan_status = self::STATUS_PENDING_PAYMENT;
        $team->plan_payment_due_at = now()->addDays(self::PAYMENT_GRACE_DAYS);
        $team->save();
    }

    /**
     * Grace period elapsed with no receipt — flip to overdue. AI dispatch
     * gets soft-throttled by Team::canDispatchAi() from this point on.
     */
    public function markOverdue(Team $team): void
    {
        if ($team->plan_status !== self::STATUS_PENDING_PAYMENT) {
            return;
        }

        $team->plan_status = self::STATUS_OVERDUE;
        $team->save();
    }

    /**
     * Super-admin verified receipt — team is fully paid up.
     */
    public function markPaid(Team $team): void
    {
        $team->plan_status = self::STATUS_PAID;
        // Clear the trial + due timestamps so the daily job stops considering
        // this team for further transitions until (if ever) they re-enter trial.
        $team->plan_payment_due_at = null;
        $team->save();
    }

    /**
     * Super-admin cancelled the account (rare — usually a chargeback or fraud).
     */
    public function markCancelled(Team $team): void
    {
        $team->plan_status = self::STATUS_CANCELLED;
        $team->save();
    }

    /**
     * Reset back to trial (e.g. super-admin wants to give someone a fresh
     * 14 days after resolving an issue). Not a state on the auto-machine.
     */
    public function resetToTrial(Team $team): void
    {
        $team->plan_status = self::STATUS_TRIAL;
        $team->plan_trial_started_at = now();
        $team->plan_payment_due_at = null;
        $team->save();
    }

    /**
     * How many days into the trial is this team? Null if never started.
     * Used by the daily job and by the super-admin dashboard.
     */
    public function trialDaysElapsed(Team $team): ?int
    {
        if (! $team->plan_trial_started_at) {
            return null;
        }
        return (int) Carbon::parse($team->plan_trial_started_at)->diffInDays(now());
    }

    /**
     * Is this team on a trial that has now elapsed? Pure predicate; no side effects.
     */
    public function trialHasElapsed(Team $team): bool
    {
        if ($team->plan_status !== self::STATUS_TRIAL) {
            return false;
        }
        $elapsed = $this->trialDaysElapsed($team);
        return $elapsed !== null && $elapsed >= self::TRIAL_DAYS;
    }

    /**
     * Is the pending_payment grace window over?
     */
    public function paymentGraceHasElapsed(Team $team): bool
    {
        if ($team->plan_status !== self::STATUS_PENDING_PAYMENT) {
            return false;
        }
        return $team->plan_payment_due_at !== null
            && Carbon::parse($team->plan_payment_due_at)->isPast();
    }

    /**
     * Days since the team was first invoiced (pending_payment). Used to decide
     * when to flag for manual founder review.
     */
    public function daysSincePaymentDue(Team $team): ?int
    {
        // day 21 → pending_payment started at day 14 → +7d grace elapsed → overdue.
        // "days since payment due" = days since we asked for payment = days
        // since trial ended = elapsed - TRIAL_DAYS.
        if (! $team->plan_trial_started_at) {
            return null;
        }
        $trialElapsed = (int) Carbon::parse($team->plan_trial_started_at)->diffInDays(now());
        return max(0, $trialElapsed - self::TRIAL_DAYS);
    }
}
