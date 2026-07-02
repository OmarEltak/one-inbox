<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes hit by the /analytics page — specifically the Reach Across Platforms
 * daily-messages chart and the response-time correlated subquery — which were
 * making 7d/14d/30d/90d period switches hang.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Speeds up DATE(created_at) GROUP BY sender_type in getDailyMessages
            // and the correlated MAX(created_at) subquery in getResponseTimes.
            $table->index(['created_at', 'sender_type'], 'messages_created_sender_idx');
        });

        Schema::table('lead_score_events', function (Blueprint $table) {
            // Speeds up getTopObjections join → contacts filter.
            $table->index(['contact_id', 'created_at'], 'lead_score_events_contact_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_created_sender_idx');
        });
        Schema::table('lead_score_events', function (Blueprint $table) {
            $table->dropIndex('lead_score_events_contact_created_idx');
        });
    }
};
