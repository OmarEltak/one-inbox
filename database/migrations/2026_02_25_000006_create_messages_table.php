<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('platform_message_id')->nullable();
            $table->string('direction'); // inbound, outbound
            $table->string('sender_type'); // contact, user, ai
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('content_type')->default('text'); // text, image, video, audio, file, location, template, interactive
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->float('ai_confidence')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('platform_sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index('platform_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
