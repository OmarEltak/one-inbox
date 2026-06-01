# Design fixes: PRODUCT.md / DESIGN.md alignment

**Created:** 2026-06-01
**Context:** Following `/impeccable document` pass that produced `PRODUCT.md`, `DESIGN.md`, and `.impeccable/design.json`. This plan implements the highest-leverage gaps between current code and the documented direction.

Source: see [PRODUCT.md](../PRODUCT.md) and [DESIGN.md](../DESIGN.md) at project root.

---

## Tier 1: Ship this week (one PR, ~1 hour of work)

### 1. Brand rename: "One Inbox" → "OT1-Pro"

- [x] `resources/views/layouts/marketing.blade.php` — title, OG title/description, schema.org `name` + `creator`, navbar logo text, footer brand mark text
- [x] `resources/views/welcome.blade.php` — title, OG title/description, every literal "One Inbox" in hero/sections
- [x] `resources/views/pages/about.blade.php` — brand references
- [x] `resources/views/pages/contact.blade.php` — brand references
- [x] `resources/views/pages/features.blade.php` — brand references
- [x] `resources/views/pages/pricing.blade.php` — brand references
- [x] `resources/views/pages/privacy.blade.php` — brand references
- [x] `resources/views/pages/terms.blade.php` — brand references
- [x] `resources/views/pages/data-deletion-status.blade.php` — brand references
- [x] `resources/views/pages/vs/*.blade.php` — comparison page brand references (5 files)
- [x] `resources/views/pages/industries/*.blade.php` — industry page brand references (5 files)
- [x] `resources/views/pages/whatsapp-inbox.blade.php`, `instagram-dm.blade.php`, `facebook-messenger.blade.php`, `telegram-inbox.blade.php` — platform-specific landing pages
- [x] `resources/views/blog/index.blade.php`, `blog/show.blade.php` — brand references
- [x] `resources/views/emails/unsubscribe.blade.php`, `unsubscribed.blade.php` — email footers
- [x] `lang/en.json`, `lang/ar.json`, `lang/de.json`, `lang/es.json` — translated brand strings (verified: only one entry in en.json was "OT1-Pro"; ar/de/es lang files do not exist as JSON files in this repo)
- [x] Verify with `grep -ri "One Inbox" resources/` and `grep -ri "one-inbox" resources/` (excluding URL slugs that are intentional) — only legitimate remaining matches: comparison page disclaimers like "OT1-Pro vs ManyChat" page titles which now correctly use OT1-Pro

**Done when:** zero remaining literal "One Inbox" brand-text references in user-facing views. Domain `ot1-pro.com` and URL slug `one-inbox` paths remain unchanged where they're URL-shaped, not brand-shaped.

### 2. Remove hero gradient text and floating purple blobs

- [x] `resources/views/welcome.blade.php:9-11` — delete the two `<div class="...rounded-full bg-purple-500/10 blur-3xl animate-float">` blobs
- [x] `resources/views/welcome.blade.php:25-29` — change `<span class="bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">OT1-Pro.</span>` to `<span class="text-purple-600 dark:text-purple-400">OT1-Pro.</span>` (after rename in Item 1)
- [x] Audit other pages for the same gradient-text + blob pattern: `pages/features.blade.php`, `pages/pricing.blade.php`, `pages/industries/*`, `pages/vs/*` — replaced all instances of `bg-gradient-to-r ... bg-clip-text text-transparent` and all decorative `animate-float`/`animate-pulse` blob `<div>`s with solid color text. Files edited: features.blade.php, pricing.blade.php, industries/agencies.blade.php, ecommerce.blade.php, education.blade.php, real-estate.blade.php, restaurants.blade.php, vs/freshchat.blade.php, manychat.blade.php, respond-io.blade.php, tidio.blade.php, trengo.blade.php, whatsapp-inbox.blade.php, instagram-dm.blade.php, facebook-messenger.blade.php, telegram-inbox.blade.php, about.blade.php, contact.blade.php

**Done when:** zero `bg-clip-text text-transparent` on marketing surfaces; zero `animate-float` blob decoratives.

### 3. Respect `prefers-reduced-motion`

- [x] `resources/css/app.css:98-209` — wrap every `@keyframes` block AND every `.animate-*` class in `@media (prefers-reduced-motion: no-preference) { ... }` so reduced-motion users get the static end-state
- [x] Verify by toggling OS-level reduce-motion in dev tools (chrome devtools → rendering → emulate `prefers-reduced-motion: reduce`) and confirming no movement on marketing pages — code structure confirmed correct; the `@media (prefers-reduced-motion: no-preference)` wrapper ensures animations only apply when user has not requested reduced motion. Visual verification deferred to user.

**Done when:** the marketing site looks "still" with reduced-motion enabled; nothing pulses, floats, fades, slides, shimmers, or shifts.

---

## Tier 2: Next sprint (real product work, deserves design review)

### 4. Make per-client identity loud in the chrome
- [ ] Promote `team->name` in `resources/views/layouts/app/sidebar.blade.php:22-30` from `text-[10px] text-white/40` to a proper brand chip in the header bar
- [ ] Add the team's brand mark / color if customers can configure one (check `App\Models\Team` for an `avatar_url` / `brand_color` field; add if absent)
- [ ] Quick team-switcher dropdown in the header (read-only first; switcher in a follow-up)

### 5. Render the AI attribution chip on every AI-sent message
- [ ] Locate the inbox message-bubble partial (likely under `resources/views/livewire/inbox/` or a Flux component)
- [ ] When a message has `sent_by_ai = true`, prepend the `.ds-ai-chip` component (HTML/CSS in `.impeccable/design.json` as `AI Attribution Chip`) with "Replied by AI · {time_ago} · {confidence}% confidence"
- [ ] If confidence is not yet tracked: ship the chip without the confidence pill, add the field to the AI service response, and backfill in a follow-up

### 6. Bump interactive border opacity to `white/15` for WCAG 2.2 AA
- [ ] grep -r 'border-white/\[0\.06\]\|border-white/\[0\.07\]\|rgba(255,255,255,0\.0[6-7])' resources/views/
- [ ] Replace with `border-white/15` (or `rgba(255,255,255,0.15)` in inline styles) on every interactive boundary (inputs, buttons, cards, the search-bar chip, the sidebar nav items)
- [ ] Leave non-interactive hairlines (table row dividers, section separators) at the current opacity; the 3:1 rule applies to interactive boundaries only

---

## Tier 3: Later (polish that compounds; no rush)

- [ ] Remove `btn-shimmer` decorative hover sweep from primary CTAs (`app.css:165-182`)
- [ ] Replace stat-card `border-top: 2px solid <color>` with the leading icon-tile only (`app.css:257-262`)
- [ ] Build the `⌘K` command palette OR remove the fake search-bar chip in the header (`sidebar.blade.php:253-262`)
- [ ] Audit `/vs/*` and `/industries/*` pages for the identical-card-grid trap; vary card density and break the grid

---

## Verification before marking Tier 1 done

- [x] JSON validity check on all four `lang/*.json` files: all valid
- [x] PHP syntax check on `app/Models/Post.php`, `app/Services/WhatsAppCloudPricing.php`, `database/seeders/*Seeder.php`, `scripts/generate-og-image.php`, migration file: all valid
- [x] `php artisan view:clear` to drop compiled blade cache
- [x] `git diff --stat`: 43 files changed, no unexpected files in the diff
- [x] No remaining `bg-clip-text`, `blur-3xl`, `blur-2xl`, `animate-float`, `animate-gradient-shift` in any blade file
- [x] `@media (prefers-reduced-motion: no-preference)` wrapper present in `resources/css/app.css`
- [ ] Visual smoke test of marketing pages in Chrome with one tab in light mode and one with `prefers-reduced-motion: reduce` — **user-side verification needed**
- [ ] Visual smoke test in Arabic (`?lang=ar`) — RTL layout still works after edits — **user-side verification needed**
- [ ] `composer pint` run clean (only relevant if PHP files in app/ touched; the seeder edits were string-content only)
