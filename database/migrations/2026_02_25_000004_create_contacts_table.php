<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedTinyInteger('lead_score')->default(0); // 0-100
            $table->string('lead_status')->default('new'); // new, warm, hot, cold, converted, lost
            $table->json('score_history')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'lead_score']);
            $table->index(['team_id', 'lead_status']);
        });

        Schema::create('contact_platform', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_contact_id');
            $table->string('platform_name')->nullable();
            $table->string('platform_avatar')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'platform', 'platform_contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_platform');
        Schema::dropIfExists('contacts');
    }
};
