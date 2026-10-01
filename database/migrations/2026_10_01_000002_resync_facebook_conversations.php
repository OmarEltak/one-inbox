<?php

use App\Jobs\SyncPageConversations;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/*
 * One-off: the connect-time Messenger import aborted on the first chat whose
 * preview was longer than 255 characters (Mishkah stopped at 28 of hundreds).
 * The import now skips bad rows and truncates previews, so run it once more
 * for every active Messenger page. Idempotent: existing conversations are
 * updated, never reopened; it creates conversations and contacts only — no
 * messages, no AI replies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Page::where('platform', 'facebook')->where('is_active', true)->pluck('id')
            ->each(fn ($id) => SyncPageConversations::dispatch(pageId: $id));
    }

    public function down(): void
    {
        //
    }
};
