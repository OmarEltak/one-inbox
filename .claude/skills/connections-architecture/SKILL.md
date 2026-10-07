---
name: connections-architecture
description: Use BEFORE touching anything in `app/Services/Platforms/`, `app/Http/Controllers/ConnectionController.php`, `app/Http/Controllers/Webhooks/`, `app/Livewire/Connections/`, `app/Models/ConnectedAccount.php`, `app/Models/Page.php`, or any OAuth / embedded-signup / QR-connect flow. Also use before diagnosing any "connect button doesn't work", "connected but nothing received", "wrong account connected", "page disappeared", "token expired", or "webhook not firing" bug for ANY of the 10 platforms (Facebook, Instagram-via-FB, Instagram Business Login, WhatsApp Cloud, WhatsApp QR, Telegram, Email, Snapchat, TikTok, Discord, Slack). Explains the common data model (ConnectedAccount + Page + page_access_token), the per-platform OAuth entry points, token lifecycles, webhook routes, scope requirements, and the known traps that have each burned real session hours. This skill is the map; `platform-messaging-testing`, `meta-webhook-two-layer-subscription`, `meta-business-page-visibility`, and `platform-messaging-testing/references/*.md` are the deep dives. Read this FIRST to know which deep-dive applies.
---

# Connections Architecture — The Map

OT1-Pro connects to **10 inbound channels**. They all funnel into one unified inbox via the same abstractions, but their OAuth, token, and webhook mechanics differ wildly. This skill is the one-stop reference so you don't have to grep across 10 files to understand what's going on.

## The common data model (same for every platform)

```
ConnectedAccount  (team_id, platform, platform_user_id, access_token, token_expires_at, scopes[], email, name, is_active)
      │
      │ one-to-many
      ▼
   Page           (team_id, connected_account_id, platform, platform_page_id, name, avatar, page_access_token, category, is_active, metadata{})
      │
      │ one-to-many
      ▼
Conversation      (team_id, page_id, contact_id, platform, platform_conversation_id, status, …)
      │
      │ one-to-many
      ▼
 Message          (conversation_id, direction, sender_type, content_type, content, media_url, platform_sent_at, …)
```

**Why both `ConnectedAccount` and `Page`?** One user may admin multiple Pages (one FB account → 5 Pages). We store the user token + profile on `ConnectedAccount`, and each Page gets its own row with its own (usually non-expiring) `page_access_token`. Outbound sends and most API calls use `page_access_token`; refreshing/listing/revoking uses the user token on `ConnectedAccount`.

**Non-negotiable invariant (CLAUDE.md pin #3, ARCHITECTURE §2):** One active `Page` per `(platform, platform_page_id)` globally — enforced by `Page::booted()` observer + audited in `page_transfers`. If a Page already belongs to another team, OAuth MUST block (not silently steal) — see `FacebookPlatform::fetchPages()` `blockedPages[]` collection.

**Non-negotiable invariant (CLAUDE.md pin #8):** `Team::hasAnyConnection()` checks active **Pages**, not `ConnectedAccount` rows. Some platforms (WhatsApp QR, Telegram, Email) don't create a `ConnectedAccount` — they create a `Page` directly.

## The platform matrix

| Platform | Entry point (controller method) | Auth style | Token lifecycle | Webhook URL | Scope / permission source |
|---|---|---|---|---|---|
| **Facebook Messenger** | `facebookRedirect` → `facebookCallback` | OAuth2 (Meta) | Short-lived user token → long-lived (~60d) → per-Page tokens (permanent) | `/api/webhooks/meta` | `pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, business_management` |
| **Instagram via FB** | `instagramViaFacebookRedirect` → `instagramViaFacebookCallback` | OAuth2 (Meta, FB app) | Same as FB + IG scopes | `/api/webhooks/meta` (same handler) | Above + `instagram_basic, instagram_manage_messages, instagram_manage_comments` |
| **Instagram Business Login** | `instagramRedirect` → `instagramCallback` | OAuth2 (Meta, graph.instagram.com) | 60d IG token, no FB page in between | `/api/webhooks/meta-ig` | `instagram_business_basic, instagram_business_manage_messages, instagram_business_content_publish` |
| **WhatsApp Cloud API** | `whatsappEmbeddedSignupCallback` (POST, no OAuth redirect) | Embedded Signup (Meta JS SDK) → System User token | Permanent System User token | `/api/webhooks/meta` (shared with FB) | `whatsapp_business_management, whatsapp_business_messaging` |
| **WhatsApp QR** | `whatsappConnect` (via `WhatsAppQrModal`) | QR scan via Evolution API (self-hosted) | Session cookies, Evolution persists | `/api/webhooks/evolution` | None (unofficial) |
| **WhatsApp QR (wuzapi)** | Same `whatsappConnect` with provider toggle | Same | Same | `/api/webhooks/wuzapi` | None |
| **Telegram** | `telegramConnect` (POST with bot token) | Bot token (user pastes from @BotFather) | Permanent | `/api/webhooks/telegram` | None (bot token is auth) |
| **Email** | `emailConnect` (POST with IMAP creds) | IMAP + SMTP creds (user pastes) | Credentials stored; scheduled poll every 2 min | None (pull-based, scheduler job) | None |
| **Snapchat** | `snapchatRedirect` → `snapchatCallback` | OAuth2 (Snapchat) | Short-lived access + refresh token | `/api/webhooks/snapchat` | `snapchat-marketing-api` |
| **TikTok** | `tiktokRedirect` → `tiktokCallback` | OAuth2 (TikTok for Business) | Access + refresh token | `/api/webhooks/tiktok` | `business.basic, biz.message` |
| **Discord** | `discordConnect` (POST with bot token) | Bot token | Permanent | None (gateway WebSocket, not HTTP webhook) | None |
| **Slack** | `slackConnect` (POST) | OAuth2 (Slack) → bot token | Permanent bot token | `/api/webhooks/slack` | `channels:history, chat:write, ...` |

## Where to look for each platform's specifics

| Topic | File |
|---|---|
| All OAuth entry/callback methods | `app/Http/Controllers/ConnectionController.php` |
| All webhook handlers | `app/Http/Controllers/Webhooks/` |
| Per-platform logic | `app/Services/Platforms/{Facebook,Instagram,WhatsApp,...}Platform.php` |
| Connection UI (modals, buttons, status pills) | `app/Livewire/Connections/Index.php` + `WhatsAppQrModal.php` + `resources/views/livewire/connections/index.blade.php` |
| Outbound send dispatch (user-triggered) | `app/Jobs/SendPlatformMessage.php` |
| Outbound send dispatch (AI-triggered) | `app/Jobs/SendAiResponse.php` |
| Webhook routing (which `Page` receives which inbound) | Webhook controllers key off `platform_page_id` → active `Page::where(['platform','platform_page_id'])->first()` |

## The traps, per platform

Each trap below has already cost at least one real session. If you're touching the related code, re-read the referenced skill before shipping.

### Facebook Messenger

1. **`/me/accounts` doesn't return Business-Portfolio-mediated Pages.** Even when Business Suite UI shows the user as "People with Facebook access, Full access", `/me/accounts` only returns Pages where they're a direct Page admin via personal FB. Business-mediated roles need `business_management` scope + `/me/businesses/{id}/owned_pages` traversal. Fixed 2026-10-07. See skill `meta-business-page-visibility`.
2. **Two-layer webhook subscription.** App-level (`POST /{app-id}/subscriptions`) AND page-level (`POST /{page-id}/subscribed_apps`) must BOTH include every subscribed_field, or Meta silently drops webhooks. See skill `meta-webhook-two-layer-subscription`.
3. **`message_echoes` must be subscribed to see outbound messages sent from Business Suite / Messenger mobile / FB composer.** Otherwise customers see our reply but we don't — same skill.
4. **Scope-stripping on 2FA-required Business Portfolios.** If the Business Portfolio enforces 2FA and user doesn't have 2FA on their personal FB, Meta silently grants only `pages_show_list` + `public_profile`. Fix is user-side (enable 2FA at facebook.com/security/2fac/settings), not code-side.
5. **Scope strings can trigger "Invalid Scopes" rejection** if they match a Facebook Login for Business config that includes a deprecated scope. See `FacebookPlatform.php:83-87` comment. **Pre-validate any scope change with the curl probe** in skill `meta-business-page-visibility` before deploying.
6. **Meta error code 2018278** means the 24-hour messaging window expired. Non-English error bodies include "خارج الإطار الزمني المسموح به". Don't retry — show the user that they need to initiate contact first. The 2026-06 incident where this was misdiagnosed as "IG cross-platform routing" is the origin story of the `evidence-first-diagnosis` skill.

### Instagram via Facebook

1. **IG Business accounts need their parent FB Page connected first.** `handleInstagramViaFacebookCallback` runs `fetchPages()` for FB, THEN detects linked IG per FB page. If `fetchPages()` returns 0 (e.g. Business-mediated-only), IG won't connect either — same `business_management` fix now applies.
2. **IG scope changes are irreversibly linked to the Dev Console use-case.** Don't try adding `instagram_business_manage_comments` without first ensuring the "Facebook Messaging" use case on developers.facebook.com has it enabled, or Meta rejects the whole OAuth with "Application does not have the capability". See `FacebookPlatform.php:36-39` comment.

### Instagram Business Login (direct, no FB)

1. **Different Graph host.** `graph.instagram.com`, not `graph.facebook.com`. Subscribed-fields endpoint and token refresh behave differently.
2. **Only `messages` subscribed_fields.** Adding `comments` without `instagram_business_manage_comments` kills the WHOLE subscribe call → page shows "Connected" but receives zero DMs. Shipped broken 2026-09-06 → 2026-10-01. See `FacebookPlatform.php:30-40` constant comment.
3. **`platform_page_id` IS the IGBID (Instagram Business ID), not the FB page ID.** Webhook routing keys off this.

### WhatsApp Cloud (Embedded Signup)

1. **No `/me/accounts` equivalent.** Embedded Signup gives us a WABA ID (WhatsApp Business Account) + phone number ID directly in the SDK callback — we don't fetch them.
2. **System User tokens are permanent** (no refresh), but can be revoked by the user from Business Suite.
3. **Still requires `whatsapp_business_management` + `whatsapp_business_messaging`** granted on the Business Portfolio.

### WhatsApp QR (Evolution API + WUZAPI)

1. **Entirely self-hosted — Evolution runs in Docker on port 8081, WUZAPI on another port.** No Meta involvement. Any production outage of Evolution kills every QR-connected WA account simultaneously.
2. **Session persistence is Evolution-side.** Our DB stores a `platform_page_id` keyed to the Evolution instance name. If Evolution's volume is lost, every WA QR connection must be rescanned.
3. **Two parallel send paths** — `SendPlatformMessage::sendViaWhatsApp` (user-triggered) and `SendAiResponse::sendViaWhatsApp` (AI-triggered). Both have Evolution branching. Changes must be applied to BOTH or one direction silently misroutes. See memory `project_whatsapp_parallel_send_paths.md`.
4. **Webhook URL is set at Evolution instance creation, not per-event.** Changing `EVOLUTION_WEBHOOK_URL` in `.env` only affects NEW instances.

### Telegram

1. **No OAuth** — user pastes a bot token from `@BotFather`. We validate it with `getMe` and register our webhook at `setWebhook`.
2. **One bot = one Page.** If the user wants to connect 3 bots, they create 3 Pages. Unlike FB where one user has many Pages.
3. **Webhook secret is in header `X-Telegram-Bot-Api-Secret-Token`.** `services.telegram.webhook_secret` env var. If misconfigured, all webhooks 403.

### Email (IMAP)

1. **Pull-based, not push.** Scheduled job (`FetchEmailsForPageJob`) runs every 2 minutes via `php artisan schedule:work`. If scheduler isn't running, zero email arrives.
2. **Credentials stored encrypted in `ConnectedAccount.access_token` (reused field).** Gmail requires an App Password, not the account password (user must have 2FA on their Google account first).
3. **New-message detection uses IMAP UID + folder state.** Changing folders or using IMAP SEARCH expensively kills Gmail IMAP quota fast.

### Snapchat / TikTok

1. **Both have refresh tokens.** The scheduled refresh job isn't visible in Horizon — it's a per-token background refresh. If a token expires without refresh, the next outbound send fails with 401 and the page is marked `is_active=false`.
2. **Scopes are called "Products" by Snapchat.** The actual OAuth-scope string is `snapchat-marketing-api`.

### Discord / Slack

1. **Discord uses a bot token via Gateway (WebSocket), not HTTP webhooks.** The daemon that holds the gateway connection isn't in the systemd unit list — it's a long-lived command. If the server reboots and the daemon isn't restarted, no Discord messages arrive. Check `supervisor` config or systemd unit for Discord daemon.
2. **Slack OAuth gives a bot token tied to a workspace, not a user.** `platform_page_id` IS the workspace ID. One workspace per Page.

## The two central controller patterns

### `connectResultFlash()` in `ConnectionController` — the one true success/error dispatcher

Every OAuth callback ends here. It handles four distinct outcomes:

1. **Partial success + blocked pages** — some pages created, some blocked by cross-team guard → `success` + `error` flash together with syncing banner.
2. **Zero pages + blocked pages** — nothing created, all blocked → `error` only.
3. **Zero pages, no blocks** — branches on `connected_accounts.metadata.last_fetch_pages_zero.likely_cause` to pick ONE of three error messages (scope-strip/2FA, needs business_management reconsent, no admin role). Added 2026-10-07.
4. **Full success** — `success` + syncing banner.

**Don't short-circuit this** by returning raw error strings from the callback method. The dispatcher is the only place that knows the right message for each failure shape.

### `fetchPages()` in `FacebookPlatform` — the merged-sources enumerator

Queries BOTH:
- `/me/accounts` → direct Page admin roles
- `fetchBusinessMediatedPages()` → `/me/businesses` → `/{biz}/owned_pages` + `/{biz}/client_pages`

Dedupes by `platform_page_id`, resolves per-page access tokens via `resolvePageAccessToken()` for Business-sourced pages (needs `business_management`). Enforces cross-team Page-takeover guard per `ARCHITECTURE §2`. Fires `SyncPageConversations` job per new Page for backfill.

## Diagnostic probes — the three you should know

**Probe 1 — Which Pages does Meta think this user has?**
```php
// run via tinker or temp script with the stored user token
Http::withToken($token)->get("https://graph.facebook.com/v23.0/me/accounts", ["summary"=>"total_count"])->body();
Http::withToken($token)->get("https://graph.facebook.com/v23.0/me/businesses")->body();
```

**Probe 2 — What scopes did Meta actually grant?**
```php
Http::get("https://graph.facebook.com/v23.0/debug_token", [
  "input_token" => $userToken,
  "access_token" => config('services.meta.app_id').'|'.config('services.meta.app_secret'),
])->body();
// Read `granular_scopes` to see WHICH pages/IG accounts the user picked on the consent screen.
```

**Probe 3 — Is a webhook subscribed at both layers?**
See skill `meta-webhook-two-layer-subscription` for the full two-level check.

## Order of operations when debugging "connected but nothing works"

1. **Is the Page row active?** `SELECT * FROM pages WHERE team_id=X AND is_active=1`. Inactive = token likely expired/revoked.
2. **Is there a `connected_accounts.metadata.last_fetch_pages_zero`?** If yes, OAuth returned no pages. Read `likely_cause` and go to the matching deep-dive skill.
3. **Does the token work right now?** Probe 1 above. 200 OK with data = token good, upstream delivery issue. 400/401 = token broken.
4. **Is the webhook subscribed?** Probe 3. Even a valid token won't deliver inbound messages if the webhook isn't subscribed.
5. **Is the queue running?** `systemctl status one-inbox-queue`. Inbound messages come in via webhook → dispatched to queue job → stored. If queue is down, messages arrive at webhook but never land in the inbox.
6. **Is the scheduler running?** `systemctl status one-inbox-scheduler` (prod) / check NSSM `OneInboxScheduler` (dev). Required for email polling, token refresh, cleanup.

See skill `platform-messaging-testing` for the per-platform end-to-end send + receive test runbooks.

## Related skills (deep dives)

- `meta-business-page-visibility` — the `/me/accounts` vs Business-mediated roles trap, including the diagnostic probe and matrix
- `meta-webhook-two-layer-subscription` — the two-layer subscription trap (app-level + page-level)
- `platform-messaging-testing` — per-platform end-to-end test runbooks
- `platform-messaging-testing/references/facebook-messenger.md` — FB-specific test flows
- `ot1-pro-prod-ops` — SSH-ing in, cache rebuilds, systemd services
- `nararouter-ops` + `nararouter-two-chain` — the AI dispatcher (orthogonal to connections but called from `SendAiResponse` which depends on an active connection)

## CLAUDE.md pins this skill maps to

- Pin #1 — `META_APP_VERIFIED` flag + per-permission Advanced Access requirement
- Pin #2 — Managed onboarding flow (super-admin OAuths through their own account, re-assigns to customer team at `/super-admin/onboarding-requests`)
- Pin #3 — One active `Page` per `(platform, platform_page_id)` observer invariant
- Pin #8 — `Team::hasAnyConnection()` checks active Pages, not `ConnectedAccount`
- Pin #12 — `/me/accounts` is NOT the complete Page list (added 2026-10-07, see `meta-business-page-visibility`)
