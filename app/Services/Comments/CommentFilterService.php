<?php

declare(strict_types=1);

namespace App\Services\Comments;

use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;
use Illuminate\Support\Facades\Cache;

class CommentFilterService
{
    /**
     * @param  array{platform_comment_id: string, platform_post_id: string, parent_comment_id: ?string, commenter_platform_id: string, text: string}  $parsed
     * @return array{decision: string|null, reason: string|null}
     */
    public function decide(Page $page, AiConfig $config, PagesPost $post, array $parsed): array
    {
        $settings = $config->comment_settings ?? AiConfig::defaultCommentSettings();

        if (empty($settings['enabled'])) {
            return ['decision' => Comment::DECISION_FILTERED_OFF, 'reason' => 'feature disabled on this page'];
        }

        if ($parsed['parent_comment_id'] !== null) {
            return ['decision' => Comment::DECISION_FILTERED_REPLY, 'reason' => 'reply-to-comment; MVP only handles top-level'];
        }

        if ($this->commenterIsPage($page, $parsed['commenter_platform_id'])) {
            return ['decision' => Comment::DECISION_FILTERED_SELF, 'reason' => 'page commented on its own post'];
        }

        if (! $config->isWithinWorkingHours()) {
            return ['decision' => Comment::DECISION_FILTERED_WORKING_HOURS, 'reason' => 'outside working hours'];
        }

        $scope     = $settings['scope']       ?? AiConfig::COMMENT_SCOPE_FUTURE_ONLY;
        $enabledAt = $settings['enabled_at'] ?? null;
        if ($scope === AiConfig::COMMENT_SCOPE_FUTURE_ONLY && $enabledAt) {
            if ($post->created_at_platform->lt(\Carbon\Carbon::parse($enabledAt))) {
                return ['decision' => Comment::DECISION_FILTERED_SCOPE, 'reason' => "post predates enabled_at={$enabledAt}"];
            }
        }

        $mode = $settings['reply_mode'] ?? AiConfig::COMMENT_REPLY_OFF;
        if ($mode === AiConfig::COMMENT_REPLY_OFF) {
            return ['decision' => Comment::DECISION_FILTERED_OFF, 'reason' => 'reply_mode=off'];
        }

        if ($mode === AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS) {
            $keywords = $settings['reply_keywords'] ?? [];
            if (! $this->anyKeywordMatches($parsed['text'], $keywords)) {
                return ['decision' => Comment::DECISION_FILTERED_KEYWORD, 'reason' => 'no reply_keyword matched'];
            }
        }

        // Rate limit — atomic increment via Cache::store('redis').
        // Explicit 'redis' store bypasses prod's CACHE_STORE=database default; hot-path needs sub-ms latency.
        $rlKey = "comments:rl:{$page->id}:{$post->id}:" . now()->format('Y-m-d');
        $store = Cache::store(config('comments.hot_cache_store', 'redis'));
        if (! $store->has($rlKey)) {
            $store->put($rlKey, 0, now()->addDay());
        }
        $count = $store->increment($rlKey);
        $cap = (int) ($settings['max_ai_replies_per_post_per_day'] ?? 20);
        if ($count > $cap) {
            return ['decision' => Comment::DECISION_RATE_LIMITED, 'reason' => "cap {$cap}/day reached"];
        }

        return ['decision' => null, 'reason' => null];
    }

    protected function commenterIsPage(Page $page, string $commenterId): bool
    {
        $selfIds = array_filter([
            $page->platform_page_id,
            $page->metadata['igsid'] ?? null,
            $page->metadata['igbid'] ?? null,
        ]);

        return in_array($commenterId, $selfIds, true);
    }

    /** @param array<int, string> $keywords */
    protected function anyKeywordMatches(string $text, array $keywords): bool
    {
        $needle = mb_strtolower($text);
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($needle, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }
}
