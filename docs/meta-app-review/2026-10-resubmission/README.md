# Meta App Review — October 2026 resubmission (app `1469090344742803`)

Everything needed to resubmit after the **21 July 2026** result (approved: `public_profile`
only; 11 permissions rejected under Policy 1.6 "screencast doesn't show the end-to-end use
case"). Built for two operators: **Omar** (records videos, final Submit click) and a
**Claude-in-Chrome session** (fills the form — see `05-browser-claude-runbook.md`).

| File | What it is |
|---|---|
| `01-permission-usage.md` | Paste-ready English text for every permission's "How will your app use…" box, incl. a "Response to previous review" paragraph |
| `02-screencast-scripts.md` | 4 videos, shot by shot, with on-screen captions; which permission each one covers |
| `03-reviewer-instructions.md` | "App access / test instructions" + test-credential guidance |
| `04-data-handling.md` | Data-handling questionnaire answers (corrected) |
| `05-browser-claude-runbook.md` | Prompt + click path for a Claude browser session to fill the form |

## Why we were rejected (from the reviewer notes) → what fixes it

| Reviewer asked for | Covered by |
|---|---|
| Full Meta login flow + user granting the permission (all 11) | Every video starts at OT1 login → Connect → Meta consent with **asset selection visible** |
| Messaging (`pages_messaging`, `instagram_manage_messages`, `instagram_business_manage_messages`): asset selection, a **live send from our UI**, and the **same message delivered in the native app** | Videos 1, 2, 3 — phone with Messenger/Instagram on camera/mirrored |
| `pages_manage_metadata`: where the app **subscribes to Page events**, and a **sample webhook event arriving**, tied to the same Page | Video 1 part B — `/super-admin/pages/{id}/diagnose` shows `page_subscribed_apps` + the webhook row for the message just sent |
| `pages_utility_messaging`: template creation/population/sending | **We don't have that feature → do NOT request it** (remove from the submission) |
| English UI, captions, explain buttons | Language switcher → EN before recording; caption overlays in every script |

## Scope of this submission

Request: `pages_show_list`, `pages_messaging`, `pages_read_engagement`, `pages_manage_metadata`,
`business_management`, `instagram_basic`, `instagram_manage_messages`,
`instagram_business_basic`, `instagram_business_manage_messages`, and (optional, Video 4)
`instagram_manage_comments`.
**Drop:** `pages_utility_messaging` (no template feature). Leave `public_profile` (approved).

Priority if you only record some videos: **2 (Instagram via Meta)** → **1 (Messenger)** →
**3 (Instagram Login)** → 4 (comments). Video 2 is what unblocks customer Instagram DMs
(verified 2026-10-01: on Standard Access only app-role senders' DMs are delivered).

## Pre-flight checklist (do before recording — each item has burned a past attempt)

- [ ] **Reviewer login on ot1-pro.com works** (the account named in `03-…`) and can see the
      connect buttons. Buttons are hidden unless `META_APP_VERIFIED=true` or the user is a
      super-admin → set `is_super_admin=1` on the reviewer user for the review window
      (revert after decision). Do **not** flip `META_APP_VERIFIED` (CLAUDE.md pin #1).
- [ ] Reviewer user can open `/super-admin/pages/{id}/diagnose` (same super-admin flag) —
      Video 1 part B uses it.
- [ ] Every requested scope is ticked in the **Facebook Login for Business configuration**
      (developers.facebook.com/apps/1469090344742803/business-login/configurations). A
      missing one makes non-admin reviewers see "Invalid Scopes" (incident 2026-09-08).
      Admin accounts don't see the error — test with a non-admin.
- [ ] Test assets: a Facebook **Page** + an Instagram **Professional** account linked to it,
      *Allow access to messages* ON; a second phone/Instagram/Messenger account to send from.
- [ ] Instagram Login (Video 3): confirm which Meta app the code uses —
      `META_INSTAGRAM_APP_ID` on prod (journal: `2382509022254519`, "OT1 Direct Connect",
      parent `2908423109505861`). If that is a **different app** from `1469090344742803`,
      submit `instagram_business_*` on **that** app, otherwise approval won't apply to the
      button customers click.
- [ ] App UI in **English** (language switcher) and browser zoom ~110% so text is legible.
- [ ] Privacy (`/privacy`), Terms (`/terms`) and data-deletion callback load.
- [ ] Dry-run `03-reviewer-instructions.md` end-to-end with a **non-admin** account.

## After the decision

Approved → each permission shows **Advanced Access** → retest with a no-role sender
(`platform-messaging-testing` skill §3) → update the skill + the hint under the Instagram
connect buttons → revert the reviewer's super-admin flag. Rejected → paste the reviewer
notes into a new session with this folder.
