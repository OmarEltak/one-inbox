<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase H — Agent audit attribution.
 *
 * Adds `handled_by_user_id` as a dedicated foreign key to users so the agent-
 * audit sub-feature of Deep Analysis can group outbound messages by the human
 * who sent them. We do NOT reuse the existing `sender_id` column because that
 * column is polymorphic (holds contact.id for inbound rows and user.id for
 * outbound rows), so running a straight JOIN to users on it would be unsafe.
 *
 * `nullOnDelete()` ensures a deleted user does not destroy historical
 * attribution rows — the audit just reports "unattributed" for those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('handled_by_user_id')
                ->nullable()
                ->after('sender_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['handled_by_user_id', 'created_at'], 'messages_handled_by_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_handled_by_user_created_idx');
            $table->dropConstrainedForeignId('handled_by_user_id');
        });
    }
};
