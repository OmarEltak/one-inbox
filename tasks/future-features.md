# Future Features Backlog

Ideas surfaced during other work that aren't scoped yet but are worth designing later. Each entry: what, why it matters, cost, prerequisites, rough shape of the work.

---

## Meta Marketing Messages on Facebook Messenger (paid, opt-in, bypasses 24h)

**Meta permission name:** `marketing_messages_messenger`

**What it is:** Meta's official channel for sending **paid promotional messages on Facebook Messenger** outside the standard 24-hour reply window, to users who have explicitly opted in via a Recurring Notifications card.

### How it works

1. **Opt-in card in Messenger.** Business sends a Meta-designed UI card ("Recurring Notifications") inside an existing conversation. The user picks a cadence (daily / weekly / monthly) and taps "Allow." Without that tap, this permission does nothing for that person.
2. **Post-opt-in send window.** Once opted in, the business can send scheduled promotional messages at the agreed cadence for a Meta-defined validity window (typically 12 months). After expiry, the user must re-opt-in.
3. **Content moderation.** Message content must match the topic the user opted in for. Meta reviews sample templates before allowing at-scale sends.
4. **Billing.** **Every sent message is billed.** Rates are per-country and change; roughly **$0.005–$0.15 per message** depending on region.
5. **Scope.** **Facebook Messenger only.** Instagram Direct uses a different mechanism entirely (`MESSAGE_TAG` categories + `human_agent_tag`).

### Does it bypass the 24-hour rule?

- **Yes**, in the sense that after a valid opt-in you can message that specific user weeks or months after their last inbound message.
- **No**, in the sense that you cannot blast your existing Facebook followers — the 24h rule still applies to non-opted-in users. This is a parallel rail, not a global override.

### How it differs from what we already have

| Feature | Our current bulk campaigns (Phase A) | Main's `fb8aea8` 24h enforcement | `marketing_messages_messenger` (this) |
|---|---|---|---|
| **Channel** | WhatsApp via Wuzapi | Facebook + Instagram via Meta Graph API | Facebook Messenger only via Meta Graph API |
| **24h rule** | N/A (Wuzapi bypasses Meta) | Enforced — filters contacts with last inbound > 24h | Circumvents — but only for opted-in recipients |
| **Opt-in model** | Implicit (uploaded phone list) | Implicit (someone messaged you first) | **Explicit tap on a Meta UI card** |
| **Cost** | Free (self-hosted Wuzapi) | Free (inside window) | **Paid per message** |
| **Ban risk** | High (unofficial WA Web) | None | None (official Meta rail) |
| **Meta App Review** | Not needed | Uses existing `pages_messaging` | **New permission — needs App Review with Advanced Access** |
| **Volume ceiling** | ~1k/day/number (Wuzapi practical) | Whatever fits the 24h reply window | Whatever your opted-in audience is |

### The three actual escape hatches from the 24h rule on Messenger

For completeness, Meta gives businesses three official ways to message a user > 24h after their last inbound. All three require opt-in of some kind:

1. **`MESSAGE_TAG` types** — free, content-restricted to a fixed list (order updates, appointment reminders, account issue notifications, confirmed event updates, post-purchase updates). NOT for marketing.
2. **`marketing_messages_messenger` / Recurring Notifications** — the one described above. Paid, opt-in via card, marketing content allowed.
3. **One-Time Notification (OTN)** — deprecated in most regions as of 2024; superseded by Recurring Notifications.

### Prerequisites before we can build this

1. **Meta App Review with Advanced Access for `marketing_messages_messenger`.** Adds to the pending permission list in `CLAUDE.md` pin #1. Cannot ship without this — non-admin users would get "Feature unavailable" on the OAuth callback.
2. **New DB column:** `contacts.messenger_recurring_notification_opted_in_at` (or a normalized `messenger_optins` table if we want per-topic granularity).
3. **Opt-in card send flow.** A separate feature customers run BEFORE they can do marketing sends — sends the Recurring Notifications card to eligible contacts inside their 24h window, then records the opt-in via webhook.
4. **Cost estimator UI.** Reads Meta's pricing endpoint per country. Unlike the dead-code Cloud API estimator that was in `Campaigns\Index` before `fb8aea8` removed it — this one would be real.

### Rough shape of the work (if we ever spec this)

**Phase D — Messenger marketing sends via Recurring Notifications**
- New wizard `/campaigns/messenger-marketing/new` sibling to `/campaigns/whatsapp/new`.
- Audience picker limited to `contacts.messenger_recurring_notification_opted_in_at IS NOT NULL`.
- Per-campaign cadence must match what recipients opted in for.
- New `MessengerMarketingSender` service — sole Meta Graph API `messages` call site for this rail (mirrors the `WhatsAppSender` sole-call-site invariant).
- Cost estimate step in the wizard (reads Meta pricing endpoint, snapshots at launch).
- Reuses the `campaigns` queue + backpressure + page circuit-breaker infrastructure Phase A already built.

**Phase D.1 — Opt-in card flow**
- Separate feature: send a Recurring Notifications card to a user inside their 24h window.
- Wire up the `messaging_optins` webhook subscription (Meta will POST when a user taps Allow/Deny).
- Write into `contacts.messenger_recurring_notification_opted_in_at`.

### Cross-references

- Meta docs: https://developers.facebook.com/docs/messenger-platform/send-messages/recurring-notifications
- Permission reference: https://developers.facebook.com/docs/permissions#marketing_messages_messenger
- Related project pin: `CLAUDE.md` pin #1 (Meta App Review two-milestone process) — add this permission to the App Review submission list.
- Related project code: `app/Services/Platforms/FacebookPlatform.php` (where the current Meta Graph API subscription lives — see also `meta-webhook-two-layer-subscription` skill).

---

## (add future items below this line)
