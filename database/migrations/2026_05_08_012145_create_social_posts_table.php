<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks "compose once, publish to many" social media posts.
     *
     * Status flow:
     *   draft -> queued -> publishing -> completed | failed | partial
     *
     * Per-target outcomes live in social_post_targets so a single post can
     * have FB succeed, IG fail, Telegram succeed - and we can show that.
     */
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content')->nullable();
            $table->string('media_path', 500)->nullable();
            $table->string('media_type', 32)->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
