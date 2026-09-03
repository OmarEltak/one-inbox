---
name: inbox-composer-safety
description: Use BEFORE any edit to resources/views/livewire/inbox/index.blade.php OR resources/views/components/inbox/*.blade.php OR anything that adds a modal / floating widget / teleported element / Alpine x-data component / inline <script> to the inbox. Codifies the six Livewire + Flux + Alpine + morphdom failure modes that have already caused 5+ prod outages on ot1-pro.com — each one silently breaks something (wire:click dies, mic button invisible, audio won't play, 100+ Alpine errors per morph) while looking fine at first glance. If you're about to add `<x-inbox.something>`, `<flux:modal>`, `x-teleport`, `wire:ignore`, `x-cloak`, `x-data="myFunc()"`, or an inline `<script>` inside the inbox Livewire component, invoke this skill first. Also use when the user reports "chat rows don't open", "clicking a chat does nothing", "wire:click stopped working", "the composer looks broken", "mic button is invisible / doesn't respond", "audio player won't play", "voice recorder does nothing", "console errors: X is not defined", or "the button works on first load but not after morph".
---

# Inbox Composer Safety

The `/inbox` Livewire component (`app/Livewire/Inbox/Index.php` + `resources/views/livewire/inbox/index.blade.php`) is a single-page app inside a single Livewire component. It renders 50+ `wire:click` handlers, hosts multiple Flux modals, lives alongside Alpine components with `x-teleport`, and re-morphs its subtree constantly (wire:poll every 30s, conversation selection, echo events). It has SIX failure modes that all present differently but share the same class: **something in the composer/inbox looks fine visually but silently doesn't work.**

Every session that touches this file eventually trips one of these. This skill codifies them so you don't lose another 3 hours diagnosing.

---

## Failure mode 1 — Multiple root elements

**Symptom:** Chat rows highlight on hover but don't open when clicked. No XHR fires. `document.querySelectorAll('[wire:id]')` shows 2+ roots.

**Root cause:** Livewire strictly requires exactly ONE top-level element per component. When the rendered output has two root-level elements (siblings), Livewire's runtime picks the FIRST one as the wire root and stamps `wire:id` on it. Every `wire:click` handler looks for its component via `closest('[wire:id]')` — buttons inside the OTHER root find nothing and become dead.

**Diagnostic:**
```js
document.querySelectorAll('[wire\\:id]').length   // must be exactly 1
document.querySelector('button[wire\\:click^="selectConversation"]').closest('[wire\\:id]')
// must return the outermost <div class="flex h-full overflow-hidden">
```

**Rule:** The first non-whitespace character in `inbox/index.blade.php` MUST be `<div class="flex h-full overflow-hidden"`. The last non-whitespace character MUST be `</div>` closing that same div. Nothing before, nothing after — not even a Blade component tag.

---

## Failure mode 2 — Flux modal teleport drags trailing siblings

**Symptom:** You added `<x-inbox.lightbox />` INSIDE the root `<div>` (correctly, per rule 1). Chat rows STILL don't open. The lightbox's fixed-position `<div>` shows up as a sibling of the main root in DevTools.

**Root cause:** `<flux:modal>` uses `x-teleport="body"` internally. At Alpine hydration time it moves ITS OWN DOM AND anything that visually follows it in the same parent, out of the Livewire root. If your `<x-inbox.lightbox />` sits after `</flux:modal>` inside the root div, Alpine drags it too. Result: lightbox becomes a body-level sibling of the Livewire root → falls under rule 1 → wire:click dies.

**Diagnostic:** Look at `<body>` children in DevTools. If you see two divs at that level where you expected one, one of them was dragged by a Flux teleport.

**Rule:** **NEVER put a floating/fixed-position child (`class="fixed inset-0"`, teleport target, lightbox, banner) inside a Livewire component that also has a `<flux:modal>`.** Put such widgets in the top-level layout (`resources/views/components/layouts/app/*.blade.php`) and communicate with them via `window`-level custom events (`x-on:my-event.window="..."`).

---

## Failure mode 3 — `wire:ignore` + `display: contents` breaks Alpine init

**Symptom:** A specific button/widget doesn't render even though its `wire:show` / `x-show` should evaluate true. `document.querySelector('[title="..."]')` returns the element but `getBoundingClientRect()` gives 0×0.

**Root cause:** The combination of `wire:ignore` (Livewire skips this subtree on morph) + a wrapper with `class="contents"` (which removes the wrapper's own box) creates an initialization race — Alpine may fail to hydrate the subtree on the first render, leaving `x-show="condition"` uninitialised and defaulting to hidden (or the button gets zero layout box). Historically bit us on the voice-recorder mic.

**Rule:** If you need `wire:ignore` to preserve Alpine state across Livewire re-renders, give the wrapper a REAL layout (`class="flex items-center"` or similar), never `class="contents"`. Accept that state resets on re-render if you'd rather use a real box.

---

## Failure mode 4 — `x-teleport` zombie DOM across Livewire re-renders

**Symptom:** Two versions of the same widget appear simultaneously (e.g. "Recording…" pill AND "Sending…" spinner both visible, or ghost lightboxes stack on `<body>`).

**Root cause:** `x-teleport="body"` moves the element out of the Livewire component's DOM subtree. When Livewire morphs the component (on `$refresh`, `wire:poll`, echo event), it removes/rebuilds the ORIGINAL location but the TELEPORTED element is at body-level and thus untouched. New component render mounts a fresh Alpine scope with a fresh teleport → now two teleported clones exist, each pointing at a different (or dangling) Alpine state.

**Rule:** **Do not use `x-teleport` for elements inside a Livewire component** unless you can guarantee the Livewire component never re-renders while the teleported element is visible. In practice for `/inbox`: don't. Render overlays inline (`flex-1`, `absolute`, whatever) or hoist to the top-level layout.

---

## Failure mode 5 — Alpine expression evaluator runs BEFORE inline script defines its `x-data` function

**Symptom:** Console spam of `ReferenceError: voiceRecorder is not defined` (or `audioPlayer is not defined`, or whatever your `x-data="myFunc()"` name is) — dozens to hundreds of errors, one per element per morph. The affected widget renders with no styling (Alpine's `:class` binding evaluates to `undefined`, so it silently applies no classes — the mic button becomes invisible; the audio bubble has no play button).

**Root cause:** Alpine hydrates the DOM by walking for `x-data` and evaluating the expression **as JS in the current global scope, right now**. If your component blade file looks like:

```blade
<button x-data="voiceRecorder()" ...>...</button>
<script>function voiceRecorder() { ... }</script>
```

…then on FIRST page load this happens to work because the browser parses top-to-bottom and the `<script>` runs before Alpine's initial sweep. But after a Livewire morph, morphdom re-injects the `x-data` attribute BEFORE the `<script>` tag runs (and in many cases doesn't re-run the script at all — see failure mode 6). Alpine evaluates `voiceRecorder()` → not defined → throws → binding is `undefined` → button has no color classes → invisible.

**Rule:** **Do NOT define an Alpine `x-data` function in the same Blade component that uses it via an inline `<script>`.** Two acceptable patterns:

1. **Vanilla JS + delegated handler + MutationObserver.** No `x-data` at all. Component is plain HTML with `data-*` attributes. A single installer script in the layout wires up handlers via event delegation and watches for new nodes via MutationObserver. This is what `voice-recorder.blade.php` and `audio-player.blade.php` do today — read them for the pattern.
2. **Define the function in a real JS module** (e.g. `resources/js/inbox-widgets.js`) that's bundled into `resources/js/app.js` and loaded once in the layout `<head>`. Then `x-data="voiceRecorder()"` resolves regardless of morph timing.

Never rely on an inline `<script>` inside a Livewire-rendered component to define an Alpine dependency.

---

## Failure mode 6 — Livewire morphdom preserves inline `<script>` tags but does NOT execute them on re-render

**Symptom:** A widget works perfectly on first page load. After ANY Livewire action that morphs the component (send a message, receive an echo, `wire:poll`, `$refresh`), the widget silently stops working — clicks do nothing, no console errors, no XHR fires. Hard reload fixes it until the next morph.

**Root cause:** morphdom keeps the `<script>` element in the DOM tree but the browser does not re-execute inline scripts that get patched into place. Any `addEventListener('click', ...)` or `new MutationObserver(...)` you wrote inside a component-local `<script>` is only ever installed the FIRST time that component ever rendered on this page load. If the component didn't exist on initial page load and only appeared after a Livewire re-render (common for conditional widgets), the script never runs — the widget is dead on arrival.

Corollary: `@push('scripts')` only fires when its containing Blade renders. A component pushed into `@stack('scripts')` won't re-push on morph either, and if the layout stack already flushed, the push goes nowhere.

**Rule:** **All JS installers for inbox widgets live in `resources/views/layouts/app/sidebar.blade.php`** (loaded exactly once per authenticated page, immune to Livewire morphs). Wrap each installer in a `window.__someFlag` guard so re-executions (during a future full page load in a wire:navigate SPA-like transition) don't stack duplicate listeners:

```js
(function () {
  if (window.__inboxAudioPlayerInstalled) return;
  window.__inboxAudioPlayerInstalled = true;
  // delegated click handler + MutationObserver here
})();
```

Then use **event delegation** on `document` (works for elements that appear later) and a **MutationObserver on `document.body`** (auto-wires per-instance state as new widgets appear during morphs). Never depend on the widget's own inline `<script>` for behavior.

Component blade files may keep a copy of the installer inside `@if(false) <script>...</script> @endif` as documentation — that's a signal to future readers where the real installer lives.

---

## Universally-applicable checks before you commit an inbox blade change

Run this JS in the browser after loading `/inbox`:

```js
JSON.stringify({
  roots: document.querySelectorAll('[wire\\:id]').length,          // must be 1
  rootClass: document.querySelector('[wire\\:id]').className.slice(0, 60),  // must start with 'flex h-full overflow-hidden'
  btnInRoot: !!document.querySelector('button[wire\\:click^="selectConversation"]').closest('[wire\\:id]'),  // must be true
  bodyChildren: document.body.children.length  // sanity — should be roughly what layout intends
})
```

If any check fails, you have a wire-click-killing bug. Fix before shipping.

---

## Post-deploy verification

For any change to `inbox/index.blade.php` or `inbox/*.blade.php`:

```bash
ssh root@187.77.67.94 "sudo -u deploy php /var/www/ot1-pro.com/artisan view:clear && sudo -u deploy php /var/www/ot1-pro.com/artisan config:cache && systemctl reload php8.4-fpm"
```

Then use a fresh browser tab (cache-busting `?v=$(date +%s)`) and click any chat. If the right panel doesn't populate within 500ms, roll back — you tripped one of the six failure modes.

**For audio/mic widget changes specifically**, after the reload also verify:

```js
JSON.stringify({
  audioInstaller: !!window.__inboxAudioPlayerInstalled,     // must be true
  micInstaller:   !!window.__inboxVoiceRecorderInstalled,   // must be true
  micVisible:     document.querySelector('[data-vr-toggle]')?.getBoundingClientRect().width > 0,  // must be true
  audioBtns:      document.querySelectorAll('[data-ap-toggle]').length,  // > 0 if conversation has audio
  alpineErrors:   'check console — should be ZERO "is not defined" errors'
})
```

Then send a message in an open conversation (triggers a Livewire morph). Re-run the check — installers must still be true, mic must still be visible. If either drops, you re-introduced failure mode 5 or 6.

---

## Real incidents this skill would have prevented

| Date | Symptom | Root cause | Time lost |
|---|---|---|---|
| 2026-09-01 21:35 | `/inbox` returned 500 | `<x-inbox.lightbox>` referenced but Blade file missing after PR revert-of-revert | ~1h |
| 2026-09-01 20:15 | Chat rows dead | Lightbox on line 1, outside root (mode 1) | ~1h |
| 2026-09-01 20:50 | Chat rows dead again after "fix" | Lightbox inside root but AFTER `<flux:modal>` → dragged out by teleport (mode 2) | ~1h |
| 2026-09-01 22:06 | Mic invisible + zombie recording pills | `wire:ignore` + `display:contents` (mode 3) AND `x-teleport` zombies (mode 4) | ~40m |
| 2026-09-02 | 100+ `voiceRecorder is not defined` errors per morph, mic invisible | Alpine hydrated before inline `<script>` defined the function (mode 5) | ~45m |
| 2026-09-02 | Voice-note button worked on first load, dead after any Livewire morph | morphdom re-inserted `<script>` node but browser did not re-execute it (mode 6) | ~30m |
| 2026-09-02 | Audio bubble showed play button but clicking did nothing | Same as above — audio-player installer was inline, killed on first morph | ~30m |

Total: ~5h+ of production debugging that could have been zero. This skill exists so future sessions read it FIRST and skip all of that.

---

## Related files

- `resources/views/livewire/inbox/index.blade.php` — the Livewire root file (~790 lines)
- `resources/views/components/inbox/*.blade.php` — sub-components (media-bubble, audio-player, voice-recorder)
- `app/Livewire/Inbox/Index.php` — the PHP component class
- `docs/ARCHITECTURE.md` §6 (sidebar-gating), §14 (Flux modal invariants — see CLAUDE.md pin #6 about `Flux::modal(...)->show()` NOT `$this->dispatch('open-modal', ...)`)

## When to invoke this skill

- BEFORE editing any file in `resources/views/livewire/inbox/` or `resources/views/components/inbox/`
- BEFORE adding `<flux:modal>`, `x-teleport`, `wire:ignore`, or any `class="fixed inset-0"` element to the inbox
- WHEN the user reports chat rows unclickable / composer broken / "wire:click stopped working"
- BEFORE claiming "the composer UI change is done" — run the browser diagnostic first
