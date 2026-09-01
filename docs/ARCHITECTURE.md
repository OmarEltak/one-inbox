# One-Inbox Architecture Reference

> **Last updated:** 2026-07-02
> **Read this doc IN FULL before touching:** AI code (`app/Services/Ai/`, `app/Jobs/SendAiResponse.php`, `app/Jobs/ProcessIncomingMessage.php`), messaging/connections (`app/Livewire/Connections/`, `app/Http/Controllers/Webhooks/`), or sales-flow models (`Conversation`, `AiConfig`, `Contact`).
>
> **Every section below documents a load-bearing decision that a previous session almost broke by "fixing" it.** If your change touches any of these systems, verify you're not undoing an intentional design.

---

## Table of contents

1. [Meta App Verification & Managed Onboarding](#1-meta-app-verification--managed-onboarding)
2. [Page Model — Single Active Invariant](#2-page-model--single-active-invariant)
3. [AI Provider Architecture (Strategy Pattern)](#3-ai-provider-architecture-strategy-pattern)
4. [NaraRouter Failover Chain + 6h Reset Window](#4-nararouter-failover-chain--6h-reset-window)
5. [Sales-Flow State Machine](#5-sales-flow-state-machine)
6. [Per-Contact Daily AI-Reply Cap](#6-per-contact-daily-ai-reply-cap)
7. [Burst Debounce](#7-burst-debounce)
8. [AI Guardrails — Customer & Admin](#8-ai-guardrails--customer--admin)
9. [Abuse Detection via [SPAM_DETECTED] Marker](#9-abuse-detection-via-spam_detected-marker)
10. [Captured-Data Extraction](#10-captured-data-extraction)
11. [`canDispatchAi()` — Single AI Dispatch Gate](#11-candispatchai--single-ai-dispatch-gate)
12. [Silent-Skip vs Fallback-String Convention](#12-silent-skip-vs-fallback-string-convention)
13. [Team Upstream Pause + Banner](#13-team-upstream-pause--banner)
14. [Flux 2.x Modal API Gotcha](#14-flux-2x-modal-api-gotcha)
15. [Sidebar Gating Pre-Connection](#15-sidebar-gating-pre-connection)

---

## 1. Meta App Verification & Managed Onboarding

### Status
The project's Meta App (ID `1469090344742803`, "One Inbox Business") is in **LIVE mode** but does **NOT** have Advanced Access approved yet. This means direct OAuth flows work for the developer's own pages but **fail silently for real customers** at the OAuth callback.

### Design
Until Meta approves Advanced Access, real customers use a **managed onboarding flow**:

1. Customer clicks "Request connection" on `/connections`
2. A form dispatches `App\Models\OnboardingRequest` (status: `pending`)
3. Customer manually adds `omarEltak88` (the "OT admin" personal FB account) as a Page admin on their Facebook Business Page
4. A super-admin (currently just the owner) opens `/super-admin/onboarding-requests`, clicks "Start review", OAuths into the customer's page through their own OT Meta account, then uses the completion form to assign the newly-connected `Page` row to the customer's team
5. The `Page` transfer cascades to `Conversation` and `Contact` rows; the customer sees their inbox populate

### Load-bearing details

- **`$metaVerified` in the Connections view is a Blade `@php` variable**, not a Livewire component property:
  ```blade
  @php $metaVerified = (bool) config('services.meta.app_verified'); @endphp
  ```
- The value comes from `config/services.php` → `env('META_APP_VERIFIED', false)`
- **Do NOT rewrite `$metaVerified` as a Livewire property.** It's a view-level flag.
- **Do NOT default `$metaVerified` to `true` "to restore the OAuth button".** That's what the OAuth button would look like, but customers can't actually use it because Meta rejects unverified apps at the callback.
- When Meta approves us, the fix is a **one-line env change**: `META_APP_VERIFIED=true` in prod `.env` + `php artisan config:cache`. OAuth buttons return automatically.

### Files
- `resources/views/livewire/connections/index.blade.php` — the `$metaVerified` gate
- `app/Livewire/SuperAdmin/OnboardingRequests.php` — admin queue
- `app/Models/OnboardingRequest.php` — request lifecycle model
- `config/services.php` → `services.meta.app_verified`

---

## 2. Page Model — Single Active Invariant

### The invariant
**At most one `Page` row per `(platform, platform_page_id)` may have `is_active = true` at any time.**

### Why
Webhooks from Meta / Telegram / etc. carry only the `platform_page_id`. If two active `Page` rows share that ID, the webhook router has to pick one — and picking the wrong one delivers the customer's message to the wrong team's inbox. This was an actual bug that took multiple sessions to diagnose before the invariant was enforced.

### Enforcement
Model observer in `Page::booted()` (`app/Models/Page.php`):
- On save with `is_active=true`, finds any other active row with the same `(platform, platform_page_id)`
- Deactivates each sibling and writes an audit row to `page_transfers`
- Runs inside a DB transaction

### Behavior
- **Reconnecting the same Meta page from a different team automatically transfers ownership.** The older `Page` row is deactivated (not deleted — history preserved), and its `page_transfers` entry records `from_team_id → to_team_id`.
- **The webhook router in `ProcessIncomingMessage` uses `->latest()->first()`** as a defensive tiebreaker if the invariant is ever violated by a race condition or manual DB edit — but it should never matter in practice.

### Do NOT
- Remove the observer.
- Rewrite the router to `->first()` — the ordering matters.
- Delete rows from `page_transfers`. It's the audit trail for who owned what when.

### Files
- `app/Models/Page.php` — observer
- `app/Models/PageTransfer.php` — audit row
- `app/Jobs/ProcessIncomingMessage.php` — router
- `database/migrations/2026_06_25_000001_create_page_transfers_table.php`
- `database/migrations/2026_06_25_000002_deactivate_duplicate_active_pages.php` (one-time cleanup)

---

## 3. AI Provider Architecture (Strategy Pattern)

### Contract
`app/Contracts/AiProviderInterface.php` — 5 methods:
- `generateResponse(Conversation, Message, AiConfig): string`
- `scoreMessage(Message, Contact): array`
- `analyzeConversation(Conversation): array`
- `processCommand(string, int): array`
- `generateText(string, string): string`

Plus one **de facto** method not on the interface but required in practice: `chatWithAdmin(string, int, string, array): string`. Every provider must implement it because `App\Livewire\AiChat` calls it on the interface-bound instance.

### Implementations
- `App\Services\Ai\NaraRouterProvider` — currently active in prod (`AI_PROVIDER=nararouter`)
- `App\Services\Ai\GeminiProvider`
- `App\Services\Ai\OllamaProvider`
- `App\Services\Ai\BuildsConversationPrompts` (shared trait) — `buildSystemPrompt()`, `buildConversationHistory()`, `buildAdminChatSystemPrompt()`

### Binding
`App\Providers\AppServiceProvider::register()` reads `config('services.ai.provider')` and binds one of the above as `AiProviderInterface::class` singleton.

### Adding a new provider
1. Implement all 5 interface methods + `chatWithAdmin`
2. Use the shared `BuildsConversationPrompts` trait — do NOT re-implement `buildSystemPrompt` or `buildAdminChatSystemPrompt` inline. Prompts must stay identical across providers.
3. Add config block to `config/services.php`
4. Register in `AppServiceProvider::register()` match arm

### Do NOT
- Inline the persona / guardrail strings into a provider. They live in the trait for a reason — that's the one place guardrails get maintained.
- Hardcode `$this->model` uses everywhere. NaraRouter specifically routes calls through the failover chain (see §4).

---

## 4. NaraRouter Failover Chain + 6h Reset Window

### The chain
Env-configurable, comma-separated:
```
NARAROUTER_FALLBACK_MODELS=claude-sonnet-4.5,mistral-medium-3-5,mistral-large,claude-haiku-4.5
```
Default in `config/services.php`. Names must **exactly** match NaraRouter's aliases — verify via `GET /v1/models` before adjusting.

### The mechanism
`NaraRouterProvider::callChat` doesn't use `$this->model` directly. It calls `currentModel()`, which reads from `Cache::get('nararouter:failover_state')` and defaults to the head of the chain (sonnet).

On each request:
1. Try `currentModel()`
2. On HTTP 2xx: `markActiveModel($tryModel)` — cache the winning model, **preserving the reset_at timestamp**
3. On HTTP 429: throw `AiQuotaExhausted` (account-level block — no other model will help)
4. On HTTP 400/401/403: return `''` (client-side; no model swap will help)
5. On HTTP 404 or 5xx: cascade to next model in chain
6. If entire chain fails: throw `AiAllProvidersUnavailable`

### The 6h reset window
The cache key stores `['model' => ..., 'reset_at' => timestamp]`. Successful calls **do NOT extend `reset_at`** — only the first fallback event opens the window. This ensures we always return to sonnet at most 6h after we first fell back, regardless of how successful the fallback was.

`markActiveModel()` preserves the existing `reset_at` if present, only opening a fresh 6h window if none exists.

### Role-alternation invariant (load-bearing)
Anthropic's Messages API — which NaraRouter proxies via OpenAI-compat — requires `user` and `assistant` turns to **strictly alternate** in the message list. Two consecutive `assistant` turns (or two consecutive `user` turns) return an HTTP 400 with body `"The model rejected this request … a parameter is invalid."` Per rule (4) above we do NOT cascade on 400, so this violation cascades to a hard failure across the whole chain and the user sees "temporarily unavailable (API error)."

Two real code paths violate this without a guard:
1. `AiChat::confirmAction()` appends a second `assistant` "Done: …" turn immediately after the AI's response turn — breaking alternation on the very next admin message.
2. On the customer path, any conversation where two outbound messages (AI + human agent, or two AI in a row) or two inbound messages land back-to-back produces the same violation.

**`NaraRouterProvider::coalesceRoles()` is the single choke-point guard.** `callChat` calls it before assembling the outgoing payload, so all four call sites (`generateResponse`, `scoreMessage`, `generateText`, `chatWithAdmin`) are covered. It merges consecutive same-role turns with `\n\n`, drops empty content, and normalizes the legacy `model` role (Gemini heritage) to `assistant`.

### Do NOT
- Change `markActiveModel()` to extend `reset_at` on every success — this would break the reset-to-sonnet-every-6h behavior.
- Add "retry sonnet first every N minutes" logic. Cascade is one-shot per request; probing wastes latency.
- Cascade on 400/401/403 — those are client-side issues that will fail identically on every model.
- Remove or bypass `coalesceRoles()` in `callChat`, or "simplify" it back to a direct role-mapping loop. That reintroduces the strict-alternation 400 (see the section above). Unit tests in `tests/Unit/Services/Ai/NaraRouterCoalesceTest.php` pin the invariant.

### Files
- `app/Services/Ai/NaraRouterProvider.php`
- `app/Exceptions/AiQuotaExhausted.php`
- `app/Exceptions/AiAllProvidersUnavailable.php`

---

## 5. Sales-Flow State Machine

### Enum
`Conversation::sales_stage` — `active | escalated | completed | spam`

Constants on the model:
- `Conversation::STAGE_ACTIVE`
- `Conversation::STAGE_ESCALATED`
- `Conversation::STAGE_COMPLETED`
- `Conversation::STAGE_SPAM`

### Transitions

**Automatic**, in `SendAiResponse::handle`:
- Escalation keyword match (per `AiConfig.escalation_keywords`) → `escalate('escalation_keyword_matched')`
- All required capture fields captured → `complete('all_required_fields_captured')`
- Contact hits daily cap → `escalate('contact_ai_reply_cap_reached')`
- AI outputs `[SPAM_DETECTED]` marker → set `sales_stage = STAGE_SPAM`

**Manual**, via `Inbox\Index::transitionConversationStage($id, $stage)`:
- Dropdown in conversation header: Reactivate / Mark Escalated / Mark Completed / Mark Spam
- Delegates to model helpers (`escalate()`, `complete()`) so audit metadata is identical to automatic transitions

### Downstream effects
The `SendAiResponse` job checks `Conversation::isSalesStageActive()` — true only when stage is `active` AND `ai_paused` is false. Any terminal stage or explicit human takeover blocks the AI from replying.

Spam conversations are hidden from every filter view except `?filter=spam` — see `Inbox\Index::conversations` query.

### Files
- `app/Models/Conversation.php` — constants + `escalate()`, `complete()`, `isSalesStageActive()`
- `app/Jobs/SendAiResponse.php` — automatic transitions
- `app/Livewire/Inbox/Index.php` — manual transitions + filter behavior

---

## 6. Per-Contact Daily AI-Reply Cap

### Ceiling
`AiConfig::CONTACT_CAP_MAX = 50` — platform-hard ceiling. Customer can dial their cap DOWN to `CONTACT_CAP_MIN = 5` but **never above 50**. Clamped both in the UI (`min/max` attributes) and server-side in `AiConfig::saveConfig`.

### The counter
`contacts.ai_replies_today` (int, default 0) + `contacts.ai_replies_reset_at` (timestamp).

**Lazy reset** — no scheduled command. Every call to `Contact::canReceiveAiReply($cap)` first calls `ensureCapWindowIsCurrent()`, which resets the counter if the 24h window has expired.

### The gate
In `SendAiResponse::handle`:
- Get cap from `$aiConfig->contact_ai_reply_cap`
- Call `$contact->canReceiveAiReply($cap)`
- If false: `$conversation->escalate('contact_ai_reply_cap_reached')`, broadcast `AiLimitReached`, return
- After a successful send: `$contact->recordAiReply()`

The counter is incremented ONLY after a successful send. Failed / silent-skipped attempts don't consume budget.

### Do NOT
- Raise `CONTACT_CAP_MAX` above 50 without discussing anti-abuse implications.
- Remove the lazy reset. A scheduled command adds a cron dependency for zero benefit.
- Reset counters on manual reactivation — the cap protects your token budget regardless of stage.

### Files
- `app/Models/Contact.php` — `ensureCapWindowIsCurrent`, `canReceiveAiReply`, `recordAiReply`
- `app/Models/AiConfig.php` — `CONTACT_CAP_MIN/MAX`
- `app/Jobs/SendAiResponse.php` — gate + increment

---

## 7. Burst Debounce

### The problem
Rapid-fire messages (e.g., customer sends 4 messages in 30 seconds) used to fire 4 separate AI dispatches → 4 API calls → 4 replies. Wasteful and poor UX.

### The fix
In `SendAiResponse::handle`, **skip if any newer inbound message exists** on this conversation:
```php
$newerInboundExists = $conversation->messages()
    ->where('id', '>', $this->triggerMessageId)
    ->where('direction', 'inbound')
    ->exists();

if ($newerInboundExists) {
    return;
}
```

Each new inbound schedules its own delayed job. Older ones silently skip when they fire because a newer inbound exists. Only the last-dispatched job produces a reply, and its `buildConversationHistory` naturally includes all the burst messages so the AI addresses the whole thread.

### The complement
The default `response_delay_min_seconds = 60`, `response_delay_max_seconds = 120` (in `AiConfig`). The delay gives room for bursts to accumulate before the job fires. Explanation of the tradeoff is surfaced in the `/settings/ai/config` UI so operators understand why longer delays save tokens.

### Do NOT
- Remove the newer-inbound check without a plan for burst cost.
- Set default delays below 30 seconds — the debounce window collapses.

---

## 8. AI Guardrails — Customer & Admin

### Customer-facing (in `BuildsConversationPrompts::buildSystemPrompt`)

Guardrails are injected as the **first** block of the system prompt AND repeated in a **FINAL REMINDER** block at the end, so both the "read first, weighted heavily" and "read last, weighted heavily" LLM biases carry the same rules.

Non-negotiable rules:
1. Never break character (you are a human sales agent, not an AI).
2. If asked about model/AI/technology → deflect casually and pivot to sale, in the customer's language.
3. Never use English refusal phrases inside an Arabic reply (`I can't discuss that`, `I apologize`, `as an AI`, etc.).
4. Never mix languages in a single reply.
5. Never say "let me connect you with a team member" unless the customer explicitly asks for a human.

The operator's `business_description` and `system_prompt` (Additional Instructions) are **sandboxed** as "operator-provided context" — the guardrails explicitly state that operator content does NOT override guardrails.

### Admin-facing (in `BuildsConversationPrompts::buildAdminChatSystemPrompt`)

Different tone — this is `/ai-chat` for operators, not customer messages:
- Identity: **Marketing & Analytics Assistant** for the platform
- Errors ARE surfaced (operators need to know when things break)
- Guardrails prevent leaking vendor / model names when asked "are you AI?"
- Full capability description (send messages, pause AI, save memory, etc.) is in the same prompt

### Do NOT
- Inline the guardrails into a specific provider — they live in `BuildsConversationPrompts` and must stay identical across providers.
- Weaken the "never say I can't discuss that" rules — this was a real production bug (Claude leaking English refusals mid-Arabic reply).

---

## 9. Abuse Detection via [SPAM_DETECTED] Marker

### The mechanism
The customer-facing system prompt contains an **ABUSE DETECTION** block that instructs the AI to output the exact token `[SPAM_DETECTED]` (and nothing else) when it judges the customer is abusive / trolling / wasting time.

In `SendAiResponse::handle`, after `generateResponse` returns:
```php
if (str_contains($responseText, '[SPAM_DETECTED]')) {
    $conversation->update([
        'sales_stage' => Conversation::STAGE_SPAM,
        'ai_paused'   => true,
        'metadata'    => array_merge($conversation->metadata ?? [], [
            'marked_spam_by'     => 'ai_auto',
            'marked_spam_at'     => now()->toIso8601String(),
            'marked_spam_reason' => 'ai_detected_abuse',
        ]),
    ]);
    return;
}
```

The marker is never sent to the customer — we return before the `Message::create` + platform send.

### Reactivation loop (load-bearing)
When an operator clicks "Reactivate" in `Inbox\Index::changeStage`, we clear `sales_stage` back to active, set `ai_paused=false`, and stamp `metadata.reactivated_at`. **But the conversation history that originally triggered the classifier is still there.** Without a guard, the very next inbound message goes through the same pipeline, the AI sees the same noise, emits `[SPAM_DETECTED]` again, and we auto-re-flag — the human's decision undone within seconds.

Two guards, layered:

1. **Defensive (source of truth) — `SendAiResponse::handle`:** if `metadata.reactivated_at` exists, we log-and-skip when `[SPAM_DETECTED]` appears. We do NOT re-flag, do NOT re-pause, do NOT send the marker to the customer. Subsequent inbound messages keep going through the pipeline; if the customer sends something substantive the AI will reply normally, if they keep sending noise the customer gets silence but no state change.
2. **Preventive (reduces false positives) — `BuildsConversationPrompts::buildSystemPrompt`:** when `metadata.reactivated_at` is set, we inject a "HUMAN OVERRIDE ACTIVE" clause into the abuse-detection block, instructing the AI to only re-classify on explicit slurs/threats in the LATEST message, and to ignore the earlier noisy history. This is a hint, not a guarantee — the defensive guard above is what actually makes the invariant hold.

### Why no regex profanity list
Deliberately not implemented. Arabic dialect variation + the Scunthorpe problem make false positives too risky. The LLM understands context — e.g., "way too expensive, are you kidding?" is a legitimate price objection, not abuse.

### Do NOT
- Add a hardcoded regex profanity list "as a first line of defense" — false positives (real customer marked as spam) are worse than false negatives (mild abuse gets one polite reply before AI catches on).
- Remove the marker check without providing an alternative. The check is what makes the whole feature work.
- Remove the `metadata.reactivated_at` reactivation guard in `SendAiResponse` — that's what breaks the AI-vs-human loop. If you must change classification behavior post-reactivation, add a manual admin re-flag path instead of removing the auto-suppress.

---

## 10. Captured-Data Extraction

### Design
`App\Services\Ai\CaptureExtractor` runs BEFORE the reply-generation call in `SendAiResponse::handle`, when `AiConfig.required_capture_fields` is non-empty.

Hybrid strategy:
- **Regex-first** for validatable fields (email, phone) — free, deterministic, ~1ms.
- **LLM-backed** for freeform fields (name, address, custom text) — uses `generateText()` with strict "output NONE if absent" instruction.

Results merged into `Conversation.captured_data` JSON. When all required fields are captured, the conversation completes and the AI stops (see §5).

### The prompt injection
`BuildsConversationPrompts::buildSystemPrompt` includes a **SALES GOAL FOR THIS CONVERSATION** section listing what's already captured and what's still needed, so the AI pushes for exactly the remaining fields without repeating.

### Do NOT
- Run extraction AFTER reply generation — the reply prompt needs to know what's still needed.
- Return junk from freeform extraction — the `NONE` sentinel is checked; garbage should return null.

### Files
- `app/Services/Ai/CaptureExtractor.php`
- `app/Services/Ai/BuildsConversationPrompts.php` — SALES GOAL section
- `app/Models/AiConfig.php` — `defaultCaptureFieldsFor($preset)`, `defaultEscalationKeywordsFor($preset)`

---

## 11. `canDispatchAi()` — Single AI Dispatch Gate

### The composition
`Team::canDispatchAi()` returns:
```php
$this->isAiEnabled()                                    // user toggle
    && \App\Http\Middleware\EnforcePlanLimits::hasAiCredits($this)  // plan quota
    && ! $this->isAiUpstreamLimited();                  // outage or upstream 429
```

Every AI dispatch site in the codebase (12+ locations across `ProcessIncomingMessage`, `SendAiResponse`, `Inbox\Index`) checks `$team->canDispatchAi()`. That's why adding a new condition (e.g., upstream pause) inherits everywhere from a single-line change.

### Do NOT
- Scatter individual gate checks. Compose them in `canDispatchAi()`.
- Rename `canDispatchAi()` without updating every dispatch site.

---

## 12. Silent-Skip vs Fallback-String Convention

### For customer-facing calls
On non-quota failure, providers return `''` (empty string). `SendAiResponse` skips empty responses without sending anything. The customer sees silence, not "I apologize, I'm having a moment. Let me connect you with a team member" (that string was a real production bug — customers thought it was a real reply).

### For admin-facing calls (`chatWithAdmin`)
Return an admin-appropriate fallback string when the API errors — operators need to see something. E.g.: "The AI service is temporarily unavailable (API error). Please try again in a few minutes."

### For 429 (quota)
Throw `AiQuotaExhausted`. `SendAiResponse` catches → pauses team (24h) → broadcasts `AiLimitReached` → banner shows.

### For all-providers-down (5xx across whole chain)
Throw `AiAllProvidersUnavailable`. `SendAiResponse` catches → pauses team (15 min — short because outages recover fast) → broadcasts → banner shows red "AI temporarily unavailable, contact support" text.

### Do NOT
- Return "I apologize" fallback strings from customer-facing paths. Ever.
- Catch `AiQuotaExhausted` or `AiAllProvidersUnavailable` in the provider — those must bubble up to `SendAiResponse` for the pause + broadcast to work.

---

## 13. Team Upstream Pause + Banner

### Cache-backed pause
`Team::markAiUpstreamPaused(?DateInterval $ttl, string $reason)`:
- Writes to cache key `ai_upstream_paused:{team_id}` with an outer TTL (default 24h, 15min for outages)
- Reason stored alongside timestamp: `'quota'` or `'outage'`

`Team::isAiUpstreamLimited()` just checks cache presence. `Team::aiUpstreamPauseReason()` returns the reason string (or `null` when not paused).

### Banner (`resources/views/partials/ai-quota-banner.blade.php`)
Rendered on every page (included in the sidebar layout). Adapts based on state:
- **Plan-quota exhausted** (internal `ai_credits_used >= limit`) → amber banner: "you've reached your plan's AI credit limit"
- **Upstream paused, reason=quota** → amber banner: "your AI token limits are used up for now"
- **Upstream paused, reason=outage** → **red** banner: "AI is temporarily unavailable — contact support if this persists"

Real-time updates via `AiLimitReached` broadcast + `x-on:ai-limit-reached.window` Alpine listener.

### Do NOT
- Reuse the upstream pause cache for other kinds of state. It has a specific contract.
- Change the banner colors without updating both the amber and red paths. Red is deliberately different so operators know an outage is different from a quota.

---

## 14. Flux 2.x Modal API Gotcha

### The trap
`$this->dispatch('open-modal', name: 'X')` is **the Filament / generic Livewire pattern**. Flux 2.x **does not listen** to that event. The dispatch silently no-ops.

### The right ways
- **Server side (Livewire method):** `Flux::modal('name')->show()` and `Flux::modal('name')->close()`. Add `use Flux\Flux;` to the component.
- **Client side (Blade):** `<flux:modal.trigger name="name">Open</flux:modal.trigger>` — pure client-side, no dispatch needed.

### Where this bit us
`Connections\Index::openRequestForm` and `submitOnboardingRequest` originally used `$this->dispatch('open-modal', name: 'onboarding-request')` — that call silently did nothing. Users clicked "Request connection" and saw no modal open. Root cause: Flux 2.x API mismatch, not a modal wiring bug.

### Do NOT
- Copy modal-open patterns from Filament / older Livewire docs without checking they work with Flux 2.x.
- Add a JS "open-modal" event listener as a workaround — use the proper Flux API.

---

## 15. Sidebar Gating Pre-Connection

### The design
A team with zero active connected pages has an unusable inbox, contacts, analytics, etc. Instead of showing empty pages, the sidebar **locks** most nav items and clicking any locked item opens a "Connect first" modal linking to `/connections`.

Unlocked when NO active connection exists: **Home, Connections, Settings, super-admin.***.
Locked: **Inbox, Contacts, Campaigns, Content, Analytics, AI Chat, AI Settings**.

### The gate
`Team::hasAnyConnection()` (memoized per-request) returns true iff at least one `is_active=true` Page exists for the team.

Locked items become `<button>` (not `<a wire:navigate>`), dim to `opacity-50`, and dispatch a browser event `needs-connection` on click.

A single Alpine modal listens for `needs-connection` at the layout level — no per-item duplication.

### Do NOT
- Check `ConnectedAccount` rows for the gate. Some platforms (WhatsApp QR, Telegram, Email) don't have a `ConnectedAccount` row — they only have a `Page`. `Page::where('is_active', true)->exists()` is the correct check.
- Drop the per-request memoization. The sidebar checks the gate for every locked item — un-memoized this becomes N DB queries per pageview.

### Files
- `app/Models/Team.php` — `hasAnyConnection()`
- `resources/views/layouts/app/sidebar.blade.php` — nav rendering + Alpine modal

---

## Change log

- **2026-07-02** — Initial architecture doc. Covers all Phase 1 sales-flow work, NaraRouter failover, abuse detection, and the Flux 2.x + sidebar-gating gotchas surfaced during recent sessions.
