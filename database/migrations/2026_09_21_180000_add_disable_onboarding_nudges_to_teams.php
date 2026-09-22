<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase C — never-let-go layer.
 *
 * Adds the kill switch for onboarding nudge emails. Respected by
 * SendOnboardingNudge before ANY email is queued. Also flipped to true
 * whenever a user hits the one-click unsubscribe link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->boolean('disable_onboarding_nudges')->default(false)->after('business_type');
            // Idempotency ledger — one row per (team, nudge) so the daily
            // scheduler never double-sends. Timestamps let us audit "was
            // nudge 3 actually sent to team X" without digging through mail
            // driver logs.
            $table->timestamp('nudge_1_sent_at')->nullable()->after('disable_onboarding_nudges');
            $table->timestamp('nudge_2_sent_at')->nullable()->after('nudge_1_sent_at');
            $table->timestamp('nudge_3_sent_at')->nullable()->after('nudge_2_sent_at');
            $table->timestamp('nudge_4_sent_at')->nullable()->after('nudge_3_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropColumn([
                'disable_onboarding_nudges',
                'nudge_1_sent_at',
                'nudge_2_sent_at',
                'nudge_3_sent_at',
                'nudge_4_sent_at',
            ]);
        });
    }
};
