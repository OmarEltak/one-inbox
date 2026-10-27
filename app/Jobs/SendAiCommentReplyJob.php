<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AiProviderInterface;
use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\ContactPlatform;
use App\Models\Message;
use App\Services\Billing\AiCredits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAiCommentReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $commentId)
    {
        $this->onQueue('comments-send');
    }

    public function handle(AiProviderInterface $ai): void
    {
        $comment = Comment::with('page.team', 'page.aiConfig', 'pagesPost')->find($this->commentId);
        if (! $comment || $comment->decision !== null) {
            return;
        }

        $team   = $comment->page->team;
        $config = $comment->page->aiConfig;

        if (! $team || ! $config || ! $team->canDispatchAi()) {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'team cannot dispatch AI',
            ]);
            return;
        }

        $settings = $config->comment_settings ?? AiConfig::defaultCommentSettings();
        $replyMode = $settings['reply_mode'] ?? AiConfig::COMMENT_REPLY_OFF;
        $dmMode    = $settings['dm_mode']    ?? AiConfig::COMMENT_DM_OFF;
        $willAttemptPublic = $replyMode !== AiConfig::COMMENT_REPLY_OFF;
        $willAttemptDm     = $this->shouldDm($dmMode, $settings, $comment->text);

        // DM per-commenter cap — enforced BEFORE the DM Nara call to save spend.
        // Public reply cap is per-post (enforced in IngestCommentJob/CommentFilterService).
        // This one is per-commenter — one chatty person on 5 posts should not get 5 DMs.
        $dmCapHit = false;
        if ($willAttemptDm) {
            $dmCap = (int) ($settings['max_dms_per_commenter_per_day']
                ?? AiConfig::defaultCommentSettings()['max_dms_per_commenter_per_day']);
            $store = Cache::store(config('comments.hot_cache_store', 'redis'));
            $key = "comments:dm-per-commenter:{$comment->page_id}:{$comment->commenter_platform_id}:" . now()->format('Y-m-d');
            if (! $store->has($key)) {
                $store->put($key, 0, now()->addDay());
            }
            $count = (int) $store->increment($key);
            if ($count > $dmCap) {
                $willAttemptDm = false;
                $dmCapHit = true;
            }
        }

        if (! $willAttemptPublic && ! $willAttemptDm) {
            $comment->update([
                'decision'        => Comment::DECISION_FILTERED_OFF,
                'decision_reason' => $dmCapHit ? 'DM cap per-commenter exhausted; no public reply configured' : 'no attempt configured',
            ]);
            return;
        }

        // --- Public reply text (uses reply_instructions) ---
        $publicReplyText = null;
        if ($willAttemptPublic) {
            [$sys, $usr] = $this->buildPublicReplyPrompt($config, $settings, $comment);
            $publicReplyText = trim($ai->generateText($sys, $usr));
        }

        // --- DM text (its OWN prompt; does NOT use reply_instructions since those
        //     are meant for public replies; instead uses conversation history for
        //     language + tone matching) ---
        $dmText = null;
        if ($willAttemptDm) {
            $conversationSnippets = $this->recentCommenterMessages($comment);
            [$sys, $usr] = $this->buildDmPrompt($config, $settings, $comment, $conversationSnippets);
            $dmText = trim($ai->generateText($sys, $usr));
        }

        if (($willAttemptPublic && $publicReplyText === '') || ($willAttemptDm && $dmText === '')) {
            // Per pin #5: empty string = provider failure. Do NOT send fallback text.
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'ai_provider_empty_response',
            ]);
            return;
        }

        // Store the public reply text on the comment row for observability.
        $comment->reply_text = $publicReplyText;

        // --- Attempt public reply ---
        $publicOk = false;
        if ($willAttemptPublic) {
            $graphResp = $this->postPublicReply($comment, $publicReplyText);
            $publicOk = $graphResp['ok'];
            if ($publicOk) {
                $comment->graph_reply_id = $graphResp['id'];
                if (! empty($graphResp['fallback_used'])) {
                    // Record so /comments can show the subtle "replied as top-level
                    // comment on parent post" badge and operators can tell apart
                    // nested-reply success from fallback success without reading logs.
                    $comment->graph_error = ['fallback_used' => 'top_level_post_comment', 'reason' => 'fb_scope_bundling_pages_manage_engagement'];
                }
            } else {
                $comment->graph_error = $graphResp['error'] ?? null;
                Log::warning('SendAiCommentReplyJob: public reply failed', [
                    'comment_id' => $comment->id,
                    'status'     => $graphResp['status'],
                    'error'      => $graphResp['error'] ?? null,
                ]);
                if ($graphResp['status'] >= 500 || $graphResp['status'] === 429) {
                    $comment->decision = Comment::DECISION_ERROR_GRAPH_API;
                    $comment->decision_reason = 'public reply retryable Graph failure';
                    $comment->save();
                    throw new \RuntimeException("Retryable Graph failure: {$graphResp['status']}");
                }
            }
        }

        // --- Attempt DM ---
        $dmOk = false;
        if ($willAttemptDm) {
            $dmResp = $this->sendDm($comment, $dmText);
            if ($dmResp['ok']) {
                $dmOk = true;
                $comment->dm_sent_at = now();
                $comment->dm_graph_message_id = $dmResp['message_id'];
            } else {
                $existing = $comment->graph_error ?? [];
                $comment->graph_error = ['public' => $existing, 'dm' => $dmResp['error'] ?? null];
                Log::warning('SendAiCommentReplyJob: DM failed', [
                    'comment_id' => $comment->id,
                    'status'     => $dmResp['status'],
                ]);
            }
        }

        // Final decision.
        $comment->decision = match (true) {
            $publicOk           => Comment::DECISION_REPLIED,   // public succeeded (with or without DM)
            $dmOk               => Comment::DECISION_DM_ONLY,   // DM only
            default             => Comment::DECISION_ERROR_GRAPH_API,
        };
        if (! $publicOk && $dmOk) {
            $comment->decision_reason = 'public reply blocked, DM delivered';
        } elseif (! $publicOk && ! $dmOk) {
            $comment->decision_reason = 'both public reply and DM failed';
        }

        $comment->save();

        // Phase A credit charge — post-success per spec §9. Only charge if at
        // least one of (public reply, DM) actually went out. A failed attempt
        // produced no AI tokens worth billing for. Idempotency key = comment
        // id so a retried job never double-charges.
        if ($publicOk || $dmOk) {
            try {
                app(AiCredits::class)->charge(
                    team: $team,
                    action: 'ai_comment_reply',
                    meta: [
                        'idempotency_key' => "ai_comment_reply:{$comment->id}",
                        'cost_source_type' => Comment::class,
                        'cost_source_id' => $comment->id,
                    ],
                );
            } catch (\Throwable $e) {
                Log::warning("AiCredits::charge failed for comment {$comment->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Prompt for the PUBLIC reply text (posted under the comment on the post).
     * Uses reply_instructions verbatim — those rules are meant for public replies.
     *
     * @return array{0: string, 1: string}
     */
    protected function buildPublicReplyPrompt(AiConfig $config, array $settings, Comment $comment): array
    {
        $persona = "You reply to public comments on the business's " . $comment->page->platform . " page. "
                 . "Keep replies to 1-3 sentences, natural tone, no hard sell. "
                 . "Business: " . mb_substr((string) $config->business_description, 0, 400) . ". "
                 . "Tone: " . ($config->tone ?? 'friendly') . ". "
                 . "Language: " . ($config->language ?? 'en') . ".";
        if (! empty($settings['reply_instructions'])) {
            $persona .= " Extra rules: " . $settings['reply_instructions'];
        }
        $user = "Public comment from {$comment->commenter_name}: \"{$comment->text}\"";

        return [$persona, $user];
    }

    /**
     * Prompt for the DM text sent privately to the commenter. Deliberately does
     * NOT use reply_instructions (those are for public replies — e.g. "always say
     * check your DM" makes no sense inside a DM). Instead uses recent
     * conversation history (if any) for language + tone matching.
     *
     * Language cascade:
     *  1. If the commenter has recent messages with this page, mirror their language.
     *  2. Otherwise, detect from the comment text itself.
     *  3. Fall back to AiConfig.language.
     *
     * @param  array<int, string>  $conversationSnippets  Last few customer messages, most recent first
     * @return array{0: string, 1: string}
     */
    protected function buildDmPrompt(AiConfig $config, array $settings, Comment $comment, array $conversationSnippets): array
    {
        $persona = "You send a private direct message to a commenter on the business's " . $comment->page->platform . " page. "
                 . "Keep the DM to 1-3 sentences: warm, personal, ask a helpful question. "
                 . "Do NOT say 'check your DM' or refer to the public comment thread — you ARE the DM. "
                 . "Business: " . mb_substr((string) $config->business_description, 0, 400) . ". "
                 . "Tone: " . ($config->tone ?? 'friendly') . ". ";

        if ($conversationSnippets) {
            $persona .= "IMPORTANT: reply in the SAME language and tone this person has used with us before. Recent messages from them (most recent first):\n"
                     . implode("\n", array_map(fn ($m) => "- \"{$m}\"", array_slice($conversationSnippets, 0, 5)));
        } else {
            $persona .= "IMPORTANT: reply in the same language the person wrote their comment in. If the comment text is too short to detect language, default to " . ($config->language ?? 'en') . ".";
        }

        $user = "Public comment from {$comment->commenter_name}: \"{$comment->text}\"\n\nWrite the DM you want to send them.";

        return [$persona, $user];
    }

    /**
     * Fetch the commenter's most recent inbound messages across any conversation
     * they have with this page. Used purely to give the DM prompt a language +
     * style anchor. Returns [] when the commenter has no history with the page.
     *
     * Only inbound messages (from the customer) matter — outbound AI/staff
     * replies don't tell us anything about the customer's language preference.
     *
     * @return array<int, string>
     */
    protected function recentCommenterMessages(Comment $comment): array
    {
        // Find contact via ContactPlatform (platform_contact_id == commenter FB id).
        $link = ContactPlatform::where('platform', $comment->page->platform)
            ->where('platform_contact_id', $comment->commenter_platform_id)
            ->first();
        if (! $link) {
            return [];
        }

        // Pull the last 5 inbound (customer-authored) messages across ANY of this
        // contact's conversations with this specific page. Direction=inbound means
        // the customer wrote it (mirrors the naming in SendAiResponse).
        // page_id lives on the conversation, not on messages — join through it.
        return Message::whereHas('conversation', fn ($q) => $q
                ->where('contact_id', $link->contact_id)
                ->where('page_id', $comment->page_id))
            ->where('direction', 'inbound')
            ->latest('id')
            ->limit(5)
            ->pluck('content')
            ->filter(fn ($c) => is_string($c) && $c !== '')
            ->values()
            ->all();
    }

    /**
     * Pick the Graph host for a page. Direct IG Login pages have tokens minted
     * by graph.instagram.com's auth server and are rejected by graph.facebook.com
     * with code 190 "Cannot parse access token" (observed in prod on
     * OWOWOKWKSJF comments 2026-10-27). Other IG pages (via-FB) and all FB
     * pages use graph.facebook.com. The auth_type sentinel 'instagram_business'
     * is written by handleInstagramCallback and matches the convention used in
     * FacebookPlatform::fetchAndStoreMessages + fetchConversationsPage +
     * sendMessageToConversation (lines 925, 983, 1122, 1235) — keep in sync.
     */
    protected function graphHostFor(Comment $comment): string
    {
        $isInstagramBusiness = ($comment->page->metadata['auth_type'] ?? null) === 'instagram_business';
        return $isInstagramBusiness
            ? 'https://graph.instagram.com/v21.0'
            : 'https://graph.facebook.com/v21.0';
    }

    /** @return array{ok: bool, id?: string, status: int, error?: array, fallback_used?: bool} */
    protected function postPublicReply(Comment $comment, string $reply): array
    {
        // Meta uses different reply subpaths per platform:
        //   • FB comments:  POST /{comment-id}/comments   (nested comment thread)
        //   • IG comments:  POST /{comment-id}/replies    (IG's reply model)
        // Hitting the wrong one returns HTTP 400 "(#100) Unsupported post request.
        // Object with ID '…' does not exist, cannot be loaded due to missing
        // permissions, or does not support this operation" because the subpath
        // literally doesn't exist on the other platform's comment object.
        $isIg    = in_array($comment->page->platform, ['instagram', 'instagram_business'], true);
        $subpath = $isIg ? 'replies' : 'comments';
        $host    = $this->graphHostFor($comment);

        $response = Http::timeout(15)->asJson()->post(
            "{$host}/{$comment->platform_comment_id}/{$subpath}",
            [
                'message'      => $reply,
                'access_token' => $comment->page->page_access_token,
            ]
        );

        if ($response->successful()) {
            return ['ok' => true, 'id' => (string) $response->json('id'), 'status' => $response->status()];
        }

        $errorBody = (array) $response->json();

        // 2026-10-27 fallback: FB nested replies on a user's comment need
        // pages_manage_engagement, which we cannot request (OAuth validator
        // bundling bug with deprecated pages_read_user_content — see
        // tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php and the
        // comments-architecture skill §6 for the 5-attempt history). When Meta
        // returns the specific "pages_read_user_content not available" error,
        // retry by posting a NEW top-level comment on the Page's own post
        // (POST /{post-id}/comments) — that endpoint is covered by
        // pages_manage_posts which we DO have. Prefix with the commenter's name
        // so the reply still reads as a direct response. IG does NOT get this
        // fallback (its comment reply path works fine via /replies). Remove this
        // fallback branch if Meta ever fixes the bundling bug and we can re-add
        // pages_manage_engagement — the primary nested path is a better UX.
        if (! $isIg && $this->isScopeBundlingError($response->status(), $errorBody) && $this->canFallBackToTopLevel($comment)) {
            $fallback = $this->postTopLevelCommentFallback($comment, $reply, $host);
            if ($fallback['ok']) {
                return $fallback + ['fallback_used' => true];
            }
            // Fallback also failed — return THAT error, more actionable than
            // the pages_read_user_content one we already knew about.
            return $fallback + ['fallback_used' => true];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => $errorBody];
    }

    /**
     * Detect Meta's "you lack pages_manage_engagement" error. Code 200 +
     * message containing 'pages_read_user_content' is Meta's canonical shape
     * for this since 2026-09 (confirmed by the Brandk prod error row at
     * /comments on 2026-10-27). Any other 4xx is a different problem.
     */
    protected function isScopeBundlingError(int $status, array $errorBody): bool
    {
        if ($status !== 400 && $status !== 403) {
            return false;
        }
        $err  = $errorBody['error'] ?? [];
        $code = (int) ($err['code'] ?? 0);
        $msg  = (string) ($err['message'] ?? '');
        return $code === 200 && str_contains($msg, 'pages_read_user_content');
    }

    /**
     * Fallback is only valid when we know the parent post ID. For nested
     * reply-to-a-reply the top-level fallback would land on the WRONG thread,
     * so skip the fallback — better to surface the error than lie publicly.
     */
    protected function canFallBackToTopLevel(Comment $comment): bool
    {
        if ($comment->parent_comment_id !== null) {
            return false;
        }
        $post = $comment->pagesPost;
        return $post !== null && ! empty($post->platform_post_id);
    }

    /** @return array{ok: bool, id?: string, status: int, error?: array} */
    protected function postTopLevelCommentFallback(Comment $comment, string $reply, string $host): array
    {
        $postId = (string) $comment->pagesPost->platform_post_id;
        // Prefix with commenter name so the reply reads as a direct response
        // even though it is not nested. FB does NOT auto-notify tagged people
        // without proper @mention tagging (which requires another scope),
        // so this is just human-readable attribution, not a real tag.
        $prefixedReply = "@{$comment->commenter_name} {$reply}";

        $response = Http::timeout(15)->asJson()->post(
            "{$host}/{$postId}/comments",
            [
                'message'      => $prefixedReply,
                'access_token' => $comment->page->page_access_token,
            ]
        );

        if ($response->successful()) {
            Log::info('SendAiCommentReplyJob: nested reply blocked by scope bundling; delivered as top-level comment on parent post', [
                'comment_id'   => $comment->id,
                'post_id'      => $postId,
                'reply_id'     => (string) $response->json('id'),
            ]);
            return ['ok' => true, 'id' => (string) $response->json('id'), 'status' => $response->status()];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => (array) $response->json()];
    }

    /** @return array{ok: bool, message_id?: string, status: int, error?: array} */
    protected function sendDm(Comment $comment, string $reply): array
    {
        $recipientContainerId = $comment->page->platform === 'instagram'
            ? ($comment->page->metadata['ig_user_id'] ?? $comment->page->platform_page_id)
            : $comment->page->platform_page_id;

        $body = [
            'recipient' => ['comment_id' => $comment->platform_comment_id],
            'message'   => ['text' => $reply],
        ];
        if ($comment->page->platform === 'facebook') {
            $body['messaging_type'] = 'RESPONSE';
        }

        $token = $comment->page->page_access_token;
        $host  = $this->graphHostFor($comment);
        $response = Http::timeout(15)->asJson()->post(
            "{$host}/{$recipientContainerId}/messages?access_token=" . urlencode($token),
            $body
        );

        if ($response->successful()) {
            return ['ok' => true, 'message_id' => (string) ($response->json('message_id') ?? ''), 'status' => $response->status()];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => (array) $response->json()];
    }

    protected function shouldDm(string $mode, array $settings, string $text): bool
    {
        return match ($mode) {
            AiConfig::COMMENT_DM_OFF                => false,
            AiConfig::COMMENT_DM_ALWAYS             => true,
            AiConfig::COMMENT_DM_ON_PURCHASE_INTENT => $this->matchesAny($text, $settings['dm_keywords'] ?? []),
            default                                 => false,
        };
    }

    /** @param array<int, string> $keywords */
    protected function matchesAny(string $text, array $keywords): bool
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
