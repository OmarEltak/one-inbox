# Meta Ads Brief — OT1-Pro

**Send this to your Meta marketer before they build any campaigns.** Written 2026-07-26. If a campaign was proposed before this brief was shared, ask them to re-scope against these constraints.

---

## Company & Product

**OT1-Pro** — a unified social inbox and AI sales responder for MENA storefronts. WhatsApp Business + Instagram DMs + Facebook Messenger + Telegram + Email in one shared inbox. AI auto-replies in Egyptian Arabic + English, 24/7. Pricing $8-$79/mo.

- Website: https://ot1-pro.com
- Sales WhatsApp: +20 102 636 1218
- Founder: Omar Eltak
- Currency: EGP via Paymob, USD via Paddle

---

## Campaign objective

**Conversions → Trial signup** (event: `CompleteRegistration` on ot1-pro.com/register). Do NOT run engagement or reach campaigns; only conversion campaigns.

Budget: **start $10/day**, scale winning ad sets to $30/day only after 7 days of proven CAC below $50.

---

## Ideal Customer Profile

**Egyptian and GCC small ecommerce brands doing $5k–$50k/month revenue, selling on Instagram + WhatsApp, missing overnight sales.**

Specifically:
- D2C brands (fashion, beauty, home goods, food)
- Solo dropshippers running FB/IG ads
- Small retail businesses with 1-3 employees managing WhatsApp orders
- Business owners between 22-45 years old
- Already spending money on Facebook/Instagram ads (that's the biggest predictor)

**NOT the ICP** — skip these:
- Enterprise (500+ employees)
- Marketing agencies (complex procurement, want white-label features we don't have)
- Education/schools (long procurement cycles, wrong price point)
- Government or B2B SaaS teams

---

## Geo targeting (in priority order)

- **Tier 1:** Egypt — focus on Cairo, Giza, Alexandria, Mansoura, Tanta
- **Tier 2:** Saudi Arabia — Riyadh, Jeddah, Dammam
- **Tier 3:** UAE (Dubai, Sharjah, Abu Dhabi), Kuwait, Jordan, Bahrain, Qatar

**Do not target:** worldwide, US-only, Europe. CAC will 10x and none of them will trust an unverified Meta app.

---

## Demographics

- Age: 22–45
- Gender: All
- Language: Arabic (primary), English (secondary)

---

## Interest targeting — three test ad sets

Test all three in parallel for 7 days. Kill losers, scale winners.

### Set A — Ecommerce store owners
- Small business owners
- Interested in: Shopify, WooCommerce, Salla, Zid, Bosta, Aramex, ShipBlu
- Interested in: Facebook for Business, Instagram Shopping, WhatsApp Business
- Job titles: Founder, Owner, E-commerce Manager, Store Owner

### Set B — Chatbot / automation seekers
- Interested in: ManyChat, Chatfuel, Respond.io, WATI, AiSensy, Zoko
- Job titles: Marketing Manager, Digital Marketing, Social Media Manager
- Behaviors: engaged shoppers, small business owners

### Set C — Sales/customer service pain
- Interested in: HubSpot, Zoho CRM, Intercom, Zendesk
- Job titles: Sales Manager, Customer Support Manager, Business Development

---

## Exclusions (critical, most marketers skip)

- Exclude existing customers — upload email list from OT1-Pro DB
- Exclude people who visited /pricing but didn't convert in the last 30 days (retarget them via a separate warmer campaign)
- Exclude employees of large enterprises (>500 people)
- Exclude anyone under 22

---

## Creative strategy

**Three formats to test:**

1. **Carousel (5 slides)** — screenshots of the unified inbox UI showing WhatsApp + Instagram + Messenger conversations side by side. Each slide highlights one pain point → solution.
2. **Video (30 seconds)** — screen recording of the founder managing WhatsApp + Instagram messages together in the OT1-Pro inbox. Real messages, real dialect Arabic replies.
3. **Static image** — before/after visual: 5 phones on a couch (WA + IG + Messenger + Telegram + laptop) vs. one laptop with OT1-Pro open. Emotional resonance.

**Copy — Arabic primary hook options:**

- `"بترد على 500 رسالة يوميًا؟ خليها AI ترد عنك في ثانية."`
- `"عملاءك بيراسلوك الساعة 11 بالليل. مين بيرد عليهم؟"`
- `"واتساب + إنستجرام + ماسنجر في صندوق واحد. ٨$ في الشهر."`

**Copy — English secondary hook options:**

- "Replying to 500 messages a day? Let AI do it for you in 1 second."
- "Your customers message you at 11pm. Who is replying?"
- "WhatsApp + Instagram + Messenger in one inbox. $8/mo."

**CTA button:** "Sign Up" pointing to https://ot1-pro.com/register?utm_source=meta&utm_medium=paid&utm_campaign=[campaign_name]

---

## Landing page

**Do NOT send traffic to the homepage.** Send to a dedicated Arabic landing experience — for now, use:

- **English clicks:** https://ot1-pro.com/industries/ecommerce
- **Arabic clicks:** https://ot1-pro.com/industries/ecommerce?lang=ar

Both pages were purpose-built for Meta ads: MENA pain framing, Salla/Zid integration, real dialect examples, 4-step setup, before/after metrics comparison, EGP pricing mention. If the marketer wants a dedicated `/lp/*` landing page later, that's a follow-up conversation.

---

## Meta Pixel & Conversions API (non-negotiable)

The marketer MUST set up **both** the Meta Pixel (client-side) AND the Conversions API (server-side). Post-iOS 14+, client-side pixel alone loses 30-40% of conversion tracking. If they don't know how to set up server-side CAPI, ask them "what's your typical server-side event delivery rate?" — the correct answer is "80%+."

**Events to track:**
- `PageView`
- `ViewContent` (fires on /pricing, /industries/ecommerce)
- `Lead` (fires when a customer clicks the WhatsApp CTA)
- `CompleteRegistration` (fires on successful signup)
- `Purchase` (fires when payment ships — coming with Paymob integration)

**Custom conversion to define:** "Upgrade from Free/Basic to Starter/Pro" — this is the actual money event.

---

## What NOT to let the Meta marketer do

- **No worldwide or English-speaking-countries targeting.** Kill CAC + waste money.
- **No engagement/reach campaigns.** We need paid users, not likes.
- **No ads before Meta Advanced Access confirmed live.** Meta App Verification is a 2-milestone process (see `docs/meta-app-verification-2026-founder-guide` blog post). If Advanced Access is still "Ready to Test" on any permission, real customers get "Feature unavailable" during OAuth — every conversion the marketer buys will bounce.
- **No Instagram Shopping ads or Reels lead-gen ads.** Those are for D2C physical products, not SaaS. Feed + Stories + WhatsApp Click-to-Chat ads only.
- **No promised CAC.** Anyone promising "$5 signups guaranteed" is lying. Budget for a $200-400 learning phase before knowing what works.

---

## Vetting questions to ask the marketer

Before paying them anything:

1. "How many SaaS clients have you scaled from 0 to first 100 users?" (SaaS ≠ ecom ads.)
2. "Show me a past dashboard with CAC and ROAS by ad set." (Screenshots.)
3. "Do you set up server-side Conversions API or just the client-side pixel?" (Correct answer: server-side.)
4. "What's your monthly retainer + ad spend minimum?" (Under $500/mo = too junior. Over $2000/mo = too senior for this stage.)
5. "Will you write Arabic ad copy yourself, or do I need a copywriter?"

If they can't answer 1-3 crisply, they're an ecom-ads specialist, not a SaaS-funnel specialist. Different discipline.

---

## Preflight checklist — must be TRUE before spending $1

- [ ] Meta App Advanced Access is live for `pages_show_list`, `pages_messaging`, `pages_manage_metadata`, `instagram_basic`, `instagram_manage_messages`, `business_management`. Confirm by asking a non-admin friend to try `/register` and complete a Facebook Login flow.
- [ ] Paymob checkout works end-to-end (customer can pay $8 for Basic plan without founder intervention).
- [ ] `/industries/ecommerce?lang=ar` renders correctly in Arabic RTL layout on mobile.
- [ ] Meta Pixel + Conversions API firing on all 4 test events (verify in Meta Events Manager).
- [ ] Retargeting audience set up: "Visited /pricing in last 30 days without registering."

If any of these are FALSE, do not launch ads. The marketer will lose your money on broken funnels.

---

## Reporting cadence

- **Weekly:** ad-set-level CAC, ROAS, spend, conversions. First report at day 7.
- **Bi-weekly:** creative-level performance breakdown, new creative tests planned for next 2 weeks.
- **Monthly:** full-funnel report (impressions → clicks → landing page views → registrations → activated users → paying users).

If day-7 report shows CAC > $50 on all three ad sets, pause and re-brief. Do not "keep going and see what happens" past the $200 budget.

---

**Founder contact for questions:** Omar Eltak · WhatsApp +20 102 636 1218 · it@mishkahu.com
