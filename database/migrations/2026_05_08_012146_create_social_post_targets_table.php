<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (post, destination). A destination is usually a Page
     * (the connected FB page / IG account / Telegram bot / Slack workspace
     * / Discord application). For platforms that have sub-channels
     * (Slack channel, Discord channel, Telegram channel chat_id) we store
     * the channel identifier in `channel_id`.
     *
     * Status: pending -> publishing -> succeeded | failed
     */
    public function up(): void
    {
        Schema::create('social_post_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 32);
            $table->string('channel_id', 128)->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('platform_post_id', 191)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['social_post_id', 'status']);
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_targets');
    }
};
