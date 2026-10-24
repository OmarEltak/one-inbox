# Meta `pages_manage_engagement` blocker — findings + state

Last updated: 2026-10-19 by Claude Code session.

## The goal

Enable the AI to **publicly reply to Facebook comments on connected business Pages' posts**. Currently 100% of public comment replies in production return HTTP 403 `(#200) The permission(s) pages_read_user_content are not available`. DMs still work; only the public comment-reply fails.

End state we need:
1. `pages_manage_engagement` scope added back to our OAuth scope request.
2. Users reconnect their Pages so Meta grants the scope on the new token.
3. `SendAiCommentReplyJob`'s Graph API call `POST /{comment_id}/comments` succeeds instead of 403.

---

## The problem (history)

**2026-09-07 incident:** developer tried requesting `pages_manage_engagement` in OAuth. Every OAuth attempt was rejected by Meta with:

```
Invalid Scopes: pages_read_user_content
```

The developer never requested `pages_read_user_content` directly (it's a scope Meta deprecated in 2024). The theory in the code comment at `FacebookPlatform.php:83-87`:

> "it triggered Meta to match a Facebook Login for Business config that includes the deprecated pages_read_user_content scope, and Meta rejected the whole request"

So `pages_manage_engagement` was **removed from the scope list** as a temporary workaround, with the note: "Re-add ONLY after the (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) config on the Meta Dev Console is cleaned of pages_read_user_content."

That workaround is still in effect today.

---

## What our code actually sends (as of this writing)

`FacebookPlatform::getConnectUrl()` at `app/Services/Platforms/FacebookPlatform.php:79-95`:

```
https://www.facebook.com/v21.0/dialog/oauth?
  client_id={APP_ID}&
  redirect_uri=https://ot1-pro.com/connections/facebook/callback&
  scope=pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,business_management&
  response_type=code&
  state={CSRF_TOKEN}
```

**Key fact:** our code uses **classic Facebook Login** (no `config_id` parameter). Not Facebook Login for Business ((IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB)). This matters — see validation section below.

`FacebookPlatform::getInstagramViaFacebookConnectUrl()` at line 103-120 is the same pattern but adds `instagram_basic, instagram_manage_messages, instagram_manage_comments`.

---

## Dev Console audit (completed 2026-10-19)

App ID: `1469090344742803` — "One Inbox Business" — LIVE mode.

### Scopes currently granted to prod tokens (confirmed via `/debug_token` probe)

```
pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement,
pages_utility_messaging, business_management,
instagram_basic, instagram_manage_comments, instagram_manage_messages,
whatsapp_business_management, whatsapp_business_messaging,
public_profile, email
```

### Granular scopes (per-target)

```
pages_messaging:        4 FB page IDs
pages_read_engagement:  4 FB page IDs
pages_manage_metadata:  4 FB page IDs
instagram_manage_comments:  1 IG account (17841435059805096)
instagram_manage_messages:  1 IG account (17841435059805096)
```

### Use Cases audit — ALL CLEAN

Confirmed via Dev Console:
- Pages API — `pages_read_user_content` was present, **removed 2026-10-19** ✓
- Messenger from Meta — no `pages_read_user_content` ✓
- Instagram API — no `pages_read_user_content` ✓
- WhatsApp — no `pages_read_user_content` ✓

### (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) config "One Inbox Messaging" (`config_id=896835833159946`)

Current scope list:

```
business_management
instagram_basic
instagram_manage_comments
instagram_manage_messages
pages_manage_engagement       ← present
pages_manage_metadata
pages_messaging
pages_read_engagement
pages_show_list
pages_utility_messaging
```

**`pages_manage_engagement` is already in the (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) config.** `instagram_business_manage_comments` is NOT in this config but we don't need it right now (the older `instagram_manage_comments` covers us).

### Classic Login settings (app-wide)

- **Valid OAuth Redirect URIs** (whitelisted):
  - `https://one-inbox.test/connections/instagram/callback`
  - `https://one-inbox.test/connections/facebook/callback`
  - `https://ot1-pro.com/connections/facebook/callback`
  - `https://ot1-pro.com/connections/instagram/callback`
  - `https://ot1-pro.com/connections/instagram-via-facebook/callback`
- **Strict mode for redirect URIs**: ON (so exact-match required — all our prod URIs match exactly)
- **Enforce HTTPS**: ON
- **JavaScript SDK allowed domains**: `https://ot1-pro.com/`

### Flagged — needs separate consultant check

Two scopes show status `تم رفض` ("rejected") in the Pages API use case despite active prod usage:
- `pages_manage_metadata` — تم رفض, k8.4 usage
- `pages_read_engagement` — تم رفض, k10 usage

Possibly a specific past App Review submission was rejected while Standard Access continues to work. Not blocking today but could become a problem — ask consultant: "What does تم رفض status mean exactly, do these risk being revoked, and if so when do we need to resubmit?"

---

## Prod production comment-reply failure data (from Phase 0 audit, 2026-10-19)

- Total `comments` rows in prod: 4 (all from 2026-09-08/09 — small sample because the feature was effectively broken shortly after launch)
- `decision = replied`: 0
- `decision = dm_only`: 3
- `decision = error_graph_api`: 1
- `graph_reply_id IS NOT NULL`: 0 (zero public replies ever posted)
- `dm_sent_at IS NOT NULL`: 3 (DMs working)
- `graph_error IS NOT NULL`: 4 (all)
- Error text, 4/4 occurrences: `(#200) The permission(s) pages_read_user_content are not available`

Log evidence (verbatim):
```
[2026-09-08 23:26:09] SendAiCommentReplyJob: public reply failed
  {"comment_id":4,"status":403,"error":{"message":"(#200) The permission(s) pages_read_user_content are not available..."}}
[2026-09-09 00:14:50] ... comment_id 5 — same error
[2026-09-09 00:18:40] ... comment_id 6 — same error
[2026-09-09 03:00:19] ... comment_id 7 — same error
```

No recent errors — the feature stopped being invoked after the workaround (removing `pages_manage_engagement` from OAuth) was applied.

---

## OAuth URL validation tests run 2026-10-19 (all failed, all were test URL construction issues)

### Test #1 — with `config_id` + full scope list including `email`/`public_profile`

```
.../dialog/oauth?client_id=1469090344742803&redirect_uri=https://ot1-pro.com/connections/facebook/callback&scope=email,public_profile,pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_manage_engagement,business_management&response_type=code&state=flfb-test&config_id=896835833159946
```

Result: "Sorry, something went wrong." Not the deprecated-scope error — a different generic error.

### Test #2 — with `config_id` + business-only scopes (no `email`/`public_profile`)

```
.../dialog/oauth?client_id=1469090344742803&redirect_uri=https://ot1-pro.com/connections/facebook/callback&scope=business_management,pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_manage_engagement&response_type=code&state=flfb-test-v2&config_id=896835833159946
```

Result: same generic "Sorry, something went wrong."

### Why both failed

Our prod code does NOT use `config_id`. Both tests above targeted the (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) config directly, but the prod OAuth flow is classic Facebook Login. The (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) test URLs likely failed due to a parameter requirement specific to (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) flows (not scope-related). This diagnostic path was a dead end for our actual use case.

### Test #3 — mirrors prod code (NO `config_id`, with `pages_manage_engagement` added)

```
https://www.facebook.com/v21.0/dialog/oauth?client_id=1469090344742803&redirect_uri=https%3A%2F%2Fot1-pro.com%2Fconnections%2Ffacebook%2Fcallback&scope=pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_manage_engagement,business_management&response_type=code&state=prod-mirror-test-2026-10-19
```

**STATUS: FAILED 2026-10-19.** Error message in logged-in developer browser:

```
هذا المحتوى غير متوفر الآن
Invalid Scopes: pages_read_user_content. This message is only shown to developers.
Users of your app will ignore these permissions if present. Please read the
documentation for valid permissions at:
https://developers.facebook.com/docs/facebook-login/permissions
```

Meta's error says users would silently ignore the deprecated scope — meaning for a real user the OAuth would proceed but **without `pages_manage_engagement` granted**, so the Graph API call to post the comment reply would still 403. End-user behavior = identical to today's broken state.

**Diagnosis:** removing `pages_read_user_content` from the Pages API use case was necessary but not sufficient. Meta is still matching `pages_manage_engagement` against the deprecated scope — the reference must be in another place in the Dev Console we haven't audited.

### Not-yet-audited locations where `pages_read_user_content` might still be referenced

1. **App Review → Permissions and Features page** — the historical list of all scopes ever requested/approved for this app, with current status per scope. Likely culprit — it may still show `pages_read_user_content` as an "approved" or "live" permission from a pre-deprecation App Review that was never explicitly withdrawn.

2. **App Review → Submissions** — list of past App Review submissions. If any past submission mentioned `pages_read_user_content` and is still in the submission history as "approved", Meta may be using that as evidence the app "has" the scope.

3. **Products panel** → remove the "Facebook Login" product entirely and re-add it fresh (destructive — may invalidate existing tokens, don't do this without a backup/rollback plan).

4. **Meta Developer Support ticket** — the error enforcement likely lives in Meta's internal scope-matching logic that isn't exposed on the Dev Console. A support ticket citing this exact error + screenshots of the clean use cases + the failing OAuth URL is likely needed.

5. **(IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) configs OTHER than "One Inbox Messaging"** — audit all (IGNORE: FLfB was a wrong diagnosis — this app uses classic FB Login, not FLfB) configs on the app, not just the primary one.

---

## The three steps to green (updated)

| # | Who | What | Status |
|---|---|---|---|
| 1 | Consultant / claude.ai on Dev Console | Remove `pages_read_user_content` from Pages API use case | ✅ DONE 2026-10-19 |
| 1b | Consultant / Meta Support | Find and remove remaining `pages_read_user_content` references on the Dev Console (see "Not-yet-audited locations" above). If audit comes up empty, open a Meta Developer Support ticket. | ⏳ **CURRENT BLOCKER** |
| 2 | User | Retest URL #3 after step 1b, confirm consent dialog loads cleanly | ⏳ BLOCKED on #1b |
| 3 | Me (Claude Code) | Edit `FacebookPlatform.php:92` and line 116 to add `pages_manage_engagement` to the scope string. Deploy. Takes ~5 minutes end-to-end. | ⏳ BLOCKED on #2 |
| 4 | User | Reconnect your pages in-app so OAuth re-runs with the new scope list | ⏳ After #3 |
| 5 | User + consultant | Submit App Review for `pages_manage_engagement` so real customers can use it (not just tester-role accounts) | ⏳ After #4 |

---

## Rollback plan if step 3 breaks something

Step 3 is a one-string change to two files:
- `FacebookPlatform.php:92`: append `,pages_manage_engagement` to the scope string
- `FacebookPlatform.php:116`: same append

If deployment causes OAuth to break (e.g. "Invalid Scopes" returns), revert that one commit and redeploy. Impact of a broken deploy: new OAuth attempts fail for the duration of the breakage. Existing connected pages are unaffected. No data loss.

Rollback command:
```
ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && sudo -u deploy git revert --no-edit HEAD && sudo -u deploy git push origin main"
# auto-deploys in ~30s
```

Also updates:
- `FacebookPlatformScopesTest.php:10-19` has a test that INTENTIONALLY asserts `pages_manage_engagement` is NOT in the scope list. That test will need to be updated / removed as part of step 3.

---

## Related files

- `app/Services/Platforms/FacebookPlatform.php` — OAuth URL construction, scope list
- `tests/Unit/FacebookPlatformScopesTest.php` — enforces the current scope list (will need updating)
- `app/Jobs/SendAiCommentReplyJob.php` — the job that calls `POST /{comment_id}/comments` which 403s today
- `app/Models/Comment.php` — row stores `graph_reply_id`, `graph_error`, `dm_graph_message_id`, `decision`
- `database/migrations/2026_09_01_100100_create_comments_table.php` — schema
- `.claude/skills/connections-architecture/SKILL.md` — full Meta OAuth architecture reference

---

## Permissions still needed from Meta (for Advanced Access via App Review)

Current approved (per screenshot shared 2026-10-19 EEST):
`email, public_profile, pages_show_list, business_management, instagram_business_basic, instagram_business_manage_messages, whatsapp_business_messaging, whatsapp_business_management`

Blocked by step 1 above (which is now done, so these can be re-attempted):
- `pages_manage_engagement` — public comment replies on FB posts
- `instagram_business_manage_comments` — direct IG Business Login comment replies (nice-to-have; `instagram_manage_comments` already granted at Standard Access covers us for FB-mediated IG flow)

Scopes to re-verify granted (consultant question):
- `pages_manage_metadata`, `pages_read_engagement` — show `تم رفض` status in Pages API use case despite active prod usage
