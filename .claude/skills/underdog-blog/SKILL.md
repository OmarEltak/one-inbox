---
name: underdog-blog
description: Use when the user asks to write a blog post for ot1-pro.com, add a new batch to the SEO content pipeline, or wants "another blog post in that voice" / "more like the meta verification one" / "in the founder voice". Also use when the user drafts a punchy blog title in a message like "how I fired my sales team" or "fire your sales team now" — those title fragments are trigger signals. Produces posts in the underdog founder-POV style that has been proven to work for this codebase (5-20 min dwell time in Clarity, only ranked slugs in GSC). Do NOT use for generic marketing copy, product page copy, or non-blog content.
---

# Underdog Blog — writing posts that actually rank for ot1-pro.com

## Why this skill exists

Every blog post on `ot1-pro.com` that has silently ranked and generated real dwell time follows the same voice: a scrappy underdog founder telling the truth about a thing bigger, better-funded competitors won't say. The Batch 17 `meta-app-verification-2026-founder-guide` post is the reference — it's the site's only real SEO winner per the 2026-08-06 audit, and the ~100 corporate-voice posts before it flopped.

The user has explicitly asked for more posts in that voice. This skill encodes what "that voice" actually is so it stays consistent across sessions.

## When to invoke

- User says "write a blog post" / "another blog" / "more like the meta one" / "in the founder voice"
- User pastes punchy title fragments like "fire your sales team now" / "how I saved my business" / "my staff cost me $X"
- User says "add a batch" / "batch 19" / "extend the SEO cluster"
- User asks for content targeting a specific query and it's for the blog (not a landing page)

## When NOT to invoke

- Landing pages, feature pages, comparison pages — those need product-marketing voice, not blog voice
- Documentation — technical, not editorial
- Blog posts that need enterprise/formal tone (we don't have any of those, but if requested, this voice is wrong)
- Emails, ad copy, social posts

## The voice rules (non-negotiable)

Read the reference post before writing:
- `database/seeders/AiSeoBlogSeederBatch17MetaFounderCluster.php` — first-person founder guide about Meta bureaucracy
- `database/seeders/AiSeoBlogSeederBatch18SoloFounderAiCluster.php` — 5 posts in the "solo founder replaced team with AI" cluster

Every post MUST:

1. **First person, founder-POV.** Say "I" and "my business" — not "businesses should" or "companies can." The winner opens with "I built OT1-Pro… so I had to walk this path myself." Copy that energy.
2. **Underdog framing.** Frame yourself as the scrappy small operator against big-funded incumbents. Name specific competitor names (Respond.io, WATI, Intercom, Zendesk, Freshworks) and their funding when it makes the underdog point sharper.
3. **Confession or contrarian premise in the opening.** Bad: "Customer service is important." Good: "Half of you clicked this because it sounded reckless." / "I fired my two-person sales team in early 2026. Not for cause." Openings that dare the reader to keep reading.
4. **Real numbers everywhere.** Real dollar figures ($29/mo, $2,480/month, ~40% missed DMs), real percentages (14% conversion, 27% re-order rate), real time ranges (18 minutes vs 5 minutes), real product names. Vague = auto-rejection by Google's helpful-content classifier.
5. **Named tools, real prices.** OT1-Pro Starter $29, Shopify Basic $39, Meta Ads Manager $0 + ~$40/day. Never say "some AI tool" — say the specific tool. Never say "affordable" — say the exact monthly cost.
6. **Original language phrases where relevant.** Arabic error strings, Meta rejection language ("جاهز للاختبار"), Egyptian sarcasm samples. This is voice ownership no corporate SEO farm can fake.
7. **Numbered lists, tables, and H2s every 200-300 words.** The blog show template has an auto-TOC that needs 3+ H2s; aim for 8-12 H2s per post. Solid text walls die.
8. **Every post ends with the `{{CTA}}` placeholder** — never hard-code a CTA. The seeder's `ctaEn()` method swaps it in at run time.
9. **Every post internally links to ≥3 of:** the Batch 17 meta-verification winner (`/blog/meta-app-verification-2026-founder-guide`), `/pricing`, `/vs/wati`, another post in the same cluster, `/register`.
10. **Word count: 1,800-2,500.** Under 1,500 = won't rank in 2026 for anything competitive. Padded fluff = also fails.
11. **Meta title ≤ 60 chars, primary keyword in first 40. Meta description 150-160 chars, ends with the primary keyword. Excerpt: 40-60 words that stand alone as a Quick Answer.**

## The forbidden phrases (auto-reject if you catch them)

If any of these appear in a draft, delete and rewrite:

- "In today's fast-paced digital landscape"
- "Revolutionize" / "seamless" / "delve into" / "in conclusion" / "leverage" (as a verb) / "unlock the power of"
- "Businesses can benefit from..." / "Companies should consider..."
- Opening with a rhetorical question ("Have you ever wondered…?")
- "It's important to note that…" / "It's worth mentioning…"
- Generic reassurance like "Don't worry" / "You're not alone" without a specific claim
- "Various" / "several" / "many" where a real number belongs
- Corporate empathy: "We understand that customer service is challenging"

If a section sounds like RankPill, Byword, Koala, or a translated AI content farm, rewrite in the founder voice. Read it aloud — if it doesn't sound like something a scrappy founder would say to a peer, it's wrong.

## The rhetorical patterns that DO work

Copy these patterns; they earn dwell time in this niche:

- **The audit setup.** "In January 2025, I sat down and calculated the fully-loaded cost of X. The number came out to $29,760/year. In [city]. For [scope]."
- **The specific-deal-size table.** Deal size / does this work? / with a real reason per row. Readers screenshot these.
- **The confession + reversal.** "For two years I told myself the problem was management. I read Ben Horowitz. Nothing worked. The real problem was [structural cause]."
- **The two-column "old vs new" table** with real dollar amounts, not "significantly less."
- **The "I would tell myself two years ago" retrospective list.** Three items, each contrarian.
- **The severance/ethical honest paragraph** when discussing firing/replacing humans. Shows the writer isn't a sociopath, which builds trust.
- **The dangerous / DO-NOT-COPY item** at the end of a list. Signals authenticity. See Batch 18 post 4 ("The two prompts I DELETED").
- **The "single meta-rule" closer.** One sentence that governs everything above. Memorable.

## Batch seeder pattern (mandatory for shipping)

Blog posts on this codebase ship via numbered batch seeders, not PostSeeder edits. Copy the Batch 18 file as a template:

```
database/seeders/AiSeoBlogSeederBatch{N}{ClusterName}.php
```

- `N` = next available number (currently 19 is next). Check `ls database/seeders/ | grep Batch` first.
- `ClusterName` = PascalCase description of the theme (e.g., `SoloFounderAiCluster`, `MetaFounderCluster`).
- Class exposes `run()`, `ctaEn()`, and `posts()` methods.
- Every post's `content` uses `<<<'HTML' ... HTML;` nowdoc (single-quoted `'HTML'`) — this prevents `$price` in the copy from being interpolated as a PHP variable. Real bug that shipped in earlier batches.
- Post array MUST include: `title`, `slug`, `excerpt`, `meta_title`, `meta_description`, `category`, `image`, `published_at`, `created_at`, `updated_at`, `content`.
- Use `Post::updateOrCreate(['slug' => $post['slug']], $post)` so re-running the seeder updates in place.

## Blade JSON-LD escaping (regression trap)

Not directly used in the seeder, but if the blog show template ever needs structured data, always use `{!! json_encode($x) !!}` inside `<script type="application/ld+json">`, never `{{ }}`. `{{ }}` turns `"` into `&quot;` and breaks Google's parser. This bit us across every blog post on 2026-07-23.

## The publish flow (state this to the user after shipping)

Seeders do NOT auto-run on deploy. After committing the seeder:

```bash
git add database/seeders/AiSeoBlogSeederBatch{N}{Name}.php
git commit -m "feat(seo): batch {N} {theme} — {short description}"
git push origin main
# GitHub Actions auto-deploys in ~24s

# Then manually run the seeder on prod:
ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && php artisan db:seed --class='Database\\Seeders\\AiSeoBlogSeederBatch{N}{Name}' --force"
```

Then tell the user to submit each new slug in Google Search Console → URL Inspection → Request Indexing. Give them the full URLs, one per line, ready to copy-paste.

## Quality checklist before returning a draft

Run through every item before saying "done":

- [ ] Word count 1,800-2,500 per post
- [ ] Opens with a confession, contrarian claim, or specific number — not a generic statement
- [ ] First person "I" / "my business" throughout
- [ ] At least one real dollar figure per major section
- [ ] At least one named competitor with a real detail (funding, price, feature)
- [ ] 8-12 H2 headings for auto-TOC to render
- [ ] At least one table OR numbered list every ~500 words
- [ ] Meta title ≤ 60 chars, meta description 150-160 chars ending with the primary keyword
- [ ] Excerpt is 40-60 words that stand alone
- [ ] `{{CTA}}` placeholder at the end (never hard-coded CTA)
- [ ] ≥3 internal links to key pages (`/pricing`, `/vs/wati`, `/blog/meta-app-verification-2026-founder-guide`, `/register`, cluster siblings)
- [ ] Zero forbidden phrases (grep the draft: "revolutionize", "delve", "in today's", "leverage", "seamless")
- [ ] Reads out loud like a founder talking to a peer, not a content mill
- [ ] Batch seeder file compiles: `php -l database/seeders/AiSeoBlogSeederBatch{N}...php`
- [ ] Post array structure matches Batch 17/18 exactly

## One meta-rule that governs all of this

If the reader could get the same information from any other SaaS marketing blog, we've written the wrong post. The differentiator is honest specificity — real numbers from a real business, told in a real voice. Corporate SEO farms cannot fake that, which is why it's our moat as an underdog with DR 0.
