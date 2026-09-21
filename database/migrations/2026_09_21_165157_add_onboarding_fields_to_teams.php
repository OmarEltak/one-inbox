<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A — "Meet Your AI" playground onboarding.
 *
 * Adds:
 *   - onboarding_completed_at: null while user hasn't finished (or skipped) the
 *     4-step playground; set to now() on completion. Used by the redirect after
 *     signup and by the ProgressService in Phase C.
 *   - business_type: one of the 6 category cards from step 1 (e-commerce,
 *     services, restaurant, clinic, real_estate, other). Kept nullable because
 *     users who signed up before this migration have no value and skipping the
 *     onboarding also leaves it null. VARCHAR(32) is more than enough.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('features');
            $table->string('business_type', 32)->nullable()->after('onboarding_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['onboarding_completed_at', 'business_type']);
        });
    }
};
