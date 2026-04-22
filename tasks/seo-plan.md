# SEO Traffic Growth Plan — ot1-pro.com
**Product:** One Inbox
**Goal:** Organic traffic → signups
**Started:** 2026-04-19

---

## Status Legend
- [ ] Not started
- [x] Done
- [~] In progress

---

## Phase 1 — Quick Wins (Week 1)
*Low effort, immediate impact on crawlability and SERP appearance*

- [ ] Fix robots.txt — wrong domain (oneinbox.app → ot1-pro.com)
- [ ] Fix title tags — all 4 inner pages (generic → keyword-targeted)
- [ ] Add FAQ schema (FAQPage JSON-LD) to homepage + pricing
- [ ] Set default og:image in layout (1200×630 branded PNG)
- [ ] Remove hreflang tags (no real translated content, confuses Google)
- [ ] Delete adminer.php + db.php from production public/ folder
- [ ] Submit sitemap to Google Search Console
- [ ] Verify ot1-pro.com in Google Search Console

---

## Phase 2 — Platform Landing Pages (Week 2–3)
*High-intent, medium-competition keywords. Each page = 800+ words.*

| URL | Target Keyword | Monthly Vol (est.) |
|-----|---------------|-------------------|
| `/whatsapp-inbox` | whatsapp business inbox tool | 1K–10K |
| `/instagram-dm` | instagram dm management software | 1K–10K |
| `/facebook-messenger` | facebook page inbox management | 1K–10K |
| `/telegram-inbox` | telegram business inbox | 100–1K |

**Each page must include:**
- H1 with exact keyword
- Feature list (3–5 bullets)
- Screenshot/demo placeholder
- FAQ section (3 questions)
- CTA → register

---

## Phase 3 — Competitor Comparison Pages (Month 2)
*Highest-converting SEO traffic in SaaS. Users are already comparing and ready to buy.*

| URL | Target Keyword | Notes |
|-----|---------------|-------|
| `/vs/trengo` | trengo alternative | Strong competitor in unified inbox |
| `/vs/manychat` | manychat alternative | Huge brand, many unhappy users |
| `/vs/freshchat` | freshchat alternative | Enterprise-heavy, SMB gap |
| `/vs/respond-io` | respond.io alternative | Direct WhatsApp CRM competitor |
| `/vs/tidio` | tidio alternative | E-commerce focus |

**Each page structure:**
- Honest comparison table (features, pricing, platforms)
- 2–3 paragraphs: "where we win"
- Real use cases
- CTA: "Try One Inbox free"

---

## Phase 4 — Blog Content Machine (Month 2–6)
*This is where the majority of organic traffic comes from.*

### Cluster 1: WhatsApp for Business (12 articles)
- [ ] WhatsApp CRM: The complete guide for 2026
- [ ] How to automate WhatsApp replies without losing the human touch
- [ ] Best WhatsApp CRM tools compared (2026)
- [ ] WhatsApp Business API: how to get access and set it up
- [ ] WhatsApp for real estate agents: scripts + automation guide
- [ ] WhatsApp for e-commerce: recover abandoned carts automatically
- [ ] How to manage multiple WhatsApp business numbers
- [ ] WhatsApp lead generation: 7 proven strategies
- [ ] WhatsApp vs SMS for business: which wins in 2026?
- [ ] How to set up WhatsApp chatbot without coding
- [ ] WhatsApp broadcast vs groups: which is better for sales?
- [ ] WhatsApp Business compliance: what you need to know

### Cluster 2: Instagram DM Automation (8 articles)
- [ ] How to auto-reply to Instagram DMs (step-by-step)
- [ ] Instagram lead generation with DM automation
- [ ] Best Instagram DM tools for businesses in 2026
- [ ] How to turn Instagram comments into DM conversations
- [ ] Instagram DM scripts that convert followers into customers
- [ ] Instagram vs Facebook for customer service: the data
- [ ] How to manage Instagram DMs for a large team
- [ ] Instagram Shopping + DM automation: the full funnel

### Cluster 3: Social Media Customer Service (8 articles)
- [ ] How to manage 1,000+ social messages per day
- [ ] Social media response time benchmarks by industry (2026)
- [ ] Building a social CRM from scratch: what you need
- [ ] Unified inbox vs separate apps: cost and efficiency analysis
- [ ] How to reduce social media response time by 80%
- [ ] Social media customer service: the ultimate playbook
- [ ] How to scale customer support without hiring more agents
- [ ] ROI of AI social media support: real numbers

### Cluster 4: AI Sales Automation (6 articles)
- [ ] AI sales chatbots: what actually works in 2026
- [ ] How to qualify leads automatically on WhatsApp and Instagram
- [ ] AI vs human support: when to hand off (and how)
- [ ] How to write an AI sales prompt that actually closes deals
- [ ] AI lead scoring: how it works and why you need it
- [ ] 10 ways AI can increase your social media sales

### Cluster 5: How-To Tutorials (6 articles)
- [ ] How to connect a Facebook Page to a CRM
- [ ] How to manage Telegram business messages at scale
- [ ] Social inbox setup guide: from zero to fully automated in 1 hour
- [ ] How to set up an AI sales bot for your business
- [ ] How to track social media leads in a CRM
- [ ] Team inbox setup: how to assign and manage social conversations

---

## Phase 5 — Industry Landing Pages (Month 3–4)
*Capture high-intent vertical traffic*

| URL | Target | Use Case |
|-----|--------|---------|
| `/real-estate` | whatsapp crm for real estate | Property inquiries via WhatsApp |
| `/ecommerce` | social inbox for ecommerce | Order support + cart recovery |
| `/agencies` | white label social inbox | Resell to clients |
| `/restaurants` | whatsapp ordering for restaurants | Table bookings + menu questions |
| `/education` | student inquiry management | Enrollment conversations |

---

## Keyword Priority Map

### Win Now (low competition, high intent)
- "how to manage instagram dms for business"
- "best whatsapp inbox tool"
- "unified social media inbox software"
- "facebook messenger crm"
- "ai reply to instagram comments"

### Win in 3–6 months (medium competition)
- "whatsapp business crm"
- "instagram dm management tool"
- "social media customer service software"
- "ai sales chatbot for instagram"

### Long-term targets (high competition)
- "whatsapp crm"
- "social media inbox"
- "ai chatbot for business"

---

## Competitors to Monitor
- Trengo (trengo.com) — strongest content library
- Respond.io — strong WhatsApp CRM content
- ManyChat — huge brand, lots of "alternative" searches
- Freshchat — enterprise trust signals
- Tidio — e-commerce focus

---

## Technical SEO Fixes Log
| Date | Fix | Status |
|------|-----|--------|
| 2026-04-19 | robots.txt domain fix | done |
| 2026-04-19 | Title tags (4 pages) | done |
| 2026-04-19 | FAQ schema on homepage | done |
| | FAQ schema on pricing | pending |
| | og:image default | pending |
| | Remove hreflang | pending |
| | Delete adminer.php | MANUAL — do on server |
| | GSC submission | MANUAL |

---

## Blog Infrastructure Needed
- [ ] Add `/blog` route and controller
- [ ] Create `Post` model + migration (title, slug, excerpt, content, published_at, meta_title, meta_description)
- [ ] Blog index page (`/blog`)
- [ ] Blog article page (`/blog/{slug}`)
- [ ] Add blog to sitemap
- [ ] Add blog links to footer nav
- [ ] Article JSON-LD schema (Article type)

---

## Notes
- Domain: **ot1-pro.com** (NOT oneinbox.app — that domain is wrong everywhere)
- All new pages must have unique title + description passed to `x-layouts.marketing`
- FAQ schema should use `@stack('schema')` pattern in layout
- Blog articles: 1,000–2,000 words, 1 target keyword, internal links to platform pages and pricing
