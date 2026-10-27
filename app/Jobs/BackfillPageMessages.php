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

    /**
     * Default cap for connect-time auto-backfill. 100 chats × ~5 messages per
     * fetch = ~500 inserts, finishes in ~2 min. Rationale: covers the top-100
     * most-recent threads which is enough for analytics ('what are my
     * customers asking about this week'); deeper pulls happen on-demand via
     * the AI confirm flow with user-approved credit cost. Previously the job
     * had no cap and a page with 3000+ chats took ~2 hours to finish, which
     * made onboarding feel broken.
     */
    public const DEFAULT_LIMIT = 100;

    public int $tries = 1;

    public int $timeout = 180;

    /**
     * @param  int  $pageId
     * @param  int|null  $limit  Chats remaining to process in THIS cycle. null
     *                            seeds DEFAULT_LIMIT on the first tick; 0 means
     *                            unlimited (admin re-dispatch, AI deep-pull).
     *                            Chained ticks pass the decremented remaining.
     */
    public function __construct(public int $pageId, public ?int $limit = null) {}

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

        // Normalize the limit for THIS tick. null = fresh cycle, seed with the
        // default; 0 = unlimited (admin override).
        $remaining = $this->limit ?? self::DEFAULT_LIMIT;
        $unlimited = $remaining === 0;

        // 2026-10-13: mark the page as 'backfill in progress' so the Connections
        // page can show a progress bar + a dismissable completion toast. Only
        // set once per backfill cycle. Also records the target count so the UI
        // can show the right denominator (e.g. 100 recent chats, not 3086 total).
        if (empty(data_get($page->metadata, 'backfill_in_progress_started_at'))) {
            $cycleTarget = $unlimited ? $pending->count() : min($pending->count(), $remaining);
            $alreadyFetched = Conversation::where('page_id', $page->id)
                ->whereJsonContains('metadata->messages_fetched', true)
                ->count();

            $page->forceFill([
                'metadata' => array_merge($page->metadata ?? [], [
                    'backfill_in_progress_started_at'            => now()->toIso8601String(),
                    'backfill_notification_acked_at'             => null,
                    'backfill_cycle_target'                      => $cycleTarget,
                    'backfill_cycle_started_fetched_count'       => $alreadyFetched,
                    // Reset the email guard so this cycle's completion
                    // triggers a fresh email (prior cycle's send doesn't block).
                    'backfill_cycle_completed_email_sent_at'     => null,
                ]),
            ])->saveQuietly();
        }

        // Trim this tick's work by the remaining budget.
        $takeN = $unlimited ? self::BATCH : min(self::BATCH, $remaining);
        $toProcess = $pending->take($takeN);

        // 2026-10-13: previously stopped the whole backfill at 3 total failures.
        // On a 3000-conv page that was catastrophic — Mishkah got 24/3086 before
        // giving up. Now we only treat auth-type errors (page token revoked /
        // insufficient scopes) as fatal. Everything else (404, 5xx, rate limit)
        // just skips the conversation and keeps chaining — those will be retried
        // on the next scheduled sync.
        $authErrors = 0;
        foreach ($toProcess as $conversation) {
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

                    // Clear the in-progress flag so the /connections "Loading your
                    // messages from X 0/N" bar stops lying about progress. Record a
                    // paused reason + timestamp so a future reconnect-triggered
                    // sync (or an admin re-dispatch) can tell this was an auth stop
                    // rather than a fresh page. The DisconnectedPagesNotice banner
                    // (driven by PageHealthCheckCommand's metadata.subscription_error)
                    // is what nudges the user to reconnect.
                    $page->forceFill([
                        'metadata' => array_merge($page->metadata ?? [], [
                            'backfill_in_progress_started_at' => null,
                            'backfill_paused_at'              => now()->toIso8601String(),
                            'backfill_paused_reason'          => 'auth_failed',
                        ]),
                    ])->saveQuietly();

                    return;
                }
                Log::warning('Message backfill failed for a conversation (skipping)', [
                    'conversation' => $conversation->id, 'error' => $msg,
                ]);
            }
            usleep(300_000); // stay well under Meta's per-page rate limit
        }

        // Chain the next tick if there's more work AND more budget. Note we
        // use $toProcess->count() (what we actually took) not BATCH, so a short
        // final tick finishes the cycle cleanly.
        $processed = $toProcess->count();
        $stillPending = $pending->count() - $processed > 0;
        $newRemaining = $unlimited ? 0 : max(0, $remaining - $processed);

        // 2026-10-27: FacebookPlatform::fetchAndStoreMessages logs + returns 0
        // on failure instead of throwing — so the earlier auth-fail bail at
        // $authErrors >= 3 is only triggered when a lower-level exception
        // escapes. The common Brandk-style stuck case is "every call returned
        // 0, no exception". Detect that by checking whether ANY conversation
        // we just processed actually got messages_fetched set. If none did,
        // the backfill is making no forward progress — stop lying to the UI.
        if ($processed > 0) {
            $justFetched = Conversation::where('page_id', $page->id)
                ->whereIn('id', $toProcess->pluck('id'))
                ->whereJsonContains('metadata->messages_fetched', true)
                ->count();

            if ($justFetched === 0) {
                Log::warning('Message backfill made zero progress this tick (likely auth or scope issue, see PageHealthCheckCommand)', [
                    'page_id'   => $page->id,
                    'name'      => $page->name,
                    'processed' => $processed,
                ]);
                $page->forceFill([
                    'metadata' => array_merge($page->metadata ?? [], [
                        'backfill_in_progress_started_at' => null,
                        'backfill_paused_at'              => now()->toIso8601String(),
                        'backfill_paused_reason'          => 'no_progress',
                    ]),
                ])->saveQuietly();
                return;
            }
        }

        if ($stillPending && ($unlimited || $newRemaining > 0)) {
            self::dispatch($page->id, $unlimited ? 0 : $newRemaining)->delay(now()->addSeconds(10));
            return;
        }

        // 2026-10-13: cycle complete — email the team owner once (gated by
        // backfill_cycle_completed_email_sent_at so re-dispatches don't spam).
        // The /connections toast is still the primary completion signal; this
        // email covers users who weren't on that tab when the cycle finished.
        try {
            $meta = $page->fresh()->metadata ?? [];
            if (empty($meta['backfill_cycle_completed_email_sent_at'])) {
                $chatsLoaded = (int) \App\Models\Conversation::where('page_id', $page->id)
                    ->whereJsonContains('metadata->messages_fetched', true)
                    ->count();

                $owner = $page->team?->owner;
                if ($owner && $owner->email && $chatsLoaded > 0) {
                    \Illuminate\Support\Facades\Mail::to($owner->email)->queue(
                        new \App\Mail\PageBackfillComplete($owner, $page, $chatsLoaded)
                    );
                }

                $page->forceFill([
                    'metadata' => array_merge($meta, [
                        'backfill_cycle_completed_email_sent_at' => now()->toIso8601String(),
                    ]),
                ])->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::warning('PageBackfillComplete email dispatch failed', [
                'page_id' => $page->id, 'error' => $e->getMessage(),
            ]);
        }
    }
}
