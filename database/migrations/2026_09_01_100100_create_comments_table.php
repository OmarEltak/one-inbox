<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pages_post_id')->constrained('pages_posts')->cascadeOnDelete();
            $table->string('platform_comment_id')->unique();
            $table->string('parent_comment_id')->nullable();
            $table->string('commenter_platform_id');
            $table->string('commenter_name');
            $table->text('text');
            $table->timestamp('received_at');
            $table->string('decision', 40)->nullable();
            $table->string('decision_reason')->nullable();
            $table->text('reply_text')->nullable();
            $table->string('graph_reply_id')->nullable();
            $table->timestamp('dm_sent_at')->nullable();
            $table->string('dm_graph_message_id')->nullable();
            $table->json('graph_error')->nullable();
            $table->timestamps();
            $table->index(['page_id', 'created_at']);
            $table->index(['page_id', 'decision', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
