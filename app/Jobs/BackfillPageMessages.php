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

        // 2026-10-13: mark the page as 'backfill in progress' so the Connections
        // page can show a progress bar + a dismissable completion toast. Only
        // set once per backfill cycle — the Connections view clears
        // backfill_notification_acked_at on dispatch, and we set the started_at
        // flag here. The toast surfaces when fetched == total AND not yet acked.
        if (empty(data_get($page->metadata, 'backfill_in_progress_started_at'))) {
            $page->forceFill([
                'metadata' => array_merge($page->metadata ?? [], [
                    'backfill_in_progress_started_at' => now()->toIso8601String(),
                    'backfill_notification_acked_at'  => null,
                ]),
            ])->saveQuietly();
        }

        $pending = Conversation::where('page_id', $page->id)
            ->orderByDesc('last_message_at')
            ->get(['id', 'page_id', 'platform', 'platform_conversation_id', 'metadata'])
            ->reject(fn (Conversation $c) => data_get($c->metadata, 'messages_fetched'))
            ->values();

        // 2026-10-13: previously stopped the whole backfill at 3 total failures.
        // On a 3000-conv page that was catastrophic — Mishkah got 24/3086 before
        // giving up. Now we only treat auth-type errors (page token revoked /
        // insufficient scopes) as fatal. Everything else (404, 5xx, rate limit)
        // just skips the conversation and keeps chaining — those will be retried
        // on the next scheduled sync. Also chain the next batch BEFORE early
        // return on fatal, so bail doesn't drop queue identity.
        $authErrors = 0;
        foreach ($pending->take(self::BATCH) as $conversation) {
            $conversation->setRelation('page', $page);
            try {
                $platform->fetchAndStoreMessages($conversation);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $isAuth = str_contains($msg, 'OAuthException')
                    || str_contains($msg, '401')
                    || str_contains($msg, '403')
                    || str_contains($msg, 'access token');
                if ($isAuth && ++$authErrors >= 3) {
                    Log::error('Message backfill stopped: page token is refusing auth', [
                        'page' => $page->id, 'name' => $page->name, 'error' => $msg,
                    ]);

                    return;
                }
                Log::warning('Message backfill failed for a conversation (skipping)', [
                    'conversation' => $conversation->id, 'error' => $msg,
                ]);
            }
            usleep(300_000); // stay well under Meta's per-page rate limit
        }

        if ($pending->count() > self::BATCH) {
            self::dispatch($page->id)->delay(now()->addSeconds(10));
        }
    }
}
