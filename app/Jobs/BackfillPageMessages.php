<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Page;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Imports the recent message history (last 25 per chat) of every Messenger /
 * Instagram conversation on a page. The connect-time sync only stores one
 * preview line per chat and history was fetched lazily when a chat was
 * opened, so the AI chat could not read customers nobody had clicked on.
 *
 * Side-effect free: FacebookPlatform::fetchAndStoreMessages() writes Message
 * rows only — no AI reply, no broadcast, nothing sent to the customer.
 * Paced in batches; stops if Meta refuses the page token.
 */
class BackfillPageMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const BATCH = 40;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public int $pageId) {}

    public function handle(FacebookPlatform $platform): void
    {
        $page = Page::find($this->pageId);
        if (! $page || ! $page->is_active || ! in_array($page->platform, ['facebook', 'instagram'], true)) {
            return;
        }

        $pending = Conversation::where('page_id', $page->id)
            ->orderByDesc('last_message_at')
            ->get(['id', 'page_id', 'platform', 'platform_conversation_id', 'metadata'])
            ->reject(fn (Conversation $c) => data_get($c->metadata, 'messages_fetched'))
            ->values();

        $failures = 0;
        foreach ($pending->take(self::BATCH) as $conversation) {
            $conversation->setRelation('page', $page);
            try {
                $platform->fetchAndStoreMessages($conversation);
            } catch (\Throwable $e) {
                Log::warning('Message backfill failed for a conversation', ['conversation' => $conversation->id, 'error' => $e->getMessage()]);
            }

            // fetchAndStoreMessages only sets the flag on success.
            if (! data_get($conversation->fresh()?->metadata, 'messages_fetched') && ++$failures >= 3) {
                Log::error('Message backfill stopped: Meta keeps refusing this page', ['page' => $page->id, 'name' => $page->name]);

                return;
            }
            usleep(300_000); // stay well under Meta's per-page rate limit
        }

        if ($pending->count() > self::BATCH) {
            self::dispatch($page->id)->delay(now()->addSeconds(10));
        }
    }
}
