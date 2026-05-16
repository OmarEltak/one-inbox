<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('status')->default('pending'); // pending, sending, sent, failed, bounced, opened, unsubscribed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'email']);
            $table->index(['campaign_id', 'status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
