---
name: meta-webhook-two-layer-subscription
description: Use when the user reports "messages sent from Business Suite / Messenger mobile app / Facebook composer don't appear in the inbox", "outgoing Facebook or Instagram messages missing", "the customer sees our reply but we don't", or "echoes not firing". Also use before ANY change to `app/Services/Platforms/FacebookPlatform.php` `subscribed_fields`, before adding new Meta webhook fields, or when onboarding a new Meta permission. Codifies the two-layer Meta webhook subscription trap that took 1h to diagnose on 2026-09-03 — Meta requires BOTH app-level (`/{app-id}/subscriptions`) AND page-level (`/{page-id}/subscribed_apps`) to include a field, and silently drops webhooks if either is missing. Symptom looks like our code bug (we don't render outbound messages) when it's actually a Meta subscription gap. Never diagnose "missing echoes" without checking both layers first.
---

# Meta Webhook Two-Layer Subscription Trap

## The trap

Meta's Graph API webhook system has **two independent subscription layers**. BOTH must include a field or webhooks for that field are silently dropped. The symptom looks like an app bug (we're not rendering the message) when it's actually Meta never delivering the payload.

| Layer | Endpoint | Scope | Set from |
|---|---|---|---|
| **App-level** | `POST /{app-id}/subscriptions` | All pages using this app | Meta Developer Console → App → Messenger → Webhooks OR one-shot Graph call |
| **Page-level** | `POST /{page-id}/subscribed_apps` | This one page | `FacebookPlatform::connectPage()` on OAuth |

There is NO UI or API response that tells you a field is subscribed at one level but not the other. You have to query both explicitly.

## Diagnostic (run this FIRST when echoes are missing)

```bash
# 1. Check app-level subscriptions
curl -s "https://graph.facebook.com/v21.0/{APP_ID}/subscriptions?access_token={APP_ID}|{APP_SECRET}" \
  | jq '.data[] | select(.object=="page") | .fields'

# Look for: messages, message_echoes, messaging_postbacks, message_deliveries, message_reads
# If message_echoes is MISSING → that's the bug. See "Fix" below.

# 2. Check page-level subscriptions for a specific page
curl -s "https://graph.facebook.com/v21.0/{PAGE_ID}/subscribed_apps?access_token={PAGE_ACCESS_TOKEN}" \
  | jq '.data[] | {app: .name, fields: .subscribed_fields}'

# Look for the SAME fields. If message_echoes is missing here → run
# FacebookPlatform::backfillEchoSubscription() OR re-connect the page.
```

**Also check what's actually arriving:** query recent webhook rows in the DB:

```bash
# In tinker:
DB::table('meta_webhook_logs')
    ->orderByDesc('id')->limit(200)
    ->get(['id', 'event_type', 'created_at'])
    ->groupBy('event_type')
    ->map->count();
# If you see 42 `messages` and 0 `message_echoes`, that's the signature of a
# missing app-level `message_echoes` subscription.
```

## Fix: add a missing app-level field

```php
// tinker on prod
use Illuminate\Support\Facades\Http;

$appId     = config('services.meta.app_id');
$appSecret = config('services.meta.app_secret');
$verify    = config('services.meta.webhook_verify_token');
$callback  = 'https://ot1-pro.com/api/webhooks/meta';

$res = Http::asForm()->post("https://graph.facebook.com/v21.0/{$appId}/subscriptions", [
    'object'        => 'page',
    'callback_url'  => $callback,
    'fields'        => 'messages,message_echoes,messaging_postbacks,message_deliveries,message_reads',
    'verify_token'  => $verify,
    'access_token'  => "{$appId}|{$appSecret}",
]);

dd($res->status(), $res->json());
// Must be 200 with {"success": true}.
```

**Gotcha:** the endpoint returns Meta error code 194 ("Requires all or none of the params: callback_url, verify_token") if you omit `verify_token` even though you're only updating fields. Always send both.

## Fix: add a missing page-level field

For NEW pages: change `subscribed_fields` in `FacebookPlatform::connectPage()` — new OAuth flows pick it up automatically.

For EXISTING pages: run `FacebookPlatform::backfillEchoSubscription()` (loops all active FB pages and re-POSTs their `/subscribed_apps` with the current field list). Idempotent; safe to run repeatedly.

## Why `message_echoes` specifically matters

Without it, we only receive webhooks for messages the customer sends TO the page. Any message the page sends — from the OT1 composer, Business Suite web, Messenger mobile app, iOS/Android Facebook app, or a Zapier/Meta bot — never generates a webhook. The customer's replies still arrive, so the inbox looks partially functional, but half the conversation is invisible.

**Real incident (2026-09-03):** page-level was correct (had `message_echoes` from the OAuth flow), app-level was missing it. 42 inbound messages received, 0 echoes ever. Symptom: user sends from Business Suite → customer sees it → customer replies → OT1 inbox shows the customer's reply out-of-context (the outbound message it's replying to never appeared). ~1h to diagnose.

## Rule for future changes

**When adding a new Meta webhook field (any `messaging_*`, `feed`, `mention`, etc.):**

1. Add it to `subscribed_fields` in `FacebookPlatform::connectPage()` (page-level, for new connections).
2. Add it to `FacebookPlatform::backfillEchoSubscription()` field list (page-level, for existing pages).
3. **Also POST it to `/{app-id}/subscriptions`** via the tinker snippet above (app-level). Do NOT skip this step. The Meta Developer Console UI can do it too, but the API call is auditable in shell history.
4. Verify both layers with the diagnostic curls.
5. Send a test message from Business Suite as the page admin → confirm a `message_echoes` webhook row appears in `meta_webhook_logs` within 5 seconds.

## Related files

- `app/Services/Platforms/FacebookPlatform.php` — `connectPage()`, `backfillEchoSubscription()`, `subscribed_fields` constant
- `app/Http/Controllers/Webhooks/MetaWebhookController.php` — receives the payload; handles `message_echoes` events
- `app/Services/Platforms/InstagramPlatform.php` — SAME two-layer trap for IG; check separately
- `docs/ARCHITECTURE.md` §17 (Meta webhook subscriptions)
- CLAUDE.md pin #1 (Meta app-verification separate issue — NOT related to webhook subscriptions)

## When NOT to use this skill

- Inbound messages missing entirely (all directions, all fields) → probably `META_APP_VERIFIED` / OAuth issue, see CLAUDE.md pin #1
- Webhook 500s in our Laravel logs → app bug, not a subscription issue
- Only ONE customer affected → per-page problem (re-subscribe that page), not app-level
