<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('subscription_plan')->default('free');
            $table->string('subscription_status')->default('active');
            $table->boolean('ai_enabled')->default(true);
            $table->timestamp('ai_disabled_at')->nullable();
            $table->unsignedInteger('ai_credits_used')->default(0);
            $table->unsignedInteger('ai_credits_limit')->default(1000);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('agent'); // admin, agent, viewer
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // Add current_team_id to users for quick team context
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_team_id')->nullable()->after('remember_token')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_team_id');
        });
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
    }
};
