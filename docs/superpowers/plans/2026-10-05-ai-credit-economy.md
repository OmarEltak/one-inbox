# AI Credit Economy — Implementation Plan

> **For agentic workers:** Each phase below is dispatched to a fresh subagent. Follow the project rules in `CLAUDE.md` (pins #4, #5, #9, #10, #12 are directly relevant). TDD per `~/.claude/rules/php/testing.md`. Use the existing Flux 2.x modal pattern (`Flux::modal('name')->show()` — see CLAUDE.md pin #6). Arabic coverage per `tests/Unit/ArabicTranslationCoverageTest.php`.

**Spec:** `docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md` (read it in full before coding)
**Capacity reference:** `docs/OT1_LIMITS.md` §13
**Goal:** Ship a credit-metered AI system with manual payment flow (no card processor). First premium feature = Deep Analysis of bulk conversations.
**Tech:** Laravel 12, Livewire 4, Flux 2.x, MySQL (prod) / SQLite (dev), Redis cache, Pest, PSR-12.
**No commits:** The implementing subagent must NOT run `git commit`. Omar reviews a diff first.

---

## Phase dependencies (orchestrator reference)

```
A (ledger + AiCredits) ─┬─► B (header meter + billing view) ──┐
                        ├─► E (super-admin grant screen) ─────┤
                        │                                      │
                        └─► D (cost table + confirmation       │
                              + Deep Analysis)                 ├─► G (privacy/terms/pricing-faq + Arabic)
                                                               │
                                                               ├─► H (agent audit on Deep Analysis)
                                                               │
C (context fix + retry) ───────────────────────────────────────┤
                                                               │
E ──► F (user top-up page) ────────────────────────────────────┘
```

Parallel waves:
- **Wave 1**: A ∥ C
- **Wave 2** (after A): B ∥ E
- **Wave 3** (after B): D
- **Wave 4** (after D, E): F ∥ H
- **Wave 5** (after F): G

---

## Phase A — Ledger + AiCredits service

**Spec sections:** §3.4, §3.5 (partial), §3.6 (exception shape only, modal is Phase D)

**Files to create**
- `database/migrations/2026_10_05_000001_create_ai_credit_ledger_table.php`
- `database/migrations/2026_10_05_000002_add_auto_deduct_to_teams_table.php` (bool, default false)
- `database/migrations/2026_10_05_000003_add_billing_cycle_anchor_to_teams_table.php` (date, default = created_at)
- `app/Models/AiCreditLedgerEntry.php` (append-only — override `update()` and `delete()` to throw)
- `app/Services/Billing/AiCredits.php` (charge, refund, grant, balance, forTeam helper)
- `app/Exceptions/Billing/InsufficientCreditsException.php`
- `app/Exceptions/Billing/ExpensiveActionRequiresConfirmationException.php`
- `config/plans.php` (**copy** `config/stripe.php` contents, do not delete the old file in this phase — Phase B will swap references and delete)
- `tests/Unit/Services/Billing/AiCreditsTest.php`

**Files to modify**
- `app/Models/Team.php` — add `aiCreditsBalance()` relationship/accessor + `grantMonthlyCredits()` method called from a reset job
- `app/Http/Middleware/EnforcePlanLimits.php` — `hasAiCredits()` reads from `AiCredits::balance($team)->total()` instead of counting messages. Keep the old implementation behind a `config('plans.use_legacy_message_counter')` flag defaulting to `false`, remove the flag in a follow-up PR.
- Every AI dispatch site that currently decrements implicitly (`SendAiResponse`, `AiChat@processMessage`, `ScoreLeadJob`, `SendAiCommentReplyJob`) — call `AiCredits::charge($team, 'ai_reply_outbound', [...])` on success. For `ScoreLeadJob` use cost=0 (we eat scoring).

**Backfill**
- `database/seeders/BackfillAiCreditLedgerSeeder.php` — for every team, write one row: `delta = +plan_quota`, `balance_type = monthly`, `reason = 'backfill_go_live'`, `created_at = now()`. Idempotent on `reason`.

**Tests to write (TDD)**
- `AiCredits::charge` writes a ledger row and decrements cached balance
- Two-balance drain: monthly drains first, then wallet
- Idempotency: same `metadata.idempotency_key` within 60s returns the first receipt without a second write
- `charge` throws `InsufficientCreditsException` when total balance < cost
- `charge` throws `ExpensiveActionRequiresConfirmationException` when cost > 5 and team.auto_deduct_expensive_actions=false and no `confirmation_token` supplied
- `refund` writes a positive row with `reason='refund_outage'`

**Scheduled job**
- `app/Console/Commands/ResetMonthlyAiCredits.php` — runs daily; for each team where `today == billing_cycle_anchor_day_of_month`, zero out monthly leftover and grant plan quota. Append two ledger rows (zeroing + grant). Register in `routes/console.php`.

**Rules**
- The 12+ dispatch sites must still call `Team::canDispatchAi()` first (CLAUDE.md pin #4) — do not bypass it. `AiCredits::charge` is called **after** the AI call succeeds (post-success charge, matches spec §9 outage-refund logic).
- Run `php artisan test tests/Unit/Services/Billing/` and ensure green before completion.

---

## Phase B — Header meter + /settings/billing

**Spec sections:** §3.7, §6 (top-up page is Phase F; this is read-only ledger view)

**Files to create**
- `app/Livewire/Settings/Billing.php` — balance card + paginated ledger
- `resources/views/livewire/settings/billing.blade.php`
- `resources/views/components/ai-credit-meter.blade.php` (the pill component)
- `tests/Feature/Settings/BillingPageTest.php`
- `tests/Feature/Settings/AiCreditMeterTest.php`

**Files to modify**
- `resources/views/components/layouts/app/header.blade.php` — insert `<x-ai-credit-meter />` next to the user menu
- `lang/ar.json` — new strings
- Rename `config/stripe.php` → delete (Phase A copied it to `plans.php`). Grep for `config('stripe.`, replace with `config('plans.`. Verify no remaining references.

**Component contract**
```php
<x-ai-credit-meter />
```
Renders:
- Pill with "`342 / 500`" or (if wallet > 0) "`342 / 500 + 120`"
- Tooltip on hover: full breakdown + days-to-reset
- Colour: `emerald` ≤60 %, `amber` 60-85 %, `red` >85 % (contrast-guardrails safe pairs)
- `wire:navigate` to `/settings/billing`

**Rules**
- Contrast guardrails skill applies — amber variant on light shell = `bg-amber-50 text-amber-900 border-amber-200`.
- Arabic tested via the coverage test.

---

## Phase C — Live-chat context fix + retry/idempotency

**Spec sections:** §6

**Files to modify**
- `app/Services/Ai/AdminChatContext.php:89-162` — add a second call path. When `$pageId` is provided by the caller **AND** the operator's current turn names that page explicitly (not inferred), use `maxConversations = 150`, `charBudget = 30000`, and change line 112 from `->where('direction', 'inbound')` to NOT filter by direction (include both). Rename the per-conversation message count to `$perConversation = 10` for expanded mode. Keep the default call path (general awareness) unchanged.
- `app/Livewire/AiChat.php` — in the method that calls NaraRouter (grep `callChat` or similar), wrap with:
  - Idempotency key: `sha256(team_id . '|' . user_id . '|' . trim($message))`
  - Redis GET on the key first; if cached, return immediately
  - Retry 2× on exception / empty response: 500 ms then 2 s backoff
  - On success, Redis SET for 60 s
- `tests/Unit/Services/Ai/CustomerDigestExpansionTest.php` (new)
- `tests/Feature/AiChat/RetryIdempotencyTest.php` (new)

**Rules**
- Do NOT remove `coalesceRoles()` call (CLAUDE.md pin #9 — role-alternation invariant).
- The general-awareness digest default (every turn) stays at ≤6 KB — we are not blowing out the input token bill.
- Expanded digest only fires once per operator turn, only when a page is explicitly named. This is a bounded cost.

---

## Phase D — Cost table + confirmation modal + Deep Analysis

**Spec sections:** §3.5 (full), §3.6, §5

**Files to create**
- `config/ai_costs.php` with the cost map
- `app/Services/Ai/DeepAnalysisService.php` — `quote($team, $cohortFilter)` returns `['cost' => int, 'cohort_size' => int, 'description' => string]`
- `app/Jobs/DispatchDeepAnalysisJob.php` on queue `heavy-analysis`, timeout=300, tries=1
- `database/migrations/2026_10_05_000010_create_deep_analyses_table.php` (team_id, cohort_filter JSON, result_json, token_usage JSON, created_at)
- `app/Models/DeepAnalysis.php`
- `app/Events/DeepAnalysisCompleted.php` (Reverb broadcast)
- `resources/views/livewire/ai-chat/partials/confirmation-modal.blade.php`
- `tests/Feature/AiChat/DeepAnalysisConfirmationTest.php`
- `tests/Feature/Billing/CostTableEnforcementTest.php`

**Files to modify**
- `app/Livewire/AiChat.php` — parse "analyze last N contacts [for page X]" and "audit [agents|moderator]" into a `pending_action` of type `deep_analysis`. On confirm, dispatch `DispatchDeepAnalysisJob`. On broadcast completion, inject the stored result on the next turn.
- `app/Services/Ai/BuildsConversationPrompts.php` — add a `DEEP ANALYSIS RESULTS` block when a `DeepAnalysis` row exists for the current session's referenced cohort.

**Systemd (document only, don't execute)**
Add to `docs/OT1_LIMITS.md §13 — Capacity`: on prod, we need `one-inbox-queue-heavy.service` consuming only `heavy-analysis` with 1 worker, timeout=300. Omar will create the systemd unit on prod; the dev `php artisan queue:work` already picks up all queues.

**Rules**
- The Deep Analysis prompt per chunk MUST include a system message telling the model to return structured JSON (themes, hot leads, objections, agent response-time stats). The aggregator call compresses 10 JSON blobs into one final JSON. Store the final JSON; render it in the chat as prose.
- Charge happens on **job dispatch** (so the user doesn't game a 50-credit action by killing the tab). Outage refund kicks in only on terminal job failure (not retries).

---

## Phase E — Super-admin /super-admin/billing

**Spec sections:** §4.3

**Files to create**
- `app/Livewire/SuperAdmin/Billing.php`
- `resources/views/livewire/super-admin/billing.blade.php`
- `tests/Feature/SuperAdmin/BillingGrantTest.php`

**Files to modify**
- `routes/web.php` — add route behind super-admin middleware
- `resources/views/components/layouts/super-admin/sidebar.blade.php` (or wherever the super-admin nav lives — grep `super-admin` + `sidebar`)

**UI contract**
Form fields:
- Team selector (searchable)
- Action radios: Add credits · Change plan · Refund
- If Add credits: amount integer input, balance_type radios (Wallet / Monthly bonus), payment-reference text input, note textarea
- If Change plan: plan dropdown (from `config('plans.plans')`)
- If Refund: amount integer, reason dropdown (outage · support · chargeback), note textarea

Submit → `AiCredits::grant` or `AiCredits::refund` with `actor_user_id = auth()->id()` and `metadata.payment_reference` captured.

Beneath the form: paginated table of last 50 ledger rows across all teams (searchable by team name or payment reference).

**Rules**
- Super-admin check — reuse existing middleware (grep `SuperAdmin` middleware or policy).
- No Arabic needed for super-admin pages (English only per existing super-admin convention — verify by looking at `app/Livewire/SuperAdmin/Analytics.php`).

---

## Phase F — User /settings/billing/top-up

**Spec sections:** §4.1, §4.2

**Files to create**
- `app/Livewire/Settings/TopUp.php`
- `resources/views/livewire/settings/top-up.blade.php`
- `app/Mail/TopUpRequestedMail.php` (to Omar)
- `resources/views/emails/top-up-requested.blade.php`
- `tests/Feature/Settings/TopUpPageTest.php`

**Files to modify**
- `routes/web.php` — route for `/settings/billing/top-up` behind auth
- `app/Livewire/Settings/Billing.php` (Phase B) — add "Top up" button pointing here
- `lang/ar.json` — new strings
- `tests/Unit/ArabicTranslationCoverageTest.php` — include `settings.top-up` view

**Email payload**
Subject: `[OT1] Top-up request from Team #{id}`
Body includes: team ID, team name, requesting user name + email, pack/plan chosen, timestamp. Omar replies manually, then grants via Phase E.

**Rules**
- PayPal + bank details come from `config('plans.manual_payment')` — add to `plans.php` with env fallbacks (`PAYPAL_ME_LINK`, `BANK_IBAN`, `BANK_BENEFICIARY`, `OWNER_WHATSAPP`). Don't hardcode.
- "I've sent payment" button must be rate-limited to 1 click per team per 10 minutes to prevent spam (use `RateLimiter::attempt`).

---

## Phase G — Privacy / Terms / /pricing-faq + Arabic

**Spec sections:** §7

**Files to modify**
- `resources/views/pages/privacy.blade.php` — add payment-data section (§7.1 copy)
- `resources/views/pages/terms.blade.php` — add credits/refunds section (§7.2 copy)

**Files to create**
- `resources/views/pages/pricing-faq.blade.php`
- `routes/web.php` entry → `pricing-faq`
- Entry in footer nav / sitemap if applicable (grep `privacy` route reference to find the footer)
- Updates to `lang/ar.json` for every new string
- Extend `tests/Unit/ArabicTranslationCoverageTest.php`

---

## Phase H — Agent audit on Deep Analysis

**Spec sections:** §5.2

**Files to create**
- `database/migrations/2026_10_05_000020_add_handled_by_user_id_to_messages_table.php` (nullable FK, indexed)
- `database/seeders/BackfillHandledByUserIdSeeder.php` — populate from existing columns if any user attribution exists (grep for `user_id` on `messages`), otherwise leave null.
- `app/Services/Ai/AgentAuditService.php` — pulls `messages WHERE sender_type='user' AND handled_by_user_id IS NOT NULL` grouped by user, computes avg_response_time, conversations_touched, conversion_rate.

**Files to modify**
- `app/Jobs/DispatchDeepAnalysisJob.php` — accept `mode = 'agent_audit'` and route to `AgentAuditService` → aggregate → NaraRouter prose summary → stored in `deep_analyses.result_json`.
- `app/Livewire/AiChat.php` — detect "audit [moderator|agents|team]" phrasing + route to agent_audit mode.
- Any place a human sends an outgoing message (grep `direction = 'outbound'` + sender_type logic) — set `handled_by_user_id = auth()->id()`.

**Rules**
- Backfill is best-effort — unknown attributions stay null and the audit filters them out gracefully.

---

## Orchestration notes

- No subagent runs `git commit`. Omar reviews each phase's diff and commits himself.
- After each phase, run `php artisan test` to catch regressions in the full suite — NOT just the new tests.
- If a phase hits a build error, invoke the `build-error-resolver` agent with the error output before continuing.
- `tasks/journal.md` gets a one-line entry per completed phase.
