<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->string('platform_conversation_id');
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('open'); // open, closed, snoozed, archived
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('labels')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'platform', 'platform_conversation_id']);
            $table->index(['team_id', 'status', 'last_message_at']);
            $table->index(['team_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
