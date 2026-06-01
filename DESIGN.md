---
name: OT1-Pro
description: The AI sales floor. Open 24/7. Visual system for the unified DM sales workspace.
colors:
  midnight-base: "#0A0A0F"
  midnight-deep: "#0D0D1A"
  midnight-violet: "#111127"
  ink-elevated: "#191C1D"
  pro-violet: "#7C3AED"
  pro-violet-deep: "#6D28D9"
  pro-violet-tint: "#C27AFF"
  signal-cyan: "#06B6D4"
  marketing-violet: "#9333EA"
  marketing-azure: "#2563EB"
  channel-facebook: "#1877F2"
  channel-instagram: "#E1306C"
  channel-whatsapp: "#25D366"
  channel-telegram: "#0088CC"
  channel-tiktok: "#EE1D52"
  channel-snapchat: "#FFFC00"
  channel-email: "#F97316"
  status-hot: "#FB2C36"
  status-hot-tint: "#FB7185"
  status-warm: "#F59E0B"
  status-warm-tint: "#FBBF24"
  status-cold: "#6366F1"
  status-cold-tint: "#818CF8"
  status-converted: "#00D492"
  status-converted-tint: "#34D399"
  status-new: "#99A1AF"
  status-lost: "#6A7282"
  paper: "#FAFAFA"
  paper-tinted: "#F5F5F5"
  rule-light: "#E5E5E5"
  ink-soft: "#737373"
  ink: "#262626"
  ink-deep: "#171717"
typography:
  display:
    fontFamily: "Cairo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 6vw, 3.75rem)"
    fontWeight: 700
    lineHeight: 1.05
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Cairo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(1.5rem, 3vw, 2.25rem)"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.015em"
  title:
    fontFamily: "Cairo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "-0.005em"
  body:
    fontFamily: "Cairo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  body-marketing:
    fontFamily: "Cairo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.65
    letterSpacing: "normal"
  label:
    fontFamily: "Cairo, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.6875rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0.075em"
  mono:
    fontFamily: "ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"
    fontSize: "0.6875rem"
    fontWeight: 500
    lineHeight: 1.2
    letterSpacing: "normal"
rounded:
  pill: "9999px"
  sm: "8px"
  md: "10px"
  lg: "12px"
  xl: "14px"
  "2xl": "16px"
  "3xl": "20px"
spacing:
  "0.5": "2px"
  "1": "4px"
  "1.5": "6px"
  "2": "8px"
  "3": "12px"
  "4": "16px"
  "5": "20px"
  "6": "24px"
  "8": "32px"
  "10": "40px"
  "12": "48px"
  "16": "64px"
components:
  button-primary:
    backgroundColor: "{colors.pro-violet}"
    textColor: "#FFFFFF"
    rounded: "{rounded.lg}"
    padding: "10px 20px"
    typography: "{typography.body}"
  button-primary-hover:
    backgroundColor: "{colors.pro-violet-deep}"
    textColor: "#FFFFFF"
  button-primary-marketing:
    backgroundColor: "{colors.marketing-violet}"
    textColor: "#FFFFFF"
    rounded: "{rounded.lg}"
    padding: "14px 32px"
    typography: "{typography.title}"
  button-secondary:
    backgroundColor: "rgba(255,255,255,0.04)"
    textColor: "rgba(255,255,255,0.80)"
    rounded: "{rounded.lg}"
    padding: "10px 20px"
    typography: "{typography.body}"
  button-secondary-hover:
    backgroundColor: "rgba(255,255,255,0.08)"
    textColor: "#FFFFFF"
  card-product:
    backgroundColor: "rgba(255,255,255,0.03)"
    textColor: "rgba(255,255,255,0.80)"
    rounded: "{rounded.xl}"
    padding: "20px"
  card-marketing:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.2xl}"
    padding: "24px"
  badge-status:
    backgroundColor: "rgba(244,63,94,0.15)"
    textColor: "{colors.status-hot-tint}"
    rounded: "{rounded.pill}"
    padding: "3px 10px"
    typography: "{typography.label}"
  input-field:
    backgroundColor: "rgba(255,255,255,0.04)"
    textColor: "rgba(255,255,255,0.80)"
    rounded: "{rounded.lg}"
    padding: "10px 14px"
    typography: "{typography.body}"
  input-field-focus:
    backgroundColor: "rgba(255,255,255,0.06)"
    textColor: "#FFFFFF"
  nav-item:
    backgroundColor: "transparent"
    textColor: "rgba(255,255,255,0.50)"
    rounded: "{rounded.lg}"
    padding: "10px 12px"
    typography: "{typography.body}"
  nav-item-active:
    backgroundColor: "{colors.pro-violet}"
    textColor: "#FFFFFF"
    rounded: "{rounded.lg}"
---

# Design System: OT1-Pro

## 1. Overview: The Operator's Console at Midnight

**Creative North Star: "The Operator's Console at Midnight"**

OT1-Pro looks the way an agency operator's command station looks at 1am: every channel visible at once, the AI quietly closing in the background, no decorative lights, no marketing buzz, no app trying to feel friendly. The interface is the operator's situational awareness, rendered in the lowest possible visual noise so the actual signal (a hot lead, a stalled conversation, an AI confidence dip) is impossible to miss.

The aesthetic is built from three layers stacked deliberately. **The product layer** is a deep midnight gradient (`#0A0A0F` to `#111127`) carrying tinted-white surfaces at 3-7% opacity: think of it as graphite-and-violet, not navy-and-purple. **The brand layer** lives in paper-white with the same violet accent at higher saturation: Stripe-grade restraint with one signature color that the product layer earns the right to use because it is the only color that means *AI is doing something*. **The signal layer** is a small set of named status colors (hot, warm, cold, converted, lost) and channel colors (FB blue, IG pink, WA green, TG cyan) that are reserved exclusively for state and identity; they are never decorative.

What this system explicitly rejects: the conversion-bro chatbot SaaS lane (ManyChat, ChatFuel, GoHighLevel) with its neon gradients, pulsing CTAs, and "🔥 hot deal 🔥" energy. And the legacy-CRM lane (Salesforce, HubSpot circa 2014) with its identical-card-grid feature pages and modal-on-modal soup. OT1-Pro is the operator's tool, not the affiliate marketer's funnel and not the corporate seat-license.

**Key Characteristics:**
- Dual register: dark-default product, light-default brand, one shared palette and type family.
- One accent color (Pro Violet `#7C3AED`) that earns its rarity by meaning "the AI is at work."
- Cairo as the system font, picked for native Arabic and Latin support at small sizes.
- Color is never decorative: it is identity (channel), state (status), or attribution (AI vs human).
- Motion serves feedback. Decorative motion is forbidden.

## 2. Colors: The Graphite-and-Violet Palette

A palette of one strong accent, channel-and-state semantics, and a midnight-graphite spine that carries both registers without losing identity.

### Primary

- **Pro Violet** (`#7C3AED` / `oklch(56% 0.24 290)`): the only purely-aesthetic color in the system. Reserved for: active navigation, primary CTA, AI-attribution UI, brand mark. Use rarely; its rarity is the point. The Tailwind equivalent is `purple-600`.
- **Pro Violet Deep** (`#6D28D9` / `oklch(50% 0.22 290)`): the gradient partner and `:hover` state of Pro Violet. Used in `linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%)` for the primary CTA and active sidebar pill.
- **Pro Violet Tint** (`#C27AFF` / `oklch(72% 0.18 295)`): used at lower-prominence on dark surfaces — small icons inside active tabs, the cyan-pair gradient accent on user avatars. Never used on a light surface; the contrast is wrong.

### Secondary

- **Signal Cyan** (`#06B6D4` / `oklch(70% 0.13 215)`): the deliberate secondary accent. Used in two places: paired with Pro Violet on user-avatar gradients (`linear-gradient(135deg, #7C3AED, #06B6D4)`), and as the "unread" stat-card top border. Cyan signals *information needing a glance*. Never used for state.

### Tertiary (Marketing-only)

- **Marketing Violet** (`#9333EA`) and **Marketing Azure** (`#2563EB`): the brighter purple-blue pair used by the current marketing hero. Documented but on probation: see the Don'ts. New brand surfaces should default to Pro Violet on Paper, not the magenta-azure pair on white.

### Channel Identity (semantic; never decorative)

These colors are *channel identifiers*. They appear only on per-channel chips, badges, and icons, never on chrome.
- **Facebook** (`#1877F2`)
- **Instagram** (`#E1306C`)
- **WhatsApp** (`#25D366`)
- **Telegram** (`#0088CC`)
- **TikTok** (`#EE1D52`)
- **Snapchat** (`#FFFC00`)
- **Email** (`#F97316`)

### Lead Status (semantic; pair always with a label, never color-alone)

- **Hot** (`#FB2C36` dot, `#FB7185` text on `rgba(244,63,94,0.15)` background): high-intent, ready-to-buy lead.
- **Warm** (`#F59E0B` dot, `#FBBF24` text on `rgba(245,158,11,0.15)` background): engaged, in qualification.
- **Cold** (`#6366F1` dot, `#818CF8` text on `rgba(99,102,241,0.15)` background): minimal recent activity.
- **Converted** (`#00D492` dot, `#34D399` text on `rgba(0,212,146,0.15)` background): closed, deal won.
- **New** (`#99A1AF` dot on `rgba(148,163,184,0.1)`): just arrived, not yet triaged.
- **Lost** (`#6A7282` dot on `rgba(100,116,139,0.1)`): closed-lost or unresponsive.

### Neutral (Product, dark)

The midnight spine. Background is a 135° gradient through four stops.

- **Midnight Base** (`#0A0A0F`): the gradient's terminator color (start + end), the body fallback solid.
- **Midnight Deep** (`#0D0D1A`): the gradient's 30% stop.
- **Midnight Violet** (`#111127`): the gradient's 60% stop; the violet bias is intentional and barely perceptible.
- **Ink Elevated** (`#191C1D`): solid surface for cases where the gradient cannot be used (Figma legacy `aio-btn-primary` non-gradient state).

Text and surface layering uses opacity-on-white:
- **`white/05`** through **`white/08`**: surface tints and borders. Cards (`white/03`), table row dividers (`white/04`), interactive borders (`white/06–08`).
- **`white/20–35`**: disabled text, decorative icons, breadcrumb separators.
- **`white/40–60`**: secondary text, captions, placeholder text.
- **`white/70–80`**: primary body text, active nav item label.
- **`#FFFFFF`**: titles, hot-state text, AI-attribution chips.

### Neutral (Brand, light)

- **Paper** (`#FAFAFA`): primary brand background. Tinted slightly toward the violet hue at chroma 0.005.
- **Paper Tinted** (`#F5F5F5`): card and section-band background on brand pages.
- **Rule Light** (`#E5E5E5`): hairline dividers on brand surfaces.
- **Ink Soft** (`#737373`): body text on brand pages.
- **Ink** (`#262626`): titles and primary text on brand pages.
- **Ink Deep** (`#171717`): the rare "shout" text on brand (use sparingly).

### Named Rules

**The One Accent Rule.** Pro Violet appears on ≤10% of any given screen. Its rarity is what makes it read as "the AI is at work." When in doubt, use a neutral and let the AI's purple do the talking elsewhere.

**The Color-Means-Something Rule.** Every non-neutral color in this system has a job. Channel colors mean "this conversation is from this platform." Status colors mean "this lead is at this temperature." Pro Violet means "this is AI." Cyan means "this needs your glance." Any use of color outside these meanings is decoration and forbidden.

**The Translucent Surface Cap.** Tinted-white surfaces (`rgba(255,255,255,X)`) cap at `0.08` opacity. Anything more opaque should become a solid `#191C1D` or graduate to a separate card surface. Stacked translucency past `0.08` reads as plastic, not graphite.

## 3. Typography: Cairo, Held Tight

**Display Font:** Cairo (with `ui-sans-serif, system-ui, sans-serif` fallbacks).
**Body Font:** Cairo (same family, lower weights).
**Mono Font:** `ui-monospace, SFMono-Regular, Menlo, Consolas, monospace` (used only for `⌘K` kbd chips and timestamps where alignment matters).

**Character:** Cairo is a hybrid Latin-Arabic family with a slightly larger x-height than Inter or Helvetica. This shows up as: readability holds at 12px, capitals feel quieter than a standard sans, and Arabic renders natively without falling back to a different family. The pairing of one family for everything carries OT1-Pro's restraint principle through the type stack.

### Hierarchy

- **Display** (`700` weight, `clamp(2.25rem, 6vw, 3.75rem)`, line-height `1.05`, letter-spacing `-0.02em`): brand hero headlines only. Product UI never uses display weight at display size.
- **Headline** (`700`, `clamp(1.5rem, 3vw, 2.25rem)`, line-height `1.15`, `-0.015em`): brand section headlines, product page titles.
- **Title** (`600`, `1.25rem` / `20px`, line-height `1.3`, `-0.005em`): card titles, modal headers, sidebar group labels.
- **Body** (`400`, `0.875rem` / `14px`, line-height `1.55`): product UI body text. The default everything-else.
- **Body Marketing** (`400`, `1.0625rem` / `17px`, line-height `1.65`): brand prose. Use 65–75ch max line length.
- **Label** (`600`, `0.6875rem` / `11px`, line-height `1.2`, letter-spacing `0.075em`, uppercase): stat card labels, table column headers, badge text.
- **Mono** (`500`, `0.6875rem` / `11px`): keyboard shortcut chips, timestamps in compact UI.

### Named Rules

**The One Family Rule.** Cairo for everything except the mono cases listed above. No display serif. No editorial-script accent. No secondary sans for "marketing only." The product and the brand share a voice.

**The 14px Body Rule.** Product body text is 14px. This is small enough to support density without slipping into illegible territory thanks to Cairo's larger x-height. Do not raise product body to 16px because "the web standard is 16px." OT1-Pro's web standard is 14px; the operator wants more on screen, not less.

**The 65ch Line Cap Rule.** Brand prose caps at 65–75 characters per line. Long-form content (blog, about) explicitly. This is a hard cap; respect it even when the column would visually allow more.

**The Arabic-Width Rule.** Arabic text occupies roughly 80% the width of equivalent English at the same point size. Containers that fit English copy at the design-intent width must not collapse when Arabic shortens the line; treat it as honest, not as a layout bug.

## 4. Elevation: Flat With Functional Shadows

The system is essentially flat. Surfaces are distinguished by tint (`rgba(255,255,255,X)` layering on dark; solid `#FAFAFA` over `#F5F5F5` on light), not by drop shadow. Shadows appear only where they do real work: telling the operator that a surface is *above* and *responsive*.

### Shadow Vocabulary

- **Sidebar elevation** (`box-shadow: 4px 0 30px rgba(0,0,0,0.6)`): the sidebar separates from the gradient body. Diffuse, directional, runs the full height.
- **Header glass** (`backdrop-filter: blur(16px)` + `background: rgba(10,10,20,0.85)` + `border-bottom: 1px solid rgba(255,255,255,0.06)`): the sticky header is the one place glassmorphism is appropriate. It serves a function (preserving spatial awareness of the body content scrolling underneath).
- **Card resting** (`box-shadow: 0 1px 0 rgba(255,255,255,0.05) inset, 0 4px 24px rgba(0,0,0,0.3)`): subtle 1px highlight + soft drop. Distinguishes the `aio-card` from the gradient body.
- **Card hover** (`box-shadow: 0 1px 0 rgba(255,255,255,0.07) inset, 0 8px 32px rgba(0,0,0,0.4)` + border `rgba(124,58,237,0.25)`): the card lifts and the violet edge appears to signal interactivity.
- **AI-attributed surface** (`box-shadow: 0 2px 12px rgba(124,58,237,0.35)`): the Pro Violet glow. Reserved for the active sidebar nav pill, the primary CTA, the AI-replied chip. Never for ambient decoration.
- **Notification dot** (`border: 2px solid #0A0A0F` ring around a `#FB2C36` dot): a hard contrast ring that lets the red dot read on any background.

### Named Rules

**The Flat-By-Default Rule.** Every new surface starts flat. Shadow is added only when the surface has a function the shadow makes possible: lift on hover (interactivity), glow when AI is the actor (attribution), header-over-content (spatial preservation). A shadow without a job is decoration and forbidden.

**The Glassmorphism-Has-One-Job Rule.** Backdrop blur is permitted only on the sticky header and the sticky sidebar — surfaces that overlay scrolling content and need to preserve spatial context. It is forbidden on cards, modals, badges, and any other ambient use. The current `app.css` defines blur on the sidebar (acceptable, sticky overlay) and the header (acceptable, sticky overlay). New uses require explicit justification in PR description.

## 5. Components

### Buttons

- **Primary (product)**: solid Pro Violet `#7C3AED` background, white text, `12px` border-radius, `10px 20px` padding, no border. Body weight. Hover transitions background to `#6D28D9` with the violet glow shadow appearing in `200ms ease-out-quart`. Focus shows a 2px white ring with 2px offset. The current `aio-btn-primary` class uses a gradient (`linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%)`); this is acceptable as a solid alternative; the *resting* state is the gradient, the *hover* state intensifies the shadow.

- **Primary (marketing)**: `#9333EA` (purple-600) background, white text, `12px` border-radius, `14px 32px` padding, title weight, no decorative shimmer. The current marketing CTA uses a `btn-shimmer` class that sweeps a translucent gradient across the button on hover; this is decorative motion and should be removed.

- **Secondary**: `rgba(255,255,255,0.04)` background, `rgba(255,255,255,0.80)` text, `12px` radius, `10px 20px` padding, no visible border at rest, hover lifts background to `rgba(255,255,255,0.08)` and text to full white. Used for "See How It Works", "Cancel", non-destructive secondary actions.

- **Ghost**: transparent background, `rgba(255,255,255,0.50)` text, no border, identical padding. Hover lifts text to `rgba(255,255,255,0.80)` and adds `rgba(255,255,255,0.05)` background.

- **Destructive**: solid `#FB2C36` background, white text, identical shape. Used only for "Delete account", "Disconnect channel", "Stop the AI" — actions the user could regret.

### Status Badges (the `aio-badge` family)

A pill (`9999px` radius), `3px 10px` padding, `11.5px` font-size, `600` weight, `0.02em` tracking. Leads with a `5px` color dot before the label. Background is the status color at 15% opacity, border is the status color at 30% opacity, text is the status-tint shade (lighter than the dot for contrast on the muted background).

All six states are documented in §2 Colors → Lead Status. Always paired with a label; never standalone color.

### Channel Chips (the `aio-page-chip` family)

Inline pill, `8px` radius, `3px 8px 3px 6px` padding (asymmetric for the leading icon), `11px` font-weight `500`. Background `rgba(255,255,255,0.04)`, border `rgba(255,255,255,0.08)`, text `#99A1AF`. Hover promotes border to `rgba(124,58,237,0.3)`. The leading 4px×4px square uses the channel color at 13% opacity with the channel color at full saturation for the channel initials text (FB, IG, WA, TG, TT, SC, EM).

### Stat Cards (the `aio-card` family)

`14px` radius, `rgba(255,255,255,0.03)` background, `rgba(255,255,255,0.07)` border, `20px` internal padding. Optional `2px solid <color>` top accent (`aio-stat-blue`, `aio-stat-green`, etc.) — this top accent is on probation: it edges close to a side-stripe pattern and should be replaced with a small leading icon-tile (already present as `aio-icon-X`) doing the same job more legibly. Hover lifts the card 4px (transform `translateY(-4px)`) and intensifies the shadow.

Structure: small caps label (top-left) + colored icon-tile (top-right) + 3xl number (bottom-left) + small caption (under number) + 80×40px sparkline SVG (bottom-right). This is the project's signature dashboard primitive.

### Inputs / Fields

- Background `rgba(255,255,255,0.04)`, border `rgba(255,255,255,0.07)`, `12px` radius, `10px 14px` padding. Placeholder text at `rgba(255,255,255,0.25)`.
- Focus: background lifts to `rgba(255,255,255,0.06)`, border lifts to `rgba(124,58,237,0.50)` (signaling violet-accent ownership of "this is the focused field"), and Flux's own focus ring (`ring-2 ring-accent ring-offset-2`) is applied via the `app.css` selector.
- Error: border shifts to `#FB2C36`, error message appears below in `#FB7185` at body-small size.
- Disabled: background drops to `rgba(255,255,255,0.02)`, text to `rgba(255,255,255,0.25)`, cursor `not-allowed`.

### Navigation (Sidebar)

The sidebar is `OT1-Pro`'s most distinctive layout primitive. Sticky, collapsible-on-mobile, `rgba(10,10,20,0.95)` background with `12px` backdrop blur, right border (`border-inline-end`) at `rgba(255,255,255,0.06)`, and a `4px 0 30px rgba(0,0,0,0.6)` shadow.

Nav items: `10px 12px` padding, `12px` radius, `14px` body text, `rgba(255,255,255,0.50)` resting color, `rgba(255,255,255,0.80)` on hover with `rgba(255,255,255,0.05)` background.

Active state: full white text, `12px` radius, background `linear-gradient(135deg, rgba(124,58,237,0.85) 0%, rgba(109,40,217,0.85) 100%)`, shadow `0 2px 12px rgba(124,58,237,0.35)`. This is the only place in the system where the violet gradient is a load-bearing UI element.

### Inbox Row (signature component)

The single most-used component in the product. Three visual zones in a flex row at `~64px` height with a left avatar (`size-10`), middle content (contact name + last-message preview, vertically stacked), right metadata (timestamp + unread count + status badge stacked at top-right).

- Hover: row background lifts to `rgba(124,58,237,0.05)` and a 1px `rgba(124,58,237,0.20)` left border appears as a hairline scope-indicator (this is permissible because it is 1px, the system's hard limit for left borders).
- Selected: persistent `rgba(124,58,237,0.10)` background plus a `2px` Pro Violet left scope-indicator (also 1px-or-2px max, never wider).
- Unread: contact name jumps to `font-weight: 600`, an unread dot (`size-2 bg-pro-violet rounded-full`) appears next to the timestamp.

### Header (sticky)

`rgba(10,10,20,0.85)` background, `16px` backdrop blur, `1px` `rgba(255,255,255,0.06)` bottom border. Three zones: breadcrumb (left), `⌘K` search bar (centered, max-width `24rem`), notification + avatar dropdown (right).

The search bar is a clickable chip rather than an active input — `rgba(255,255,255,0.04)` background, `rgba(255,255,255,0.07)` border, `12px` radius, `10px 14px` padding, with a `Search...` placeholder and a `⌘K` kbd chip on the right. Clicking it opens the command palette (TODO: not yet built).

## 6. Do's and Don'ts

### Do:

- **Do** use Pro Violet `#7C3AED` only for: active nav, primary CTA, AI-attribution, brand mark. Stay under 10% of any screen.
- **Do** pair every status color with a text label. Color-blind operators must be able to triage.
- **Do** keep product body type at 14px. Density is a feature.
- **Do** test every new screen in `dir="rtl"` before considering it done. RTL is a layout, not a translation.
- **Do** use the channel color (FB blue, IG pink, WA green, TG cyan) on per-channel chips and badges, never anywhere else.
- **Do** respect `prefers-reduced-motion`. Currently nothing in `app.css` does; new components must.
- **Do** raise interactive border opacity to at least `rgba(255,255,255,0.15)` for WCAG 2.2 AA 3:1 contrast. The current `white/06` and `white/07` borders fail this.
- **Do** attribute every AI-sent message visually: "Replied by AI · 2m · 96% confidence". Silent AI is a trust failure.

### Don't:

- **Don't** use gradient text. The current marketing hero (`bg-clip-text` from purple to blue on "One inbox.") is on the impeccable absolute-ban list. Replace with a solid color and use weight or size for emphasis.
- **Don't** use glassmorphism on cards, modals, or badges. The header and sidebar are the only sanctioned glass surfaces because they sit above scrolling content and serve spatial preservation.
- **Don't** add floating animated gradient blobs to any new page. The current hero (`size-[600px] rounded-full bg-purple-500/10 blur-3xl animate-float`) is exactly the "generic AI tool template" reflex. Remove it.
- **Don't** use side-stripe borders wider than 1px as colored accents. The `aio-stat-X` `border-top: 2px solid <color>` is on probation: replace with the leading colored icon-tile pattern.
- **Don't** use the hero-metric template: big number, small label, gradient accent, supporting stats. SaaS cliché.
- **Don't** build identical card grids. If the brand site needs to list features, vary the cards by content density and image presence; don't ship nine `icon + heading + paragraph` clones.
- **Don't** use the `btn-shimmer` decorative hover sweep on production CTAs. Function only.
- **Don't** put modals on top of modals. If a flow needs three modals to complete, the flow is the bug.
- **Don't** use em dashes in copy. Use commas, colons, semicolons, periods, or parentheses.
- **Don't** use exclamation points in product copy. They read amateurish and trigger the ManyChat-lane association.
- **Don't** ship the "One Inbox" name in new code or copy. The brand is **OT1-Pro**.
- **Don't** treat the AI as a black-box feature. It is a teammate. Name it, attribute it, show its state.
- **Don't** copy the Salesforce/HubSpot information architecture: bloated nav, 12-field forms, settings organized by team org chart. Organize by user task.
