<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('command');
            $table->text('response')->nullable();
            $table->json('action_taken')->nullable();
            $table->unsignedInteger('contacts_affected')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_commands');
    }
};
