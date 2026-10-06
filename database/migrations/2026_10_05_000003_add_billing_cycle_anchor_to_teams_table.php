<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A — monthly billing-cycle anchor.
 *
 * The day-of-month on which a team's monthly credit allowance resets.
 * Defaults to the team's created_at date (so a team that signed up on
 * the 7th resets on the 7th every month). See AiCredits::resetMonthly()
 * and app/Console/Commands/ResetMonthlyAiCredits.php.
 *
 * See docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md §3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Date (not timestamp) — we only care about the day-of-month.
            $table->date('billing_cycle_anchor')->nullable()->after('plan_payment_due_at');
        });

        // Backfill existing teams to their created_at date so the first
        // monthly-reset cycle is anchored sensibly.
        DB::statement('UPDATE teams SET billing_cycle_anchor = DATE(created_at) WHERE billing_cycle_anchor IS NULL');
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('billing_cycle_anchor');
        });
    }
};
