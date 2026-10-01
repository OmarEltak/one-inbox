# Instagram — reference

Instagram has **two independent integrations** that share one `pages.platform='instagram'`
table. Almost every IG bug in this repo came from mixing them up. Identify the path first:
`metadata.auth_type === 'instagram_business'` → Direct IG Login; otherwise (has
`metadata.linked_facebook_page_id`) → Connect via Meta. The diagnostic prints it as
`meta.path`.

## Path A — "Connect via Meta" (Messenger Platform for Instagram)

| Thing | Value |
|---|---|
| Meta app | main app `1469090344742803` ("One Inbox Business") |
| OAuth | FB dialog, scopes add `instagram_basic,instagram_manage_messages,instagram_manage_comments` (`getInstagramViaFacebookConnectUrl`) |
| Page row | `platform_page_id` = `instagram_business_account.id` (1784…); token = **the linked FB Page's token**; `metadata.linked_facebook_page_id` |
| Webhook | object `instagram`, `entry.id` = IG account id, POST `/api/webhooks/meta`, signed with `META_APP_SECRET` |
| App-level `instagram` fields (verified 2026-10-01) | messages, message_edit, comments |
| Page-level | the **FB Page's** `subscribed_apps` (same `FB_SUBSCRIBED_FIELDS`) — there is no IG-level subscribe call on this path |
| Send | `POST graph.facebook.com/{v}/{linked_facebook_page_id}/messages` (FB page id, not IG id) |
| Sync | `graph.facebook.com/{v}/{fb_page_id}/conversations?platform=instagram` |

**Verified 2026-10-01 (page 22, `ot1.pro`, app NOT approved):** DMs delivered as webhooks,
routed, stored; history backfilled (21 messages); survives reassignment to a customer
team. Token scopes include `instagram_manage_messages` targeting the IG id.
**Not yet verified:** whether DMs from senders with **no app role** are delivered on
Standard Access — record the sender's role on the next test (SKILL §3.1). The April 2026
"Mishkah IG gets nothing via Meta" conclusion was never measured with the diagnostic and
is contradicted by page 22 — treat it as unproven.

## Path B — "Connect Direct (IG Login)" (Instagram API with Instagram Login)

| Thing | Value |
|---|---|
| Meta app | Instagram app "OT1 Direct Connect": Instagram app id `2382509022254519` (parent Meta app `2908423109505861`, journal 2026-04-27) |
| OAuth | `instagram.com/oauth/authorize`, scopes `instagram_business_basic,instagram_business_manage_messages` (+ `config_id` if `META_LOGIN_CONFIG_ID`) → callback `/connections/instagram/callback` |
| Token exchange | `api.instagram.com/oauth/access_token` (tries `META_INSTAGRAM_APP_SECRET` then `_LEGACY`) → long-lived via `graph.instagram.com/access_token` |
| Page row | `platform_page_id` = IGBID = `/me?fields=user_id` (same 1784… id the webhooks carry; `/me?fields=id` is an app-scoped id — never route on it); `metadata.auth_type='instagram_business'` |
| Subscribe | `POST graph.instagram.com/{v}/{igbid}/subscribed_apps`, `IG_SUBSCRIBED_FIELDS = 'messages'` |
| Webhook | POST `/api/webhooks/meta-ig`, signed with `META_INSTAGRAM_APP_SECRET` (or `_LEGACY`) |
| Send | `POST graph.instagram.com/{v}/me/messages` with the IG token |

**Verified:** real DMs from other users arrived for the tester account `omar_eltak88`
(2026-04/05). The connected IG account itself must be an **accepted Instagram Tester**
while the app is unapproved.
**Known traps:**
- It authorizes **whichever IG account is logged in** on instagram.com in that browser —
  no picker. To connect a different account, switch accounts or use a private window.
- `comments` in `IG_SUBSCRIBED_FIELDS` without `instagram_business_manage_comments` in the
  OAuth scope → 400 "Application does not have the capability" for the **whole** call →
  `messages` never subscribed (shipped 2026-09-06, fixed 2026-10-01).
- Callback with **no `code` and no error field** happens (OT1 business account,
  2026-10-01, cause unknown — suspect missing tester role or FB-login redirect). The flash
  now prints `received: …` params; collect them before theorising.
- App-level subscriptions for this app can't be read with our creds (Graph returns
  OAuthException 190) — check App Dashboard → Instagram → Webhooks.

## Account-side requirements (both paths)

- Professional (Business/Creator) account.
- *Allow access to messages* ON (Settings → Messages and story replies → Message controls →
  Connected tools).
- Path A: IG account linked to a Facebook Page the connecting FB user manages.

## Self-heal / routing notes

- `handleMetaMessage` matches `platform_page_id` OR metadata `igsid/igbid/legacy_id/
  oauth_user_id`, prefers an active page with an active connected account, and will
  re-activate an inactive exact match only if its connected account is still active
  (so user disconnects stick).
- Messages where sender == page itself are dropped as duplicate echoes.
- Switching a page between paths: Path B reconnect over a Path A row keeps
  `linked_facebook_page_id` but sets `auth_type` (send/sync follow `auth_type`); Path A
  reconnect over a Path B row overwrites metadata (drops `auth_type`).
