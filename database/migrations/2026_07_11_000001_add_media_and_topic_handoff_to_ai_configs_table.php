<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->boolean('escalate_on_media')->default(false)->after('escalation_keywords');
            $table->json('escalation_topics')->nullable()->after('escalate_on_media');
        });
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn(['escalate_on_media', 'escalation_topics']);
        });
    }
};
