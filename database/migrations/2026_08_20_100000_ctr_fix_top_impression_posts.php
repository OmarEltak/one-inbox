<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('posts')
            ->where('slug', 'meta-business-suite-inbox-missing-messages-fix-2026')
            ->update([
                'meta_title'       => 'Meta Business Suite Inbox Missing Messages? 6 Fixes 2026',
                'meta_description' => 'Meta Business Suite drops Instagram DMs, Messenger chats, and WhatsApp messages other tools receive. 6 real fixes for the missing messages bug in 2026.',
            ]);

        DB::table('posts')
            ->where('slug', 'instagram-dms-not-showing-all-messages-fixes-2026')
            ->update([
                'meta_title'       => 'Instagram DMs Not Showing All Messages? 8 Fixes 2026',
                'meta_description' => 'Missing Instagram DMs kills ad ROI. 8 fixes that work in 2026 — the message-request folder trap, Meta Business Suite bug, and missing DMs on Instagram.',
            ]);
    }

    public function down(): void
    {
        DB::table('posts')
            ->whereIn('slug', [
                'meta-business-suite-inbox-missing-messages-fix-2026',
                'instagram-dms-not-showing-all-messages-fixes-2026',
            ])
            ->update([
                'meta_title'       => null,
                'meta_description' => null,
            ]);
    }
};
