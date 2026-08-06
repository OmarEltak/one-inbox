# ⚠️ STOP — READ BEFORE TOUCHING CODE

**This project has non-negotiable architectural decisions that recent sessions almost broke by "fixing" them silently.**

**Full reference:** [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md). Read it before you change AI code, messaging/connections code, or sales-flow models. It's ~700 lines but each section is scannable — read the sections relevant to your task in full, not just the summary.

## Non-negotiable pins (things sessions have actually broken)

1. **`$metaVerified` in `resources/views/livewire/connections/index.blade.php` IS defined** — as a Blade `@php` var reading `config('services.meta.app_verified')`. **Do NOT** rewrite it as a Livewire property. **Do NOT** default it to `true` "to restore the OAuth button" — Meta rejects unverified apps at the OAuth callback, so real customers can't use it. See ARCHITECTURE §1.

   **⚠️ CRITICAL CORRECTION (2026-07-21):** Meta "verification" is TWO separate milestones, not one. Both must be complete before flipping `META_APP_VERIFIED=true`:

   | Milestone | Where | Effect |
   |---|---|---|
   | (a) **Business Portfolio Verification** (OT1 Pro, ID `2169075923895403`) | Meta Business Suite → Security Center | Prerequisite. Unlocks the ability to submit App Review. Does NOT unlock OAuth. |
   | (b) **App Review with Advanced Access** on each permission (`public_profile`, `email`, `pages_show_list`, `pages_messaging`, `pages_manage_metadata`, `pages_read_engagement`, `instagram_basic`, `instagram_manage_messages`, `business_management`) | developers.facebook.com/apps/1469090344742803 → Use Cases → Permissions | Each permission must show "Advanced Access" (not "جاهز للاختبار" / "Ready to Test", which = Standard Access = admins/testers only). Without this, non-admin OAuth returns *"Feature unavailable: Facebook Login is currently unavailable for this app"*. |

   **How to verify before flipping the flag:** Log in to developers.facebook.com, open the "Facebook Messaging" use case, and confirm every permission the app requires shows "Advanced Access". If any show "Ready to Test" / "جاهز للاختبار" / blank, DO NOT flip the flag — real customers will still get the "Feature unavailable" error and the direct-OAuth button will be worse than the managed-onboarding fallback.

   **Rollback:** `ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && sed -i '/^META_APP_VERIFIED=/d' .env && php artisan config:cache"` (a timestamped `.env.bak.*` is preserved from any prior flip).

2. **Managed onboarding is the current OAuth alternative.** Customers use "Request connection" → super-admin OAuths through their own OT account → super-admin re-assigns the `Page` to the customer team at `/super-admin/onboarding-requests`. **Do NOT** remove or "simplify" this flow. See ARCHITECTURE §1.

3. **One active `Page` per `(platform, platform_page_id)`.** Enforced by `Page::booted()` observer + audited in `page_transfers`. Removing the observer breaks webhook routing (multiple active rows → wrong team receives messages). See ARCHITECTURE §2.

4. **`Team::canDispatchAi()` is the single AI dispatch gate.** Every dispatch site (12+ locations) calls it. Compose new conditions inside it — do NOT scatter checks. See ARCHITECTURE §11.

5. **Providers return `''` (empty) on non-quota failures, throw specific exceptions on quota/outage.** **Do NOT** re-add "I apologize, I'm having a moment" fallback strings — they were a real production bug (customers thought the bot's apology was a real reply). See ARCHITECTURE §12.

6. **Flux 2.x modals use `Flux::modal('name')->show()`, NOT `$this->dispatch('open-modal', name: 'X')`.** The dispatch silently no-ops. This bit us on the onboarding request modal. See ARCHITECTURE §14.

7. **NaraRouter failover chain resets to sonnet every 6h from FIRST fallback, not per success.** `markActiveModel()` preserves `reset_at` — do NOT refresh it on every successful call, or we'd never return to sonnet. See ARCHITECTURE §4.

8. **`Team::hasAnyConnection()` checks `is_active` pages**, not `ConnectedAccount` rows. Some platforms (WhatsApp QR, Telegram, Email) don't create a `ConnectedAccount`. See ARCHITECTURE §15.

9. **`NaraRouterProvider::coalesceRoles()` MUST run before every `callChat` payload.** Anthropic (via NaraRouter) rejects requests where `user`/`assistant` don't strictly alternate, returning a 400 that we intentionally don't cascade on. `AiChat::confirmAction` and any customer conversation with two same-direction messages in a row will trip it without the guard. Do NOT remove the coalesce step or "simplify" it back to a direct role-mapping loop. See ARCHITECTURE §4 "Role-alternation invariant". Unit test: `tests/Unit/Services/Ai/NaraRouterCoalesceTest.php`.

10. **Once a conversation has `metadata.reactivated_at`, `SendAiResponse` MUST NOT auto-re-flag it as spam even if the AI still emits `[SPAM_DETECTED]`.** Otherwise every subsequent inbound message loops through the same history and re-triggers the classifier — the human's reactivation click gets undone within seconds. See ARCHITECTURE §9 "Reactivation loop". Feature test: `tests/Feature/SendAiResponseSpamGuardTest.php`.

11. **Blog post body quality standard (SEO).** New blog posts go in numbered batch seeders (`database/seeders/AiSeoBlogSeederBatch{N}{Theme}.php`) — that pattern is settled and not what this pin is about. This pin is about the **body content** itself. The bar is set by `meta-app-verification-2026-founder-guide` (the site's only real SEO winner per the 2026-08-06 audit: UK founders on Google, 5-20 min dwell time in Clarity). Every new post MUST meet ALL of the following or it will silently sink to position 40+ like the ~100 low-quality posts before it did:

    - **1,800–2,500 words minimum.** Under 1,500 = won't rank in 2026 for anything competitive. Padded fluff also fails — length must come from real specificity.
    - **Founder-POV or specialist-POV, first person where useful.** No third-person "businesses should consider" corporate voice. The winner post opens with "I built OT1-Pro… so I had to walk this path myself." That voice IS the moat vs corporate SEO farms.
    - **Named, concrete failure modes.** List real error codes, real Meta rejection strings in the original language ("جاهز للاختبار" / "Feature unavailable: Facebook Login…"), real document names (السجل التجاري, Ejari, Companies House confirmation statement), and real dollar figures. Vague ("various issues can arise") = auto-rejection by Google's helpful-content classifier.
    - **Numbered lists, tables, and H2s every 200-300 words.** Google rewards scannable structure. Solid text walls = low dwell time = demotion. Aim for 8-12 H2s per post so the auto-TOC in `resources/views/blog/show.blade.php` (added 2026-08-06) actually renders — it needs 3+ H2s and looks empty with few.
    - **Every post MUST internally link to at least 3 of:** the winning meta-app-verification post, `/pricing`, `/vs/wati`, another post in the same cluster, and `/register`. Link equity concentration is the biggest DR-0 site lever.
    - **Every post MUST end with the `{{CTA}}` placeholder** so `run()` swaps in the CTA block. Never hard-code a CTA — the CTA copy will change and hard-coded ones rot.
    - **Meta description 150-160 chars, ends with the primary keyword.** Meta title ≤ 60 chars, primary keyword in first 40. `excerpt` (used in the "Quick answer" box) MUST be a genuinely useful 40-60 word answer to the post's headline query — it renders in the featured-snippet-magnet card at the top of every post.
    - **No AI-content-farm tells:** don't say "In today's fast-paced digital landscape", don't open with a rhetorical question, don't use "revolutionize" / "seamless" / "delve into" / "in conclusion". Read the winner post aloud — if a section sounds like it could come from RankPill or Byword, rewrite it in the founder voice (which per pin #5 in `docs/seo-progress.md` we explicitly rejected as a channel).
    - **Publish flow:** commit → push → auto-deploy → SSH `php artisan db:seed --class='Database\\Seeders\\...' --force` (seeders do NOT auto-run). Then submit each new slug in Google Search Console URL Inspection to nudge indexing.
    - **Batch 17 (2026-08-06)** is the current reference batch. If you need a template, copy `database/seeders/AiSeoBlogSeederBatch17MetaFounderCluster.php` — it hits every rule above.

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

---

## Production Topology (updated 2026-07-08)

| | Local Dev | Production |
|---|---|---|
| **Path** | `C:\Users\NanoChip\Herd\one-inbox\` | `/var/www/ot1-pro.com` on VPS `187.77.67.94` |
| **Domain** | `https://one-inbox.test` | `https://ot1-pro.com` |
| **Server** | Laravel Herd (Windows) | nginx + PHP 8.4-FPM (Ubuntu 24.04) |
| **DB** | SQLite | MySQL 8.0 (`one_inbox`) |
| **Deploy** | n/a | `git push origin main` → GitHub Actions auto-deploys in ~24s |
| **Queue** | `php artisan queue:work` / NSSM | systemd `one-inbox-queue` |
| **Reverb** | NSSM `OneInboxReverb` | systemd `one-inbox-reverb` (port 8080) |
| **Scheduler** | NSSM `OneInboxScheduler` | crontab `* * * * *` |

**Development flow**: work in `C:\Users\NanoChip\Herd\one-inbox\` → push to `main` → auto-deploys to prod. Never edit files directly on the VPS.

**Prod .env**: lives only on the VPS at `/var/www/ot1-pro.com/.env` (mode 600, not in git). If you need to change a prod env var, SSH in and edit it directly, then `php artisan config:cache`.
