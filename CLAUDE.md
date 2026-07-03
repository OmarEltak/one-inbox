# ⚠️ STOP — READ BEFORE TOUCHING CODE

**This project has non-negotiable architectural decisions that recent sessions almost broke by "fixing" them silently.**

**Full reference:** [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md). Read it before you change AI code, messaging/connections code, or sales-flow models. It's ~700 lines but each section is scannable — read the sections relevant to your task in full, not just the summary.

## Non-negotiable pins (things sessions have actually broken)

1. **`$metaVerified` in `resources/views/livewire/connections/index.blade.php` IS defined** — as a Blade `@php` var reading `config('services.meta.app_verified')`. **Do NOT** rewrite it as a Livewire property. **Do NOT** default it to `true` "to restore the OAuth button" — Meta rejects unverified apps at the OAuth callback, so real customers can't use it. When Meta approves us, the fix is `META_APP_VERIFIED=true` in `.env`. See ARCHITECTURE §1.

2. **Managed onboarding is the current OAuth alternative.** Customers use "Request connection" → super-admin OAuths through their own OT account → super-admin re-assigns the `Page` to the customer team at `/super-admin/onboarding-requests`. **Do NOT** remove or "simplify" this flow. See ARCHITECTURE §1.

3. **One active `Page` per `(platform, platform_page_id)`.** Enforced by `Page::booted()` observer + audited in `page_transfers`. Removing the observer breaks webhook routing (multiple active rows → wrong team receives messages). See ARCHITECTURE §2.

4. **`Team::canDispatchAi()` is the single AI dispatch gate.** Every dispatch site (12+ locations) calls it. Compose new conditions inside it — do NOT scatter checks. See ARCHITECTURE §11.

5. **Providers return `''` (empty) on non-quota failures, throw specific exceptions on quota/outage.** **Do NOT** re-add "I apologize, I'm having a moment" fallback strings — they were a real production bug (customers thought the bot's apology was a real reply). See ARCHITECTURE §12.

6. **Flux 2.x modals use `Flux::modal('name')->show()`, NOT `$this->dispatch('open-modal', name: 'X')`.** The dispatch silently no-ops. This bit us on the onboarding request modal. See ARCHITECTURE §14.

7. **NaraRouter failover chain resets to sonnet every 6h from FIRST fallback, not per success.** `markActiveModel()` preserves `reset_at` — do NOT refresh it on every successful call, or we'd never return to sonnet. See ARCHITECTURE §4.

8. **`Team::hasAnyConnection()` checks `is_active` pages**, not `ConnectedAccount` rows. Some platforms (WhatsApp QR, Telegram, Email) don't create a `ConnectedAccount`. See ARCHITECTURE §15.

9. **`NaraRouterProvider::coalesceRoles()` MUST run before every `callChat` payload.** Anthropic (via NaraRouter) rejects requests where `user`/`assistant` don't strictly alternate, returning a 400 that we intentionally don't cascade on. `AiChat::confirmAction` and any customer conversation with two same-direction messages in a row will trip it without the guard. Do NOT remove the coalesce step or "simplify" it back to a direct role-mapping loop. See ARCHITECTURE §4 "Role-alternation invariant". Unit test: `tests/Unit/Services/Ai/NaraRouterCoalesceTest.php`.

10. **Once a conversation has `metadata.reactivated_at`, `SendAiResponse` MUST NOT auto-re-flag it as spam even if the AI still emits `[SPAM_DETECTED]`.** Otherwise every subsequent inbound message loops through the same history and re-triggers the classifier — the human's reactivation click gets undone within seconds. See ARCHITECTURE §9 "Reactivation loop". Feature test: `tests/Feature/SendAiResponseSpamGuardTest.php`.

If your task touches AI, messaging, connections, sales flow, or the sidebar — **grep the relevant ARCHITECTURE section** before writing code. Every pin above corresponds to a real bug shipped by a previous session that thought they were helping.

## Debugging discipline (mandatory)

Before proposing ANY fix for a reported bug on this project, invoke the [`evidence-first-diagnosis`](https://github.com/OmarEltak/evidence-first-diagnosis) skill via the Skill tool. It's installed locally at `~/.claude/skills/evidence-first-diagnosis/`.

Rationale: this repo has burned **multiple hours of session time** on confident memory-based misdiagnoses that would have been caught by 30 seconds of raw-data reading (Meta error 2018278 misread as IG cross-platform, NaraRouter model aliases guessed instead of queried via `/v1/models`, etc.). The skill's four-step forcing function — fetch raw data, translate/decode, look at N=3+ cases, state the diagnosis as "data says X, therefore Y" — is designed to prevent that specific failure mode.

**Invoke it when the user says "not working", "failing", "wrong data", or shows any error code.** Especially when you feel confident about the cause — that's the highest-risk moment.

See `examples/` in the skill's repo for real case studies from this codebase.

---

## Workflow Orchestration

### 1. Plan Mode Default
- Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately - don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### 2. Subagent Strategy
- Use subagents liberally to keep main context window clean
- Offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One task per subagent for focused execution

### 3. Self-Improvement Loop
- After ANY correction from the user: update `tasks/lessons.md` with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start for relevant project

### 4. Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### 5. Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes - don't over-engineer
- Challenge your own work before presenting it

### 6. Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests - then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

## Task Management

1. **Plan First**: Write plan to `tasks/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review section to `tasks/todo.md`
6. **Capture Lessons**: Update `tasks/lessons.md` after corrections

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.
