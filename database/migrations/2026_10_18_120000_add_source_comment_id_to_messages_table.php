<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 (comment-to-DM link) — add `source_comment_id` on `messages`.
 *
 * When the AI public-comment reply flow sends a one-shot DM to a commenter
 * (SendAiCommentReplyJob) and that commenter later replies to the DM, the
 * resulting inbound Message row has no connection back to the Comment row
 * that triggered the outreach. This column records that link so Phase 2
 * (inbox banner "Started from comment on [post]") and Phase 3 (Comments
 * sidebar section) can traverse Comment → inbound Message cheaply.
 *
 * `nullOnDelete()` keeps the Message history intact if the originating
 * Comment is ever deleted — the message simply loses its provenance tag.
 * No data backfill: this links NEW messages only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->foreignId('source_comment_id')
                ->nullable()
                ->after('reply_to_message_id')
                ->constrained('comments')
                ->nullOnDelete();

            $table->index('source_comment_id', 'messages_source_comment_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_source_comment_id_idx');
            $table->dropConstrainedForeignId('source_comment_id');
        });
    }
};
