# Blog Topics — OT1-Pro Features Marketing Hasn't Covered Yet

**Captured 2026-10-08.** Audit of the actual app surface (Livewire + Services + Jobs) vs. the current marketing pages (`resources/views/pages/*.blade.php`) found 15+ real features **in production** that have no blog post explaining them to prospective customers. Each cluster below is 1-3 posts in the founder voice (per the `underdog-blog` skill — 1,800-2,500 words, first-person, concrete failure modes, 10+ H2s, internal links to pricing/vs-wati/register).

Reference post for voice + length: `database/seeders/AiSeoBlogSeederBatch17MetaFounderCluster.php` → `meta-app-verification-2026-founder-guide`.

Target: 1 batch seeder (`AiSeoBlogSeederBatch34UnderCoveredFeatures.php` or similar) with 8-12 posts.

---

## Cluster 1 — Deep Analysis (0 posts currently; biggest gap)

The dashboard has a "Deep Analysis" feature that reads 1,000-5,000 past conversations at once and answers a specific business question. Nowhere on the marketing site does this feature exist. It's one of our strongest differentiators vs ManyChat / WATI / Chatfuel — none of them do this.

- **"I let an AI read every WhatsApp message I'd sent in a year. Here's what it found about my customers."** — Deep Analysis walkthrough, show real anonymized outputs (top 10 objections, "hot leads ready to buy", "why deals died"). The founder-voice gold mine.
- **"The 5 questions I ask my AI once a week to run my business"** — one post per Deep Analysis mode (Top objections, Who's ready to buy, Win-back quiet leads, Why deals are lost, This week vs last week). Each with real example output and the exact prompt.
- **"Agent audit: using AI to review how my support team is actually doing"** — the agent_audit Deep Analysis mode. Niche but hits agencies and multi-rep brands hard.
- **"How much does it cost to analyze 10,000 contacts with AI? (Live receipt)"** — credits-economy post, shows the actual AI credit cost + wallet math. Builds pricing-page trust.

## Cluster 2 — Instagram Comment AI (0 posts; competitors charge $$$ for less)

- **"I added AI comment replies to my Instagram. Reach doubled in 11 days."** — the Instagram Comment AI feature. ManyChat/Chatfuel charge extra for this; we include it on Pro+.
- **"Public comment vs private DM: how my brand's AI decides which to send"** — explain `reply_mode` + `dm_mode` + `on_purchase_intent` strategy. Deep-link to `/settings/ai` → Comments tab.
- **"How we filter spam comments without ever showing a 'bot' reply"** — the comment classification pipeline (ClassifyCommentJob → IngestCommentJob → SendAiCommentReplyJob). Dev-adjacent but useful for IG-first brands.

## Cluster 3 — Media AI: Voice notes & image reading (0 posts; hidden gem)

Many Egyptian/Gulf customers send voice notes instead of typing. OT1-Pro transcribes those AND responds in text to the voice. Same with images.

- **"Voice-note WhatsApp customers get instant text replies now (and here's why that matters in Egypt)"** — the TranscribeAudio + ConvertAudioToOgg flow. Hyper-targeted to our market.
- **"How my AI replies to a customer's photo of a damaged product"** — the DescribeImage job. Shows a real image → extracted description → tailored reply.
- **"Running a dental clinic? Here's how the AI handles 'can you check this x-ray' DMs."** — vertical angle on image AI for medical/dental.

## Cluster 4 — Lead Scoring (0 posts; CRM-grade feature)

- **"Automatic lead scoring: how my AI tags every DM as hot / warm / cold without me lifting a finger"** — the ScoreLeadJob + LeadScoreEvent system. Opens door to "stop losing hot leads in your inbox" headlines.
- **"The 7 signals my AI watches to decide a lead is 'ready to buy'"** — list what the lead-score heuristics actually look for (purchase intent words, response time, question count, etc.).

## Cluster 5 — Sales Connectors (0 posts; zapier-adjacent crowd)

- **"Pipe every captured lead into Google Sheets automatically (no Zapier required)"** — Sales Connectors sheet integration. Zapier/Make/n8n users searching for native paths will find this.
- **"Webhook-first lead capture: push every DM-captured lead into Make / n8n / Power Automate"** — the webhook side of SalesConnectors. Technical buyers.

## Cluster 6 — AI Credit Economy (1 post currently; incomplete)

- **"How much does an AI reply actually cost? (Receipts, not estimates)"** — the ledger system. Transparency = trust.
- **"Monthly credits vs wallet credits: how OT1-Pro's credit stack works and why it beats token-based pricing"** — differentiates vs OpenAI-token-reseller tools.
- **"We refund your AI credits if the AI is down — here's the audit trail"** — REASON_REFUND_OUTAGE in the ledger. Transparency hook.

## Cluster 7 — Multi-model AI (NaraRouter) (0 posts; differentiator)

- **"Why OT1-Pro uses 5 different AI models to answer your customers (and how we switch between them)"** — NaraRouter two-chain explainer. Deep-tech but credibility-building.
- **"What happens when ChatGPT goes down and your business is on autopilot? (A story about failover)"** — real tale of a NaraRouter failover event. Dramatic + specific.

## Cluster 8 — Managed Onboarding (0 posts; concierge angle)

- **"'I don't want to click OAuth buttons.' Here's how we connect your Facebook for you."** — SuperAdmin/OnboardingRequests concierge flow. Hits non-technical SMB owners hard.
- **"We onboarded a 55-year-old dentist in 20 minutes. Here's exactly what we did."** — case study format on managed onboarding.

## Cluster 9 — Team collaboration (0 posts)

- **"How my 3-person support team shares an inbox without stepping on each other"** — role-based permissions + Quick Replies.
- **"Quick Replies: the sticky-notes feature every social-media-manager uses by 10 AM"** — the QuickReplies feature. Small but used-every-day.
- **"Multi-workspace: running 8 brands' inboxes from one OT1-Pro account"** — agency angle. Business plan upsell.

## Cluster 10 — AI config as a product (0 posts; hidden depth)

The `/settings/ai` page has 6 tabs: Sales Goal, Knowledge, Behavior, Handoff, Comments, Connectors. Each has deep configuration customers don't know exists.

- **"The 5 sales goals you can pick for your AI (and when each one wins)"** — walkthrough of `GOAL_INFO_ONLY / CAPTURE_DATA / BOOKING / ECOMMERCE / CUSTOM`.
- **"Teaching an AI your dialect: how we handle Egyptian Arabic, Khaleeji, and Levantine without re-training"** — language setting + auto-detect. Hyper-localized.
- **"24h messaging window: Meta's silent rule that costs businesses customers (and how we handle it)"** — PageSyncWindowService angle, educational + SEO on "Meta 24 hour window".
- **"Working hours: letting your AI reply after 9 PM without 'our team is away' feeling"** — working_hours config.

## Cluster 11 — Compliance / privacy (0 posts; EU/GDPR hook)

- **"Data deletion done right: how OT1-Pro honors Meta's 'delete my data' requirement"** — DataDeletionRequest flow. Legal-adjacent, trust-building.
- **"Where your customer conversations live, exactly (self-hosted on our Egypt VPS)"** — Transparency post. Differentiates vs cloud-only competitors.

## Cluster 12 — The AI Chat (operator) (0 posts; weird-cool feature)

The `/ai-chat` page lets the business OWNER chat with an AI about their own data. "Which contacts are hot?", "What are my top objections?", "Draft a win-back for Sara." It's a Deep Analysis conversational wrapper.

- **"The one AI chat window every founder should have open all day"** — the AiChat operator interface, with 5 real prompt examples that produce useful output.
- **"Stop opening 7 dashboards. Ask your AI instead."** — comparison to building reports in Metabase / Looker / spreadsheets.

---

## Internal-link map for every post in this batch

Every post MUST link to at least 3 of:
- `meta-app-verification-2026-founder-guide` (the SEO winner)
- `/pricing`
- `/vs/wati`
- `/register`
- Another post in the same cluster

Every post MUST end with the `{{CTA}}` placeholder so `run()` swaps in the current CTA.

## Publishing plan

- One batch seeder: `database/seeders/AiSeoBlogSeederBatch34UnderCoveredFeatures.php`
- 8-12 posts selected from the clusters above (prioritize Deep Analysis + Instagram Comment AI + Media AI — those are the biggest gaps vs competitors)
- Follow the `underdog-blog` skill rules for each: 1,800-2,500 words, founder voice, real failure modes, 8-12 H2s, `{{CTA}}`, meta-description 150-160 chars ending with the primary keyword
- Ship with `git push origin main` → auto-deploy → SSH + `php artisan db:seed --class='Database\\Seeders\\...' --force` → submit each slug in Google Search Console URL Inspection

## File link

Open in VS Code: `tasks/blog-topics-2026-10-under-covered-features.md`

Prod path (after this commit lands): `https://github.com/OmarEltak/one-inbox/blob/main/tasks/blog-topics-2026-10-under-covered-features.md`
