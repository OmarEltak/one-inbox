# 03 — Reviewer access instructions (paste into "App access / testing instructions")

> The July text told reviewers to click "Add Connection → Instagram" (that button doesn't
> exist) and said connections were pre-connected so "no OAuth setup required" — reviewers
> must see the login flow, so that wording works against us. Replace it with this.

---

OT1-Pro (https://ot1-pro.com) is a web app; no download needed. The UI is in English (use the
EN/AR switcher at the top if it shows Arabic).

**Sign in**
1. Go to https://ot1-pro.com/login and sign in with the test account provided in the
   credentials field.

**Connect a Facebook Page (pages_show_list, business_management, pages_read_engagement, pages_manage_metadata, pages_messaging)**
2. In the left sidebar click **Connections**.
3. On the **Facebook** card click **Connect with Facebook** and complete Facebook Login for
   Business, selecting the test Page.
4. The Page appears on Connections as **Active**.
5. Send a Messenger message to that Page from another Facebook account. Open **Inbox** in the
   sidebar; the conversation appears within a few seconds.
6. Open it, type a reply in the box at the bottom and click **Send**. The reply is delivered
   in Messenger.
7. Webhook subscription for the Page can be inspected at
   `https://ot1-pro.com/super-admin/pages/<pageId>/diagnose` (`<pageId>` is the number in the
   inbox URL after selecting the Page in the sidebar), field `meta.page_subscribed_apps`.

**Connect Instagram (instagram_basic, instagram_manage_messages)**
8. Connections → **Instagram** card → **Connect via Meta**; select the Page linked to the test
   Instagram professional account.
9. Send an Instagram Direct message to that account from another Instagram account, then
   reply from **Inbox** as in steps 5–6. The reply arrives in Instagram.

**Instagram Business Login (instagram_business_basic, instagram_business_manage_messages)**
10. Connections → **Instagram** card → **Connect Direct (IG Login)**; log in with the test
    Instagram account, then repeat step 9.

**Comment replies (instagram_manage_comments, if requested)**
11. Sidebar → **AI Settings** → **AI Configuration** → **Comments** tab → enable for the
    Instagram account. Comment on one of its posts from another account; the reply appears
    under the comment.

---

## Credentials field

```
OT1-Pro login: <reviewer email>   Password: <reviewer password>
Facebook / Instagram test accounts: <test FB user + test IG professional account, or "use your own test users">
```

- Fill the real values **in the Meta form only** — never commit them to this repo. The July
  form used `reviewer@ot1-pro.com`; the journal also names
  `accountformetaappreview@gmail.com` as the dedicated Facebook reviewer account. Pick one,
  confirm it can log in on prod, and set `is_super_admin=1` on it for the review window (the
  connect buttons and the diagnostics page need it — see README pre-flight).
- Codes must stay valid for a year: don't rotate that password until the review is decided.
