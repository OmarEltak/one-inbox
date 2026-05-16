<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('message_template');
            $table->foreignId('sender_page_id')->nullable()->after('subject')->constrained('pages')->nullOnDelete();
            $table->unsignedInteger('daily_cap')->nullable()->default(200)->after('sender_page_id');
            $table->unsignedInteger('jitter_min_seconds')->default(30)->after('daily_cap');
            $table->unsignedInteger('jitter_max_seconds')->default(60)->after('jitter_min_seconds');
            $table->unsignedInteger('failed_count')->default(0)->after('reply_count');
            $table->unsignedInteger('opened_count')->default(0)->after('failed_count');
            $table->unsignedInteger('unsubscribed_count')->default(0)->after('opened_count');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['sender_page_id']);
            $table->dropColumn([
                'subject',
                'sender_page_id',
                'daily_cap',
                'jitter_min_seconds',
                'jitter_max_seconds',
                'failed_count',
                'opened_count',
                'unsubscribed_count',
            ]);
        });
    }
};
