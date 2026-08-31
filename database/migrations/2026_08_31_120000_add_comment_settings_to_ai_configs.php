<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table): void {
            $table->json('comment_settings')->nullable()->after('escalation_topics');
        });
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table): void {
            $table->dropColumn('comment_settings');
        });
    }
};
