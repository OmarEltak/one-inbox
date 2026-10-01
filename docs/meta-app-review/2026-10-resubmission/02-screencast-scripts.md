# 02 — Screencast scripts (record each as ONE uncut take)

**Rules that apply to every video** (from the reviewer notes + Meta's screen-recording guide):
- App UI in **English**. Browser zoom ~110%. Hide bookmarks/notifications.
- Start **logged out of OT1-Pro** and show the login, then the **full Meta login** including
  the permission/asset-selection screens. Don't skip or cut the consent dialog.
- Show the **phone** (screen-mirroring window next to the browser, or film it) for every
  "delivered in the native app" moment.
- Add **caption overlays** (any editor, or a sticky-note window) — reviewers watch muted.
  Suggested caption text is in quotes below.
- Keep each video 2–4 minutes. Export MP4.
- Use the same Page / Instagram account throughout one video (reviewer asked for events
  "tied to the same Page shown during setup").

---

## Video 1 — Facebook Page messaging + webhooks
**Covers:** `pages_show_list`, `business_management`, `pages_read_engagement`,
`pages_manage_metadata`, `pages_messaging`. Upload to all five.

**Part A — connect & send**
1. ot1-pro.com/login → sign in. Caption: "Business owner signs in to OT1-Pro."
2. Sidebar → **Connections**. Caption: "Connections lists the channels a business can link."
3. Facebook card → **Connect with Facebook**. Caption: "Connect a Facebook Page with Facebook Login for Business."
4. Meta dialog: continue as the user → **select the business portfolio + the Page** (Page
   name clearly visible) → review permissions → **Save/Continue**.
   Caption: "Business selects ONLY the Page it wants to connect and grants access."
5. Back on Connections: Page shows **Active** with its name/picture.
   Caption: "pages_show_list + pages_read_engagement: the selected Page and its details."

**Part B — subscription + sample webhook (pages_manage_metadata)**
6. Open `https://ot1-pro.com/super-admin/pages/{id}/diagnose` for that Page (id from the
   inbox URL `?pageId=`). Scroll to `meta.page_subscribed_apps`.
   Caption: "On connect, OT1-Pro subscribes this Page to webhooks (pages_manage_metadata): our app with messages, message_echoes, feed…"
7. Phone (Messenger, a different account): send "Hi, is the leather bag available?" to the Page.
8. Refresh the diagnostics page → the newest `webhooks.for_this_page_latest` row shows the
   **same Page id** and the time just now. Caption: "A webhook event for the same Page arrives."
9. Sidebar → **Inbox** → the new conversation appears. Click it.
   Caption: "The customer's message appears in the business inbox."

**Part C — live send (pages_messaging)**
10. Type "Yes! It's in stock. Want medium or large?" → **Send**.
    Caption: "Staff replies from OT1-Pro (pages_messaging)."
11. Phone: Messenger shows the reply. Hold 3 s. Caption: "Delivered in Messenger."

## Video 2 — Instagram messaging via Facebook Login (most important)
**Covers:** `instagram_basic`, `instagram_manage_messages` (+ re-shows `pages_show_list`,
`pages_read_engagement`, `business_management`).
1. OT1-Pro login → Connections. Caption: "Connect an Instagram professional account."
2. Instagram card → **Connect via Meta**.
3. Meta dialog: select the **Page that is linked to the Instagram account** and the
   **Instagram account** (both visible) → grant. Caption: "Business grants access to its Instagram account through its linked Page."
4. Connections: Instagram account listed (username + picture), Active.
   Caption: "instagram_basic: the connected Instagram account."
5. Phone (Instagram, a **different account with no role on the app**): DM the business
   "Do you ship to Cairo?"
6. OT1-Pro **Inbox** → Instagram conversation appears → open it.
   Caption: "instagram_manage_messages: the customer's Instagram DM arrives in OT1-Pro."
7. Reply "Yes — 2–3 days to Cairo." → **Send**. Caption: "Staff replies from OT1-Pro."
8. Phone: Instagram Direct shows the reply. Caption: "Delivered in Instagram."

## Video 3 — Instagram Business Login (no Facebook Page)
**Covers:** `instagram_business_basic`, `instagram_business_manage_messages`.
1. OT1-Pro login → Connections → Instagram card → **Connect Direct (IG Login)**.
2. Instagram Business Login window: log in / choose the account (username visible) →
   permission screen → **Allow**. Caption: "Business logs in with Instagram and grants messaging access."
3. Connections: account listed. Caption: "instagram_business_basic: account id, username, picture."
4. Phone (different Instagram account) → DM the business → appears in OT1-Pro Inbox.
5. Reply from OT1-Pro → **Send** → phone shows it in Instagram Direct.
   Caption: "instagram_business_manage_messages: receive and reply from OT1-Pro."

## Video 4 (optional) — Instagram comment replies
**Covers:** `instagram_manage_comments`. Only if requesting it.
1. Login + Connect via Meta as Video 2 (can be shortened but must show the grant).
2. **AI Settings → AI Configuration → Comments** tab → enable Comment AI for the Instagram
   account, mode "All comments". Caption: "Business opts in to automatic comment replies."
3. Phone: comment "Price?" on one of the business's posts.
4. Wait for the reply; phone shows the reply under the comment.
   Caption: "instagram_manage_comments: OT1-Pro replies to the comment on the business's behalf."

---

## Recording checklist (tick per video)
- [ ] Logged-out start, full Meta/Instagram consent visible, asset names readable
- [ ] Phone visible for every "delivered" step
- [ ] Same Page/account from setup to webhook to reply
- [ ] Captions on; English UI; ≤4 min; MP4
