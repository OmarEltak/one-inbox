<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_score_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type'); // pricing_asked, contact_shared, went_silent, etc.
            $table->smallInteger('score_change'); // +/- value
            $table->string('reason');
            $table->text('ai_analysis')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_score_events');
    }
};
