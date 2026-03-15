# Meta Platform APIs Research for Unified Inbox

> Researched: 2026-02-25 | Graph API Current Version: v21.0+ (v24.0 announced)

---

## Table of Contents

1. [Facebook Conversations API / Page Inbox (Messenger)](#1-facebook-conversations-api--page-inbox-messenger)
2. [Instagram Messaging API](#2-instagram-messaging-api)
3. [WhatsApp Business Cloud API](#3-whatsapp-business-cloud-api)
4. [Meta Graph API General (OAuth, Tokens, App Review)](#4-meta-graph-api-general)
5. [Key Considerations for Laravel Implementation](#5-key-considerations-for-laravel-implementation)
6. [Unified Architecture Recommendation](#6-unified-architecture-recommendation)

---

## 1. Facebook Conversations API / Page Inbox (Messenger)

### API Endpoints

All endpoints use the base URL: `https://graph.facebook.com/v{VERSION}/`

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| List conversations | GET | `/{PAGE_ID}/conversations` | Get all conversations for a page |
| Filter by platform | GET | `/{PAGE_ID}/conversations?platform=MESSENGER` | Filter Messenger-only conversations |
| Get messages in thread | GET | `/{CONVERSATION_ID}/messages` | Get messages within a conversation |
| Send message | POST | `/{PAGE_ID}/messages` or `/me/messages` | Send a message on behalf of the page |

### Send API Request Format

```json
POST https://graph.facebook.com/v21.0/me/messages
Authorization: Bearer {PAGE_ACCESS_TOKEN}
Content-Type: application/json

{
  "recipient": {
    "id": "{PSID}"
  },
  "messaging_type": "RESPONSE",
  "message": {
    "text": "Hello, how can I help you?"
  }
}
```

### Messaging Types

| Type | Use Case |
|------|----------|
| `RESPONSE` | Replying within 24h of user's last message (standard window) |
| `UPDATE` | Proactive non-promotional updates (deprecated for most uses) |
| `MESSAGE_TAG` | Sending outside 24h window with an approved tag |

### Approved Message Tags (for outside 24h window)

- `CONFIRMED_EVENT_UPDATE` - Event reminders/updates
- `POST_PURCHASE_UPDATE` - Order/shipping updates
- `ACCOUNT_UPDATE` - Account status changes
- `HUMAN_AGENT` - Human agent responses (within 7 days, requires approval)

### Required Permissions

| Permission | Purpose | Access Level |
|------------|---------|--------------|
| `pages_messaging` | Send and receive messages on behalf of the page | Advanced Access required |
| `pages_manage_metadata` | Subscribe to webhooks, update page settings | Advanced Access required |
| `pages_read_engagement` | Read page conversations and engagement data | Advanced Access required |
| `pages_show_list` | List pages the user is admin of | Standard Access |

### Webhook System

**Webhook Setup:**
1. Configure a callback URL in the Meta App Dashboard under Webhooks
2. Meta sends a GET verification request with `hub.mode`, `hub.verify_token`, and `hub.challenge`
3. Your server must return `hub.challenge` value to verify

**Subscribable Webhook Events:**
- `messages` - Incoming messages
- `message_deliveries` - Delivery confirmations
- `message_reads` - Read receipts
- `message_echoes` - Messages sent by the page (echo)
- `messaging_postbacks` - Button/quick reply postbacks
- `messaging_optins` - User opt-ins
- `messaging_referrals` - Referral tracking
- `messaging_handovers` - Handover protocol events
- `messaging_policy_enforcement` - Policy violation notifications

**Webhook Payload Structure:**
```json
{
  "object": "page",
  "entry": [
    {
      "id": "{PAGE_ID}",
      "time": 1458692752478,
      "messaging": [
        {
          "sender": { "id": "{PSID}" },
          "recipient": { "id": "{PAGE_ID}" },
          "timestamp": 1458692752478,
          "message": {
            "mid": "mid.xxx",
            "text": "Hello"
          }
        }
      ]
    }
  ]
}
```

**Signature Verification:**
Meta includes `X-Hub-Signature-256` header with HMAC SHA256 hash of the payload using your App Secret.

### Reading AND Sending Messages

Yes, you can both read and send messages on behalf of a page:
- **Read**: GET `/{PAGE_ID}/conversations` and GET `/{CONVERSATION_ID}/messages`
- **Send**: POST `/{PAGE_ID}/messages` with Page Access Token
- The Page Access Token acts as the page identity

### Page Admin / Role Management

**Roles available:** Admin, Editor, Moderator, Advertiser, Analyst, Jobs Manager

**API Access:**
- `GET /me/accounts` - Lists all pages the user administers (with page access tokens)
- Page roles are managed through Business Manager, not directly via Graph API for assignment
- To check if a user has admin access, the `me/accounts` endpoint returns only pages where the user has a role

### Rate Limits

- **Send API**: ~250 requests/second (no hard published limit, but this is safe)
- **Messenger Platform API (24h window)**: 200 x Number_of_Engaged_Users calls
- **Pages API (24h window)**: 4,800 x Number_of_Engaged_Users calls
- **Messenger Profile API**: 10 calls per 10-minute interval per page

---

## 2. Instagram Messaging API

### Overview

The Instagram Messaging API is NOT a standalone API. It is part of the **Messenger Platform** and uses the **Facebook Graph API** infrastructure. Instagram DMs for business/creator accounts are managed through the same Graph API endpoints as Facebook Messenger, but with the `platform=instagram` filter.

### API Endpoints

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| List IG conversations | GET | `/{PAGE_ID}/conversations?platform=instagram` | Get Instagram DM conversations |
| Get messages | GET | `/{CONVERSATION_ID}/messages` | Get messages in a conversation |
| Send IG DM | POST | `/{PAGE_ID}/messages` | Send a DM via the linked page |

### Send Message Format (Instagram)

```json
POST https://graph.facebook.com/v21.0/{PAGE_ID}/messages
Authorization: Bearer {PAGE_ACCESS_TOKEN}
Content-Type: application/json

{
  "recipient": {
    "id": "{INSTAGRAM_SCOPED_ID}"
  },
  "message": {
    "text": "Thanks for reaching out!"
  }
}
```

**Supported message types:** text, image, video, audio attachments

### Required Permissions

| Permission | Purpose | Access Level |
|------------|---------|--------------|
| `instagram_basic` | Basic Instagram account info | Standard or Advanced |
| `instagram_manage_messages` | Read and send Instagram DMs | Advanced Access required |
| `pages_manage_metadata` | Subscribe to IG messaging webhooks | Advanced Access required |
| `pages_show_list` | List pages connected to IG accounts | Standard Access |
| `pages_messaging` | Core messaging capability | Advanced Access required |
| `pages_read_engagement` | Get Instagram ID connected to page | Advanced Access required |

### Webhook Events for Instagram Messaging

Subscribe to the `instagram` webhook object with these fields:
- `messages` - Incoming DMs
- `message_reactions` - Reactions to messages
- `messaging_seen` - Read receipts
- `messaging_postbacks` - Quick reply / button postbacks

**Webhook payload for Instagram messages follows the same structure as Messenger** but with Instagram-scoped user IDs.

### Personal vs. Business Account Differences

| Feature | Personal Account | Business/Creator Account |
|---------|-----------------|------------------------|
| API Access | NO API access for DMs | Full Messaging API access |
| Webhooks | Not available | Available |
| Automation | Not allowed | Allowed within rules |
| Required setup | N/A | Must be linked to a Facebook Page |
| Minimum followers | N/A | 1,000 followers for DM API access |

### 24-Hour Messaging Window

Same as Messenger: automated responses are only allowed within 24 hours of the user's last message. Outside this window, only human agent responses are permitted.

### Rate Limits

- **Messaging**: 200 x Number_of_Conversations per hour per Instagram account
- **Graph API general**: 200 requests per hour per Instagram user (BUC-adjusted)
- Response header `X-Business-Use-Case-Usage` contains `acc_id_util_pct` (consumption %) and `reset_time_duration`

### Key Constraints

- Instagram account MUST be a Professional account (Business or Creator)
- MUST be connected to a Facebook Page
- Basic Display API was deprecated December 4, 2024
- 1,000-follower minimum for DM API access
- Cannot send unsolicited messages; automation must start from user-initiated action

---

## 3. WhatsApp Business Cloud API

### Overview

The WhatsApp Cloud API is hosted by Meta and replaces the older on-premise Business API (which lost support by end of 2025). All new integrations should use the Cloud API.

### API Endpoint

**Single endpoint for all message types:**
```
POST https://graph.facebook.com/v21.0/{PHONE_NUMBER_ID}/messages
```

### Setup Process

1. **Create a Meta Developer Account** at developers.facebook.com
2. **Create a new App** - select "Business" type
3. **Add WhatsApp product** from the App Dashboard
4. **WhatsApp Business Account (WABA)** is created automatically
5. **Register a phone number:**
   - Must be a fresh number NOT linked to any existing WhatsApp account
   - Add via WhatsApp Dev Console (not just Business Manager)
   - You receive a `PHONE_NUMBER_ID` (different from the actual phone number)
6. **Generate a System User Token:**
   - In Meta Business Manager > System Users
   - Create admin system user
   - Assign the app
   - Generate token with `whatsapp_business_messaging` and `whatsapp_business_management` permissions
   - This token does NOT expire (treat like a password)

### Authentication

```
Authorization: Bearer {SYSTEM_USER_ACCESS_TOKEN}
Content-Type: application/json
```

**Important:** The temporary token from API setup expires in 24 hours. For production, always use a System User token.

### Message Types

#### Text Message
```json
{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "to": "14155551234",
  "type": "text",
  "text": {
    "preview_url": false,
    "body": "Your order #12345 is on its way!"
  }
}
```

#### Template Message (Business-Initiated)
```json
{
  "messaging_product": "whatsapp",
  "to": "14155551234",
  "type": "template",
  "template": {
    "name": "order_shipped",
    "language": { "code": "en" },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "John" },
          { "type": "text", "text": "12345" },
          { "type": "text", "text": "FedEx Express" }
        ]
      }
    ]
  }
}
```

#### Interactive Message (Buttons)
```json
{
  "messaging_product": "whatsapp",
  "to": "14155551234",
  "type": "interactive",
  "interactive": {
    "type": "button",
    "body": { "text": "What would you like to do?" },
    "action": {
      "buttons": [
        { "type": "reply", "reply": { "id": "btn_1", "title": "Option 1" } },
        { "type": "reply", "reply": { "id": "btn_2", "title": "Option 2" } }
      ]
    }
  }
}
```

### Template Messages vs. Session Messages (24-Hour Window)

| Aspect | Template Messages | Session Messages |
|--------|------------------|-----------------|
| Who initiates | Business-initiated | Response to user message |
| Approval | Must be pre-approved by Meta | No approval needed |
| Time restriction | Can be sent anytime | Only within 24h of user's last message |
| Content | Must follow template structure | Free-form text, media, interactive |
| Cost | Billed per message (varies by category) | Free (1,000/month/WABA) then billed per conversation |
| Categories | Marketing, Utility, Authentication | Service conversation |

**Template categories and pricing (as of July 2025):**
- **Marketing**: Promotional content - always billed per message
- **Utility**: Transaction updates - free within 24h window, billed outside
- **Authentication**: OTP/verification - always billed per message
- **Service**: User-initiated conversations - 1,000 free/month/WABA, then billed

### Webhook Setup for Incoming Messages

**Configuration:**
1. In Meta App Dashboard > WhatsApp > Configuration
2. Set webhook URL (must be HTTPS)
3. Set a verify token (any string you choose)
4. Subscribe to `messages` webhook field

**Verification endpoint (GET):**
```
GET /whatsapp/webhook?hub.mode=subscribe&hub.verify_token={YOUR_TOKEN}&hub.challenge={CHALLENGE}
```
Return the `hub.challenge` value with 200 status.

**Incoming message webhook payload:**
```json
{
  "object": "whatsapp_business_account",
  "entry": [
    {
      "id": "{WABA_ID}",
      "changes": [
        {
          "value": {
            "messaging_product": "whatsapp",
            "metadata": {
              "display_phone_number": "15551234567",
              "phone_number_id": "{PHONE_NUMBER_ID}"
            },
            "contacts": [
              { "profile": { "name": "John" }, "wa_id": "14155551234" }
            ],
            "messages": [
              {
                "from": "14155551234",
                "id": "wamid.xxx",
                "timestamp": "1677000000",
                "text": { "body": "Hello" },
                "type": "text"
              }
            ]
          },
          "field": "messages"
        }
      ]
    }
  ]
}
```

**Webhook event types:**
- `messages` - Incoming messages
- `message_status` (sent, delivered, read, failed)

### Required Permissions

| Permission | Purpose |
|------------|---------|
| `whatsapp_business_messaging` | Send and receive WhatsApp messages |
| `whatsapp_business_management` | Manage WABA settings, phone numbers, templates |

### Rate Limits

- **Throughput**: Up to 80 messages per second via Cloud API
- **Messaging tiers** (based on unique users messaged in 24h):
  - Tier 1: 1,000 unique users/day
  - Tier 2: 10,000 unique users/day
  - Tier 3: 100,000 unique users/day
  - Tier 4: Unlimited
- Tiers automatically upgrade based on quality and volume

### 2025-2026 Changes

- On-premise Business API lost support end of 2025; Cloud API is the only path forward
- Per-message pricing introduced July 1, 2025
- Since January 2026, pure "General Purpose AI" bots without a specific focus are prohibited per Meta guidelines

---

## 4. Meta Graph API General

### OAuth Flow

**Step 1: Facebook Login (Authorization)**
```
https://www.facebook.com/v21.0/dialog/oauth?
  client_id={APP_ID}
  &redirect_uri={REDIRECT_URI}
  &scope=pages_messaging,pages_manage_metadata,pages_read_engagement,instagram_basic,instagram_manage_messages
  &response_type=code
```

**Step 2: Exchange Code for Short-Lived User Token**
```
GET https://graph.facebook.com/v21.0/oauth/access_token?
  client_id={APP_ID}
  &redirect_uri={REDIRECT_URI}
  &client_secret={APP_SECRET}
  &code={CODE}
```
Returns a short-lived user access token (valid ~1 hour).

**Step 3: Exchange for Long-Lived User Token**
```
GET https://graph.facebook.com/v21.0/oauth/access_token?
  grant_type=fb_exchange_token
  &client_id={APP_ID}
  &client_secret={APP_SECRET}
  &fb_exchange_token={SHORT_LIVED_TOKEN}
```
Returns a long-lived user access token (valid ~60 days).

**Step 4: Get Page Access Tokens**
```
GET https://graph.facebook.com/v21.0/me/accounts?
  access_token={LONG_LIVED_USER_TOKEN}
```
Returns page IDs, names, and page access tokens for all pages the user administers.

**Important:** When you request Page Access Tokens using a long-lived user token, the returned Page Access Tokens do NOT expire (they are "permanent" page tokens).

### Token Types

| Token Type | Lifespan | Use Case |
|------------|----------|----------|
| Short-lived User Token | ~1 hour | Initial OAuth exchange |
| Long-lived User Token | ~60 days | Server-side operations, must be refreshed |
| Page Access Token (from short-lived) | ~1 hour | Temporary page operations |
| Page Access Token (from long-lived) | Never expires | Production page operations |
| System User Token | Never expires | WhatsApp Cloud API, server-to-server |
| App Access Token | Never expires | App-level operations (not user-specific) |

### Token Refresh Strategy

```
GET https://graph.facebook.com/v21.0/oauth/access_token?
  grant_type=fb_exchange_token
  &client_id={APP_ID}
  &client_secret={APP_SECRET}
  &fb_exchange_token={EXISTING_LONG_LIVED_TOKEN}
```

- Long-lived user tokens can be refreshed within their validity period
- Refreshed tokens get a new 60-day lifespan
- Page tokens derived from long-lived user tokens don't need refreshing

### App Review Process

1. **Development Mode**: App works only with users who have a role on the app (admin/developer/tester). No app review needed.
2. **Submit for App Review**: Required for production. Submit each permission individually with:
   - Description of how the permission will be used
   - Screencast/video demonstrating the use case
   - Privacy policy URL
   - Terms of service URL
3. **Business Verification**: Required for Advanced Access. Submit business documents through Meta Business Manager.
4. **Approval Timeline**: Typically 2-5 business days, but can take longer.

### Permission Levels

| Level | Who Can Use | How to Get |
|-------|-------------|------------|
| Standard Access | Only app admins/developers/testers | Automatic |
| Advanced Access | All users | Requires App Review + Business Verification |

### Complete Permissions List for Unified Inbox

| Permission | Platform | Purpose |
|------------|----------|---------|
| `pages_show_list` | Facebook | List user's pages |
| `pages_messaging` | Facebook/Messenger | Send/receive page messages |
| `pages_manage_metadata` | Facebook | Subscribe to webhooks |
| `pages_read_engagement` | Facebook | Read conversations, get IG account ID |
| `instagram_basic` | Instagram | Basic IG account info |
| `instagram_manage_messages` | Instagram | Read/send Instagram DMs |
| `whatsapp_business_messaging` | WhatsApp | Send/receive WhatsApp messages |
| `whatsapp_business_management` | WhatsApp | Manage WABA, templates, phone numbers |
| `business_management` | All | Manage business assets |
| `public_profile` | Facebook | Basic user profile info |

### Rate Limits Summary

| API | Rate Limit | Window |
|-----|-----------|--------|
| Graph API (general) | 200 calls/user/hour | Rolling 1 hour |
| Messenger Send API | ~250 requests/second | Per second |
| Messenger Platform | 200 x Engaged_Users | Rolling 24 hours |
| Pages API | 4,800 x Engaged_Users | Rolling 24 hours |
| Messenger Profile API | 10 calls per 10 min | Per page |
| Instagram Messaging | 200 x Conversations/hour | Rolling 1 hour |
| Instagram Graph API | 200 requests/hour/user | Rolling 1 hour (BUC-adjusted) |
| WhatsApp Cloud API | 80 messages/second | Per second throughput |

Rate limits use **Business Use Case (BUC)** logic -- actual limits may vary based on account type, endpoint, and compliance history.

---

## 5. Key Considerations for Laravel Implementation

### Laravel Packages

| Package | Purpose | Status |
|---------|---------|--------|
| `joelbutcher/laravel-facebook-graph` | Laravel wrapper for Facebook Graph PHP SDK | Active, PHP 8+ |
| `marshmallow/laravel-facebook-webhook` | Facebook webhook handling with Spatie webhook client | v2.3.0 (Feb 2025), actively maintained |
| `missael-anda/laravel-whatsapp` | WhatsApp Cloud API wrapper with webhook handling | Active, MIT license |
| `laravel/socialite` + `socialiteproviders/facebook` | OAuth login flow | Stable, widely used |
| `spatie/laravel-webhook-client` | Generic webhook receiving and processing | v3.x, excellent for Meta webhooks |

**Note:** For a unified inbox, you may not need heavy packages. Laravel's built-in `Http` facade (Guzzle) works well for direct API calls. The key packages are for OAuth (Socialite) and webhook handling (Spatie).

### Recommended Webhook Architecture

```
[Meta Platform] --> HTTPS POST --> [Laravel Webhook Route]
                                         |
                                    [Verify Signature]
                                    (X-Hub-Signature-256)
                                         |
                                    [Return 200 OK immediately]
                                         |
                                    [Dispatch to Laravel Queue]
                                         |
                              [Queue Worker processes job]
                                         |
                         [Store message in database]
                         [Trigger any AI responder logic]
                         [Send reply via appropriate API]
```

**Laravel Route Setup:**
```php
// routes/api.php (no CSRF, no auth middleware)
Route::get('/webhook/meta', [WebhookController::class, 'verify']);
Route::post('/webhook/meta', [WebhookController::class, 'handle']);
```

**Verification Controller (simplified):**
```php
public function verify(Request $request)
{
    if ($request->query('hub_mode') === 'subscribe'
        && $request->query('hub_verify_token') === config('services.meta.webhook_verify_token')) {
        return response($request->query('hub_challenge'), 200);
    }
    return response('Forbidden', 403);
}

public function handle(Request $request)
{
    // 1. Verify signature
    $signature = $request->header('X-Hub-Signature-256');
    $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), config('services.meta.app_secret'));
    if (!hash_equals($expected, $signature)) {
        return response('Invalid signature', 403);
    }

    // 2. Return 200 immediately, dispatch to queue
    ProcessMetaWebhook::dispatch($request->all());

    return response('OK', 200);
}
```

**Key Best Practices:**
1. Return 200 OK as fast as possible (Meta retries on failure)
2. Use `X-Hub-Signature-256` HMAC verification with App Secret
3. Dispatch all processing to Laravel queues (jobs)
4. Store raw webhook payload in database before processing (for debugging/replay)
5. Handle idempotency (Meta may send the same webhook multiple times)
6. Use exponential backoff for outgoing API calls that hit rate limits

### Multi-Page Management Architecture

**How it works:**
1. User authenticates via Facebook Login OAuth
2. Your app calls `GET /me/accounts` with the user's long-lived token
3. This returns ALL pages the user administers, each with its own Page Access Token
4. Store each Page Access Token in your database (they don't expire when derived from long-lived user tokens)
5. For each page, subscribe to webhooks using the page token

**Database Schema Concept:**
```
users
  - id
  - name
  - meta_user_id
  - long_lived_user_token (encrypted, refresh before 60-day expiry)

connected_pages
  - id
  - user_id (FK)
  - page_id (Meta Page ID)
  - page_name
  - page_access_token (encrypted, does not expire)
  - instagram_account_id (nullable, linked IG account)
  - platform (facebook|instagram)
  - is_active

connected_whatsapp_numbers
  - id
  - user_id (FK)
  - waba_id
  - phone_number_id
  - display_phone_number
  - system_user_token (encrypted)
  - is_active

conversations
  - id
  - connected_page_id (FK, nullable)
  - connected_whatsapp_number_id (FK, nullable)
  - platform (messenger|instagram|whatsapp)
  - platform_conversation_id
  - contact_name
  - contact_platform_id
  - last_message_at
  - status (open|closed|snoozed)

messages
  - id
  - conversation_id (FK)
  - direction (inbound|outbound)
  - message_type (text|image|video|audio|template|interactive)
  - content (text or JSON)
  - platform_message_id
  - status (sent|delivered|read|failed)
  - sent_at
  - metadata (JSON - raw platform data)
```

### Single Webhook Endpoint Strategy

You can use ONE webhook URL for all three platforms. The `object` field in the payload differentiates:
- `"object": "page"` = Facebook Messenger or Instagram message
- `"object": "whatsapp_business_account"` = WhatsApp message
- For Messenger vs Instagram within page events, check the messaging metadata or use the `platform=instagram` context

Alternatively, use separate endpoints per platform for cleaner separation:
- `/webhook/messenger`
- `/webhook/instagram`
- `/webhook/whatsapp`

---

## 6. Unified Architecture Recommendation

### High-Level Flow

```
[User's Browser]
    |
    v
[Laravel App] -- OAuth --> [Facebook Login]
    |                           |
    v                           v
[Store tokens]          [GET /me/accounts]
    |                           |
    v                           v
[Subscribe webhooks]    [Get page + IG tokens]
    |
    |--- Incoming webhooks from Meta --->  [Queue Worker]
    |                                         |
    |                                    [Normalize message]
    |                                    [Store in DB]
    |                                    [Trigger AI responder]
    |                                         |
    |                                    [Send reply via correct API]
    |                                         |
    |<-- POST to Graph API (Messenger/IG) ---|
    |<-- POST to WhatsApp Cloud API ---------|
    |
    v
[Unified Inbox UI] -- polls/websocket --> [Real-time updates]
```

### API Version Strategy

- Always pin to a specific API version (e.g., v21.0)
- Monitor Meta's changelog for deprecations
- Each Graph API version is supported for ~2 years after release
- Plan version upgrades quarterly

### Important 2025-2026 Deprecations to Watch

- **Messaging Events API**: Deprecated September 2025
- **Conversation object in message status webhooks**: Deprecated in v24.0
- **Basic Display API (Instagram)**: Fully deprecated December 2024
- **On-premise WhatsApp Business API**: Support ended late 2025
- **Sponsored Messages ad type**: No longer available

---

## Sources

- Meta Graph API Release Notes: https://releasebot.io/updates/meta/graph-api
- Facebook Messenger API Essentials: https://rollout.com/integration-guides/facebook-messenger/api-essentials
- Instagram Graph API Developer Guide 2026: https://elfsight.com/blog/instagram-graph-api-complete-developer-guide-for-2026/
- WhatsApp Cloud API Setup Guide: https://chatarmin.com/en/blog/whatsapp-cloudapi
- WhatsApp API Send Messages Guide: https://chatarmin.com/en/blog/whats-app-api-send-messages
- Laravel WhatsApp Cloud API Integration: https://getsamplecode.com/blog/whatsapp-cloud-api-with-laravel-12
- Laravel Facebook Webhook Package: https://github.com/marshmallow-packages/laravel-facebook-webhook
- Laravel WhatsApp Package: https://github.com/MissaelAnda/laravel-whatsapp
- Meta API Rate Limits: https://agentsapis.com/meta-api/pricing/
- Instagram Messaging API Guide: https://www.brevo.com/blog/instagram-dm-api/
- WhatsApp Business Registration: https://medium.com/@hamzas2401/how-i-registered-my-whatsapp-business-number-on-meta-b175a290a451
