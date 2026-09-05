# Meta App Review: `pages_manage_engagement` + `instagram_manage_comments`

**Purpose:** Get Advanced Access on Meta for these two permissions so future self-serve OAuth (post managed-onboarding phase) works for every customer, not only those inside our Business Portfolio.

**Status when this doc lands in prod:** feature ships live to existing customers **before** approval, because managed OAuth places every real Page inside our verified Business Portfolio (OT1 Pro, ID `2169075923895403`), where Standard Access is sufficient. Submission is parallel/future-proofing.

**Who does what:**
- **Claude (this doc):** drafts submission text, reviewer test-user script, screencast script, and the exact developer.facebook.com console click path.
- **Omar (human):** logs into developers.facebook.com/apps/1469090344742803, pastes the text, uploads the screencast, submits.
- **When submission is confirmed:** Omar tells me "submitted"; I mark Task 8 complete in the Phase B plan.

---

## 1. Overview (paste into every submission)

OT1-Pro is a unified inbox for small businesses that ties Facebook, Instagram, WhatsApp, Telegram, and email into one AI-assisted response system. Businesses connect their Facebook Page and/or Instagram Business account, configure how the AI should talk to customers, and let the AI handle first-touch replies during and after working hours. The business owner reviews AI activity in a dashboard and takes over any conversation the AI escalates.

This submission unlocks the comment-reply feature. Businesses opt in per Page via a **Comments** tab in the AI Configuration page. When a customer comments on a Page's post, our system reads the comment, optionally classifies it (question, complaint, or neither), and replies publicly on behalf of the business. Optionally, we open a one-shot direct message with the commenter to move the conversation off the public thread — a pattern Meta itself documents as a best practice for merchant sales flow.

We do NOT hide, delete, or moderate comments. We do NOT modify posts. We do NOT read comments from Pages the customer has not connected and enabled the feature on.

---

## 2. `pages_manage_engagement` — submission text

**Use case:** Public reply to customer comments on a Facebook Page.

**Why we need it:** When a customer comments on a business's Facebook Page post, our system publishes an AI-generated reply below the comment. Without `pages_manage_engagement`, the Graph API returns a permission error and we cannot post the reply. The reply is written on behalf of the connected Page (not the business owner personally) and is opt-in per Page from the business's OT1-Pro settings screen.

**How it works step by step:**

1. Business owner connects their Facebook Page via OT1-Pro's OAuth flow.
2. In OT1-Pro's AI Configuration page → Comments tab, they enable "Comment AI" and pick a reply mode: All comments, Questions & complaints only, Custom keywords, or Off.
3. When a customer comments on any of the Page's posts, Meta sends us a `feed` webhook with `item=comment`.
4. Our system checks the business's config, may run a lightweight question/complaint classifier, then generates a reply using the business-supplied tone, language, and business description.
5. We POST the reply to `/{comment-id}/comments` — the exact endpoint `pages_manage_engagement` authorizes.
6. The reply appears publicly under the customer's comment.

**Volume & rate limits:** Businesses set a per-post daily cap (default 20, max 100 replies per post per 24 hours). The system enforces this cap in code via a Redis-backed counter to protect Meta's rate limits and the business's own AI budget.

**Data we do NOT touch with this permission:** comment moderation (hide, delete, ban), post edits, page-level engagement stats, Reels comments (planned for a later phase).

---

## 3. `instagram_manage_comments` — submission text

**Use case:** Public reply to customer comments on Instagram Business posts.

**Why we need it:** Identical to `pages_manage_engagement` but for Instagram. When a customer comments on a business's Instagram post, our system publishes an AI-generated reply. Without `instagram_manage_comments`, the Graph API returns a permission error.

**How it works step by step:**

1. Business owner connects their Instagram Business account (linked to a Facebook Page) via OT1-Pro's OAuth flow.
2. Same Comments tab in AI Configuration.
3. When a customer comments on any of the IG account's media, Meta sends us a `comments` webhook.
4. Same classification + reply-generation pipeline.
5. We POST the reply to `/{ig-comment-id}/comments` — the endpoint `instagram_manage_comments` authorizes.
6. The reply appears publicly on Instagram.

**Volume & rate limits:** Same per-post daily cap as Facebook. Same code path — same Redis counter enforcing the limit.

**Data we do NOT touch:** comment moderation, post edits, Insights, DM inbox (that's a separate permission we already have via `instagram_manage_messages`).

---

## 4. Private-message-via-comment-id (uses existing permissions)

Both permissions above also enable a follow-on private message via Meta's "Private Reply" endpoint:
- FB: `POST /{page-id}/messages` with `recipient={comment_id: X}` — 24-hour window
- IG: `POST /{ig-user-id}/messages` with `recipient={comment_id: X}` — 7-day window

Sending the DM itself uses `pages_messaging` (FB) and `instagram_manage_messages` (IG) which we already have Advanced Access for. The comment permissions are the gate for the comment side; message permissions are the gate for the DM side. Both must be present.

---

## 5. Reviewer test-user script

Meta App Review requires a login the reviewer can use to test the feature. Use the test users created in **Meta Developer Console → App → Roles → Test Users** (or the App Review test account form).

**Setup Omar does before submitting:**

1. Create/reuse a Meta test user.
2. That test user gets promoted to **Admin** on a test Facebook Page (created via the Test Users interface, not a real Page).
3. Ensure the same test user has an Instagram Business account linked to the FB Page.
4. In OT1-Pro, create an OT1-Pro user account with credentials Omar shares in the submission form. Attach the reviewer's test FB Page via the OAuth flow using the reviewer's test-user login. Enable the Comments tab.

**Reviewer steps:**

1. Log in to https://ot1-pro.com with credentials (provided in submission form).
2. Navigate to `/settings/ai/config`. Confirm the Comments tab is present for the connected Facebook and Instagram pages.
3. Confirm the master switch is ON and reply mode is "All comments" for testing.
4. Log in to Facebook as the test user; post a public comment ("Reviewer test: what's your price?") on any post on the connected Page.
5. Wait up to 30 seconds. Refresh the comment thread; confirm the AI has replied publicly with a business-appropriate response.
6. Repeat the same test on the Instagram side by commenting on an Instagram post.
7. (Optional) Configure DM mode → "Always DM after replying." Repeat step 4; confirm a private message from the Page appears in the test user's Messenger inbox.

---

## 6. Screencast script (30-90 seconds per permission, two screencasts)

Record on a clean browser session, incognito, using the reviewer test-user credentials. Voiceover optional; on-screen captions preferred.

### Screencast A: `pages_manage_engagement` (target 60s)

- 0-5s: caption "OT1-Pro Comments feature — Facebook"
- 5-15s: show `/settings/ai/config` Comments tab. Click through the reply-mode cards showing "All comments" selected. Save.
- 15-25s: cut to Facebook, show a public post on the connected Page. Post a comment as the test user: "How much is the standard plan?"
- 25-45s: cut back to the OT1-Pro comments log (or the Facebook thread refreshed after 30s). Show the AI reply appearing publicly under the customer's comment.
- 45-55s: caption "AI reply generated by OT1-Pro on behalf of the business owner. Reply text: [show text]"
- 55-60s: caption "pages_manage_engagement — reply to comments on the business's own Page"

### Screencast B: `instagram_manage_comments` (target 60s)

Same shape but on Instagram. Reference an Instagram post, comment there, show the reply appearing under the IG comment.

---

## 7. Submission checklist for developers.facebook.com

1. Log in to https://developers.facebook.com/apps/1469090344742803
2. Left sidebar → **App Review** → **Permissions and Features**
3. Search "pages_manage_engagement"
4. Click "Request Advanced Access" on that permission row
5. Fill the submission form:
   - **Purpose:** copy §2 above
   - **Screencast:** upload Screencast A (mp4, <100MB)
   - **Test user credentials:** paste the OT1-Pro login (created per §5)
   - **Reviewer instructions:** paste §5 above
6. Submit
7. Repeat steps 3-6 for "instagram_manage_comments" with §3 and Screencast B
8. Also submit for App Review if not already: **Business Verification** should already be complete (per CLAUDE.md pin #1); if not, do it before the permission requests
9. Note the submission timestamp somewhere — Meta typically responds in 5-10 business days

---

## 8. When approval lands

1. Meta emails Omar. The **Permissions and Features** page shows Advanced Access on both permissions.
2. No code change required — OAuth already requests these scopes since Phase B (see `FacebookPlatform::getInstagramViaFacebookConnectUrl` line 70).
3. Any future self-serve customer (not routed through managed onboarding) can now connect and use the comment feature directly, without needing Omar to OAuth on their behalf.
4. Delete the "coming soon" banner in the Comments tab (Phase A) — the feature is no longer a soft-launch. See `resources/views/livewire/settings/ai-config.blade.php` for the banner.

---

## 9. If Meta rejects

Common rejection reasons and mitigations:

- **"Feature is not clearly explained"** → the reviewer probably didn't get through steps 1-6 of §5. Add a walkthrough video URL to the submission text, re-record Screencast A with slower pacing.
- **"You don't seem to need this permission"** → the reviewer thinks a lower-privilege permission would work. Reply with a specific Graph API error message (`{"error":{"code":200,"message":"Missing permission pages_manage_engagement"}}`) captured from a test call.
- **"Screencast doesn't show the permission being used"** → re-record showing the network tab / Graph API call fingerprint, or add captions naming the endpoint.

The submission form has a "Reply" option; use it once. If rejected twice, wait 30 days per Meta policy before re-submitting the same request.
