---
name: comments-architecture
description: Use BEFORE touching anything in `app/Jobs/IngestCommentJob.php`, `app/Jobs/SendAiCommentReplyJob.php`, `app/Services/Comments/`, `app/Livewire/Comments/`, `app/Models/Comment.php`, `app/Models/PagesPost.php`, the `comments` field in a Meta webhook payload, or any part of `/comments` / `AiConfig::comment_settings`. Also use when diagnosing "comment didn't appear in /comments", "AI replied publicly but didn't DM" (or vice-versa), "comment reply shows pages_read_user_content error", "comment ingested on wrong team", "duplicate comment rows", or any question about which Meta OAuth scope + which graph host each comment-flow endpoint requires. Explains the full pipeline from Meta webhook → Comment row → public reply + DM, every decision node, the scope matrix (including the known-unresolvable `pages_manage_engagement` bundling bug), the three Graph-host rules, and the cross-platform (FB/IG via Meta/IG via Direct Login) differences. This skill is the deep-dive for public comments; the shallow map is `connections-architecture`, which routes you here when the touched code is comment-side.
---

# Comments Architecture — The Deep Dive

Public comments on FB posts and IG posts are the ONLY surface where OT1-Pro's AI speaks in the open — the inbox is private, the AI chat is internal. A failure here is always visible to the customer AND to anyone browsing the post. This skill exists because the pipeline has 8 moving parts, three independent Meta Graph hosts, and a scope-matrix that has flip-flopped five times in two months — mistakes here are both costly and non-obvious.

Scope: public comments only. DMs that originate from a comment ARE in scope (they're triggered from `SendAiCommentReplyJob`); DMs that originate from the inbox or AI chat are NOT.

---

## 1. The full pipeline

```
customer posts a public comment on an FB post or IG post
  │
  ▼
Meta webhook delivery to our server
  │  POST /api/webhooks/meta       (FB + IG-via-FB + WhatsApp)
  │  POST /api/webhooks/meta-ig    (IG Direct Login)
  │
  ▼
MetaWebhookController::handle
  │  • rate limit: 120/min/IP
  │  • HMAC signature check (per-app secret, legacy fallback)
  │  • writes a webhook_logs row
  │  • dispatches ProcessIncomingMessage onto `urgent` queue
  │
  ▼
ProcessIncomingMessage::processMetaMessenger
  │  foreach entry.changes[] → MetaCommentPayloadParser::parse($change)
  │  parser matches field=feed (FB) or field=comments (IG)
  │  if parsed → IngestCommentJob::dispatch($parsed, $pageId) on `comments-ingest`
  │
  ▼
IngestCommentJob::handle
  │  1. Dedupe: Cache::add("comments:seen:{platform_comment_id}", TTL=1d, store=redis)
  │  2. Page lookup: platform + (platform_page_id OR metadata.igsid OR metadata.igbid), is_active=true
  │  3. PostCreationTimeCache::resolve → PagesPost (used for 24h window gate)
  │  4. CommentFilterService::decide(page, aiConfig, post, parsed) → {decision, reason}
  │  5. Comment::create(...) ALWAYS — even filtered rows are stored for /comments visibility
  │  6. If decision === null → SendAiCommentReplyJob::dispatch($commentId) on `comments-send`
  │
  ▼
SendAiCommentReplyJob::handle
  │  • pre-filter: Team::canDispatchAi() (CLAUDE.md pin #4 — single AI gate)
  │  • reads AiConfig::comment_settings{reply_mode, dm_mode, …}
  │  • per-commenter DM cap: cache "comments:dm-per-commenter:{page}:{commenter}:{YYYY-MM-DD}"
  │  • generates PUBLIC reply text via NaraRouter (uses reply_instructions)
  │  • generates DM text separately (its OWN prompt; does NOT reuse reply_instructions)
  │  • attempts public reply → postPublicReply() → graphHostFor() + /comments or /replies
  │  • attempts DM → sendDm() → graphHostFor() + /{recipient}/messages with recipient.comment_id
  │  • sets decision = replied | dm_only | error_graph_api
  │  • charges AI credits post-success (idempotency key = comment id)
```

Three queues are involved. All must be running on prod (systemd services `one-inbox-queue@1..@4` cover them because they're not queue-pinned at the service level — they poll all queues).

---

## 2. Data model

```
Comment  (page_id, pages_post_id, platform_comment_id, parent_comment_id,
          commenter_platform_id, commenter_name, text, received_at,
          decision, decision_reason, reply_text, graph_reply_id, graph_error,
          dm_sent_at, dm_graph_message_id)
   │
   │ belongs to
   ▼
PagesPost (page_id, platform_post_id, created_at_platform, metadata{})
   │
   │ belongs to
   ▼
Page      (team_id, platform, platform_page_id, page_access_token, is_active, metadata{auth_type?, igsid?, igbid?, …})
```

Key invariants:
- **Every webhook writes a Comment row**, even when the AI chose not to reply. Operators need to see the AI's choices (DECISION_FILTERED_OFF etc.) on `/comments` — hiding filtered rows was a 2026-09 bug that made it look like webhooks were dropping. See `IngestCommentJob` step 5.
- `platform_comment_id` is **globally unique** on Meta's side AND is our dedupe key. The `Cache::add` on line 1 returns `false` if the key already exists, so retried webhooks (Meta re-delivers on 5xx) do not create duplicates.
- `PagesPost.created_at_platform` is used by `CommentFilterService` to enforce the 24h-after-post reply window — IG posts >24h old without `page-messaging` fallback would get `filtered_working_hours`. `PostCreationTimeCache::resolve()` uses `now()` as a fallback when the Graph fetch fails, so the row ALWAYS exists even if the timestamp is approximate.

---

## 3. The three Graph hosts (DO NOT CONFUSE)

| Platform | Page.platform | metadata.auth_type | Host for sends/replies | OAuth minted by |
|---|---|---|---|---|
| **FB** | `facebook` | (none) | `graph.facebook.com` | FB OAuth (main app) |
| **IG via FB** | `instagram` | (none) | `graph.facebook.com` | FB OAuth (main app) + Page token delegated to IG account |
| **IG Direct Login** | `instagram` | `instagram_business` | `graph.instagram.com` | IG OAuth (OT1 Direct Connect app) |

**The 2026-10-27 Brandk-style "Cannot parse access token" bug** happened because `SendAiCommentReplyJob::postPublicReply` and `::sendDm` hard-coded `https://graph.facebook.com/v21.0/` for both endpoints. Direct IG Login tokens are MINTED by graph.instagram.com's auth server; Facebook's Graph host literally cannot parse them and returns error code 190 "Cannot parse access token". Fixed in commit `c3637fe` by introducing `SendAiCommentReplyJob::graphHostFor($comment)` that reads `metadata.auth_type === 'instagram_business'` → graph.instagram.com, else graph.facebook.com. Same convention is used in `FacebookPlatform::fetchAndStoreMessages:1261`, `fetchConversationsPage:983`, `sendMessageToConversation:1122, 1235`. **If you add a new code path that sends to the Graph API for a page, you MUST switch on auth_type.** Add the pin to the existing scopes test if the path affects IG Direct Login specifically.

### Reply subpaths also differ

| Comment on | Public reply endpoint |
|---|---|
| FB | `POST /{comment-id}/comments` (nested comment thread) |
| IG (both flavours) | `POST /{comment-id}/replies` (IG's reply model) |

Mixing them returns HTTP 400 "(#100) Unsupported post request. Object with ID '…' does not exist, cannot be loaded due to missing permissions, or does not support this operation." See `SendAiCommentReplyJob::postPublicReply` for the `$isIg` branch.

---

## 4. Webhook subscription — the TWO layers + the field whitelist per app

Comment webhooks require THREE things lined up:

1. **App-level subscription:** `POST /{app-id}/subscriptions` includes the field (`feed` for FB, `comments` for IG). Not visible in UI — set via tinker. See `meta-webhook-two-layer-subscription` skill.
2. **Page-level subscription:** `POST /{page-id}/subscribed_apps?subscribed_fields=…` includes the field, using the page's own token. Written by `FacebookPlatform::subscribeFacebookPage`, `::subscribeInstagramPage`, `::subscribeInstagramViaFacebookPage`, and the one-shot `app:resubscribe-ig-comments` command.
3. **OAuth scope on the Page's access token:** the user granted the scope at connect time, otherwise the subscribe call 400s and silently kills OTHER fields in the same call.

### Current field strings (as of 2026-10-27)

| Platform | Code constant | Value | Where it's POSTed |
|---|---|---|---|
| FB page | `FB_SUBSCRIBED_FIELDS` | `messages,message_echoes,message_deliveries,message_reads,messaging_postbacks,feed` | `graph.facebook.com/{page-id}/subscribed_apps` |
| IG via FB | `IG_VIA_FB_SUBSCRIBED_FIELDS` | `messages,comments` | `graph.facebook.com/{igbid}/subscribed_apps` (uses parent FB Page token) |
| IG Direct Login | `IG_SUBSCRIBED_FIELDS` | `messages,comments` | `graph.instagram.com/{igbid}/subscribed_apps` (uses IG token) |

**`message_echoes` is Messenger-only.** Sending it to IG causes Meta to reject the whole `subscribed_apps` call with (#100). The 2026-09 incident: a dev added `message_echoes` to IG and killed all IG DMs for 25 days. IG's complete whitelist is documented at `IG_VIA_FB_SUBSCRIBED_FIELDS`'s docblock.

**IF ONE FIELD LACKS ITS SCOPE, META REJECTS THE WHOLE CALL** → every other field silently stops being subscribed. This bit us when `comments` was added to IG_SUBSCRIBED_FIELDS without first requesting `instagram_business_manage_comments` (2026-09-06 → 2026-10-01). The guard today: `IG_SUBSCRIBED_FIELDS`'s docblock explicitly says "add `comments` only together with the scope."

---

## 5. The OAuth scope matrix (which scope unlocks which comment capability)

### IG Direct Login (OT1 Direct Connect app)

| Capability | Scope | Requested? | Dev Console state (2026-10-27) |
|---|---|---|---|
| Subscribe `messages` webhook | `instagram_business_manage_messages` | ✅ | Standard Access |
| Subscribe `comments` webhook | `instagram_business_manage_comments` | ✅ (added 2026-10-27) | Standard Access (added this session) |
| Reply publicly to IG comment | `instagram_business_manage_comments` | ✅ | same as above |
| Reply via DM to IG commenter | `instagram_business_manage_messages` | ✅ | Standard Access |
| Read IG profile / media | `instagram_business_basic` | ✅ | Standard Access |

Fully working for tester accounts. Advanced Access via App Review needed for public users.

### IG via FB (main app)

| Capability | Scope | Requested? | Dev Console state (2026-10-27) |
|---|---|---|---|
| Subscribe `messages` webhook | `instagram_manage_messages` | ✅ | App Review REJECTED (works for testers) |
| Subscribe `comments` webhook | `instagram_manage_comments` | ✅ | App Review REJECTED (works for testers) |
| Reply publicly to IG comment | `instagram_manage_comments` | ✅ | same |
| Reply via DM to IG commenter | `instagram_manage_messages` | ✅ | same |
| Enumerate pages via Business | `business_management` | ✅ | Advanced Access |

Works for tester accounts; blocked for public users until rejection is addressed.

### FB (main app)

| Capability | Scope | Requested? | Status |
|---|---|---|---|
| Subscribe `feed` webhook (gets `comment` items) | `pages_manage_metadata` | ✅ | REJECTED (testers OK) |
| Receive messages | `pages_messaging` | ✅ | REJECTED (testers OK) |
| Read engagement (reactions, comments bodies) | `pages_read_engagement` | ✅ | REJECTED (testers OK) |
| Publish on Page's own feed | `pages_manage_posts` | ✅ | Standard |
| Reply publicly to a user's FB comment<br>(`POST /{comment-id}/comments`) | `pages_manage_engagement` | ✅ (re-added 2026-10-28) | Standard (works for testers) |
| Read user-generated content on a Page (dependency of `pages_manage_engagement`) | `pages_read_user_content` | ❌ not in OAuth, but MUST be in use case | Standard (added 2026-10-28 to unblock the OAuth validator) |
| DM a commenter (`POST /{page-id}/messages` with recipient.comment_id) | `pages_messaging` | ✅ | REJECTED (testers OK) |

Fully working for tester accounts as of 2026-10-28 after reconnecting pages. For public (non-tester) users, both `pages_manage_engagement` AND `pages_read_user_content` need App Review Advanced Access — submit as part of the main App Review push.

---

## 6. The `pages_manage_engagement` saga — RESOLVED 2026-10-28

**TL;DR:** `pages_manage_engagement` is required for `POST /{comment-id}/comments` (public reply to a user's FB comment). We've requested → removed → re-requested → removed it four times over two months because every re-add broke OAuth with "Invalid Scopes: pages_read_user_content". The root cause was explained by Meta on 2026-10-28 (ticket 2162436254672944): the OAuth validator fails when any requested scope's internal dependency is missing from the app. `pages_manage_engagement` depends on `pages_read_user_content`. Adding `pages_read_user_content` to the Pages API use case (Standard Access auto-grants on "Add to App Review" click) unblocks the OAuth validator. Both scopes are now in the OAuth URL as of commit after `f9b99ff`.

### Full attempt history (keep this for the next time someone considers removing the scope)

| Commit | Date | Action | Outcome |
|---|---|---|---|
| `ea12a17` | 2026-09 | Originally removed | — |
| `cc3cfc0` | later | Re-added "config fixed on Meta side" | OAuth broken |
| `f692bda` | right after | Reverted the re-add | Restored |
| `7137fbf` | 2026-10-25 | Swapped to `pages_manage_posts` on the hypothesis that it covers the same endpoint | Public FB replies silently break — `pages_manage_posts` is for the Page's own feed, NOT replies to users' comments |
| `49b8815` | 2026-10-27 | Re-add after Dev Console audit showed clean row | OAuth broken AGAIN — Arabic error page "هذا المحتوى غير متوفر الآن" + "Invalid Scopes: pages_read_user_content" |
| `6b75c62` | 2026-10-27 | Revert | Restored |
| (ticket submitted via browser) | 2026-10-27 | Meta bug report `2162436254672944` | Meta responded next day explaining the dependency |
| current | 2026-10-28 | Added `pages_read_user_content` to the Pages API use case → Meta auto-granted Standard Access → verified consent screen loads cleanly in-browser → re-added `pages_manage_engagement` to the OAuth URL | WORKING |

### The permanent lesson (if someone proposes removing or re-swapping the scope again)

- The Dev Console Permissions UI does NOT show OAuth-validator dependencies. The only way to prove a scope works is a LIVE OAuth attempt in a browser.
- `pages_manage_posts` is NOT a valid substitute for `pages_manage_engagement` for `POST /{comment-id}/comments` — it covers only the Page's own feed publishing.
- `POST /{post-id}/comments` (publishing a new top-level comment on the Page's own post) ALSO fails without `pages_manage_engagement`, despite docs suggesting `pages_manage_posts` covers it. Tested in prod `32e6fd5` → `6e82df9`.
- `pages_read_user_content` MUST stay in the Pages API use case on developers.facebook.com. If anyone removes it there, OAuth breaks again with the exact same "Invalid Scopes" error. There's no code visibility to catch that drift — only a live OAuth test does.

Pin: `tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php` asserts BOTH `pages_manage_engagement` AND `pages_manage_posts` are present in both FB OAuth URLs.

---

## 7. AiConfig::comment_settings — the per-page knob set

Lives at `AiConfig::comment_settings` (JSON on the `ai_configs` row for a page). Defaults in `AiConfig::defaultCommentSettings()`.

```
enabled                             : bool
reply_mode                          : off | all | questions_and_complaints | custom_keywords
reply_keywords                      : string[]              (used only when reply_mode=custom_keywords)
reply_instructions                  : string                (verbatim injected into the PUBLIC reply prompt)
dm_mode                             : off | always | on_purchase_intent
dm_keywords                         : string[]              (used only when dm_mode=on_purchase_intent)
max_dms_per_commenter_per_day       : int [1..10]           (per-commenter DM cap)
max_ai_replies_per_post_per_day     : int                   (per-POST public reply cap; default 20)
```

**Why two separate prompts (`reply_instructions` for public, nothing analogous for DM):**
`reply_instructions` sometimes says "always say check your DM" — that sentence makes no sense INSIDE a DM. So `SendAiCommentReplyJob::buildDmPrompt` deliberately does NOT inject `reply_instructions`. Instead the DM prompt uses the last few customer messages (via `recentCommenterMessages`) as a language + tone anchor. See the docblock on `buildDmPrompt` for the full language-cascade rules.

### UI edit path

`/settings/ai/config` → the "Comments" section renders a per-page editor using `AiConfig::defaultCommentSettings()` as the baseline. When Omar flipped "Always DM after replying + reply to all comments" on OWOWOKWKSJF in this session, those map to `dm_mode='always'` + `reply_mode='all'` in the stored JSON.

---

## 8. The Comment.decision enum — what each value means on /comments

| Constant | UI chip | Set by | When |
|---|---|---|---|
| `DECISION_REPLIED` | Public reply success | `SendAiCommentReplyJob` | Public reply graph call returned 200 |
| `DECISION_DM_ONLY` | DM only (no public reply) | `SendAiCommentReplyJob` OR `CommentFilterService` | DM succeeded while public was blocked, OR filter decided DM-only ahead of time |
| `DECISION_FILTERED_OFF` | Filtered — AI off | `SendAiCommentReplyJob` early-exit | `reply_mode=off` AND `dm_mode=off` |
| `DECISION_FILTERED_MODE` | Filtered — mode | `CommentFilterService` | reply_mode didn't match (e.g. mode=questions_and_complaints, comment is a compliment) |
| `DECISION_FILTERED_SCOPE` | Filtered — scope | `CommentFilterService` | Post is too old (>24h, outside messaging window) |
| `DECISION_FILTERED_KEYWORD` | Filtered — keyword | `CommentFilterService` | reply_mode=custom_keywords and no match |
| `DECISION_FILTERED_WORKING_HOURS` | Filtered — hours | `CommentFilterService` | Current time outside the page's working hours |
| `DECISION_FILTERED_SELF` | Filtered — self | `CommentFilterService` | Commenter IS the page itself (we commented on our own post) |
| `DECISION_FILTERED_REPLY` | Filtered — reply | `CommentFilterService` | Comment is itself a reply to another comment (we only engage with top-level) |
| `DECISION_RATE_LIMITED` | Rate limited | `CommentFilterService` | Per-post reply cap hit for today |
| `DECISION_ERROR_GRAPH_API` | Error — Meta Graph API | `SendAiCommentReplyJob` | Both public reply AND DM failed with 4xx+; retryable on 429/5xx (job throws) |
| `DECISION_ERROR_AI` | Error — AI | `SendAiCommentReplyJob` | NaraRouter returned empty string (CLAUDE.md pin #5 — no fallback copy) |

`decision=null` is a transient state meaning "not yet decided, SendAiCommentReplyJob will fill it in." Only queried by the job itself (as an idempotency guard — if re-dispatched after already being filled, it no-ops).

---

## 9. Dedupe + rate limiting — the hot-path caches

Three independent caches, all with their own purposes. All default to the `COMMENTS_HOT_CACHE_STORE` driver (prod: redis, test: array). Reads/writes bypass CACHE_STORE because the hot-path needs sub-millisecond latency.

| Cache key | TTL | Purpose | Set by | Read by |
|---|---|---|---|---|
| `comments:seen:{platform_comment_id}` | 1 day | Dedupe retried webhooks | `IngestCommentJob::handle` step 1 | — (only `Cache::add` which is atomic SET-NX) |
| `comments:dm-per-commenter:{page}:{commenter}:{YYYY-MM-DD}` | 1 day | Per-commenter DM cap (one chatty person on 5 posts shouldn't get 5 DMs) | `SendAiCommentReplyJob::handle` DM-cap block | increment on each attempt |
| `comments:reply-per-post:{page}:{post}:{YYYY-MM-DD}` | 1 day | Per-post public reply cap (`max_ai_replies_per_post_per_day`) | `CommentFilterService::decide` | increment on each attempt |

If prod Redis is unavailable, the hot-path falls back to database cache — slower but correct. The hot-store override exists so the dedupe lookup doesn't hit the DB on every webhook (which at peak was 400+/min on the Mishkah import day).

---

## 10. Common failure modes + where to look

| Symptom | Most likely cause | Where to look |
|---|---|---|
| Comment posted on IG, nothing in `/comments` | Subscription missing — either page not subscribed to `comments` OR scope missing on token | `/super-admin/pages/{id}/diagnose` → `meta.page_subscribed_apps` includes `comments`? Scopes include the comment scope for the account's flow? |
| Comment ingests but public reply fails with "Cannot parse access token" (code 190) | Direct IG Login page using graph.facebook.com by mistake | `SendAiCommentReplyJob::graphHostFor` is the branch point. If a NEW code path sends to Graph for a page, it MUST switch on `metadata.auth_type` |
| FB comment reply fails "pages_read_user_content not available" (code 200) | Known. `pages_manage_engagement` bundling bug. Not fixable by scope changes | §6 above. Do NOT try to re-add the scope |
| Comment appears on the wrong team's `/comments` | Page lookup picked the wrong row — multiple active rows for same `(platform, platform_page_id)` | ARCHITECTURE §2 single-active invariant. Check `page_transfers` and `Page::booted()` observer |
| Duplicate comment rows | Dedupe cache store is wrong — `comments.hot_cache_store` set to a store without atomic SET-NX | `config/comments.php`, must be redis or database. Array store in prod would cause this |
| AI replied publicly but didn't DM | Three possibilities, in order: (a) `dm_mode=off`; (b) per-commenter cap hit (`comments:dm-per-commenter:*`); (c) DM graph call failed and was logged with "SendAiCommentReplyJob: DM failed" | laravel.log + the `graph_error` JSON on the Comment row |
| AI replied as DM but public reply "failed" — but the user actually sees NO public reply on Meta | FB scope bundling bug. Expected for FB pages today — see §6 |
| Comment ingested but AI never attempted anything | AI config disabled OR team can't dispatch — see `Team::canDispatchAi()` (CLAUDE.md pin #4) |
| Spam folder catches DMs sent via comment-triggered path | **Known on IG** (user saw it 2026-10-27). Not fixable from our side — Meta's spam heuristics look at new-conversation / short-text / fast-send. Educate the user to check "Message Requests" filter in IG DMs |

---

## 11. Testing — what each test file pins

- `tests/Feature/Jobs/IngestCommentJobTest.php` — dedupe, page resolution, filter decisions, Comment row creation.
- `tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php` — parser for both FB `field=feed` and IG `field=comments` shapes, parent_id vs post_id disambiguation.
- `tests/Feature/Jobs/SendAiCommentReplyJobTest.php` — including the regression pin "it routes Direct IG Login comment replies to graph.instagram.com, not graph.facebook.com" (added commit `c3637fe`).
- `tests/Feature/Jobs/ProcessIncomingMessageCommentsTest.php` — end-to-end from webhook payload to Comment row.
- `tests/Feature/Comments/IndexTest.php` — `/comments` team-scoping, chip filters.
- `tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php` — pins `pages_manage_engagement` as **FORBIDDEN** in both FB OAuth URLs (see §6).
- `tests/Feature/Connections/InstagramDirectLoginTest.php` — pins `instagram_business_manage_comments` IS in the Direct IG Login OAuth URL AND that `IG_SUBSCRIBED_FIELDS` is `messages,comments`.

If you add a code path that affects any of (a) scope strings, (b) subscribed_fields, (c) Graph host selection, (d) decision enum, (e) `/comments` team-scoping — add a pin here. The scope matrix flip-flops because old assumptions aren't locked in tests.

---

## 12. Dev Console checklist (where things live for human eyes)

| Thing | URL |
|---|---|
| OT1 Direct Connect app | https://developers.facebook.com/apps/2908423109505861/ |
| OT1 Direct Connect — Instagram use case permissions | https://developers.facebook.com/apps/2908423109505861/use_cases/customize/permissions/?use_case_enum=INSTAGRAM_BUSINESS |
| One Inbox Business app | https://developers.facebook.com/apps/1469090344742803/ |
| One Inbox Business — FB Messaging permissions | https://developers.facebook.com/apps/1469090344742803/use_cases/customize/permissions/?use_case_enum=FACEBOOK_MESSAGING |
| One Inbox Business — IG permissions | https://developers.facebook.com/apps/1469090344742803/use_cases/customize/permissions/?use_case_enum=INSTAGRAM_BUSINESS |
| One Inbox Business — Pages permissions | https://developers.facebook.com/apps/1469090344742803/use_cases/customize/?use_case_enum=PAGES_API&selected_tab=permissions |
| Snapshot audit of all of the above | `tasks/meta-app-permissions-snapshot.md` |

**Reminder on reading the Dev Console status column:**
- `جاهز للاختبار` = Standard Access (testers only; sufficient for Omar's dev)
- `جاهز للنشر` = Advanced Access (live for all users)
- `تم رفض مراجعة التطبيقات` = App Review Rejected (testers still work; public blocked)
- Empty / "إضافة" button = NOT ADDED to the use case (OAuth with this scope → "Invalid Scopes")

The Dev Console does NOT reveal OAuth-validator bundling. Only a live OAuth attempt proves whether a scope is actually requestable (§6).

---

## 13. Related skills + files

Shallower maps:
- `connections-architecture` — the map that routes you here when the touched code is comment-side.
- `platform-messaging-testing` — overlapping diagnostic patterns for the whole inbound pipeline, with references/instagram.md + references/facebook-messenger.md.
- `meta-webhook-two-layer-subscription` — THE deep dive on app-level vs page-level subscriptions.
- `meta-business-page-visibility` — only tangentially relevant (page enumeration, not comment flow).

Code anchors:
- `app/Jobs/IngestCommentJob.php`
- `app/Jobs/SendAiCommentReplyJob.php`
- `app/Services/Comments/{MetaCommentPayloadParser,CommentFilterService,PostCreationTimeCache}.php`
- `app/Livewire/Comments/Index.php` + `resources/views/livewire/comments/index.blade.php`
- `app/Models/{Comment,PagesPost,AiConfig}.php`
- `app/Services/Platforms/FacebookPlatform.php` (`IG_SUBSCRIBED_FIELDS`, `IG_VIA_FB_SUBSCRIBED_FIELDS`, `getConnectUrl`, `getInstagramConnectUrl`, `getInstagramViaFacebookConnectUrl`, `subscribeFacebookPage`, `subscribeInstagramPage`)
- `app/Console/Commands/ResubscribeInstagramCommentsWebhook.php` — one-shot operational command

Doc anchors:
- `tasks/meta-app-permissions-snapshot.md` — captured 2026-10-27
- `tasks/meta-pages-manage-engagement-blocker.md` — the open-blocker file
- `docs/superpowers/plans/2026-08-31-comments-ai-phase-b.md` + `docs/superpowers/specs/2026-08-31-comments-ai-phase-b-design.md` — original design docs
