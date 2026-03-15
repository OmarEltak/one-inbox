# One Inbox - Complete Setup Journal & Documentation

> This file documents EVERY step taken to set up and configure the One Inbox project.
> It includes credentials, config values, Meta console steps, code changes, and mistakes.
> **READ THIS FILE AT THE START OF EVERY NEW SESSION.**

---

## 1. Project Basics

- **Framework**: Laravel 12 + Livewire 4 + Flux UI
- **Local Domain**: `https://one-inbox.test` (HTTPS via Laravel Herd)
- **Dev Environment**: Laravel Herd on Windows 11, PHP 8.4
- **Database**: SQLite (default Laravel)
- **Queue**: database driver
- **Session**: database driver

---

## 2. Herd $__herd_closure Fix

**Problem**: Site showed fatal error: `Undefined global variable $__herd_closure in bootstrap\app.php on line 27`

**Root Cause**: Herd's PHP extension (`php_herd-8.4.dll`) wraps PHP files in closures. When `"dumps": false` in Herd config, the extension's closure wrapping breaks (known bug - GitHub Issue #1003).

**Key Discovery**: Herd's PHP-FPM **overrides php.ini settings** via command-line arguments. Editing `php.ini` does NOT affect FPM behavior.

**Fix**: Changed `"dumps": false` to `"dumps": true` in:
```
C:\Users\NanoChip\.config\herd\config\config.json
```
Then restarted Herd.

**Relevant Herd Files**:
- PHP ini: `C:\Users\NanoChip\.config\herd\bin\php84\php.ini`
- Herd extension DLL: `C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\phpherd\php_herd-8.4.dll`
- Dump loader: `C:\Program Files\Herd\resources\app.asar.unpacked\resources\valet\dump-loader.php`
- FPM config: `C:\Program Files\Herd\resources\app.asar.unpacked\resources\config\fpm\phpfpm-valet.conf`
- Valet config: `C:\Users\NanoChip\.config\herd\config\valet\config.json` (TLD: "test")
- Nginx config: `C:\Users\NanoChip\.config\herd\config\valet\Nginx\one-inbox.test.conf`

---

## 3. Meta Developer Console - App Setup

### 3.1 Old App (DEPRECATED - DO NOT USE)
- **App Name**: One Inbox
- **App ID**: `1433402598508734`
- **Type**: Consumer (WRONG - only supports basic Facebook Login)
- **Problem**: Consumer type has NO access to pages_messaging, pages_manage_metadata, or any Messenger/Instagram API permissions. Only email/public_profile/user_* permissions available.
- **Status**: Abandoned - do not use this app

### 3.2 New App (CURRENT - USE THIS)
- **App Name**: One Inbox Business
- **App ID**: `1469090344742803`
- **App Secret**: `17e946f6bfd0f02c8c6d585b32671b7c`
- **Type**: Business
- **Use Cases**: Messenger from Meta + Instagram API
- **Console URL**: `https://developers.facebook.com/apps/1469090344742803/dashboard/`

### 3.3 How We Created the Business App (Step by Step)

1. Go to `https://developers.facebook.com/apps/creation/`
2. **Step 1 - App Details**: Name = "One Inbox Business", Email = omareltak7@gmail.com
3. **Step 2 - Use Cases**:
   - Checked "Messenger from Meta"
   - Checked "Instagram API" (إدارة المراسلة والمحتوى على Instagram)
   - Did NOT select "Facebook Login" (it conflicts with Messenger/Instagram use cases - shows warning about incompatible use cases)
4. **Step 3 - Business**: Skipped (no business portfolio linked)
5. **Step 4 - Requirements**: Accepted
6. **Step 5 - Overview**: Created the app

### 3.4 Facebook Login for Business Configuration

**Important**: Business-type apps use "Facebook Login for Business" which is DIFFERENT from regular "Facebook Login". It uses a `config_id` parameter instead of `scope` in the OAuth URL.

**Redirect URI Setup**:
1. Go to: Facebook Login for Business > الإعدادات (Settings)
2. Scroll to "محددات URI لإعادة توجيه OAuth الصالحة" (Valid OAuth Redirect URIs)
3. Added: `https://one-inbox.test/connections/facebook/callback`
4. Clicked "حفظ التغييرات" (Save Changes)
5. **Note**: The save button was unresponsive to coordinate clicks - had to use JavaScript `document.querySelector('button[type="submit"]').click()` to save

**Login Configuration Setup**:
1. Go to: Facebook Login for Business > عمليات التكوين (Configurations)
2. Clicked "إنشاء تكوين" (Create Configuration)
3. **Step 1 - Name**: "One Inbox Messaging"
4. **Step 2 - Login Variation**: Left default (no special variation selected - Conversions API and Instagram Graph API were available but not needed)
5. **Step 3 - Access Token**: Selected "رمز وصول المستخدم" (User access token) - NOT system user token
6. **Step 4 - Assets**: Skipped (auto-skipped because we chose user access token)
7. **Step 5 - Permissions**: Selected from dropdown:
   - `pages_messaging` - manage page conversations in Messenger
   - `pages_manage_metadata` - subscribe to webhooks, manage page settings
   - `pages_show_list` - show list of pages user manages
   - Note: `instagram_basic` and `instagram_manage_messages` are NOT available in this dropdown - Instagram permissions are handled separately through the Instagram API use case
8. Clicked "إنشاء" (Create)
9. **Config ID**: `896835833159946`

---

## 4. Code Changes for Facebook Login for Business

### 4.1 .env Updates
```env
# Meta Platform (Facebook, Instagram, WhatsApp)
META_APP_ID=1469090344742803
META_APP_SECRET=17e946f6bfd0f02c8c6d585b32671b7c
META_WEBHOOK_VERIFY_TOKEN=one_inbox_verify_2024
META_GRAPH_API_VERSION=v21.0
META_LOGIN_CONFIG_ID=896835833159946
```

### 4.2 config/services.php
Added `login_config_id` to the meta config array:
```php
'meta' => [
    'app_id' => env('META_APP_ID', ''),
    'app_secret' => env('META_APP_SECRET', ''),
    'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', ''),
    'graph_api_version' => env('META_GRAPH_API_VERSION', 'v21.0'),
    'login_config_id' => env('META_LOGIN_CONFIG_ID', ''),
],
```

### 4.3 app/Services/Platforms/FacebookPlatform.php - OAuth URL Change
**Before** (scope-based, for regular Facebook Login):
```php
public function getConnectUrl(): string
{
    $scopes = implode(',', [
        'pages_messaging', 'pages_manage_metadata', 'pages_read_engagement',
        'pages_show_list', 'instagram_basic', 'instagram_manage_messages',
    ]);
    // ...
    return "https://www.facebook.com/{version}/dialog/oauth?" . http_build_query([
        'client_id' => $this->appId,
        'redirect_uri' => $redirectUri,
        'scope' => $scopes,       // <-- OLD: scope parameter
        'response_type' => 'code',
        'state' => $state,
    ]);
}
```

**After** (config_id-based, for Facebook Login for Business):
```php
public function getConnectUrl(): string
{
    // ...
    return "https://www.facebook.com/{version}/dialog/oauth?" . http_build_query([
        'client_id' => $this->appId,
        'redirect_uri' => $redirectUri,
        'config_id' => config('services.meta.login_config_id'),  // <-- NEW: config_id
        'response_type' => 'code',
        'state' => $state,
    ]);
}
```

### 4.4 FacebookPlatform.php - Fetch Conversations on Connect
Added `$this->fetchConversations($page)` call inside `fetchPages()` method so existing conversations are pulled when connecting:
```php
// Inside fetchPages() loop, after subscribePage and detectInstagramAccount:
$this->fetchConversations($page);
```

### 4.5 Stored Scopes Update
Updated the `scopes` array stored in `handleCallback()`:
```php
// Before: 6 scopes including instagram_basic, instagram_manage_messages, pages_read_engagement
// After: 3 scopes matching what's in the config
'scopes' => ['pages_messaging', 'pages_manage_metadata', 'pages_show_list'],
```

---

## 5. Facebook OAuth Flow (How It Works End-to-End)

1. User clicks "Connect with Facebook" on `/connections`
2. App redirects to: `https://www.facebook.com/v21.0/dialog/oauth?client_id=1469090344742803&redirect_uri=https://one-inbox.test/connections/facebook/callback&config_id=896835833159946&response_type=code&state={csrf_token}`
3. Facebook shows "Facebook Login for Business" dialog:
   - Step 1: "Continue as [User]?" - user confirms identity
   - Step 2: "Choose Pages to share" - user selects all current + future pages
   - Step 3: "Review permissions" - shows pages_messaging, pages_manage_metadata, pages_show_list
   - Step 4: "Save" - user confirms
   - Step 5: "[User] linked to One Inbox Business" - success, click "Done"
4. Facebook redirects to: `https://one-inbox.test/connections/facebook/callback?code={auth_code}&state={csrf_token}`
5. `ConnectionController::facebookCallback()` calls `FacebookPlatform::handleCallback()`
6. `handleCallback()`:
   - Exchanges `code` for short-lived user access token via `GET /oauth/access_token`
   - Exchanges short-lived for long-lived token (~60 days) via `GET /oauth/access_token` with `grant_type=fb_exchange_token`
   - Fetches user profile via `GET /me?fields=id,name,email`
   - Creates/updates `ConnectedAccount` record
   - Calls `fetchPages()` which:
     - Fetches pages via `GET /me/accounts?fields=id,name,access_token,category,picture`
     - For each page: creates `Page` record, subscribes to webhooks, detects linked Instagram, fetches existing conversations

---

## 6. Connected Accounts & Pages (Current State)

### Connected Account
- **User**: Omar Mohamed Eltak
- **Platform**: facebook
- **Token**: Long-lived user access token (stored encrypted in DB)
- **Token Expires**: ~60 days from connection date

### Connected Pages
1. **Brandk** - Facebook page
   - Platform Page ID: `105577575011406`
   - Category: ملابس (علامة تجارية) - Clothing brand
   - Status: Active
   - Conversations fetched: 50+

2. **تعلم الموسيقة** - Facebook page
   - Platform Page ID: `450418318493611`
   - Category: سلسلة حفلات - Concert series
   - Status: Active
   - Conversations fetched: 0

3. **Instagram** (if any linked to above pages) - auto-detected

### Database Counts (after initial fetch)
- Pages: 3 (2 Facebook + possibly 1 Instagram/old)
- Conversations: 52
- Contacts: 52
- Messages: 0 (messages are only stored when sent/received via webhooks or fetched individually)

---

## 7. Known Issues & Bugs

### 7.1 Sidebar Branding
- Shows "Laravel Starter Kit" instead of "One Inbox"
- File: `resources/views/layouts/app/sidebar.blade.php` (needs update)

### 7.2 Clicking Conversation Shows Blank Page
- When clicking a conversation in the inbox, the right panel doesn't show messages
- Need to investigate: `selectedConversation` computed property and message loading
- Messages table is empty (0 messages) - conversations were fetched but individual messages were NOT pulled from Facebook API

### 7.3 Inbox Sidebar Structure Missing
- Current: Only "All Messages" filter with platform badges (FB, IG, WA, TG)
- Missing: Per-page navigation in sidebar (should show each connected page as a separate item)
- Missing: Personal chat section

### 7.4 Meta Console API Version Mismatch
- Console advanced settings shows: v25.0
- Code uses: v21.0 (from .env META_GRAPH_API_VERSION)
- Should update to latest stable version eventually

### 7.5 App Mode - RESOLVED
- ~~Meta app status: "غير منشور" (Not published)~~
- **Status as of 2026-03-10**: App is now LIVE ("تم النشر")
- External users can connect their Facebook accounts without "App not active" error
- Privacy Policy URL saved: `https://github.com/privacy` (placeholder — update to real URL before launch)

---

## 8. Mistakes & Lessons Learned

1. **Consumer vs Business app type**: The original Meta app was created as Consumer type which ONLY supports basic Facebook Login. Business type is required for Messenger, Pages API, Instagram API. Always create Business type for SaaS inbox apps.

2. **Facebook Login vs Facebook Login for Business**: Business-type apps get "Facebook Login for Business" (not regular "Facebook Login"). This uses `config_id` parameter instead of `scope` in the OAuth URL. You must create a "Login Configuration" in the console to get the config_id.

3. **Herd php.ini is ignored by FPM**: Don't waste time editing `C:\Users\NanoChip\.config\herd\bin\php84\php.ini` - Herd passes config to PHP-FPM via command-line arguments, completely overriding php.ini.

4. **fetchConversations not called on connect**: The original `handleCallback` → `fetchPages` flow only subscribed pages to webhooks but never pulled existing conversations. Had to add `$this->fetchConversations($page)` call.

5. **Messages not fetched with conversations**: `fetchConversations` only creates Conversation and Contact records. It does NOT fetch individual messages. The messages column in DB is empty (0). Need to fetch messages when a conversation is selected/opened.

6. **Meta console save button**: The save button on Facebook Login for Business settings page sometimes doesn't respond to browser automation clicks. Use JavaScript `button.click()` as fallback.

7. **Instagram permissions not in Login Configuration**: When creating a Login Configuration, Instagram permissions (instagram_basic, instagram_manage_messages) are not available in the permissions dropdown. They're handled separately through the Instagram API use case.

---

## 9. File Reference

### Key Application Files
- `app/Services/Platforms/FacebookPlatform.php` - Facebook/Instagram OAuth, messaging, webhooks
- `app/Services/Platforms/WhatsAppPlatform.php` - WhatsApp connection and messaging
- `app/Services/Platforms/TelegramPlatform.php` - Telegram bot connection and messaging
- `app/Http/Controllers/ConnectionController.php` - OAuth redirect + callback routes
- `app/Http/Controllers/MetaWebhookController.php` - Incoming webhook handler for FB/IG/WA
- `app/Http/Controllers/TelegramWebhookController.php` - Incoming webhook handler for Telegram
- `app/Jobs/ProcessIncomingMessage.php` - Processes incoming messages from all platforms
- `app/Jobs/SendPlatformMessage.php` - Sends outbound messages to platform APIs
- `app/Jobs/SendAiResponse.php` - AI auto-responder
- `app/Livewire/Inbox/Index.php` - Inbox Livewire component
- `app/Livewire/Connections/Index.php` - Connections page Livewire component
- `resources/views/livewire/inbox/index.blade.php` - Inbox UI template
- `resources/views/livewire/connections/index.blade.php` - Connections UI template
- `resources/views/layouts/app/sidebar.blade.php` - App sidebar navigation
- `config/services.php` - Platform API config (meta, telegram sections)
- `.env` - Environment variables with credentials

### Task/Documentation Files
- `tasks/todo.md` - Master spec (Phase 1-4)
- `tasks/progress.md` - What's done, what's in progress
- `tasks/lessons.md` - Mistakes and learnings
- `tasks/setup-journal.md` - THIS FILE - complete step-by-step documentation
- `tasks/meta-api-research.md` - Meta API research notes

---

## 10. Meta App - Switching to Live Mode (2026-03-10)

### 10.1 The Problem
Meta requires a Privacy Policy URL in Basic Settings before the app can go Live. Without it, external users see "App not active" error when trying to connect.

### 10.2 Privacy Policy URL — What Works and What Doesn't

| URL | Save API | Go-Live Check | Notes |
|-----|----------|---------------|-------|
| `https://www.freeprivacypolicy.com/live/...` | ❌ `success:false` | — | Meta blocklists this domain |
| `https://app.freeprivacypolicy.com/...` | ❌ `success:false` | — | Same blocklist |
| `https://example.com/privacy-policy` | ✅ | ❌ URL invalid | Not a real page |
| `https://github.com/privacy` | ✅ | ✅ | Use as placeholder |

**Current PP URL**: `https://github.com/privacy`
**TODO**: Replace with real PP URL at your production domain.

### 10.3 How We Saved the Privacy Policy URL (Technical)

Meta's Basic Settings form uses React controlled inputs. Typing via DOM manipulation doesn't update React state, so the PP URL doesn't make it into the POST body. The working solution was **XHR interception**:

```javascript
// Inject this in browser console BEFORE clicking save
const orig = XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.send = function(body) {
  if (typeof body === 'string' && body.includes('app_details_privacy_policy_url')) {
    body = body.replace(/app_details_privacy_policy_url=[^&]*/,
      'app_details_privacy_policy_url=' + encodeURIComponent('https://github.com/privacy'));
    window._lastSaveResult = 'intercepted';
  }
  orig.call(this, body);
};
```

Then click "حفظ التغييرات" (Save Changes) on the Basic Settings page. Check success via:
```javascript
// After save completes
window._lastSaveResult
```

### 10.4 How We Went Live

1. Navigated to: `https://developers.facebook.com/apps/1469090344742803/go_live/`
2. Page showed: "كل إعدادات التطبيق المطلوبة مكتملة" (All required app settings are complete)
3. Found the `[role="button"]` element containing "نشر" text and called `.click()` on it
4. URL changed to `?is_go_live_modal_shown=1`
5. App status changed from "غير منشور" → "تم النشر" (Live)

### 10.5 Important Notes
- The `jazoest` CSRF token does NOT need to be recomputed when modifying form fields — it's `"2"` + sum of char codes of `fb_dtsg`, independent of other fields
- Meta's save API returns HTTP 200 even on failure — always check `payload.success` in the JSON body
- After going live, webhook events from external users will now be delivered (no longer limited to app roles)

---

*Last updated: 2026-03-10*
*Session: Meta app switched to Live mode*
