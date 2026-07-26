---
name: seo-feedback
description: Use when the user asks for feedback on their SEO, an SEO health report, "how is my SEO", "check my SEO", "give me my SEO status", or any variant that means "audit the current SEO state of ot1-pro.com and tell me what to do." Pulls LIVE data from Google Search Console, Bing Webmaster Tools, Microsoft Clarity, Google Analytics 4, and Ahrefs via browser MCP, cross-references with technical on-page checks (canonical, sitemap, robots, llms.txt, page speed hints), and produces a prioritized report. Do not invoke for one-off keyword lookups or "how do I rank for X" questions — this skill is for the full-portfolio audit.
---

# SEO Feedback Skill for OT1-Pro

You are producing a **live, data-driven SEO health report** for `ot1-pro.com`. This is not a lecture on best practices — every claim must be backed by a number pulled from an actual dashboard or a `curl` result from prod. If you cannot verify a claim, do not make it.

## Read this first: strategic anchors that never change

Before you fire a single tool, remember the strategic reality documented in `docs/seo-progress.md`:

1. **ICP is Egyptian/GCC ecommerce brands doing $5k–$50k/mo on Instagram + WhatsApp.** Never critique the site for not ranking on enterprise/US-only queries — that's out of scope.
2. **The 30-user goal is not delivered by SEO alone.** SEO is the compounding secondary channel. Do not overstate SEO's role in commercial success.
3. **Impressions problem is real (~100–150/day).** Any report should acknowledge low volume as a KNOWN issue, not "discover" it.
4. **Ahrefs shows 381 referring domains but DR 0.** Historical Mishkah cross-links exist but haven't compounded into domain authority yet.
5. **Meta App Verification is still "Ready to Test"** for most permissions — real customers cannot self-serve OAuth. This gates conversion no matter how good SEO gets. Flag this if the user talks about "why isn't SEO converting."

If any pin looks stale, read `docs/seo-progress.md` before proceeding.

## Execution protocol

Run these five phases in order. Do not skip. Do not batch multiple dashboards into one summary — read each one, note the numbers, then move to the next.

### Phase 1 — Technical health (curl-based, ~30 seconds)

Run these curl checks against production and note results:

```bash
# 1. Sitemap serves and has expected URL count
curl -s "https://ot1-pro.com/sitemap.xml" | grep -c "<loc>"

# 2. Robots.txt still allows AI crawlers
curl -s "https://ot1-pro.com/robots.txt" | grep -i "GPTBot\|PerplexityBot\|ClaudeBot"

# 3. llms.txt still serves the canonical description
curl -s -o /dev/null -w "%{http_code}\n" "https://ot1-pro.com/llms.txt"

# 4. Canonical bug regression check — /?lang=ar MUST canonical to clean root
curl -s "https://ot1-pro.com/?lang=ar" | grep -oE '<link rel="canonical" href="[^"]+"' | head -1

# 5. Homepage HTTP status + response time
curl -o /dev/null -s -w "HTTP: %{http_code}  Time: %{time_total}s  Size: %{size_download}b\n" "https://ot1-pro.com/"

# 6. Verify our 3 verification meta tags render on prod homepage
curl -s "https://ot1-pro.com/" | grep -oE '<meta name="(google-site-verification|msvalidate\.01|ahrefs-site-verification|clarity)[^>]*>' 

# 7. Confirm the 301 redirects are still 301s (not accidentally 200s)
for u in "vs/respond-io" "vs/freshchat" "industries/education"; do
  curl -s -o /dev/null -w "%{http_code}  /$u\n" "https://ot1-pro.com/$u"
done
```

**Flag anything that isn't as expected.** Especially: canonical missing `?lang=` strip, sitemap URL count dropped >10%, any of the 3 verification meta tags missing, any 301 turned into a 200 or 500.

### Phase 2 — Google Search Console (browser MCP)

Load browser tools:

```
ToolSearch: select:mcp__claude-in-chrome__tabs_context_mcp,mcp__claude-in-chrome__tabs_create_mcp,mcp__claude-in-chrome__navigate,mcp__claude-in-chrome__get_page_text,mcp__claude-in-chrome__javascript_tool
```

Navigate to and read each of these tabs sequentially (create new tab if needed):

1. **Performance overview (last 28 days):**
   `https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fot1-pro.com%2F&num_of_days=28`
   Extract: total clicks, total impressions, average CTR, average position.

2. **Top queries by impression:**
   Same URL, click "Queries" tab. Extract the top 20 queries with impressions + position. Look for:
   - Queries at position 10-20 → these are the "one-good-nudge-away" opportunities
   - Queries with high impressions + low CTR → title/description rewrite candidates
   - New queries that appeared in the last 7 days vs the prior 21

3. **Top pages by impression:**
   Extract top 20 pages. Cross-reference against the sitemap — any page in the sitemap that has ZERO impressions in 28 days is either too new (last 7 days) or a rewrite/kill candidate.

4. **Coverage / Indexing status:**
   `https://search.google.com/search-console/index?resource_id=https%3A%2F%2Fot1-pro.com%2F`
   Extract: pages indexed vs pages excluded. Any "Crawled - currently not indexed" or "Discovered - currently not indexed" numbers matter — flag if >20% of the sitemap.

5. **Core Web Vitals:**
   `https://search.google.com/search-console/core-web-vitals?resource_id=https%3A%2F%2Fot1-pro.com%2F`
   Extract: mobile passing, mobile needs improvement, mobile poor.

### Phase 3 — Bing Webmaster Tools (browser MCP)

1. **Search performance:**
   `https://www.bing.com/webmasters/reports/searchperformance?siteUrl=https://ot1-pro.com/`
   Extract clicks, impressions, position. Bing often ranks OT1-Pro pages earlier than Google — this signal previews Google trajectory.

2. **Sitemap crawl status:**
   `https://www.bing.com/webmasters/sitemaps?siteUrl=https://ot1-pro.com/`
   Confirm URLs discovered matches (or exceeds) Google Coverage report.

### Phase 4 — Ahrefs Webmaster Tools (browser MCP)

1. **Overview:**
   `https://app.ahrefs.com/site-explorer/overview?projectId=10148820&target=ot1-pro.com%2F`
   Extract: Domain Rating, Referring domains, Organic traffic, Organic keywords.

2. **New backlinks (last 7 days):**
   From the overview → click "Backlinks" → filter to last 7 days. Any new dofollow backlink from a DR30+ domain is worth calling out by name in the report.

3. **Top competitors' organic keywords vs ours:**
   Compare against wati.io, respond.io, manychat.com. Any keyword where a competitor ranks top 10 and we rank 20+ is a rewrite target.

### Phase 5 — Microsoft Clarity + GA4 (browser MCP)

1. **Clarity dashboard:**
   `https://clarity.microsoft.com/projects/view/xs758fpq2f/dashboard`
   Extract: sessions (last 7 days), dead clicks, rage clicks, excessive scrolling pages. Report the top 3 pages by rage-click volume as UX-fix candidates.

2. **GA4 acquisition report:**
   `https://analytics.google.com/analytics/web/#/a388783609p531080583/reports/explorer?params=_u..nav%3Dmaui&ruid=lifecycle-user-acquisition-v2,business-objectives,generate-leads`
   Extract: sessions by source (Organic Search / Direct / Referral / Social). Note trial signups + conversions if visible.

## Output format

Produce a single markdown report with these sections **in this order**. Do not omit sections; if a phase had no data, say so explicitly.

```markdown
## SEO Health Report — {{today's date}}

### TL;DR
Two-sentence honest state-of-the-union. Not "everything is great" and not "everything is broken" — the actual number-backed truth.

### Direction of travel (vs last check if inferable)
- Clicks: X (prior N) → arrow direction
- Impressions: X → arrow direction
- Average position: X → arrow direction
- Referring domains: X → arrow direction
Only include this section if data supports a comparison.

### Technical health
- Sitemap: X URLs discovered, indexed status
- Canonical fix live: YES/NO
- Verification meta tags rendering: list which ones
- Robots.txt still allows AI crawlers: YES/NO
- Redirects still 301: YES/NO
- Any regressions: ...

### Search performance (Google + Bing)
- Top 5 queries by impression, with position and CTR
- Top 3 pages by impression
- Top 3 "close to page 1" queries (position 11–20) — these are the leverage points
- Top 3 "high impression, low CTR" queries — title rewrite candidates

### Backlinks & authority (Ahrefs)
- DR, referring domains, organic keywords
- Any new backlinks in last 7 days
- Top 3 competitor keywords where we're 20+ but competitor is top 10

### UX & funnel (Clarity + GA4)
- Sessions in last 7 days
- Top 3 pages with rage clicks or dead clicks
- Conversion funnel drop-off if visible

### The 3 things to do next (ranked by ROI/hour)
Each item must be:
- Specific (file path, URL, or exact action)
- Estimated effort (minutes or hours)
- Expected impact (which specific metric it should move)

### The 3 things NOT to do
Watch for hollow "publish more content" impulses. Reject them explicitly here if warranted.

### Strategic reminder
One-liner from the pins in docs/seo-progress.md that's most relevant to the current state.
```

## Guardrails

- **Never invent numbers.** If a dashboard is loading slowly or an element didn't render, say "unable to read X, retry needed" instead of estimating.
- **Never recommend RankPill, Byword, Koala, or any AI-content-farm tool.** The user explicitly ruled these out — pin #5 in the doc.
- **Never recommend rebuilding the site design.** Fix funnel bottlenecks (OAuth, payment, CTA clarity) before aesthetics.
- **Do not batch dashboards into one summary.** Read each individually. Multi-dashboard summaries lose the per-source signal.
- **If Meta App Verification is still "Ready to Test"**, always mention it in the report — no amount of SEO fixes converts if OAuth is broken for real customers.
- **Length target: 400–800 words.** Longer than that = user won't read it. Shorter = you skipped a phase.
- **After delivering the report, ask which item the user wants to execute first.** Do not auto-execute recommendations from the report unless asked.

## Reference URLs (bookmark these for future runs)

- Google Search Console: `https://search.google.com/search-console/performance/search-analytics?resource_id=https%3A%2F%2Fot1-pro.com%2F`
- Bing Webmaster Tools: `https://www.bing.com/webmasters/reports/searchperformance?siteUrl=https://ot1-pro.com/`
- Ahrefs project: `https://app.ahrefs.com/site-explorer/overview?projectId=10148820&target=ot1-pro.com%2F`
- Microsoft Clarity: `https://clarity.microsoft.com/projects/view/xs758fpq2f/dashboard`
- GA4 property: `https://analytics.google.com/analytics/web/#/a388783609p531080583/`
- Prod domain: `https://ot1-pro.com` · VPS: `root@187.77.67.94:/var/www/ot1-pro.com`
- Session-wide SEO doc: `docs/seo-progress.md` (single source of truth for prior decisions)
