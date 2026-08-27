<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // WhatsApp recipients have phone, not email. Make email nullable and
        // replace the (campaign_id, email) unique constraint with two channel-
        // specific ones that both permit nulls (MySQL and SQLite both treat
        // NULLs as distinct in unique indexes, so this works correctly).
        Schema::table('campaign_recipients', function (Blueprint $table) {
            // Drop the existing email-only unique; we replace with channel-aware ones.
            try {
                $table->dropUnique(['campaign_id', 'email']);
            } catch (\Throwable) {
                // Index name may differ across DB engines; ignore if already gone.
            }
        });

        // Change email to nullable. Requires doctrine/dbal on Laravel < 10;
        // Laravel 10+ supports native `change()`.
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->unique(['campaign_id', 'email'], 'campaign_recipients_campaign_email_unique');
            $table->unique(['campaign_id', 'phone'], 'campaign_recipients_campaign_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table) {
            try {
                $table->dropUnique('campaign_recipients_campaign_email_unique');
                $table->dropUnique('campaign_recipients_campaign_phone_unique');
            } catch (\Throwable) {
            }
            $table->string('email')->nullable(false)->change();
            $table->unique(['campaign_id', 'email']);
        });
    }
};
