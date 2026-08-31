# Comments AI — Phase B (Ingestion + Sending) Design

**Date:** 2026-08-31
**Status:** Approved, ready to plan
**Scope:** Turn Phase A's dormant configuration into a live system: subscribe to Meta feed/comment webhooks, classify + filter + rate-limit, send public replies via Graph API, and optionally open a one-shot DM with the commenter. Ships without waiting for Meta App Review because the managed OAuth flow already places customer Pages inside our verified Business Portfolio (Standard Access is sufficient).

**Depends on:** [Phase A spec](./2026-08-31-comments-ai-config-design.md) — the `comment_settings` JSON column and its constants.

---

## 1. Why we can ship without App Review

Per `CLAUDE.md` pin #1, our Business Portfolio (OT1 Pro, ID `2169075923895403`) is verified. Meta permissions in **Standard Access** work for any Page inside a verified Business Portfolio. The managed onboarding flow (customer → OT super-admin OAuths → super-admin reassigns Page) means every real customer Page is inside our Portfolio. So `pages_manage_engagement` and `instagram_manage_comments` under Standard Access are sufficient for the managed flow.

App Review is still submitted in parallel (Task in plan) so that if we ever add self-serve OAuth we're already approved.

## 2. What we add to the existing managed OAuth

`app/Services/Platforms/FacebookPlatform.php` already requests `instagram_manage_comments` (line 70, IG-via-FB flow). We need to add `pages_manage_engagement` in three places:

| Location | Current scopes | Add |
|---|---|---|
| Line 48 (`getConnectUrl` — FB-only) | pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement | `pages_manage_engagement` |
| Line 70 (`getInstagramViaFacebookConnectUrl` — combined FB+IG) | above + instagram_basic, instagram_manage_messages, instagram_manage_comments | `pages_manage_engagement` |
| Line 339 (metadata array on `ConnectedAccount`) | same as line 48 list | `pages_manage_engagement` |

## 3. Webhook subscription

Meta requires each app's Page subscriptions to list which fields we consume. Add `feed` (FB) and `comments` (IG) to the subscribe call currently used for `messages` in `FacebookPlatform::subscribePageToWebhooks()` and its Instagram counterpart. This is one Graph call per Page during onboarding — reuses the existing pattern.

## 4. Storage — two small, focused tables

### `pages_posts` — post creation-time cache

```
id BIGINT PK
page_id BIGINT FK -> pages.id (cascade)
platform_post_id VARCHAR(255)          -- Meta post id, e.g. "123_456"
created_at_platform TIMESTAMP           -- when the post was made on FB/IG
first_seen_at TIMESTAMP                 -- when we first saw a comment on it
UNIQUE (page_id, platform_post_id)
```

Written on first comment for a post; read on every comment for scope filtering. Stays tiny (~1 row per unique post the AI has ever processed).

### `comments` — decisions, not raw firehose

```
id BIGINT PK
page_id BIGINT FK -> pages.id (cascade)
pages_post_id BIGINT FK -> pages_posts.id (cascade)
platform_comment_id VARCHAR(255) UNIQUE INDEXED
parent_comment_id VARCHAR(255) NULL     -- for future reply-to-reply support; MVP ignores replies
commenter_platform_id VARCHAR(255)
commenter_name VARCHAR(255)
text TEXT
received_at TIMESTAMP                    -- Meta-provided created_time
decision ENUM(
    'replied',                          -- AI generated + sent a public reply
    'dm_only',                          -- (reserved) AI sent DM without a public reply — not used in MVP
    'rate_limited',                     -- per-post daily cap exceeded
    'filtered_off',                     -- comment_settings.reply_mode = off
    'filtered_mode',                    -- classifier said "N" and mode = questions_and_complaints
    'filtered_scope',                   -- post is older than enabled_at + scope = future_only
    'filtered_keyword',                 -- mode = custom_keywords and no match
    'filtered_working_hours',           -- outside AiConfig working_hours
    'filtered_self',                    -- the Page commented on its own post
    'filtered_reply',                   -- reply-to-comment (not top-level)
    'error_graph_api',                  -- Graph API 4xx (permanent) — see graph_error
    'error_ai'                          -- Nara call failed
)
decision_reason VARCHAR(255) NULL       -- human-readable one-liner for support
reply_text TEXT NULL                    -- the AI's reply, if we sent one
graph_reply_id VARCHAR(255) NULL       -- Meta's id for the reply we posted (audit trail)
dm_sent_at TIMESTAMP NULL              -- when the one-shot DM went out
dm_graph_message_id VARCHAR(255) NULL  -- Meta message id for the DM
graph_error JSON NULL                  -- Meta error body if the send failed
created_at, updated_at
INDEX (page_id, created_at DESC)        -- for a future admin dashboard
INDEX (page_id, decision, created_at)   -- for filtering by outcome
```

Every row means "we saw this comment, we decided X." Skipped-by-filter rows are still stored (they're small — most fields NULL) so support can answer "why didn't the AI reply?"

## 5. Redis, not MySQL, for two hot-path lookups

Prod's `CACHE_STORE=database`. That's fine for other caches but our two per-comment lookups need <1ms. Use `Illuminate\Support\Facades\Redis::` directly (Redis is installed and QUEUE_CONNECTION=redis already runs against it).

### Dedupe

```php
$key = "comments:seen:{$platformCommentId}";
if (! Redis::set($key, '1', 'EX', 86400, 'NX')) {
    return; // seen in the last 24h — Meta redelivered
}
```

Meta's redelivery window is 24h. NX makes the check-and-set atomic.

### Rate limit

```php
$key = "comments:rl:{$pageId}:{$pagePostId}:" . now()->format('Y-m-d');
$count = Redis::incr($key);
if ($count === 1) {
    Redis::expire($key, 86400);
}
if ($count > $config->commentSettings()['max_ai_replies_per_post_per_day']) {
    // store row with decision=rate_limited, return
}
```

Both operations are O(1) and don't touch MySQL.

## 6. Two-stage job pipeline

**Why two jobs, not one:** The ingest stage is <50ms (Redis lookups, DB reads, one Graph call for post creation-time only on cache miss). The send stage is 2-8s (Nara AI call + Graph API replies). Splitting them lets ingest drain webhook bursts fast while sends throttle independently.

### `IngestCommentJob` (queue: `comments-ingest`)

Dispatched from `ProcessIncomingMessage` when a webhook `change.field` is `feed` (FB) or `comments` (IG) with `item=comment`.

```
1. Parse comment payload → extract platform_comment_id, post_id, commenter, text, created_time.
2. Redis dedupe → return if seen.
3. Resolve Page (existing helper: match on platform_page_id + JSON metadata).
4. If ! $page || ! $page->aiConfig || ! $page->aiConfig->comment_settings['enabled'] → drop silently.
5. Skip if payload marks this as a reply-to-comment (item.parent_id exists) → store filtered_reply.
6. Skip if commenter == page → store filtered_self.
7. Skip if ! aiConfig->isWithinWorkingHours() → store filtered_working_hours.
8. Resolve pages_posts row (SELECT + on miss, Graph API GET /{post_id}?fields=created_time, then INSERT).
9. Scope check: if scope=future_only && post.created_at_platform < enabled_at → store filtered_scope.
10. Mode filter:
    - reply_mode=off → store filtered_off.
    - reply_mode=all → proceed.
    - reply_mode=questions_and_complaints → dispatch to ClassifyCommentJob first (see below).
    - reply_mode=custom_keywords → substring match against reply_keywords; on miss store filtered_keyword.
11. Rate limit INCR → on over-cap, store rate_limited.
12. Store `comments` row (decision=null so far means "queued for reply") + dispatch SendAiCommentReplyJob.
```

### `ClassifyCommentJob` (queue: `comments-ingest`, throttled)

Only invoked when `reply_mode=questions_and_complaints`. Makes one Nara call with a 1-token expected output ("Q", "C", or "N"). Uses the cheapest model in the fallback chain (haiku). On "N" → store `filtered_mode`. On "Q" or "C" → proceed to rate limit check + SendAiCommentReplyJob.

Split from IngestComment so we can throttle classifier calls per-page under load without slowing simpler modes.

### `SendAiCommentReplyJob` (queue: `comments-send`)

```
1. Load comments row (bail if decision already set).
2. Build prompt: base persona + comment_reply_instructions + short business context (business_description, tone, language).
   Keep prompt short — comments are public, reply is 1-3 sentences target.
3. Nara callChat via existing AiProviderInterface. On failure → decision=error_ai, exception → retry (Laravel default).
4. Graph API: POST /{platform_comment_id}/comments with message=reply_text + access_token=page_access_token.
5. On 4xx → decision=error_graph_api, log body, NO retry (permanent — deleted comment or blocked user).
6. On 5xx/429 → throw → Laravel retries with backoff.
7. On 200 → decision=replied, graph_reply_id=response.id, reply_text=...
8. Decide DM:
   - dm_mode=off → done.
   - dm_mode=always → send.
   - dm_mode=on_purchase_intent → substring match against dm_keywords; send if match.
9. Send DM: POST /{page_id or ig_user_id}/messages with recipient={comment_id: platform_comment_id} and message={text: dm_text}.
   Meta's private-reply-via-comment-id endpoint. FB has 24h window, IG has 7d. We hit it well within both.
10. On DM success → dm_sent_at=now(), dm_graph_message_id=response.message_id.
    On DM failure → log to graph_error but keep decision=replied (public reply succeeded, DM is bonus).
```

**Retry policy:** `tries=3`, backoff `[10, 60, 300]` seconds. Only 5xx/429 from Meta triggers retry. 4xx is a permanent decision.

## 7. Classifier prompt (cheapest-tier Nara call)

```
System: You classify a single public comment into one of three categories.
Respond with EXACTLY ONE letter: Q, C, or N.
- Q: the comment asks a question (explicit or implied).
- C: the comment expresses a complaint, problem, or negative sentiment.
- N: neither — praise, greeting, spam, off-topic.

User: {comment_text}
```

Budget: ~$0.0001/comment on haiku. Fires only in `questions_and_complaints` mode. Zero cost for other modes.

## 8. Post creation-time fetch

On `pages_posts` cache miss:

```
GET https://graph.facebook.com/v21.0/{platform_post_id}?fields=created_time
Authorization: page_access_token
```

Response has ISO 8601 `created_time`. Store in `pages_posts.created_at_platform`. One call per unique post ever, regardless of comment volume.

## 9. Graph API endpoints (reference)

**Reply to comment (both platforms):**
```
POST https://graph.facebook.com/v21.0/{comment_id}/comments
  ?access_token={page_access_token}
  &message={reply_text}
```
Returns `{id: "commentId_replyId"}` which we store in `graph_reply_id`.

**DM the commenter (FB):**
```
POST https://graph.facebook.com/v21.0/{page_id}/messages
  ?access_token={page_access_token}
Body (JSON):
{
  "recipient": {"comment_id": "{platform_comment_id}"},
  "message": {"text": "{dm_text}"},
  "messaging_type": "RESPONSE"
}
```

**DM the commenter (IG):**
```
POST https://graph.facebook.com/v21.0/{ig_user_id}/messages
  ?access_token={page_access_token}
Body (JSON):
{
  "recipient": {"comment_id": "{platform_comment_id}"},
  "message": {"text": "{dm_text}"}
}
```

Meta enforces the 24h (FB) / 7d (IG) window server-side. If we're within it and the OAuth scopes are present, it works. If out of window, Meta returns a 4xx which we log as `error_graph_api` and don't retry.

## 10. Explicit non-goals for Phase B (Phase C or later)

- Batching comments-per-post into one AI call
- Analytics dashboard (raw table is queryable meanwhile)
- Manual "reprocess this comment" override for super-admin
- Deleting/hiding comments (`pages_manage_engagement` grants it but MVP doesn't ship UI)
- Passing post text/context to the AI (only comment text)
- Handling comment REPLIES (only top-level for MVP; stored with `filtered_reply`)
- Custom rate-limit reply message (silent stop only)
- Nudging or reactivating stalled DM threads (fire-and-forget)

## 11. Load-bearing pins respected (from CLAUDE.md)

- **Pin #1 `$metaVerified`:** unchanged; managed OAuth is untouched at the button level, only scope string is extended.
- **Pin #2 managed onboarding flow:** unchanged.
- **Pin #3 Page::booted() observer:** untouched.
- **Pin #4 `Team::canDispatchAi()`:** SendAiCommentReplyJob MUST call `$team->canDispatchAi()` before the Nara call, just like every other dispatch site.
- **Pin #5 provider empty-string on failure:** honored; on empty string from Nara we set `decision=error_ai` and skip the Graph reply (no "I'm having a moment" text ever sent).
- **Pin #7 NaraRouter reset semantics:** untouched.
- **Pin #9 `coalesceRoles`:** we're using single-turn classifier + short reply prompts, but still route through the standard `AiProviderInterface` which handles it.
- **Pin #10 reactivation guard:** doesn't apply — comments don't participate in conversation metadata.

## 12. Meta App Review (parallel task)

Even though managed OAuth ships today, submit the two permissions for App Review so a future self-serve path unlocks them for everyone. I will draft:

- **Use-case text** for each permission (150-300 words each; explains the exact user-facing feature and why the permission is required).
- **Reviewer test-user script** (step-by-step: log in as this test user, connect this test page, post this comment, verify AI reply appears).
- **Screencast script** (30-90s per permission, showing the user flow).
- **Submission checklist** for the developer.facebook.com console (exact click path).

The submission itself must be performed by you (no Meta credentials on my end). When you confirm submission in chat, I mark the App Review task complete in the plan.

## 13. Performance budget

Baseline: assume worst-case 500 comments/day across all customer pages initially.

Per-comment cost:
- 2 Redis calls (~80µs)
- 1-3 MySQL queries (Page lookup already cached in Laravel's per-request cache; pages_posts on cache hit is 1 query; comments insert is 1 query) — ~5ms
- 0 or 1 Graph API call for post creation-time (cache miss only)
- 0 or 1 Nara classifier call (~200ms, only in q&c mode, cheap model)
- 1 Nara reply call (~1-3s, primary model)
- 1 Graph API reply call (~300ms)
- 0 or 1 Graph API DM call (~300ms)

Worst case per comment: ~5s wall time, ~$0.001-$0.003 in AI cost. At 500 comments/day: ~40 minutes of queue worker time and ~$0.50-$1.50/day in Nara spend. Sustainable on the current small server.

## 14. Rollout & rollback

**Rollout:** normal `git push origin main` → GitHub Actions auto-deploy → migration runs → queue picks up new job classes on next `queue:restart` (which the deploy pipeline already does).

**Rollback (within the hour):** revert the merge commit. The migration adds two new tables; leaving them in place after revert is harmless (nothing reads them post-revert). If they must be dropped: `sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan migrate:rollback --step=1`.

**Feature-flag safety:** the whole feature is already gated behind `AiConfig::comment_settings.enabled=false` per row from Phase A. Zero customers have flipped it. So even after Phase B code lands, nothing fires until each customer opts in on their config page.
