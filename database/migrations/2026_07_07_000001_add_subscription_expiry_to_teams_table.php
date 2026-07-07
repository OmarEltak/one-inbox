<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            // Null = "no expiry" (free tier or manually granted forever).
            // Set = subscription lapses at this timestamp; canDispatchAi() blocks after.
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
            $table->string('billing_cycle')->nullable()->after('subscription_ends_at');
            $table->index('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->dropIndex(['subscription_ends_at']);
            $table->dropColumn(['subscription_ends_at', 'billing_cycle']);
        });
    }
};
