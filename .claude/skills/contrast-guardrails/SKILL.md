---
name: contrast-guardrails
description: Use BEFORE any edit to a Blade view that adds or changes text color, background color, or a Flux `<flux:badge>` / `<flux:button variant="ghost">` / `<flux:text>` / `<flux:modal>` element. Codifies the specific low-contrast failure modes that keep shipping to prod on ot1-pro.com (white text on light-yellow badge, white text on white background, gray placeholder text where the value should be, green-on-green flash messages, ghost buttons that become invisible, dark-theme classes like `bg-*/5` / `text-white/60` / `border-white/10` copy-pasted into modals rendering on a LIGHT app shell). If you're about to write `text-white`, `text-zinc-500`, `flux:badge color=`, `flux:text`, `variant="ghost"`, any `text-*-500` on a light background, or `bg-*/5` / `text-white/*` / `border-white/*` anywhere in a light-themed component, invoke this skill first. Also use when the user reports "I can't see the text", "invisible until I select all", "white on white", "gray on white", "green on green", "no border on the input", "washed out modal", "apply contrast skill", or shows a screenshot where text is missing.
---

# Contrast Guardrails

Every UI change on this repo must satisfy WCAG AA at minimum (4.5:1 for body text, 3:1 for UI elements and large text). This skill exists because we keep shipping the same three-or-four failure modes over and over. Each one is documented below with the specific class combos that fail, why they fail, and the fix pattern.

---

## Failure mode 1 — Flux badges default to white text on light tint

**Symptom:** `<flux:badge color="yellow">Head Admin</flux:badge>` renders as an INVISIBLE badge (light yellow background, white text). Same for `color="blue"`, `color="red"`, `color="green"`. User can only see the text after selecting-all with Ctrl+A.

**Root cause:** Flux 2.x's `<flux:badge>` component picks its foreground color from a theme that was designed for DARK app shells. On a light app (like ours), the badge text ends up near-white while the badge background is a light tint of the same color = zero contrast.

**Diagnostic (grep-time):**
```
grep -rn 'flux:badge' resources/views | grep -v 'class=' 
```
Any badge without an explicit `class="..."` override is at risk.

**Fix pattern:** Do NOT use `<flux:badge>` on light-themed pages. Use a plain `<span>` with explicit Tailwind:
```blade
<span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900 ring-1 ring-amber-200">
    Head Admin
</span>
```

The rule of thumb: `bg-{color}-100` + `text-{color}-900` + `ring-{color}-200` is always safe. The 900 shade on a 100 background always passes AA.

---

## Failure mode 2 — Flux inputs render typed content as `text-zinc-500`

**Symptom:** User types "we are selling bags" into a `<flux:input>` or `<flux:textarea>` and can barely read what they typed. The text is a very light gray, same shade as the placeholder.

**Root cause:** Flux's input/textarea theme sets `color: var(--flux-input-text)` which resolves to a muted zinc-500 in the Flux stylesheet. Fine for admin dashboards where dozens of inputs shouldn't compete, terrible for the wizard where the input IS the primary action.

**Diagnostic (grep-time):**
```
grep -rn 'flux:input\|flux:textarea' resources/views/livewire/onboarding resources/views/livewire/settings resources/views/livewire/auth
```

**Fix pattern:** For any wizard-style or high-attention input, use a hand-rolled `<input>` / `<textarea>` instead:
```blade
<input type="text"
    wire:model="q1Offer"
    placeholder="e.g. Handmade leather bags"
    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition"
/>
```

Key ingredients:
- `text-zinc-900` for typed content (never zinc-500, zinc-600, or zinc-700 — they all read as "muted/disabled")
- `placeholder:text-zinc-400` so hint text is distinct from typed content
- `border-zinc-300` at rest (Flux only shows border on focus by default)
- `focus:ring-2 focus:ring-violet-100` for the visible focus ring

---

## Failure mode 3 — `variant="ghost"` on light backgrounds = invisible button

**Symptom:** A `<flux:button variant="ghost">Permissions</flux:button>` renders as invisible text on white. User doesn't know a button is there. Same for "Password", "Tweak my AI", etc.

**Root cause:** Ghost variant = no background, no border, just text at ~40% opacity of the theme accent. On a white page, the button visually disappears.

**Diagnostic (grep-time):**
```
grep -rn 'variant="ghost"' resources/views
```

**Fix pattern:** Use `variant="outline"` for secondary buttons on light backgrounds. Ghost is only appropriate INSIDE dark surfaces (nav bars, dark cards, etc):
```blade
<flux:button variant="outline" size="sm" icon="pencil">
    Permissions
</flux:button>
```

If you truly need a no-border secondary CTA on a light bg, use an explicit outline pattern with a border:
```blade
<button class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition">
    Cancel
</button>
```

---

## Failure mode 4 — `flux:text` inside a tinted alert loses its color

**Symptom:** Success/error flash message: `bg-green-50` container with `<flux:text class="text-green-700">Admin created</flux:text>` inside. Text appears WASHED OUT green-on-green because `flux:text` overrides the class-level color with its own muted default.

**Root cause:** `<flux:text>` has an internal `text-{muted}` on top of the `class="..."` you pass. On low-opacity backgrounds the two blend into unreadability.

**Diagnostic (grep-time):**
```
grep -rn 'bg-\(green\|red\|amber\|blue\|violet\)-50' resources/views | xargs -I{} sh -c 'echo "---{}---"; grep -A3 "bg-.*50" "{}" | grep flux:text'
```

**Fix pattern:** Use plain `<p>` instead of `<flux:text>` inside tinted containers:
```blade
@if(session('success'))
    <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-4">
        <p class="text-sm font-medium text-emerald-900">{{ session('success') }}</p>
    </div>
@endif
```

Always use the `-900` text shade with `-50` background. Never `text-{color}-500` or `text-{color}-600` on a `bg-{color}-50` — the contrast ratio is around 2.5-3.0, failing AA.

---

## Failure mode 6 — Dark text on dark-mode background (dynamic color pills)

**Symptom:** Pills like `<span class="bg-{$planColor}-100 dark:bg-{$planColor}-900/30 text-zinc-900">` render fine in light mode but the DARK MODE variant only overrides the background, leaving `text-zinc-900` (near-black) on a dark colored background = unreadable.

**Root cause:** People remember to add `dark:bg-*` for the surface but forget the paired `dark:text-*`. Result is dark-on-dark specifically in dark mode.

**Diagnostic (grep-time):**
```
grep -rn 'dark:bg-' resources/views | grep -v 'dark:text-\|dark:border-'
```
Any line with `dark:bg-*` that doesn't also mention `dark:text-*` on nearby text is at risk.

**Fix pattern:** Every `dark:bg-*` MUST be paired with a `dark:text-*`. For pills using dynamic colors, follow the light-100→dark-900 mirror pattern:
```blade
<span class="inline-flex items-center rounded-md
             bg-{{ $planColor }}-100 dark:bg-{{ $planColor }}-900/40
             text-{{ $planColor }}-900 dark:text-{{ $planColor }}-100
             ring-1 ring-{{ $planColor }}-200 dark:ring-{{ $planColor }}-800/50
             px-2 py-0.5 text-xs font-medium capitalize">
    {{ $plan }}
</span>
```
For dynamic Tailwind classes, ensure the color families are in `tailwind.config.js` `safelist` so `text-blue-100`, `text-blue-900`, etc. all get compiled.

---

## Failure mode 8 — Dark-theme classes surviving inside a light-theme modal

**Symptom:** The customer-facing "Request Facebook connection" modal renders with:
- An instruction box that looks like a light-blue outlined block with almost-invisible bluish-white body text (`bg-blue-500/5` + `text-blue-200`)
- Amber "Note:" paragraph that's near-invisible on the light bg (`text-amber-200/90`)
- A "Cancel" button that's just floating text with no visible border (`variant="ghost"`)
- A footer border you can barely see (`border-zinc-200/10`)
- Typed input text that's greyer than the placeholder

User reports "washed out modal", "can barely read the instructions", or "apply contrast skill here <pastes text>".

**Root cause:** The modal was originally written when the app had a DARK shell. On dark bg:
- `bg-blue-500/5` reads as "faint blue on ink" — fine
- `text-blue-200` reads as "pale blue on dark" — legible
- `border-white/10` is a faint white separator on ink — visible
- `variant="ghost"` shows white text on ink — a real button

When the app switched to a LIGHT (`bg-cream`) shell, these classes DID NOT get updated. On a cream/white bg:
- `bg-blue-500/5` = ~95% cream, ~5% blue = still cream
- `text-blue-200` = light pastel blue = washes out on cream
- `border-white/10` = 90% invisible on cream
- `variant="ghost"` = text with no bg, no border, no ring = invisible button

The trap: `<flux:modal>` and `<flux:input>` LOOK like they'll adapt to the app theme. They don't. Whatever classes you passed at build time render literally.

**Diagnostic (grep-time):**
```bash
# Every modal file — check for dark-theme-only classes rendering on light bg
grep -rEn "bg-[a-z]+-500/[0-9]{1,2}|text-(white|[a-z]+-100|[a-z]+-200)|border-white/[0-9]{1,2}|variant=\"ghost\"" resources/views/livewire/**/*.blade.php | grep -iE "modal|dialog|drawer|panel"
```

Any hit inside a `<flux:modal>` on a light-themed page is at risk.

**Fix pattern:** Rewrite the modal with the "always-safe" pairings from the table below. Replace every dark-theme class:

| Was (dark theme) | Now (light theme) |
|---|---|
| `bg-blue-500/5` + `text-blue-200` | `bg-blue-50 border-blue-200 text-blue-900` |
| `bg-amber-500/10` + `text-amber-200` | `bg-amber-50 border-amber-200 text-amber-900` |
| `text-white/60` (body copy) | `text-zinc-700` (light) `dark:text-zinc-200` (dark, if supported) |
| `text-white/40` (tertiary) | `text-zinc-600` (light) |
| `border-white/10` | `border-zinc-200 dark:border-zinc-700` |
| `variant="ghost"` (secondary button) | Plain HTML `<button class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-zinc-50">` |
| `<flux:input>` (typed text is zinc-500) | Hand-rolled `<input class="... text-ink placeholder:text-zinc-400 focus:border-emer-500 focus:ring-2 focus:ring-emer-100">` (see failure mode 2 pattern) |
| `<flux:textarea>` | Hand-rolled `<textarea>` with same brand focus classes |

For dual-theme support, ALWAYS pair a `dark:*` variant with any surface class:
```blade
<div class="rounded-lg border border-blue-200 bg-blue-50
            dark:border-blue-800/60 dark:bg-blue-900/20
            text-blue-900 dark:text-blue-100">
```

**Real-world reference:** the `<flux:modal name="onboarding-request">` in `resources/views/livewire/connections/index.blade.php` — read the current version (post-rewrite) for a complete "before/after" of every class swap in one modal.

---

## Failure mode 7 — Gray-on-gray (table headers, muted text in dark mode)

**Symptom:** Table `<thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-500">` — column labels look fine in light mode, but in dark mode `text-zinc-500` on `bg-zinc-800` is gray-on-slightly-lighter-gray = illegible column headers. Same for `text-zinc-500` on `text-zinc-400` inside dark-tinted cards, and `text-zinc-500` as secondary text on `bg-zinc-800` panels.

**Root cause:** `text-zinc-500` is a middle-gray that's readable on white (contrast ~5:1) but fails on any dark bg (contrast ~2:1). The `text-zinc-400` variant used for tertiary text is worse — it's basically invisible against zinc-800.

**Diagnostic (grep-time):**
```
grep -rn 'text-zinc-500\|text-zinc-400' resources/views
```
Every match is suspicious. Especially check tables, panel headers, "meta" text below primary content, and empty states.

**Fix pattern:** For any secondary/tertiary text that renders in BOTH light and dark themes, use paired variants:
```
Primary text:      text-zinc-900 dark:text-zinc-100
Secondary text:    text-zinc-700 dark:text-zinc-200   (was text-zinc-500)
Tertiary text:     text-zinc-600 dark:text-zinc-300   (was text-zinc-400)
Table header:      text-zinc-700 dark:text-zinc-200
```
Never use `text-zinc-500` or below without a dark: variant on dark surfaces. On white-only surfaces, `text-zinc-500` is only acceptable for placeholder text — never for real content.

---

## Failure mode 5 — White text on gradient with low opacity color-tint background

**Symptom:** Card with `bg-gradient-to-br from-indigo-500/10 via-violet-500/10 to-fuchsia-500/10` + `text-white` copy. Text becomes near-invisible because 10% opacity gradient over white page bg = ~90% white surface.

**Root cause:** Copying the "committed color strategy" (from `impeccable` skill) without understanding that the opacity modifier means the background is mostly the page background. White text needs a background at least ~50% saturated to have real contrast.

**Diagnostic:** Any element combining `text-white` (or `text-white/*`) with a `bg-*/{10,15,20,25}` background:
```
grep -rn 'text-white' resources/views | xargs grep -l 'bg-.*/1[05]'
```

**Fix pattern:** Two options:
1. **Solid saturated background** (Committed color strategy):
   ```blade
   <div class="bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-5 text-white">
       ...
   </div>
   ```
   Now white text has real contrast against the strong gradient.

2. **Keep low-opacity background, switch text to dark**:
   ```blade
   <div class="bg-indigo-500/10 border border-indigo-500/30 p-5 text-indigo-900">
       ...
   </div>
   ```
   The `-900` text on `-500/10` gives readable contrast against page white.

---

## Pre-flight checks before writing UI

Before opening any Blade file to add/edit color:

1. **Read the parent's background color.** If you're inside a card at `bg-white`, plan your palette around dark text. If you're inside a `bg-zinc-900` panel, plan around white text.
2. **Never write `text-white` without checking the resolved background at that specific point in the DOM.** Opacity modifiers on the parent are frequently the trap.
3. **Grep for every Flux component you're about to use** on `<flux:badge`, `<flux:text` inside tinted containers, `<flux:button variant="ghost"` on light bg. If any match, prefer the plain HTML equivalent.
4. **When in doubt, use `-900` on `-100`.** This combo always passes AA.
5. **For inputs and textareas**, always add `text-zinc-900` + `placeholder:text-zinc-400` explicitly. Never rely on the Flux default.

---

## Safe palette combinations for this codebase

These pairings are all verified AA on the ot1-pro light theme:

| Text | Background | Ring | Use case |
|------|-----------|------|----------|
| `text-zinc-900` | `bg-white` | `border-zinc-300` | body text, inputs, cards |
| `text-zinc-700` | `bg-zinc-50` | `border-zinc-200` | secondary text, subtle sections |
| `text-violet-900` | `bg-violet-100` | `ring-violet-200` | primary badges, chips |
| `text-emerald-900` | `bg-emerald-50` | `border-emerald-200` | success flash |
| `text-red-900` | `bg-red-50` | `border-red-200` | error flash |
| `text-amber-900` | `bg-amber-100` | `ring-amber-200` | warning badges, "Head Admin" tier chips |
| `text-blue-900` | `bg-blue-100` | `ring-blue-200` | info chips, permission tags |
| `text-white` | `bg-violet-600` | (none) | primary CTAs, active nav |
| `text-white` | `bg-gradient-to-br from-indigo-600 to-fuchsia-600` | (none) | hero cards |

If you find yourself wanting to use a combo not in this table, stop and think about whether you're introducing a new visual pattern the app doesn't have. The app already leans violet for primary + zinc for neutral. Every new color you introduce is a maintenance cost.

---

## Related skills

- `impeccable` for broader design decisions (color strategy, register, typography)
- `livewire-alpine-dirty-guard` when the input contrast fix touches form-state persistence
- `inbox-composer-safety` if editing inbox/index.blade.php (contrast + morphdom compound issues)
