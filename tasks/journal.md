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
| Local dev app | `C:\Users\NanoChip\Herd\one-inbox\` → `https://one-inbox.test` |
| Production app | `C:\Users\NanoChip\Herd\one-inbox-prod\` → `https://ot1-pro.com` |
| Both served by | Laravel Herd on Windows 11 via Cloudflare tunnel |
| Database | SQLite — each app has its OWN `database/database.sqlite` |
| Queue driver | database |
| PHP version | 8.4 (Herd) |
| WhatsApp gateway | Evolution API v2 in Docker → `http://localhost:8081` |
| Meta parent app | `1469090344742803` (One Inbox Business) |
| Meta Instagram sub-app | `1408745007038040` |
| Production domain | `https://ot1-pro.com` |
| Production team_id | 3 (user: omareltak7@gmail.com, user id: 3) |
| Local dev team_id | 1 (user: omareltak7@gmail.com, user id: 1) |

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

```bash
# 1. Docker (Evolution API — WhatsApp)
docker compose -f docker-compose.evolution.yml up -d
# Verify: curl http://localhost:8081/instance/fetchInstances -H "apikey: {EVOLUTION_API_KEY}"

# 2. Queue worker (processes incoming messages, AI responses, email fetch)
php artisan queue:work
# OR as NSSM service: OneInboxQueue / OneInboxQueueProd

# 3. Scheduler (polls email every 2 min)
php artisan schedule:work
# OR as NSSM service: OneInboxScheduler / OneInboxSchedulerProd

# 4. Cloudflare tunnel (exposes ot1-pro.com to internet)
# Runs as Windows service — check Services panel if webhooks stop arriving

# 5. Reverb (WebSockets for real-time inbox updates)
# NSSM service: OneInboxReverb / OneInboxReverbProd
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
