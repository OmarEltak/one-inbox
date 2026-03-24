<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Page;
use App\Services\Platforms\SnapchatPlatform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchSnapchatMessages extends Command
{
    protected $signature   = 'snapchat:fetch-messages';
    protected $description = 'Poll Snapchat Business API for new creator messages (runs every 2 minutes via scheduler)';

    public function handle(SnapchatPlatform $snapchat): void
    {
        $pages = Page::where('platform', 'snapchat')
            ->where('is_active', true)
            ->get();

        if ($pages->isEmpty()) {
            return;
        }

        foreach ($pages as $page) {
            $profileId = $page->metadata['profile_id'] ?? null;

            if (! $profileId) {
                Log::debug("snapchat:fetch-messages — page {$page->id} has no profile_id, skipping");
                continue;
            }

            $conversations = Conversation::where('page_id', $page->id)
                ->whereNotNull('metadata->conversation_id')
                ->get();

            foreach ($conversations as $conversation) {
                try {
                    $snapchat->pollMessages($page, $conversation);
                } catch (\Throwable $e) {
                    Log::error("snapchat:fetch-messages — failed for conversation {$conversation->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
