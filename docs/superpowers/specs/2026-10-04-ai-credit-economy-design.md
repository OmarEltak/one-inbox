# AI Credit Economy — Design

**Date**: 2026-10-04
**Status**: Draft — awaiting review
**Owner**: Omar
**Related**: `docs/OT1_LIMITS.md` §11 (plan-tier campaign limits), `docs/ARCHITECTURE.md` §11 (`canDispatchAi`), `docs/ARCHITECTURE.md` §13 (quota banner), CLAUDE.md pin #4 (single AI dispatch gate)

---

## 1 · Problem

Three gaps today:

1. **AI chat silently caps context at 14-18 conversations** (`AdminChatContext.php:89`, 6 KB char budget) — users ask "analyze last 1,000 contacts" and get "I can only see 14." No way to pay to unlock more.
2. **No visible credit meter.** Users see a quota-exhausted banner only at 100 %. They can't tell they're at 80 % until it's too late.
3. **No prepaid top-up.** `config/stripe.php` has `ai_credits` per plan but Stripe isn't wired, Lemon Squeezy isn't installed, and all actions cost exactly 1 credit regardless of real cost (an AI chat turn and a 1,000-contact deep analysis both decrement by 1 — the latter is 50-100× more expensive to us).

## 2 · Non-goals (ruthless YAGNI)

- Multiple currencies. Lemon Squeezy handles that as merchant-of-record; we price in USD.
- Per-user credits inside a team. Credits live on the team.
- Rollover of unused monthly credits. "Use it or lose it" preserves the revenue upside; prepaid wallet is the way users keep unused value.
- Credit refunds inside the app. Any dispute is handled through Lemon Squeezy support (they're MoR).
- Pay-per-token metering (hour-by-hour variable pricing). Flat per-action cost table; we eat the variance.

## 3 · Core architectural decisions

### 3.1 · Two balances, one chip

```
+----------------------------------------------------------+
|  AI credits:  342 / 500 (plan)   +  120 (wallet)         |
|  [=========================     ] 68 % used this cycle   |
+----------------------------------------------------------+
```

- **Monthly allowance** — resets on the billing-cycle anniversary (`teams.billing_cycle_anchor`, defaults to signup day). Drained first. Use it or lose it.
- **Prepaid wallet** — purchased in packs (Lemon Squeezy one-time products). Never expires. Drained only after monthly hits zero.

Rationale: this is Vercel's and OpenAI's model. Users intuitively understand "included vs. extra." Keeps the revenue upside of monthly (don't let a Pro-plan user hoard 60 k credits across 12 months) while giving a safety net for burst use.

### 3.2 · No payment provider — manual top-up via PayPal / bank / WhatsApp

Changed from the first two drafts after Omar confirmed OT1 Pro is not registered as a legal entity yet, with no users yet, and will use "PayPal or bank transfer" when the first paying user arrives.

- **No Stripe. No Lemon Squeezy. No PayPal SDK. No webhook controller. No checkout UI.** You can't open a merchant account without a legal entity anyway, and automating billing before product-market fit is premature optimisation.
- All credit grants happen through a **super-admin screen** where Omar manually adds credits to a team after confirming payment landed in his PayPal or bank account.
- Users see a `/settings/billing/top-up` page that explains pricing and gives them Omar's PayPal link + bank details + a "Message founder on WhatsApp" button (reuses the sidebar WhatsApp chip pattern from OT1_LIMITS §11 Phase 1).
- The dormant `teams.lemon_squeezy_id` + `teams.lemon_squeezy_customer_id` columns stay (nullable, do no harm). The `laravel/cashier` package stays installed but un-configured. If/when a provider is wired later, the credit ledger is already provider-agnostic.
- Rename `config/stripe.php` → `config/plans.php` (plans aren't a Stripe concept — the name was misleading).

### 3.3 · Zero card data on our server

- Checkout is **hosted on Lemon Squeezy** (we never see the card). We store only `lemon_squeezy_customer_id` + `lemon_squeezy_subscription_id` — opaque pointers.
- **"Disconnect payment" button** on `/settings/billing` POSTs to `/billing/disconnect` which calls the LS API `cancelSubscription`, nulls both pointers on the team, and downgrades the team to the Free tier at the end of the current billing cycle (not immediately — they paid for the month).
- No webhooks store PII beyond `customer_email` on the LS customer record (which the user controls).

### 3.4 · Append-only credit ledger

```sql
CREATE TABLE ai_credit_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    delta INTEGER NOT NULL,                 -- positive = grant, negative = charge
    balance_type ENUM('monthly','wallet') NOT NULL,
    reason VARCHAR(64) NOT NULL,            -- 'ai_chat_turn', 'deep_analysis', 'monthly_grant', 'pack_purchase', 'refund_outage'
    cost_source_type VARCHAR(64) NULL,      -- morph target (Message, DeepAnalysisJob, LemonSqueezyOrder)
    cost_source_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,     -- who triggered it (null for system)
    metadata JSON NULL,                     -- {model, tokens_in, tokens_out, ls_order_id, etc.}
    created_at TIMESTAMP NOT NULL,
    INDEX idx_team_created (team_id, created_at),
    INDEX idx_team_reason (team_id, reason)
);
```

Balance is a `SUM(delta)` partitioned by `balance_type`. Cached in Redis (`team:credits:{id}:monthly`, `team:credits:{id}:wallet`) with 60 s TTL — the hot path (every AI dispatch) never hits MySQL. Writes invalidate the cache atomically.

Monthly reset = a scheduled job that for each team posts a `delta = +plan_quota - unused_monthly_remainder, reason = 'monthly_reset'` so the ledger always balances.

### 3.5 · Cost table in config

`config/ai_costs.php`:

```php
return [
    'ai_chat_turn' => 1,            // operator asks AiChat a question
    'ai_reply_outbound' => 1,       // AI responds to a customer message
    'ai_lead_score' => 0,           // free (we eat this — happens on every inbound)
    'ai_comment_reply' => 1,
    'deep_analysis_per_100_contacts' => 5,  // bulk analysis — scales with cohort size
    'agent_audit' => 3,
    // ...
];
```

Costs tunable without code changes. Every dispatch site calls `AiCredits::charge($team, $action, $meta)` which looks up the cost, writes a ledger row, decrements the cached balance, and returns a `Receipt` object.

### 3.6 · Confirmation gate for expensive actions

`AiCredits::charge()` throws `ExpensiveActionRequiresConfirmationException` if:

- `cost > 5` AND
- team setting `auto_deduct_expensive_actions = false` (default) AND
- the action's `confirmation_token` is not present in the request

The caller catches and returns a `pending_action` block to the AI chat UI:

```json
{
  "type": "confirmation_required",
  "cost": 50,
  "balance_after": 290,
  "reason": "Deep analysis of 1,000 contacts",
  "action_token": "<signed, 10-min TTL>"
}
```

The UI renders a modal:

> **This will use 50 AI credits.**
> You'll have 290 left after.
> [ ] Always auto-deduct for actions ≥ 5 credits (don't ask me again)
> **Confirm** · Cancel

Confirmed → UI re-dispatches with `action_token` → charge succeeds. Checkbox persists to `team.auto_deduct_expensive_actions`.

### 3.7 · Header meter

Replace the existing banner-only pattern with an always-visible pill in `components/layouts/app/header.blade.php`:

- Green (≤ 60 % used), amber (60-85 %), red (> 85 %)
- Click → opens `/settings/billing` with the ledger visible
- Hover tooltip: "`342 / 500 monthly · 120 wallet · resets in 11 days`"
- Keeps the existing full-width banner for 100 % exhaustion + upstream pause (unchanged)

## 4 · Manual top-up flow (replaces payment-provider integration)

### 4.1 · Published pricing (reference only — no automation)

**Plans** (monthly credit allowance, set on team record manually by Omar after first payment):

| Plan     | Price/mo | Monthly credits |
|----------|---------:|---------------:|
| Free     | $0       | 50             |
| Starter  | $29      | 500            |
| Pro      | $79      | 5 000          |
| Business | $199     | 10 000         |
| Agency   | $499     | 50 000         |
| Enterprise | custom | unlimited      |

(These numbers already live in `config/stripe.php` → will be renamed to `config/plans.php`.)

**One-time credit packs** (shown on `/settings/billing/top-up`):

| Pack      | Price | Credits | USD per credit |
|-----------|------:|--------:|---------------:|
| Small     | $10   | 500     | $0.020         |
| Medium    | $35   | 2 000   | $0.0175        |
| Large     | $100  | 7 500   | $0.0133        |

Pack prices are deliberately higher per-credit than subscriptions so heavy users have incentive to subscribe when they're ready.

### 4.2 · `/settings/billing/top-up` page

Visible to all team owners/admins. Shows:

- Current team balance (plan + wallet) and days until monthly reset
- Pricing table (plans + packs above)
- Payment instructions:
  - **PayPal**: `[omar's paypal.me link]` with amount pre-filled
  - **Bank transfer**: IBAN + beneficiary name + note "include your team ID in the reference"
  - **Chat with us**: "Message founder on WhatsApp" button → `wa.me/201026361218` with prefilled message `"Hi, I'd like to upgrade team #{team_id} to {plan or pack}"`
- Big "I've sent payment — notify Omar" button → sends an email to Omar with team ID + what they bought + their email, so Omar knows to go top them up

Zero card capture, zero PCI concern. The page is literally instructions + a notification trigger.

### 4.3 · Super-admin "Grant credits" screen (`/super-admin/billing`)

Only Omar sees it. One row per team showing current balance + last 10 ledger entries. Primary form:

```
Team: [dropdown]
Action: ( ) Add credits  ( ) Change plan  ( ) Refund
Amount: [__] credits
Balance type: ( ) Wallet (never expires)  ( ) Monthly bonus (resets with cycle)
Note: [textarea — "PayPal $35 received 2026-10-05, order #..."]
[Grant]
```

Submit → writes a ledger row with `reason = 'manual_grant'`, actor_user_id = Omar, metadata captures the note. Also records the payment reference string separately for easy search later.

### 4.4 · There is nothing to "disconnect"

Per the user's original ask ("give them the option to disconnect their payment credentials at any time") — we store no payment credentials, so there's nothing to disconnect. The privacy policy says this explicitly (§7.1 below).

If a user wants a plan downgrade, they email / WhatsApp Omar. Omar changes it in the super-admin screen. Simple and honest for the pre-automation stage.

## 5 · Deep Analysis — the first big-cost action

The whole reason credits exist. New feature:

### 5.1 · User flow

In AiChat, user asks: *"analyze the last 1,000 contacts for brandk and tell me who's ready to buy"*.

Parser detects `analyze last N contacts` → routes to `DeepAnalysisService::quote($team, $cohort)`:

- Cost = `ceil(count / 100) * config('ai_costs.deep_analysis_per_100_contacts')` → 1 000 contacts = 50 credits
- Returns `confirmation_required` pending action (per §3.6)

User confirms → `DispatchDeepAnalysisJob` queued on new `heavy-analysis` queue (isolated worker, won't starve `urgent`). Job:

1. Pulls cohort conversations (contacts, messages inbound + outbound, lead_score history)
2. Chunks into 10 batches of 100 contacts
3. For each chunk → single NaraRouter call with a focused prompt ("summarize themes, objections, hot leads from these transcripts")
4. Aggregates all 10 summaries → one final NaraRouter call → structured JSON result
5. Writes to `deep_analyses` table (team_id, cohort_filter, result_json, token_usage, created_at)
6. Broadcasts via Reverb to the originating AiChat session → "Your analysis is ready"
7. AiChat loads the stored result on next turn and answers from it (not re-analyzing)

Why async + stored: a single AiChat turn can't block for 60-120 s. Storing lets the user re-ask about the same cohort without re-paying.

### 5.2 · Agent audit sub-feature

Same path. User asks: *"audit how our agents handled the last 200 Mishkah conversations"*.

Cost = 3 credits (small cohort, text-only, no AI calls per agent — just aggregation). Deep Analysis job pulls `messages WHERE sender_type = 'user'` grouped by `handled_by_user_id`, computes `avg_response_time`, `conversations_touched`, `conversion_rate = COUNT(status='converted') / COUNT(*)`, hands the aggregate to NaraRouter for prose summary.

(Note: `handled_by_user_id` doesn't exist yet. Added in this spec — new migration.)

## 6 · Live-chat context fix (bundled, no new credit cost)

Per the prior conversation: `AdminChatContext.php:89` caps at 40 conversations / 6 KB / inbound-only. Change:

- Keep the default (`$maxConversations = 40`, `$charBudget = 6000`, inbound-only) for the AI's **general awareness block** sent on every turn
- Add a **targeted-page expansion** path: when the operator names a specific page (parsed by existing `page-mention` logic), inject a *second* digest for that page with `$maxConversations = 150`, `$charBudget = 30000`, **both directions** (inbound + outbound so moderator audit works)
- Still a hard ceiling. Beyond that = Deep Analysis (paid).

Rationale: this gets Mishkah from 14 → ~80-100 conversations visible when the operator asks about Mishkah specifically. Enough for honest per-page questions. Bulk 1 000-contact queries still gated behind credits.

### Retry + idempotency (prior ask)

The "AI took too long or returned nothing. Please try again" error path in `AiChat@processMessage`:

- Wrap the NaraRouter call in a `Str::uuid()` idempotency key, hash of (team_id, operator_message_hash)
- On exception / empty response → retry up to 2× with 500 ms, 2 s backoff
- Idempotency key stored in Redis for 60 s — concurrent dupes return the cached response, user never sees two charges or two replies
- Only count a credit charge after the first successful completion (ledger has unique index on `metadata->>'$.idempotency_key'`)

## 7 · Privacy / Terms updates

### 7.1 · `/privacy` — add section

> **Payment data.** We do not accept card payments through our website and we do not store any payment card details. Payments are made manually via PayPal or bank transfer to the business owner, who credits your account after confirming the payment. The only payment-related data we hold is the amount granted, the method you told us you used (e.g. "PayPal"), and any reference you gave us (e.g. order number) — stored in an audit log on your team record. You may request deletion of this log at any time by emailing support@ot1-pro.com.

### 7.2 · `/terms` — add section

> **Credits, allowances, and refunds.** Each subscription plan grants a monthly AI-credit allowance that resets on your billing-cycle anniversary and does not roll over. Credit packs purchased separately do not expire. Credits consumed by AI actions are non-refundable except where the action failed due to a verified system outage, in which case the credits are automatically returned. Refunds of money (as opposed to credits) are handled case-by-case — contact support@ot1-pro.com.

### 7.3 · New `/pricing-faq` page

Public page explaining: what counts as 1 credit, how Deep Analysis is priced, how auto-deduct works, how to request a top-up. Lowers support load.

## 8 · Rollout order

| Phase | Scope | Dependencies | Est. |
|-------|-------|--------------|------|
| **A** | Ledger table (`ai_credit_ledger`) + `AiCredits` service with `charge`/`refund`/`grant`/`balance`. Rename `config/stripe.php` → `config/plans.php`. Backfill: set every team's monthly balance to its plan quota as of go-live (no retroactive charging). All existing AI dispatch sites now call `AiCredits::charge()` on success. No user-visible change. | — | 1 day |
| **B** | Header meter chip (green/amber/red) + `/settings/billing` page showing current balances, days to reset, and ledger history (read-only) | A | 0.5 day |
| **C** | Live-chat context fix (`AdminChatContext::customerDigest` targeted-page expansion to ~100 convos / 30 KB / both directions) + retry/idempotency on `AiChat@processMessage` NaraRouter calls | — | 0.5 day |
| **D** | Cost table (`config/ai_costs.php`) + confirmation modal (>5 credit threshold) + auto-deduct opt-in on team setting + Deep Analysis job on new `heavy-analysis` queue + results stored in `deep_analyses` table + first premium action live (chat-triggered 1 000-contact analysis) | A, B | 1.5 days |
| **E** | Super-admin `/super-admin/billing` screen: grant/change-plan/refund form writing to ledger with Omar as actor, searchable payment-reference log, team balance list | A | 0.5 day |
| **F** | User-facing `/settings/billing/top-up` page: pricing table, PayPal link, bank details, WhatsApp button, "I've sent payment" email trigger to Omar | A, E | 0.5 day |
| **G** | Privacy policy addendum + terms addendum + new `/pricing-faq` public page. Arabic translations for every new string. | B, D, F | 0.5 day |
| **H** | Agent-audit sub-feature on Deep Analysis path (needs `messages.handled_by_user_id` migration + backfill from existing `sender_type='user'` + `user_id` if captured) | D | 1 day |

**Total: ~6 days of focused work.** Everything ships without touching a payment provider. When OT1 registers and signs up for a real provider later, that's a separate smaller spec that bolts onto the ledger already built here.

## 9 · Open risks / things I don't know

- **Credit refund on provider outage** — if NaraRouter is fully cooled down and a chat turn fails after charging, we need an auto-refund. Design: `SendAiResponse` on terminal failure → `AiCredits::refund($receipt, 'outage')` writes a positive ledger row. Needs testing in Phase D.
- **Backfill decision confirmed** (per approval tick): existing teams start at their plan quota as of go-live. Zero retroactive charging. The old `message_count`-based banner logic in `EnforcePlanLimits::hasAiCredits()` keeps working during the migration window (both systems return the same answer); we remove the old path once the ledger has been in production for a week.
- **Timezone for monthly reset** — chose UTC (matches existing `recordOverdueAiSent` TTL). Billing-cycle anchor = team's created-at date, converted to UTC. Documented in `/pricing-faq`.
- **Confirmation-modal UX in Arabic** — the modal copy needs an Arabic variant that reads cleanly in RTL. Covered in Phase G.

## 10 · Tests to write (TDD, 80 % coverage per project rules)

- `tests/Unit/Services/AiCreditsTest.php` — charge, refund, two-balance drain order (monthly first then wallet), confirmation gate, idempotency key dedup, outage auto-refund
- `tests/Feature/Billing/SuperAdminGrantTest.php` — Omar grants/refunds, ledger row written correctly, only super-admins can access
- `tests/Feature/Billing/TopUpPageTest.php` — pricing visible, "I've sent payment" notifies Omar
- `tests/Feature/AiChat/DeepAnalysisConfirmationTest.php` — >5 credit action triggers modal, confirm proceeds, auto-deduct opt-in persists
- `tests/Feature/AiChat/HeaderMeterTest.php` — renders green/amber/red at correct thresholds
- `tests/Unit/Services/Ai/CustomerDigestExpansionTest.php` — targeted-page expansion returns ~100 convos w/ both inbound+outbound
- `tests/Feature/AiChat/RetryIdempotencyTest.php` — timeout retried, dupe request returns cached response, no double-charge
- Extend `tests/Unit/ArabicTranslationCoverageTest.php` with every new string on top-up page, meter chip, confirmation modal, pricing-faq

---

## Approvals (confirmed by Omar 2026-10-05)

- [x] **Two balances** — monthly (resets, use-it-or-lose-it) + wallet (never expires, drained second)
- [x] **No payment provider** — manual via PayPal / bank / WhatsApp; super-admin grants
- [x] **Pack prices** — $10 / $35 / $100 for 500 / 2 000 / 7 500 credits
- [x] **5-credit threshold** for confirmation modal
- [x] **Deep Analysis cost** — 5 credits per 100 contacts (1 000 = 50)
- [x] **Backfill** — set every team to its plan quota as of go-live, no retroactive charging
- [x] **Phase order A-H** as listed in §8

Next step: invoke `superpowers:writing-plans` to produce the detailed implementation plan.
