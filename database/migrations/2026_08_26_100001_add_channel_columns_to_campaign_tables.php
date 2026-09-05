<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('warmup_bypass')->default(false)->after('jitter_max_seconds');
            $table->unsignedTinyInteger('quiet_hours_start')->default(9)->after('warmup_bypass');
            $table->unsignedTinyInteger('quiet_hours_end')->default(21)->after('quiet_hours_start');
            $table->boolean('respect_recipient_tz')->default(true)->after('quiet_hours_end');
            $table->string('paused_reason')->nullable()->after('status');
            $table->boolean('use_spintax')->default(true)->after('respect_recipient_tz');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->char('phone_country', 2)->nullable()->after('phone');
            $table->string('channel', 20)->default('email')->after('campaign_id');

            $table->index(['status', 'scheduled_at'], 'campaign_recipients_status_scheduled_idx');
        });

        if (Schema::hasTable('contact_imports')) {
            Schema::table('contact_imports', function (Blueprint $table) {
                $table->string('channel', 20)->default('email')->after('team_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contact_imports')) {
            Schema::table('contact_imports', function (Blueprint $table) {
                $table->dropColumn('channel');
            });
        }

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropIndex('campaign_recipients_status_scheduled_idx');
            $table->dropColumn(['phone', 'phone_country', 'channel']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'warmup_bypass', 'quiet_hours_start', 'quiet_hours_end',
                'respect_recipient_tz', 'paused_reason', 'use_spintax',
            ]);
        });
    }
};
