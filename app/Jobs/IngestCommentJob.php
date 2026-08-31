<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\Page;
use App\Services\Comments\CommentFilterService;
use App\Services\Comments\PostCreationTimeCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IngestCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    /**
     * @param  array{platform: 'facebook'|'instagram', platform_comment_id: string, platform_post_id: string, parent_comment_id: ?string, commenter_platform_id: string, commenter_name: string, text: string, received_at: \Carbon\Carbon}  $parsed
     */
    public function __construct(public array $parsed, public string $platformPageId)
    {
        $this->onQueue('comments-ingest');
    }

    public function handle(PostCreationTimeCache $postCache, CommentFilterService $filter): void
    {
        // 1. Dedupe — Cache::add() is atomic on any driver (SET NX on Redis, INSERT-if-missing on database).
        // Explicit store bypasses prod's CACHE_STORE=database default; hot-path needs sub-ms latency.
        $dedupeKey = "comments:seen:{$this->parsed['platform_comment_id']}";
        $store = Cache::store(config('comments.hot_cache_store', 'redis'));
        if (! $store->add($dedupeKey, 1, now()->addDay())) {
            return; // already seen in the last 24h
        }

        // 2. Resolve page (matches ProcessIncomingMessage's shape).
        $page = Page::where('platform', $this->parsed['platform'])
            ->where(fn ($q) => $q->where('platform_page_id', $this->platformPageId)
                ->orWhereJsonContains('metadata->igsid', $this->platformPageId)
                ->orWhereJsonContains('metadata->igbid', $this->platformPageId))
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $page || ! $page->aiConfig) {
            return;
        }

        // 3. Resolve/cache post.
        $post = $postCache->resolve($page, $this->parsed['platform_post_id']);
        if (! $post) {
            Log::warning('IngestCommentJob: could not resolve post creation time', [
                'page_id' => $page->id,
                'post_id' => $this->parsed['platform_post_id'],
            ]);
            return;
        }

        // 4. Run filter decision tree.
        $decision = $filter->decide($page, $page->aiConfig, $post, $this->parsed);

        // 5. Store row (decision may be null → "queued for reply").
        $comment = Comment::create([
            'page_id'               => $page->id,
            'pages_post_id'         => $post->id,
            'platform_comment_id'   => $this->parsed['platform_comment_id'],
            'parent_comment_id'     => $this->parsed['parent_comment_id'],
            'commenter_platform_id' => $this->parsed['commenter_platform_id'],
            'commenter_name'        => $this->parsed['commenter_name'],
            'text'                  => $this->parsed['text'],
            'received_at'           => $this->parsed['received_at'],
            'decision'              => $decision['decision'],
            'decision_reason'       => $decision['reason'],
        ]);

        if ($decision['decision'] !== null) {
            return;
        }

        // 6. Dispatch classifier (q&c mode) or straight to sender.
        $settings = $page->aiConfig->comment_settings ?? AiConfig::defaultCommentSettings();
        $mode = $settings['reply_mode'] ?? AiConfig::COMMENT_REPLY_OFF;
        if ($mode === AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS) {
            ClassifyCommentJob::dispatch($comment->id);
        } else {
            SendAiCommentReplyJob::dispatch($comment->id);
        }
    }
}
