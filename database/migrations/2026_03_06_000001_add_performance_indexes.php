<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'direction', 'sender_type'], 'messages_conv_dir_sender_idx');
            $table->index(['conversation_id', 'platform_sent_at'], 'messages_conv_platform_sent_idx');
            $table->index(['conversation_id', 'created_at'], 'messages_conv_created_idx');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['team_id', 'page_id', 'status'], 'conversations_team_page_status_idx');
            $table->index(['team_id', 'unread_count'], 'conversations_team_unread_idx');
            $table->index(['team_id', 'platform', 'created_at'], 'conversations_team_platform_created_idx');
            $table->index(['team_id', 'last_message_at'], 'conversations_team_last_msg_idx');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->index(['team_id', 'is_active'], 'pages_team_active_idx');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['team_id', 'created_at'], 'contacts_team_created_idx');
            $table->index(['team_id', 'lead_score'], 'contacts_team_score_idx');
            $table->index(['team_id', 'lead_status'], 'contacts_team_status_idx');
        });

        Schema::table('lead_score_events', function (Blueprint $table) {
            $table->index(['contact_id', 'created_at'], 'lead_events_contact_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conv_dir_sender_idx');
            $table->dropIndex('messages_conv_platform_sent_idx');
            $table->dropIndex('messages_conv_created_idx');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_team_page_status_idx');
            $table->dropIndex('conversations_team_unread_idx');
            $table->dropIndex('conversations_team_platform_created_idx');
            $table->dropIndex('conversations_team_last_msg_idx');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_team_active_idx');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_team_created_idx');
            $table->dropIndex('contacts_team_score_idx');
            $table->dropIndex('contacts_team_status_idx');
        });

        Schema::table('lead_score_events', function (Blueprint $table) {
            $table->dropIndex('lead_events_contact_created_idx');
        });
    }
};
