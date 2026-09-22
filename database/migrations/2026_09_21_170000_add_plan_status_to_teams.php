<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase D — Trust-based plan trial + billing state machine.
 *
 * Adds three columns to `teams`:
 *   - plan_status:            trial | pending_payment | paid | overdue | cancelled
 *   - plan_trial_started_at:  set when a paid tier is picked
 *   - plan_payment_due_at:    set when trial→pending_payment (now()+7d)
 *
 * We intentionally KEEP the existing `subscription_plan` / `subscription_status`
 * / `subscription_ends_at` columns — they're used by SuperAdmin\Subscriptions,
 * EnforcePlanLimits, and the existing PayWire grant flow. This migration adds a
 * parallel *lifecycle* state machine on top; the two are consumed together by
 * Team::canDispatchAi() (see CLAUDE.md pin #4).
 *
 * Existing paying customers are backfilled to plan_status = 'paid' if they
 * either (a) hold a non-free subscription_plan with subscription_ends_at in
 * the future, or (b) have any PaymentRequest row with status = 'approved'.
 * Everyone else defaults to 'trial'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // MySQL ENUM would be strictly better, but SQLite (local dev) does
            // not support altering ENUM types. Use a short varchar with an
            // application-level whitelist (see App\Services\Billing\PlanLifecycle).
            $table->string('plan_status', 32)->default('trial')->after('billing_cycle');
            $table->timestamp('plan_trial_started_at')->nullable()->after('plan_status');
            $table->timestamp('plan_payment_due_at')->nullable()->after('plan_trial_started_at');

            $table->index('plan_status');
        });

        // Backfill: any existing team that looks like a real paying customer is
        // seeded as 'paid' so tomorrow's TrialExpiryCheck doesn't accidentally
        // start throttling them. The two signals we check are (a) an active,
        // non-free subscription grant from the super-admin panel, and (b) any
        // approved PaymentRequest row (some early manual grants may have
        // reset subscription_ends_at back to null).
        $paidTeamIds = DB::table('teams')
            ->where('subscription_plan', '!=', 'free')
            ->whereNotNull('subscription_plan')
            ->where(function ($q) {
                $q->whereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '>', now());
            })
            ->pluck('id')
            ->all();

        // PaymentRequest table exists (see 2026_07_05_095418_create_payment_requests_table).
        // Guard the query just in case a future teardown drops it.
        if (Schema::hasTable('payment_requests')) {
            $approvedTeamIds = DB::table('payment_requests')
                ->where('status', 'approved')
                ->whereNotNull('team_id')
                ->pluck('team_id')
                ->all();
            $paidTeamIds = array_values(array_unique(array_merge($paidTeamIds, $approvedTeamIds)));
        }

        if (! empty($paidTeamIds)) {
            DB::table('teams')
                ->whereIn('id', $paidTeamIds)
                ->update(['plan_status' => 'paid']);
        }
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['plan_status']);
            $table->dropColumn(['plan_status', 'plan_trial_started_at', 'plan_payment_due_at']);
        });
    }
};
