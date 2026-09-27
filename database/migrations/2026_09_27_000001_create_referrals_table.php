<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral coupon system.
 *
 * Each team gets one shareable code. When another team redeems it, both the
 * referrer and the redeemer receive a percent discount. The discount values
 * are SNAPSHOTTED into the row at redemption time so that changes to
 * config('referrals.*') later don't retroactively alter historical grants.
 *
 *  - code:                          human-shareable coupon (unique)
 *  - referrer_team_id:              team that owns / shares the code
 *  - referred_team_id:              team that redeemed it (null until then)
 *  - redeemed_at:                   set at redemption
 *  - discount_percent:              redeemer's discount snapshot
 *  - reciprocal_discount_percent:   referrer's discount snapshot
 *  - expires_at:                    future-proofing; leave null for now
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('referrer_team_id')
                ->constrained('teams')
                ->cascadeOnDelete();
            $table->foreignId('referred_team_id')
                ->nullable()
                ->constrained('teams')
                ->cascadeOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('reciprocal_discount_percent', 5, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('referrer_team_id');
            $table->index('referred_team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
