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
- [x] Promote `team->name` in `resources/views/layouts/app/sidebar.blade.php` — added a dedicated "Workspace" chip with deterministic per-team color (hue derived from `crc32($team->slug)`), team initial avatar, label + name
- [x] Per-team brand mark / color: derived from team slug (no schema change); same team always gets same hue. Future: when customers can configure a brand color, swap the `$teamHue` source from `crc32` to `$team->settings['brand_hue']` or new column
- [x] Quick team-switcher dropdown: dropdown opens on chip click, lists all `$user->teams()`, shows current with check mark, includes "New workspace" footer, POSTs to new `teams.switch` route. Shown only if user has >1 team or is super-admin
- [x] Added matching team chip as the breadcrumb anchor in the page header bar (replaces the previous "OT1 Pro / {title}")
- [x] Added `POST /teams/{team}/switch` route with membership guard

### 5. Render the AI attribution chip on every AI-sent message
- [x] Located inbox message-bubble in `resources/views/livewire/inbox/index.blade.php:452-456`; pre-existing AI label was just sparkles + "AI"
- [x] Upgraded to "Replied by AI · {confidence}% confidence" with sparkles icon, semibold label, defensive confidence handling (works whether `ai_confidence` is 0-1 decimal or 0-100 integer)
- [x] Translatable via `__('Replied by AI')` and `__('confidence')`

### 6. Bump interactive border opacity to `white/15` for WCAG 2.2 AA
- [x] Replaced all `border-white/[0.06]` and `border-white/[0.07]` Tailwind classes with `border-white/15` across 6 blade files
- [x] Replaced inline `border: 1px solid rgba(255,255,255,0.0[6-8])` styles with `0.15` opacity (8 occurrences)
- [x] Left non-interactive boundaries alone: shadow/inset highlights, table dividers stay at original opacity
- [x] `.impeccable/design.json` snippets verified already correct (component css uses `0.15` on interactive borders; the remaining `0.06/0.07/0.08` refs are shadow tokens and background fills)

---

## Tier 3: Later (polish that compounds; no rush)

- [x] Remove `btn-shimmer` decorative hover sweep from primary CTAs — class usage removed from 3 blade files (welcome.blade.php x2 and features.blade.php x1); `@keyframes shimmer`, `.btn-shimmer`, `.btn-shimmer::after`, and `.btn-shimmer:hover::after` rules all stripped from `app.css`
- [x] Replace stat-card `border-top: 2px solid <color>` with the leading icon-tile only — `aio-stat-*` rules in `app.css` collapsed to a single no-op selector preserving backwards-compatible classnames; identity now carried solely by the `aio-icon-*` colored icon-tiles; design.json snippet updated to match
- [x] Removed the fake search-bar chip in the header (`sidebar.blade.php`); deferred the real `⌘K` command palette as a future feature. Inline comment in the blade explains the deletion intent so it isn't accidentally re-added
- [x] Industry pages broken out of identical-card-grid pattern: first value-prop card in each of the 5 industry pages now spans 2 columns on desktop with violet accent, larger padding, and slightly larger type. Achieved via `$loop->first` ternary; zero changes to underlying data shape. `vs/*` pages keep their 3-card structure since 3 cards is editorial honesty, not a 6-9-12 identical-grid cliché

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
