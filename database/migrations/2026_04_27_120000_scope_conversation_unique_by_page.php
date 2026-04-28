<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'platform', 'platform_conversation_id']);
            $table->unique(['team_id', 'page_id', 'platform', 'platform_conversation_id'], 'conversations_team_page_platform_pcid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_team_page_platform_pcid_unique');
            $table->unique(['team_id', 'platform', 'platform_conversation_id']);
        });
    }
};
