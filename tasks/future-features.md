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

## AI-assisted AI Config setup wizard

**Where:** `/settings/ai/config` — a new entry point on the Sales Goal tab (or top-of-page banner) that says "Let the AI set this up for you." Clicking it opens a chat panel.

**Why it matters:** The current 5-tab config (Sales Goal, Knowledge, Behavior, Handoff, Comments) is intimidating for a new customer with no idea what "sales_goal_preset" or "reply_instructions" even means. First-time setup takes 15-30 min of typing and copy-paste from the customer's own head. AI interview + auto-fill collapses that to a 5-min conversation.

**Rough shape:**
1. Chat UI (existing `AiChat` component can be reused as scaffolding) with a "config-fill" system prompt.
2. AI asks in the customer's language: "What's your business?" → "What products or services do you sell?" → "What's your typical price range?" → "What's your tone with customers (friendly / formal / casual)?" → "What times are you actively available?" → "Any words that mean 'human take over now'?"
3. AI collects answers into a structured intermediate object.
4. AI shows the customer a preview: "Here's what I'm going to fill in — okay to apply?"
5. On confirm, the Livewire `AiConfig` component receives a `hydrateFromAi(array $filled)` call that sets all the props, marks `$dirty = true`, does NOT save.
6. Customer lands on Sales Goal tab with fields pre-populated, can adjust, then Save Changes triggers the normal wizard flow (Save → Knowledge → Save → Behavior → …).

**Cost:** one Nara conversation (10-20 turns, ~$0.01-$0.05 per session). Saves customer-support time answering "how do I fill this in?".

**Prerequisites:** the wizard-flow save advance from `2026-09-09` change is already the foundation — this feature just prepends an optional intake step.

**Blocked on:** nothing structural. Nice to wait until Meta App Review lands (comments) so we don't collect answers for a feature we can't deliver.

---

## (add future items below this line)

---

## Per-team customer data isolation (architectural concern — revisit at scale)

**Context (2026-11-18, surfaced by Lavin Fashion feedback):** Today every team's conversations live in the same `conversations` + `messages` tables, scoped by `page_id → team_id`. This works fine at single-digit-team scale, but has long-term risks:

- **Cross-team phone collision.** Two of our customers could have the same phone number in their contact list (not uncommon). Our `Contact` model is scoped per-team, so technically no mixing today — but any future "find prior order by phone" feature needs to be scrupulously team-scoped or we'll leak data between tenants.
- **Shared Conversation table at high volume** means a bad index or a runaway query from one team can slow down every other team's inbox.
- **Audit/export requests.** When Customer X asks "give me all my data" we can serve it, but it requires joining across the shared tables with team_id filter in every query — missing one filter is a GDPR/privacy incident.

### What would need to happen

Either:
1. **Per-team schema isolation** — each team gets its own `conversations_<team_id>` + `messages_<team_id>` tables. Complicated to maintain (migrations × N teams), but hardest-to-mess-up. Standard pattern for strict B2B SaaS with data residency requirements.
2. **Per-team database** — isolate at the DB level, one tenant per schema. Overkill for our scale but unavoidable if any customer ever demands single-tenant deployment.
3. **Status quo + policy** — keep shared tables, enforce team_id filters via query scopes (`BelongsToTeam` trait on every model), and audit-test every join. Cheapest, safest if we're careful.

**Recommendation for now:** Option 3. Add `BelongsToTeam` trait to `Conversation`, `Message`, `Contact` and have `boot()` register a global scope that REQUIRES team_id in every query. Anyone writing a query without team context gets a thrown exception. Prevents accidental cross-team leaks without the migration cost.

**Revisit when:** we hit 50+ paying teams OR any customer asks "is my data isolated?" with real intent.

---

## Cross-conversation customer order lookup (Lavin feedback 2026-11-18)

**Problem:** Customer places an order on WhatsApp conversation #A. Days later, closes that chat and opens a NEW WhatsApp chat window to our business (conversation #B). AI in conv #B has no idea they already ordered. If they ask "where is my order?" with their phone, AI treats them as a new customer and runs the order-capture flow.

**Not a bug — a knowledge gap.** My dedupe prevents the DUPLICATE SHEET ROW outcome (if AI re-fires complete() the sheet is blocked), but the customer still has a bad conversation experience ("why are you asking me stuff you should already know?").

### Solution (NOT AI-based — cheap DB lookup)

When any inbound message arrives, prepend to the AI's system prompt:
```
[Returning customer context] This contact (phone: 01026448322) has a
prior confirmed order on this team: "1 × شال كبير أسود + 1 × شال كبير
بيج" on 2026-11-17. Address: حدائق أكتوبر مشروع 1185.
```

Lookup is 1 DB query against `conversations` where `contact_id = X` and `captured_data.confirmed_at IS NOT NULL`. Essentially free — adds maybe 10ms per AI call. **No AI reads or costs incurred.**

Then prompt Rule #10 (returning-customer) already knows what to do — doesn't re-confirm.

**Scope:** ~2 hours work. Universal benefit (every team).

**Blockers:** Need contact-matching key. Current `Contact` model uses `(team_id, phone)` as the natural key. If customer uses different phone across convs (common — especially if they send TWO phones in one order), the match fails. Workaround: fuzzy-match on last 10 digits.

---

## External-message order capture (Lavin feedback 2026-11-18)

**Problem:** Some customers close orders by replying to Messenger DIRECTLY from the owner's phone (not through our app's inbox UI). Those messages come in as `sender_type=external`, and our AI never processes them. Orders closed this way never reach the sheet.

**Why NOT building this now:** You correctly flagged: parsing "order" from external messages means hard-coding e-commerce patterns (price, phone, address, product). But not every customer is e-commerce — a barber booking appointments has completely different message shapes, a lawyer taking consultations another. Writing a generic parser that works for all would be over-engineering, and hard-coding e-commerce patterns would break non-e-commerce tenants.

### If we ever do build it

- Make it per-team **opt-in** via a `capture_external_messages` flag on `ai_configs`
- Use the team's own `required_capture_fields` + `system_prompt` as the hint
- Use an LLM pass to extract (NOT regex), prompted with the team-specific field list
- Cost: ~$0.0001 per external message processed — only for teams that opt in
- Scope: ~1 day

**Trigger to revisit:** When 3+ teams complain about the same "orders closed on my phone don't reach my sheet" issue.

---

## Prompt refactor — business-type templates (NOT universal rules)

**Problem:** Rules #0, #6, #6.1, #7, #9, #10, #11 in Lavin's prompt are useful for every ecommerce team — but Lavin-specific today. Writing them again for every new customer is lazy copy-paste.

**Why NOT "universal rules for all businesses":** You correctly rejected this. A barber's AI doesn't need rules about "سجلت الأوردر" format — it needs appointment-confirmation format. A lawyer needs consultation-booking format.

### Solution: business-type prompt templates

Define a few archetypes:
- `ecommerce_cod` (Lavin, OT1 Pro merchants, clothing/food/accessories shops) — our current "ecommerce" preset + the 12 behavioural rules
- `appointment_booking` (hair salon, clinic, consultations) — different capture fields (service + time slot + name + phone), different "confirmation format"
- `lead_gen` (info-only, funnel leads to sales human) — minimal AI closure, prioritise handoff

Add a `business_type` column on `teams`. During onboarding wizard, user picks one. `AiConfig` builds its system prompt by composing `BaseIdentity + BusinessTypeRules + TeamSpecificRules`.

**Scope:** ~4 hours (if we only define 3 archetypes). Can grow as we onboard more diverse customers.

**Blocker:** We need at least 2-3 customers in each archetype to design the rules confidently. Right now we have 1 ecommerce (Lavin) + a few OT1-Pro-adjacent. **Revisit after we onboard customer #3 or #4** so the archetype rules are validated by real patterns, not guessed.

---

## Sheet row UPDATE (not just append) when customer modifies order

**Problem:** Dedupe blocks any second push — meaning if customer adds an item after confirming (e.g. "and add a 3rd one for my sister"), the sheet doesn't reflect it.

### Three implementation options with tradeoffs

| Option | Behaviour | Pros | Cons |
|---|---|---|---|
| **A. Push new row with "UPDATE" event** | New row with Event="Order updated" | Clear audit trail | Sheet grows, operator has to reconcile 2 rows per order |
| **B. In-place update via Apps Script search** | Overwrites existing row in sheet | Clean single-source | Requires Apps Script rewrite (search by Conv ID + update), loses history, 2× API calls per update |
| **C. New row + marks old "SUPERSEDED"** | Both rows visible, old annotated | Full history + current state | Needs Apps Script change, 2 rows per updated order |

**Recommendation:** Start with A when needed. Promote to B only if operator complains about reconciliation.

**Trigger to revisit:** First time a customer updates an order post-confirmation AND the owner notices the sheet is out of date.

---
