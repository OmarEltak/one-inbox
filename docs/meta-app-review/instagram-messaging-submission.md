# Meta App Review: Instagram messaging (Advanced Access)

**Why (verified 2026-10-01):** on Standard Access, Instagram DMs reach OT1 only when the
**sender** has a role on our app. A DM from an ordinary customer to `ot1.pro` produced no
webhook at all, while the admin's own DMs arrived. The page token already carries
`instagram_manage_messages`; what's missing is **Advanced Access**. No code change can
work around it (see `.claude/skills/platform-messaging-testing/references/instagram.md`).

**Who does what:**
- **Claude (this doc):** submission text, reviewer instructions, screencast script, click path.
- **Omar:** records the screencast, pastes the text, submits, then tells Claude "submitted"
  so the skill + journal get updated.

**Previous rejections to answer:** round 2 (2026-08-11) rejected 11/12 permissions under
Policy 1.6: *"screencast fails to show end-to-end experience of the use case"*. Reviewers
asked to see (1) asset selection, (2) a live send from our app UI, (3) the message delivered
in the native Instagram client on a phone. Every video below shows all three.

---

## 0. Which submissions (do both; they're independent)

| # | App | Permission(s) | Feeds connect button |
|---|---|---|---|
| A | **One Inbox Business** `1469090344742803` (developers.facebook.com/apps/1469090344742803) | `instagram_manage_messages` + its prerequisites `instagram_basic`, `pages_show_list`, `pages_manage_metadata`, `pages_read_engagement`, `business_management` | Connect via Meta |
| B | **OT1 Direct Connect** (parent `2908423109505861`, Instagram app `2382509022254519`) | `instagram_business_basic`, `instagram_business_manage_messages` | Connect Direct (IG Login) |

B is the smaller review (two permissions, one product surface). A unlocks the
managed-onboarding path customers already use. Submit B first if you must choose.

---

## 1. App overview (paste into both)

OT1-Pro is a shared team inbox for small businesses. A business connects its Instagram
professional account (and optionally Facebook Page, WhatsApp, Telegram, email) and answers
every customer conversation from one screen. Customers' Instagram DMs appear in the OT1-Pro
inbox in real time; the business's staff reply from the inbox, and the reply is delivered
to the customer in Instagram Direct. Businesses can optionally enable an AI assistant that
drafts or sends first replies using information the business provides (products, prices,
working hours); staff can pause the AI per conversation and take over at any time.

We only access conversations of Instagram accounts the business explicitly connected. We do
not read, store, or message anyone who has not first messaged the business. We do not send
promotional broadcasts over Instagram; replies are always responses to a customer-initiated
conversation and respect Instagram's 24-hour messaging window.

---

## 2. Submission A — `instagram_manage_messages` (One Inbox Business)

**How we use it:**
1. The business connects through Facebook Login for Business and selects the Facebook Page
   linked to its Instagram professional account (asset selection screen).
2. We read the linked Instagram account id (`instagram_basic`, `pages_read_engagement`) and
   subscribe the Page to messaging webhooks (`pages_manage_metadata`).
3. When a customer sends the business an Instagram DM, Meta sends a `messages` webhook on the
   `instagram` object. OT1-Pro stores the message and shows it in the business's inbox.
4. A staff member replies in the OT1-Pro inbox; we call
   `POST /{page-id}/messages` with the Instagram recipient id. This is the call
   `instagram_manage_messages` authorizes. The customer receives it in Instagram Direct.
5. Optional: we load recent conversation history (`GET /{page-id}/conversations?platform=instagram`)
   so staff see context when they first connect.

**Why Advanced Access:** our customers are independent businesses, and their customers are
the general public. Standard Access only delivers messages from people with a role on our
app, so real customer DMs never reach the business's inbox.

**Response to previous rejection:** the new screencast shows, without cuts: the Facebook
Login for Business asset-selection screen with the Instagram-linked Page chosen; an incoming
DM sent from a phone appearing in the OT1-Pro inbox; a reply typed and sent from the OT1-Pro
inbox; and the reply arriving in the Instagram app on the phone.

## 3. Submission B — `instagram_business_manage_messages` (OT1 Direct Connect)

**How we use it:**
1. The business clicks **Connect Direct (IG Login)** and signs in with Instagram Business
   Login, approving `instagram_business_basic` and `instagram_business_manage_messages`.
2. We subscribe the account to the `messages` webhook
   (`POST graph.instagram.com/{ig-user-id}/subscribed_apps`).
3. Customer DMs arrive as webhooks and appear in the OT1-Pro inbox.
4. Staff reply in the inbox; we call `POST graph.instagram.com/me/messages`. The customer
   receives it in Instagram Direct.

`instagram_business_basic` is used only to read the connected account's id, username, name
and profile picture to label it in the inbox.

**Response to previous rejection:** same end-to-end screencast as Submission A but using the
Instagram Business Login screen (no Facebook step).

---

## 4. Reviewer instructions (paste into "How to test")

1. Go to https://ot1-pro.com/login and sign in with the test account in the "test
   credentials" field (`accountformetaappreview@gmail.com`; password is in the journal key
   table / password manager, never in this repo).
2. Open **Connections** in the left sidebar.
3. Instagram card → click **Connect via Meta** (Submission A) or **Connect Direct (IG Login)**
   (Submission B) and approve with the Instagram/Facebook test asset provided.
4. From any other Instagram account, send a DM to the connected Instagram account.
5. Open **Inbox**. The conversation appears within a few seconds. Click it.
6. Type a reply in the composer at the bottom and press send. The reply arrives in the
   sender's Instagram Direct.

## 5. Screencast script (one take each, ~2 min, phone visible on camera or screen-mirrored)

1. **0:00** Browser at ot1-pro.com logged in as the reviewer account. Narrate/caption:
   "Business connects its Instagram account."
2. **0:10** Connections → Instagram card → Connect button. Show the Meta consent screen
   **including the asset-selection step** (pick the Page/Instagram account). Approve.
3. **0:40** Back on Connections: the Instagram account is listed as Active.
4. **0:50** Phone: from a *different* Instagram account, send "Hi, do you ship to Cairo?" to
   the business account.
5. **1:05** Browser: Inbox → the new conversation appears. Click it; show the message.
6. **1:20** Type "Yes, delivery to Cairo takes 2-3 days." Send.
7. **1:35** Phone: open Instagram Direct and show the reply delivered.
8. **1:50** End on the OT1 inbox showing both messages.

Record A and B separately. Captions beat narration (reviewers often watch muted).

## 6. Pre-submission checklist

- [ ] Reviewer user exists on ot1-pro.com and can see the Instagram **connect** buttons. The
      buttons are hidden unless `META_APP_VERIFIED=true` or the user is super-admin. Make
      the reviewer user a super-admin for the review window (revert after), and **don't**
      flip `META_APP_VERIFIED` (CLAUDE.md pin #1).
- [ ] Reviewer FB account is a Tester on app A; the test Instagram account is an accepted
      Instagram Tester on app B (instagram.com/accounts/manage_access → Tester Invites).
- [ ] Test Instagram account: Professional, linked to a Facebook Page (A), and
      *Allow access to messages* ON.
- [ ] Every permission requested in A is checked in the Facebook Login for Business config
      (otherwise Meta shows "Invalid Scopes" to non-admin reviewers; 2026-09-08 incident).
- [ ] Do a dry run of section 4 yourself with a non-admin account before recording.
- [ ] Privacy policy + data deletion URLs still load (Meta re-checks them).

## 7. After approval

1. Confirm each permission shows **Advanced Access** in the app dashboard.
2. Have a no-role account DM a via-Meta Instagram page; check
   `/super-admin/pages/{id}/diagnose` shows the webhook row.
3. Update `.claude/skills/platform-messaging-testing` (open-issues table, instagram.md) and
   the hint text under the Instagram connect buttons.
4. Only when the *Facebook* permissions also show Advanced Access, consider
   `META_APP_VERIFIED=true` per CLAUDE.md pin #1.
