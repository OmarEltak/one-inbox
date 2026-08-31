# One Inbox — Engineering Journal

> **READ AT THE START OF EVERY SESSION.**
> This file is the single source of truth for every code change, decision, deployment step, debug result, browser action, and key location.
> Claude must append to this file after every meaningful action in every session.

---

## How to Use This File

- Every code change: what changed, why, file + line range
- Every deployment step: exact commands run, on which machine
- Every browser action: URL visited, what was clicked, what was changed
- Every debug result: what was tested, what the output was
- Key locations: where each credential lives, NOT the value itself — just how to find it
- Mistakes: what went wrong, what fixed it

---

## Project Topology

| Item | Value |
|------|-------|
| Local dev app | `C:\Users\NanoChip\Herd\one-inbox\` → `https://one-inbox.test` (Herd on Windows) |
| Production app | `/var/www/ot1-pro.com` on VPS `187.77.67.94` (Hostinger KVM2, Germany) |
| Production served by | nginx + PHP 8.4-FPM + MySQL 8 + Redis on Ubuntu 24.04 |
| Local dev DB | SQLite — `database/database.sqlite` |
| Production DB | MySQL 8.0, database `one_inbox`, user `deploy`@`localhost` |
| Queue driver | database |
| PHP version | 8.4 (Herd locally, ondrej/php PPA on VPS) |
| WhatsApp gateway | Evolution API v2.3.7 in Docker → `http://localhost:8081` (local dev only for now) |
| Meta parent app | `1469090344742803` (One Inbox Business) |
| Meta Instagram sub-app | `1408745007038040` |
| Production domain | `https://ot1-pro.com` (Cloudflare proxied) |
| Production team_id | 3 (user: omareltak7@gmail.com, user id: 3) |
| Local dev team_id | 1 (user: omareltak7@gmail.com, user id: 1) |
| Auto-deploy | GitHub Actions push-to-main → SSH deploy in ~24s |

> ⚠️ Local dev and production have SEPARATE databases. Fixes to one do NOT affect the other.
> Always verify which app directory you're running `php artisan` from.

---

## Key Locations (Never Write the Key Here — Only How to Find It)

### Meta / Facebook
| Key | Where to find it |
|-----|-----------------|
| `META_APP_ID` | developers.facebook.com → App `1469090344742803` → App Settings → Basic → App ID |
| `META_APP_SECRET` | Same page → App Secret (click Show) |
| `META_WEBHOOK_VERIFY_TOKEN` | Set in `.env` as `META_WEBHOOK_VERIFY_TOKEN`. Also entered manually in Meta console webhook config. Current value in `.env` file. |
| `META_INSTAGRAM_APP_ID` | developers.facebook.com → App `1408745007038040` (Instagram sub-app) → App Settings → Basic → App ID |
| `META_INSTAGRAM_APP_SECRET` | Same sub-app page → App Secret |

### Evolution API (WhatsApp Gateway)
| Key | Where to find it |
|-----|-----------------|
| Global API key | `docker-compose.evolution.yml` → `AUTHENTICATION_API_KEY`. Also in `.env` as `EVOLUTION_API_KEY`. |
| Instance token for `team_10_Xt4tGTvW` | `GET http://localhost:8081/instance/fetchInstances -H "apikey: {global_key}"` → field `token` |
| Instance name | `team_10_Xt4tGTvW` — created when user connected via QR scan as team 10 in local dev |

### Gemini AI
| Key | Where to find it |
|-----|-----------------|
| `GEMINI_API_KEY` | console.cloud.google.com → APIs & Services → Credentials. Or aistudio.google.com → Get API key |
| Free tier limit | 20 requests/day/model. Exhausted = 429 error in logs. |

### App
| Key | Where to find it |
|-----|-----------------|
| `APP_KEY` | `.env` file — generated once with `php artisan key:generate`. Different between local and prod. |
| WhatsApp phone number | `201028342835` (Mr Mohamed Eltak) |

---

## Running Services (What Must Be Running for Full Functionality)

### Production (VPS `187.77.67.94`) — auto-managed by systemd
```bash
# Queue worker — systemd service, auto-restarts
systemctl status one-inbox-queue

# Reverb WebSocket server (port 8080) — systemd service, auto-restarts
systemctl status one-inbox-reverb

# Scheduler — crontab for deploy user, runs every minute
# php artisan schedule:run

# nginx + php8.4-fpm — started at boot
systemctl status nginx php8.4-fpm
```

### Local Dev (Windows, `one-inbox.test`)
```bash
# 1. Docker (Evolution API — WhatsApp)
docker compose -f docker-compose.evolution.yml up -d
# Verify: curl http://localhost:8081/instance/fetchInstances -H "apikey: {EVOLUTION_API_KEY}"

# 2. Queue worker
php artisan queue:work
# OR NSSM service: OneInboxQueue

# 3. Scheduler
php artisan schedule:work
# OR NSSM service: OneInboxScheduler

# 4. ngrok tunnel (for Meta/Telegram webhooks in local dev)
ngrok http https://one-inbox.test --host-header=one-inbox.test

# 5. Reverb
# NSSM service: OneInboxReverb
```

---

## Meta Webhook Configuration

### Parent App (`1469090344742803`)
- **Webhook URL**: `https://ot1-pro.com/api/webhooks/meta`
- **Verify token**: in `.env` as `META_WEBHOOK_VERIFY_TOKEN`
- **Subscribed objects**: `page` (Messenger), `instagram` (Instagram DMs)
- **How to verify active**: `GET https://graph.facebook.com/v21.0/{app_id}/subscriptions?access_token={app_id}|{app_secret}`

### Instagram Sub-App (`1408745007038040`)
- **Webhook URL**: Must be set to `https://ot1-pro.com/api/webhooks/meta` ← **PENDING** as of 2026-03-30
- **Verify token**: Same as above
- **How to set**: developers.facebook.com → App `1408745007038040` → Webhooks → Edit → fill URL + verify token

### Evolution API Webhook (WhatsApp)
- **Instance**: `team_10_Xt4tGTvW`
- **Webhook URL configured**: `https://ot1-pro.com/api/webhooks/evolution`
- **Events**: `MESSAGES_UPSERT`, `CONNECTION_UPDATE`, `QRCODE_UPDATED`
- **How to check**: `GET http://localhost:8081/webhook/find/team_10_Xt4tGTvW -H "apikey: {global_key}"`

---

## Session Log

---

### Session: 2026-03-30 — Fix Inbound Messages (WhatsApp + Instagram)

**Goal**: Users can send messages but cannot receive them. Fix both WhatsApp and Instagram inbound.

---

#### WhatsApp — Root Cause Analysis

**Problem**: WhatsApp messages not received in production (`ot1-pro.com`).

**Debug steps**:
1. Checked Evolution API status: `GET http://localhost:8081/instance/fetchInstances` → instance `team_10_Xt4tGTvW` has `connectionStatus: open` ✓
2. Checked Evolution webhook config: `GET http://localhost:8081/webhook/find/team_10_Xt4tGTvW` → URL = `https://ot1-pro.com/api/webhooks/evolution`, enabled=true, no auth headers ✓
3. Sent test webhook to `https://ot1-pro.com/api/webhooks/evolution` → returned "OK" but no webhook_log created in local DB
4. Sent test webhook to `https://one-inbox.test/api/webhooks/evolution` → webhook_log created (id=594, 595) ✓
5. **Discovery**: Production app is at `C:\Users\NanoChip\Herd\one-inbox-prod\` with its OWN database. Local dev is `one-inbox\`. They are completely separate.
6. Checked production DB: **zero WhatsApp pages**. The instance `team_10_Xt4tGTvW` was connected under team_id=10 (local dev "omar aa859's Team"), not team_id=3 (production "Omar Eltak's Team").

**Fix applied to production DB**:
```sql
-- Created ConnectedAccount for WhatsApp in production
INSERT INTO connected_accounts (team_id, platform, platform_user_id, name, access_token, is_active, metadata, ...)
VALUES (3, 'whatsapp', '201028342835', 'WhatsApp (+201028342835)',
        Crypt::encryptString('EVOLUTION_INSTANCE_TOKEN'), 1,
        '{"gateway_mode":true,"gateway_instance":"team_10_Xt4tGTvW"}', ...)

-- Created Page for WhatsApp in production
INSERT INTO pages (team_id, connected_account_id, platform, platform_page_id, name, page_access_token, is_active, metadata, ...)
VALUES (3, 10, 'whatsapp', '201028342835', 'WhatsApp (+201028342835)',
        Crypt::encryptString('EVOLUTION_INSTANCE_TOKEN'), 1,
        '{"gateway_mode":true,"gateway_instance":"team_10_Xt4tGTvW","phone_number":"201028342835"}', ...)
```

> ⚠️ **Mistake**: First used `encrypt()` helper which PHP-serializes the value (`s:36:"..."`).
> The model's `encrypted` cast uses `Crypt::encryptString()` (no serialization).
> Comparison failed → 403 Forbidden on all real webhooks.
> **Fix**: Re-ran with `Crypt::encryptString()` — token match confirmed.

**Test result**:
```bash
curl -X POST https://ot1-pro.com/api/webhooks/evolution \
  -H "Content-Type: application/json" \
  -d '{"event":"MESSAGES_UPSERT","instance":"team_10_Xt4tGTvW","apikey":"INSTANCE_TOKEN",...}'
# Response: "OK"
# webhook_logs: id=105, platform=whatsapp_gateway ✓
# messages: id=220, content="end to end test", direction=inbound ✓
# conversations: id=548, platform=whatsapp ✓
```
**WhatsApp inbound: WORKING** ✅

---

#### WhatsApp — Local Dev DB Fixes (team_id=1)

In local dev DB, the WhatsApp page and account were misconfigured:
- Instance `team_10_Xt4tGTvW` was assigned to team_id=10 (another test account)
- Fixed: reassigned `connected_accounts.id=19` and `pages.id=22` to team_id=1

Also fixed via DB: all of Omar's Facebook and Instagram pages/accounts had `is_active=0`.
Activated:
- `connected_accounts` ids: 2 (Facebook), 8 (Instagram)
- `pages` ids: 2 (Brandk), 3 (تعلم الموسيقة), 9 (Instagram omar_eltak88)

---

#### Instagram — Root Cause Analysis

**Current Instagram setup in production**:
- Connected account id=9, platform=instagram, `platform_user_id=27389582010629405`, `auth_type=instagram_business`
- Uses `instagram_business_manage_messages` scope (standalone Instagram Business Login)
- **Limitation**: Only 15 test users until app review is approved

**Instagram sub-app webhook URL**: **EMPTY** in Meta console as of last check.
- Need to set in: developers.facebook.com → App `1408745007038040` → Webhooks → Edit Callback URL
- URL: `https://ot1-pro.com/api/webhooks/meta`
- Verify token: `META_WEBHOOK_VERIFY_TOKEN` from `.env`

**Instagram via Facebook Login (new approach — 0 requirements)**:
- Uses `instagram_manage_messages` permission — approved immediately, no app review
- Works for IG accounts linked to Facebook Pages
- **Code added this session** (see below)

---

#### Code Changes This Session

**File**: `app/Services/Platforms/FacebookPlatform.php`

1. **Added** `getInstagramViaFacebookConnectUrl()` (line ~59):
   - Builds Facebook OAuth URL with extra scope: `instagram_manage_messages`
   - Redirects to `connections.instagram-via-facebook.callback`
   - Reason: `instagram_manage_messages` has 0 requirements vs `instagram_business_manage_messages` which needs app review

2. **Added** `handleInstagramViaFacebookCallback()` (line ~80):
   - Calls `handleCallback()` with the new redirect URI
   - For each Facebook page fetched, calls `detectInstagramAccount()` to find linked IG account
   - Re-subscribes FB page with `instagram_messaging` field added
   - Dispatches `SyncPageConversations` for each found IG account

3. **Modified** `handleCallback()` signature (line ~206):
   - Added optional `?string $redirectUri = null` parameter
   - Default: `route('connections.facebook.callback')`
   - Reason: token exchange requires exact redirect_uri match — new IG-via-FB flow uses different callback URL

**File**: `app/Http/Controllers/ConnectionController.php`

4. **Added** `instagramViaFacebookRedirect()`:
   - Checks META_APP_ID configured and plan limits
   - Redirects to `getInstagramViaFacebookConnectUrl()`

5. **Added** `instagramViaFacebookCallback()`:
   - Handles OAuth return for IG-via-FB flow
   - Calls `handleInstagramViaFacebookCallback()`
   - Shows count of Instagram accounts detected

**File**: `routes/web.php`

6. **Added two routes** (after line 63):
   ```
   connections/instagram-via-facebook/redirect → instagramViaFacebookRedirect
   connections/instagram-via-facebook/callback → instagramViaFacebookCallback
   ```
   ⚠️ The callback URL must be added to Meta App `1469090344742803` → Facebook Login → Valid OAuth Redirect URIs:
   `https://ot1-pro.com/connections/instagram-via-facebook/callback`

**File**: `resources/views/livewire/connections/index.blade.php`

7. **Updated Instagram card** to show two connect buttons:
   - "Connect via Facebook" (gradient, primary) → `instagram-via-facebook.redirect` — works NOW, 0 requirements
   - "Connect Direct (IG Login)" (outline) → `instagram.redirect` — existing standalone flow, 15-user limit

---

#### Subscription & AI Credits Fix (Production)

```php
// Production team_id=3 was on 'free' plan, AI disabled
DB::table('teams')->where('id',3)->update([
    'subscription_plan' => 'enterprise',
    'subscription_status' => 'active',
    'ai_enabled' => 1,
    'ai_credits_limit' => 99999,
    'ai_credits_used' => 0,
]);
```

Same fix applied to local dev team_id=1 earlier in session.

---

#### Known Remaining Issues as of 2026-03-30

| Issue | Status | What's needed |
|-------|--------|--------------|
| Instagram sub-app webhook URL empty | ⚠️ Pending manual step | Set `https://ot1-pro.com/api/webhooks/meta` in Meta console for app `1408745007038040` |
| Facebook OAuth redirect URI for IG-via-FB | ⚠️ Pending manual step | Add `https://ot1-pro.com/connections/instagram-via-facebook/callback` to Valid OAuth Redirect URIs in app `1469090344742803` → Facebook Login → Settings |
| Instagram 15-user limit | ⚠️ Needs app review | `instagram_business_manage_messages` requires Standard→Advanced Access review in Meta console |
| Brandk page not showing after Facebook reconnect | 🔍 Investigating | User may not be selecting it in OAuth page picker, OR Facebook account permissions differ |
| Instagram contacts still showing after disconnect | 🔍 Known behavior | Conversations are not deleted on disconnect — only `is_active=false` on page. Inbox "All" view should filter to active pages only. |
| Gemini AI 429 (daily limit) | ⚠️ Free tier | 20 req/day limit on free Gemini tier. Upgrade to paid or add API key rotation. |

---

## Architecture Notes

### Webhook Routing Logic

```
Inbound webhook arrives at POST /api/webhooks/meta or /api/webhooks/evolution
         ↓
MetaWebhookController / EvolutionWebhookController
    - HMAC verification (Meta) or apikey check (Evolution)
    - Creates WebhookLog record
    - Dispatches ProcessIncomingMessage job
         ↓
ProcessIncomingMessage (queue worker)
    - Detects platform from webhook_log.platform
    - facebook/instagram → processMetaMessenger()
        - Finds Page by platform_page_id = entry[0].id
    - whatsapp_gateway → processEvolution()
        - Finds Page by metadata->gateway_instance = instanceName, is_active=1
    - Creates/updates Contact, ContactPlatform, Conversation, Message
    - Triggers AI response if enabled and not paused
```

### Two Instagram Flows

```
Flow 1: instagram_manage_messages (via Facebook Login)
  - App: 1469090344742803 (parent Facebook app)
  - OAuth: graph.facebook.com/dialog/oauth
  - Scope: pages_show_list, pages_messaging, instagram_manage_messages + standard FB scopes
  - Works for: IG accounts linked to FB Pages
  - Requirements: 0 (approved immediately)
  - Webhook: comes through parent app webhook
  - API for send: graph.facebook.com/{ig_user_id}/messages

Flow 2: instagram_business_manage_messages (Instagram Business Login)
  - App: 1408745007038040 (Instagram sub-app)
  - OAuth: www.instagram.com/oauth/authorize
  - Scope: instagram_business_basic, instagram_business_manage_messages
  - Works for: IG Business/Creator accounts (even without FB page)
  - Requirements: Standard Access only (15 test users) — needs app review for full
  - Webhook: comes through sub-app webhook (URL was EMPTY — needs to be set)
  - API for send: graph.instagram.com/{ig_user_id}/messages
```

### Production vs Local Dev

Both `one-inbox` and `one-inbox-prod` run from the same Windows machine.
Cloudflare tunnel routes `ot1-pro.com` → `one-inbox-prod`.
`one-inbox.test` is served by Herd directly.
**Each has its own `.env`, `APP_KEY`, and SQLite database.**

When running `php artisan tinker` or any artisan command, always `cd` to the correct directory first.

---

## Browser Actions Log

| Date | App | URL | Action | Result |
|------|-----|-----|--------|--------|
| 2026-03-30 | Meta console | developers.facebook.com/apps/1469090344742803 | Verified parent app webhook active for `instagram` object | Confirmed |
| 2026-03-30 | Meta Graph Explorer | graph.facebook.com tools/explorer | Queried `/me/accounts?fields=id,name,instagram_business_account{...}` | Found IG account linked to FB pages |
| 2026-03-30 | Instagram sub-app | developers.facebook.com/apps/1408745007038040 | Observed empty webhook URL field | ⚠️ Still needs to be filled |
| 2026-03-30 | Meta | Checked `instagram_manage_messages` permission | Shows 0 requirements, جاهز للاختبار | Approved for use immediately |
| 2026-03-31 | Meta App Review | developers.facebook.com/apps/1469090344742803/app-review/submissions/?submission_id=1488855866099584 | Full App Review submission wizard session | See session log below |

---

### Session: 2026-03-31 — Meta App Review Submission

**Goal**: Complete and submit the Meta App Review for "One Inbox Business" (app_id=1469090344742803, submission_id=1488855866099584, business_id=2169075923895403).

---

#### Step-by-Step Progress

**Step 1 — التحقق (Verification)**
- Status: ○ INCOMPLETE
- Requires submitting Meta business verification documents via "انتقل إلى التحقق" button
- Cannot be automated — user must upload business documents manually
- Blocker: No documents submitted yet

**Step 2 — إعدادات التطبيق (App Settings)**
- Status: ✅ COMPLETE
- All app metadata filled (name, category, privacy URL, etc.)
- **Website platform added**: Used "إضافة منصة +" dialog → selected Website → clicked التالي → URL `https://ot1-pro.com` filled in and saved via حفظ التغييرات
- Previous attempts to add platform via JavaScript DOM manipulation of `hidden_elem` class did NOT persist (client-side only)
- Successful approach: proper UI dialog flow (+ إضافة منصة → select Website checkbox → التالي) — revealed the Website section through React state, then typed URL and saved

**Step 3 — الاستخدام المسموح به (Permitted Use)**
- Status: ○ INCOMPLETE (partially filled)
- Permissions and their status:

| Permission | Status | Notes |
|-----------|--------|-------|
| `instagram_business_basic` | ○ | Description filled; screencast upload REQUIRED but needs actual video file |
| `instagram_business_manage_messages` | ✅ | Complete |
| `instagram_business_manage_comments` | ○ | Needs `instagram_basic` in API calls (old/new API mismatch) |
| `instagram_manage_messages` | ○ | Same mismatch — `instagram_basic` dependency check fails |
| `instagram_manage_comments` | ○ | Same mismatch |
| `pages_messaging` | ✅ | Complete |
| `pages_read_engagement` | ✅ | Complete |
| `business_management` | ✅ | Complete |
| `public_profile` | ✅ | Complete |

- **instagram_business_basic description** (filled this session):
  ```
  OT1 Pro requests instagram_business_basic as a DEPENDENT permission only. Required by instagram_business_manage_messages and instagram_business_manage_comments. NOT used standalone. Profile info (name, profile pic) shown in Connections UI only to identify the connected account.
  HOW TO TEST: Visit https://ot1-pro.com → Connections → Add Connection → Instagram → OAuth → account appears in list.
  Test credentials: reviewer@ot1-pro.com / Review2024!
  ```
- **instagram_manage_messages description** (filled this session): Explained it receives inbound DMs and sends replies via Pages API
- **KEY BLOCKER**: `instagram_manage_comments` and `instagram_manage_messages` have a META SYSTEM DEPENDENCY CHECK requiring `instagram_basic` in API calls. The app uses `instagram_business_basic` (new API), not `instagram_basic` (legacy API). These ○ items may block submission.

**Step 4 — معالجة البيانات (Data Processing)**
- Status: ✅ COMPLETE

**Step 5 — تعليمات المراجع (Reviewer Instructions)**
- Status: ○ IN PROGRESS (filled this session)
- Was blocked by: "ستحتاج إلى تحديد منصات لهذا التطبيق" — no platform registered
- **Unblocked by**: Adding Website platform to app settings (Step 2 above)
- **Instructions filled** (instructions-web-2):
  - App description: unified social inbox SaaS
  - 8-step testing walkthrough: login → Connections → Add Connection → Instagram OAuth → verify connection → test DM → test comment → reply
  - Lists each permission being tested and what it does
- **Access credentials filled** (accesscode-web-1):
  - Email: reviewer@ot1-pro.com
  - Password: Review2024!
  - Note: pre-connected Instagram Business account and Facebook Page already active on this test account

---

#### Current Status (End of Session 2026-03-31)

| Step | Status | Blocker |
|------|--------|---------|
| التحقق | ○ | User must upload business verification documents manually |
| إعدادات التطبيق | ✅ | — |
| الاستخدام المسموح به | ○ partial | instagram_business_basic needs screencast video; instagram_manage_comments/messages need instagram_basic API dependency |
| معالجة البيانات | ✅ | — |
| تعليمات المراجع | ○ in progress | Instructions + credentials filled; التالي button not yet clicked to confirm |

**إرسال للمراجعة button is still greyed out** — all steps must be ✅ before submission is enabled.

---

#### Key Decisions / Lessons

1. **JavaScript DOM manipulation of `hidden_elem` does NOT persist** — Meta's React UI uses server-side state. You must go through the actual UI dialog to register a platform. The nativeInputValueSetter approach only changes the DOM, it doesn't trigger the API call.
2. **`instagram_basic` dependency**: Meta's system check for old API permissions (`instagram_manage_comments`, `instagram_manage_messages`) requires the app to call `instagram_basic` in its Graph API requests. The new Business API (`instagram_business_*`) uses a different endpoint/scope family — this mismatch causes ○ on those permission items.
3. **Website platform**: Successfully added via + إضافة منصة dialog. Verified persistence: after full page reload, `https://ot1-pro.com/` appears in Website URL field and is NOT inside a `hidden_elem`.

---

#### Pending Manual Steps (User Must Do)

1. **التحقق**: Upload business verification documents at "انتقل إلى التحقق"
2. **instagram_business_basic screencast**: Record a screen capture showing Instagram Business account OAuth connection flow and upload it in the Permitted Use dialog for `instagram_business_basic`
3. **instagram_manage_comments / instagram_manage_messages**: Either:
   - Add `instagram_basic` to the app scopes and update API calls to use old endpoint, OR
   - Remove these legacy permissions from the submission (app already uses `instagram_business_manage_*` new API)
4. **تعليمات المراجع**: Confirm التالي was saved (session interrupted before verification)

---

## How to Test Each Platform

### WhatsApp
```bash
# End-to-end test (replace INSTANCE_TOKEN with value from Evolution API)
curl -X POST https://ot1-pro.com/api/webhooks/evolution \
  -H "Content-Type: application/json" \
  -d '{"event":"MESSAGES_UPSERT","instance":"team_10_Xt4tGTvW","apikey":"INSTANCE_TOKEN","data":{"key":{"remoteJid":"TEST_PHONE@s.whatsapp.net","fromMe":false,"id":"TEST001"},"messageType":"conversation","message":{"conversation":"test"},"pushName":"Tester","messageTimestamp":1711800000}}'

# Check result in production DB:
cd C:/Users/NanoChip/Herd/one-inbox-prod
php artisan tinker --execute="echo DB::table('webhook_logs')->orderBy('id','desc')->first()->id;"
```

### Instagram (sub-app flow)
```bash
# Verify webhook endpoint alive
curl "https://ot1-pro.com/api/webhooks/meta?hub.mode=subscribe&hub.verify_token=VERIFY_TOKEN&hub.challenge=test123"
# Expected response: "test123"
```

### Meta Webhook Subscription Check
```bash
# Check parent app subscriptions
curl "https://graph.facebook.com/v21.0/1469090344742803/subscriptions?access_token=APP_ID|APP_SECRET"
```

---

## Migrations / DB Schema Notes

- `pages.metadata` — JSON column. Stores `auth_type`, `username`, `gateway_instance`, `linked_facebook_page_id`, etc.
- `connected_accounts.access_token` — encrypted (uses `Crypt::encryptString`, NOT `encrypt()` helper)
- `pages.page_access_token` — encrypted (same — use `Crypt::encryptString`)
- `connected_accounts.scopes` — JSON array string
- `webhook_logs.payload` — full raw webhook JSON stored for debugging

---

*Last updated: 2026-03-30 by Claude (session: fix inbound WA+IG messages)*

---

### Session: 2026-03-31 — Fix Facebook Messenger inbound + stale conversations after disconnect

**Problem**: User reported: (1) FB Messenger inbound not working; (2) conversations showing after disconnect; (3) "only telegram works — do the rest the same way"

**Root causes found**:
1. **Missing `is_active` filter in ProcessIncomingMessage** — `handleMetaMessage()` looked up pages without `is_active=true`, so a disconnected page could match and swallow messages
2. **Stale conversations** — `Inbox/Index.php` `conversations()` had no `whereHas('page', is_active=true)` filter
3. **Facebook subscription blocked by 2FA** — `subscribePage()` calls `POST /{page_id}/subscribed_apps` using the page access token. Returns `(#200) User does not have sufficient administrative permission... Two Factor Authentication`. This is a Meta platform-level requirement: the token owner's personal FB account must have 2FA enabled. It is NOT a business-manager policy (Security Center shows "No one required" — that's separate).

**Fixes applied (both local + production)**:
- `app/Jobs/ProcessIncomingMessage.php` line ~93: Added `->where('is_active', true)` to page lookup
- `app/Livewire/Inbox/Index.php` line ~113: Added `->whereHas('page', fn($q) => $q->where('is_active', true))`

**Platform status after this session**:
| Platform | Status | Notes |
|----------|--------|-------|
| Telegram | ✅ Working | `setWebhook` always succeeds |
| Instagram | ✅ Working | 150+ inbound messages confirmed |
| WhatsApp | ✅ Working | End-to-end test confirmed (webhook_log id=105) |
| Facebook Messenger | ❌ Broken | `subscribePage()` fails silently with 2FA error |

**Facebook Messenger blocker — full analysis**:
- App-level Page webhook subscription: ✅ Confirmed — `messages`, `message_deliveries`, `message_reads` all subscribed in Meta Developer Console (use_cases/customize/webhooks → Page object → blue toggles)
- Per-page subscription: ❌ `POST /{page_id}/subscribed_apps` fails — 2FA not enabled on Omar's personal FB account
- `subscribePage()` is at `app/Services/Platforms/FacebookPlatform.php` lines 354-371 — logs failure but returns false silently; calling code in `fetchPages()` line 340 ignores return value, page saves as is_active=true anyway

**Known page states (production)**:
- Page `450418318493611` (تعلم المسيقة): team_id=3 id=9, is_active=true, NOT webhook-subscribed
- Page `313985005290971`: receives webhooks but NOT in DB (old stale subscription, not Omar's page)
- Duplicate page `450418318493611` in team 4 id=12: deactivated this session

**Options to fix Facebook Messenger**:
1. **Omar enables 2FA on personal FB account** → facebook.com → Settings → Security and Login → Two-Factor Authentication → then reconnect the page ← simplest
2. **Meta Business Manager System User** → create system user, grant page admin access, generate system user page token, store as env var, use in `subscribePage()` — no 2FA for system users
3. **Surface error to user** — instead of silent fail, show "2FA required" message

**Manual step required**: Omar enables 2FA at facebook.com → Settings → Security and Login → Two-Factor Authentication, then reconnects FB page in app.

*Last updated: 2026-03-31 by Claude (session: FB Messenger 2FA analysis + stale conversations fix)*

---

### Session: 2026-03-31 — Facebook Messenger 2FA warning UI + retry button

**Problem**: `subscribePage()` fails silently with 2FA error. Page saved as active but not subscribed. User had no idea why Facebook messages weren't coming in.

**Fix implemented**:
1. `app/Services/Platforms/FacebookPlatform.php` — `fetchPages()` now checks `subscribePage()` return value:
   - On failure: sets `$page->metadata['subscription_error'] = 'twofa_required'`
   - On success: clears `subscription_error` from metadata
2. `app/Livewire/Connections/Index.php` — Added `retryPageSubscription(int $pageId)` method:
   - Re-calls `subscribePage()` on demand
   - On success: clears error, flashes success message
   - On failure: keeps error, flashes error message with 2FA instructions
3. `resources/views/livewire/connections/index.blade.php` — Added 2FA warning under affected pages in the Connected Pages & Accounts table:
   - Shows: "⚠ Not receiving messages — Two-Factor Authentication required on Facebook. Enable 2FA on Facebook, then retry here."
   - "Enable 2FA" links to facebook.com/settings?tab=security
   - "retry here" calls `retryPageSubscription()`

**Deployed to**: local (one-inbox) AND production (one-inbox-prod) — all 3 files copied + `view:clear` run on prod

**DB updated**:
- Production: page id=9 (تعلم المسيقة) metadata updated with `subscription_error: twofa_required`
- Local: page id=2 (FB page) metadata updated with `subscription_error: twofa_required`

**Verified in browser**: Connections page on ot1-pro.com shows the yellow 2FA warning under تعلم المسيقة with clickable links.

**Intermittent 500 on /connections**: Observed two timeouts at 19:15:53 and 19:44:25 — `Maximum execution time of 30 seconds exceeded` in the compiled layout view. Cause: intermittent SQLite locking when queue worker processes jobs simultaneously. NOT a code bug. Page loads fine on retry. The WAL mode + 5s busy_timeout from a previous session mitigates this but doesn't eliminate it entirely under heavy load.

**Next action needed from Omar**:
1. Go to facebook.com → Settings → Security and Login → Two-Factor Authentication → Enable it
2. Come back to ot1-pro.com/connections → scroll to Connected Pages & Accounts → click "retry here" next to تعلم المسيقة
3. Success flash should appear → Facebook Messenger will start receiving messages

*Last updated: 2026-03-31 by Claude (session: FB 2FA warning UI + retry button)*

---

## 2026-03-31 — Fix Instagram Inbound ID Mismatch

**Problem**: Instagram webhooks arrive (`entry.id = 17841429680280453`) but `ProcessIncomingMessage::handleMetaMessage()` looks up the page by `platform_page_id` which was stored as the IGBID (`27389582010629405`). These are two different ID formats for the same Instagram account. Lookup fails → `No page found for instagram page ID: 17841429680280453` → message dropped.

**Root Cause**:
- `graph.instagram.com/me` returns `id = 27389582010629405` (IGBID — new format)
- Instagram webhook `entry.id = 17841429680280453` (legacy Instagram User ID — used for webhook routing)
- DB stored the IGBID, webhook sends the legacy ID → mismatch

**Fix applied** (local + prod):

1. **`app/Jobs/ProcessIncomingMessage.php`** — `handleMetaMessage()` — added Instagram self-healing fallback:
   - When primary lookup by `platform_page_id` fails for instagram
   - Finds any active instagram page, updates its `platform_page_id` to the webhook's `entry.id`
   - Stores old IGBID in `metadata['igbid']` so subscription API calls still use the right ID
   - Same pattern as Telegram (`first()` lookup on any active page)

2. **`app/Services/Platforms/FacebookPlatform.php`** — `subscribeInstagramPage()`:
   - Changed to use `$page->metadata['igbid'] ?? $page->platform_page_id` as the API ID
   - Ensures subscription uses IGBID even after webhook routing ID is stored as `platform_page_id`

3. **`app/Services/Platforms/FacebookPlatform.php`** — `handleInstagramCallback()`:
   - Now stores `'igbid' => $profile['id'] ?? $igUserId` in page metadata on connect
   - Future reconnects automatically have the IGBID available for subscription calls

4. **DB updated directly via tinker**:
   - Local: page id=9 — `platform_page_id` → `17841429680280453`, `metadata['igbid']` → `27389582010629405`
   - Prod: page id=10 — same update

5. **Queue restarted** on both local and prod (`php artisan queue:restart`)

**Platform status after this fix**:
| Platform | Inbound | Outbound | Notes |
|----------|---------|----------|-------|
| Telegram | ✅ | ✅ | Working |
| WhatsApp (QR) | ✅ | ✅ | Working |
| Instagram | ✅ | ✅ | Fixed — ID mismatch resolved |
| Facebook Messenger | ❌ | ✅ | Awaiting Omar to enable 2FA on personal FB account |

*Last updated: 2026-03-31 by Claude (session: Instagram inbound ID mismatch fix)*

---

## 2026-04-05 — WhatsApp Reconnect UI + Email Verification

### Email — Working (no code changes needed)
- `omareltak7@gmail.com`, `is_active=1`, scheduler runs every 2 min
- Last fetch: 2026-04-05 01:14:13, `processed=true`
- 43 conversations, inbound ✅ outbound ✅

### WhatsApp — Evolution API instances wiped
- `fetchInstances` returns `[]` — all instances gone (docker restart or `down` wiped them)
- Prod page id=14 (no phone, stale) → **deactivated via tinker**
- Prod page id=17 (`201026361218`, `instance=team_3_mGtbAjTL`) → still active, needs QR reconnect

### Code changes (local + copied to prod + `view:clear` on prod)

1. **`app/Services/EvolutionApiService.php`** — Added `fetchConnectedInstanceNames(): array`
   - Single `fetchInstances` call (3s timeout), returns array of live instance names

2. **`app/Livewire/Connections/Index.php`**:
   - `$waInstanceStates = []` — live instance lookup map loaded in `mount()`
   - `refreshWaStates()` — populates map from Evolution API
   - `reconnectGateway(int $accountId)` — deletes old instance + fires `open-whatsapp-qr`
   - `onGatewayConnected()` — now also calls `refreshWaStates()`

3. **`resources/views/livewire/connections/index.blade.php`**:
   - QR WhatsApp accounts: green "Active" if instance alive, yellow "Disconnected" if not
   - Shows "Reconnect" button for disconnected accounts
   - Reconnect → QR modal → after scan, `saveConnection()` updates page id=17 by phone number

### Action needed from Omar
1. Go to `ot1-pro.com/connections`
2. WhatsApp section shows "Disconnected" + "Reconnect" for `+201026361218`
3. Click Reconnect → scan QR → WhatsApp works again

**Platform status:**
| Platform | Inbound | Outbound | Notes |
|----------|---------|----------|-------|
| Telegram | ✅ | ✅ | Working |
| WhatsApp (QR) | ❌ | ❌ | Needs QR reconnect (UI now shows this clearly) |
| Instagram | ✅ | ✅ | Fixed prev session |
| Email | ✅ | ✅ | Working |
| Facebook Messenger | ❌ | ✅ | Awaiting Omar 2FA |

*Last updated: 2026-04-05 by Claude (session: WhatsApp reconnect UI + email verification)*

---

## 2026-04-27 — Evolution API Upgrade v2.2.3 → v2.3.7

**Goal**: Update Evolution API to latest stable version to fix any known issues and get latest features.

### What Changed
- **Old version**: `atendai/evolution-api:v2.2.3` (April 7, 2026)
- **New version**: `evoapicloud/evolution-api:v2.3.7` (December 5, 2025, pushed 2 months ago)

### Why the Change?
- The old Docker image `atendai/evolution-api` hasn't been updated in 11 months
- Official Docker repository moved to `evoapicloud/evolution-api` starting v2.3.0
- v2.3.7 includes fixes for Baileys reconnection issues, proxy integration, Chatwoot integration, and more

### Steps Taken
1. Updated `docker-compose.evolution.yml` line 33:
   - Changed image from `atendai/evolution-api:v2.2.3` to `evoapicloud/evolution-api:v2.3.7`
2. Pulled new image: `docker compose -f docker-compose.evolution.yml pull evolution-api`
3. Recreated container: `docker compose -f docker-compose.evolution.yml up -d --force-recreate evolution-api`
4. Verified startup in logs — HTTP ON: 8080, redis ready, Prisma migrations applied

### Verification
```bash
curl http://localhost:8081/instance/fetchInstances -H "apikey: {EVOLUTION_API_KEY}"
# Response: [] (empty array = healthy, no instances yet)
```

### Migration Notes
- Database migrations applied automatically (49 migrations found, no pending changes)
- Prisma client regenerated for v2.3.7
- Redis cache reinitialized
- Existing instances preserved in `evolution_instances` volume

### Next Steps
- Users need to reconnect WhatsApp via QR code if the instance format changed
- Test inbound/outbound messaging to confirm webhook delivery still works
- Monitor logs for any breaking changes in payload structure

*Last updated: 2026-04-27 by Claude (session: Evolution API upgrade)*

---

## 2026-04-05 — Meta App Review Submission (continued from 2026-03-31)

### Submission: One Inbox Business
- App ID: `1469090344742803`
- Submission ID: `1488855866099584`
- URL: `https://developers.facebook.com/apps/1469090344742803/app-review/submissions/?submission_id=1488855866099584&business_id=2169075923895403`

### Final Wizard Status

| Step | Status | Notes |
|------|--------|-------|
| التحقق (Business Verification) | ○ BLOCKED | Requires Omar to upload business docs manually via "انتقل إلى التحقق" |
| إعدادات التطبيق (App Settings) | ✅ | Website platform https://ot1-pro.com/ added |
| الاستخدام المسموح به (Permitted Use) | ○ BLOCKED | See permission table below |
| معالجة البيانات (Data Processing) | ✅ | "لا" selected for third parties, responsible party filled |
| تعليمات المراجع (Reviewer Instructions) | ✅ | 8-step walkthrough + credentials filled, https://ot1-pro.com |

### Permission Status (الاستخدام المسموح به)

| Permission | Status | Notes |
|-----------|--------|-------|
| pages_show_list | ✅ | Complete |
| pages_manage_metadata | ✅ | Complete |
| pages_utility_messaging | ✅ | Complete |
| pages_messaging | ✅ | Complete |
| business_management | ✅ | Complete |
| pages_read_engagement | ✅ | Complete |
| instagram_business_basic | ✅ | Screencast video uploaded ("instagram app review video.mp4") — saved this session |
| instagram_business_manage_messages | ✅ | Complete |
| instagram_manage_comments | ○ BLOCKED | System check: "يجب أن يشتمل الطلب المرسل على instagram_basic" — legacy permission requires instagram_basic in API calls. App uses new instagram_business_* API so this will never pass automatically. |
| instagram_manage_messages | ○ BLOCKED | Same as above — legacy permission blocked by instagram_basic dependency |
| public_profile | ✅ | Complete |

### What blocks إرسل للمراجعة

1. **التحقق** — Omar must manually go to Meta Business Verification and upload business identity documents. Click "انتقل إلى التحقق" on the summary page.

2. **الاستخدام المسموح به** — `instagram_manage_comments` and `instagram_manage_messages` are legacy permissions. Meta's system check requires `instagram_basic` to be included in API calls when requesting these permissions. The app uses the newer `instagram_business_*` API which doesn't use `instagram_basic`.
   - **Option A**: Remove these two legacy permissions from the submission (they're redundant since `instagram_business_manage_messages` is already ✅)
   - **Option B**: Accept they'll stay ○ and try submitting anyway — the wizard DID allow clicking التالي past them

### Key decisions this session
- Screencast video ("instagram app review video.mp4") was already being uploaded from a previous session — completed at 100% and saved successfully
- instagram_business_basic is now ✅ after screencast upload
- Did NOT remove any permissions (user instructed to keep all)
- The wizard allowed advancing past الاستخدام المسموح به despite ○ items — but the final summary still shows it as ○

### Next actions required (by Omar)
1. **Business Verification**: Click "انتقل إلى التحقق" on the summary page → upload business identity docs
2. **Decision on instagram_manage_comments + instagram_manage_messages**: Either remove them from submission OR proceed and see if Meta accepts the submission with those as ○
3. Once التحقق is complete → "إرسل للمراجعة" button should become active

*Last updated: 2026-04-05 by Claude (session: Meta App Review — screencast upload + instagram_business_basic ✅)*

---

## Session: 2026-04-05 — Meta App Review: instagram_basic token + test calls

### What Changed Since Last Session

The previous journal had `instagram_manage_comments` and `instagram_manage_messages` marked as BLOCKED. These have since been resolved — both now show ✅ in the Permitted Use section. The only remaining blocker in الاستخدام المسموح به is `instagram_basic`.

### Actions Taken

1. **Verified current test call counts** (الاختبار tab):
   - `instagram_basic`: مطلوب 0 من 1 من عمليات استدعاء واجهة API (0/1 — still needed)
   - `instagram_business_basic`: 141 calls (gray — may be processing, 24h delay)
   - `instagram_business_manage_messages`: 58 calls (gray)
   - `pages_show_list`: 365 calls (gray)
   - `public_profile`: 0 calls (gray)
   - `pages_read_engagement`, `instagram_manage_comments`, `business_management`: ✅ Complete

2. **Generated token with instagram_basic scope via direct OAuth navigation**:
   - Opened `https://www.facebook.com/dialog/oauth?client_id=1469090344742803&redirect_uri=...&scope=...instagram_basic...&response_type=token` in a new tab
   - Clicked through 3 OAuth consent screens (Pages selection, Instagram accounts, final summary)
   - Token auto-populated in Graph API Explorer tab 304699823
   - Token confirmed to have 8 permissions including `instagram_basic`

3. **Made 2 test API calls using the instagram_basic token**:
   - `GET /me?fields=id,name` → returned id: 3127507027458198, name: "Omar Mohamed Eltak" ✅
   - `GET /me/accounts?fields=id,name,instagram_business_account` → returned 2 pages (ELDAR, تعلم المسبقة) with instagram_business_account field ✅
   - These calls should register as test calls for instagram_basic within 24 hours

4. **Completed instagram_basic Permitted Use dialog** (بدء الاستخدام button):
   - Description: already filled from previous session ("OT1 Pro uses instagram_basic as a required dependency for instagram_manage_messages and instagram_manage_comments...")
   - Screencast: already uploaded from previous session ✅
   - API test calls: still 0/1 (pending — the 2 calls made today will show within 24h)
   - Agreement checkbox: ✅ checked and saved
   - Clicked "حفظ" (Save) — dialog closed successfully

### Current Permission Status (Updated)

| Permission | Status | Notes |
|-----------|--------|-------|
| instagram_basic | ○ PENDING | Description ✅, Screencast ✅, Agreement ✅, API test: 0/1 (calls made 2026-04-05, will update within 24h) |
| pages_show_list | ✅ | Complete |
| pages_manage_metadata | ✅ | Complete |
| pages_utility_messaging | ✅ | Complete |
| pages_messaging | ✅ | Complete |
| business_management | ✅ | Complete |
| pages_read_engagement | ✅ | Complete |
| instagram_business_basic | ✅ | Complete |
| instagram_business_manage_messages | ✅ | Complete |
| instagram_manage_comments | ✅ | Complete (was BLOCKED previously, now resolved) |
| instagram_manage_messages | ✅ | Complete (was BLOCKED previously, now resolved) |
| public_profile | ○ ? | 0 test calls shown, but may be auto-complete |

### What Blocks إرسل للمراجعة (Updated)

1. **التحقق (Business Verification)** — Omar must manually upload business identity docs. Click "انتقل إلى التحقق" on summary page.

2. **الاستخدام المسموح به** — Only `instagram_basic` remains ○. The description, screencast, and agreement are saved. The 1 required API test call was made today (2026-04-05) and will register within 24 hours.

### Next Actions Required (by Omar)

1. **After 24 hours (2026-04-06)**: Check the testing page (tab 304699812) to confirm instagram_basic shows 1+ API calls. Then re-open the instagram_basic Permitted Use dialog and click "حفظ" again — it should now show all 4 sub-items ✅.
2. **Business Verification**: Upload business identity documents via "انتقل إلى التحقق"
3. Once both are done → click "إرسل للمراجعة" to submit

*Last updated: 2026-04-05 by Claude (session: instagram_basic token generated + test calls made)*

---

## Session: 2026-04-09 — Instagram Subscription Refresh + AI Working Hours Fix

### App Review Submissions (by Omar)
- **Meta (`instagram_manage_messages`)** — App review submitted for app `1469090344742803`. Pending Meta approval. Once approved, all non-tester Instagram-via-Facebook accounts (e.g. Mishkah) will receive webhooks.
- **TikTok Business Messaging** — App review submitted for app `7614863552092014604`. Status: pending review at `developers.tiktok.com/app/7614863552092014604/pending`.

---

### Fix 1 — Instagram-via-Facebook Subscription Refresh

**Problem:** Facebook-linked Instagram pages (e.g. Mishkah University page_id=36) were never receiving incoming webhooks for two reasons:
1. `instagram_manage_messages` not yet approved → only app testers receive webhooks (Meta restriction)
2. Page webhook subscription was potentially stale after token refresh

**Root cause discovered:** `instagram_messaging` is NOT a valid `subscribed_fields` value for the `/subscribed_apps` endpoint — the Meta API rejects it with HTTP 400. Removed from all subscription calls.

**Code changes (local + prod):**
- `app/Services/Platforms/FacebookPlatform.php` — Added `refreshFacebookSubscription(Page $page): bool`. Loads the canonical Facebook `Page` record to use its own token (not the Instagram page's copy). Updates `metadata.subscription_refreshed_at` on success, `metadata.subscription_error` on failure.
- `app/Console/Commands/RefreshInstagramSubscriptionsCommand.php` — New command `instagram:refresh-subscriptions`. Loops all active Facebook-linked Instagram pages, calls refresh per page with try/catch so one bad page doesn't abort the rest. `--team=` option for targeted runs.
- `routes/console.php` — Scheduled monthly.
- Also fixed `handleInstagramViaFacebookCallback()` to not use the invalid `instagram_messaging` field.

**Verified on prod:**
```bash
php artisan instagram:refresh-subscriptions
# Found 1 Facebook-linked Instagram page(s). Refreshing subscriptions...
#   OK  [36] Mishkah University | جامعة مشكاة (FB page: 313985005290971)
# Done. Success: 1  Failed: 0
```

**Pending manual step:** Add Mishkah's Instagram account owner as an **App Tester** at `developers.facebook.com/apps/1469090344742803/roles/roles/` — required until App Review approves `instagram_manage_messages`.

**Git:** `1f64398` — `fix: refresh Facebook page subscriptions for Instagram-via-Facebook accounts`

---

### Fix 2 — AI Not Responding to Customers (isWithinWorkingHours bug)

**Problem:** AI was not auto-responding on page_id=25 (Mishkah University Facebook page) despite `team.ai_enabled=true`, `AiConfig.is_active=true`, no paused conversations, and sufficient credits.

**Root cause:** `isWithinWorkingHours()` in `app/Models/AiConfig.php` called `Carbon::between($start, $end)`. The saved config had `start: "09:00"` / `end: "08:59"` (intended as 24/7 — 9am to 8:59am next day). Since `end < start` on the same Calendar day, `between()` always returned `false`.

**Fix:** Detect cross-midnight range (`end < start`) and use `$now->gte($start) || $now->lte($end)` instead of `between()`.

**File:** `app/Models/AiConfig.php:78-82` (both local + prod)

**Verified:**
```php
// Before fix:
$aiConfig->isWithinWorkingHours() // false
// After fix:
$aiConfig->isWithinWorkingHours() // true
```

**Tested:** Opened `ot1-pro.com/inbox?pageId=25`, conversation "Omar Mohamed Eltak" shows "AI Active" ✅ in header. Tested only on Omar's own conversations (not customer chats).

**Git:** `507eff4` — `fix: handle cross-midnight working hours range in isWithinWorkingHours`

---

### Privacy / Terms Updates
- `resources/views/pages/privacy.blade.php` — Updated domain refs from `oneinbox.app` → `ot1-pro.com`, expanded TikTok Business Messaging permission descriptions (`message.list.read`, `message.list.send`, `message.list.manage`)
- `resources/views/pages/terms.blade.php` — Updated contact email/URL to `ot1-pro.com`

*Last updated: 2026-04-09 by Claude*

---

## 2026-04-06 — Fix: Instagram Incoming Messages Not Showing in Inbox (pageId=39)

### Problem
Outbound messages from One Inbox → Instagram worked fine. But inbound messages from contacts (e.g. `amdo7a` / Ahmed Mamdouh) never appeared in the inbox at `ot1-pro.com/inbox?pageId=39`.

### Root Cause
The Instagram account `omar_eltak88` was connected to **three different page records** in production:

| Page | Team | platform_page_id | is_active | Issue |
|------|------|-----------------|-----------|-------|
| 10 | 3 | `17841429680280453` | false | Old connection, deactivated |
| 13 | 4 | `17841429680280453` | true → false | **Wrong team** (team 4, not team 3). Received all webhooks. |
| 39 | 3 | `27389582010629405` | true | Correct team but **wrong webhook ID** — never matched any webhook. |

Instagram webhooks arrive with `entry.id = 17841429680280453`. This matched page 13 (team 4), so all inbound messages were saved to team 4's conversations — invisible to the user on team 3.

Page 39 had `platform_page_id = 27389582010629405` (the IGBID returned by `graph.instagram.com/me`) which never matched any webhook `entry.id`.

### Fix (production DB only, no code changes)
Executed via `php artisan tinker` on `one-inbox-prod`:

1. **Deactivated page 13** (team 4 duplicate)
2. **Moved all conversations from page 10** (3 convs) → reassigned to page 39
3. **Moved all conversations from page 13** (150 convs) → merged into matching page 39 conversations; key merge: conv 550 (4 inbound messages from amdo7a) → conv 547
4. **Deleted page 10** (now empty, freed the unique constraint)
5. **Updated page 39** `platform_page_id` from `27389582010629405` → `17841429680280453`
   - Old IGBID preserved in `metadata.igbid`

### Verification
- Conversation 547 (amdo7a, page 39) now shows 4 inbound messages correctly
- Active Instagram pages: page 36 (Mishkah University) + page 39 (Omar Mohamed Eltak), both team 3
- Future webhooks with `entry.id = 17841429680280453` will route directly to page 39 ✓

### Note on Outbound 403 Error
At 11:42 AM today, sending to amdo7a failed with Instagram error `2534022`: "message sent outside allowed period". This is Instagram's 24-hour messaging window — you can only reply within 24h of the last inbound message. Unrelated to the incoming message bug.

*Last updated: 2026-04-06 by Claude (session: Instagram incoming messages fix)*

---

## SEO Campaign — Phase 2–5 Complete
*Session: 2026-04-21 by Claude*

### What Was Built

#### Phase 2 — Platform Landing Pages (previous session)
- `resources/views/pages/whatsapp-inbox.blade.php` — target: "whatsapp business inbox"
- `resources/views/pages/instagram-dm.blade.php` — target: "instagram dm management"
- `resources/views/pages/facebook-messenger.blade.php` — target: "facebook messenger management"
- `resources/views/pages/telegram-inbox.blade.php` — target: "telegram business inbox"
- Routes: `whatsapp-inbox`, `instagram-dm`, `facebook-messenger`, `telegram-inbox`

#### Phase 3 — Comparison Pages (previous session)
- `resources/views/pages/vs/trengo.blade.php`
- `resources/views/pages/vs/manychat.blade.php`
- `resources/views/pages/vs/freshchat.blade.php`
- `resources/views/pages/vs/respond-io.blade.php`
- Routes: `vs.trengo`, `vs.manychat`, `vs.freshchat`, `vs.respond-io`

#### Phase 4 — Blog Infrastructure (previous session)
- `database/migrations/2026_04_20_091226_create_posts_table.php` — migrated ✓
- `app/Models/Post.php` — `published()` scope, meta title/description accessors
- `app/Http/Controllers/BlogController.php` — `index()` paginated + `show()` with related posts
- `resources/views/blog/index.blade.php` + `resources/views/blog/show.blade.php`
- Routes: `blog.index`, `blog.show`
- Sitemap updated to include `/blog` + dynamic published posts

#### Phase 4 — Blog Content (this session)
- `database/seeders/PostSeeder.php` — 7 SEO-targeted articles seeded
  - "How to Manage WhatsApp Business Messages at Scale" (6 min, WhatsApp Business)
  - "Best WhatsApp Business Inbox Tools in 2025" (7 min, WhatsApp Business)
  - "Instagram DM Management for Business: The Complete 2025 Guide" (5 min, Instagram)
  - "AI Sales Responder for WhatsApp: Close Deals While You Sleep" (5 min, AI Sales)
  - "Unified Social Inbox: Why Every Growing Business Needs One in 2025" (6 min, Social CX)
  - "How to Set Up a Shared WhatsApp Inbox for Your Team" (5 min, WhatsApp Business)
  - "Facebook Messenger for Business: Complete 2025 Setup Guide" (5 min, Facebook)
- Run: `php artisan db:seed --class=PostSeeder` ✓

#### Phase 5 — Industry Landing Pages (this session)
- `resources/views/pages/industries/real-estate.blade.php` — target: "whatsapp inbox real estate"
- `resources/views/pages/industries/ecommerce.blade.php` — target: "whatsapp inbox ecommerce"
- `resources/views/pages/industries/agencies.blade.php` — target: "social inbox marketing agencies"
- `resources/views/pages/industries/restaurants.blade.php` — target: "whatsapp for restaurants"
- `resources/views/pages/industries/education.blade.php` — target: "whatsapp inbox education"
- Routes: `industry.real-estate`, `industry.ecommerce`, `industry.agencies`, `industry.restaurants`, `industry.education`
- All added to sitemap (priority 0.8, monthly)
- "Industries" section added to footer in `layouts/marketing.blade.php`

### Bug Fixed — Blade @context Compilation
**Problem:** `"@context"` and `"@type"` inside JSON-LD `<script>` blocks within `@push('schema')` were parsed as Blade directives, causing `syntax error, unexpected end of file`.
**Fix:** Escaped to `"@@context"` and `"@@type"` in 14 files:
- All platform pages, comparison pages, industry pages, blog/show.blade.php, layouts/marketing.blade.php
**Command used:** `sed -i 's/"@context"/"@@context"/g; s/"@type"/"@@type"/g; s/"@id"/"@@id"/g'`

### Translation Files Updated
- `lang/en.json` — grew from ~421 → ~650 lines (all industry page strings)
- `lang/ar.json` — grew from ~421 → ~650 lines (full Arabic translations for all industry pages)

### Current Page Count
| Section | Pages |
|---------|-------|
| Platform | 4 (WhatsApp, Instagram, Facebook, Telegram) |
| Comparison | 4 (vs Trengo, ManyChat, Freshchat, Respond.io) |
| Industry | 5 (Real Estate, Ecommerce, Agencies, Restaurants, Education) |
| Blog | 7 articles live |
| Core | 6 (home, features, pricing, about, contact, privacy, terms) |
| **Total** | **~27 indexable pages** |

### Pending
- Re-submit sitemap to Google Search Console (now has 27+ pages + 7 blog posts)
- Arabic `/ar/` URL structure (current `?lang=ar` is NOT indexed separately)
- Write next batch of blog articles (target: 40 total)
- Default og:image (1200×630 branded image)

*Last updated: 2026-04-21 by Claude (SEO Phase 2–5)*

---

## Session: 2026-04-26 — Instagram Issues Diagnosis + Send Fix

### Context
User reported 4 Instagram problems:
1. mishkahuniversity IG (connected via Meta/Facebook method) — no messages arriving
2. Personal IG (direct IG login) — receives messages but outbound send fails
3. Friend's IG — sees "profile doesn't exist" (screen 1) or "Facebook Login unavailable" (screen 2) on either connection method
4. idz page — only added because Ahmed (admin) was connecting via Facebook at the same time

### Root Cause Analysis

**Problem 2 — Send fails for IG Business Login pages (CODE BUG)**

Self-heal logic in `ProcessIncomingMessage::handleMetaMessage()` (session 2026-03-31) updates `platform_page_id` to the legacy IG User ID from the webhook `entry.id`, and stores the real IGBID in `metadata['igbid']`.

`SendPlatformMessage::sendViaMetaMessenger()` was building the send URL using `platform_page_id` — which is now the legacy ID, not IGBID. `graph.instagram.com/{IGBID}/messages` requires the IGBID.

**Problem 1 — mishkahuniversity not receiving (NOT a code bug)**
Per journal 2026-04-09: `instagram_manage_messages` app review submitted but PENDING Meta approval. Until approved, only App Testers receive webhooks for IG-via-Facebook accounts. Mishkah's IG account owner must be added as a Tester, OR wait for Meta approval.

**Problem 3 — Friend errors (NOT code bugs)**
- "Profile doesn't exist" = friend has a personal IG account, not Business/Creator. Instagram Business Login only works for Business/Creator account types.
- "Facebook Login unavailable" = app is in review. Friend must accept the Tester invitation at developers.facebook.com/apps/1469090344742803/roles/roles/ before they can use the app.

**Problem 4 — idz only via Ahmed (BY DESIGN)**
For method 1 (IG via Facebook), the IG account is detected from the Facebook Page's `instagram_business_account` field. Only the Facebook Page admin can grant this. No fix possible — this is a Meta API constraint.

### Code Changes This Session

**File: `app/Jobs/SendPlatformMessage.php` — `sendViaMetaMessenger()`**
- Added `$igPageId` variable: uses `metadata['igbid'] ?? platform_page_id` for IG Business Login pages
- Build send URL with `$igPageId` instead of `platform_page_id`
- Reason: after self-heal, `platform_page_id` = legacy IG User ID; IGBID is in `metadata['igbid']`

**File: `resources/views/livewire/connections/index.blade.php`**
- Renamed "Connect via Facebook" → "Connect via Meta"
- Renamed "Add via Facebook" → "Add via Meta"

### Deploy Needed
Both files must be copied to `one-inbox-prod`:
```bash
cp app/Jobs/SendPlatformMessage.php C:/Users/NanoChip/Herd/one-inbox-prod/app/Jobs/SendPlatformMessage.php
cp resources/views/livewire/connections/index.blade.php C:/Users/NanoChip/Herd/one-inbox-prod/resources/views/livewire/connections/index.blade.php
cd C:/Users/NanoChip/Herd/one-inbox-prod && php artisan view:clear && php artisan queue:restart
```

### Platform Status (2026-04-26)
| Platform | Inbound | Outbound | Notes |
|----------|---------|----------|-------|
| Telegram | ✅ | ✅ | Working |
| WhatsApp (QR) | ✅ | ✅ | Working |
| Instagram (IG Business Login) | ✅ | ✅ Fixed | Send URL bug fixed this session |
| Instagram via Meta (mishkahuniversity) | ❌ | ✅ | Waiting for Meta app review approval |
| Facebook Messenger | ❌ | ✅ | Waiting for Omar to enable 2FA on FB account |
| Email | ✅ | ✅ | Working |

*Last updated: 2026-04-26 by Claude (session: Instagram send fix + diagnosis)*

---

## Session: 2026-04-26 (continued) — Critical IG Self-Heal Crash + Full Diagnosis

### CRITICAL BUG FOUND AND FIXED: All IG Webhooks Crashing Since 2026-04-23

**Evidence from DB investigation:**
- 30 failed jobs in `failed_jobs` table, all `ProcessIncomingMessage`
- Exception: `SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: pages.team_id, pages.platform, pages.platform_page_id`
- All 245 IG webhook_logs have `entry.id=17841429680280453` (Omar's personal IG legacy ID)
- `team_id` column on all recent IG webhook_logs is NULL → processing never completed

**Root cause chain:**
1. Page 39 (Omar's IG, `platform_page_id=17841429680280453`) was inactive
2. Webhooks arrive for Omar's IG with `entry.id=17841429680280453`
3. Primary lookup fails (inactive page filtered out)
4. Self-heal finds page 36 (Mishkah, only active IG page) → tries to set its `platform_page_id=17841429680280453`
5. Page 39 already has that ID → UNIQUE constraint crash
6. Job fails after 3 retries → ALL IG webhooks dropped for 3 days

**Fixes applied:**
1. `app/Jobs/ProcessIncomingMessage.php` — Rewrote self-heal:
   - First check any page (including inactive) with exact matching ID
   - If found inactive: reactivate it instead of patching a different page
   - Only patch first active page if no conflict exists
2. **Production DB:**
   - Page 39 (Omar IG, `platform_page_id=17841429680280453`): `is_active=1`
   - Account 9 (Instagram connected account): `is_active=1`
   - Page 40 (duplicate, different ID): `is_active=0`
3. All 3 fixed files deployed to prod, `view:clear`, `queue:restart` run

**Tester status check (Meta roles page):**
- Ahmed Mamdouh: General Tester (مُختبر) — ACCEPTED ✅ (no معلق badge)
- amdo7a (Ahmed's IG): Instagram Tester — معلق PENDING ❌ (hasn't accepted IG sub-app invite)
- omar_eltak88: Instagram Tester — ACCEPTED ✅

**Mishkah IG (page 36) has 0 conversations confirmed:**
- No webhooks for IGBID `17841406970888724` have ever arrived
- Confirmed: `instagram_manage_messages` Standard Access = Meta doesn't deliver 3rd-party IG webhooks until Advanced Access approved via App Review

**Full problems documented in `tasks/problems.md`**

*Last updated: 2026-04-26 by Claude (session: critical IG crash fix + full diagnosis)*

---

## 2026-04-27 — New IG Sub-App + Dual-Webhook Architecture

**Goal:** Replace inaccessible old sub-app `1408745007038040` with a new sub-app to fix outbound DM 403/code 10/subcode 2534022 caused by IGSID universe mismatch between two Meta apps.

**New sub-app created:** `OT1 Direct Connect`
- Parent App ID: `2908423109505861`
- Instagram App ID: `2382509022254519` (used for OAuth `client_id` and Graph API calls)
- Instagram App Secret: stored in `.env` `META_INSTAGRAM_APP_SECRET`
- Webhook URL: `https://ot1-pro.com/api/webhooks/meta-ig`
- Webhook verify token: `META_INSTAGRAM_WEBHOOK_VERIFY_TOKEN` (same value as main token by design)
- OAuth redirect URI: `https://ot1-pro.com/connections/instagram/callback`
- Subscribed fields: messages, messaging_postbacks, messaging_seen, messaging_referral, comments, live_comments, message_edit, message_reactions
- Tester accepted: `omar_eltak88`

**Webhook architecture (already in code from previous session):**
- Route: `routes/api.php` → `/meta` for main app, `/meta-ig` for sub-app (both call MetaWebhookController@handle)
- Verify: controller accepts both `META_WEBHOOK_VERIFY_TOKEN` and `META_INSTAGRAM_WEBHOOK_VERIFY_TOKEN`
- Signature check: validates against both `META_APP_SECRET` and `META_INSTAGRAM_APP_SECRET` so each app's HMAC passes
- Confirmed live: `curl https://ot1-pro.com/api/webhooks/meta-ig?hub.mode=subscribe&hub.verify_token=…&hub.challenge=test` → `200 test`

**Production .env updated:**
- `META_INSTAGRAM_APP_ID=2382509022254519`
- `META_INSTAGRAM_APP_SECRET=<new>`
- `META_INSTAGRAM_WEBHOOK_VERIFY_TOKEN=8b50f8498a4e0e4eea7f1395bb9888e2`
- `config:clear` + `queue:restart` run on both `one-inbox` and `one-inbox-prod`

**Database cleanup before reconnect:**
- Page 40 (Omar IG via OLD sub-app, IGBID `27389582010629405`): `is_active=0`
- ConnectedAccount 9, 12 (legacy IG records for Omar): `is_active=0`
- Only Page 36 (Mishkah, FB-linked IG) remains active

**Next:** Omar reconnects via Connect Direct (IG Login) → new Page record created with NEW app-scoped IGBID, mapped to webhook `/meta-ig`. End-to-end test inbound + outbound DM.

*Last updated: 2026-04-27 by Claude*

---

## 2026-04-29 — Chained 30-Day Backfill for Instagram Conversations

**Goal:** Replace eager full-history pull at IG connect time with chained paginated rate-limited backfill to handle 100k+ historical DMs without blocking queue or getting rate-limited by Meta.

### Problem

The old `SyncPageConversations` job called `FacebookPlatform::fetchConversations()` which walked ALL conversation pages until empty in a single job. For accounts with 100k+ historical DMs this would:
- Block the queue worker for extended periods
- Get rate-limited by Meta (no throttling between API calls)
- Timeout before completing

### Solution Implemented

**File: `app/Jobs/SyncPageConversations.php`** — Complete rewrite:
- New constructor signature: `(int $pageId, ?string $afterCursor=null, int $depth=0, ?string $stopAtIso=null)`
- First run: `$stopAtIso` defaults to 30 days ago in ISO8601
- Each job fetches ONE page (limit=25) of conversations via new `FacebookPlatform::fetchConversationsPage()`
- For each conversation: persists contact + ContactPlatform + Conversation + last message preview (reuses existing upsert logic)
- If conversation `updated_time < $stopAtIso` → stops chain (returns)
- If next cursor exists → re-dispatches self with 2-second delay (rate limit ~30 calls/min)
- Hard safety stop at `$depth >= 200`
- On completion: writes `metadata.backfill_completed_at` and `metadata.backfill_oldest_at` to Page

**File: `app/Services/Platforms/FacebookPlatform.php`** — Added `fetchConversationsPage()`:
- Fetches single page of 25 conversations
- Returns `['next_cursor' => string|null, 'stopped_at_iso' => string|null]`
- Checks each conversation's `updated_time` against stop threshold
- Reuses same contact resolution and upsert logic as old `fetchConversations()`
- Old `fetchConversations()` kept as `@deprecated` for reference (not called anywhere)

**Call sites updated:**
- `handleInstagramViaFacebookCallback()` line 94: `dispatch(pageId: $igPage->id)`
- `handleInstagramCallback()` line 248: `dispatch(pageId: $page->id)`

**Files changed:**
- `app/Jobs/SyncPageConversations.php` — complete rewrite
- `app/Services/Platforms/FacebookPlatform.php` — added `fetchConversationsPage()`, updated 2 dispatch calls

### Acceptance Criteria Met

- [x] Fresh connect dispatches at most 200 jobs (depth limit)
- [x] Stops when conversations older than 30 days
- [x] Each job processes ≤ 25 conversations
- [x] Existing conversations NOT duplicated (unique index on team_id, page_id, platform, platform_conversation_id)
- [x] No regression for FB Messenger (job returns early for non-Instagram pages)
- [x] No changes to config/, .env, or migrations

### Deployment

Sync both directories:
```bash
# Already done in one-inbox (current working directory)
# Copy to one-inbox-prod:
cp app/Jobs/SyncPageConversations.php C:/Users/NanoChip/Herd/one-inbox-prod/app/Jobs/SyncPageConversations.php
cp app/Services/Platforms/FacebookPlatform.php C:/Users/NanoChip/Herd/one-inbox-prod/app/Services/Platforms/FacebookPlatform.php
cd C:/Users/NanoChip/Herd/one-inbox-prod && php artisan view:clear && php artisan queue:restart
```

---

## 2026-04-29 — Phase 1: BackfillContactNameJob + Phase 2: Sync Windows

### Phase 1 — Lazy Contact Name Backfill

**Problem**: Instagram Business Login webhook doesn't include sender name. Contacts are created with null/Unknown names.

**Solution**: `BackfillContactNameJob` — single-attempt job dispatched 2 minutes after new contact creation. Fetches `/me` profile from graph.instagram.com (or graph.facebook.com) and fills in name + avatar.

**Files created**:
- `app/Jobs/BackfillContactNameJob.php` — in both dirs (tries=1, timeout=30, mirrors IG/FB profile fetch logic from ProcessIncomingMessage)

**Files modified**:
- `app/Jobs/ProcessIncomingMessage.php` (both dirs) — `findOrCreateContact()`: added dispatch of BackfillContactNameJob when new contact created for instagram/facebook with no name
- `app/Console/Commands/BackfillUnknownContacts.php` (new, both dirs) — artisan command `contacts:backfill-names {--batch=100} {--platform=}` — dispatches BackfillContactNameJob for all contacts with null/empty/Unknown name, staggered 2s apart

**Fixed this session (dev was missing)**:
- `routes/api.php` (dev): added `/webhooks/meta-ig` route (was in prod but missing from dev)

---

### Phase 2 — Sync Windows + On-Demand Range Backfill

**Problem**: No way to know which date ranges have already been fetched for a page. Campaigns need to target historical contacts, but conversations older than 30 days haven't been pulled.

**Solution**:
1. `page_sync_windows` table tracks completed date ranges
2. `PageSyncWindowService` computes gaps, merges windows, estimates conversation counts
3. `BackfillRangeJob` chained job that fetches a specific date range and marks window complete
4. `POST /api/campaigns/preview` endpoint returns gap analysis and dispatches backfill for uncovered ranges

**Files created** (both dirs):
- `database/migrations/2026_04_29_100000_create_page_sync_windows_table.php` — page_id, starts_at, ends_at, status, failure_reason
- `app/Models/PageSyncWindow.php` — Eloquent model with page() relation
- `app/Services/PageSyncWindowService.php` — gapsFor(), merge(), estimateConversations()
- `app/Jobs/BackfillRangeJob.php` — chained job, depth limit 200, creates/updates PageSyncWindow, chains with 2s delay
- `app/Http/Controllers/Api/CampaignPreviewController.php` — POST /api/campaigns/preview (auth:sanctum)
- `app/Console/Commands/SeedSyncWindowsFromMetadata.php` — one-time seeder `sync-windows:seed`

**Files modified** (both dirs):
- `app/Models/Page.php` — added `syncWindows()` HasMany relation
- `routes/api.php` — added `POST /api/campaigns/preview` route under `auth:sanctum`

**Migrations run**: both dev and prod ✅

**Key design decision**: `BackfillRangeJob` uses `fetchConversationsPage($page, $cursor, $stopAtIso)` where `$stopAtIso` = the range's `starts_at`. This stops pagination once conversations older than the requested start are hit. On completion, `PageSyncWindowService::merge()` coalesces overlapping windows.

---

## 2026-04-29 — IG Conversation Sync Root Cause Analysis + Historical Data Migration

### Problem Statement

User reported: "Omar's IG shows 7 chats while in real life it's more than 100" and "a month ago I had ALL my personal IG chats on this web app."

---

### Root Cause Found

**Two IG connection paths create separate Page records:**

| Page | platform_page_id | Source | Conversations | Status |
|------|-----------------|--------|--------------|--------|
| prod page 40 | `27389582010629405` (old IGSID) | Old sub-app `1408745007038040` in dev mode | 150 | inactive |
| prod page 39 | `17841429680280453` (IGBID) | New sub-app `2382509022254519` in live mode | 8 | active |

**Why old sync pulled 150 but new sync pulls 0–8:**
- Old sub-app was likely in **development mode** — app owner gets unrestricted API access to their own IG account regardless of `instagram_manage_messages` Standard vs Advanced Access.
- New sub-app is in **live mode** — `instagram_manage_messages` Standard Access only returns conversations with users who have explicitly authorized the app. Omar's real customers haven't authorized OT1 Pro, so the API returns 0.
- Both `graph.instagram.com/{IGSID}/conversations` and `graph.instagram.com/{IGBID}/conversations` confirmed to return 0 conversations for Omar's new app token.

**The `me/conversations` endpoint also returns 0** — confirmed the Standard Access limitation applies across all endpoint variants.

**The "174,473 contacts" on the dashboard** was a stale cached value (cache TTL 300s). Actual prod DB has 17,473 contacts, overwhelmingly from the Mishkah FB page sync in April 2026. No data was lost.

---

### Fix Applied (Production)

**Migrated 149 conversations from prod page 40 → page 39:**
```php
// In one-inbox-prod, run via tinker
DB::table('conversations')
    ->where('page_id', 40)
    ->whereNotIn('platform_conversation_id', $page39PlatformIds)
    ->update(['page_id' => 39, 'team_id' => $page39->team_id, 'updated_at' => now()]);
// Migrated: 149 | Skipped (duplicate): 1 | Result: page 39 now has 157 conversations
```

Dashboard cache cleared for teams 3, 4, 5 to reflect updated counts.

**Dev was already correct**: dev page 9 (IGBID) had 150 conversations, dev page 16 (IGSID) has 149 that are all duplicates of page 9 — no migration needed.

---

### Code Fix

`app/Services/Platforms/FacebookPlatform.php` line ~661:
- Fixed stale comment from "filterParticipant = IGSID for Direct IG Login" to correctly say IGBID for both paths.
- Applied to both `one-inbox` and `one-inbox-prod`.

---

### What Still Doesn't Work (and Why)

| Issue | Root Cause | Fix |
|-------|-----------|-----|
| New IG messages from existing contacts may create duplicate conversations | Old conversations stored with IGSID from old app; new webhooks use IGSID from new app — different values → no match | Acceptable for now; dedup can be added later |
| Mishkah IG (page 36) has only 1 conversation | `graph.facebook.com/313985005290971/conversations?platform=instagram` times out (error -2, subcode 2534084) — too many conversations + Standard Access | Needs Advanced Access approval from Meta |
| Omar IG API returns 0 conversations on fresh backfill | Standard Access: Meta only returns conversations with app-authorized users | Needs Advanced Access approval from Meta |

---

### Advanced Access Status

- `instagram_manage_messages` submitted for App Review in Meta Developer Console (app `1469090344742803`)
- User confirmed it was added ("it's there already")
- **Once Advanced Access is approved**: delete `backfill_completed_at` from page 39 metadata → dispatch `SyncPageConversations` → it will backfill all conversations from all users

```php
// Command to re-trigger backfill after Advanced Access approved:
$page = Page::find(39); // Omar's IG (prod)
$meta = $page->metadata;
unset($meta['backfill_completed_at'], $meta['backfill_oldest_at']);
$page->metadata = $meta;
$page->save();
\App\Jobs\SyncPageConversations::dispatch($page->id);
```

---

## 2026-05-05 (later) — Real root cause: rotated Instagram app secret

aalkader sent live test DMs and they didn't reach the inbox. `tail`-ing `laravel.log` showed Meta IS posting webhooks every few seconds, but every one was being rejected by `MetaWebhookController::verifySignature()` with `tried_secrets:["instagram_app_secret","app_secret"]`.

`.env` had `META_INSTAGRAM_APP_SECRET_LEGACY=a4b06ab65febb0edf47b8cf13776f2ea` left over from a prior secret rotation, but the controller never tried it. Meta is currently signing IG webhooks with the **legacy** value — so all real inbound DMs were silently dropped with 403s.

### Fix shipped (commit `8bd63da`, pushed to origin)
- `config/services.php`: expose `instagram_app_secret_legacy` and `app_secret_legacy`.
- `MetaWebhookController::verifySignature()`: try the legacy keys after the primary ones; on match log "Meta webhook verified with legacy secret '…' — promote it to the primary key in .env".

### Env swap applied to both dirs
```
META_INSTAGRAM_APP_SECRET=a4b06ab65febb0edf47b8cf13776f2ea          # was the LEGACY value
META_INSTAGRAM_APP_SECRET_LEGACY=69d75ba8ba69092d2be5bc71a178f5bd   # was the primary value
```
Then `php artisan config:clear` + `queue:restart` in both dirs. Post-swap signed-probe to `https://ot1-pro.com/api/webhooks/meta-ig` returned `200 EVENT_RECEIVED`.

### Verified live
4 real IG inbound messages from `aa.elkader80` landed seconds after the verifier fix went live — `msg#7297..7300` on conv 402 / page 39. Pending jobs: 0.

### Pushed to origin
`f444ea9..8bd63da main -> main` (3 commits: IG routing/UI, journal, signature verifier).

---

## 2026-05-13 — Super-Admin / OT AI shared-account model

### Why
Meta App Review is stalled. Workaround: one Facebook account (OT AI) becomes admin on each customer's pages. OT AI logs into our app, all pages land in OT AI's team, then a platform-owner UI hands each page off to a customer workspace.

### Changes
- `database/migrations/2026_05_13_000001_add_is_super_admin_to_users_table.php` — adds `users.is_super_admin` boolean.
- `app/Models/User.php` — fillable + cast + `isSuperAdmin()` helper.
- `app/Console/Commands/GrantSuperAdmin.php` — `php artisan super-admin:grant {email} [--revoke]`.
- `app/Http/Middleware/EnsureSuperAdmin.php` — 403 unless `isSuperAdmin()`; aliased as `super-admin` in `bootstrap/app.php`.
- `app/Livewire/SuperAdmin/Customers.php` + view — create customer (Team + owner User + password), list with page/user counts, reset password, delete.
- `app/Livewire/SuperAdmin/PageAssignments.php` + view — table of every Page with platform filter & search; per-row dropdown to a customer team. Move also reassigns conversations + (source-team-only) contacts and clears the active-pages cache for source and target.
- `routes/web.php` — `/super-admin/customers`, `/super-admin/page-assignments` under `auth, verified, team, throttle, super-admin`.
- `resources/views/layouts/app/sidebar.blade.php` — appends two nav items only when `$user->isSuperAdmin()`.

### Granted
`php artisan super-admin:grant omareltak7@gmail.com` → `id=1` on local dev DB.

### Operator flow
1. Create FB account "OT AI" → add as admin to each customer page (off-platform).
2. Log into the app as OT AI → Connections → Connect with Facebook → all customer pages import into OT AI's team.
3. Sidebar → Customers → New Customer → enter company + login.
4. Sidebar → Page Assignments → pick target team per page → Move.
5. Share the login. Customer uses existing `/settings/admins` to add their own team.

### Not done
- No "transfer back" or audit log of moves.
- Contacts with conversations spanning multiple pages stay on source team for those pages; only contacts whose `team_id` still equals the source get moved.
- Production DB still needs the migration + grant run separately.

---

## 2026-06-25 — Fix: Instagram account reappears after disconnect

### Root cause
`ProcessIncomingMessage::handleMetaMessage()` contained a "self-heal" block (lines ~141-159) that re-activated any inactive Instagram Page whenever a new webhook message arrived for it. After the user clicked Disconnect (which sets both Page.is_active=false and ConnectedAccount.is_active=false), an incoming IG DM would trigger the self-heal, setting Page.is_active=true again. Because the `connectedAccounts` Livewire query surfaces accounts with at least one active page (by design, so orphaned pages can be disconnected), the IG ConnectedAccount would reappear in the UI on next login.

### Fix
`app/Jobs/ProcessIncomingMessage.php` — self-heal block now checks `$anyMatch->connectedAccount?->is_active` before reactivating. If both the page and its connected account are inactive (= intentional disconnect), the self-heal is skipped and the message is dropped gracefully. If the account is still active but only the page is inactive (stale state), self-heal runs as before.

Applied to BOTH:
- `C:\Users\NanoChip\Herd\one-inbox\app\Jobs\ProcessIncomingMessage.php`
- `C:\Users\NanoChip\Herd\one-inbox-prod\app\Jobs\ProcessIncomingMessage.php`

### Cleanup
Ran tinker on prod DB to deactivate the lingering active page left by prior self-heals:
- Account 24 (Omar Mohamed Eltak) — had 1 active page with inactive ConnectedAccount → page deactivated.

---

## 2026-06-25 — Fix: Dashboard shows data from disconnected pages

### Root cause
`Dashboard::render()` queried all `Conversation` rows by `team_id` only, with no filter on `page_id` / active pages. After disconnecting a page, its historical conversations still counted toward every dashboard stat (messages, conversations, unread, platform breakdown, recent conversations).

Additionally, `clearActivePagesCache()` only forgot `team.{id}.active_pages` — not the `dashboard.{id}` cache — so even with a correct query, stale numbers would persist for up to 5 minutes after a disconnect.

### Fix
**`app\Livewire\Dashboard.php`** (both dirs):
- Added `$activePageIds` lookup (active pages for the team) at the top of the cache closure.
- Added `->whereIn('page_id', $activePageIds)` to: conversation stats, message stats join, platform breakdown, recent conversations.
- Contacts left unscoped (contacts are team-wide, not per-page).

**`app\Models\Team.php`** (both dirs):
- `clearActivePagesCache()` now also calls `Cache::forget("dashboard.{$this->id}")`.

**Prod cache busted immediately** via tinker — cleared `dashboard.3`, `dashboard.6`, `dashboard.8`.

## 2026-07-03 — Fix: AI Chat + Inbox 400 "parameter is invalid" from NaraRouter/Anthropic

**Symptom**
- `/ai-chat`: after user confirmed a `pending_action`, every subsequent message returned "The AI service is temporarily unavailable (API error)."
- `/inbox`: same 400 body on some conversations (e.g. `?pageId=54`).
- NaraRouter dashboard: `POST /v1/chat/completions` → `400 · Invalid`, `reason=invalid: model call rejected request`, `input_tokens=0`.

**Root cause**
Anthropic's Messages API (which NaraRouter proxies via OpenAI-compat) requires `user`/`assistant` to strictly alternate. Two consecutive turns of the same role → 400 with body `"The model rejected this request. It may not support the input you sent (e.g. images on a text-only model) or a parameter is invalid."` Per ARCHITECTURE §4 rule (4) we intentionally do NOT cascade on 400 (`callChat` bails to `''`), so the entire chain skips and the user sees "temporarily unavailable."

Two independent code paths triggered the violation:
1. `AiChat::confirmAction()` appended a second `assistant` "Done: …" turn right after the AI response turn → next admin message always failed.
2. `buildConversationHistory()` maps by direction only — any customer conversation with two outbound (AI + human agent, or campaign + AI) or two inbound messages in a row produced the same shape.

**Fix**
Single choke-point guard in `NaraRouterProvider::callChat()`:

- New public helper `NaraRouterProvider::coalesceRoles(array)` — merges consecutive same-role turns with `\n\n`, drops empty/null content, normalizes legacy `model` → `assistant`. Emits `Log::info('NaraRouter coalesced consecutive same-role turns', ['count' => N])` when it fires so we can observe it in prod logs.
- `callChat()` runs `coalesceRoles($conversationHistory)` before assembling the payload. Covers all four call sites: `generateResponse`, `scoreMessage`, `generateText`, `chatWithAdmin`.
- Kept ARCHITECTURE §4 rule (4) intact: still no cascade on 400/401/403.

**Tests**
`tests/Unit/Services/Ai/NaraRouterCoalesceTest.php` — 8 cases pinning the invariant (alternating unchanged, `[u,a,a]` merged, `[u,u]` merged, `model` normalized, empty/null dropped, empty input, all-assistant collapse). All green.

**Docs**
- `docs/ARCHITECTURE.md` §4 — new "Role-alternation invariant (load-bearing)" subsection + new "Do NOT" bullet forbidding removal of `coalesceRoles`.
- `CLAUDE.md` — new pin #9 in the non-negotiable list.

**Deploy**
Committed on `main` and pushed. Prod: `git pull`, `composer install --no-dev -o` (no-op), `php artisan config:clear && route:clear && view:clear`, `php artisan queue:restart` (no NSSM restart needed — code-only change).

**Not fixed here (deferred)**
- `AiChat::confirmAction` still visually pushes a separate "Done:" bubble. UI polish, not a bug — separate PR later.
- Underlying `NaraRouterProvider::$apiKey` type nullability weirdness (surfaced in test bootstrapping). Cosmetic, not shipping.

## 2026-07-03 (later) — Fix: AI-vs-human reactivation loop (auto-re-flag as spam)

**Symptom**
On `/inbox?pageId=15`, contact "H h a h" conv 699:
1. Customer sends noise messages ("hi", ".", "?", "..") → AI auto-flags as spam
2. Operator clicks "Reactivate (resume AI)" — clears flag, sets `metadata.reactivated_at`
3. Customer sends next message → AI re-classifies from unchanged history → auto-re-flags
4. Infinite loop; operator's decision is undone every ~30 seconds

**Root cause**
`SendAiResponse::handle` at line 163-175 unconditionally acts on `[SPAM_DETECTED]`. Reactivation cleared `sales_stage`/`ai_paused` but left the conversation history intact — the exact messages that triggered the classifier are still there on the next call. `metadata.reactivated_at` was written by `Inbox\Index::changeStage` but never consulted by the AI pipeline.

**Fix (two layers, defensive + preventive)**

Layer 1 — defensive, in `SendAiResponse::handle`:
- Before the spam-flag `update()`, check `data_get($conversation->metadata, 'reactivated_at')`.
- If set: log "AI wanted to re-flag conversation X as spam, but a human operator reactivated it — suppressing" and `return` early.
- No re-flag, no re-pause, no marker sent to customer. Silent skip breaks the loop.

Layer 2 — preventive, in `BuildsConversationPrompts::buildSystemPrompt`:
- When `metadata.reactivated_at` exists, append a "CONTEXT — HUMAN OVERRIDE ACTIVE" clause to the ABUSE DETECTION block.
- Tells the AI: operator judged this customer legitimate; do NOT emit `[SPAM_DETECTED]` unless the LATEST message contains explicit slurs/threats; ignore the earlier noisy history.
- Reduces false positives; the defensive guard in layer 1 is what actually enforces the invariant.

**Tests**
`tests/Feature/SendAiResponseSpamGuardTest.php` — 3 cases:
1. Without reactivation → spam marker → conversation auto-flagged (regression guard for baseline behavior).
2. With reactivation → spam marker → conversation stays active, no outbound message, no metadata change (the pin).
3. With reactivation → normal reply → outbound message still sent (rules out over-suppression).

All 3 green. Also re-ran the 8 coalesce unit tests — still green.

**Docs**
- `docs/ARCHITECTURE.md` §9 — new "Reactivation loop (load-bearing)" subsection + new Do-NOT bullet.
- `CLAUDE.md` — new pin #10.

**Deploy**
Committed on `main`, pushed. Prod: `git pull`, `php artisan config:clear && route:clear && view:clear`, `php artisan queue:restart` (code-only, no NSSM restart needed).

**Deferred / open**
- If AI keeps emitting `[SPAM_DETECTED]` on a reactivated conversation across many messages, we currently silently skip every reply — customer sees nothing until the AI decides otherwise. Not a bug per se (operator can manually re-mark or unassign), but worth watching. If it becomes a real UX issue, we can escalate the conversation to a human after N consecutive suppressions.

---

### Session: 2026-07-08 — Cloud Deployment to Hostinger VPS

**Goal**: Move production from Windows (Herd + Cloudflare Tunnel) to a real VPS.

---

#### Decisions Made

- **Provider**: Hostinger KVM 2 (2 vCPU / 8 GB / 100 GB NVMe, Germany) — chosen because it accepts Apple Pay (only available payment method). ~£6.79/mo, 2-year plan.
- **No Laravel Forge**: Stripe on Forge doesn't show Apple Pay on Windows/Chrome. Switched to fully self-managed nginx + PHP-FPM + MySQL 8 + Redis.
- **WhatsApp deferred**: Evolution API / Wuzapi scope removed from this deployment. Saves a whole server and class of complexity.
- **Object storage**: Cloudflare R2 deferred (not needed yet — app uses local storage for now).
- **Auto-deploy**: GitHub Actions (`appleboy/ssh-action`) instead of Forge deploy hooks.

---

#### Server: `187.77.67.94` (Hostinger KVM 2, Germany)

**OS**: Ubuntu 24.04.4 LTS
**deploy user**: `deploy` (non-root, sudoers for `systemctl reload php8.4-fpm`)

**Stack installed (as root)**:
```bash
add-apt-repository ppa:ondrej/php
apt install php8.4-fpm php8.4-cli php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-mysql php8.4-redis php8.4-zip php8.4-bcmath php8.4-gd \
  nginx mysql-server-8.0 redis-server nodejs npm certbot python3-certbot-nginx ufw
```

**MySQL**: DB `one_inbox`, user `deploy`@`localhost`, password stored at `/root/db_password.txt` on server.

**UFW**: ports 22, 80, 443, 8080 open.

---

#### Application Setup

- Repo cloned to `/var/www/ot1-pro.com` via `git clone git@github.com:OmarEltak/one-inbox.git`
- Deploy user SSH keypair generated on VPS at `~/.ssh/id_ed25519` — public key added as GitHub deploy key at github.com/OmarEltak/one-inbox/settings/keys
- `.env` SCP'd from laptop (`C:\Users\NanoChip\Herd\one-inbox\.env`), then patched:
  - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ot1-pro.com`
  - `DB_CONNECTION=mysql`, host/db/user/password added (was sqlite-only in dev .env)
- `composer install --no-dev --optimize-autoloader` ✅
- `npm ci && npm run build` ✅
- `php artisan migrate --force` ✅ (all migrations up)
- `php artisan storage:link` ✅
- `php artisan config:cache && route:cache && view:cache` ✅

---

#### nginx

Vhost at `/etc/nginx/sites-available/ot1-pro.com` → symlinked to `sites-enabled`.
Root: `/var/www/ot1-pro.com/public`, fastcgi to `unix:/var/run/php/php8.4-fpm.sock`.

---

#### SSL

```bash
certbot --nginx -d ot1-pro.com -d www.ot1-pro.com --non-interactive --agree-tos -m it@mishkahu.com
```
Certificate valid until 2026-10-05. Auto-renew via certbot systemd timer.

---

#### Systemd Services

`/etc/systemd/system/one-inbox-queue.service` — queue worker, `Restart=always`
`/etc/systemd/system/one-inbox-reverb.service` — Reverb on port 8080, `Restart=always`
Both enabled and started. Check with `systemctl status one-inbox-queue one-inbox-reverb`.

**Scheduler**: crontab for `deploy` user:
```
* * * * * cd /var/www/ot1-pro.com && php artisan schedule:run >> /dev/null 2>&1
```

---

#### GitHub Actions Auto-Deploy

File: `.github/workflows/deploy.yml`
Trigger: push to `main`
Action: `appleboy/ssh-action@v1` SSHs as `deploy` → git pull → composer → npm build → migrate → cache → queue:restart → reload php-fpm.

**Secrets set in GitHub** (github.com/OmarEltak/one-inbox/settings/secrets/actions):
- `DEPLOY_HOST` = `187.77.67.94`
- `DEPLOY_KEY` = deploy user's private key (`~/.ssh/id_ed25519` on VPS)

**Fix required**: VPS-generated keypair public key wasn't in `authorized_keys`. Fixed by appending `id_ed25519.pub` to `~/.ssh/authorized_keys`. First successful deploy: run #28900764059, 24s.

---

#### DNS (Cloudflare)

- Deleted old Tunnel CNAME record for `ot1-pro.com`
- Added `A ot1-pro.com → 187.77.67.94`
- Added `A www.ot1-pro.com → 187.77.67.94`
- Both records set to **Proxied** (orange cloud) — Cloudflare sits in front for DDoS protection.

---

#### Project Topology Update

Production is now **VPS-hosted**, not Windows. Old topology (Herd + Cloudflare Tunnel) is gone for prod.

| Item | New Value |
|------|-----------|
| Production app | `/var/www/ot1-pro.com` on VPS `187.77.67.94` |
| Production served by | nginx + PHP 8.4-FPM on Ubuntu 24.04 |
| Production DB | MySQL 8.0, db `one_inbox`, user `deploy` |
| Queue worker | systemd `one-inbox-queue` |
| WebSocket (Reverb) | systemd `one-inbox-reverb`, port 8080 |
| Scheduler | crontab every minute |
| Deploy | GitHub Actions push-to-main auto-deploy |
| CDN / proxy | Cloudflare (proxied) |
| SSL | Let's Encrypt via certbot |

**No longer needed for prod**: Windows cloudflared tunnel, NSSM services for prod, `one-inbox-prod\` folder.

---

#### Open Items

- Register `LEMONSQUEEZY_WEBHOOK_SECRET` in Lemon Squeezy dashboard (already in `.env`)
- Submit Meta App Review for Facebook/Instagram Live Mode
- Stop Windows cloudflared tunnel (no longer routes prod traffic)

---

### Session: 2026-07-14 — Managed onboarding: honest UX + 24h retry + super-admin notify

**Problem (customer-reported):** Connection card promises "we'll set up your page within 1 minute" but the OnboardingAutomator hard-rejects the moment super-admin's `/me/accounts` doesn't include the customer's page. That happens whenever the customer added `omarEltak88` as Page admin but omarEltak88 hasn't clicked "Accept" on Facebook yet. Customers saw the wrong error: *"you didn't add us as admin"* — blaming them for a step they had already completed. Root cause: FB Page role invitations are NOT auto-accepted, and Meta exposes no public Graph API to auto-accept them (deprecated `manage_notifications`, no `/page_invitations/{id}/accept` endpoint).

**Approach:** Since we cannot auto-accept on the FB side, do two things:
1. Instantly notify super-admin (omareltak7@gmail.com) the moment a customer submits, with a deep-link to `business.facebook.com/latest/settings/pending_requests` — so acceptance is minutes-not-hours latency.
2. Retry `OnboardingAutomator` on a 24h schedule (15min → 1h → 6h → 24h) before ever hard-rejecting. If super-admin accepts the invite during that window, the next retry succeeds and the customer gets a success email instead of a wrong-blame rejection.

**Files changed:**
- `app/Mail/NewOnboardingRequestSubmitted.php` (new) — mailable for instant super-admin notification.
- `resources/views/mail/new-onboarding-request.blade.php` (new) — email template with deep-link + acceptance checklist.
- `app/Livewire/Connections/Index.php` — dispatches new mailable inside `submitOnboardingRequest`; also updates the flash success copy from "within 1 minute" to honest "few hours during business hours" wording.
- `app/Jobs/AutoProcessOnboardingRequest.php` — added `$attempt` constructor param, passes it into automator.
- `app/Services/Meta/OnboardingAutomator.php` — added `RETRY_SCHEDULE_MINUTES` const (attempts 2–5 at 15m/1h/6h/24h); when no candidate page is found, dispatches next retry with `delay()` instead of hard-rejecting; only rejects after final attempt with an honest, three-cause explanation (including Meta Business Suite mobile-app hint).
- `resources/views/livewire/connections/index.blade.php` — replaced both "within 1 minute" strings (managed-onboarding banner + request modal footer) with honest expectation copy.
- `lang/ar.json` + `lang/en.json` — added translation entries for the two new customer-facing strings. Old "1 minute" and "24 hours" keys are now orphaned (harmless).

**What we did NOT do:**
- Did NOT add WhatsApp integration for super-admin ping (email is enough for MVP; WA can be added later if latency really matters).
- Did NOT poll Facebook for pending invitations (no reliable public Graph endpoint since v2.4 deprecation).
- Did NOT touch `$metaVerified` flag, OAuth flow, or `superAdminFacebookAccount()` lookup — orthogonal to this change.

**Config used:** `services.meta.managed_onboarding_notify` (already existed, defaults to `omareltak7@gmail.com`).

**Kill switch:** `MANAGED_ONBOARDING_AUTO=false` in prod `.env` still disables the whole pipeline including retries — no new env var introduced.

**Verification:** `php -l` clean on all 4 changed PHP files; JSON parse-check clean on both translation files. Behavior verification requires an end-to-end onboarding submission on prod (customer submits → email arrives → super-admin accepts → retry succeeds → page assigned). Recommended to smoke-test on the next real onboarding request.

**Follow-ups worth tracking:**
- If Meta app review passes → this whole pipeline becomes dead code (customers OAuth directly). See `$metaVerified` and CLAUDE.md §1.
- Consider adding a WhatsApp notification (`+201026361218`) as a second channel if email latency proves too slow in practice.
- Consider surfacing "retry in progress" status to customers in `/super-admin/onboarding-requests` UI so they can see the automator is still working, not silently stuck.

---

## 2026-07-21 — Meta verification investigation + full SEO push (Weeks 1-4)

### Meta app OAuth failure — root cause identified

**Problem:** After business portfolio verification completed, non-admin accounts still saw "Feature unavailable: Facebook Login is currently unavailable for this app" during OAuth. Flipping `META_APP_VERIFIED=true` in prod `.env` didn't help; had to roll back.

**Diagnosis (via developers.facebook.com):** Meta verification is TWO milestones, not one. Business portfolio verification (which we just got) is only the prerequisite. Each individual permission (`public_profile`, `email`, `pages_show_list`, `pages_messaging`, `pages_manage_metadata`, `pages_read_engagement`, `instagram_basic`, `instagram_manage_messages`, `business_management`) must be separately submitted for App Review and receive "Advanced Access". All 13 required permissions currently show "جاهز للاختبار" / "Ready to Test" (Standard Access only = admins/testers only). That's why non-admin OAuth 5xx's.

**Files changed:**
- `CLAUDE.md` — pin #1 rewritten with the 2-milestone correction, table of required permissions, verification checklist, and rollback command. This was the highest-priority preservation task since the original pin actively misleads future sessions into repeating the same failed flag flip.

**Next step for Omar:** submit the ~9 core permissions for App Review with screencasts + reviewer instructions. Only after each shows "Advanced Access" is it safe to flip `META_APP_VERIFIED=true`.

### Full 4-week SEO push shipped

Ran the diagnostic → plan → execution cycle based on Search Console data (avg position 55.5, 169 impressions/wk, 1 click).

**Week 1 (commit 28cb37c):** Rewrote `/blog/respond-io-pricing-explained-2026` (position 17.6) from ~600 to ~1,680 words. Added regional WhatsApp cost table, 4 real annual cost scenarios, 10-question FAQ, outbound links to Meta docs / G2 / Respond.io ToS. Shipped `BlogController::PRIORITY_SLUGS` internal-linking algorithm: 2 hand-picked slugs get injected into every blog post's related section = ~240 new internal links pointing at 3 SEO targets. Full rationale documented in memory (`seo_priority_slugs_pattern.md`).

**Week 2 (commit d6998a5):** Rewrote `/blog/what-is-unified-inbox-complete-guide-2026` (~700 → 2,000 words) and `/blog/unified-inbox-vs-shared-inbox-vs-team-inbox-difference` (~650 → 2,100 words). Both are the top-2 impression pages after respond-io-pricing. Added 8-channel matrix, ROI math, 5 real scenarios, decision framework, 10-Q FAQ each.

**Week 3 (commit 4a59672):** Shipped 8 programmatic vertical landing pages at `/unified-inbox-for-{role}` for engineering-managers / sales-teams / support-teams / agencies / customer-success-teams / devops-teams / hr-teams / marketing-teams. Validated by Search Console showing "unified inbox for engineering managers" at 22 impressions/day with near-zero competition. Implementation:
- `app/Http/Controllers/VerticalLandingController.php` — one controller with static ROLES config array (source of truth), `Cache::remember` per role for 6h
- `resources/views/pages/vertical-landing.blade.php` — single Blade template driven by config
- `routes/web.php` — route with regex constraint (`->where('role', 'engineering-managers|sales-teams|...')`), sitemap iterator auto-includes all 8
- Each page: literal-keyword H1, 3 metrics, 4 pain points, 6 use cases, 4-Q FAQ with `FAQPage` schema, cross-links to 7 other role pages with keyword anchors (56 total new internal links across the cluster)

**Week 4 (commit e7c04d2):** Backlinks launch kit shipped as `tasks/backlinks-launch-kit.md` — ready-to-paste copy for Product Hunt (tagline, first-comment, categories), Show HN (3 titles + body), 6 directory submissions (SaaSHub, AlternativeTo, StackShare, G2, Capterra, TAAFT), Reddit strategy, niche community outreach. Requires human execution — accounts, timing, upvote coordination.

**Content batches also shipped this session:**
- Batch 12 (commit 38bc0d2): 10 blogs filling the Phase 4 gap topics — `automate-whatsapp-replies-human-touch`, `whatsapp-broadcast-vs-groups-sales`, `instagram-lead-generation-dm-automation`, `best-instagram-dm-tools-2026`, `instagram-shopping-dm-automation-funnel`, `build-social-crm-from-scratch`, `social-media-customer-service-playbook`, `ai-sales-chatbots-what-works-2026`, `qualify-leads-whatsapp-instagram-ai`, `whatsapp-sales-scripts-that-convert`. Also reconciled `tasks/seo-plan.md` — the Phase 4 checklist was heavily stale, most items were already shipped.
- Batch 13 (commit 1fc9419): 10 blogs on the AI closing sales cluster targeting specific keywords per post — `how-ai-closes-sales-guide`, `ai-sales-closer-deploy-guide`, `ai-objection-handling-scripts`, `conversational-ai-for-sales`, `ai-follow-up-sequences-cadence`, `ai-sales-negotiation-guide`, `ai-cold-outreach-book-meetings`, `ai-sales-assistant-vs-sdr`, `ai-voice-agents-sales`, `ai-sales-funnel-automation`.

**Pattern learned:** `<<<HTML` (interpolated heredocs) silently mangle content with `$X`, `$0.30`, `$79/mo` because PHP tries to interpolate those as variables. Switched all new content seeders to `<<<'HTML'` (nowdoc) + `{{CTA}}` placeholder + `str_replace` in `run()`. Documented in memory (`seo_content_seeder_conventions.md`).

### Small home-page copy edit
Temporary hero swap to "OT Pro" during Meta app review — reverted on same day once app was flagged verified (though see Meta OAuth issue above for the follow-up correction). Commits: `3ce69ea` (swap in), `49509ea` (revert).

### Verification results
- All 4 SEO weeks: URLs return 200 on prod, content renders, schema validates.
- Priority-slugs pattern working: `curl` on a random blog post confirmed `respond-io-pricing-explained-2026` and `what-is-unified-inbox-complete-guide-2026` in the related-articles section.
- 8 vertical landing pages: all 200 on prod, invalid role slugs return 404 (route constraint working).
- Sitemap: 8 new URLs present.
- Meta rollback: `config('services.meta.app_verified')` confirmed `false` on prod after `sed -i '/^META_APP_VERIFIED=/d' .env && php artisan config:cache`.

### Follow-ups worth tracking
- Watch Search Console weekly for the 3 priority slugs — expected position improvement 10-20 within 30 days from the content rewrites + internal linking compound.
- After Meta approves the ~9 core permissions (Advanced Access), flip `META_APP_VERIFIED=true` — but only then, per corrected CLAUDE.md pin #1.
- Execute the Product Hunt launch when Omar picks a Tuesday/Wednesday date. Materials ready in `tasks/backlinks-launch-kit.md`.
- Batches 12/13 URLs still need Search Console indexing submission (Days 8 & 9 in `tasks/seo-indexing-plan.md`).

## 2026-08-12 — Meta App Review resubmission (round 2)

### Context
Round 1 (submitted 2026-08-11 19:40 GMT+3) got rejected on 11 of 12 requested permissions. Only `public_profile` approved. Every rejection cited Policy 1.6 with the same boilerplate: "screencast fails to show end-to-end experience of the use case." Reviewer notes on the messaging permissions were specific: need to see (1) asset selection, (2) live send action from app UI, (3) delivered message in native Messenger/Instagram client on a phone.

### Diagnosis
Not an app problem. Screencast-quality problem. Original videos likely skipped the FB OAuth flow or didn't cut to the phone showing the delivered message.

### Strategy for round 2
Chose the tight-resubmission path: only submit permissions we can demonstrate cleanly with the three new videos Omar already recorded. Dropped `pages_utility_messaging` (no template feature), `pages_manage_metadata` (no Page-subscription settings UI to demo), `instagram_manage_comments` (no comment moderation feature). Kept the 8 permissions where the video coverage is real:
- `pages_show_list`, `pages_read_engagement`, `business_management`, `pages_messaging` — covered by `Connect a Facebook Page.mp4` and `Reply to a Facebook Messenger conversation.mp4`
- `instagram_basic`, `instagram_manage_messages` — covered by `Reply to an Instagram DM.mp4` via Facebook Login for Business path
- `instagram_business_basic`, `instagram_business_manage_messages` — covered by same IG DM video because the app actually offers a separate Instagram Business Login flow (correction — earlier draft had said "no separate IG login" which was wrong)

### New info about the app that came out of this session
- OT1 Pro has TWO Instagram onboarding paths: (a) Facebook Login for Business with linked IG account (feeds `instagram_basic`, `instagram_manage_messages`), (b) direct Instagram Business Login (feeds `instagram_business_basic`, `instagram_business_manage_messages`). This wasn't obvious from the codebase read.
- `accountformetaappreview@gmail.com` is Omar's dedicated Facebook reviewer account. Same password as email. This is the Facebook account he wants Meta reviewers to use for OAuth testing. It has his personal Pages connected — do NOT touch or suggest disconnecting them.

### Submission form work done in this session
Wrote clean copy-paste text for every field of the resubmission for all 8 permissions plus the "Provide your website for review" section. All descriptions include: (1) product context, (2) exact permission usage, (3) "Response to previous rejection" paragraph that literally addresses the reviewer's rejection reason with the 3-part fix, (4) how-to-test steps using `accountformetaappreview@gmail.com` as both the OT1 Pro login AND the Meta OAuth credential. Data Handling questions filled: processor=Yes (Hetzner, Gemini API, Cloudflare listed as processors), controller = Omar Mohamed / Egypt, no national-security requests, minimum-viable public-authority policies checked (mandatory legality review + data minimization).

### Pre-submission checklist (must be done before hitting Submit)
- [ ] Verify `accountformetaappreview@gmail.com` can actually log into OT1 Pro at ot1-pro.com (create the user if not).
- [ ] Add `accountformetaappreview@gmail.com` as a Tester in the app's Roles (الأدوار) — required per Meta's own warning for `pages_messaging`.
- [ ] Confirm the linked Instagram Business account on that Facebook account is set up for both OAuth paths.
- [ ] Videos are already uploaded to their respective permission slots.

### Open question flagged to Omar
Whether to flip `META_APP_VERIFIED=true` during the review window so reviewers can hit the direct OAuth button. Trade-off: real customers who try to add NEW connections during the ~7 business day review would hit the broken OAuth callback (per CLAUDE.md pin #1). Existing customer connections are unaffected either way. Recommended keeping it `false` and flipping to `true` only after each permission shows "Advanced Access" — matching CLAUDE.md pin #1's stated policy. Reviewers use their internal Meta tester accounts which bypass the "unverified app" callback rejection, so keeping the flag `false` should not block them from testing.

### Follow-ups
- Wait 3-7 business days for Meta's decision on the 8 permissions.
- If any of the two `_business_` variants get rejected specifically for the login-flow mismatch, next round should include a dedicated screencast of just the Instagram Business Login flow (not intermixed with FB Login).
- Once at least the 6 non-`_business_` permissions show Advanced Access, flip `META_APP_VERIFIED=true` on prod per the rollback command in CLAUDE.md pin #1.


---

## 2026-08-23 — WhatsApp End-to-End Fix on Prod (Wuzapi Gateway Deployed)

**Symptom** (verbatim): "our whatsapp feature has never connected and worked at all"

### Root cause diagnosis (5 stacked blockers)

Evidence-first check found *five* independent reasons WhatsApp had never worked, each one alone would block the flow:

1. UI: `resources/views/livewire/connections/index.blade.php:324-332` rendered a hard-coded "temporarily disabled while we rebuild the gateway" placard — **no Connect button existed at all**. The `WhatsAppQrModal` Livewire component was silently mounted at line 1035 with nothing to dispatch `open-whatsapp-qr`.
2. Prod `.env` had zero `WUZAPI_*` vars → `EvolutionApiService::isConfigured()` returned false → any Connect call would short-circuit.
3. Prod VPS had **no Docker installed** → Wuzapi container had never run.
4. `WUZAPI_QR_ENABLED` config flag defaulted to false (feature gate that was never flipped).
5. Alternative Cloud API modal existed but its trigger button was also removed when the placard was added.

Also relevant: local `.env` had `WUZAPI_WEBHOOK_URL=https://ot1-pro.com/...` (pointing at prod, not local Herd) — explains why local scan attempts, if they ever succeeded, would have delivered webhook messages to prod.

Also: `EvolutionApiService` is a misleading class name — it was rewritten to call Wuzapi (whatsmeow-based, Go) while keeping the class + method names for backwards compat. Active compose file is `docker-compose.wuzapi.yml`, NOT `docker-compose.evolution.yml`.

### What was done (in order, all on prod VPS 187.77.67.94 unless noted)

1. **Local**: edited `resources/views/livewire/connections/index.blade.php` — replaced the "temporarily disabled" placard (WhatsApp card) with a green "Connect via QR (Beta)" button that dispatches `open-whatsapp-qr`, plus a secondary "Connect via Cloud API (Advanced)" that opens the existing `whatsapp-connect` Flux modal. Both gated behind `config('services.wuzapi.qr_enabled')`. Committed as `46f1e43` and auto-deployed.
2. **VPS**: installed Docker via `curl -fsSL https://get.docker.com | sh` (official convenience script; Ubuntu 24.04's `docker.io` package doesn't ship `docker-compose-plugin`). Result: Docker 29.7.2, Compose v5.5.0, daemon active.
3. **VPS**: generated fresh Wuzapi secrets — `WUZAPI_ADMIN_TOKEN` (64 hex chars), `WUZAPI_GLOBAL_ENCRYPTION_KEY` (32 hex), `WUZAPI_GLOBAL_HMAC_KEY` (32 hex). Appended a `# --- Wuzapi block ---` section to `/var/www/ot1-pro.com/.env` with all `WUZAPI_*` vars including `WUZAPI_QR_ENABLED=true`, `WUZAPI_URL=http://127.0.0.1:8083`, `WUZAPI_HOST_PORT=8083`, `WUZAPI_WEBHOOK_URL=https://ot1-pro.com/api/webhooks/wuzapi`. Preserved with `.env.bak.{ts}` backup. `chmod 600 .env`. Ran `php artisan config:cache`.
4. **VPS**: booted Wuzapi via `docker compose -f docker-compose.wuzapi.yml up -d`. Migrations applied cleanly (12 whatsmeow schema migrations). Container listens on port 8080 internally.
5. **Port conflict on 8082** on first boot (docker-proxy leftover from failed create attempts) → moved prod bind to port 8083 via `WUZAPI_HOST_PORT` env var. Local dev continues to use 8082.
6. **CRITICAL security fix**: Docker publishes ports by inserting rules into the DOCKER-USER iptables chain, which **bypasses UFW**. On first boot, the compose file's bare `- "8082:8080"` bound to `0.0.0.0` and an external `curl http://187.77.67.94:8082/admin/users` returned `HTTP 401` (the Wuzapi auth challenge — proving reachability). Fixed by:
   - `sed`'ing the base compose file on prod to `- "127.0.0.1:8083:8080"` (loopback-only) and recreating the container.
   - Committed the tracked-file fix as `a9bda97`: compose now uses `"${WUZAPI_HOST_BIND:-127.0.0.1}:${WUZAPI_HOST_PORT:-8082}:8080"` so loopback is the *default* going forward (dev is safer too).
   - After fix: external test `HTTP 000` (unreachable) on both 8082 and 8083; local `HTTP 200` on 8083. Verified.
7. **Browser E2E test (Phase A — pairing infrastructure)**: navigated Chrome to `https://ot1-pro.com/connections` → "Connect via QR (Beta)" button rendered → click → modal opened → click "Generate QR Code" → Livewire called `EvolutionApiService::createInstance()` → Wuzapi created tenant `team_2_U6ztSsdS` (id `b52724fe...`) → QR PNG rendered in modal. Full pairing infrastructure proven working, awaiting scan.

### Isolation guarantees (per user rule "if WhatsApp breaks, don't affect other features")

- New systemd services: `docker.service` + one wuzapi container. Zero touch to `nginx`, `php8.4-fpm`, `mysql`, `redis`, `one-inbox-queue`, `one-inbox-reverb`.
- All new code paths gated by `config('services.wuzapi.qr_enabled')`. If Wuzapi is offline the button doesn't render; WhatsApp card degrades to showing existing connections + Cloud API path only.
- No changes to `Team::canDispatchAi()`, `NaraRouter`, spam pipeline, or FB/IG/Telegram/Email code.
- New webhook route `/api/webhooks/wuzapi` is separate from all other webhook endpoints.

### Key locations (append to top table on next journal edit)

| Key | Where to find it |
|-----|-----------------|
| `WUZAPI_ADMIN_TOKEN` (prod) | `/var/www/ot1-pro.com/.env` — in `# --- Wuzapi block ---` |
| `WUZAPI_GLOBAL_ENCRYPTION_KEY` / `HMAC_KEY` (prod) | Same block. **CRITICAL: never regenerate** — every paired user's device store is encrypted with these; changing them = every user must re-scan QR. |
| Wuzapi HTTP endpoint (prod) | `http://127.0.0.1:8083` — loopback only, never exposed to public. |
| Wuzapi compose files | `docker-compose.wuzapi.yml` (tracked, secure defaults). No override file needed. |

### Follow-ups still pending

- **Phase B (inbound message webhook test)**: after successful QR pair, send yourself a WhatsApp message to the connected number and verify it lands in the inbox via `/api/webhooks/wuzapi` → `ProcessIncomingMessage::processWuzapi()`.
- **Local dev**: `WUZAPI_WEBHOOK_URL` in local `.env` points at prod; to test webhook locally, set it to the ngrok tunnel URL. Not blocking prod.
- **Consider**: renaming `EvolutionApiService` → `WhatsAppGatewayService` for clarity (deferred to avoid churn during rollout).


---

## 2026-08-23 (continued) — WhatsApp End-to-End Full Fix (post-rollout stabilization)

**Context:** After the initial Wuzapi rollout got the QR pair working, five follow-on bugs surfaced when the paired phone actually started receiving real messages. All are fixed and live in commits `482a0ba → 6bf0c93`.

### Bugs found & fixes shipped (in order)

1. **Race: reconnect nukes freshly paired tenant** (`482a0ba`)
   - Sequence: PairSuccess → Laravel `saveConnection` writes DB → `refreshWaStates` queries Wuzapi → Wuzapi's `jid` write is async & hasn't propagated → the just-paired tenant is filtered out of `fetchConnectedInstanceNames` → UI shows the fresh pair as "Disconnected" → user clicks "Reconnect" → `reconnectGateway` calls `deleteInstance` on the still-live paired tenant → session dies.
   - Fix: `Connections\Index::reconnectGateway` now checks `$account->connected_at->gt(now()->subSeconds(90))` before touching `deleteInstance`. Fresh accounts are left alone; the modal just reopens for a new scan. Same 90s grace also added to `isGatewayAccountActive()` for the UI badge.

2. **Race: closeModal nukes freshly paired tenant** (`a063b9a`)
   - Symptom: even with the reconnect fix, `POST /session/logout` was still firing ~7s after PairSuccess. Cause: `WhatsAppQrModal::cleanupInstance()` fires from `closeModal` if the Livewire snapshot the client posted back still had `status='qr_pending'` (client saw stale state while server had already advanced to `'connected'`).
   - Fix: `cleanupInstance()` short-circuits if `ConnectedAccount` with `metadata->gateway_instance = $this->instanceName` exists with `connected_at` in the last 90s. DB is now the authoritative "was this paired?" signal, not the possibly-stale Livewire snapshot.

3. **Payload shape mismatch: no inbound message ever created** (`4ff846f`)
   - Wuzapi upgrade changed the outbound envelope from `{event, instance, data}` to `{instanceName, userID, jsonData: "..."}` where `jsonData` is a JSON string containing `{type, event: {Info, Message}}`. `processWuzapi` was reading the old shape, so every real message landed as `event_type=wuzapi.unknown` and the first `if ($event !== 'Message') return;` bailed out. Also field paths for Sender/IsFromMe/IsGroup moved from `Info.MessageSource.*` (Baileys) to `Info.*` (whatsmeow).
   - Fix: `WuzapiWebhookController::handle` parses `jsonData` and normalises into the shape `processWuzapi` expects (raw envelope preserved in `payload.raw` for debugging). `processWuzapi` reads Sender/IsGroup directly on Info with a fallback to the legacy MessageSource nesting.

4. **Channel/broadcast pollution + wrong-TZ timestamps** (`8521e5a`)
   - `120363XXX@newsletter` (WhatsApp Channels) subscribed by the paired phone were being turned into "conversations" in the inbox. Added a suffix guard for `@newsletter`, `@broadcast`, `@g.us` in `processWuzapi`.
   - `Info.Timestamp` from Wuzapi arrives as ISO 8601 with a `+03:00` offset. `Carbon::parse` preserved the offset, Eloquent serialised the local wall time — so `platform_sent_at` was 3h ahead of the UTC `created_at`. Inbox displayed inbound messages at "6:39 PM" for what was actually 15:39 UTC, so they sorted AFTER subsequent outbound messages. Fix: `->setTimezone('UTC')` before persisting.
   - Retroactive cleanup: deleted 4 channel-garbage conversations from page 18 (ids 893, 895, 896, 897) + rewound `platform_sent_at` by 3h on 7 previously-mis-stored messages.

5. **AI replies bypassed the gateway_mode gate** (`6bf0c93`)
   - `SendAiResponse::sendViaWhatsApp` hardcoded Meta Graph API — never checked `gateway_mode`. Every AI-generated reply on a QR-paired page hit Meta with a Wuzapi per-user token, producing `OAuthException code 190 ("Cannot parse access token")` and no delivery. The user-triggered `SendPlatformMessage::sendViaWhatsApp` already had the gate, so user messages worked and AI messages didn't — perfectly matches the reported "AI replies show in inbox but don't reach WhatsApp" symptom.
   - Fix: added the same `gateway_mode` branch to `SendAiResponse::sendViaWhatsApp`, calling `EvolutionApiService::sendText()` with the paired tenant name + Wuzapi per-user token. Comments on both sides asking future edits to keep them in sync.

### Verified end-to-end on prod

- Two paired pages simultaneously: page 18 (`201026361218`, tenant `team_2_nTlYG3Ka`), page 19 (`201148041136`, tenant `team_2_6f6EEzRU`). Both `loggedIn=true`. Sends per-page correctly route to the matching Wuzapi tenant via `metadata.gateway_instance`.
- Inbound: real WhatsApp text → webhook → `WuzapiWebhookController` → `ProcessIncomingMessage::processWuzapi` → `Contact`/`Conversation`/`Message` rows written → `SendAiResponse` dispatched → AI reply generated → `sendViaWhatsApp` gates to Wuzapi → `POST /chat/send/text` returns 200 → customer's phone shows the AI reply.
- User-triggered inbox send: same path via `SendPlatformMessage` — verified with 5 messages Omar typed into conv 894 (msg 525-529), all delivered with real Wuzapi message IDs.

### Operational lessons (also captured in memory)

- **NEVER run `php artisan config:cache` as root on prod.** Twice in this session I did so via SSH `root@` — the cached file was written with root ownership context and PHP-FPM (running as www-data) served intermittent `MissingAppKeyException` on ~1 in N requests, causing user-visible 500s. Correct pattern: `sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache && systemctl reload php8.4-fpm`.
- **`SendPlatformMessage::sendViaWhatsApp` and `SendAiResponse::sendViaWhatsApp` are parallel code paths that both need the same routing gates.** Adding a new WhatsApp send condition (gateway_mode, media handling, tokens, etc.) requires editing both, or one flow silently misroutes. Comments now on both sides.

### Files touched this session

- `resources/views/livewire/connections/index.blade.php` — QR Connect button, isOnline uses helper
- `app/Livewire/Connections/Index.php` — `reconnectGateway` guard, `isGatewayAccountActive` helper
- `app/Livewire/Connections/WhatsAppQrModal.php` — `cleanupInstance` DB-level guard
- `app/Http/Controllers/Webhooks/WuzapiWebhookController.php` — payload normalization
- `app/Jobs/ProcessIncomingMessage.php` — `processWuzapi` new payload shape + channel/broadcast skip + UTC timestamp
- `app/Jobs/SendAiResponse.php` — gateway_mode branch mirroring `SendPlatformMessage`
- `docker-compose.wuzapi.yml` — loopback-only bind, env-configurable port

### Commits in order

- `46f1e43` — expose QR Connect button on connections page
- `a9bda97` — bind Wuzapi port to loopback only, env-configurable
- `482a0ba` — reconnect nukes freshly paired tenant (race guard v1)
- `a063b9a` — DB-level race guard on cleanup + reconnect (race guard v2)
- `4ff846f` — parse new Wuzapi webhook payload shape
- `8521e5a` — skip channels/broadcasts + normalize webhook timestamp to UTC
- `6bf0c93` — AI send must respect gateway_mode too

*Session end: WhatsApp is production-ready on prod. Two paired phones tested end-to-end (inbound + outbound + AI reply). Zero send failures in the last 30+ minutes.*

---

## 2026-08-31 — Comments AI config Phase A shipped

**PR:** #3 → merged squash as `459ae68` on main
**Deploy:** GitHub Actions "Deploy to Production" succeeded first run
**Prod verify:**
- `git log --oneline -1` on prod → `459ae68 feat(ai): Comments AI config tab — phase A (config only)`
- `php artisan migrate:status | grep comment_settings` → `Ran`
- `curl -sS -o /dev/null -w "HTTP %{http_code}" -L https://ot1-pro.com/settings/ai/config` → HTTP 200

**What shipped:** New nullable JSON column `comment_settings` on `ai_configs`. Dormant "Comments" tab on `/settings/ai/config` for Facebook + Instagram pages. Zero runtime behavior — feature activates only in Phase B when Meta App Review approves `pages_manage_engagement` + `instagram_manage_comments` and the ingestion + send pipeline lands.

**Precursor:** HasFactory trait + factory classes for Team/Page/ConnectedAccount (test infra; same additions independently exist on the campaigns branch — git will resolve identically at that PR's merge).

**Rollback (if needed within the hour):**
```bash
git revert -m 1 459ae68 && git push origin main
# Migration undo, if the column becomes problematic:
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan migrate:rollback --step=1'
```

**Next:** Phase B (comment webhook ingestion + Graph API reply/DM sending) blocked on Meta App Review completion for the two new permissions. Separate spec when ready.
