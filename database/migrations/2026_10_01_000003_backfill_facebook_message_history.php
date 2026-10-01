<?php

use App\Jobs\BackfillPageMessages;
use App\Jobs\SyncPageConversations;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/*
 * One-off: import recent message history for every chat on active
 * Messenger/Instagram pages, so the AI chat can read customers nobody has
 * opened yet. Delayed so the conversation re-sync (000002) finishes first.
 * Message rows only — no AI replies, nothing is sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Messenger: re-run the conversation import (it now records its
        // outcome in page metadata and queues the history backfill itself).
        Page::where('platform', 'facebook')->where('is_active', true)->pluck('id')
            ->each(fn ($id) => SyncPageConversations::dispatch(pageId: $id));

        Page::where('platform', 'instagram')->where('is_active', true)->pluck('id')
            ->each(fn ($id) => BackfillPageMessages::dispatch($id)->delay(now()->addMinutes(2)));
    }

    public function down(): void
    {
        //
    }
};
