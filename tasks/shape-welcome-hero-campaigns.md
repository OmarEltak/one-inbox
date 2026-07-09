# Shape — Welcome Hero Rewrite (Campaigns-first)

**Date:** 2026-07-09
**Owner:** design/marketing
**Status:** Planning only. No code changes committed.
**Target file:** `resources/views/welcome.blade.php` (lines 6–49 = current hero section)
**Preview URL after implementation:** https://one-inbox.test

---

## Goal

Reposition the homepage hero from *"unified inbox that closes DMs"* to *"AI marketing manager you run by chat."* The inbox is table stakes — every competitor claims it. The *campaign-by-message* angle is category-creation and it's currently invisible on the marketing site.

## Current hero (what's there today)

- Eyebrow: **"The AI sales floor"**
- H1: **"Your DMs close themselves."**
- Sub: unified inbox (FB, IG, WA, TG) + AI qualifies/closes 24/7
- CTA: Start closing on autopilot / See pricing
- Right column: `partials.home-inbox-demo` — animated live inbox mockup

**Why it's underselling:** the H1 is a *feature promise* ("DMs close themselves"). Strong copy, but it lives in a category (chatbot / unified inbox) where five competitors already claim the same thing. Reader has to squint to figure out what's actually new.

## What we're changing (and what we're not)

| Element | Change? | Why |
|---|---|---|
| Eyebrow "The AI sales floor" | Yes → "The chat-driven marketing manager" | Signals the new category |
| H1 "Your DMs close themselves" | Yes → new campaign-led H1 (options below) | This is the whole reposition |
| Sub-paragraph | Yes → mention campaigns + inbox in one line | Don't drop the inbox story, subordinate it |
| Primary CTA copy | Keep "Start closing on autopilot" | It still applies. Not the fight to pick here. |
| Secondary CTA | Keep "See pricing" | ditto |
| Right column illustration | **Replace** `home-inbox-demo` with new `home-campaign-chat-demo` partial | The demo IS the pitch. Show don't tell. |
| Sections 2–9 (pull quote, how-it-works, etc.) | Unchanged for now | Scope this change to hero only. Below-fold rewrite is a separate ticket. |

## H1 options (pick one — I recommend #2)

Each has been read out loud. Each avoids em dashes and clichés. None reuse "close themselves."

**Option 1 — Command-style, direct**
> *Text your AI. Launch the campaign.*

Sharp, symmetrical, feels like a slogan. Downside: sounds a little like a Twitter bio; doesn't say what business this is for.

**Option 2 — Concrete, verb-led (recommended)**
> *Launch a marketing campaign*
> *in the time it takes to type one.*

Two lines, break on "campaign". The second line is the payoff. Reads as a promise + a demonstration in the same breath. Works in Arabic layout (`rtl`) because the payoff still lands last.

**Option 3 — Editorial**
> *The marketing team you run from your inbox.*

Editorial voice — matches the existing `Cormorant Garamond` pull-quote in section 2. Downside: "inbox" here means the AI chat, but readers will hear "customer inbox" and be confused.

**Option 4 — Category-naming**
> *Chat-driven marketing.*
> *Every campaign starts with a message.*

Explicit category creation. Best for SEO/positioning docs. Slightly cold as a hero. Save the phrase for section 2 subhead.

**Recommendation:** #2. It's the strongest promise-and-demonstration. It also implicitly still covers the inbox story (you're chatting, campaigns happen from the chat).

## Sub-paragraph (under H1)

Keep the same length as current (~30–36 words, ~2 lines). This one line is where the inbox stays alive:

> *Tell your AI what to run. It picks the audience, writes the message, and ships it across WhatsApp, Instagram, Facebook, and Telegram — then handles every reply in one inbox.*

Notes:
- Verbs first: **tell / picks / writes / ships / handles**. No adjectives.
- All four channels named explicitly (SEO signal, and reassures the "wait, does it do WhatsApp?" reader).
- "in one inbox" — that's the whole current site story, now demoted to a clause. It's still true, still visible, no longer leading.

## Eyebrow

> *The chat-driven marketing manager*

Reason: `The AI sales floor` is a vibe, not a category. `chat-driven marketing manager` is a searchable term and tells the reader in five words what the product is.

## Right-column visual (`partials/home-campaign-chat-demo`)

Replace the inbox mockup with a **live chat transcript** that reads like a real conversation between the operator and the AI. Reads top-to-bottom. Roughly 6 messages:

1. **Operator (user):** *"Launch a Ramadan promo to Cairo customers who bought in the last 90 days. Budget 500 EGP. Send Thursday morning."*
2. **AI:** *"Got it. 847 customers match. Draft below:"* → shows a product card with a mocked-up WhatsApp message.
3. **Operator:** *"Make it shorter. Add a discount code."*
4. **AI:** *"Updated. RAMADAN20 → 20% off. Scheduled for Thu 09:00. Est. reach 847, est. replies ~120."*
5. **Operator:** *"Ship it."*
6. **AI:** *"Sent. I'll handle every reply in the inbox and tag hot leads for you."*

Design notes:
- No screenshot border, no phone frame, no glass. Just a clean two-column bubble stack on a warm-tinted zinc background (`bg-[#FAF8F4]` matches section 2's palette).
- Sender labels above bubbles: `You` and `Nara` (or whatever the AI's product name is — grep for the current product name in `BuildsConversationPrompts.php`).
- The mocked WhatsApp preview inside message 2 uses the WhatsApp green (`#25D366`) sparingly — one dot, one border. Restrained color strategy.
- One motion cue only: on-scroll-into-view, the last bubble ("Sent") fades in 400ms after the rest, so the visitor feels the moment the AI ships the campaign. No looping animation, no typing indicator (looks like a cliché, hurts perf).

## What the below-fold sections need (later, not now)

Not touching these in this pass, but flagging:
- Section 3 ("How it works") currently has 3 steps: Connect / Train / Sleep. If the hero leads with campaigns, step 3 "Sleep" reads weird next to it. Should probably become **Connect / Train / Command** in a follow-up.
- Section 7 ("vs. The alternatives") — the "Generative AI sales agent" row is fine, but a "Chat-driven campaign launcher" row would make ManyChat and Trengo look worse. Add in follow-up.

## Preview instructions

Once implementation lands (separate PR):

1. Pull the branch, `composer install && npm ci && npm run build`.
2. Boot Herd — site is at **https://one-inbox.test**.
3. Homepage is the shape target. Compare against `main` in a second tab or use `?ref=` param.
4. Read the hero aloud in English. Then swap locale to `ar` (query string or session) and read the Arabic version. Line break on "campaign" should still land correctly in RTL.
5. Check the chat transcript animation on scroll — should feel instant, not slow.

## Open questions (need product owner input)

1. **What is the AI's name?** The transcript labels it. If it's "Nara" (per `NaraRouter*` in the codebase), use that. If there's a customer-facing brand name, use that instead.
2. **Do we mention "AI Campaign Manager" as a product noun** anywhere on the homepage after the hero? If yes, this becomes a proper category term across the site. If no, the phrase lives only in the eyebrow.
3. **Arabic H1** — the "in the time it takes to type one" pun doesn't translate directly. Suggested Arabic version:
   > *أطلق حملتك التسويقية*
   > *بسرعة كتابة رسالة واحدة.*
   Confirm with a native speaker before shipping.

## Success metric

If this hero rewrite works, we expect (within 30 days of deploy):
- CTR from home → `/ai-campaign-manager` landing (new page) > 8% of homepage sessions
- Bounce rate on home unchanged or lower
- `signups.utm_source=home_hero` up meaningfully

If bounce goes up or signups drop, revert. The current H1 is strong; we don't ship a weaker one just because it's newer.
