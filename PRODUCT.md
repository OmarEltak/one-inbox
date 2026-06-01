# Product

## Register

product-and-brand

> This project carries two registers in parallel. The logged-in app (inbox, dashboard, campaigns, super-admin) is `product`: design serves the workflow. The marketing site (landing, `/vs/*`, `/industries/*`, blog, pricing) is `brand`: design IS the pitch. Treat the two as a coherent system with surface-specific behavior, not two visual languages. When unclear which applies to a given task, default to `product` for any authenticated route and `brand` for any anonymous-accessible route.

## Brand

**Canonical name:** **OT1-Pro** (lowercase `ot1-pro` in URLs, capitalized **OT1-Pro** in prose, **OT1 Pro** acceptable in spoken/UI display where the hyphen looks awkward).

**Legacy name to migrate:** "One Inbox" appears throughout views, copy, SEO meta, and the open-graph site name. This is a working title from early development. Production domain is `ot1-pro.com`. New copy should use OT1-Pro; existing copy can be migrated incrementally but new screens must not introduce "One Inbox" as the brand name.

**Tagline (canonical, use on home + open graph):**
> **OT1-Pro. Your AI sales floor. Open 24/7.**

**Headline alternates (rotate by surface):**
- Hero (home): *Every DM closes itself.*
- Pricing: *One subscription. Every channel. No missed sales.*
- Industries: *Built for [Industry]. Trained on how you actually sell.*
- vs/competitor: *What [Competitor] charges for, OT1-Pro automates.*

**One-line elevator:**
> OT1-Pro is the AI sales floor for agencies and operators running DM-driven businesses. Plug in Facebook, Instagram, WhatsApp, Telegram, and email. Our AI qualifies leads, handles objections, and pushes conversations toward a close while you're asleep. You wake up to deals, not a backlog.

## Users

### Primary: Agency operator

Runs an agency that manages social-DM sales for multiple client brands. Day-to-day reality:

- 5 to 50 client pages across 4+ channels (FB, IG, WhatsApp, Telegram, email).
- Multiple seats: account managers, junior responders, the founder. Permissions matter.
- Reports to clients monthly: "here's what we did for you, here's what closed."
- Client churn = death. Every missed DM is a client-trust event, not just a lost sale.
- Likely operating from Egypt, Gulf, Southeast Asia, or LATAM. English is fluent but not necessarily native. Arabic, Spanish, and other RTL/non-Latin scripts are first-class concerns.
- Buys for: leverage (handle 10x the volume per head), trust (clients can see the system working), and defensibility (the AI improves the more it handles, locking out competitors).

### Secondary: SMB owner-operator and indie creator

Same job-to-be-done at smaller scale. Single account, single page or two, doing everything themselves. Buys for time-back, not leverage. Designs that work for agency operators almost always work for them; the reverse is not true. Default to agency.

### Anti-persona

- Enterprise BPO buying for thousands of seats. We are not Salesforce. They will ask for SSO, audit logs, and a 90-day procurement cycle. Politely decline.
- Hobbyist running a Discord. Different channel, different motion.
- Anyone whose primary need is internal team chat (Slack/Teams replacement). Wrong product.

## Product Purpose

**The strategic frame:** *AI that closes deals while you sleep.*

OT1-Pro exists because every business that sells through DMs is leaving money on the table at 11pm and on Sunday morning. Humans can't be in every inbox at every hour, can't speak six languages, can't qualify a lead the same way 400 times in a row. The AI can. Every design decision answers: **does this let the AI do more of the work, while the human stays in command?**

**What success looks like:**
- An agency operator goes from managing 8 client accounts manually to managing 40 with the same team.
- The AI handles 70%+ of inbound conversations end-to-end. The human only touches the hot/complex/converted-pending tier.
- The dashboard tells the agency owner — at a glance, on a phone, in 10 seconds — which clients are winning this week.

**What success does NOT look like:**
- A faster shared inbox. Front and Intercom already do that.
- A chatbot builder with drag-and-drop flows. ManyChat already does that, badly.
- A CRM. Salesforce already does that, expensively.

## Brand Personality

**Three words:** **Confident. Ambitious. Futurist.**

The voice of a senior operator who has seen the inside of a hundred ad-spend funnels and knows exactly what closes and what doesn't. Not loud. Not desperate. Pulls receipts.

**Tone calibration by surface:**
- **Product UI copy:** terse, precise, never apologetic. "5 messages need a human." not "Hey there! You have a few messages that might need your attention 😊"
- **Marketing copy:** specific over clever. Numbers, named scenarios, real verbs. "Cut response time from 4 hours to 90 seconds" beats "Reply faster than ever."
- **Empty states and onboarding:** assume the user is capable. Skip "tooltips for the obvious." Show the next concrete action.
- **AI-generated replies (the AI's own voice when it speaks to your customers):** matches *your* brand voice, not OT1-Pro's. The AI is invisible by design — the customer should feel they're talking to a sharp human at your company, not a tool.

**No-go voice notes:**
- No emojis in product chrome. (User-content emojis pass through; UI doesn't generate them.)
- No exclamation points in copy. None.
- No "we're so excited to announce" startup-blog voice.
- No "fellow founders" peer-coding language.
- No "revolutionary AI-powered cutting-edge platform" stack of empty modifiers.

## Anti-references

PRODUCT.md's anti-references become DESIGN.md's "Don'ts" verbatim. These are the looks and feels OT1-Pro **must not** resemble:

### 1. The ManyChat / ChatFuel / GoHighLevel lane (conversion-bro chatbot SaaS)

What this lane looks like, and what OT1-Pro avoids:
- Cartoon mascots, illustrated characters, "your AI buddy" anthropomorphism.
- Hero-metric template: giant number, small label, gradient accent ("⚡ 10x your conversions!").
- Floating-screenshot collages with arrow callouts.
- Avalanche of testimonial avatars in a 3x4 grid.
- Neon gradients on every CTA. Pulsing buttons. Animated underlines on every link.
- "🔥 LIMITED TIME 🔥" countdown banners.
- The dollar-sign-emoji vibe.

Why this matters: agencies show OT1-Pro to their clients. The moment the dashboard looks like a 2019 affiliate marketer's funnel, the agency loses credibility. We are the *operator's* tool, not the affiliate's.

### 2. The Salesforce / HubSpot enterprise lane (CRM circa 2014)

What this lane looks like, and what OT1-Pro avoids:
- Identical card grids. Same icon + heading + 2 lines of description, repeated nine times.
- Bloated top nav with 8 dropdowns and a "More" overflow.
- Modal on top of modal on top of modal. Tab-and-modal soup.
- Forms with 12 fields visible at once, none of them required to actually do anything.
- "Enterprise" badges. "Trusted by Fortune 500" logos that mean nothing for an agency operator.
- Settings pages organized by internal team org chart, not by user task.

Why this matters: the agency operator left their corporate job to avoid this. If OT1-Pro feels like Salesforce, it loses the persona's emotional reason for using us.

### 3. (Universal craft bans, inherited from the impeccable design system)

These are absolute. Apply regardless of register:
- Gradient text (`bg-clip-text` with a multi-stop gradient).
- Glassmorphism as decorative default. Blurs and translucent cards used for ambience, not function.
- Side-stripe borders (`border-left: 4px solid <accent>` on cards or list items).
- Hero-metric template (big number + small label + supporting stats + gradient accent).
- Identical card grids (icon + heading + paragraph, repeated endlessly).
- Modal as first thought. Exhaust inline / progressive alternatives first.
- Em dashes. (Use commas, colons, semicolons, periods, or parentheses.)

The current site violates several of these in `welcome.blade.php` and `app.css`. DESIGN.md documents the gap; subsequent design work should close it.

## Positive references

What OT1-Pro should feel adjacent to (these become positive references in DESIGN.md too):

- **Stripe**: premium-and-clinical. Restrained color, technical credibility through density and precision. Marketing and product feel intentional and unified. Specifically: the way Stripe documentation, the dashboard, and the marketing site share one visual language without feeling like the same page.
- **Intercom / Front**: conversation-centric inbox tools. Three-pane layouts that respect the operator's spatial memory. The conversation is always the protagonist; chrome stays out of the way.

Honorable mentions for tone (not visual):
- **Linear's writing voice** for product copy. Direct, capable, no-nonsense, never cute.
- **Pitch and Notion** for the way settings and admin areas refuse to feel like settings and admin.

## Design Principles

Five principles, ordered by load-bearing weight. When two principles conflict on a design decision, the higher-numbered one yields.

### 1. The AI is a teammate, not a feature

The AI sales responder is the most valuable thing OT1-Pro does. Treat it as a named, attributed, accountable participant in every conversation — not as a black box behind a settings page. This shows up in design as:

- Every AI-sent message is visually attributed ("Replied by AI · 2m ago · 96% confidence"), never silently masquerading as a human.
- The AI has presence in the sidebar (AI Chat, AI Settings, the green/red dot on AI Settings that shows whether it's currently on duty).
- AI status is a first-class system indicator, like internet connectivity. Always visible, never buried.
- Settings for the AI use the language of *training a coworker* (tone, knowledge, escalation rules), not *configuring a model* (temperature, prompt template).

### 2. Per-client clarity over consolidated efficiency

The agency operator's worst-case error is sending Brand A's message to Brand B's customer. Every screen must answer, without effort: *whose conversation is this, on which channel, for which brand?* This shows up as:

- Persistent per-page identity (channel color + page name chip) on every conversation, every notification, every export.
- Multi-tenant team switching as a top-bar primitive, not a settings page item.
- Per-client analytics that can be filtered, exported, and white-labeled for a client deliverable.
- No "all clients aggregated" view ever shown to a non-admin without an explicit toggle.

### 3. Density without claustrophobia

Power users live in OT1-Pro for 4+ hours a day. Optimize for the keyboard, the scan, and the repeat task. But never let density tip into noise.

- Compact tables and inbox lists, with deliberate row-height (no 64px chat-row monsters).
- Type that holds its own at 12-13px (Cairo handles this well at the cost of slightly larger x-height; embrace it).
- Hover and focus states do work, decorative effects do not. Every transition has a job.
- The keyboard (`⌘K`, `j`/`k` to navigate, `r` to reply) is a first-class interaction, not a power-user easter egg.

### 4. Trust through restraint, not through decoration

This is the principle the current visual most needs to internalize. Agencies show OT1-Pro to paying clients. Decorative glassmorphism, floating gradient blobs, and purple-to-blue gradient text read as *amateur tool* to a Fortune-500-brand-buying agency client. Pull toward Stripe-grade restraint:

- Color is meaningful (channel identity, lead status, AI state), never decorative.
- Motion serves feedback (hover, loading, transition), never atmosphere.
- The brand surface and the product surface share one visual vocabulary; the brand is allowed slightly more breathing room and slightly larger type, but not a different palette or different motion energy.

### 5. RTL is not a translation, it is a layout

OT1-Pro serves Arabic, German, Spanish, and English today. Arabic is RTL. The product must not look like an LTR product with mirrored CSS hacks. Specifically:

- Spacing tokens use logical properties (`padding-inline-start`, not `padding-left`).
- Icons that imply direction (arrows, chevrons) flip in RTL contexts.
- Numerals stay LTR even inside RTL prose; this is correct Arabic typography.
- Test every new screen in both directions before considering it done.

## Accessibility & Inclusion

**Floor: WCAG 2.2 AA.** Specifically:

- Body text contrast: 4.5:1 minimum against background. The current `text-white/40` (≈ rgba(255,255,255,0.40)) against the `#0A0A0F` body fails this for body copy; raise to `white/60` minimum for sub-headlines, `white/75` for body. Captions only may sit at `white/50`.
- Interactive elements: 3:1 contrast for the visible boundary (border, focus ring, icon). The current `border-white/[0.06]` and `border-white/[0.07]` fail this; promote interactive borders to `white/15` at minimum.
- Focus state: visible 2px ring with 2px offset on every interactive element, including the custom `aio-btn-primary` and the inbox row clickable areas. Cannot rely on hover alone.
- Keyboard reachability: every action available via mouse must be available via keyboard. Test the inbox, the campaign wizard, and the AI settings flow with the mouse unplugged.

**RTL-first:**

- Arabic is a primary language for the target market. Every layout must work in `dir="rtl"` without breaking.
- Logical CSS properties (`inline-start` / `inline-end`) instead of `left` / `right` in all new code. The legacy custom CSS in `app.css` has some `[dir="rtl"]` overrides; new components should not need them.
- Mirrored icons for directional cues (back, forward, expand-toward-content).

**Reduced motion:**

- Respect `prefers-reduced-motion: reduce` for every animation. The marketing site's `animate-fade-in-up`, `animate-float`, `animate-gradient-shift`, and shimmer effects must all disable cleanly. Currently none of them do; this is a real bug to fix.
- Reduced-motion users still get state transitions (color change on hover, opacity change on focus); they don't get scene-setting animation.

**Color-blindness:**

- Lead temperature is currently color-coded only (hot=red, warm=orange, cold=indigo, converted=green). Add a secondary cue (the `aio-badge::before` dot is good; pair with a text label like "Hot lead" rather than just the color chip).
- Channel identity (FB blue, IG pink, WA green, TG cyan) is reinforced by the channel initials chip — keep this redundancy.

**Multilingual support:**

- Four languages today: `en`, `ar`, `de`, `es`. German and Spanish have longer word lengths than English; design with 30% text expansion as the baseline.
- Cairo (the body font) supports Latin and Arabic scripts natively. Verify Cyrillic / CJK coverage if those markets become priorities.

## Brand catchphrases (the catchy phrase library)

This section exists because PRODUCT.md is the load-bearing source for marketing copy. Pulled from the [copywriting skill]'s formulas (outcome-without-painpoint, never-X-again, category-for-audience, question-as-headline) and the [marketing-psychology skill]'s loss-aversion + specificity principles.

### Hero candidates (rotate or A/B test)

1. **Every DM closes itself.** *(specificity + ownership transfer; pairs with hero illustration of conversations resolving on their own)*
2. **Never miss a sale at 2am again.** *(loss aversion + specific moment + hyperbolic discounting reversal)*
3. **The AI sales floor. Open 24/7.** *(category creation: "AI sales floor" is the new shelf we sit on)*
4. **Stop running an inbox. Start running a sales floor.** *(reframing: inbox is the old job, sales floor is the new one)*
5. **Sleep through the close.** *(short, ownable, slightly bold — for billboard / paid social)*

### Subhead pairings

- After "Every DM closes itself": *Plug in Facebook, Instagram, WhatsApp, Telegram, and email. Our AI qualifies, objects, and closes — in any language, 24/7.*
- After "Never miss a sale at 2am again": *OT1-Pro replies in 90 seconds. Across every channel. In your customer's language. Whether you're awake or not.*
- After "The AI sales floor. Open 24/7.": *One inbox. Every channel. An AI sales rep who never sleeps, never forgets, never misses a follow-up.*

### CTAs (action verb + what they get + qualifier)

- **Start closing on autopilot** *(primary CTA on home; outcome-oriented)*
- **See it close a real DM** *(secondary; specific, demo-routing)*
- **Connect your first inbox** *(activation CTA inside the product; concrete first step)*
- **Watch the AI handle 5 conversations** *(landing variant; "watch" = low commitment, "5" = specific)*

Weak CTAs to avoid (per copywriting skill): "Get Started", "Sign Up", "Learn More", "Submit". The current site uses "Get Started Free" — acceptable but generic; promote to "Start closing on autopilot — free for 14 days" on hero.

### Comparison-page openings (for `/vs/*` pages)

Pattern: *"What [Competitor] charges for, OT1-Pro automates."*

- *What ManyChat makes you build, OT1-Pro builds itself.* (vs ManyChat — they sell a flow-builder; we sell an AI that doesn't need flows)
- *What Trengo bills per seat, OT1-Pro bills per business.* (vs Trengo — they're per-seat; reframe to per-team)
- *What Freshchat treats as a ticket, OT1-Pro treats as a deal.* (vs Freshchat — they're support-shaped; we're sales-shaped)
- *What Respond.io routes, OT1-Pro replies.* (vs Respond.io — they're a router; we're an actor)
- *What Tidio bots, OT1-Pro converses.* (vs Tidio — they automate scripts; we negotiate)

### Industry-page hooks (for `/industries/*` pages)

Pattern: *"Built for [Industry]. Trained on how you actually sell."*

- **Real Estate**: *Every property inquiry, qualified by your AI agent before you ever pick up the phone.*
- **E-commerce**: *Cart questions, return objections, upsell openings — handled while you ship.*
- **Agencies**: *Run 40 client inboxes with the headcount you have today.*
- **Restaurants**: *Reservation confirmations, allergy questions, group bookings — answered in 90 seconds.*
- **Education**: *Course inquiries answered, doubts addressed, enrollments closed — across every timezone.*

### One-liner social proof formulas (when real metrics exist)

- *"Cut response time from [X hours] to [Y minutes] across [Z channels]."*
- *"Handled [N] conversations last month. [M%] without human touch."*
- *"Closed [$X] in deals through DMs we didn't open."*

These are templates. Use only with real numbers. Per the copywriting skill: fabricated stats erode trust and create legal liability.

## Notes for future PRODUCT.md updates

- Capture real customer language from sales calls, support tickets, and Twitter mentions. Add to a `## Customer Language` section when there's a corpus to mine.
- Once pricing is finalized (currently Free/$0, Starter/$29, Pro/$79, Enterprise/Custom per `tasks/launch-plan.md`), add to a `## Pricing positioning` section with the anchoring and good-better-best rationale.
- Once there are named launch customers, add a `## Proof points` section with logos, metrics, and quotes.
