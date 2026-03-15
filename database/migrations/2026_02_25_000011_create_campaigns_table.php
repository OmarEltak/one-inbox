<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // re_engagement, follow_up, promotion
            $table->json('target_criteria')->nullable(); // filters: date range, lead score, status
            $table->text('message_template')->nullable();
            $table->boolean('ai_personalize')->default(true);
            $table->unsignedInteger('total_contacts')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->string('status')->default('draft'); // draft, active, paused, completed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
