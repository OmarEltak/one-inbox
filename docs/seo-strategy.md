# SEO Strategy — Living Doc

**Owner:** Omar. **Last updated:** 2026-08-20.
**Companion to:** `docs/seo-progress.md` (historical decisions), `docs/ARCHITECTURE.md` (non-SEO pins), `CLAUDE.md` pin #11 (blog post quality bar).

This doc captures what we know, what we're doing, and what we've explicitly ruled out. **Update it every time strategy shifts** — append to the Change Log at the bottom and edit the sections above.

---

## Goal (as of 2026-08-20)

| Metric | Today | 90-day target | Multiplier |
|---|---|---|---|
| GSC impressions/day (28d avg) | 130 | **1,000** | 7.7x |
| GSC clicks/day (28d avg) | 0.7 | **20** | 28x |
| Site-wide CTR | 0.5% | 2.0% | 4x |
| AI-answer citations (ChatGPT / Perplexity / Google AI Overviews / Claude) | ~1 known (meta-app-verification post) | 15+ known | — |

**Explicit non-goal:** signed-up users. GA4 signup data is Omar-testing noise until Meta App Review clears the OAuth path (see CLAUDE.md pin #1). Track signups via GA4 *after* the first non-Omar user appears.

---

## Current state (2026-08-20)

### Traffic
- **28-day GSC:** 19 clicks, 3.65k impressions, 0.5% CTR, avg position 33.3.
- **Last-7-day GSC:** 3 clicks, 937 impressions, 0.3% CTR, avg position 22.9. (Position *improved* while impressions dropped — Google narrowed keyword eligibility, classic signal of post-change re-evaluation.)
- **Real users:** ~0. GA4 shows 102 users in 28d, all Omar-testing.

### The Aug 6 template regression (evidence-based diagnosis)

Commit `ad8fb35` on **2026-08-06 20:31** rewrote `resources/views/blog/show.blade.php` (+196 / −34 lines) — Quick Answer box, sticky sidebar, JS-generated TOC, reading progress bar. Applies to every blog post.

Daily impressions before/after (from GSC breakdown=date):

| Window | Days | Avg impr/day | Note |
|---|---|---|---|
| Pre-deploy | Jul 30 – Aug 5 | **173** | Peaks 225–246 |
| Deploy shock | Aug 6 – 9 | **62** | **-64% drop, day-of-deploy** |
| Recovery | Aug 10 – 18 | **135** | ~80% recovered, still climbing |

**Confirmed cause:** template deploy re-crawl. Google saw:
1. `$post->excerpt` rendered **3 times** on every post (`<meta description>`, header `<p>`, Quick Answer box) → duplicate content signal.
2. Global sidebar CTA ("Skip the Meta bureaucracy") on **every** post regardless of topic → boilerplate off-topic content dragging quality.
3. JS-generated TOC + anchor structure — internal-link depth only exists after JS renders.

**Not the cause:** batch 17/18 content quality (both meet pin #11 bar), Meta App Review (that's a conversion floor, not an impression driver), a Google core update (no signals of a broad update in the Aug 6-9 window).

### Top pages / queries (28-day GSC)

**Top queries by impressions:**
| Query | Impr | Clicks | Diagnosis |
|---|---|---|---|
| unified inbox for engineering managers | 216 | 1 | Title/meta not converting; page copy solid |
| conversational ai lead scoring | 49 | 0 | No dedicated landing page (opportunity) |
| whatsapp lead qualification | 45 | 0 | No dedicated landing page (opportunity) |
| intercom blog vs zendesk | 43 | 0 | Cross-competitor query (opportunity, low intent match) |
| whatsapp business multiple numbers | 42 | 0 | No dedicated landing page (opportunity) |
| whatsapp lead generation | 36 | 0 | No dedicated landing page (opportunity) |

**Top pages by 7-day impressions:**
| URL | Impr (7d) | Clicks (7d) | Status |
|---|---|---|---|
| `/blog/meta-business-suite-inbox-missing-messages-fix-2026` | **428** | 0 | Batch 17 winner by volume; title/meta bleeding |
| `/unified-inbox-for-engineering-managers` | 52 | 0 | Steady; needs title rewrite |
| `/blog/cheapest-whatsapp-business-api-providers-2026` | 47 | 0 | Steady |
| `/blog/meta-app-verification-2026-founder-guide` | 38 | 0 | The reference winner; holding position |
| `/blog/instagram-graph-api-business-verification-2026` | 25 | 0 | Batch 17, ramping |
| `/blog/whatsapp-business-api-pricing-2026-complete-breakdown` | 23 | 0 | Batch 17, ramping |
| `/blog/meta-app-review-rejection-reasons-2026-decoded` | 19 | 0 | Batch 17, ramping |

### Coverage
- **Sitemap:** 325 URLs. No `X-Robots-Tag: noindex`. Clean.
- **Pages with any impression in 28d:** 224 of 325 → **101 zero-impression pages** (~31% dead weight).
- **Indexing report:** not pulled in latest audit (open item — need to check "Crawled – not indexed" and "Discovered – not indexed" counts).

### Technical health (2026-08-19 audit)
- ✓ Canonical `/?lang=ar` strips lang param → `https://ot1-pro.com`
- ✓ AI crawlers allowed: GPTBot, ClaudeBot, PerplexityBot
- ✓ `llms.txt` serves 200
- ✓ 301 redirects intact (`/vs/respond-io`, `/vs/freshchat`, `/industries/education`)
- ✓ Verification meta: google-site-verification, ahrefs-site-verification (msvalidate.01 via DNS)
- ✓ Clarity tag `xs758fpq2f` firing
- ✗ **HeronSignal tracker MISSING from homepage** — no RUM, no funnel data. P0.

### Authority (Ahrefs, pinned baseline)
- DR: 0
- Referring domains: 381 (mostly Mishkah cross-links, not compounding into DA yet)
- Live Ahrefs KPI cards didn't render in last audit — retry needed to get current values.

---

## Active strategy (do these in order — no shipping until #1 is done)

### 1. Fix the template regression (~30 min)
Edit `resources/views/blog/show.blade.php`:

- **Remove the header `<p>` that renders `$post->excerpt`.** Keep the Quick Answer box (that's the better placement). The `<meta description>` still has it — one visible render is enough.
- **Make the sidebar CTA per-post, not global Meta boilerplate.** Either drive from `$post->category` (e.g., WhatsApp posts get a WhatsApp CTA, Meta posts get the Meta CTA) or remove the sidebar CTA entirely and let the bottom CTA carry the conversion.

Expected outcome: impressions stabilize at 180-220/day within 10 days.

### 2. Fix titles/metas on the two top-impression, zero-click pages (~1 hour)
CTR fix via post `meta_title` and `meta_description` DB fields — no template touch, no re-crawl shock.

- `/blog/meta-business-suite-inbox-missing-messages-fix-2026` (**428 impr / 0 clicks — worst offender by 4x**)
- `/blog/instagram-dms-not-showing-all-messages-fixes-2026` (198 impr in 28d / 1 click)

Target: 2-3% CTR each = +10-15 clicks/month from existing impressions.

### 3. Restore HeronSignal tracker on homepage (~15 min)
`resources/views/partials/heronsignal.blade.php` include is missing on `/`. Restore + verify `HERONSIGNAL_PUBLIC_KEY` on prod. Without RUM, we can't see what real GSC visitors do when they land.

### 4. Freeze new batch publishing until 2026-09-02 (~0 min — a discipline)
Do NOT ship batch 19-20+ until daily impressions are stable ≥180/day for 5 consecutive days. Adding more volume on a still-recovering site keeps Google in re-evaluation mode.

### 5. AI-impressions retrofit (after #1-4 are stable, ~5 hours)
Add `FAQPage` JSON-LD block with 5 real Q&As to the top 20 posts by impressions. Structure H2s as questions with ≤40-word paragraph directly under each. Track AI mentions via Ahrefs Brand Radar (already available in Ahrefs account) — check weekly.

### 6. Content expansion — batch 19 (after impressions ≥180/day, ~4-6 hours)
5 posts targeting close-to-page-1 queries GSC already surfaces:
- "conversational ai lead scoring"
- "whatsapp lead qualification"
- "whatsapp business multiple numbers"
- "whatsapp lead generation"
- Expand `/blog/cheapest-whatsapp-business-api-providers-2026` into a cluster (top-3 page, worth doubling down)

Meet CLAUDE.md pin #11 quality bar. Ship one at a time with 3-day gaps between to avoid volume shock.

### 7. Audit + noindex the 101 zero-impression pages (~1 hour)
GSC shows 224 of 325 pages have any impression. The other 101 are dead weight signaling low quality to Google's classifier. Either rewrite to pin #11 standard or add `<meta robots="noindex">` and remove from sitemap. Killing dead weight typically lifts remaining pages 10-20% in position within 6 weeks.

### 8. Batches 20-22 — sustained content growth (~12-18 hours over 6-8 weeks)
Committed on 2026-08-20 as **Option A** — the path to close the gap between step 6's 500 impr/day ceiling and the 1,000 impr/day goal.

**Cadence:** 5 posts per batch, one batch every ~14 days, one post per week within each batch. Never ship 2+ posts within 3 days of each other — the Aug 6-9 volume shock is proof that concentration hurts.

- **Batch 20** (target ship-window: 2026-09-14 → 2026-09-28): topic TBD once batch 19 GSC data comes in; likely expansion of whichever batch 19 post breaks into top-10 first.
- **Batch 21** (target ship-window: 2026-09-28 → 2026-10-12): TBD, driven by the query gap analysis after batch 20 stabilizes.
- **Batch 22** (target ship-window: 2026-10-12 → 2026-10-26): TBD.

**Gating rule for every batch:** do NOT ship the next batch until the prior batch's posts have accumulated ≥50 impressions/post average AND site-wide daily impressions have not dropped >20% vs the 7 days before batch launch. If either check fails, freeze publishing and diagnose before proceeding.

**Projected outcome** (batch 19 + 20 + 21 + 22, 90-day horizon from 2026-08-20):
- Impressions: 500 (from steps 1-7) + 300-600 (from batches 20-22 at maturity) = **~1,000-1,100/day**
- Clicks: 12 (from steps 1-7) + 8-12 (from new batches at 2% CTR) = **~20-24/day**

**Explicit non-goal:** we are NOT going to add batches 23+ during this window. If the plan works, we hit the goal at batch 22. If it doesn't work, the diagnosis matters more than more content.

---

## Do-not-do list (explicit)

- **Do not ship batches 19-24 in rapid succession.** Volume shock caused the Aug 6-9 drop. Space by ≥3 days per post.
- **Do not touch `resources/views/blog/show.blade.php` layout without a canary.** Every blog post depends on it. Next template change ships with a 3-post sample first, then rolls out only if impressions hold for 5 days.
- **Do not use RankPill, Byword, Koala, or any AI-content-farm tool.** Pin #5 in `docs/seo-progress.md`.
- **Do not chase enterprise-tier queries** (Zendesk/Intercom/Freshdesk heads). Stay in the Egypt/GCC/UK ecommerce WhatsApp+IG lane per `docs/seo-progress.md` pin #1.
- **Do not cite GA4 numbers as market signal** until first non-Omar user arrives. All current GA4 data is testing noise (see `memory/project_analytics_baseline.md`).
- **Do not redesign the homepage.** Bottleneck is not aesthetics.

---

## Strategic anchors (from `docs/seo-progress.md`, not negotiable)

1. ICP is Egyptian/GCC ecommerce brands doing $5k-$50k/mo on Instagram + WhatsApp.
2. SEO is the compounding secondary channel; not the primary path to first 30 users.
3. Impressions are the leading indicator. Signups are gated by Meta App Review completion, so impression growth ≠ signup growth until that clears.
4. DR 0 despite 381 referring domains. Mishkah cross-links exist but haven't compounded — internal link equity concentration is the biggest lever we control.

---

## Change log

Append a dated entry every time strategy shifts. Do not rewrite history; if something turned out to be wrong, note it in a new entry with `superseded:` reference.

### 2026-08-20 — Initial version
- Diagnosed Aug 6 impression drop as template regression (`ad8fb35`), not batch 17/18 content quality.
- Set active strategy to stabilize-then-expand (fix template → fix CTR → freeze publishing → then batch 19).
- Superseded prior "ship batches 20-24 + FAQ schema" recommendation from same day session — that was pattern-matching without diagnosis. See `evidence-first-diagnosis` skill in CLAUDE.md.
- Set 90-day goal: 1,000 impr/day + 20 clicks/day + 15 AI citations.

### 2026-08-20 — Committed to Option A (content-led growth path)
- After walking through the 90-day math, steps 1-7 top out at ~500 impr/day + 12 clicks/day (50-60% of goal). Considered three ways to close the gap: (A) more content batches on staggered cadence, (B) backlink outreach from DR 30+ sites, (C) both.
- **Chose Option A.** Backlink outreach requires channels/relationships not currently in place; content is fully within our control and the pin #11 quality bar is already proven to work.
- Added Step 8 (batches 20-22) with strict cadence rule: never ship 2+ posts within 3 days, freeze if impressions drop >20% vs pre-batch baseline.
- Explicit ceiling at batch 22. If we don't hit 1,000 impr/day by then, the answer is diagnosis, not more content.

### 2026-08-20 — Executed steps 1 & 2, staged step 3
**Step 1 done (template regression fix):**
- Removed duplicate `<p>{{ $post->excerpt }}</p>` from post header — kept the Quick Answer box as the single visible excerpt render.
- Removed global "Skip the Meta bureaucracy" sidebar CTA — the bottom CTA carries conversion without boilerplate off-topic content on non-Meta posts.
- File: `resources/views/blog/show.blade.php`. Bottom CTA + Quick Answer box + TOC + reading progress bar all preserved.

**Step 2 done (CTR title/meta rewrites, staged for auto-deploy):**
- `AiSeoBlogSeederBatch16HighVolume.php`: added `meta_title` / `meta_description` to the two top-impression-zero-click posts.
- One-off migration `2026_08_20_100000_ctr_fix_top_impression_posts.php` will UPDATE prod DB on next auto-deploy.
- Target titles: "Meta Business Suite Inbox Missing Messages? 6 Fixes 2026" (57ch), "Instagram DMs Not Showing All Messages? 8 Fixes 2026" (52ch).
- Target descriptions: both ~155ch, ending with primary keyword per pin #11.

**Step 3 deferred (2026-08-20):**
- HeronSignal partial IS wired in all 3 layouts. Tracker is missing from prod because `HERONSIGNAL_PUBLIC_KEY` env var is not set on prod.
- Omar chose to defer this on 2026-08-20 — not blocking the SEO plan. Consequence: no RUM/funnel data on real GSC visitors when they arrive. Revisit when we have consistent daily traffic to make the tracker worth wiring.

**Step 4 (freeze) is active until 2026-09-02** at earliest, or until daily impressions ≥180/day for 5 consecutive days — whichever is later.

**Step 5 (FAQPage JSON-LD retrofit) not executed this session** — nontrivial to build the auto-extractor for existing content. Recommend building a `resources/views/partials/faq-schema.blade.php` helper that reads a `faqs` JSON field on the post model (schema addition), then retrofits top 20 posts by adding 5 Q&As each via a data migration. Est: 3-4 hours. Batch this with the batch 19 launch prep.

**Step 6 (batch 19) blocked by step 4 freeze — do not ship before 2026-09-02.**

**Step 7 (noindex 101 dead pages) blocked** — needs the actual list of zero-impression pages from GSC (only got top 10 by impression this session). Next step: pull the full 224-page GSC report, diff against sitemap.xml (325 URLs), noindex the 101 delta. Est: 1 hour.

**Step 8 (batches 20-22) blocked by step 6.**
