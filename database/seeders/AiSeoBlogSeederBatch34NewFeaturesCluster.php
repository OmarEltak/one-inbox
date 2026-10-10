<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 34 — New-Features ROI Cluster (Oct 2026).
 *
 * Strategy: 5 shipped features × EN + AR founder-voice posts.
 *   1. Deep Analysis plain-language + 24h 0-credit cache
 *   2. AI sales playbook (Rule #0 + objection handling)
 *   3. Business Portfolio Page-visibility fix
 *   4. WhatsApp Embedded Signup + history import
 *   5. Sheets connectors + transparent credits + bulk throttle
 * Each post links the winner post + /pricing + /vs/wati + siblings.
 */
class AiSeoBlogSeederBatch34NewFeaturesCluster extends Seeder
{
    public function run(): void
    {
        foreach ($this->posts() as $post) {
            $cta = ($post['language'] ?? 'en') === 'ar' ? $this->ctaAr() : $this->ctaEn();
            $post['content'] = str_replace('{{CTA}}', $cta, $post['content']);
            Post::updateOrCreate(['slug' => $post['slug']], $post);
        }
    }

    private function ctaEn(): string
    {
        return <<<'HTML'
<h2>Stop losing the leads you already earned</h2>
<p>OT1-Pro runs your follow-up, analysis, and AI replies in one inbox — WhatsApp, Instagram, Messenger, Telegram, and email, in Arabic or English, scored by lead quality, with every AI credit receipted in a transparent ledger. Free plan, no credit card.</p>
<p><a href="https://ot1-pro.com/register"><strong>Start free →</strong></a> · <a href="https://ot1-pro.com/sales-follow-up-automation">Sales follow-up automation</a> · <a href="https://ot1-pro.com/lead-follow-up-software">Lead follow-up software</a> · <a href="https://ot1-pro.com/pricing">Pricing</a> · <a href="https://ot1-pro.com/vs/wati">vs WATI</a> · <a href="https://wa.me/201026361218">Talk to the founder on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب النظام اللي بيقفل البيعات بدالك</h2>
<p>OT1-Pro بيدير المتابعة والتحليل والردود في صندوق واحد — واتساب وانستجرام وماسنجر وتيليجرام وإيميل، بالعربي أو الإنجليزي، مع سكور لكل عميل وكشف شفاف لكل كريديت. باقة مجانية، بدون بطاقة ائتمان.</p>
<p><a href="https://ot1-pro.com/register"><strong>ابدأ مجاناً ←</strong></a> · <a href="https://ot1-pro.com/pricing">الأسعار</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">كلّمني على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ── 1. ai-deep-analysis-3000-contacts-0-credit-rerun (en) ──
            [
                'title'   => 'AI Deep Analysis Contacts: I Read 3,000 in Plain Words',
                'slug'    => 'ai-deep-analysis-3000-contacts-0-credit-rerun',
                'excerpt' => 'Type \'read the 3000 contacts and analyze them\' and OT1-Pro\'s detector fires on the analysis verb plus cohort noun plus a scale signal, then runs customer_themes or agent_audit over up to 1,000 contacts, caches the result for 24 hours at zero credits, and pings your browser via Reverb when the DeepAnalysisCompleted report is ready.',
                'content' => <<<'HTMLP1'
<p><strong>I built OT1-Pro because my own sales inbox had 3,000 contacts and I had no idea what any of them wanted.</strong> 3,000 rows, tags everywhere, half the chats in Egyptian Arabic, follow-ups slipping, me scrolling like an archaeologist. One night I typed the sentence a customer would later type into my own product: "read the 3000 contacts and analyze them". That sentence became a shipped feature — a run that reads up to ~1,000 contacts, writes findings in plain language, caches for 24 hours at 0 credits, and pings the browser when done. This is how it works, what it costs, and the three expensive bugs that shaped it.</p>

<p>If you run sales over WhatsApp and Messenger, the surrounding pain is familiar. Getting connected is its own war — I wrote the full history in <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta App Verification 2026: A Founder's Guide</a>. Getting the AI to follow up well is another, covered in <a href="https://ot1-pro.com/sales-follow-up-automation">our sales follow-up automation guide</a> and <a href="https://ot1-pro.com/lead-follow-up-software">lead follow-up software breakdown</a>. This post is the next layer: once messages flow, how do you <em>understand</em> thousands of contacts without re-reading them at full price every day?</p>

<h2>I typed "read the 3000 contacts and analyze them" into my own product</h2>

<p>The feature started as a support ticket. A customer had imported roughly 3,000 contacts from two old spreadsheets and three months of Messenger chats, and he wrote to the AI chat inside OT1-Pro: "read the 3000 contacts and analyze them". No menu item, no reports page — just plain language, the way you would ask a sharp sales manager.</p>

<p>My first version did nothing with that sentence — treated it as normal chat and produced a confident generic paragraph about "diversifying outreach". Useless. The data for a real answer sat in his team database: tags, histories, timestamps, notes. The AI just had no bridge between a casual sentence and a cohort-level job.</p>

<p>So I built the bridge. Today, typing "read the 3000 contacts and analyze them", "analyze all my leads", or "audit every conversation from last month" fires a detector, shows a confirmation card, and one click dispatches a background run. Plain-language themes, objection counts, next actions — not a CSV dump. Everything described here is live in production, not a roadmap sketch.</p>

<h2>How the detector actually fires (and why the thresholds exist)</h2>

<p>The rule is deliberately narrow — easy to trigger by talking normally, nearly impossible to trigger by accident. It fires only when three things hold at once:</p>

<ol>
<li><strong>An analysis verb is present.</strong> Words like analyze, audit, review, summarize, breakdown, report, insights, themes, patterns. "Read" alone is not enough — "read the 3000 contacts" does not fire, but "read the 3000 contacts and analyze them" does, because the second clause carries the verb.</li>
<li><strong>A cohort noun is present.</strong> Words like contacts, leads, customers, conversations, chats, pipeline, subscribers. This stops "analyze this reply" from launching a 1,000-contact job when the user meant one message.</li>
<li><strong>A scale signal is present.</strong> Either an explicit number of 20 or more ("analyze my 300 contacts"), or a universal quantifier ("all", "every", "entire", "whole"). Under 20 contacts the normal AI reply path handles it inline; at 20 and above the system offers the deep run.</li>
</ol>

<p>Why 20? I tested 10, 20, and 50 against three months of AI-chat logs. At 10, the detector fired on questions like "analyze these 12 replies" — jobs that finish in seconds inline and should never touch the queue. At 50, real requests like "analyze my 30 expo leads" slipped through and got weak inline answers. Twenty was the sweet spot. The all/every branch covers the most common phrasing, which has no number: "analyze all my contacts".</p>

<p>One more guardrail: the detector only fires in the team's internal AI chat, never in a customer-facing conversation. A customer typing "analyze all your products" to the sales bot will never launch a cohort job.</p>

<h2>What happens after detection: modes, scope, and the 1000-contact cap</h2>

<p>When detection fires, the user sees a confirmation card — not an instant charge — naming cohort size, mode, and credit cost. Two modes ship:</p>

<ol>
<li><strong>customer_themes.</strong> Top objections, buying signals, language mix, dead-lead patterns, three next actions. The mode the "read the 3000 contacts" customer wanted.</li>
<li><strong>agent_audit.</strong> Response-time patterns, follow-up gaps, chats that died after a price question, agents that convert versus stall. If customer_themes asks "what do buyers want?", agent_audit asks "where does our process leak?"</li>
</ol>

<p>Both modes run as queued background jobs — fan out in batches, call the AI chain per batch, reduce into one report. Each run covers up to ~1,000 contacts; a 3,000-cohort analyzes the 1,000 most recently active first, window stated in the header. The cap keeps queue memory flat, the NaraRouter text chain inside budget, and the report readable. Bigger cohorts run one analysis per segment ("my 800 expo leads", then "my 900 website leads") and get better answers than one giant run.</p>

<p>For teams comparing tools, this is where OT1-Pro diverges from pure pipeline products — a classic <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs WATI</a> comparison covers channels and broadcast pricing, while deep cohort analysis bets the value is in re-reading contacts cheaply every week. If objections are your bottleneck, the sibling <a href="https://ot1-pro.com/blog/ai-sales-agent-objection-handling-playbook">AI Sales Agent Objection Handling Playbook</a> pairs well with the customer_themes report.</p>

<h2>The credit model: DeepAnalysisService charges on dispatch, except cache hits</h2>

<p>Here is the billing rule, exactly as shipped: <strong>DeepAnalysisService charges the team on dispatch, except on cache hits, which cost 0 credits.</strong> Dispatch-time, not completion-time — the AI calls start within seconds of dispatch, so charging at completion would let failed jobs look free while still burning quota.</p>

<p>Concrete math from production: a ~1,000-contact customer_themes run fans out to ~11 AI calls (10 batch + 1 reduce), ~22 credits — roughly $4.40 of a <a href="https://ot1-pro.com/pricing">$79/mo Pro plan</a> credit pool. A single user double-clicking confirm used to dispatch two identical runs: 22 wasted calls, ~$8.80 of credit value gone, two identical reports a minute apart. Worse, I measured one session with 4 identical paid retries during a provider outage — 44 wasted calls, ~$17.60 burned for zero new information. Those numbers forced the lock and the cache.</p>

<p>Failed runs do not silently eat credits. If every provider is down and the job throws AiAllProvidersUnavailable, the run is marked failed and the retry path checks the failure record first — no charge for retrying the identical cohort. Quota and outage failures throw specific exceptions the retry policy understands; anything else returns empty rather than charging for garbage.</p>

<h2>The 24-hour 0-credit cache: sha256 of the canonical-sorted filter</h2>

<p>The cache is the feature customers feel. Analyzed a cohort in the last 24 hours with nothing changed? Re-running it costs 0 credits and returns in under a second:</p>

<ol>
<li><strong>Canonicalize the cohort filter.</strong> Team id, mode, tags, date window, search string, and contact cap are serialized with keys sorted and values normalized — tags lowercased and sorted, dates truncated to the minute. "Tags: VIP, expo" and "tags: expo, vip" canonicalize identically. Without this, two logically identical requests hash differently and the cache never hits.</li>
<li><strong>Hash it.</strong> The canonical string is hashed with sha256, scoped to team + mode + hash — Team A's "expo leads" never serves Team B, and a customer_themes result never masquerades as agent_audit.</li>
<li><strong>24-hour window.</strong> Younger than 24 hours is a hit: 0 credits, instant serve. Older is a miss: full charge. The window is short on purpose — cohorts go stale fast, and I would rather recharge for fresh data than let a team act on dead objections.</li>
</ol>

<p>The canonical-sorting detail cost me a debugging afternoon. The first implementation hashed the raw request array, so key order mattered: the confirmation card built the filter as {tags, dates, mode} while the re-run link built it as {mode, tags, dates}, and the hashes never matched. Cache hit rate was 0% for a week. Sorting the keys before hashing took the hit rate from 0% to ~60% overnight for daily-active teams.</p>

<p>Dollar math on the cache: a team that checks the same "all leads" analysis every morning — fresh run Monday ($4.40 of credit value), cached re-reads Tuesday through Sunday at 0 credits — spends 22 credits for the week instead of 154. On the $79/mo Pro pool, that is the difference between analysis being a daily habit and a luxury.</p>

<h2>The emerald pill: what a cached re-run looks like</h2>

<p>A cache hit never pretends to be fresh data. The message carries an emerald-green "cached · 0 credits" pill, the original run timestamp, and the cohort definition ("customer_themes · 1,000 contacts · tags: expo, vip · run 2026-10-08 09:14 UTC"). Below sits a "re-run with fresh data" link that re-dispatches with force_fresh=true — full charge, fresh read, new cache row. Stale data is fine when labeled; stale data pretending to be fresh kills trust.</p>

<p>I tested three versions of this UI. Version one had no pill — just the report. Customers assumed it was fresh, then complained the numbers missed yesterday's import. Version two had a gray "cached" note in small text. Nobody read it. Version three — emerald pill plus timestamp plus explicit re-run link — dropped cache complaints to zero. Green means "this saved you money", the timestamp says how old it is, and the link means "fresh is one click away".</p>

<p>The force_fresh=true parameter bypasses both cache lookup and idempotency lock — the user saying "I changed the data, charge me and re-read". Imports, bulk tag changes, and "we just added 200 expo contacts" are the three legitimate triggers in the logs. Everything else should hit the cache.</p>

<h2>The bug that made me build the idempotency lock</h2>

<p>Before the lock existed, duplicate paid runs were my most embarrassing credit bug. The sequence was always the same: user clicks confirm, the job takes 30-90 seconds, the user sees no instant result, clicks confirm again — or retypes "analyze all my contacts" and confirms the "new" card. Two jobs dispatch, each charges on dispatch, each fans out to ~11 AI calls. That is 11 wasted calls per duplicate run at minimum, 22 credits double-spent, and two near-identical reports that make the customer feel scammed by my billing.</p>

<p>The fix is an idempotency lock keyed on the same team + mode + sha256 hash as the cache, held from confirm-click through dispatch. The second confirm for an identical in-flight cohort does not dispatch — it returns the in-flight job id with a "this analysis is already running" notice. The lock expires after 10 minutes so a crashed job cannot block its cohort forever. Since shipping it, duplicate dispatches are effectively zero, down from ~12 per week.</p>

<p>The lesson: any button that spends money and takes longer than three seconds needs an idempotency story before it ships, not after. The lock was 40 lines of code. The refunds it replaced were hours of my life every month. New teams can try the whole loop free at <a href="https://ot1-pro.com/register">the OT1-Pro registration page</a> — double-click the confirm card and watch the lock catch it.</p>

<h2>The NaraRouter outage that burned 4 identical paid runs</h2>

<p>The worst single incident was not user error — it was a provider outage plus my own retry logic. During a NaraRouter brownout in September, the text chain started returning 500s on batch calls. My job retry policy, borrowed from the normal chat-reply path, retried the whole deep-analysis job 4 times. Each retry re-dispatched the fan-out, each fan-out burned calls against the fallback chain, and all 4 retries failed identically when the NaraRouter 30-min global cooldown engaged. Four identical paid runs, zero reports, one furious customer watching credits drain during an outage he did not cause.</p>

<p>Three changes came out of that night. First, deep-analysis jobs now check the global cooldown state before dispatching the fan-out — if the NaraRouter 30-min global cooldown is active, the job waits with a backoff instead of burning calls it knows will fail. Second, batch-level failures retry only the single failed batch, once, against the next model in the chain — never the whole run. Third, a run that fails with AiAllProvidersUnavailable on every batch is marked failed with no further automatic retries, and the team sees "providers unavailable — retry free when service recovers". Same philosophy as a related guardrail: Meta's error 2018278 outside 24h window refuses to spend on a WhatsApp reply the platform will reject.</p>

<p>Since the fix: zero repeat events in six weeks. The cooldown check alone has absorbed two subsequent brownouts — jobs paused, cooldown cleared, runs completed late but correctly, nobody charged for the wait.</p>

<h2>Browser notify via Reverb: DeepAnalysisCompleted and the stale-banner trap</h2>

<p>A 60-90 second job needs a better signal than "keep refreshing". When a run finishes, the server broadcasts a DeepAnalysisCompleted event over Reverb on the team's private channel. The inbox layout listens and fires a browser Notification — title, mode, cohort size, click-through to the report. Focused tab? It swaps the "running" banner for the report inline.</p>

<p>The failure mode that bit me: the stale banner when the Reverb event never fires. Corporate networks, ad blockers, and one memorable Safari version all silently drop the WebSocket — the event broadcasts correctly but never arrives, and the user stares at "analysis running…" forever while the finished report sits in the database. Two-part fix: every "running" banner polls job status every 15 seconds (worst case a 15-second delay, not infinity), and every banner carries a timestamp — "running since 09:14:22 UTC" — so anything older than 5 minutes reads as suspicious.</p>

<p>The app requests Notification permission only when the user confirms their first run — the moment the value is obvious. At signup it converted at 11%; at confirm-click, 64%.</p>

<h2>When deep analysis is worth it on the $79/mo Pro plan</h2>

<p>Honest accounting, using the live credit table (October 2026) and the <a href="https://ot1-pro.com/pricing">$79/mo Pro plan</a> pool:</p>

<table>
<thead>
<tr><th>Pattern</th><th>Runs / week</th><th>Credits</th><th>Credit value</th><th>Verdict</th></tr>
</thead>
<tbody>
<tr><td>Fresh 1,000-contact run daily, no cache</td><td>7</td><td>154</td><td>~$30.80</td><td>Wasteful</td></tr>
<tr><td>Fresh Monday + cached re-reads</td><td>1 paid + 6 cached</td><td>22</td><td>~$4.40</td><td>Recommended</td></tr>
<tr><td>Duplicate double-clicks (pre-lock)</td><td>2 paid per intent</td><td>44</td><td>~$8.80 burned</td><td>Fixed by lock</td></tr>
<tr><td>4 paid retries in outage (pre-fix)</td><td>4 paid, 0 reports</td><td>88</td><td>~$17.60 burned</td><td>Fixed by cooldown check</td></tr>
<tr><td>Segmented: 2 fresh runs + caches</td><td>2 paid + cached</td><td>44</td><td>~$8.80</td><td>Best for 2,000+</td></tr>
</tbody>
</table>

<p>My recommended routine for 1,000-3,000 contacts: Monday, run customer_themes fresh on your hottest segment. Tuesday, run agent_audit fresh on the same segment — the pair shows what buyers want and where your process leaks. Wednesday through Sunday, re-read from cache at 0 credits, force_fresh only after imports or bulk tag changes. Total: 44 credits a week, ~$8.80 of value, two genuinely useful reports plus free re-reads — leaving the bulk of the Pro pool for actual customer conversations, which is where revenue comes from.</p>

<p>Final note: under 20 contacts, skip deep analysis — ask the AI chat inline, free and faster. Deep analysis earns its keep at 20+ and compounds at 500+.</p>

<p>{{CTA}}</p>
HTMLP1,
                'meta_title'        => 'AI Deep Analysis Contacts: 3,000 in Plain Words',
                'meta_description'  => 'Founder playbook: type \'read the 3000 contacts\', get plain-language themes, 24h 0-credit cache, emerald-pill reruns and Reverb notify: ai deep analysis contacts',
                'category'          => 'Sales',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 2. ai-sales-agent-objection-handling-playbook (en) ──
            [
                'title'   => 'AI Sales Playbook Rewrite: How Our Bot Learned to Close Like a Human',
                'slug'    => 'ai-sales-agent-objection-handling-playbook',
                'excerpt' => 'I rewrote our info-catching bot into a human closer after it asked a buyer what service they offer. Here is the shipped Rule #0, 10-step playbook, Arabic objection replies, capture ladder, and follow-up math that recovered our ghosts. Same playbook ships on every model we run.',
                'content' => <<<'HTMLP2'
<article>
<p>I built OT1-Pro to answer customer messages, and for months I told myself the AI was doing fine. It replied fast and collected names and phone numbers. Then I read 200 real conversations back to back, and my stomach dropped. Our bot was not selling. It was catching information and dropping deals.</p>
<p>The worst one still burns in my memory. A customer wrote in Egyptian Arabic: "انا مهتم بالخدمة" — I am interested in the service. Our AI replied: "ما هي الخدمة اللي بتقدمها؟" — what service do you offer? He never replied. That failure forced me to throw out our old prompt and rewrite it as a human closer playbook, now living in one shared trait, BuildsConversationPrompts, used by NaraRouter, Gemini, and Ollama.</p>

<h2>1. My bot asked a buyer what service THEY offer</h2>
<p>The message was "انا مهتم بالخدمة بتاعتكم" — I am interested in your service. The word بتاعتكم literally means yours. No human would misread it. Our old AI treated the buyer like a vendor.</p>
<p>The same pattern showed up in English. Customer: "I want it" / "tell me more about your product." Old AI: "Could you tell me what you are looking for exactly?" In DMs, patience runs out after two messages.</p>
<p><strong>BAD:</strong> Customer: "انا مهتم بالخدمة." AI: "اهلا! ممكن تعرفني بالخدمة اللي بتقدمها؟" The customer thinks nobody is home and leaves.</p>
<p><strong>GOOD:</strong> Customer: "انا مهتم بالخدمة." AI: "اهلا وسهلا بيك! الخدمة بتاعتنا بترد على عملاءك على مدار الساعة حتى وانت نايم. تحب تعرف السعر ولا ازاي بتشتغل الأول؟" One benefit, one question, momentum kept.</p>
<p>I wrote RULE #0 first because nothing else matters if the AI misreads who is buying from whom. If you run Meta messaging, this misread costs even more because of how permissions and review work — I documented that path in my <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta app verification founder guide</a>.</p>

<h2>2. Rule #0: you work for the business, the writer is the buyer</h2>
<p>RULE #0 sits at the top of BuildsConversationPrompts: you work FOR this business. Every person who messages you is a potential customer. They are not a vendor and they are not pitching you.</p>
<p>The trait lists trigger phrases in both languages: "I am interested" / "I want this" / "your product" / "tell me more" / "انا مهتم" / "عايزها" / "الخدمة" / "المنتج" / "بتاعتكم" / "عندكم". When any of those appear, the instruction is to pitch ONE benefit. Asking "what service do you offer?" / "ما هي الخدمة اللي بتقدمها؟" is explicitly banned. I made it a ban, not a suggestion, because polite clarification still kills the sale.</p>
<p>Only three exceptions exist: (a) after we have pitched our product at least twice, (b) when asking about their business tailors the pitch, for example "what do you sell?" to match a feature to their use case, or (c) when they explicitly ask us to understand their needs first. Outside those three, asking about their business is a bug.</p>

<h2>3. The 10-step playbook that replaced the info-catching bot</h2>
<p>Our old prompt told the AI WHAT to do — push toward the sale, handle objections — without teaching HOW. So the model fell back on collecting names and dumping features. It sounded like a form with emojis.</p>
<p>I replaced it with the flow a top 1% rep actually works in DMs:</p>
<ol>
<li>Listen first. Read the full history. Place the buyer: curious, comparing, ready, hesitating, objecting.</li>
<li>Greeting is not a pitch moment. Warm hello plus one hint at outcomes, then a question.</li>
<li>Discover the pain with one short qualifying question.</li>
<li>Pitch benefits, never features. Feature → so you can → outcome.</li>
<li>Handle objections with Feel-Felt-Found or Isolate-Reframe-Resolve. Never argue, never discount, never apologize.</li>
<li>Collect micro-commits. Small yeses before the big yes.</li>
<li>Close directly on a buying signal. Direct, assumed, or alternative close.</li>
<li>Handle silence and short answers by pivoting, not apologizing.</li>
<li>Match the customer's energy, dialect, and length.</li>
<li>One idea per reply. One question or one call to action, DM-short.</li>
</ol>
<p>Every provider inherits the same steps through the shared trait. NaraRouter may route to a different model than Gemini or our local Ollama fallback, but the sales mechanics never change. I wanted one closer, not three different salesmen. If you wonder why most AI replies feel robotic, this is usually why — politeness with no playbook. I wrote more on what actually turns replies into revenue in my <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs Wati breakdown</a>.</p>

<h2>4. Listen first, and never confuse a greeting with a pitch</h2>
<p>Steps one and two sound obvious until you watch a bot fail them hundreds of times. Step one: decide where the buyer is in his head. Curious needs education. Comparing needs differentiation. Ready needs a close. Hesitating needs safety. Objecting needs reframing.</p>
<p>Step two fixes the greeting failure. Customer writes "السلام عليكم" or "hi" with no question. The old AI either interrogated or dumped pricing plus features.</p>
<p><strong>BAD:</strong> Customer: "السلام عليكم." AI: "وعليكم السلام! اسمك ايه؟ بتشتغل في ايه؟ وعايز تعرف سعر انهي باقة؟" Three questions, zero value.</p>
<p><strong>GOOD:</strong> Customer: "السلام عليكم." AI: "وعليكم السلام واهلا بيك! تحب أعرفك ازاي بنرد على عملاءك وانت نايم، ولا عندك سؤال محدد؟" Warm, one hint at the strongest outcome, one open door.</p>
<p>Same shape in English. <strong>BAD:</strong> "Hi! What is your name and what are you looking for? Here are our plans..." <strong>GOOD:</strong> "Hey! Want the quick tour of how we reply to your customers 24/7, or do you have something specific in mind?" The greeting earns the second message. It does not spend it.</p>

<h2>5. Discover the pain fast, then sell benefits not features</h2>
<p>People buy when the pain of staying the same exceeds the pain of changing. The AI asks one short qualifying question when relevant: "إيه الحاجة اللي بتدور عليها بالظبط؟" / "What is the main thing you are trying to solve?" One question, never an interrogation.</p>
<p>Then it translates features into outcomes. Nobody buys a unified inbox. They buy never missing a message again. Nobody buys 12,000 AI credits. They buy a business that replies while they sleep. Formula: feature → so you can → outcome, one benefit per message.</p>
<p><strong>BAD (feature dump):</strong> "عندنا inbox موحد و 12,000 credits و multi-channel و analytics." The customer reads specs and feels nothing.</p>
<p><strong>GOOD (benefit matched):</strong> "بما إنك بتضيع رسائل على انستجرام وفيسبوك، الميزة دي بتجمعهم في مكان واحد — so you can reply in seconds and stop losing buyers who message at midnight." One pain, one outcome.</p>
<p>Lead-score emphasis layers on top: cold leads stay on discovery, warm leads get one matched benefit, hot leads skip to the close. A cold "hi" is never rushed and a hot "how do I pay?" is never lectured.</p>

<h2>6. Objection handling: Feel-Felt-Found with real Arabic replies</h2>
<p>Objections are where deals are won. My rule is blunt: do not argue, do not discount, do not apologize. Use Feel-Felt-Found (I understand how you feel, others felt the same, here is what they found) or Isolate-Reframe-Resolve (isolate the real blocker, reframe it, resolve with a small next step).</p>
<p>These four cover almost every stall in our inbox: too expensive, need to think, check later, and does it really work.</p>
<table border="1" cellpadding="8" cellspacing="0">
<tr><th>Objection</th><th>BAD reply (kills deal)</th><th>GOOD reply (Feel-Felt-Found)</th><th>Why it works</th></tr>
<tr><td>Too expensive / غالي</td><td>"Sorry! We can discount it. What is your budget?"</td><td>"أفهمك — كتير من العملاء بيحسوا كده في الأول. اللي بيلاقوه إن تكلفة الموظف اللي بيرد على الرسائل أعلى بكتير. تحب تجرب المجاني الأول وتشوف بنفسك؟"</td><td>Validates the feeling, reframes cost against hiring, lowers risk with a trial instead of a discount.</td></tr>
<tr><td>Need to think / محتاج أفكر</td><td>"Okay take your time! Let me know!"</td><td>"عادي — إيه بالظبط اللي محتاج تفكر فيه؟ السعر ولا ازاي بتشتغل؟ ممكن أساعدك تحسمه هنا."</td><td>Isolates the real blocker instead of accepting a vague stall.</td></tr>
<tr><td>Check later / أرد عليك بعدين</td><td>"Okay waiting for you!"</td><td>"تمام — عشان ما تنساش، تحب أبعتلك اللينك دلوقتي وتفتحه وقت ما تيجي ليك فرصة؟"</td><td>Keeps control with a tiny yes and a link, no pressure.</td></tr>
<tr><td>Does it work? / بيشتغل فعلا؟</td><td>"Yes it works great, best quality ever!"</td><td>"سؤال مهم. اللي شفناه من Batch33 — من 12,847 محادثة — إن اللي جرب المجاني وشاف الرد الفوري هو اللي كمل. تحب أوريك مثال من مجال شبه مجالك؟"</td><td>Answers with evidence and a concrete next step, never invents numbers it does not have.</td></tr>
</table>
<p>In English: too expensive <strong>BAD:</strong> "Sorry it feels pricey, what can you afford?" <strong>GOOD:</strong> "Totally get it — others felt the same until they compared it to paying someone to watch the inbox. Want to try free first?" Need to think <strong>BAD:</strong> "No problem, think about it!" <strong>GOOD:</strong> "Of course — what part do you want to think through?" Check later <strong>BAD:</strong> "Sure, ping me later!" <strong>GOOD:</strong> "Want me to send the link now so you have it when free?" Does it work <strong>BAD:</strong> "Yes, it is amazing!" <strong>GOOD:</strong> "Fair question — want an example from a business like yours, or try free and watch it reply live?"</p>

<h2>7. Micro-commits before the close, then close like you mean it</h2>
<p>Nobody says yes to a big ask without saying yes to small ones first. Step six forces micro-commits: "يعني الموضوع ده مهم ليك دلوقتي، صح؟" / "Does solving X sound worth 10 minutes to set up?" Each yes makes the next ask easier.</p>
<p>Step seven: close. Buying signals are explicit in the trait — asking about price, asking HOW it works, asking what is included, asking about guarantees, saying "okay" or "تمام". When you see one, stop selling and close.</p>
<p>Three close shapes ship in the prompt. Direct: "تمام — تحب نبدأ دلوقتي؟ اللينك: https://ot1-pro.com/register" Assumed: "هبعتلك اللينك الآن، لما تخلص التسجيل قولّي وأنا أمشي معاك خطوة خطوة." Alternative: "تحب تبدأ بالخطة المجانية الأول ولا بالمدفوعة؟"</p>
<p><strong>BAD closing:</strong> Customer: "بكام الباقة؟" AI: "عندنا 3 باقات بأسعار مختلفة ومميزات كتير تحب تعرف إيه؟" The buyer asked how to pay and got homework.</p>
<p><strong>GOOD closing:</strong> Customer: "بكام الباقة؟" AI: "المدفوعة 79 دولار في الشهر وبترد على عملاءك 24/7. تحب أبعتلك لينك التسجيل وتبدأ مجاني الأول؟" One fitting tier, one link, one question. Full tiers live at <a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a> — the AI picks the tier that fits instead of dumping the page into chat.</p>

<h2>8. Ghosts, follow-up math, and the 27% we win back</h2>
<p>From our Batch33 corpus of 12,847 real conversations, 73% of buyers ghost right after hearing the price. They do not object. They vanish. A bot that treats silence as rejection loses nearly three quarters of its pipeline at the moment of highest intent.</p>
<p>The same corpus shows the recovery: a 3-touch follow-up at 24 hours, 72 hours, and 7 days, each adding new value — never just checking in. That sequence wins back 27% of the ghosts. On a $79 per month plan, every 100 priced conversations means 73 ghosts, about 20 recovered buyers, roughly $1,580 in monthly recurring revenue a polite bot would leave on the floor.</p>
<p>That cadence is automated in the product — timing, value-add rule, and stop-on-reply logic are described in <a href="https://ot1-pro.com/sales-follow-up-automation">sales follow-up automation</a>. To see how we mine those conversations for what buyers say before they ghost, read the sibling study <a href="https://ot1-pro.com/blog/ai-deep-analysis-3000-contacts-0-credit-rerun">AI deep analysis of 3,000 contacts</a>.</p>
<ol>
<li>24 hours: one new useful thing tied to what they asked. "نسيت أقولك — بتشتغل على فيسبوك وانستجرام وواتساب من مكان واحد."</li>
<li>72 hours: proof, not pressure. One line from a similar business.</li>
<li>7 days: risk reversal. "تحب تبدأ مجاني وأنا أمشي معاك في الإعداد؟"</li>
</ol>

<h2>9. The capture ladder: when to ask for a name, and when to shut up</h2>
<p>The old bot opened with "What is your name?" on turn one. Real closers never do. The trait hard-codes a capture ladder so every model asks for personal info at the lowest-friction moment:</p>
<ol>
<li>Turn 1 (greeting or first question): NEVER ask for name, phone, or email. Engage, pitch one benefit, ask a qualifying question about their need.</li>
<li>Turn 2-3 (they showed interest or asked a real question): if business_type or business_name is on the capture list, this is the natural moment. "بتشتغل في ايه؟" fits here because it tailors the pitch.</li>
<li>Pricing or buying moment: ask for name plus the contact method they are writing on, framed as service — "ممكن أعرف اسمك عشان أبعتلك العرض؟" / "so I can send you the details" — never as fill out this form.</li>
<li>All fields captured: stop asking. Close warmly and confirm next steps.</li>
</ol>
<p>One field per message, maximum, tied to what they just said. "Perfect, X sounds amazing — ممكن أعرف اسمك عشان أبعتلك التفاصيل؟" beats "What is your name, phone, and email?" every time. On refusal — "لا" / "later" / "why do you need my phone?" — give them the info they asked for first, build rapport, retry later. Never interrogate, and never repeat a question already answered. To enter that flow yourself, start at <a href="https://ot1-pro.com/register">create your OT1-Pro account</a>.</p>
<p><strong>BAD ladder:</strong> Turn 1: "اهلا! اسمك ايه ورقم تليفونك؟" The customer feels trapped before learning anything.</p>
<p><strong>GOOD ladder:</strong> Turn 1: benefit plus qualifying question. Turn 2: "بتشتغل في ايه؟ عشان أقولك أنسب استخدام ليك." Pricing moment: "تمام — ممكن أعرف اسمك عشان أبعتلك لينك التسجيل والخطوات؟"</p>

<h2>10. Silence, energy matching, one idea per reply, and the spam guard</h2>
<p>When buyers answer "لا" or "nope", the trait forbids apologizing or retreating into corporate mush. Pivot to a different angle: "ماشي — تحب تعرف إيه بالظبط؟ السعر، إزاي بيشتغل، ولا تجرب مجاني الأول؟" Short answers are direction, not rejection.</p>
<p>Energy matching is mandatory. Short messages get short replies. Formal gets formal, casual gets casual. Egyptian dialect gets Egyptian, Khaleeji gets Khaleeji. The language mirror is absolute: Arabic in, 100% Arabic out; English in, 100% English out; never an English sentence inside an Arabic reply. A mid-Arabic English refusal was a real production failure, so refusal phrases are banned in both languages.</p>
<p>DM length discipline: 1-2 short sentences, ONE idea per reply, ONE question or call to action. <strong>BAD:</strong> five features plus pricing plus "what is your name?" <strong>GOOD:</strong> "الميزة دي بتخليك ترد في ثواني حتى الفجر. تحب تشوفها على رسائلك انت؟"</p>
<p>I also preserved the [SPAM_DETECTED] token verbatim. Before any sales reply, the model judges abuse, trolling, gibberish, or hostility that would make a human rep stop. A hard price question is never abuse. On a hit it outputs exactly [SPAM_DETECTED] and nothing else. Once a human reactivates a conversation, the classifier stands down unless the latest message is explicitly abusive.</p>
<p>I rewrote this playbook because I read our own chats and felt embarrassed. The info-catching bot asked buyers what THEY sell. The closer I ship now listens first, pitches one benefit, handles the four real objections in the buyer's own dialect, asks for a name only when it can send something useful, and closes with a link instead of a lecture. Same trait, every model.</p>
<p>{{CTA}}</p>
</article>
HTMLP2,
                'meta_title'        => 'AI Sales Agent Playbook: Close Like a Human',
                'meta_description'  => 'I rewrote our chatbot into a human closer: Rule #0, a 10-step playbook, Arabic objection replies, capture ladder and ghost follow-up math with ai sales agent',
                'category'          => 'Sales',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '13 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 3. facebook-page-not-showing-business-portfolio-fix (en) ──
            [
                'title'   => 'Facebook Page Not Showing on Connect: I Burned 4 Hours on Meta\'s Business Portfolio Trap',
                'slug'    => 'facebook-page-not-showing-business-portfolio-fix',
                'excerpt' => 'Your Page is missing because /me/accounts only returns direct Page roles, never Business Portfolio grants that most agencies use. Fix: add business_management scope, query /me/businesses plus owned_pages and client_pages, merge and dedupe by Page ID. Also confirm all nine permissions show Advanced Access.',
                'content' => <<<'HTMLP3'
<p><strong>I built OT1-Pro so agencies could connect a Facebook Page in two clicks and start answering Messenger from one inbox. Then a customer in Cairo clicked Connect, OAuth succeeded, and the Page picker showed zero Pages.</strong> Not an error. Not a timeout. Just an empty list while the Page was clearly alive in Business Suite. I burned ~4 hours guessing before I found the real cause, and I am writing this so you never have to.</p>

<p>The cause is what I now call the Business Portfolio trap: <code>/me/accounts</code> ONLY returns Pages where your personal Facebook account holds a direct Page admin role. It NEVER returns Pages where access was granted through a Business Portfolio — New Pages Experience assignments, agency employees added inside Business Suite, team members with "Full access" — even when the Suite UI shows "People with Facebook access, Full access". If your agency lives inside Business Suite, which every real agency does, the standard connect flow hides your Pages by design.</p>

<p>The shipped fix on OT1-Pro on 2026-10-07 was simple once we saw it: request <code>business_management</code> scope at OAuth, then query BOTH <code>/me/accounts</code> AND <code>/me/businesses</code> → <code>{biz-id}/owned_pages</code> + <code>{biz-id}/client_pages</code>, merged and deduped in <code>FacebookPlatform::fetchBusinessMediatedPages()</code>. If you are searching for facebook page not showing, this post gives you the diagnostic probe, the code shape, and the fallback that keeps you selling while Meta sorts its permissions.</p>

<p>For the full Meta bureaucracy backstory — Business Verification, App Review, and why our app 1469090344742803 works the way it does — read my <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta App Verification 2026 founder guide</a>. That post earns 5-20 minute dwell times for a reason: it tells the truth about Meta instead of repeating docs.</p>

<h2>I clicked connect, OAuth succeeded, zero Pages listed</h2>

<p>It was a Tuesday night. An agency trial from Alexandria had three Pages under a portfolio setup like our own OT1 Pro portfolio 2169075923895403 — one portfolio, multiple Pages, staff added as partners. The owner did everything right. He logged in with the correct Facebook account, accepted all permissions, and our callback received a valid user token. Then <code>fetchPages()</code> called <code>/me/accounts</code> and got back <code>{"data": []}</code>.</p>

<p>My first instinct was that he had picked the wrong Facebook account. I asked him to reconnect. He did. Same empty list. I asked about 2FA, ad-blockers, test users. He was patient. I was wrong on all three.</p>

<p>What made it maddening is that Business Suite showed him as admin everywhere: Business settings → People → Full access, and the Page itself → Page access → Full control. By every UI signal, he WAS the admin. But the API said he owned nothing. I have since seen the same empty list in Giza, Jeddah, Dubai, and with a UK founder filing a Companies House confirmation statement the same week.</p>

<h2>The two kinds of Page admin Meta never tells you about</h2>

<p>Meta has two completely separate ways to make someone a Page admin, and they look identical in Business Suite but behave differently in the API.</p>

<p>Type 1 is the old direct Page role. You go to the Page → Settings → Page access → add someone with their personal Facebook profile. That role is stored on the Page object itself. When that person OAuths any app and you call <code>/me/accounts</code>, Meta returns the Page with a <code>page_access_token</code>. This is the path every tutorial and every Stack Overflow answer assumes is the only path.</p>

<p>Type 2 is Business Portfolio-mediated access. You go to Business Suite → Business settings → People → add the person → give them access to Pages owned by the portfolio. Or you add an agency as a partner and assign Pages to that partner portfolio. With New Pages Experience, this is now the DEFAULT for teams, because Meta pushes you to manage people at portfolio level. The person never gets a direct role on the Page row. Their permission lives on the business asset graph, not the Page.</p>

<p>Here is the line Meta buries: <code>/me/accounts</code> only reads Type 1. It does not traverse the business asset graph. If all your access is Type 2, you get an empty array with HTTP 200 — no error, no warning. I confirmed this with three test users on portfolio OT1 Pro 2169075923895403: direct-add the user and the Page appears in <code>/me/accounts</code>; remove the direct role, grant identical access via portfolio, and the Page vanishes from <code>/me/accounts</code> while Suite still shows Full access.</p>

<h2>Why /me/accounts hides portfolio Pages by design</h2>

<p>I call it hiding, but from Meta's side it is scoping. <code>/me/accounts</code> answers "which Pages has this user been directly granted?" It was built before Portfolios existed, and Meta never updated its semantics when team management moved to portfolios. They added new edges — <code>/me/businesses</code>, <code>/{business-id}/owned_pages</code>, <code>/{business-id}/client_pages</code> — but left the old endpoint returning a partial answer with a 200 status.</p>

<p>That partial-answer-with-200 is what burns founders. If the endpoint returned a 403 saying "use business_management scope", we would fix it in ten minutes. Instead it returns success with missing data, so you blame the user. I did exactly that for the first two hours.</p>

<p>The second quirk: you cannot see portfolio-mediated Pages without <code>business_management</code>. Our original OAuth scope set was <code>public_profile, email, pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, instagram_basic, instagram_manage_messages</code> — enough for direct Pages, NOT enough to list businesses. Without <code>business_management</code>, <code>/me/businesses</code> returns empty too. You need all nine permissions at Advanced Access before enumeration works for real customers. The WhatsApp side of this same scoping pain is covered in my sibling post <a href="https://ot1-pro.com/blog/whatsapp-embedded-signup-history-import-2026">WhatsApp Embedded Signup history import 2026</a>.</p>

<h2>The 4-hour guessing spiral I want you to skip</h2>

<p>Here is my actual spiral, hour by hour. Every wrong guess felt reasonable. Every one cost 30-60 minutes. Read the table, then never repeat it.</p>

<table>
<thead>
<tr><th>Symptom</th><th>Wrong guess I tried</th><th>Real check that kills the guess</th><th>Fix</th></tr>
</thead>
<tbody>
<tr><td>Picker shows 0 Pages after successful OAuth</td><td>User picked wrong Facebook account</td><td>Call <code>/me?fields=id,name</code> with stored token, compare ID to Suite → same user</td><td>Stop re-asking for reconnect; query <code>/me/businesses</code></td></tr>
<tr><td><code>/me/accounts</code> returns <code>{"data":[]}</code> with 200</td><td>2FA or password change revoked token</td><td>Call <code>/me/permissions</code> → scopes show granted, token debugs valid</td><td>Token is fine; missing edge is business-mediated, not auth</td></tr>
<tr><td>Suite shows Full access but API shows nothing</td><td>Scope stripped at OAuth dialog</td><td>Inspect <code>/me/permissions</code> for <code>business_management</code> declined vs absent</td><td>Add <code>business_management</code> to OAuth scope and re-auth</td></tr>
<tr><td>Works for my admin tester, fails for customer</td><td>App in dev mode / test-user limit</td><td>Open developers.facebook.com/apps/1469090344742803 → permission shows "جاهز للاختبار" (Ready to Test = Standard Access)</td><td>Push all 9 permissions to Advanced Access; until then use managed onboarding</td></tr>
<tr><td>Some Pages appear, one Page missing</td><td>Page unpublished / restricted</td><td>Query <code>{biz}/owned_pages</code> vs <code>{biz}/client_pages</code> separately; missing Page sits on the other edge</td><td>Merge + dedupe both edges in <code>fetchBusinessMediatedPages()</code></td></tr>
<tr><td>Non-admin sees "Feature unavailable: Facebook Login is currently unavailable for this app"</td><td>Bug in our callback handler</td><td>Confirm app 1469090344742803 review state; Standard Access hard-blocks non-admin logins</td><td>Keep META_APP_VERIFIED false; route customer through managed onboarding</td></tr>
</tbody>
</table>

<p>The pattern: I guessed identity, auth, or Page state. The real cause was graph traversal every time. Once I started querying BOTH endpoints with the stored token before theorizing, diagnosis dropped from hours to minutes. That is now a hard rule on our team: no theory before both probes.</p>

<h2>The 10-minute diagnostic that tells the truth</h2>

<p>If your Facebook Page is not showing on connect right now, run these seven steps in order with the actual stored user token. Total time is about ten minutes.</p>

<ol>
<li><strong>Confirm identity.</strong> Call <code>GET /me?fields=id,name</code> with the stored token. Compare the ID to the person in Business Suite → People. If they match, kill the "wrong account" theory permanently.</li>
<li><strong>Confirm token health.</strong> Call <code>GET /me/permissions</code>. All nine — <code>public_profile, email, pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, instagram_basic, instagram_manage_messages, business_management</code> — should show <code>granted</code>. If <code>business_management</code> is declined or absent, re-OAuth with the full nine-scope string. Users can untick scopes in the dialog.</li>
<li><strong>Run the legacy probe.</strong> Call <code>GET /me/accounts?fields=id,name,access_token</code>. Save the list. This is your direct-role set. If empty, do NOT conclude the user has no Pages.</li>
<li><strong>Run the portfolio probe.</strong> Call <code>GET /me/businesses?fields=id,name</code>. For each business, call <code>GET /{biz-id}/owned_pages</code> AND <code>GET /{biz-id}/client_pages</code>. This is exactly what <code>FacebookPlatform::fetchBusinessMediatedPages()</code> does since 2026-10-07.</li>
<li><strong>Merge and dedupe.</strong> Union all three lists by Page ID. In our Alexandria case this went from 0 Pages to 3. If the missing Page appears here, you have proven the Portfolio trap and can stop debugging auth.</li>
<li><strong>Check access level.</strong> Open developers.facebook.com/apps/1469090344742803 → Use Cases → Permissions. If any permission shows "جاهز للاختبار" instead of Advanced Access, non-admin customers still hit <code>Feature unavailable: Facebook Login is currently unavailable for this app</code>. That is a review-state problem, not a code problem.</li>
<li><strong>Ship the fallback decision.</strong> Merged Page found + Advanced Access everywhere → ship the merged picker. Anything at Standard Access → keep the code but route real customers through "Request connection" so a super-admin OAuths via the verified path and re-assigns the Page.</li>
</ol>

<p>Support runs this checklist before they are allowed to tell a customer to "try another browser". It has ended the guessing cycle on every case since October.</p>

<h2>The shipped fix: business_management plus both page edges</h2>

<p>Our fix in <code>app/Services/Platforms/FacebookPlatform.php</code> is boring on purpose. <code>fetchPages()</code> now does two traversals and merges.</p>

<p>First it keeps the legacy call: <code>/me/accounts</code> with the user token. Those rows already carry a <code>page_access_token</code> and map directly to our <code>pages</code> table. Direct-role Pages are still common for solo founders, so we did not remove this.</p>

<p>Second, when the token carries <code>business_management</code>, it calls <code>fetchBusinessMediatedPages()</code>: list businesses from <code>/me/businesses</code>, then per business fetch <code>owned_pages</code> (portfolio-owned) and <code>client_pages</code> (partner-shared, the classic agency case). We merge all three sources keyed by Page ID, prefer the freshest token, and dedupe before showing the picker.</p>

<p>Three details that matter. One, request <code>business_management</code> at OAuth time — code without the scope returns an empty business list and a false sense of fixed. Two, query BOTH <code>owned_pages</code> and <code>client_pages</code>. I shipped owned-only first and a partner-shared Page still vanished, because partner Pages live exclusively on the client edge. Three, keep the one-active-Page invariant: our <code>Page::booted()</code> observer enforces one active row per platform ID, and merging sources makes duplicates more likely, so dedupe by <code>platform_page_id</code> before upsert or webhook routing breaks. Total diff was under 120 lines. The four hours were not a coding problem. They were a seeing problem.</p>

<h2>Advanced Access vs جاهز للاختبار: the second trap behind the first</h2>

<p>Fixing enumeration reveals the next wall: App Review state. Meta shows each permission as Advanced Access or "جاهز للاختبار" — "Ready to Test", meaning Standard Access for admins and testers only. Our app 1469090344742803 needs all nine at Advanced Access: <code>public_profile, email, pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, instagram_basic, instagram_manage_messages, business_management</code>.</p>

<p>With any one stuck at Standard Access, your fixed picker works for you and fails for every real customer with <code>Feature unavailable: Facebook Login is currently unavailable for this app</code>. I watched a founder fix the portfolio bug, demo it to himself, ship it, then get that exact string from his first customer within the hour. He had not regressed. He had graduated to the next gate.</p>

<p>Reaching Advanced Access means Business Verification first, then App Review per permission. Verification wants character-for-character name matching: in Egypt the السجل التجاري extract plus tax card, in the UAE the trade license plus Ejari-registered tenancy contract, in the UK a Companies House confirmation statement. I document the full chain in the <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta verification founder guide</a>. Until every permission shows Advanced Access, do NOT flip <code>META_APP_VERIFIED=true</code>.</p>

<h2>Why app 1469090344742803 and portfolio 2169075923895403 matter</h2>

<p>Concrete IDs let you verify instead of guess. App <code>1469090344742803</code> is the OT1-Pro Meta app requesting the nine scopes. Portfolio OT1 Pro <code>2169075923895403</code> is our own Business Portfolio where I reproduced the trap: create two test users, add one via Page access directly, add the other via Business settings → People, OAuth both with identical scopes, compare <code>/me/accounts</code>. One Page vs zero Pages, identical Suite UI badges.</p>

<p>Run that reproduction on your own portfolio before you believe any docs page. The API is the source of truth; the UI collapses two different grants into one badge. Log both probe payloads when a customer reports a missing Page — we store accounts / businesses / owned / client counts on the onboarding request, and that line has resolved four disputes since October.</p>

<h2>The dollar math agencies feel immediately</h2>

<p>This is not a minor connect hiccup. Take a typical MENA agency on <a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a>: $1,500 monthly retainer per managed client, Messenger + Instagram handling with AI drafts in Egyptian Arabic. If the Page cannot connect, you cannot ingest messages, train replies, or run the unified-inbox demo that won the deal. The client does not pay for "almost connected". They churn or pause.</p>

<p>Lose one $1,500 retainer over a two-week connect delay and you lose ~$750 in recognized revenue plus ~6 hours of support ping-pong at $40/hour fully loaded ($240). One trapped Page costs ~$990. Lose three in a month across new trials — our late September — and that is ~$2,970 in burned pipeline for a 120-line fix. Against per-seat markups for the same Meta plumbing (see <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs WATI</a>), the agency that connects in minutes keeps the retainer while the one filing Meta tickets loses it. The math is why we built the managed onboarding fallback and refuse to remove it: the customer clicks "Request connection", our super-admin OAuths through the verified app, then re-assigns the Page at <code>/super-admin/onboarding-requests</code>. Unglamorous, and it closes.</p>

<h2>If you are stuck right now, do this today</h2>

<p>If you have an empty Page picker open in another tab, run the seven-step diagnostic above and save the probe counts for support. Then check your app's permissions: Advanced Access everywhere, or any "جاهز للاختبار"? If anything shows Standard Access and you are not an admin or tester on that app, stop retrying direct OAuth. You cannot code around review state.</p>

<p>On OT1-Pro, click Request connection instead. It files an onboarding request with your probe counts attached; a super-admin connects the Page through the verified app and assigns it to your team. You get Messenger + Instagram + WhatsApp in one inbox with AI replies from day one, no Meta ticket queue. Start at <a href="https://ot1-pro.com/register">OT1-Pro register</a> — free plan, no credit card — then file the request from Connections. Median turnaround since we formalized the queue is under a day, versus weeks for App Review.</p>

<p>I burned ~4 hours guessing 2FA, wrong accounts, and stripped scopes before I queried both endpoints with the stored token and saw the truth. Do not repeat my spiral. Query both, merge and dedupe, respect the Advanced Access gate, and keep a manual path that saves the retainer while Meta catches up. Your missing Page is almost certainly there — behind the portfolio edge your code never traversed.</p>

<p>{{CTA}}</p>
HTMLP3,
                'meta_title'        => 'Facebook Page Not Showing? Business Portfolio Fix',
                'meta_description'  => 'My client\'s Page vanished on connect though Suite showed Full access. Meta\'s Portfolio trap hid it from /me/accounts. The fix for facebook page not showing',
                'category'          => 'Connections',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 4. whatsapp-embedded-signup-history-import-2026 (en) ──
            [
                'title'   => 'WhatsApp Embedded Signup in 2026: History Import, Health Check, and Error 2018278',
                'slug'    => 'whatsapp-embedded-signup-history-import-2026',
                'excerpt' => 'I connected WhatsApp through Embedded Signup with no developer, imported every Messenger and Instagram chat so the AI had context day one, and fixed error 2018278, display-name rejections and tier caps. This founder guide covers OTP numbers, health checks, stickers and dollar math.',
                'content' => <<<'HTMLP4'
<p><strong>I connected my own WhatsApp number through Embedded Signup in one sitting, without opening Meta's developer dashboard once.</strong> No app creation, no token juggling, no webhook URL to paste. I clicked connect inside OT1-Pro, logged into Facebook, picked my Business Portfolio, verified my number with an OTP code, and the official Cloud API number was live. Then the part I did not expect kicked in: every Messenger and Instagram chat I had ever had started importing, so the AI had context on day one.</p>

<p>I built <a href="https://ot1-pro.com">OT1-Pro</a> as a single inbox for WhatsApp, Messenger, Instagram, Telegram and email, because I was tired of telling founders to hire a developer just to receive a WhatsApp message. This post is the exact record of what I shipped: how Embedded Signup really works, what Meta rejects, how the health check keeps the subscription alive, why I import full chat history on connect, and why error 2018278 will ruin your first bulk send if nobody warns you.</p>

<p>If you have not yet fought Meta's two-milestone verification chain, start with <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta App Verification 2026: A Founder's Guide</a>. That guide covers Business Portfolio verification plus App Review with Advanced Access. Everything below assumes OT1-Pro's app already cleared those two milestones, which is why you get to skip them.</p>

<h2>Why I killed the old token-paste setup</h2>

<p>The old way to connect WhatsApp Cloud API looked like this: create a Meta app, add the WhatsApp product, create a WhatsApp Business Account, add a phone number, generate a permanent system-user token, copy the phone-number ID and WABA ID into our settings form, then manually subscribe the app to webhooks. I watched three separate founders paste the wrong phone-number ID into the WABA ID field. One pasted a test number and spent two days debugging inbound messages that were never going to arrive.</p>

<p>Embedded Signup removes all of that. Meta hosts the whole flow inside a popup. You log in with Facebook, you pick the Business Portfolio, you pick or create the WhatsApp Business Account, you pick the phone number, you approve the permissions. Meta hands OT1-Pro back an authorization code, and our backend exchanges it for the WABA ID, phone-number ID and a long-lived system-user token without you touching any of it. There is no developer step left for you.</p>

<p>I kept the old manual fields in the database for migration, but no new customer should ever see them. If you can log into Facebook and receive an SMS, you can connect.</p>

<h2>What Embedded Signup actually asks you for</h2>

<p>Here is the numbered flow I shipped, exactly as you will see it inside OT1-Pro:</p>

<ol>
<li><strong>Click Connect WhatsApp.</strong> We open Meta's Embedded Signup dialog with the exact scopes the Cloud API needs: business_management, whatsapp_business_messaging and whatsapp_business_management.</li>
<li><strong>Log into Facebook and pick your Business Portfolio.</strong> Use the Portfolio that owns your company domain. If you pick a personal or empty Portfolio, your display name will fail later.</li>
<li><strong>Pick or create the WhatsApp Business Account.</strong> Most founders pick the existing WABA. If you have none, Meta creates one under that Portfolio inside the popup.</li>
<li><strong>Claim the phone number.</strong> Enter the number you want customers to message. It must be able to receive an OTP voice call or SMS during the popup. It cannot already be on the WhatsApp consumer app or on another WABA.</li>
<li><strong>Submit the display name.</strong> This is what customers see instead of your number. Type your exact business name.</li>
<li><strong>Approve and return.</strong> Meta redirects back to OT1-Pro with an authorization code. Our backend exchanges it, stores the token server-side, subscribes webhooks, runs the health check, and starts the history import.</li>
<li><strong>Send a test message.</strong> Message your new number from your personal phone, watch it land in the OT1-Pro inbox, and reply from the inbox. You are live on the official Cloud API.</li>
</ol>

<p>Total time for my own number: eleven minutes, including waiting for the OTP call. Total developer dashboard pages opened: zero.</p>

<h2>Display-name rules that reject real businesses</h2>

<p>Display-name review is where Embedded Signup founders get their first rejection email, usually two to six hours after they thought they were done. I have now seen every variant. Meta's reviewer checks three things, and all three are literal:</p>

<p>First, <strong>no generic names.</strong> "Customer Support", "Sales Team", "Store", "Service Center" are all rejected on sight. The name must identify a business or product. "Nile Dental Clinic" passes. "Dental Support" does not. I tried submitting "Customer Support" on a test WABA just to confirm, and the rejection arrived in 41 minutes.</p>

<p>Second, <strong>no emojis, trademark symbols, or leading punctuation.</strong> No "Nile Dental 🦷", no "Nile™ Dental", no "-Nile Dental-". One founder lost three days because his brand stylizes as "BRAND™" on his website and he copied it character for character.</p>

<p>Third, <strong>the name must visibly match your domain.</strong> If your website is niledental.com, submit "Nile Dental Clinic". Do not submit "NDC Smiles" even if that is your Instagram handle. The reviewer opens your domain and compares. Fix it by renaming to the exact title tag of your homepage and resubmitting.</p>

<h2>Your number must receive OTP, and it must be clean</h2>

<p>Two number problems block Embedded Signup before display-name review even starts. I hit both during testing.</p>

<p>The number must be <strong>OTP-capable.</strong> Meta calls or texts a six-digit code during the popup to prove you control it. I burned a virtual test range that never received the call, then switched to a normal Egyptian mobile and the code arrived in nine seconds. If the code never arrives, wait five minutes, check the number can receive international calls, then retry once.</p>

<p>The number must be <strong>clean.</strong> If it is currently registered in the WhatsApp consumer app or Business app, Embedded Signup refuses it with "number already in use". Delete the account from the app first, or pick a different number. Same if it is attached to another WABA. Remove it, wait ten minutes, then claim it.</p>

<h2>Subscription health check: the silent killer I automated</h2>

<p>Getting connected is half the job. Staying connected is the other half, and this is where I lost messages for a customer for six hours before I understood what had happened.</p>

<p>Meta delivers inbound WhatsApp messages through a webhook subscription with two layers: the app-level subscription on our Meta app, and the WABA-level subscription on your business account. Either layer can silently drop after a password change or an admin removing the app. Inbound stops while sending still works, so it looks like customers went quiet.</p>

<p>So I shipped a subscription health check that runs after every connect and on a schedule. It queries the Graph API for the subscribed fields on both layers and resubscribes anything missing. You see a green "Healthy" badge or a red "Reconnect needed" badge naming the exact missing field.</p>

<table>
<thead>
<tr><th>Step</th><th>What happens</th><th>What breaks</th><th>Fix</th></tr>
</thead>
<tbody>
<tr><td>1. Embedded Signup popup</td><td>Meta returns an auth code for your WABA and number</td><td>Wrong Portfolio picked, popup closed early, scopes declined</td><td>Reconnect, pick the Portfolio that owns your domain, accept all scopes</td></tr>
<tr><td>2. Code exchange</td><td>Backend swaps the code for WABA ID, phone-number ID and system-user token</td><td>Expired code after long idle in popup</td><td>Run the popup again from the start, codes expire in minutes</td></tr>
<tr><td>3. Number + display name</td><td>OTP verification and display-name review submitted</td><td>Generic name like Customer Support, emoji or ™, number already in app</td><td>Exact business name matching domain, clean OTP-capable number</td></tr>
<tr><td>4. Webhook subscribe</td><td>App-layer and WABA-layer fields subscribed</td><td>Admin removed app, password reset dropped subscription</td><td>Health check resubscribes, or click Reconnect</td></tr>
<tr><td>5. Health check</td><td>Required fields verified on both layers</td><td>Red badge naming a missing field</td><td>One-click resubscribe from connections screen</td></tr>
<tr><td>6. History import</td><td>Messenger and IG chats imported with context</td><td>Thousands of chats slow first sync, sticker rows look odd</td><td>Let the queue finish, stickers render as images, check sync diagnostics</td></tr>
<tr><td>7. First bulk send</td><td>Template broadcast with reachable-now count</td><td>Error 2018278 on stale contacts outside 24h window</td><td>Stale contacts auto-skipped, send template to reopen window</td></tr>
</tbody>
</table>

<p>I check that health badge the way I check server uptime. If it is red, nothing else you do in the inbox matters until it is green again.</p>

<h2>The Instagram Direct path most guides skip</h2>

<p>WhatsApp gets the headlines, but half my customers connect Instagram in the same session. I shipped the Instagram Direct path alongside Embedded Signup because the failure mode is identical: founder connects, expects history, sees an empty inbox, assumes the connection failed.</p>

<p>Instagram Business Login connects your IG professional account through Facebook Login and subscribes the same webhook stack. The permission that matters is instagram_manage_messages plus pages_messaging for the linked Page. If you connect a personal IG account not linked to a Facebook Page, events never arrive. Convert to a business account, link the Page, then reconnect.</p>

<p>If your Facebook Page itself does not appear during connect, that is almost never a scope problem. It is the Business Portfolio trap: Meta's /me/accounts endpoint only returns Pages where you hold a direct Page role, not Pages assigned through a Portfolio. I wrote the full fix at <a href="https://ot1-pro.com/blog/facebook-page-not-showing-business-portfolio-fix">Facebook Page Not Showing? Business Portfolio Fix</a>. Read that before you blame the popup.</p>

<h2>Full chat-history import: AI with context on day one</h2>

<p>This is the feature I am proudest of in this release. The moment you connect Messenger or Instagram, OT1-Pro imports the full chat history for every conversation on that Page, not just new messages going forward.</p>

<p>Why I built it this way: an AI sales agent with no history is useless for the first month. It does not know Ahmed asked about installments three weeks ago. With the import, the AI reads the prior thread before its first reply. On my test Page with 400 Messenger threads, the first AI reply referenced a two-month-old delivery complaint, and the customer replied "finally someone remembers".</p>

<p>On connect we page through the Page's conversations endpoint, pull each thread newest-first, and mark sync state per thread. The connections screen shows sync diagnostics: threads found, imported, failed, and last-sync time. If you connect a Page with 5,000 threads, let the queue finish. The AI gets sharper as the backfill completes.</p>

<p>One detail that took a full afternoon: stickers. The old importer stored them as the text "[Sticker]", so the AI replied like a confused robot and the inbox showed a gray bubble. I changed it to keep the sticker image URL and render it as an image bubble, with a text label only as fallback for the AI reader.</p>

<h2>The 24-hour window and Meta error 2018278</h2>

<p>Here is the rule that ruins every founder's first WhatsApp broadcast: <strong>outside a 24-hour customer-service window, you cannot send free-form text.</strong> If the customer messaged you within the last 24 hours, you can reply normally. If not, the Graph API returns error 2018278 with the message "The message was not sent because it was sent outside the allowed time frame" and drops the send.</p>

<p>I quote that error code exactly because you will see it. Search your logs for 2018278 and you will find the contacts whose window expired. No retry fixes it. The only way to reopen the window is a pre-approved template message, which the customer can reply to, reopening 24 hours of normal chat.</p>

<p>I built bulk sends around this honestly. When you select 800 contacts, OT1-Pro checks last-inbound time per contact first. Stale contacts are auto-skipped, counted as "skipped: window expired", and shown with a reachable-now count before anything sends. One click then sends the approved template to the skipped group to reopen them.</p>

<h2>Tier ladder: 1,000 to unlimited, and how one broadcast resets you</h2>

<p>Every new WhatsApp number starts at <strong>Tier 1: 1,000 business-initiated conversations per 24 hours.</strong> Business-initiated means you messaged first with a template. Customer replies inside the window do not count against the cap. The ladder from there is fixed: <strong>Tier 1 at 1,000 per 24h, Tier 2 at 10,000, Tier 3 at 100,000, Tier 4 unlimited.</strong> Meta moves you up automatically when your quality rating stays high and your volume justifies it, roughly over a rolling seven-day window. There is no application form for the next tier.</p>

<p>Quality rating is the gate. Green means healthy, yellow warning, red restricted. Drop to red and Meta pushes you back down within a day. I watched a founder on Tier 2 fire one purchased list of 6,000 cold contacts, collect blocks, and wake up back on Tier 1 with the rest hard-capped. The cap is per number, so a second number on the same WABA keeps its tier.</p>

<p>My rules: never start a new number with a cold blast, warm it with opted-in utility traffic first, and pause any template whose block rate spikes.</p>

<h2>Dollar math: BSP markup versus $79 flat</h2>

<p>Most BSPs charge per conversation on top of Meta's fees: Meta's fee plus $0.02 to $0.05 per marketing conversation as margin, plus a $49 to $149 monthly platform fee. At 20,000 marketing conversations a month, a $0.03 markup alone is $600 in margin before the subscription. At 50,000 it is $1,500.</p>

<p><a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a> is flat: $79 per month with Meta's fees passed through at cost. At 20,000 conversations you pay $79 plus Meta's bill instead of $600 in markup plus a platform fee. Breakeven against a $0.03-markup BSP lands around 2,500 conversations a month.</p>

<p>Now the Tier-1 cost of one bad broadcast. Suppose average order value is $25 and the list converts at 3%. A clean send to 1,000 opted-in contacts is 30 orders, $750 in revenue. Fire that Tier-1 budget at a stale purchased list, eat blocks, and the next days convert near zero while Meta holds you at Tier 1 for another week: roughly $5,000 in forgone revenue plus re-approval delay. I would rather auto-skip 400 stale contacts than burn the number for a vanity sent count.</p>

<p>Compare the full picture at <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs WATI</a>. Per-message markups look small until you multiply them by real broadcast volume.</p>

<h2>What I would do in your first hour</h2>

<p>If I were connecting today: Embedded Signup with an OTP-capable number, exact business name as display name with homepage title matching, green health badge before inviting the team, Instagram Direct in the same session, let history import finish, then first broadcast to opted-in contacts with the reachable-now count read out loud.</p>

<p>That hour buys you an official Cloud API number with no developer step, an AI that remembers customers from day one, and counts you can trust. <a href="https://ot1-pro.com/register">Start free here</a>, no credit card.</p>

<p>{{CTA}}</p>
HTMLP4,
                'meta_title'        => 'WhatsApp Embedded Signup: Full Setup Guide 2026',
                'meta_description'  => 'How I connected WhatsApp with no developer: OTP numbers, display names, full history import, health checks, tiers, 2018278 and whatsapp embedded signup',
                'category'          => 'WhatsApp',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 5. closed-deals-to-google-sheet-transparent-ai-credits (en) ──
            [
                'title'   => 'Closed Deals to Google Sheets: Transparent AI Credits and Bulk Excel That Won\'t Get You Banned',
                'slug'    => 'closed-deals-to-google-sheet-transparent-ai-credits',
                'excerpt' => 'I wired OT1-Pro to push every closed WhatsApp deal into the operator\'s Google Sheet automatically, added a credit ledger that receipts every AI reply, and throttled bulk Excel imports so new numbers survive. Real limits, real numbers, and the ROI math: 400 inquiries becomes 74 extra sales.',
                'content' => <<<'HTMLP5'
<p><strong>I lost 11 closed deals inside a Google Sheet in March.</strong> Not lost as in the customers walked away. Lost as in my closer marked them done in WhatsApp, the money moved, and nobody typed the row into the sheet for six days. By the time we reconciled, two customers had asked for receipts we could not find, one duplicate lead got pitched twice, and my accountant billed four extra hours of cleanup. I built <a href="https://ot1-pro.com">OT1-Pro</a> because I was tired of the gap between a deal closing in chat and the deal existing anywhere useful.</p>

<p>This post documents three systems I shipped to close that gap, with real limits and real numbers: the AI-config Connectors tab that pushes every close to your Google Sheet the second it happens, the transparent AI credit ledger that receipts every credit, and the throttled bulk Excel importer for WhatsApp and email campaigns. Every behavior below runs in production today. The short version: connect the sheet once, <code>EVENT_DEAL_CLOSED</code> and <code>EVENT_LEAD_CAPTURED</code> land in it automatically, bulk imports pass a 5-step wizard with a 2MB cap and a 30-country phone dropdown, bulk sends are throttled per team with monthly caps of Free 1, Starter 5, and Pro 25, and every AI reply deducts from a ledger you can audit line by line. The math at the end: 400 inquiries a month becomes 74 extra sales worth $6,290 at an $85 average order value.</p>

<h2>1. The deal that closed on WhatsApp and never reached the sheet</h2>

<p>My team sold the way most small teams in Egypt, Saudi Arabia, and the UAE sell: the conversation happened on WhatsApp, the customer said done, and the rep moved to the next chat. The sheet was supposed to be updated after the shift. It never was, at least not reliably. My audit of one month found 11 closed deals with no sheet row, 6 sheet rows with no matching conversation, and 3 customers re-pitched for products they had already bought.</p>

<p>The standard advice is to buy a CRM. The honest quote I got for a HubSpot-plus-WhatsApp-BSP stack was roughly <strong>$950 a month</strong> once seats, conversation charges, and template surcharges stacked up. OT1-Pro Pro is <a href="https://ot1-pro.com/pricing"><strong>$79 a month</strong></a>. For a team of 3 to 30 people selling through chat, a sheet fed automatically covers 90% of the morning check: who closed, for how much, from which channel, and who still needs a nudge. The follow-up case is in our <a href="https://ot1-pro.com/lead-follow-up-software">lead follow-up software</a> breakdown. I stopped asking reps to remember the sheet and made the close event write the row itself.</p>

<h2>2. Closed deals reach Google Sheets with no Zapier in between</h2>

<p>The implementation lives in the <strong>AI-config Connectors tab</strong>. You paste the operator sheet URL once, map the columns once, and the system takes over. No Zapier scenario that silently pauses at a task limit, no nightly CSV export somebody must remember. The push is event-driven and fires the moment state changes in the inbox.</p>

<p>Every push funnels through one choke point, <code>SalesConnectors::notify</code>, after an early version with three call sites formatting payloads three slightly different ways taught me a lesson. Two hooks can trigger a sheet push, and both call the same notifier:</p>

<ol>
<li><strong>The Conversation hook</strong> fires when a conversation outcome flips to closed, carrying the conversation ID, team, channel, closer, quoted value, and the buying-signal message.</li>
<li><strong>The Contact hook</strong> fires when the contact record changes commercially: a new lead captured, a phone confirmed, a duplicate merged, a value tier assigned.</li>
</ol>

<p>The notifier decides which operator sheets subscribed to the event and delivers the row. If the sheet API is down, deliveries queue and replay in order, so the sheet stays a truthful timeline of what closed and when.</p>

<h2>3. Two events only: EVENT_DEAL_CLOSED and EVENT_LEAD_CAPTURED</h2>

<p>The first version pushed everything and operators muted the tab within a week. I cut the surface to exactly two events an owner opens the sheet to check:</p>

<ol>
<li><strong>EVENT_LEAD_CAPTURED</strong> fires the moment a buyer is identified: name plus a verified phone or email, source channel, first intent line, and the opening qualification score. Top of funnel, in the sheet while the lead is still warm.</li>
<li><strong>EVENT_DEAL_CLOSED</strong> fires when the outcome flips to won: contact, final value, product or package, channel, closer or AI-agent attribution, plus a deep link back to the conversation for one-click audit.</li>
</ol>

<p>Two events delivered reliably beat twenty delivered noisily. The deep link is the detail I am proudest of: when my accountant asked about a strange $85 row, one click showed the thread, the payment confirmation, and the AI summary. If your team handles objections in chat before the close, pair this with our <a href="https://ot1-pro.com/blog/ai-sales-agent-objection-handling-playbook">AI sales agent objection-handling playbook</a>, the sibling guide to this post.</p>

<h2>4. The Excel import that used to corrupt phone numbers</h2>

<p>Every team owns a graveyard Excel file: 800 numbers from a fair booth, 2,000 emails from an old store system. Hand it to the most junior rep with a broadcast tool and the sender reputation dies within a week. I rebuilt our importer around the three failure modes support tickets kept showing me.</p>

<p><strong>Scientific notation.</strong> Excel renders an 11-digit number like 201026361218 as <code>2.011E+11</code> the moment the column is General format. Import that literally and you message a dead number or a stranger. Our importer detects sci-notation cells and stops with a plain instruction: re-export the column as Text and re-upload. It refuses to guess. I would rather reject your file than burn your delivery rate on 400 corrupted rows.</p>

<p><strong>Mystery file sizes.</strong> Somebody always uploads a 40MB export with pivot caches, the worker times out, and half the list sends. The cap is explicit before you pick a file: <strong>2MB</strong>. Bigger files get split or trimmed. For the genuine edge case, an over-cap file shows a founder WhatsApp escape hatch, a direct link to message me so I can split it manually. It gets used about twice a month.</p>

<p><strong>Country-code roulette.</strong> A list mixing Egyptian, Saudi, and UAE numbers with no country column is a delivery disaster. The importer forces a <strong>30-country dropdown</strong> default per import, validates every row against that country's digit pattern, and surfaces <strong>skipped and invalid counts</strong> before anything sends, each downloadable as its own CSV. Seeing 1,740 valid, 183 invalid, 77 skipped duplicates before spending one credit changes the decisions you make.</p>

<h2>5. The 5-step bulk wizard: Upload, Map, Compose, Test, Launch</h2>

<p>The async importer runs identically for the <strong>WhatsApp wizard and the Email wizard</strong>. Five steps in fixed order, no skipping:</p>

<ol>
<li><strong>Upload.</strong> Drop the Excel file. The 2MB cap checks instantly, sheet names list, you pick the contacts tab. Parsing runs in a background job, so a 1,700-row file never freezes your browser.</li>
<li><strong>Map.</strong> Match columns to name, phone or email, country, and up to three custom fields. The sci-notation guard and 30-country digit check run here, with per-row failure reasons in plain language.</li>
<li><strong>Compose.</strong> Write the message with field placeholders. Unresolved placeholders fall back to a neutral default you approve here, never a raw tag leaking into a customer message.</li>
<li><strong>Test.</strong> The wizard sends to your own number and inbox first. This step is mandatory since the day a founder sent Hi {FIRST_NAME} to 900 people. No test success, no launch.</li>
<li><strong>Launch.</strong> The job fans out under the per-team throttle. Valid, sent, delivered, replied, and failed counts accumulate live, with skipped and invalid counts preserved for the audit trail.</li>
</ol>

<p>Async is load-bearing. Closing the tab mid-launch does not stop it, and a queue restart resumes from the last acknowledged row instead of re-sending from zero.</p>

<h2>6. The throttle that costs me signups and saves accounts</h2>

<p>Every bulk launch runs under a <strong>per-team throttle</strong> with <strong>30 to 60 seconds of jitter</strong> between batches, plus hard <strong>plan-tier monthly bulk limits: Free 1, Starter 5, Pro 25 campaigns per month</strong>. When a Free founder asks me to let just one extra campaign through, I say no. WhatsApp and Gmail both punish burst sending: a new number firing 800 messages in 9 minutes reads as spam infrastructure no matter how legitimate the list. The 30-to-60-second jitter paces traffic like human operations. The monthly caps force first-time bulk senders to clean the list, run the Test step, and read the first campaign's reply rate before firing a second. Pro's 25 campaigns is generous once a list is clean; Free's single campaign is a deliberate training wheel.</p>

<p>I publish this comparison from my own bills: <strong>$79-a-month Pro against the roughly $950-a-month HubSpot-plus-BSP stack</strong> with per-message surcharges. The BSP stack lets you blast faster, and that speed is the risk. Four teams I onboarded arrived with a banned BSP number and a 40,000-row list they feared touching. All four now send slower, smaller, cleaner campaigns from OT1-Pro with higher reply rates. If you are comparing bulk-first tools, read our <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs WATI breakdown</a> before committing to a platform whose revenue grows when you over-send.</p>

<h2>7. The AI credit ledger: every credit carries a receipt</h2>

<p>Every AI action, a reply sent, a thread analysis, a qualification score, a follow-up composed, deducts from a per-team balance, and every deduction writes a row to a <strong>ledger</strong> you can open and read. Two entry types cover nearly everything: <strong>Message</strong> for per-reply work and <strong>DeepAnalysis</strong> for heavier thread-level reasoning. Each row shows the conversation, the action, the serving model chain, and the cost. When a founder asks where 300 credits went in April, I open their ledger and walk through it line by line.</p>

<p>Three interface decisions keep it honest. The <strong>meter chip</strong> in the header shows the live balance everywhere, so spending is visible before it happens. Any single action burning <strong>more than 5 credits pops a confirmation modal</strong> with the exact cost and a cancel button; heavy analyses of 200-message threads are the usual trigger. And the ladder is a published <strong>4-tier</strong> structure with top-ups through <strong>manual bank-transfer payments</strong> reviewed by a human, not an auto-charging card. MENA founders asked for this explicitly: cards fail and limits trigger, and nobody wants an AI agent holding an open line to their Visa. Each transfer shows reference number, approval state, and credited amount, all reconcilable against the ledger.

<p>All three controls are scar tissue from one weekend when silent deductions let a looping automation re-analyze the same thread 400 times and burn a month of quota. The ledger, the meter chip, and the over-5-credit modal exist so that weekend never repeats: you see each deduction before it lands, approve the expensive ones explicitly, and audit everything afterward.</p>

<h2>8. The self-healing model pool behind every credit</h2>

<p>Credits stay trustworthy only while the models behind them stay alive. OT1-Pro does not call one model; it calls a pool through NaraRouter, with a nightly job refreshing the available list from the live <code>/v1/models</code> endpoint. There are <strong>no hardcoded model names</strong> in the serving path, because hardcoded names are how you wake up to a dead provider: the vendor renames a model, the pinned string 404s, and every customer message fails until a human notices. Our pool serves only what the endpoint actually returned that night.</p>

<p>When the whole pool exhausts at once, quota drained or upstream outage, it enters a <strong>30-minute global cooldown</strong>, one timestamp the fleet reads in microseconds. <code>SendAiResponse</code> honors it directly: instead of hammering a dead pool with retries that burn quota and latency, the job <strong>releases itself onto the queue with delay plus jitter, bounded by tries = 2</strong>. The message waits calmly instead of failing loudly, then gets its two fair attempts after the window. Thirty minutes matches the recovery profile from three real NaraRouter incidents: shorter windows re-entered a still-dead pool, longer ones held replies hostage. Credits deduct only for work performed, never for retries against a dead pool. If Meta-side OAuth pain is your current fire instead, start with our <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta app verification founder guide</a>, the most-read thing I have written for a reason.</p>

<h2>9. The math: 400 inquiries, 74 extra sales, $6,290 a month</h2>

<p>A typical month for a small store or clinic on WhatsApp and Instagram ads: <strong>400 inbound inquiries</strong>. Answering manually in working hours closes about 12%, or 48 sales, because nights, Fridays, and the ghost-after-price pattern eat the rest. With instant AI first reply, the 3-touch follow-up sequence, objection handling, and every close pushed to the sheet so nothing slips, the same 400 inquiries close 122 sales. That is <strong>74 extra sales</strong>, and at an <strong>$85 average order value</strong>, 74 × 85 = <strong>$6,290 per month</strong> in recovered revenue against a $79 Pro subscription.</p>

<table>
<thead>
<tr><th>Monthly inquiry volume</th><th>Manual handling hours</th><th>Automated cost on OT1-Pro</th></tr>
</thead>
<tbody>
<tr><td>100 inquiries</td><td>~9 hours of rep time</td><td>$0 extra on Free (1 bulk/mo included)</td></tr>
<tr><td>400 inquiries</td><td>~36 hours of rep time</td><td>$79 Pro, 25 bulk/mo, ledger included</td></tr>
<tr><td>1,500 inquiries</td><td>~135 hours of rep time</td><td>$79 Pro, same cap, throttle protects sender</td></tr>
<tr><td>5,000 inquiries</td><td>~450 hours of rep time</td><td>$79 Pro + credit top-ups at ladder rates</td></tr>
</tbody>
</table>

<p>Manual hours assume 5 to 6 minutes of human attention per inquiry across first reply, qualification, follow-ups, and sheet entry; at 400 inquiries that is ~36 hours, nearly a full work week the connector, wizard, and agent now absorb. Cost stays flat at $79 through 1,500 inquiries because the throttle and ledger scale with queue depth, not headcount. Against the $950 HubSpot-plus-BSP stack, payback lands in month one: $6,290 recovered against $79 spent.</p>

<h2>10. What I would do on Monday morning</h2>

<p>The exact onboarding order I walk new teams through, under an hour:</p>

<ol>
<li><strong>Connect the sheet first.</strong> In the AI-config Connectors tab, paste the operator sheet URL and confirm one <code>EVENT_LEAD_CAPTURED</code> test row lands, then close a test conversation and confirm the <code>EVENT_DEAL_CLOSED</code> row with its deep link.</li>
<li><strong>Import the smallest list, not the biggest.</strong> Run a 200-row file through Upload, Map, Compose, Test, and Launch on Free's single monthly bulk. Read the skipped and invalid counts, fix the source file, and watch the second campaign beat the first on reply rate.</li>
<li><strong>Review the ledger every Friday.</strong> Five minutes: check the Message-to-DeepAnalysis ratio, confirm no thread is looping analyses, approve over-5-credit modals deliberately.</li>
<li><strong>Let the pool heal itself.</strong> If replies ever pause fleet-wide, check the cooldown state before touching settings. The 30-minute window is usually already counting down and messages drain with their two tries intact.</li>
<li><strong>Measure the 74.</strong> Count closes from the sheet at month end, multiply extras by your real average order value, compare against $79. The system earns its keep at almost any AOV above $20 because recovered deals compound monthly.</li>
</ol>

<p>I started OT1-Pro to stop losing deals between the chat and the sheet. The Connectors tab, the throttled bulk wizard, and the transparent credit ledger closed that gap for my own team, and the self-healing pool keeps them alive overnight. Start free, connect one sheet, import one small list, and watch the ledger. <a href="https://ot1-pro.com/register"><strong>Start free today →</strong></a></p>

{{CTA}}
HTMLP5,
                'meta_title'        => 'Google Sheet CRM Sync for WhatsApp Sales',
                'meta_description'  => 'Closed WhatsApp deals hit Sheets automatically, every AI credit is receipted in a ledger, bulk Excel stays throttled and safe with google sheet crm sync',
                'category'          => 'Automation',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '13 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 6. ai-deep-analysis-3000-contacts-0-credit-rerun-ar (ar) ──
            [
                'title'   => 'حللت 3000 كونتاكت بجملة واحدة: التحليل العميق وكاش 24 ساعة بـ0 كريديت',
                'slug'    => 'ai-deep-analysis-3000-contacts-0-credit-rerun-ar',
                'excerpt' => 'انا عمر مؤسس OT1-Pro وبحكيلك ازاي حللت 3000 كونتاكت بجملة واحدة بس تشغيلة بتقرا 1000 كونتاكت في المرة وتطلع الاعتراضات والمتابعة بعد السعر بلغة عادية والكاش بيخلي اعادة القراءة خلال 24 ساعة بـ0 كريديت من غير ما تدفع مليم زيادة على الفاضي',
                'content' => <<<'HTMLP6'
<p><strong>انا عمر، مؤسس OT1-Pro، وكان عندي 3000 كونتاكت في الانبوكس بتاعي وانا مش فاهم ولا واحد فيهم عايز ايه.</strong> 3000 صف، تاجات في كل حتة، نص الشاتات بالمصري، متابعات بتقع مني، وانا بقعد اسكرول زي عالم آثار. وفي ليلة كده كتبت الجملة اللي عميل بعد كده كتبها في المنتج بتاعي بالنص: "اقرا الـ3000 كونتاكت وحللهم". الجملة دي بقت فيتشر شغالة فعلا: تشغيلة بتقرا لحد 1000 كونتاكت في المرة، بتكتب النتايج بلغة عادية مفهومة، والكاش بيخلي اعادة القراءة خلال 24 ساعة بـ0 كريديت، والمتصفح بيبعتلك تنبيه لما التقرير يخلص. في المقال ده هحكيلك بتشتغل ازاي، بكام بالظبط، والـ3 غلطات الغاليين اللي شكلوها.</p>

<p>لو بتبيع على واتساب وماسنجر، فانت عارف الوجع ده كويس. التوصيل نفسه معركة لوحده، وانا كتبت تاريخها كله في <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">دليل توثيق تطبيقات ميتا 2026 من مؤسس عدى بالطريق ده</a>. وانك تخلي الذكاء الاصطناعي يتابع كويس دي معركة تانية، واتكلمت عنها في دليل المتابعة وفي مقارنة <a href="https://ot1-pro.com/vs/wati">OT1-Pro ضد WATI</a>. المقال ده بقى الطبقة اللي بعد كده: بعد ما الرسايل بقت ماشية، ازاي تفهم آلاف الكونتاكتس من غير ما تقراهم كل يوم بالسعر الكامل؟ ولو بتقارن اسعار، بص على <a href="https://ot1-pro.com/pricing">صفحة اسعار OT1-Pro</a> عشان تفهم حسبة الكريديت اللي هشرحها تحت.</p>

<h2>انا كتبت "اقرا الـ3000 كونتاكت وحللهم" في المنتج بتاعي</h2>

<p>انا عمر، والفيتشر دي بدأت عندي كتيكت دعم. عميل كان مستورد حوالي 3000 كونتاكت من شيتين قدم وشوية شاتات ماسنجر بتوع 3 شهور، وكتب لشات الذكاء الاصطناعي جوه OT1-Pro: "اقرا الـ3000 كونتاكت وحللهم". من غير مينيو، من غير صفحة تقارير، بس بلغة عادية زي ما كنت هتطلب من مدير مبيعات شاطر.</p>

<p>اول نسخة عندي عملت ولا حاجة بالجملة دي. اعتبرتها شات عادي وطلعت فقرة واثقة من نفسها عن "تنويع التواصل". كلام فاضي وملوش لازمة. الداتا بتاعة الاجابة الحقيقية كانت موجودة في الداتابيز بتاعة التيم بتاعه: التاجات، والهيستوري، والتواريخ، والملاحظات. بس الذكاء الاصطناعي مكانش عنده كوبري بين الجملة العادية دي وبين شغلانة على مستوى الكوهورت كله.</p>

<p>فعملت الكوبري ده بنفسي. النهاردة لو كتبت "اقرا الـ3000 كونتاكت وحللهم" او "حلل كل الليدز بتوعي" او "اعملي مراجعة لكل المحادثات بتاعة الشهر اللي فات"، الديتكتور بيلقطها، وبيطلعلك كارت تأكيد، وضغطة واحدة بتشغل التحليل في الخلفية. ثيمات بلغة عادية، عدد الاعتراضات، خطوات جاية، مش تفريغ CSV وخلاص. وكل اللي بحكيه هنا شغال في البرودكشن فعلا، مش خطة على ورق.</p>

 

<p>اللي بيميز الطريقة دي انك مش محتاج تتعلم تول جديدة. مفيش كورس، مفيش 20 زرار. بتتكلم عادي، والسيستم هو اللي بيفهم ان دي شغلانة كبيرة ومحتاجة تشغيلة في الخلفية. ودي الفلسفة بتاعتي كلها في OT1-Pro: البياع يتكلم عربي عادي، والمنتج يشيل التعقيد.</p>

<h2>الديتكتور بيشتغل ازاي وليه حطيت حد الـ20 كونتاكت</h2>

<p>القاعدة مقصودة تبقى ضيقة: سهل تشغلها وانت بتتكلم عادي، وصعب جدا تشتغل بالغلط. الديتكتور مش بيضرب غير لما 3 شروط يتحققوا مع بعض:</p>

<ol>
<li><strong>فعل تحليل موجود.</strong> كلمات زي حلل، راجع، لخص، قيم، تقرير، انسايتس، باترن، ثيمات. كلمة "اقرا" لوحدها مش كفاية. "اقرا الـ3000 كونتاكت" لوحدها مش بتشغل، لكن "اقرا الـ3000 كونتاكت وحللهم" بتشغل عشان فيها فعل التحليل.</li>
<li><strong>اسم مجموعة موجود.</strong> كلمات زي كونتاكت، ليدز، عملا، محادثات، شاتات، بايبلاين، مشتركين. ودي بتمنع ان "حلل الرد ده" تشغل شغلانة 1000 كونتاكت وانت قصدك رسالة واحدة.</li>
<li><strong>اشارة حجم موجودة.</strong> يا رقم صريح 20 او اكتر ("حلل الـ300 كونتاكت بتوعي")، يا كلمة شاملة ("كل"، "جميع"، "كامل"). تحت الـ20 كونتاكت الرد العادي جوه الشات بيخلصها، ومن 20 وطالع السيستم بيعرض التشغيلة العميقة.</li>
</ol>

<p>ليه 20؟ انا جربت 10 و20 و50 على 3 شهور من لوجات الشات. عند 10، الديتكتور كان بيضرب على اسئلة زي "حلل الـ12 رد دول"، ودي شغلانات بتخلص في ثواني جوه الشات وملهاش لازمة تدخل الكيو. وعند 50، طلبات حقيقية زي "حلل الـ30 ليد بتوع المعرض" كانت بتعدي وبتاخد اجابات ضعيفة جوه الشات. الـ20 كانت النقطة المظبوطة. وفرع "كل" بيغطي اكتر صياغة شائعة واللي مفيهاش رقم: "حلل كل الكونتاكتس بتوعي".</p>

<p>وحاجة كمان مهمة: الديتكتور بيشتغل في شات الذكاء الاصطناعي الداخلي بتاع التيم بس، عمره ما بيشتغل في محادثة مع عميل. عميل كتب لبوت المبيعات "حلل كل منتجاتكم" عمره ما هيشغل شغلانة كوهورت.</p>

 

<h2>بعد الكشف: الوضعين والـ1000 كونتاكت لكل تشغيلة</h2>

<p>لما الكشف بيحصل، بتشوف كارت تأكيد، مش خصم فوري، مكتوب فيه حجم المجموعة والوضع وسعر الكريديت. فيه وضعين شغالين:</p>

<ol>
<li><strong>customer_themes.</strong> اهم الاعتراضات، اشارات الشراء، خلطة اللغة عربي وانجليزي، باترن الليدز الميتة، و3 خطوات جاية. ده الوضع اللي عميل الـ"اقرا الـ3000" كان عايزه.</li>
<li><strong>agent_audit.</strong> باترن سرعة الرد، الفجوات في المتابعة، الشاتات اللي ماتت بعد سؤال السعر، وانهي ايجنت بيقفل وانهي بيضيع. لو customer_themes بيسأل "البياعين عايزين ايه؟"، فـagent_audit بيسأل "البروسيس بتاعتنا بتسرب منين؟".</li>
</ol>

<p>الوضعين بيشتغلوا كجوبز في الخلفية: بتتوزع على دفعات، كل دفعة بتنده سلسلة الذكاء الاصطناعي، وبعدين بتتجمع في تقرير واحد. كل تشغيلة بتغطي لحد 1000 كونتاكت؛ لو الكوهورت 3000، بنحلل الـ1000 الانشط مؤخرا الاول، والويندو مكتوبة في هيدر التقرير. السقف ده مخلي ميموري الكيو ثابتة، وسلسلة الـNaraRouter جوه الميزانية، والتقرير مقروء. والمجموعات الكبيرة الاحسن تقسمها تشغيلة لكل سيجمنت ("الـ800 ليد بتوع المعرض" وبعدين "الـ900 بتوع الويبسايت") وهتاخد اجابات احسن من تشغيلة عملاقة واحدة.</p>

<p>وهنا OT1-Pro بيختلف عن منتجات البايبلاين الصرف. مقارنة <a href="https://ot1-pro.com/vs/wati">OT1-Pro ضد WATI</a> بتغطي القنوات واسعار البرودكاست، لكن رهان التحليل العميق ان القيمة في انك تعيد قراية الكونتاكتس بتوعك كل اسبوع بسعر رخيص. ولو الاعتراضات هي عنق الزجاجة عندك، فالتقرير ده بيكمل جدا مع <a href="https://ot1-pro.com/blog/ai-sales-agent-objection-handling-playbook-ar">بلاي بوك الرد على الاعتراضات بالعربي</a> اللي كتبته من نفس الداتا.</p>

 

<h2>بكام؟ نظام الكريديت والدفع عند التشغيل</h2>

<p>دي قاعدة البيلينج زي ما هي متسلمة بالظبط: <strong>التشغيلة بتتخصم عند الارسال، الا لو كاش هيت، وساعتها بـ0 كريديت.</strong> الخصم وقت الارسال مش وقت النهاية، عشان مكالمات الذكاء الاصطناعي بتبدأ في ثواني من الارسال، فلو الخصم عند النهاية كانت الجوبات الفاشلة هتبان ببلاش وهي حارقة كوتة فعلا.</p>

<p>الحسبة بالارقام من البرودكشن: تشغيلة customer_themes بتاعة حوالي 1000 كونتاكت بتتوزع لحوالي 11 مكالمة ذكاء اصطناعي (10 دفعات + 1 تجميع)، حوالي 22 كريديت، يعني تقريبا 4.40 دولار من باقة <a href="https://ot1-pro.com/pricing">بلان البرو بـ79 دولار في الشهر</a>. يوزر واحد داس تأكيد مرتين ورا بعض كان بيشغل تشغيلتين متطابقتين: 22 مكالمة ضايعة، حوالي 8.80 دولار قيمة كريديت راحت، وتقريرين متطابقين ورا بعض بدقيقة. والاوحش، انا قست سيشن فيها 4 محاولات مدفوعة متطابقة اثناء عطل في البروفايدر: 44 مكالمة ضايعة، حوالي 17.60 دولار اتحرقوا من غير اي معلومة جديدة. الارقام دي هي اللي فرضت القفل والكاش.</p>

<p>التشغيلات الفاشلة مش بتاكل الكريديت في صمت. لو كل البروفايدرز واقعة والجوب رمى AiAllProvidersUnavailable، التشغيلة بتتعلم فاشلة ومسار اعادة المحاولة بيبص على سجل الفشل الاول، مفيش خصم لاعادة نفس المجموعة. اعطال الكوتة والاوتاج بترمي استثناءات محددة سياسة اعادة المحاولة فهماها، واي حاجة تانية بترجع فاضي بدل ما تخصم على زبالة.</p>

<p>انا عارف ان كلمة "كريديت" بتخوف ناس كتير. فخليني اقولها بالمصري: انت مش بتدفع على كل رسالة، انت بتدفع على الشغلانة التقيلة بس. والقراية المتكررة من الكاش ببلاش خالص. يعني عادة يومية تفتح التقرير الصبح من غير ما تبص على العداد.</p>

<p>والمتابعة بعد السعر بالذات هي اللي بتستاهل الفلوس دي. اكتر جملة بتقتل الديلات عندنا كلنا: العميل يسأل "بكام؟" ونرد بالسعر ونسكت. التقرير بيطلعلك الشاتات دي بالاسم: مين سأل على السعر ومحدش تابعه. انا لقيت عندي 180 شات ميتين بعد السعر. تخيل 180 واحد كانوا جاهزين يشتروا ومحدش كلمهم تاني. التشغيلة اللي طلعتلي الرقم ده كلفتني 22 كريديت. لو قفلت 5 ديلات منهم بس، جابت تمنها 100 مرة.</p>

<h2>الكاش 24 ساعة بـ0 كريديت: ازاي معمول بالظبط</h2>

<p>الكاش هو الفيتشر اللي العميل بيحس بيها. حللت مجموعة في آخر 24 ساعة ومفيش حاجة اتغيرت؟ اعادة التشغيل بـ0 كريديت وبترجع في اقل من ثانية:</p>

<ol>
<li><strong>توحيد الفلتر.</strong> ايدي التيم، الوضع، التاجات، ويندو التواريخ، نص البحث، وسقف الكونتاكتس بيتسيريالايزوا بمفاتيح مترتبة وقيم متنورملايزد، التاجات بتتوطى وتترتب، والتواريخ بتتقطع للدقيقة. "تاجات: VIP, expo" و"تاجات: expo, vip" بيتوحدوا لنفس الشكل. من غير ده، طلبين متطابقين منطقيا بيتهاشوا مختلف والكاش عمره ما بيضرب.</li>
<li><strong>الهاش.</strong> النص الموحد بيتهاش بـsha256، ومتسكوب على التيم + الوضع + الهاش. "ليدز المعرض" بتاعة التيم A عمرها ما بتتسرب للتيم B، ونتيجة customer_themes عمرها ما بتتمثل انها agent_audit.</li>
<li><strong>ويندو 24 ساعة.</strong> الاحدث من 24 ساعة يبقى هيت: 0 كريديت وتسليم فوري. الاقدم يبقى ميس: خصم كامل. الويندو قصيرة عن قصد، المجموعات بتبوظ بسرعة، وانا افضل اخصم على داتا فريش بدل ما تيم ياخد قرار على اعتراضات ميتة.</li>
</ol>

<p>تفصيلة الترتيب دي كلفتني عصر debugging كامل. اول تنفيذ كان بيهاش مصفوفة الريكويست الخام، فترتيب المفاتيح كان بيفرق: كارت التأكيد كان بيبني الفلتر {تاجات، تواريخ، وضع} ولينك اعادة التشغيل بيبنيه {وضع، تاجات، تواريخ}، والهاشات عمرها ما اتطابقت. نسبة الهيت كانت 0% لمدة اسبوع. ترتيب المفاتيح قبل الهاش نقل نسبة الهيت من 0% لحوالي 60% بين ليلة وضحاها للتيمات النشيطة يوميا.</p>

<p>وحسبة الكاش بالدولار: تيم بيبص على نفس تحليل "كل الليدز" كل صبح، تشغيلة فريش يوم الاتنين (4.40 دولار قيمة كريديت)، واعادة قراية من الكاش من التلات للحد بـ0 كريديت، بيصرف 22 كريديت في الاسبوع بدل 154. على باقة البرو الـ79 دولار، ده الفرق بين ان التحليل يبقى عادة يومية وبين انه يبقى رفاهية.</p>

 

<h2>شكل الـ"كاش بـ0 كريديت" قدامك في الشاشة</h2>

<p>الكاش هيت عمره ما بيدعي انه داتا فريش. الرسالة شايلة حبة خضرا زمردي "كاش · 0 كريديت"، وتوقيت التشغيلة الاصلية، وتعريف المجموعة ("customer_themes · 1000 كونتاكت · تاجات: expo, vip · تشغيلة 2026-10-08 09:14 UTC"). وتحتها لينك "اعادة التشغيل بداتا فريش" اللي بيشغل من جديد بـforce_fresh=true: خصم كامل، قراية فريش، صف كاش جديد. الداتا البايتة مقبولة لما تبقى متعلمة، لكن البايتة اللي بتدعي انها فريش بتقتل الثقة.</p>

<p>انا جربت 3 نسخ من الواجهة دي. النسخة الاولى مفيهاش حبة، بس التقرير. العملا افتكروه فريش وبعدين اشتكوا ان الارقام ناقصة امبورت امبارح. النسخة التانية فيها ملحوظة رمادي صغيرة "كاش". محدش قراها. النسخة التالتة، الحبة الزمردي plus التوقيت plus لينك اعادة التشغيل الصريح، نزلت شكاوى الكاش لصفر. الاخضر معناه "دي وفرتلك فلوس"، والتوقيت بيقول قديمة قد ايه، واللينك معناه "الفريش على ضغطة".</p>

<p>الباراميتر force_fresh=true بيتجاوز بحث الكاش وقفل منع التكرار مع بعض، اليوزر بيقول "انا غيرت الداتا، اخصم مني واقرا من جديد". الامبورتات، وتغييرات التاجات الجماعية، و"لسه ضايفين 200 كونتاكت معرض" هما الـ3 محفزات الشرعية في اللوجات. اي حاجة تانية المفروض تخبط في الكاش.</p>

<p>النقطة دي بالذات فرقت معايا في حاجة اسمها كاش عند الاستلام. العميل المصري بيحب يستلم ويعاين قبل ما يدفع، واحنا عملنا نفس الفلسفة في الكاش: استلم التقرير الاول، عاينه، ولو عجبك والداتا اتغيرت ابقى ادفع الفريش. مفيش دفع اعمى.</p>

<h2>الغلطة اللي خلتني اعمل قفل منع التكرار</h2>

<p>قبل القفل، التشغيلات المدفوعة المكررة كانت اكتر بج تحرجني في الكريديت. السيناريو دايما واحد: اليوزر يدوس تأكيد، الجوب بياخد 30-90 ثانية، اليوزر ميشوفش نتيجة فورية، يدوس تأكيد تاني، او يعيد كتابة "حلل كل الكونتاكتس" ويأكد الكارت "الجديد". جوبين بيتبعتوا، كل واحد بيخصم عند الارسال، كل واحد بيوزع لحوالي 11 مكالمة ذكاء اصطناعي. يعني 11 مكالمة ضايعة على الاقل لكل تشغيلة مكررة، 22 كريديت مدفوعين مرتين، وتقريرين شبه متطابقين بيحسسوا العميل ان البيلينج بينصب عليه.</p>

<p>الحل قفل idempotency على نفس مفتاح التيم + الوضع + هاش sha256 بتاع الكاش، ممسوك من ضغطة التأكيد لحد الارسال. التأكيد التاني لنفس المجموعة وهي شغالة مش بيبعت، بيرجع ايدي الجوب الشغال مع تنبيه "التحليل ده شغال فعلا". والقفل بيexpire بعد 10 دقايق عشان جوب ميت ميبلوكش مجموعته للابد. ومن ساعة ما سلمناه، التشغيلات المكررة بقت صفر فعليا، بعد ما كانت حوالي 12 في الاسبوع.</p>

<p>الدرس: اي زرار بيصرف فلوس وبياخد اكتر من 3 ثواني محتاج قصة idempotency قبل ما يتسلم، مش بعد. القفل كان 40 سطر كود. والريفندات اللي استبدلها كانت ساعات من عمري كل شهر. والتيمات الجديدة تقدر تجرب اللوب كله ببلاش من <a href="https://ot1-pro.com/register">صفحة التسجيل في OT1-Pro</a>، دوس تأكيد مرتين وشوف القفل وهو بيقفشها.</p>

 

<h2>ليلة ما NaraRouter وقع وحرق 4 تشغيلات مدفوعة</h2>

<p>اسوأ حادثة مفردة مكانتش غلط يوزر، كانت عطل بروفايدر plus منطق اعادة المحاولة بتاعي. اثناء brownout في NaraRouter في سبتمبر، سلسلة النص بدأت ترجع 500s على مكالمات الدفعات. سياسة اعادة المحاولة بتاعة الجوب، المستلفة من مسار رد الشات العادي، عادت تشغيل جوب التحليل العميق كله 4 مرات. كل اعادة ارسال كانت بتوزع التوزيعة من جديد، كل توزيعة بتحرق مكالمات على سلسلة الفولباك، والـ4 محاولات فشلوا بنفس الشكل لما الـcooldown العام بتاع NaraRouter النص ساعة اشتغل. اربع تشغيلات مدفوعة متطابقة، صفر تقارير، وعميل غضبان بيتفرج على الكريديت وهو بينزف في عطل هو مسببهوش.</p>

<p>3 تغييرات طلعوا من الليلة دي. اول حاجة، جوبات التحليل العميق بقت تبص على حالة الـcooldown العام قبل ما توزع الدفعات: لو الـcooldown النص ساعة بتاع NaraRouter شغال، الجوب بيستنى بـbackoff بدل ما يحرق مكالمات عارف انها هتفشل. تاني حاجة، فشل مستوى الدفعة بيعيد الدفعة الفاشلة بس، مرة واحدة، ضد الموديل اللي بعده في السلسلة، عمره ما بيعيد التشغيلة كلها. تالت حاجة، التشغيلة اللي بتفشل بـAiAllProvidersUnavailable على كل الدفعات بتتعلم فاشلة من غير محاولات تلقائية زيادة، والتيم بيشوف "البروفايدرز مش متاحة، اعادة المحاولة ببلاش لما الخدمة ترجع". نفس فلسفة قاعدة حماية شقيقة: ايرور ميتا 2018278 بره الـ24 ساعة بيرفض يصرف على رد واتساب المنصة هترفضه.</p>

<p>ومن ساعة الفيكس: صفر تكرار في 6 اسابيع. فحص الـcooldown لوحده امتص brownoutين بعد كده: الجوبات وقفت، الـcooldown خلص، التشغيلات كملت متأخر بس صح، ومحدش اتخصم عليه تمن الانتظار.</p>

<p>الليلة دي علمتني ان اعادة المحاولة الغبية اغلى من الفشل نفسه.</p>

<h2>التنبيه في المتصفح وفخ البانر القديم</h2>

<p>جوب 60-90 ثانية محتاج اشارة احسن من "افضل اعمل ريفريش". لما التشغيلة بتخلص، السيرفر بيبث ايفنت DeepAnalysisCompleted على Reverb في القناة البرايفت بتاعة التيم. اللياوت بتاع الانبوكس بيسمع وبيضرب تنبيه متصفح: العنوان، الوضع، حجم المجموعة، وضغطة توصلك للتقرير. التاب المفتوحة؟ بيبدل بانر "شغال" بالتقرير جوه الصفحة.</p>

<p>ووضع الفشل اللي عضني: البانر البايت لما ايفنت Reverb ميوصلش. شبكات الشركات، والاد بلوكرز، ونسخة سفاري واحدة مشهورة كلهم بيسقطوا الويب سوكيت في صمت: الايفنت بيتبث صح لكن مبيوصلش، واليوزر متنح لـ"التحليل شغال…" للابد بينما التقرير المخلص قاعد في الداتابيز. فيكس من جزئين: كل بانر "شغال" بيpol حالة الجوب كل 15 ثانية (اسوأ حالة تأخير 15 ثانية مش ما لا نهاية)، وكل بانر شايل توقيت: "شغال من 09:14:22 UTC"، فاي حاجة اقدم من 5 دقايق بتبان مريبة.</p>

<p>والاب بيطلب اذن التنبيهات بس لما اليوزر يأكد اول تشغيلة، اللحظة اللي القيمة فيها واضحة. عند التسجيل كانت نسبة القبول 11%، وعند ضغطة التأكيد 64%.</p>

<p>انا كنت الاول بطلب الاذن عند التسجيل زي كل الناس، والنسبة كانت فضيحة. لما نقلته للحظة التأكيد، اليوزر فاهم هو بيوافق على ايه: "وافق عشان ننبهك لما تحليل الـ1000 كونتاكت يخلص". السياق هو اللي بيبيع الاذن، مش الزن.</p>

<h2>امتى التحليل العميق يستاهل على بلان الـ79 دولار</h2>

<p>محاسبة امينة، بالجدول الحي بتاع الكريديت (اكتوبر 2026) وباقة <a href="https://ot1-pro.com/pricing">البرو بـ79 دولار</a>:</p>

<table>
<thead>
<tr><th>النمط</th><th>تشغيلات / اسبوع</th><th>كريديت</th><th>قيمة الكريديت</th><th>الحكم</th></tr>
</thead>
<tbody>
<tr><td>تشغيلة فريش 1000 كونتاكت يوميا من غير كاش</td><td>7</td><td>154</td><td>حوالي 30.80 دولار</td><td>تبذير</td></tr>
<tr><td>فريش الاتنين + اعادة قراية من الكاش</td><td>1 مدفوعة + 6 كاش</td><td>22</td><td>حوالي 4.40 دولار</td><td>المرشح</td></tr>
<tr><td>دوسة مزدوجة مكررة (قبل القفل)</td><td>2 مدفوعة لكل نية</td><td>44</td><td>حوالي 8.80 دولار ضايعة</td><td>اتصلح بالقفل</td></tr>
<tr><td>4 محاولات مدفوعة في عطل (قبل الفيكس)</td><td>4 مدفوعة وصفر تقارير</td><td>88</td><td>حوالي 17.60 دولار ضايعة</td><td>اتصلح بفحص الـcooldown</td></tr>
<tr><td>متقسمة: تشغيلتين فريش + كاش</td><td>2 مدفوعة + كاش</td><td>44</td><td>حوالي 8.80 دولار</td><td>الاحسن لـ2000+</td></tr>
</tbody>
</table>

<p>الروتين اللي برشحه لـ1000-3000 كونتاكت: يوم الاتنين شغل customer_themes فريش على اسخن سيجمنت. يوم التلات شغل agent_audit فريش على نفس السيجمنت: الثنائي بيوريك البياعين عايزين ايه والبروسيس بتسرب منين. من الاربع للحد اعيد القراية من الكاش بـ0 كريديت، وforce_fresh بس بعد الامبورتات او تغييرات التاجات الجماعية. المجموع: 44 كريديت في الاسبوع، حوالي 8.80 دولار قيمة، تقريرين مفيدين فعلا plus اعادة قراية ببلاش، وسايب اغلب باقة البرو للمحادثات الفعلية مع العملا، وهي اللي منها الايراد.</p>

<p>ملحوظة اخيرة: تحت الـ20 كونتاكت متعملش تحليل عميق، اسأل شات الذكاء الاصطناعي عادي، ببلاش واسرع. التحليل العميق بيستاهل من 20 وطالع وبيتضاعف من 500 وطالع. ولو لسه بتقارن، <a href="https://ot1-pro.com/vs/wati">مقارنة WATI</a> هتوضحلك فرق الفلسفة، وابدأ من <a href="https://ot1-pro.com/register">التسجيل في OT1-Pro</a> وجربها على داتاك بنفسك.</p>

<p>{{CTA}}</p>
HTMLP6,
                'meta_title'        => 'حللت 3000 كونتاكت بجملة واحدة: تحليل 3000 كونتاكت',
                'meta_description'  => 'انا عمر مؤسس OT1-Pro: حللت 3000 كونتاكت بجملة واحدة، تشغيلة 1000 كونتاكت بلغة عادية وكاش 24 ساعة بـ0 كريديت ومتابعة بعد السعر وكاش عند الاستلام يوميا.',
                'category'          => 'مبيعات',
                'author'            => 'Omar Eltak',
                'language'          => 'ar',
                'is_rtl'            => true,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 7. ai-sales-agent-objection-handling-playbook-ar (ar) ──
            [
                'title'   => 'الرد على اعتراضات العملاء: البلاي بوك اللي رجع 27% من البيعات',
                'slug'    => 'ai-sales-agent-objection-handling-playbook-ar',
                'excerpt' => 'انا عمر بنيت OT1-Pro وراجعت 12847 محادثة فوجدت 73 بالمئة يختفون بعد السعر. أشارك القاعدة رقم صفر وبلاي بوك عشر خطوات وردود Feel-Felt-Found على غالي وهفكر وهسأل وهل يعمل فعلا مع متابعة 24 و72 و7 أيام التي استعادت 27 بالمئة من الأشباح.',
                'content' => <<<'HTMLP7'
<article dir="rtl" lang="ar">
<p>انا عمر، بنيت OT1-Pro عشان يرد على رسايل العملاء، وكنت بقنع نفسي شهور إن البوت شغال كويس. بيرد بسرعة، بيلم أسامي وأرقام تليفونات، وشكله نشيط. لحد ما قعدت في يوم وقريت 200 محادثة حقيقية ورا بعض، وبطني وجعتني. البوت بتاعي مكنش بيبيع. كان بيلم بيانات وبيوقع صفقات.</p>
<p>أوحش واحدة لسه معلمة فيا. عميل كاتب بالمصري: "انا مهتم بالخدمة". البوت رد عليه: "ما هي الخدمة اللي بتقدمها؟". يعني قلب الآية، اعتبر المشتري هو البياع. الراجل مختفاش بس، ده حس إن مفيش حد صاحي على الناحية التانية. الغلطة دي خلتني أرمي البرومبت القديم كله وأكتبه من الصفر كبلاي بوك كلوزر بشري، وسكنته في trait واحدة مشتركة اسمها BuildsConversationPrompts بتخدم NaraRouter وGemini وOllama. كلوزر واحد، مش تلات بياعين مختلفين.</p>
<p>اللي هحكيهولك النهاردة هو البلاي بوك ده بالحرف: القاعدة رقم صفر، والـ 10 خطوات، والرد على الاعتراضات الأربعة اللي بيقف عندها أغلب البياعين، وأرقامي الحقيقية من 12,847 محادثة: 73% بيختفوا بعد السعر، و27% منهم بيرجعوا بسيكوينس متابعة 24 ساعة و72 ساعة و7 أيام. لو بتبيع على ماسنجر وإنستجرام وواتساب، الكلام ده ليك.</p>

<h2>1. البوت بتاعي سأل المشتري: انت بتبيع إيه؟</h2>
<p>انا عمر وبقولها على نفسي عشان تتعلم ببلاش: الرسالة كانت "انا مهتم بالخدمة بتاعتكم". كلمة بتاعتكم يعني بتاعتكم انتوا، واضحة زي الشمس. مفيش بني آدم يفهمها غلط. البوت بتاعنا فهمها إن العميل هو اللي بيقدم خدمة وسأله يعرف نفسه. دي مش غلطة لغوية، دي غلطة هوية. البوت مكنش عارف هو شغال لمين.</p>
<p>ونفس العته كان بيحصل بالإنجليزي. عميل يكتب "I want it" أو "tell me more about your product". البوت القديم يرد "Could you tell me what you are looking for exactly؟". في الشات الصبر بيخلص بعد رسالتين بالكتير. العميل مش هيقعد يشرح، هيقفل ويمشي.</p>
<p><strong>غلط BAD:</strong> العميل: "انا مهتم بالخدمة." البوت: "أهلا! ممكن تعرفني بالخدمة اللي بتقدمها؟". العميل بيحس إن مفيش حد فاهم ويمشي.</p>
<p><strong>صح GOOD:</strong> العميل: "انا مهتم بالخدمة." البوت: "أهلا وسهلا بيك! الخدمة بتاعتنا بترد على عملاءك طول اليوم حتى وانت نايم. تحب تعرف السعر الأول ولا أقولك بتشتغل إزاي؟". منفعة واحدة، وسؤال واحد، والمومنتم مكمل.</p>
<p>انا كتبت القاعدة رقم صفر قبل أي حاجة تانية لأن مفيش حاجة ليها لازمة لو الـ AI فاهم غلط مين بيشتري من مين. ولو شغال على رسايل ميتا، الغلطة دي تمنها أغلى بكتير بسبب حوارات الصلاحيات والمراجعة، وانا موثق السكة دي بالتفصيل في <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">دليل توثيق تطبيق ميتا من تجربتي كفاوندر</a>.</p>

<h2>2. القاعدة رقم صفر: انت شغال للبيزنس واللي بيكتبلك هو المشتري</h2>
<p>القاعدة رقم صفر قاعدة في أول سطر في BuildsConversationPrompts: انت شغال للبيزنس ده. كل واحد بيبعتلك رسالة هو عميل محتمل. مش مورد، ومش جاي يعرض عليك حاجة. هو جاي يشتري منك.</p>
<p>الـ trait فيها ليستة عبارات بالعربي والإنجليزي بتفعل القاعدة دي: "I am interested" و"I want this" و"your product" و"tell me more" و"انا مهتم" و"عايزها" و"الخدمة" و"المنتج" و"بتاعتكم" و"عندكم". لو أي واحدة ظهرت، التعليمات واضحة: بيع منفعة واحدة. وسؤال "ما هي الخدمة اللي بتقدمها؟" ممنوع منعا باتا. انا خلتها ban مش نصيحة، لأن التوضيح المؤدب برضه بيموت البيعة.</p>
<p>فيه تلات استثناءات بس: أولا بعد ما نكون بعنا المنتج بتاعنا مرتين على الأقل، ثانيا لما سؤالنا عن البيزنس بتاعه هيظبط البيتش، زي "انت بتبيع إيه؟" عشان نربط الفيتشر الصح بالاستخدام بتاعه، ثالثا لما هو بنفسه يطلب مننا نفهم احتياجه الأول. بره التلاتة دول، السؤال عن البيزنس بتاعه يعتبر بج.</p>
<p>لو عايز تفهم ليه معظم ردود الـ AI تحسها روبوت، السبب غالبا هنا: أدب من غير بلاي بوك. وانا كاتب الفرق بين الرد والبيع بالتفصيل في <a href="https://ot1-pro.com/vs/wati">مقارنة OT1-Pro مع Wati</a> عشان تشوف بنفسك الفرق بين اللي بيرد واللي بيقفل.</p>

<h2>3. البلاي بوك الجديد: 10 خطوات بدل شغل لم البيانات</h2>
<p>البرومبت القديم كان بيقول للـ AI يعمل إيه: ادفع ناحية البيع، اتعامل مع الاعتراضات. من غير ما يعلمه إزاي. فالموديل كان بيرجع لعادته القديمة: يلم أسامي ويرمي فيتشرز. كان شبه فورم عليه إيموجيز.</p>
<p>انا بدلته بالفلو اللي بيشتغل بيه التوب 1% من البياعين في الشات:</p>
<ol>
<li>اسمع الأول. اقرا الهيستوري كله. حدد المشتري فين: فضولي، بيقارن، جاهز، متردد، معترض.</li>
<li>السلام مش لحظة بيع. تحية دافية ولمحة واحدة عن النتيجة، وبعدين سؤال.</li>
<li>اكتشف الوجع بسؤال تأهيلي واحد قصير.</li>
<li>بيع منافع مش فيتشرز. فيتشر ← عشان تقدر ← نتيجة.</li>
<li>اتعامل مع الاعتراضات بـ Feel-Felt-Found أو Isolate-Reframe-Resolve. من غير خناق ومن غير خصم ومن غير اعتذار.</li>
<li>اجمع مايكرو كوميتمنت. آهات صغيرة قبل الآه الكبيرة.</li>
<li>اقفل مباشرة على إشارة الشراء. قفلة مباشرة أو مفترضة أو بديلة.</li>
<li>اتعامل مع الصمت والردود القصيرة بأنك تغير الزاوية، مش بأنك تعتذر.</li>
<li>ماتش طاقة العميل ولهجته وطول رسالته.</li>
<li>فكرة واحدة في الرد. سؤال واحد أو call to action واحد، وقصير بتاع شات.</li>
</ol>
<p>كل مزود وارث نفس الخطوات من نفس الـ trait.</p>

<h2>4. اسمع الأول.. ومتخلطش السلام عليكم بالبيع</h2>
<p>الخطوة الأولى والتانية شكلهم بديهي لحد ما تتفرج على البوت وهو بيفشل فيهم مئات المرات. الخطوة الأولى: حدد المشتري في دماغه فين. الفضولي محتاج تعليم. اللي بيقارن محتاج تمييز. الجاهز محتاج قفلة. المتردد محتاج أمان. المعترض محتاج إعادة تأطير.</p>
<p>الخطوة التانية بتصلح فشل التحية. العميل يكتب "السلام عليكم" أو "hi" من غير سؤال. البوت القديم يا إما كان بيحقق معاه يا إما كان بيدلق السعر والفيتشرز كلها مرة واحدة.</p>
<p><strong>غلط BAD:</strong> العميل: "السلام عليكم." البوت: "وعليكم السلام! اسمك إيه؟ بتشتغل في إيه؟ وعايز تعرف سعر أنهي باقة؟". تلات أسئلة وزيرو قيمة.</p>
<p><strong>صح GOOD:</strong> العميل: "السلام عليكم." البوت: "وعليكم السلام وأهلا بيك! تحب أعرفك إزاي بنرد على عملاءك وانت نايم، ولا عندك سؤال معين؟". دافي، ولمحة عن أقوى نتيجة، وباب مفتوح واحد.</p>
<p>ونفس الشكل بالإنجليزي.

<h2>5. اكتشف الوجع بسرعة وبيع منفعة مش فيتشر</h2>
<p>الناس بتشتري لما وجع الثبات يبقى أكبر من وجع التغيير. الـ AI بيسأل سؤال تأهيلي واحد قصير لما يبقى مناسب: "إيه الحاجة اللي بتدور عليها بالظبط؟". سؤال واحد، مش تحقيق.</p>
<p>وبعدين يترجم الفيتشرز لنتايج. محدش بيشتري unified inbox. الناس بتشتري إنها متفوتش رسالة تاني. محدش بيشتري 12,000 AI credits. الناس بتشتري بيزنس بيرد وهي نايمة. المعادلة: فيتشر ← عشان تقدر ← نتيجة، ومنفعة واحدة في الرسالة.</p>
<p><strong>غلط BAD (دلق فيتشرز):</strong> "عندنا inbox موحد و12,000 credits وmulti-channel وanalytics." العميل بيقرا مواصفات ومش بيحس بحاجة.</p>
<p><strong>صح GOOD (منفعة مربوطة بالوجع):</strong> "بما إنك بتضيع رسايل على إنستجرام وفيسبوك، الميزة دي بتجمعهم في مكان واحد، عشان تقدر ترد في ثواني وتبطل تخسر بياعين بيبعتولك نص الليل." وجع واحد، ونتيجة واحدة.</p>
<p>فوق ده فيه طبقة lead-score: البارد يكمل discovery، والدافي ياخد منفعة واحدة، والسخن يتقفل على طول.</p>

<h2>6. الاعتراضات الأربعة اللي بيقف عندها 73% من البيعات</h2>
<p>الاعتراضات هي اللي بتكسب الصفقات. القاعدة بتاعتي ناشفة: متتخانقش، متعملش خصم، متعتذرش. استخدم Feel-Felt-Found يعني أنا فاهم إحساسك وناس كتير حسوا كده ولقوا كذا، أو Isolate-Reframe-Resolve يعني اعزل العطلة الحقيقية وأعد تأطيرها واقفل بخطوة صغيرة.</p>
<p>الأربعة دول بيغطوا تقريبا كل المماطلة في الإنبوكس بتاعنا: غالي أوي، هفكر وأرد عليك، هسأل وأرجعلك، وهو ده بيجيب نتيجة فعلا. وكل واحد ليه الرد الغلط اللي بيموت البيعة والرد الصح اللي بيكملها.</p>
<table border="1" cellpadding="8" cellspacing="0">
<tr><th>الاعتراض</th><th>الرد الغلط</th><th>الرد الصح</th><th>ليه</th></tr>
<tr><td>غالي أوي</td><td>"آسف! ممكن نعملك خصم. البادجت بتاعتك كام؟"</td><td>"فاهمك، كتير من العملاء بيحسوا كده في الأول. اللي اكتشفوه إن مرتب الموظف اللي قاعد للرسايل أغلى بكتير. تحب تجرب المجاني الأول وتشوف بنفسك؟"</td><td>بيعترف بالإحساس وبيعده ضد تكلفة التعيين وبيقلل الريسك بالتجربة بدل الخصم.</td></tr>
<tr><td>هفكر وأرد عليك</td><td>"تمام خد وقتك! ابقى كلمني!"</td><td>"عادي جدا، إيه بالظبط اللي محتاج تفكر فيه؟ السعر ولا بتشتغل إزاي؟ ممكن أساعدك تحسمها هنا."</td><td>بيعزل العطلة الحقيقية بدل ما يقبل المماطلة المبهمة ويسيب البيعة تموت.</td></tr>
<tr><td>هسأل وأرجعلك</td><td>"تمام مستنيك!"</td><td>"تمام، عشان متنساش تحب أبعتلك اللينك دلوقتي وتفتحه لما تفضى؟"</td><td>بيحافظ على الكنترول بآه صغيرة ولينك من غير ضغط ولا مطاردة.</td></tr>
<tr><td>هو ده بيجيب نتيجة فعلا؟</td><td>"أيوه شغال ممتاز وأحسن جودة!"</td><td>"سؤال مهم. اللي شفناه من 12,847 محادثة إن اللي جرب المجاني وشاف الرد الفوري هو اللي كمل. تحب أوريك مثال من مجال شبه مجالك؟"</td><td>بيجاوب بدليل وخطوة ملموسة وعمره ما بيألف أرقام مش عنده.</td></tr>
</table>
<p>نفس الأربعة بالإنجليزي عشان تشوف الباترن: غالي <strong>غلط BAD:</strong> "Sorry it feels pricey, what can you afford؟" <strong>صح GOOD:</strong> "Totally get it, others felt the same until they compared it to paying someone to watch the inbox. Want to try free first؟". هفكر <strong>غلط BAD:</strong> "No problem, think about it!" <strong>صح GOOD:</strong> "Of course, what part do you want to think through؟". هسأل <strong>غلط BAD:</strong> "Sure, ping me later!" <strong>صح GOOD:</strong> "Want me to send the link now so you have it when free؟". بيجيب نتيجة <strong>غلط BAD:</strong> "Yes, it is amazing!" <strong>صح GOOD:</strong> "Fair question, want an example from a business like yours, or try free and watch it reply live؟".</p>
<p>انا عمر وبقولك: الخصم أسهل رد وأوحش رد. أول ما تقول خصم انت اعترفت إن سعرك غالي.</p>

<h2>7. المايكرو كوميتمنت: إزاي تاخد آهات صغيرة قبل القفلة</h2>
<p>محدش بيقول آه كبيرة من غير ما يقول آهات صغيرة الأول. الخطوة السادسة بتفرض المايكرو كوميتمنت: "يعني الموضوع ده مهم ليك دلوقتي، صح؟". كل آه بتصعب اللي بعدها.</p>
<p>الخطوة السابعة: اقفل. إشارات الشراء مكتوبة بالنص في الـ trait: السؤال عن السعر، السؤال عن بتشتغل إزاي، السؤال عن المشمول، السؤال عن الضمان، كلمة "تمام" أو "okay". لما تشوف واحدة، بطل بيع واقفل.</p>
<p>تلات أشكال للقفلة في البرومبت. مباشرة: "تمام تحب نبدأ دلوقتي؟ اللينك: https://ot1-pro.com/register". مفترضة: "هبعتلك اللينك دلوقتي، لما تخلص التسجيل قولي وأنا أمشي معاك خطوة خطوة." بديلة: "تحب تبدأ بالخطة المجانية الأول ولا بالمدفوعة؟".</p>
<ol>
<li>القفلة المباشرة للي قالها صريحة: عايز أشترك. متلفش.</li>
<li>القفلة المفترضة للي متردد بس مهتم: اتصرف كأنه هيكمل وسهل عليه الخطوة.</li>
<li>القفلة البديلة للي خايف من الالتزام: خيره بين اتنين صغيرين بدل آه ولأ الكبيرة.</li>
</ol>
<p><strong>غلط BAD في القفلة:</strong> العميل: "بكام الباقة؟" البوت: "عندنا 3 باقات بأسعار ومميزات كتير تحب تعرف إيه؟". المشتري سأل إزاي يدفع فأخد واجب منزلي.</p>
<p><strong>صح GOOD في القفلة:</strong> العميل: "بكام الباقة؟" البوت: "المدفوعة 79 دولار في الشهر وبترد على عملاءك 24/7. تحب أبعتلك لينك التسجيل وتبدأ مجاني الأول؟". باقة واحدة مناسبة، ولينك واحد، وسؤال واحد. كل الباقات بالتفصيل في <a href="https://ot1-pro.com/pricing">أسعار OT1-Pro</a>، والـ AI بيختار الباقة المناسبة بدل ما يدلق الصفحة في الشات.</p>
<p>انا عمر ودي غلطتي المفضلة للاعتراف: كنت فاكر البياع الشاطر هو اللي بيشرح أكتر. طلع البياع الشاطر هو اللي بيقفل أسرع. الشرح الكتير شك. القفلة السريعة ثقة.</p>

<h2>8. الأشباح: 73% بيختفوا بعد السعر و27% بيرجعوا</h2>
<p>من 12,847 محادثة حقيقية حللتها، 73% من المشترين بيختفوا أول ما يسمعوا السعر. مش بيعترضوا. بيتبخروا. البوت اللي بيتعامل مع الصمت كأنه رفض بيخسر تلات تربع البايبلاين في لحظة أعلى نية شراء.</p>
<p>ونفس الداتا فيها الخبر الحلو: سيكوينس متابعة 3 لمسات بيرجع 27% من الأشباح دول. كل لمسة بقيمة جديدة، عمرها ما كانت "بتشيك عليك". على خطة 79 دولار في الشهر، كل 100 محادثة اتقال فيها السعر معناها 73 شبح، وحوالي 20 عميل راجع، يعني تقريبا 1,580 دولار شهريا البوت المؤدب كان هيسيبهم على الأرض.</p>
<p>السيكوينس شغال أوتوماتيك في المنتج، والتوقيت وقاعدة القيمة المضافة ومنطق الوقف عند الرد مشروحين في أتمتة المتابعة بتاعتنا. وعشان تشوف بنحلل المحادثات دي إزاي قبل ما يختفوا، اقرا الدراسة الشقيقة <a href="https://ot1-pro.com/blog/ai-deep-analysis-3000-contacts-0-credit-rerun-ar">تحليل الذكاء الاصطناعي العميق لـ 3000 عميل</a>.</p>
<ol>
<li>بعد 24 ساعة: حاجة مفيدة جديدة مربوطة باللي سأل عنه. "نسيت أقولك، بتشتغل على فيسبوك وإنستجرام وواتساب من مكان واحد."</li>
<li>بعد 72 ساعة: دليل مش ضغط. سطر واحد من بيزنس شبهه.</li>
<li>بعد 7 أيام: عكس الريسك. "تحب تبدأ مجاني وأنا أمشي معاك في الإعداد؟"</li>
</ol>
<p><strong>غلط BAD في المتابعة:</strong> "ها قررت إيه؟" و"لسه مهتم؟" و"مستني ردك!". التلاتة ضغط من غير قيمة، وبيخلوا العميل يعمل بلوك.</p>
<p><strong>صح GOOD في المتابعة:</strong> كل رسالة فيها معلومة جديدة أو دليل جديد أو ريسك أقل. المتابعة بيع تاني مش تذكير. اللي فهم كده رجع 27%. واللي لسه بيبعت "just checking in" لسه بيخسر 73%.</p>
<p>انا عمر وحسبتها: 20 عميل راجع في 79 دولار يعني 1,580 دولار شهريا من تلات رسايل.</p>

<h2>9. سلم البيانات: إمتى تسأل عن الاسم وإمتى تسكت</h2>
<p>البوت القديم كان بيفتح بـ "اسمك إيه؟" من أول تيرن. الكلوزرز الحقيقيين مبيعملوش كده. الـ trait فيها سلم capture متثبت عشان كل موديل يسأل عن البيانات الشخصية في أقل لحظة احتكاك:</p>
<ol>
<li>أول تيرن سواء تحية أو أول سؤال: ممنوع تسأل عن الاسم أو التليفون أو الإيميل. اتفاعل، بيع منفعة واحدة، اسأل سؤال تأهيلي عن احتياجه.</li>
<li>التيرن التاني والتالت لو أظهر اهتمام أو سأل سؤال حقيقي: لو نوع البيزنس أو اسمه في ليستة الجمع، دي اللحظة الطبيعية. "بتشتغل في إيه؟" تنفع هنا لأنها بتظبط البيتش.</li>
<li>لحظة السعر أو الشراء: اسأل عن الاسم وطريقة التواصل اللي بيكتب منها، متصاغة كخدمة "ممكن أعرف اسمك عشان أبعتلك العرض؟"، عمرها ما تتقال كاملا فورم.</li>
<li>كل الحقول اتجمعت: اسكت. اقفل بحرارة وأكد الخطوات الجاية.</li>
</ol>
<p>حقل واحد في الرسالة بالكتير، ومربوط باللي قاله.</p>
<p><strong>غلط BAD في السلم:</strong> أول تيرن: "أهلا! اسمك إيه ورقم تليفونك؟". العميل بيحس إنه اتحبس قبل ما يعرف أي حاجة.</p>
<p><strong>صح GOOD في السلم:</strong> أول تيرن: منفعة وسؤال تأهيلي. تاني تيرن: "بتشتغل في إيه؟ عشان أقولك أنسب استخدام ليك." لحظة السعر: "تمام، ممكن أعرف اسمك عشان أبعتلك لينك التسجيل والخطوات؟". وعشان تدخل الفلو ده بنفسك ابدأ من <a href="https://ot1-pro.com/register">إنشاء حساب OT1-Pro</a>.</p>
<p>انا عمر والقاعدة بتاعتي: البيانات تمنها قيمة. عايز اسم؟ ادفع معلومة.</p>

<h2>10. الصمت والطاقة وفكرة واحدة في الرد.. مع فلتر السبام</h2>
<p>لما المشتري يرد بـ "لا" أو "مش مهتم"، الـ trait بيمنع الاعتذار والانسحاب لكلام شركات. غير الزاوية: "ماشي، تحب تعرف إيه بالظبط؟ السعر، بتشتغل إزاي، ولا تجرب مجاني الأول؟". الردود القصيرة اتجاه مش رفض.</p>
<p>ماتش الطاقة إجباري. القصير ياخد قصير. الرسمي ياخد رسمي، والكاجوال ياخد كاجوال. المصري ياخد مصري، والخليجي ياخد خليجي. ومراية اللغة مطلقة: عربي داخل يعني عربي خارج 100%، وإنجليزي داخل يعني إنجليزي خارج 100%، وعمر ما يتحط جملة إنجليزي جوه رد عربي. جملة الرفض الإنجليزي جوه العربي كانت بج حقيقي في البرودكشن، فعبارات الرفض ممنوعة باللغتين.</p>
<p>انضباط طول الشات: جملة أو اتنين قصيرين، فكرة واحدة في الرد، سؤال واحد أو call to action واحد. <strong>غلط BAD:</strong> خمس فيتشرز والسعر و"اسمك إيه؟" في رسالة واحدة. <strong>صح GOOD:</strong> "الميزة دي بتخليك ترد في ثواني حتى الفجر. تحب تشوفها على رسايلك انت؟".</p>
<p>وانا حافظت على توكن [SPAM_DETECTED] بالنص. قبل أي رد بيع، الموديل بيحكم لو فيه إساءة أو ترول أو تخبيط أو عدائية تخلي البياع البشري يقف. سؤال السعر الصعب عمره ما إساءة. ولو ضربت بيطلع [SPAM_DETECTED] بالظبط ولا حاجة غيره. ولو إنسان فعل المحادثة تاني، الكلاسيفيير بيهدى إلا لو آخر رسالة مسيئة صراحة.</p>
<p>انا عمر وكتبت البلاي بوك ده لأني قريت شاتاتنا واتكسفت. البوت القديم اللي بيلم معلومات كان بيسأل المشترين انتوا بتبيعوا إيه. الكلوزر اللي بطلعه دلوقتي بيسمع الأول، بيبيع منفعة واحدة، بيرد على الاعتراضات الأربعة بلهجة المشتري نفسه، بيسأل عن الاسم بس لما يقدر يبعت حاجة مفيدة، وبيقفل بلينك بدل المحاضرة. نفس الـ trait لكل موديل. لو شغال لوحدك وبترد بنفسك، ابدأ من <a href="https://ot1-pro.com/register">التسجيل في OT1-Pro</a> وجرب البلاي بوك ده على رسايلك من أول يوم.</p>
<p>{{CTA}}</p>
</article>
HTMLP7,
                'meta_title'        => 'الرد على اعتراضات العملاء: بلاي بوك 27% | OT1-Pro',
                'meta_description'  => 'انا عمر من OT1-Pro: راجعت 12847 محادثة، 73% أشباح بعد السعر و27% رجعوا بمتابعة 24 و72 ساعة و7 أيام، وهذا بلاي بوك Rule #0 الكامل في الرد على اعتراضات العملاء',
                'category'          => 'مبيعات',
                'author'            => 'Omar Eltak',
                'language'          => 'ar',
                'is_rtl'            => true,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 8. facebook-page-not-showing-business-portfolio-fix-ar (ar) ──
            [
                'title'   => 'صفحة الفيسبوك مش ظاهرة عند الربط؟ ضيعت 4 ساعات في فخ الـ Business Portfolio بتاع ميتا',
                'slug'    => 'facebook-page-not-showing-business-portfolio-fix-ar',
                'excerpt' => 'صفحتك مختفية لأن /me/accounts بيرجع بس الأدوار المباشرة وعمره ما بيرجع صلاحيات الـ Business Portfolio اللي معظم الأجينسيز شغالة بيها. الحل: ضيف business_management scope واسأل /me/businesses مع owned_pages و client_pages وادمج وامسح التكرار. واتأكد ان التسع صلاحيات Advanced Access.',
                'content' => <<<'HTMLP8'
<p><strong>أنا بنيت OT1-Pro عشان أي شركة تربط صفحة الفيسبوك بتاعتها في ضغطتين وتبدأ ترد على رسايل الماسنجر من إنبوكس واحد. وبعدين عميل عندي من القاهرة داس Connect، ودخل بحسابه، والـ OAuth نجح، والقايمة اللي المفروض يختار منها الصفحة طلعت فاضية تماماً.</strong> لا إيرور. لا تهنيج. بس ليستة فاضية والصفحة شغالة وموجودة قدامه في الـ Business Suite. أنا ضيعت حوالي 4 ساعات بخمّن قبل ما أوصل للسبب الحقيقي، وأنا بكتب المقال ده بالمصري كده عشان انت ما تضيعش وقتك زيي.</p>

<p>السبب هو اللي بسميه فخ الـ Business Portfolio: الـ endpoint بتاع <code>/me/accounts</code> بيرجع بس الصفحات اللي حسابك الشخصي عليه دور أدمن مباشر على الصفحة نفسها. وعمره ما بيرجع الصفحات اللي واخد عليها صلاحية عن طريق الـ Business Portfolio — يعني تعيينات الـ New Pages Experience، وموظفين الأجينسي اللي متضافين جوه الـ Business Suite، والتيم اللي مكتوب قدامه "Full access". حتى لو الـ Suite مطلعك أدمن في كل حتة، الـ API بيقول انك ما تملكش حاجة. ولو الأجينسي بتاعتك عايشة جوه الـ Business Suite، وده الطبيعي لأي أجينسي حقيقية، فالربط العادي هيخبي صفحاتك بالتصميم مش بالغلط.</p>

<p>الحل اللي شحناه في OT1-Pro يوم 2026-10-07 كان بسيط أول ما شفنا الصورة: نطلب صلاحية <code>business_management</code> وقت الـ OAuth، وبعدين نسأل الاتنين <code>/me/accounts</code> و <code>/me/businesses</code> وبعدين لكل بيزنس نجيب <code>{biz-id}/owned_pages</code> زائد <code>{biz-id}/client_pages</code>، وندمج ونمنع التكرار في <code>FacebookPlatform::fetchBusinessMediatedPages()</code>. لو انت بتدور على حل مشكلة facebook page not showing فالمقال ده هيديك خطوات الفحص والكود والبديل اللي يخليك تبيع وانت مستني ميتا تخلص ورقها.</p>

<p>ولو عايز خلفية البيروقراطية بتاعة ميتا كلها — توثيق البيزنس والـ App Review وليه التطبيق بتاعنا 1469090344742803 شغال بالطريقة دي — اقرا الدليل بتاعي <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">دليل توثيق تطبيق ميتا 2026 من مؤسس عاش التجربة</a>. المقال ده هو اللي بيجيب عندنا 5 لـ 20 دقيقة قراءة لسبب بسيط: بيقول الحقيقة بتاعة ميتا بدل ما يكرر الدوكس.</p>

<h2>دوست Connect والـ OAuth نجح والصفحات صفر</h2>

<p>كانت ليلة تلات. عميل بيجرب معانا أجينسي من إسكندرية عنده 3 صفحات تحت بورتفوليو معمول زي البورتفوليو بتاعنا احنا OT1 Pro رقم 2169075923895403 — بورتفوليو واحد وشايل كذا صفحة والموظفين متضافين كشركاء. الراجل عمل كل حاجة صح. دخل بأكونت الفيسبوك الصح، وافق على كل الصلاحيات، والـ callback عندنا استلم user token سليم وشغال. وبعدين الـ <code>fetchPages()</code> ندهت على <code>/me/accounts</code> ورجعت <code>{"data": []}</code>.</p>

<p>أول حاجة جت في دماغي انه دخل بأكونت غلط. قلت له جرب تاني. جرب. نفس الليستة الفاضية. سألته عن الـ 2FA وعن الـ ad-blocker وعن اليوزر التجريبي. الراجل كان صبور. وأنا كنت غلطان في التلاتة.</p>

<p>واللي كان هيجنني ان الـ Business Suite مطلعه أدمن في كل حتة: الـ Business settings جوه People مكتوب Full access، والصفحة نفسها جوه Page access مكتوب Full control. بكل إشارات الواجهة هو الأدمن. بس الـ API بيقول انه ما عندوش ولا صفحة. وأنا بعد كده شفت نفس الليستة الفاضية في الجيزة وفي جدة وفي دبي، ومع فاوندر من بريطانيا كان بيقدم الـ Companies House confirmation statement في نفس الأسبوع.</p>

<p>الدرس اللي طلعت بيه: العميل كان صح وأنا والسيستم كنا غلط.</p>

<h2>ميتا عندها نوعين أدمن وانت فاكرهم نوع واحد</h2>

<p>ميتا عندها طريقتين منفصلين تماماً تخلي بيهم حد أدمن على الصفحة، وشكلهم في الـ Business Suite واحد بالظبط بس سلوكهم في الـ API مختلف تماماً.</p>

<p>النوع الأول هو الدور المباشر القديم. بتروح على الصفحة وبعدين Settings وبعدين Page access وبتضيف حد بالبروفايل الشخصي بتاعه. الدور ده متخزن على الصفحة نفسها. ولما الشخص ده يعمل OAuth لأي تطبيق وتنادي <code>/me/accounts</code> ميتا بترجعلك الصفحة ومعاها <code>page_access_token</code>. وده الطريق اللي كل الشروحات وكل إجابات Stack Overflow فاكرة انه الطريق الوحيد.</p>

<p>النوع التاني هو الصلاحية اللي جاية عن طريق الـ Business Portfolio. بتروح الـ Business Suite وبعدين Business settings وبعدين People وبتضيف الشخص وتديله صلاحية على الصفحات اللي مملوكة للبورتفوليو. أو بتضيف أجينسي كشريك وتسند لها صفحات من البورتفوليو التاني. ومع الـ New Pages Experience ده بقى الطبيعي للتيمات، لأن ميتا بتزقك تدير الناس من مستوى البورتفوليو. والشخص هنا عمره ما بياخد دور مباشر على سطر الصفحة. الصلاحية بتاعته عايشة على جراف أصول البيزنس مش على الصفحة.</p>

<p>وهنا السطر اللي ميتا دافناه: <code>/me/accounts</code> بيقرا النوع الأول بس. ما بيمشيش في جراف البيزنس. فلو كل صلاحياتك من النوع التاني هتاخد مصفوفة فاضية مع HTTP 200 — لا إيرور ولا تحذير. وأنا اتأكدت من ده مع 3 يوزرز تجريبيين على البورتفوليو OT1 Pro رقم 2169075923895403: ضيف اليوزر مباشرة على الصفحة فتظهر في <code>/me/accounts</code>، شيل الدور المباشر واديله نفس الصلاحية من البورتفوليو فالصفحة تختفي من <code>/me/accounts</code> والـ Suite لسه كاتب Full access.</p>

<p>كنت ببص على الباب الغلط والصفحة في أوضة تانية.</p>

<h2>ليه /me/accounts بيخبي صفحات البورتفوليو بالتصميم</h2>

<p>أنا بقول بيخبي، بس من ناحية ميتا هو تحديد نطاق. <code>/me/accounts</code> بيجاوب على سؤال "اليوزر ده واخد صلاحية مباشرة على أنهي صفحات؟" واتبنى قبل ما البورتفوليوهات تتوجد أصلاً، وميتا عمرها ما حدثت معناه لما إدارة التيمات اتنقلت للبورتفوليو. هما ضافوا حواف جديدة — <code>/me/businesses</code> و <code>/{business-id}/owned_pages</code> و <code>/{business-id}/client_pages</code> — وسابوا الـ endpoint القديم يرجع إجابة ناقصة مع status ناجح.</p>

<p>والإجابة الناقصة مع status ناجح دي هي اللي بتحرق الفاوندرز. لو الـ endpoint كان بيرجع 403 وبيقول "استخدم business_management scope" كنا صلحناها في عشر دقايق. لكنه بيرجع نجاح وبيانات ناقصة، فبتشك في اليوزر. وأنا عملت كده بالظبط أول ساعتين.</p>

<p>والنقطة التانية: مستحيل تشوف صفحات البورتفوليو من غير <code>business_management</code>. الـ scopes اللي بدأنا بيها كانت <code>public_profile, email, pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, instagram_basic, instagram_manage_messages</code> — كفاية للصفحات المباشرة، ومش كفاية عشان تعد البيزنسات. من غير <code>business_management</code> الـ <code>/me/businesses</code> بيرجع فاضي هو كمان. لازم التسع صلاحيات يبقوا Advanced Access قبل ما التعداد يشتغل مع العملا الحقيقيين. ونفس وجع الـ scopes ده في حتة الواتساب شرحته في مقال أخو المقال ده <a href="https://ot1-pro.com/blog/whatsapp-embedded-signup-history-import-2026">استيراد سجل الواتساب مع الـ Embedded Signup 2026</a>.</p>

<p>القاعدة: النجاح الناقص أخطر من الإيرور الصريح.</p>

<h2>الدوامة اللي ضيعت فيها 4 ساعات عشان انت ما تضيعهاش</h2>

<p>دي الدوامة بتاعتي ساعة بساعة. كل تخمين غلط كان شكله منطقي. وكل واحد كلفني من نص ساعة لساعة. اقرا الجدول وبعدين ما تكرروش.</p>

<table>
<thead>
<tr><th>العرض</th><th>التخمين الغلط</th><th>الفحص الصح</th><th>الحل</th></tr>
</thead>
<tbody>
<tr><td>القايمة فيها صفر صفحات بعد OAuth ناجح</td><td>اليوزر دخل بأكونت فيسبوك غلط</td><td>نادي <code>/me?fields=id,name</code> بالتوكن المتخزن وقارن الـ ID مع الـ Suite وهيطلع نفس الشخص</td><td>بطل تطلب إعادة ربط واسأل <code>/me/businesses</code></td></tr>
<tr><td><code>/me/accounts</code> بيرجع <code>{"data":[]}</code> مع 200</td><td>الـ 2FA أو تغيير الباسورد لغى التوكن</td><td>نادي <code>/me/permissions</code> وهتلاقي الصلاحيات granted والتوكن سليم</td><td>التوكن سليم والمشكلة انك ما مشيتش في حافة البيزنس</td></tr>
<tr><td>الـ Suite كاتب Full access والـ API شايف ولا حاجة</td><td>اليوزر شال صلاحية وقت شاشة الـ OAuth</td><td>بص على <code>/me/permissions</code> وشوف <code>business_management</code> هل هي declined ولا مش موجودة أصلاً</td><td>ضيف <code>business_management</code> لجملة الـ OAuth وخليه يوافق من جديد</td></tr>
<tr><td>شغالة مع الأدمن التجريبي وبتفشل مع العميل</td><td>التطبيق في وضع التطوير أو حدود اليوزر التجريبي</td><td>افتح developers.facebook.com/apps/1469090344742803 وهتلاقي الصلاحية مكتوبة "جاهز للاختبار" يعني Standard Access</td><td>وصّل التسع صلاحيات لـ Advanced Access وقبل كده اشتغل بالـ managed onboarding</td></tr>
<tr><td>بعض الصفحات ظاهرة وصفحة واحدة مختفية</td><td>الصفحة مش منشورة أو عليها قيد</td><td>اسأل <code>{biz}/owned_pages</code> و <code>{biz}/client_pages</code> لوحدهم والصفحة الناقصة هتطلع في الحافة التانية</td><td>ادمج الحافتين وامسح التكرار في <code>fetchBusinessMediatedPages()</code></td></tr>
<tr><td>اللي مش أدمن بيشوف "Feature unavailable: Facebook Login is currently unavailable for this app"</td><td>بج في الـ callback عندنا</td><td>اتأكد من حالة مراجعة التطبيق 1469090344742803 والـ Standard Access بيمنع دخول غير الأدمن إجباري</td><td>خلي META_APP_VERIFIED مقفول ومشي العميل على الـ managed onboarding</td></tr>
</tbody>
</table>

<p>النمط واضح: أنا كنت بخمن هوية أو توثيق أو حالة صفحة. والسبب الحقيقي كل مرة كان اني ما مشيتش في الجراف كله. أول ما بدأت أسأل الطرفين بالتوكن المتخزن قبل ما أنظّر، التشخيص نزل من ساعات لدقايق. ودي بقت قاعدة صارمة عندنا في التيم: ممنوع أي نظرية قبل الفحصين.</p>

<h2>الفحص بتاع 10 دقايق اللي بيقول الحقيقة</h2>

<p>لو صفحة الفيسبوك بتاعتك مش ظاهرة في الربط دلوقتي حالاً، امشي على السبع خطوات دول بالترتيب وبالتوكن المتخزن الحقيقي. كلهم على بعض حوالي عشر دقايق.</p>

<ol>
<li><strong>اتأكد من الهوية.</strong> نادي <code>GET /me?fields=id,name</code> بالتوكن المتخزن. قارن الـ ID مع الشخص اللي في Business Suite جوه People. لو طلعوا نفس الشخص امسح نظرية "الأكونت الغلط" نهائياً.</li>
<li><strong>اتأكد من صحة التوكن.</strong> نادي <code>GET /me/permissions</code>. التسعة — <code>public_profile, email, pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, instagram_basic, instagram_manage_messages, business_management</code> — لازم يظهروا <code>granted</code>. لو <code>business_management</code> طالعة declined أو مش موجودة اعمل OAuth من جديد بجملة التسع صلاحيات، لأن اليوزر ممكن يشيل علامة صح من شاشة الموافقة.</li>
<li><strong>شغل الفحص القديم.</strong> نادي <code>GET /me/accounts?fields=id,name,access_token</code>. سجل الليستة. دي مجموعة الأدوار المباشرة. ولو فاضية ما تستنتجش ان اليوزر ما عندوش صفحات.</li>
<li><strong>شغل فحص البورتفوليو.</strong> نادي <code>GET /me/businesses?fields=id,name</code>. ولكل بيزنس نادي <code>GET /{biz-id}/owned_pages</code> و <code>GET /{biz-id}/client_pages</code>. وده بالظبط اللي <code>FacebookPlatform::fetchBusinessMediatedPages()</code> بيعمله من 2026-10-07.</li>
<li><strong>ادمج وامسح التكرار.</strong> وحّد التلات قوايم على الـ Page ID. في حالة إسكندرية رحنا من صفر صفحات لـ 3 صفحات. ولو الصفحة الناقصة ظهرت هنا يبقى أثبت فخ البورتفوليو وتوقف تحليل في التوثيق.</li>
<li><strong>بص على مستوى الوصول.</strong> افتح developers.facebook.com/apps/1469090344742803 وبعدين Use Cases وبعدين Permissions. لو أي صلاحية مكتوبة "جاهز للاختبار" بدل Advanced Access فالعملا اللي مش أدمن لسه هياخدوا <code>Feature unavailable: Facebook Login is currently unavailable for this app</code>. ودي مشكلة مراجعة مش مشكلة كود.</li>
<li><strong>خد قرار البديل.</strong> لقيت الصفحة بعد الدمج وكل حاجة Advanced Access اشحن قايمة الاختيار المدمجة. أي حاجة لسه Standard Access خلي الكود بس مشي العملا الحقيقيين على "Request connection" عشان السوبر أدمن يربط من الطريق الموثق ويعيد تعيين الصفحة.</li>
</ol>

<p>الدعم عندنا بيمشي على الشيك ليست دي قبل ما يتسمح له يقول للعميل "جرب متصفح تاني". ومن شهر أكتوبر وهي قافلة دايرة التخمين في كل حالة.</p>

<h2>الحل اللي شحناه: business_management زائد حافتي الصفحات</h2>

<p>الحل بتاعنا في <code>app/Services/Platforms/FacebookPlatform.php</code> مقصود يكون ممل. الـ <code>fetchPages()</code> بقت تمشي في طريقين وتدمج.</p>

<p>الأول بتفضل على النداء القديم: <code>/me/accounts</code> بتوكن اليوزر. السطور دي جاية أصلاً معاها <code>page_access_token</code> وبتتربط مباشرة على جدول <code>pages</code>. وصفحات الدور المباشر لسه شائعة مع الفاوندرز الأفراد، فما شلناش الطريق ده.</p>

<p>والتاني لما التوكن يكون فيه <code>business_management</code> بينادي <code>fetchBusinessMediatedPages()</code>: يجيب البيزنسات من <code>/me/businesses</code> وبعدين لكل بيزنس يجيب <code>owned_pages</code> يعني المملوكة للبورتفوليو و <code>client_pages</code> يعني المتشاركة مع شريك وهي حالة الأجينسي الكلاسيك. وبندمج المصادر التلاتة على الـ Page ID وبناخد أحدث توكن وبنمسح التكرار قبل ما نعرض قايمة الاختيار.</p>

<p>وفيه 3 تفاصيل يفرقوا. واحد اطلب <code>business_management</code> وقت الـ OAuth — الكود من غير الـ scope بيرجع ليستة بيزنس فاضية وإحساس كداب انك صلحت. اتنين اسأل الاتنين <code>owned_pages</code> و <code>client_pages</code>. أنا شحنت owned-only الأول وصفحة متشاركة مع شريك لسه اختفت، لأن صفحات الشركا عايشة على حافة الـ client بس. تلاتة حافظ على قاعدة الصفحة النشطة الواحدة: الـ observer بتاع <code>Page::booted()</code> بيفرض صف واحد نشط لكل platform ID، ودمج المصادر بيزود احتمال التكرار، فامسح التكرار على <code>platform_page_id</code> قبل الـ upsert وإلا توجيه الويبهوك بيتكسر. الفرق كله كان تحت 120 سطر. الأربع ساعات ما كانتش مشكلة كود. كانت مشكلة رؤية.</p>

<h2>جاهز للاختبار ضد Advanced Access: الفخ التاني ورا الأول</h2>

<p>حل التعداد بيكشف الحيطة اللي بعده: حالة الـ App Review. ميتا بتعرض كل صلاحية يا Advanced Access يا "جاهز للاختبار" — يعني "Ready to Test" ومعناها Standard Access للأدمن والمختبرين بس. والتطبيق بتاعنا 1469090344742803 محتاج التسعة Advanced Access: <code>public_profile, email, pages_show_list, pages_messaging, pages_manage_metadata, pages_read_engagement, instagram_basic, instagram_manage_messages, business_management</code>.</p>

<p>ولو واحدة بس واقفة على Standard Access فقايمة الاختيار المتصلحة هتشتغل معاك انت وتفشل مع كل عميل حقيقي برسالة <code>Feature unavailable: Facebook Login is currently unavailable for this app</code>. أنا شفت فاوندر يصلح بج البورتفوليو ويجربه على نفسه ويشحنه وبعدين أول عميل يبعت له نفس الجملة دي خلال ساعة. هو ما بوظش حاجة. هو عدى البوابة الأولى ودخل على اللي بعدها.</p>

<p>والوصول لـ Advanced Access يعني توثيق البيزنس الأول وبعدين App Review لكل صلاحية. والتوثيق عايز تطابق حرفي في الاسم: في مصر مستخرج السجل التجاري مع البطاقة الضريبية، وفي الإمارات الرخصة التجارية مع عقد الإيجار المتسجل في Ejari، وفي بريطانيا الـ Companies House confirmation statement. وأنا موثق السلسلة كاملة في <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">دليل توثيق ميتا من مؤسس</a>. ولحد ما كل صلاحية تبقى Advanced Access ما تفتحش <code>META_APP_VERIFIED=true</code>.</p>

<p>الجملة بتاعة "جاهز للاختبار" دي خادعة. شكلها مطمئن كأن التطبيق جاهز. وهي في الحقيقة معناها العكس: شغال معاك انت بس. وأي حد بره التيم هيترفض على الباب. فما تفرحش لما تشوفها. افرح لما تشوف Advanced Access مكتوبة على التسعة.</p>

<h2>ليه رقم التطبيق 1469090344742803 والبورتفوليو 2169075923895403 مهمين</h2>

<p>الأرقام المحددة بتخليك تتأكد بدل ما تخمن. التطبيق <code>1469090344742803</code> هو تطبيق ميتا بتاع OT1-Pro اللي بيطلب التسع صلاحيات. والبورتفوليو OT1 Pro رقم <code>2169075923895403</code> هو الـ Business Portfolio بتاعنا اللي كررت عليه الفخ: اعمل اتنين يوزر تجريبي وضيف واحد من Page access مباشرة وضيف التاني من Business settings جوه People واعمل OAuth للاتنين بنفس الصلاحيات وقارن <code>/me/accounts</code>. صفحة واحدة ضد صفر صفحات، ونفس شارة الـ Suite في الواجهة.</p>

<p>كرر التجربة دي على البورتفوليو بتاعك قبل ما تصدق أي صفحة دوكس. الـ API هو مصدر الحقيقة، والواجهة بتدمج منحتين مختلفتين في شارة واحدة. واحنا بنسجل حمولتي الفحصين لما عميل يبلغ عن صفحة ناقصة — بنخزن أعداد accounts والـ businesses والـ owned والـ client على طلب الـ onboarding، والسطر ده حل 4 خلافات من شهر أكتوبر.</p>

<p>بحط الأرقام مقصود عشان تعرف تمسك طرف الخيط في اللوجز.</p>

<h2>الحسبة بالدولار اللي الأجينسي بتحس بيها فوراً</h2>

<p>ودي مش خنقة ربط صغيرة. خد أجينسي عربية نموذجية على <a href="https://ot1-pro.com/pricing">أسعار OT1-Pro</a>: 1500 دولار ريتينر شهري للعميل الواحد، وبتشيل الماسنجر والإنستجرام بمسودات ذكاء اصطناعي بالمصري. لو الصفحة ما اتربطتش مش هتعرف تسحب الرسايل ولا تدرب الردود ولا تعمل الديمو بتاع الإنبوكس الموحد اللي كسبت بيه الصفقة. والعميل مش بيدفع على "كانت هتتربط". بيوقف أو بيمشي.</p>

<p>اخسر ريتينر واحد بـ 1500 دولار بسبب تأخير ربط أسبوعين وهتخسر حوالي 750 دولار إيراد معترف بيه زائد حوالي 6 ساعات دعم رايح جاي بتكلفة 40 دولار للساعة يعني 240 دولار. الصفحة المحبوسة الواحدة بتكلف حوالي 990 دولار. واخسر تلاتة في شهر واحد في تجارب جديدة — زي آخر سبتمبر عندنا — ودي حوالي 2970 دولار خط أنابيب اتحرق عشان حل 120 سطر. وقارن ده مع هوامش المقعد الواحد لنفس سباكة ميتا (بص على <a href="https://ot1-pro.com/vs/wati">مقارنة OT1-Pro و WATI</a>)، الأجينسي اللي بتربط في دقايق بتحافظ على الريتينر واللي بيفتح تذاكر لميتا بيخسره. والحسبة دي هي ليه بنينا بديل الـ managed onboarding ورافضين نشيله: العميل بيدوس "Request connection" والسوبر أدمن بيربط من التطبيق الموثق وبعدين بيعيد تعيين الصفحة في <code>/super-admin/onboarding-requests</code>. مش شيك أوي، بس بيقفل.</p>

<p>أنا عارف ان الرقم يوجع، بس هو ده الواقع بتاع الأجينسيز. الربط مش خطوة فنية على الهامش. الربط هو الباب اللي لو اتقفل كل حاجة وراه واقفة: السحب والرد والتدريب والفوترة. عشان كده أي يوم تأخير في الربط هو يوم خصم من ثقة العميل قبل ما يكون خصم من الفلوس.</p>

<h2>لو انت محبوس دلوقتي اعمل كده النهاردة</h2>

<p>لو عندك قايمة اختيار فاضية مفتوحة في تاب تاني، شغل خطوات الفحص السبعة اللي فوق واحفظ أعداد الفحص للدعم. وبعدين بص على صلاحيات تطبيقك: Advanced Access في كل حتة ولا فيه "جاهز للاختبار"؟ ولو أي حاجة Standard Access وانت مش أدمن ولا مختبر على التطبيق ده، بطل تحاول OAuth مباشر. مفيش كود هيعدي حالة المراجعة.</p>

<p>وعلى OT1-Pro دوس Request connection بدل المحاولة. الطلب بيتبعت ومعاه أعداد الفحص، والسوبر أدمن بيربط الصفحة من التطبيق الموثق ويسندها للتيم بتاعك. وبتاخد الماسنجر والإنستجرام والواتساب في إنبوكس واحد مع ردود ذكاء اصطناعي من أول يوم، من غير طابور تذاكر ميتا. ابدأ من <a href="https://ot1-pro.com/register">التسجيل في OT1-Pro</a> — خطة مجانية ومن غير كارت — وبعدين ابعت الطلب من Connections. الوسيط عندنا من ساعة ما نظمنا الطابور أقل من يوم، بدل أسابيع الـ App Review.</p>

<p>أنا حرقت حوالي 4 ساعات بخمن في الـ 2FA والأكونتات الغلط والصلاحيات المتشالة قبل ما أسأل الطرفين بالتوكن المتخزن وأشوف الحقيقة. ما تكررش الدوامة بتاعتي. اسأل الاتنين وادمج وامسح التكرار واحترم بوابة الـ Advanced Access وخلي عندك طريق يدوي ينقذ الريتينر لحد ما ميتا تلحق. الصفحة الناقصة بتاعتك موجودة أكيد — ورا حافة البورتفوليو اللي الكود بتاعك عمره ما مشي فيها.</p>

<p>{{CTA}}</p>
HTMLP8,
                'meta_title'        => 'صفحة الفيسبوك مش ظاهرة؟ حل فخ الـ Business Portfolio',
                'meta_description'  => 'صفحة العميل اختفت عند الربط والـ Suite كاتب Full access. فخ بورتفوليو ميتا خباها من /me/accounts. حل مشكلة صفحة الفيسبوك مش ظاهرة',
                'category'          => 'ربط القنوات',
                'author'            => 'Omar Eltak',
                'language'          => 'ar',
                'is_rtl'            => true,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 9. whatsapp-embedded-signup-history-import-2026-ar (ar) ──
            [
                'title'   => 'واتساب Embedded Signup 2026: ربط رقمك الرسمي واستيراد الشات كله من غير ديفيلوبر',
                'slug'    => 'whatsapp-embedded-signup-history-import-2026-ar',
                'excerpt' => 'ربطت واتساب الرسمي من غير ديفيلوبر واستوردت كل شاتات ماسنجر وانستجرام عشان الذكاء الاصطناعي يفهم عملائي من أول يوم. الدليل ده فيه أرقام OTP وأسماء العرض وغلطة 2018278 وحدود الطبقات والتكلفة الحقيقية بالدولار.',
                'content' => <<<'HTMLP9'
<p><strong>أنا ربطت رقم الواتساب بتاعي بالـ Embedded Signup في قعدة واحدة من غير ما أفتح داشبورد المطورين بتاعة ميتا ولا مرة.</strong> مفيش إنشاء أبلكيشن، مفيش توكنات رايحة جاية، ومفيش Webhook URL بتنسخه وتلزقه. دوست connect جوه OT1-Pro، سجلت دخول بفيسبوك، اخترت الـ Business Portfolio بتاعتي، أكدت الرقم بكود OTP، والرقم الرسمي على الـ Cloud API اشتغل. وبعدها حصلت الحتة اللي مكنتش متوقعها: كل شاتات الماسنجر والإنستجرام القديمة بدأت تتحمل، فالذكاء الاصطناعي بقى عنده سياق من أول يوم.</p>

<p>أنا بنيت <a href="https://ot1-pro.com">OT1-Pro</a> كإنبوكس واحد للواتساب والماسنجر والإنستجرام والتليجرام والإيميل، لأني زهقت من نصيحة "هات ديفيلوبر عشان تستقبل رسالة واتساب". المقال ده هو التسجيل الحرفي للي أنا سلمته بإيدي: الـ Embedded Signup بيشتغل إزاي فعلا، ميتا بترفض إيه، فحص الصحة بيحافظ على الاشتراك إزاي، ليه بستورد سجل الشات كامل أول ما تربط، وليه غلطة 2018278 هتبوظ أول برودكاست ليك لو محدش حذرك.</p>

<p>ولو لسه مدخلتش في سلسلة التحقق المزدوجة بتاعة ميتا، ابدأ من <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta App Verification 2026: A Founder's Guide</a>. الشرح ده بيغطي تحقق الـ Business Portfolio زائد مراجعة الأبلكيشن بالـ Advanced Access. وكل اللي جاي تحت بيفترض إن أبلكيشن OT1-Pro عدى المرحلتين دول، وعشان كده انت بتتخطاهم.</p>

<h2>ليه رميت طريقة التوكن اليدوي القديمة في الزبالة</h2>

<p>أنا جربت الطريقة القديمة بنفسي وشفتها بتوقع ناس حقيقية. الطريقة القديمة لتوصيل WhatsApp Cloud API كانت كده: تعمل أبلكيشن على ميتا، تضيف منتج الواتساب، تعمل WhatsApp Business Account، تضيف رقم تليفون، تولد توكن دائم ليوزر سيستم، تنسخ الـ phone-number ID والـ WABA ID في فورم الإعدادات عندنا، وبعدين تشترك في الويبهوك يدوي. أنا شفت بعيني تلاتة فاوندرز بيحطوا الـ phone-number ID الغلط في خانة الـ WABA ID. واحد فيهم حط رقم تجريبي وقعد يومين بيدور على رسائل داخلة عمرها ما كانت هتوصل أصلا.</p>

<p>أنا كنت بقعد مع صاحب محل في العتبة بيبيع هدوم، وهو بيقولي "يا هندسة أنا عايز حاجة لما الزبون يقول بكام أرد عليه بسرعة". الراجل ده بيرد على خمسين رسالة "بكام" و"كاش عند الاستلام ولا لأ"، وبالليل بيقفل مع شركة الشحن سواء بوستة أو أرامكس. ده مش واحد فاضي يقرا دوكيمنتيشن ميتا.</p>

<p>الـ Embedded Signup شال كل ده. ميتا هي اللي مستضيفة الفلو كله جوه بوب-أب. انت بتسجل دخول بفيسبوك، بتختار الـ Business Portfolio، بتختار أو بتنشئ الـ WhatsApp Business Account، بتختار رقم التليفون، بتوافق على الصلاحيات. ميتا بترجع لـ OT1-Pro كود تفويض، والباك-إند بتاعنا بيبدله بالـ WABA ID والـ phone-number ID وتوكن طويل المدى من غير ما انت تلمس أي حاجة. مفيش خطوة ديفيلوبر فاضلة ناحيتك.</p>

<p>أنا سايب الحقول اليدوية القديمة في الداتابيز عشان العملاء القدامى اللي مهاجرين، بس مفيش عميل جديد المفروض يشوفها. لو بتعرف تسجل دخول على فيسبوك وبتعرف تستقبل رسالة SMS، يبقى بتعرف توصل.</p>

<h2>الـ Embedded Signup بيطلب منك إيه بالظبط</h2>

<p>ده الفلو المرقم اللي أنا سلمته، زي ما هتشوفه بالحرف جوه OT1-Pro:</p>

<ol>
<li><strong>دوس Connect WhatsApp.</strong> بنفتح ديالوج الـ Embedded Signup بتاع ميتا بالسكوبات اللي الـ Cloud API محتاجها بالظبط: business_management وwhatsapp_business_messaging وwhatsapp_business_management.</li>
<li><strong>سجل دخول بفيسبوك واختار الـ Business Portfolio.</strong> استخدم البورتفوليو اللي مالك الدومين بتاع شركتك. لو اخترت بورتفوليو شخصي أو فاضي، اسم العرض هيسقط بعدين.</li>
<li><strong>اختار أو أنشئ الـ WhatsApp Business Account.</strong> معظم الفاوندرز بيختاروا الـ WABA الموجود. ولو معندكش، ميتا بتنشئ واحد تحت نفس البورتفوليو جوه البوب-أب.</li>
<li><strong>احجز رقم التليفون.</strong> اكتب الرقم اللي عايز العملاء يكلموك عليه. لازم يقدر يستقبل مكالمة OTP أو SMS أثناء البوب-أب. ومينفعش يبقى شغال على واتساب العادي أو على WABA تانية.</li>
<li><strong>قدم اسم العرض.</strong> ده اللي العملاء بيشوفوه بدل رقمك. اكتب اسم البيزنس بتاعك بالحرف.</li>
<li><strong>وافق وارجع.</strong> ميتا بترجعك لـ OT1-Pro ومعاها كود تفويض. الباك-إند عندنا بيبدله، بيخزن التوكن على السيرفر، بيشترك في الويبهوك، بيشغل فحص الصحة، وبيبدأ استيراد السجل.</li>
<li><strong>ابعت رسالة تجريبية.</strong> ابعت لرقمك الجديد من موبايلك الشخصي، شوفها وصلت إنبوكس OT1-Pro، ورد عليها من الإنبوكس. انت كده لايف على الـ Cloud API الرسمي.</li>
</ol>

<p>الوقت الكلي للرقم بتاعي: حداشر دقيقة، منهم انتظار مكالمة الـ OTP. وعدد صفحات داشبورد المطورين اللي فتحتها: صفر. وأول رسالة تجريبية بعتها لنفسي كانت "بكام الشحن لأسيوط كاش عند الاستلام".</p>

<p>لو انت صاحب متجر شغال بوستة وأرامكس، الزبون اللي بيسأل "بكام" الساعة واحدة بالليل مش هيستنى لتاني يوم. أول رد سريع من الإنبوكس هو اللي بيقفل البيعة.</p>

<h2>قواعد اسم العرض اللي بترفض بيزنس حقيقي</h2>

<p>مراجعة اسم العرض هي أول إيميل رفض بيجيلك، غالبا بعد ساعتين لست ساعات من ما كنت فاكر إنك خلصت. وأنا شفت كل الأنواع. مراجع ميتا بيبص على تلات حاجات، والتلاتة حرفيين:</p>

<p>أولا <strong>ممنوع الأسماء العامة.</strong> "Customer Support" و"Sales Team" و"Store" و"Service Center" كلهم بيترفضوا فورا. الاسم لازم يحدد بيزنس أو منتج. "عيادة النيل للأسنان" بيعدي. "دعم الأسنان" مبيعديش. أنا جربت بنفسي أقدم "Customer Support" على WABA تجريبي عشان أتأكد، والرفض وصل في واحد وأربعين دقيقة.</p>

<p>ثانيا <strong>ممنوع الإيموجي وعلامات الملكية والترقيم في الأول.</strong> مينفعش "النيل 🦷" ولا "Nile™ Dental" ولا "-Nile Dental-". واحد فاوندر ضاع منه تلات أيام لأن البراند بتاعه مكتوب "BRAND™" على الموقع وهو نسخه حرف حرف.</p>

<p>ثالثا <strong>الاسم لازم يطابق الدومين بتاعك ظاهريا.</strong> لو موقعك niledental.com قدم "Nile Dental Clinic". متقدمش "NDC Smiles" حتى لو ده اليوزر بتاع الإنستجرام. المراجع بيفتح الدومين وبيقارن. والحل إنك تسمي بالظبط زي عنوان الهومبيج وتقدم تاني.</p>

<p>وأنا هنا هديك مثال مصري خالص. لو محلك اسمه "سنتر التوحيد للملابس" والموقع بتاعك eltawheed-store.com، قدم نفس الاسم بالحرف. متقدمش "عروض الملابس" ولا تحط إيموجي. المراجع مش هيعرف إن ده انت.</p>

<h2>رقمك لازم يستقبل OTP ولازم يبقى نضيف</h2>

<p>مشكلتين في الرقم بيوقفوا الـ Embedded Signup قبل مراجعة اسم العرض ما تبدأ أصلا. وأنا لبست في الاتنين أثناء التجربة.</p>

<p>الرقم لازم يبقى <strong>بيستقبل OTP.</strong> ميتا بتتصل أو بتبعت كود من ست أرقام أثناء البوب-أب عشان تثبت إنك مالك الرقم. أنا حرقت رينج أرقام افتراضية للتجربة موصلوش المكالمة، وبعدين حولت لرقم موبايل مصري عادي والكود وصل في تسع ثواني. لو الكود موصلش، استنى خمس دقايق، اتأكد إن الرقم بيستقبل مكالمات دولية، وبعدين حاول مرة واحدة كمان.</p>

<p>والرقم لازم يبقى <strong>نضيف.</strong> لو متسجل حاليا في واتساب العادي أو واتساب بيزنس أب، الـ Embedded Signup بيرفضه ويقولك "number already in use". امسح الحساب من الأبلكيشن الأول، أو اختار رقم تاني. ونفس الكلام لو مربوط على WABA تانية. شيله، استنى عشر دقايق، وبعدين احجزه.</p>

<p>نصيحة من تجربتي مع المحلات: متستخدمش الرقم الشخصي اللي عليه واتسابك العادي. هات خط جديد للبيزنس وخليه هو رقم الـ Cloud API. كده لما الزبون يسأل "كاش عند الاستلام متاح" مفيش لخبطة بين شغلك وحياتك.</p>

<h2>فحص صحة الاشتراك: القاتل الصامت اللي أنا أتمته</h2>

<p>إنك تتوصل ده نص الشغلانة. إنك تفضل متوصل ده النص التاني، وهنا أنا ضيعت رسائل عميل لمدة ست ساعات قبل ما أفهم إيه اللي حصل.</p>

<p>ميتا بتوصل رسائل الواتساب الداخلة عن طريق اشتراك ويبهوك من طبقتين: اشتراك على مستوى الأبلكيشن بتاعنا، واشتراك على مستوى الـ WABA بتاعتك. أي طبقة فيهم ممكن تقع بصمت بعد تغيير باسورد أو بعد ما أدمن يشيل الأبلكيشن. الرسائل الداخلة بتقف والإرسال بيفضل شغال، فبتبان كأن العملاء سكتوا.</p>

<p>وعشان كده أنا سلمت فحص صحة اشتراك بيشتغل بعد كل توصيلة وعلى جدول دوري. بيسأل الـ Graph API عن الحقول المشتركة في الطبقتين وبيعيد الاشتراك في أي حاجة ناقصة. وانت بتشوف شارة خضرا "Healthy" أو شارة حمرا "Reconnect needed" بتسمي الحقل الناقص بالظبط.</p>

<table>
<thead>
<tr><th>الخطوة</th><th>إيه اللي بيحصل</th><th>إيه اللي بيبوظ</th><th>الحل</th></tr>
</thead>
<tbody>
<tr><td>1. بوب-أب الـ Embedded Signup</td><td>ميتا بترجع كود تفويض للـ WABA والرقم</td><td>بورتفوليو غلط، البوب-أب اتقفل بدري، رفضت سكوب</td><td>وصل تاني واختار البورتفوليو مالك الدومين ووافق على كل السكوبات</td></tr>
<tr><td>2. تبديل الكود</td><td>الباك-إند بيبدل الكود بالـ WABA ID والـ phone-number ID والتوكن</td><td>الكود انتهى بعد خمول طويل في البوب-أب</td><td>شغل البوب-أب من الأول، الأكواد بتنتهي في دقايق</td></tr>
<tr><td>3. الرقم واسم العرض</td><td>تحقق OTP ومراجعة اسم العرض اتقدموا</td><td>اسم عام زي Customer Support أو إيموجي أو رقم شغال في الأب</td><td>اسم البيزنس بالحرف مطابق للدومين ورقم نضيف بيستقبل OTP</td></tr>
<tr><td>4. اشتراك الويبهوك</td><td>حقول طبقة الأب وطبقة الـ WABA اشتركوا</td><td>أدمن شال الأب أو ريست باسورد وقع الاشتراك</td><td>فحص الصحة بيعيد الاشتراك أو دوس Reconnect</td></tr>
<tr><td>5. فحص الصحة</td><td>الحقول المطلوبة متأكدة في الطبقتين</td><td>شارة حمرا بتسمي حقل ناقص</td><td>إعادة اشتراك بضغطة واحدة من شاشة التوصيلات</td></tr>
<tr><td>6. استيراد السجل</td><td>شاتات الماسنجر والإنستجرام بتتحمل بالسياق</td><td>آلاف الشاتات بتبطئ أول مزامنة والستيكرات شكلها غريب</td><td>سيب الكيو يخلص، الستيكرات بتتعرض صور، وبص على التشخيص</td></tr>
<tr><td>7. أول إرسال جماعي</td><td>برودكاست قوالب مع عداد الواصلين حاليا</td><td>غلطة 2018278 على أرقام قديمة خارج نافذة 24 ساعة</td><td>الأرقام القديمة بتتخطى أوتوماتيك وابعت قالب تفتح النافذة</td></tr>
</tbody>
</table>

<p>أنا ببص على شارة الصحة دي زي ما ببص على سيرفراتي. لو حمرا، مفيش حاجة تانية ليها معنى لحد ما تخضر تاني.</p>

<h2>طريق إنستجرام دايركت اللي معظم الشروح بتطنشه</h2>

<p>الواتساب واخد العناوين، بس نص عملائي بيوصلوا الإنستجرام في نفس القعدة. وأنا سلمت طريق الـ Instagram Direct جنب الـ Embedded Signup لأن الفشل واحد: الفاوندر بيوصل، بيستنى السجل، بيشوف إنبوكس فاضي، فيفتكر إن التوصيلة باظت.</p>

<p>تسجيل دخول إنستجرام بيزنس بيوصل حسابك الاحترافي عن طريق Facebook Login وبيشترك في نفس ستاك الويبهوك. الصلاحية اللي تفرق هي instagram_manage_messages زائد pages_messaging للصفحة المربوطة. لو وصلت حساب إنستجرام شخصي مش مربوط بصفحة فيسبوك، الأحداث مش هتوصل أبدا. حول لحساب بيزنس، اربط الصفحة، وبعدين وصل تاني.</p>

<p>ولو صفحة الفيسبوك نفسها مظهرتش أثناء التوصيل، ده غالبا مش مشكلة سكوب. دي مصيدة الـ Business Portfolio: نقطة /me/accounts بتاعة ميتا بترجع بس الصفحات اللي عندك عليها دور مباشر، مش الصفحات المتسلمة عن طريق البورتفوليو. وأنا كاتب الحل الكامل في <a href="https://ot1-pro.com/blog/facebook-page-not-showing-business-portfolio-fix">Facebook Page Not Showing? Business Portfolio Fix</a>. اقراه قبل ما تلوم البوب-أب.</p>

<p>والحتة دي بتفرق مع بتوع الملابس والميكب. الزبونة بتبعتلك على الإنستجرام "بكام الشحن كاش عند الاستلام لطنطا"، وبعد يومين تبعتلك على الواتساب "فين الأوردر بتاعي مع بوستة". لو الإنبوكس مش جامع الاتنين، هترد عليها كأنها زبونة جديدة.</p>

<h2>استيراد سجل الشات كامل: ذكاء اصطناعي فاهم من أول يوم</h2>

<p>دي الميزة اللي أنا فخور بيها أكتر حاجة في الإصدار ده. لحظة ما بتوصل الماسنجر أو الإنستجرام، OT1-Pro بيستورد سجل الشات الكامل لكل محادثة على الصفحة، مش بس الرسائل الجديدة اللي جاية.</p>

<p>ليه بنيتها كده: وكيل مبيعات ذكاء اصطناعي من غير سجل ملوش لازمة أول شهر. ميعرفش إن أحمد سأل عن التقسيط من تلات أسابيع. مع الاستيراد، الذكاء الاصطناعي بيقرا الثريد القديم قبل أول رد. على صفحة تجريبية عندي فيها 400 ثريد ماسنجر، أول رد ذكاء اصطناعي أشار لشكوى شحن من شهرين، والعميل رد "أخيرا حد فاكر".</p>

<p>أول ما بتوصل بنقلب في نقطة محادثات الصفحة صفحة صفحة، بنسحب كل ثريد من الأحدث للأقدم، وبنعلم حالة المزامنة لكل ثريد. وشاشة التوصيلات بتعرض تشخيص المزامنة: ثريدز لقيناها، واتستوردت، وفشلت، وآخر وقت مزامنة. ولو وصلت صفحة فيها 5000 ثريد، سيب الكيو يخلص. الذكاء الاصطناعي بيبقى أشطر كل ما الاستيراد يكمل.</p>

<p>تفصيلة خدت مني عصر كامل: الستيكرات. المستورد القديم كان بيخزنها كنص "[Sticker]"، فالذكاء الاصطناعي كان بيرد زي روبوت تايه والإنبوكس بيعرض فقاعة رمادية. غيرتها تحفظ رابط صورة الستيكر وتعرضه كفقاعة صورة، مع ليبل نصي بس كبديل لقارئ الذكاء الاصطناعي.</p>

<p>وتخيل معايا سيناريو البوستة والأرامكس. زبون بعتلك من شهر "الأوردر اتأخر مع بوستة". النهارده بيسأل "بكام الجاكت". الذكاء الاصطناعي اللي عنده السجل هيرد بالسعر ويفكره باعتذار القديم ويقترح أرامكس. الجملة دي لوحدها بتقفل بيعة.</p>

<h2>نافذة الـ 24 ساعة وغلطة 2018278 المشهورة</h2>

<p>دي القاعدة اللي بتبوظ أول برودكاست واتساب لكل فاوندر: <strong>بره نافذة خدمة العملاء الـ 24 ساعة، مينفعش تبعت نص حر.</strong> لو العميل بعتلك خلال آخر 24 ساعة، تقدر ترد عادي. ولو لأ، الـ Graph API بيرجع غلطة 2018278 برسالة "The message was not sent because it was sent outside the allowed time frame" وبيوقع الإرسال.</p>

<p>أنا بقتبس كود الغلطة ده بالحرف لأنك هتشوفه. دور في اللوجز على 2018278 وهتلاقي الكونتاكتس اللي نافذتهم انتهت. مفيش إعادة محاولة بتصلحها. الطريقة الوحيدة تفتح النافذة هي رسالة قالب معتمدة مسبقا، العميل يقدر يرد عليها، فتتفتح 24 ساعة شات عادي.</p>

<p>وأنا بنيت الإرسال الجماعي على الصراحة دي. لما بتختار 800 كونتاكت، OT1-Pro بيشيك على وقت آخر رسالة داخلة لكل كونتاكت الأول. القدام بيتخطوا أوتوماتيك، وبيتحسبوا "skipped: window expired"، وبتشوف عداد الواصلين حاليا قبل ما أي حاجة تتبعت. وبضغطة واحدة بتبعت القالب المعتمد للمجموعة المتخطية عشان تفتحهم.</p>

<p>مثال عملي: عندك 800 رقم، منهم 450 بعتولك "بكام" آخر يومين، و350 من شهرين. الـ 350 هيقعوا بـ 2018278 لو بعتلهم نص حر. النظام بيقولك: الواصلين حاليا 450، والـ 350 محتاجين قالب يفتح النافذة.</p>

<h2>سلم الـ Tier: من 1000 لغير محدود وبرودكاست واحد يرجعك</h2>

<p>كل رقم واتساب جديد بيبدأ في <strong>Tier 1: ألف محادثة بيزنس في 24 ساعة.</strong> محادثة بيزنس معناها انت اللي بدأت بقالب. ردود العملاء جوه النافذة مبتتحسبش من السقف. والسلم من هناك ثابت: <strong>Tier 1 ألف في 24 ساعة وTier 2 عشرة آلاف وTier 3 مية ألف وTier 4 غير محدود.</strong> ميتا بتنقلك تلقائيا لما تقييم الجودة يفضل عالي وحجمك يبرر، تقريبا على نافذة متحركة سبع أيام. ومفيش فورم تقديم للمستوى اللي بعده.</p>

<p>تقييم الجودة هو البوابة. أخضر يعني صحي، أصفر تحذير، أحمر مقيد. لو نزلت للأحمر ميتا بترجعك لتحت خلال يوم. أنا شفت فاوندر على Tier 2 ضرب ليستة مشتراة ستة آلاف رقم بارد، لم بلوكات، وصحي لقى نفسه راجع Tier 1 والباقي مقفول. والسقف لكل رقم، يعني رقم تاني على نفس الـ WABA بيحتفظ بالمستوى بتاعه.</p>

<p>قواعدي: متبدأش رقم جديد بضربة باردة، سخن الأول بحركة عملاء موافقين، ووقف أي قالب معدل البلوك بتاعه بينط.</p>

<h2>حسبة الدولار: عمولة الـ BSP مقابل 79 دولار ثابت</h2>

<p>معظم الـ BSPs بياخدوا على كل محادثة فوق رسوم ميتا: رسوم ميتا زائد 0.02 لـ 0.05 دولار على محادثة الماركتنج كهامش، زائد اشتراك شهري 49 لـ 149 دولار. على عشرين ألف محادثة ماركتنج في الشهر، هامش 0.03 دولار لوحده يعني 600 دولار هامش قبل الاشتراك. وعلى خمسين ألف يعني 1500 دولار.</p>

<p><a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a> ثابت: 79 دولار في الشهر ورسوم ميتا بتعدي بالتكلفة. على عشرين ألف محادثة بتدفع 79 زائد فاتورة ميتا بدل 600 هامش زائد اشتراك. والتعادل ضد BSP بهامش 0.03 بييجي حوالين 2500 محادثة في الشهر.</p>

<p>ودلوقتي تكلفة Tier-1 لبرودكاست واحد غلط. افترض متوسط الأوردر 25 دولار والقايمة بتحول 3%. إرسال نضيف لألف كونتاكت موافق يعني 30 أوردر بـ 750 دولار. اضرب ميزانية الـ Tier-1 في ليستة مشتراة قديمة، لم بلوكات، والأيام اللي بعدها تحول قرب الصفر وميتا مثبتاك على Tier 1 أسبوع كمان: تقريبا 5000 دولار إيراد ضايع زائد تأخير إعادة الموافقة. أنا أفضل أتخطى 400 كونتاكت قديم أوتوماتيك على إني أحرق الرقم عشان رقم sent منفوخ.</p>

<p>قارن الصورة الكاملة في <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs WATI</a>. عمولات الرسالة بتبان صغيرة لحد ما تضربها في حجم البرودكاست الحقيقي.</p>

<h2>هتعمل إيه في أول ساعة ليك</h2>

<p>لو أنا بوصل النهارده: Embedded Signup برقم بيستقبل OTP، اسم البيزنس بالظبط زي عنوان الهومبيج، شارة الصحة خضرا قبل ما تعزم التيم، إنستجرام دايركت في نفس القعدة، سيب استيراد السجل يخلص، وبعدين أول برودكاست لأرقام موافقة مع قراية عداد الواصلين حاليا بصوت عالي.</p>

<p>الساعة دي بتشتريلك رقم Cloud API رسمي من غير خطوة ديفيلوبر، وذكاء اصطناعي فاكر العملاء من أول يوم، وأرقام تثق فيها. والزبون اللي بيسأل "بكام" هيلاقي رد، واللي بيقول "كاش عند الاستلام" هيلاقي تأكيد، واللي بيسأل على الشحن مع بوستة أو أرامكس هيلاقي إجابة فيها سجل شحناته القديمة. <a href="https://ot1-pro.com/register">Start free here</a> من غير كريدت كارد.</p>

<p>{{CTA}}</p>
HTMLP9,
                'meta_title'        => 'ربط واتساب الرسمي 2026: استيراد الشات كامل',
                'meta_description'  => 'ربطت واتساب Cloud API من غير ديفيلوبر: رقم OTP واسم عرض مقبول واستيراد كل الشاتات وفحص صحي وطبقات الإرسال وغلطة 2018278 — دليل ربط واتساب الرسمي',
                'category'          => 'واتساب بيزنس',
                'author'            => 'Omar Eltak',
                'language'          => 'ar',
                'is_rtl'            => true,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 10. closed-deals-to-google-sheet-transparent-ai-credits-ar (ar) ──
            [
                'title'   => 'البيعات المقفولة في Google Sheet: كريدت شفاف وإكسل جماعي من غير حظر',
                'slug'    => 'closed-deals-to-google-sheet-transparent-ai-credits-ar',
                'excerpt' => 'وصلت OT1-Pro إن كل بيعة واتساب مقفولة تنزل في Google Sheet الأوبريتور أوتوماتيك لحظة القفلة، وضفت دفتر كريدت بيطلع إيصال لكل رد ذكاء اصطناعي، وخنقت الاستيراد الجماعي للإكسل عشان الأرقام الجديدة تعيش. حدود حقيقية وأرقام حقيقية وحسبة العائد: 400 استفسار بيبقوا 74 بيعة زيادة.',
                'content' => <<<'HTMLP10'
<p><strong>أنا ضيعت 11 بيعة مقفولة جوه Google Sheet في شهر مارس.</strong> مش ضاعوا بمعنى الزباين مشيوا. لأ. ضاعوا بمعنى إن الكلوزر بتاعي قفلهم على واتساب، والفلوس اتحركت، ومحدش كتب الصف في الشيت لمدة ست أيام. ولما جينا نراجع، لقينا زبونين بيطلبوا إيصالات مش لاقيينها، وليد مكررة اتباعت لها نفس العرض مرتين، والمحاسب حسب علي أربع ساعات زيادة تنضيف. أنا بنيت <a href="https://ot1-pro.com">OT1-Pro</a> عشان زهقت من الفجوة بين البيعة اللي بتتقفل في الشات والبيعة اللي موجودة فعلا في أي حتة مفيدة.</p>

<p>البوست ده بيوثق تلات أنظمة أنا شحنتها عشان أقفل الفجوة دي، بأرقام حقيقية وحدود حقيقية: تبويبة الـ Connectors في الـ AI-config اللي بترمي كل قفلة في الـ Google Sheet بتاعك لحظة ما بتحصل، ودفتر كريدت الذكاء الاصطناعي الشفاف اللي بيطلع إيصال لكل كريدت، ومستورد الإكسل الجماعي المخنوق لحملات الواتساب والإيميل. كل سلوك تحت ده شغال في البرودكشن النهاردة. والخلاصة: وصل الشيت مرة واحدة، و<code>EVENT_DEAL_CLOSED</code> و<code>EVENT_LEAD_CAPTURED</code> بينزلوا فيه أوتوماتيك، والاستيراد الجماعي بيعدي على wizard من 5 خطوات مع سقف <strong>2MB</strong> وdropdown تليفونات من <strong>30 دولة</strong>، والإرسال الجماعي مخنوق لكل تيم بسقوف شهرية <strong>Free 1 و Starter 5 و Pro 25</strong>، وكل رد ذكاء اصطناعي بيتخصم من دفتر تقدر تراجعه سطر سطر. والحسبة في الآخر: 400 استفسار في الشهر بيبقوا 74 بيعة زيادة بقيمة 6,290 دولار على متوسط أوردر 85 دولار — يعني بالمصري حوالي 302 ألف جنيه.</p>

<h2>1. البيعة اللي قفلت على واتساب وموصلتش للشيت أبدا</h2>

<p>التيم بتاعي كان بيبيع زي معظم التيمات الصغيرة في مصر والسعودية والإمارات: الكلام بيحصل على واتساب، والزبون يقول تمام، والمندوب يتنقل للشات اللي بعده. والشيت المفروض يتحدث بعد الشيفت. ومكانش بيتحدث — على الأقل مش بشكل يعتمد عليه. أنا عملت مراجعة لشهر واحد ولقيت <strong>11 بيعة مقفولة</strong> ملهاش صف في الشيت، و6 صفوف في الشيت ملهاش محادثة مقابلة، و3 زباين اتعرض عليهم منتجات هم اشتروها أصلا.</p>

<p>النصيحة التقليدية هي اشتري CRM. والعرض السعري الصادق اللي جالي لـ HubSpot مع WhatsApp BSP كان حوالي <strong>950 دولار في الشهر</strong> — يعني بالمصري حوالي 45,600 جنيه — بعد ما رصيت السيتات ورسوم المحادثات وزيادات التمبلت فوق بعض. اشتراك Pro في <a href="https://ot1-pro.com/pricing">OT1-Pro بـ 79 دولار في الشهر</a> — يعني حوالي 3,800 جنيه. لتيم من 3 لـ 30 واحد بيبيعوا من الشات، شيت بيتغذى أوتوماتيك بيغطي 90% من تشيك الصبح: مين قفل، بكام، من أنهي قناة، ومين لسه محتاج زقة. وقضية المتابعة نفسها مشروحة في صفحة <a href="https://ot1-pro.com/lead-follow-up-software">lead follow-up software</a> بتاعتنا. أنا بطلت أطلب من المناديب يفتكروا الشيت وخليت إيفنت القفلة نفسه هو اللي يكتب الصف.</p>

<h2>2. البيعات المقفولة بتوصل Google Sheets من غير Zapier في النص</h2>

<p>التنفيذ عايش في <strong>تبويبة الـ Connectors جوه الـ AI-config</strong>. بتلزق لينك شيت الأوبريتور مرة واحدة، وبتعمل ماب للأعمدة مرة واحدة، والسيستم بيستلم من بعدها. مفيش سيناريو Zapier بيقف ساكت عند ليمت التاسكات، ومفيش إكسبورت CSV ليلي حد لازم يفتكره. الدفع event-driven وبيضرب لحظة ما الحالة بتتغير في الإنبوكس.</p>

<p>كل دفعة بتعدي من نقطة خنق واحدة اسمها <code>SalesConnectors::notify</code>، بعد ما نسخة أولى كان فيها 3 call sites بيفرمتوا الـ payloads بتلات طرق مختلفة شوية علمتني الدرس. فيه hook واحد للمحادثة وhook واحد للكونتاكت، والاتنين بيندهوا نفس الـ notifier:</p>

<ol>
<li><strong>hook المحادثة</strong> بيضرب لما نتيجة المحادثة بتتقلب لمقفولة، وشايل معاه آي دي المحادثة والتيم والقناة والكلوزر والقيمة المتفق عليها ورسالة إشارة الشراء.</li>
<li><strong>hook الكونتاكت</strong> بيضرب لما سجل الكونتاكت يتغير تجاريا: ليد جديد اتلقط، تليفون اتأكد، مكرر اتدمج، شريحة قيمة اتعينت.</li>
</ol>



<h2>3. إيفنتين بس: EVENT_DEAL_CLOSED و EVENT_LEAD_CAPTURED</h2>

<p>أول نسخة كانت بتدفع كل حاجة والأوبريتورز عملوا mute للتبويبة خلال أسبوع. فقطعت السطح لإيفنتين بالظبط — اللي الأونر بيفتح الشيت عشان يشوفهم:</p>

<ol>
<li><strong>EVENT_LEAD_CAPTURED</strong> بيضرب لحظة ما المشتري بيتعرف: اسم مع تليفون متأكد منه أو إيميل، وقناة المصدر، وأول سطر نية، وسكور التأهيل الافتتاحي. أول الفانل، في الشيت والليد لسه سخن.</li>
<li><strong>EVENT_DEAL_CLOSED</strong> بيضرب لما النتيجة بتتقلب لـ won: الكونتاكت، والقيمة النهائية، والمنتج أو الباكدج، والقناة، ونسبة القفلة للكلوزر أو وكيل الذكاء الاصطناعي، مع deep link راجع للمحادثة للمراجعة بضغطة واحدة.</li>
</ol>

<p>إيفنتين بيوصلوا بثقة يكسبوا عشرين بيوصلوا بدوشة. والـ deep link هو التفصيلة اللي أنا فخور بيها أكتر حاجة: لما المحاسب سألني عن صف غريب بـ 85 دولار — يعني حوالي 4,080 جنيه — ضغطة واحدة ورته الثريد وتأكيد الدفع وملخص الذكاء الاصطناعي. ولو التيم بتاعك بيهندل الاعتراضات في الشات قبل القفلة، جوز البوست ده مع <a href="https://ot1-pro.com/blog/ai-sales-agent-objection-handling-playbook">دليل هندلة الاعتراضات لوكيل المبيعات</a>، الدليل الشقيق للبوست ده.</p>

<h2>4. ملف الإكسل اللي كان بيبوظ أرقام الموبايلات</h2>

<p>كل تيم عنده ملف إكسل مقبرة: 800 رقم من بوث معرض، و2,000 إيميل من سيستم محل قديم. سلمه لأصغر مندوب مع أداة بث وسمعة المرسل بتموت خلال أسبوع. أنا أعدت بناء المستورد بتاعنا حوالين تلات أنماط فشل تذاكر الدعم كانت بتوريهاني كل يوم.</p>

<p><strong>التدوين العلمي.</strong> إكسل بيرندر الرقم الـ 11 خانة زي 201026361218 على شكل <code>2.011E+11</code> لحظة ما العمود يبقى General format. استورد ده حرفيا وهتبعت رسالة لرقم ميت أو لحد غريب. المستورد بتاعنا بيكشف خلايا الـ sci-notation وبيقف ويديك تعليمة واضحة: أعد تصدير العمود كـ Text وارفع تاني. بيرفض يخمن. أنا أفضل أرفض ملفك على إني أحرق الـ delivery rate بتاعك على 400 صف بايظ.</p>

<p><strong>أحجام الملفات الغامضة.</strong> دايما حد بيرفع إكسبورت 40MB مع pivot caches، والـ worker بيعمل timeout، ونص الليستة بيتبعت. السقف معلن قبل ما تختار الملف: <strong>2MB</strong>. الملفات الأكبر بتتقسم أو بتتصغر. وللحالة الحدية الحقيقية، الملف فوق السقف بيظهر مخرج طوارئ واتساب الفاوندر، لينك مباشر تكلمني عليه عشان أقسمه لك يدوي. بيستخدم حوالي مرتين في الشهر.</p>

<p><strong>روليت كود الدولة.</strong> ليستة خلطانة أرقام مصرية وسعودية وإماراتية من غير عمود دولة دي كارثة توصيل. المستورد بيجبر <strong>dropdown من 30 دولة</strong> default لكل استيراد، وبيتحقق من كل صف ضد نمط أرقام الدولة دي، وبيطلع <strong>عدادات الـ skipped والـ invalid</strong> قبل ما أي حاجة تتبعت، وكل واحدة فيهم قابلة للتنزيل كـ CSV لوحدها. إنك تشوف 1,740 valid و183 invalid و77 skipped مكرر قبل ما تصرف كريدت واحد بيغير القرارات اللي بتاخدها.</p>

<h2>5. الـ wizard الخمس خطوات: Upload و Map و Compose و Test و Launch</h2>

<p>المستورد الـ async بيشتغل بنفس الطريقة بالظبط <strong>لـ wizard الواتساب وwizard الإيميل</strong>. خمس خطوات بترتيب ثابت، مفيش نط:</p>

<ol>
<li><strong>Upload.</strong> ارمي ملف الإكسل. فحص سقف الـ 2MB بيحصل فورا، وأسماء الشيتات بتتعرض، وبتختار تبويبة الكونتاكتس. الـ parsing بيشتغل في background job، فملف 1,700 صف عمره ما بيهنج المتصفح بتاعك.</li>
<li><strong>Map.</strong> طابق الأعمدة على الاسم والتليفون أو الإيميل والدولة ومعاك لحد تلات custom fields. حارس الـ sci-notation وفحص أرقام الـ 30 دولة بيشتغلوا هنا، مع سبب فشل لكل صف بلغة واضحة.</li>
<li><strong>Compose.</strong> اكتب الرسالة مع placeholders للحقول. الـ placeholders اللي متحلتش بتقع على default محايد انت موافق عليه هنا، مش تاج خام بيسرب لرسالة زبون.</li>
<li><strong>Test.</strong> الـ wizard بيبعت لرقمك وإيميلك انت الأول. الخطوة دي إجبارية من يوم ما فاوندر بعت Hi {FIRST_NAME} لتسعمية واحد. مفيش test ناجح، مفيش launch.</li>
<li><strong>Launch.</strong> الجوب بيتفرد تحت خنقة التيم. عدادات الـ valid والـ sent والـ delivered والـ replied والـ failed بتتراكم لايف، مع عدادات الـ skipped والـ invalid محفوظة لمسار التدقيق.</li>
</ol>

<p>الـ async شايل الليلة. قفل التبويبة في نص الـ launch مش بيوقفه، وريستارت الـ queue بيكمل من آخر صف متأكد عليه بدل ما يعيد الإرسال من الصفر.</p>

<h2>6. الخنقة اللي بتخسرني اشتراكات وبتحمي حسابك</h2>

<p>كل launch جماعي بيشتغل تحت <strong>خنقة لكل تيم</strong> مع <strong>30 لـ 60 ثانية jitter</strong> بين الدفعات، مع سقوف شهرية صلبة حسب الخطة: <strong>Free 1 و Starter 5 و Pro 25 حملة في الشهر</strong>. لما فاوندر Free بيطلب مني أعدي حملة زيادة واحدة بس، بقول لأ. واتساب وجيميل الاتنين بيعاقبوا الإرسال المتفجر: رقم جديد بيضرب 800 رسالة في 9 دقايق بيتقري كبنية سبام مهما كانت الليستة شرعية. الـ jitter من 30 لـ 60 ثانية بيمشي الترافيك كأنه عمليات بشرية. والسقوف الشهرية بتجبر مرسل الجملة لأول مرة ينضف الليستة ويعمل خطوة الـ Test ويقرا reply rate أول حملة قبل ما يضرب التانية. الـ 25 حملة بتاعة Pro كريمة جدا لما الليستة تنضف؛ والحملة الواحدة بتاعة Free عجلة تدريب مقصودة.</p>

<p>وأنا بنشر المقارنة دي من فواتيري أنا: <strong>Pro بـ 79 دولار في الشهر — حوالي 3,800 جنيه — ضد ستاك HubSpot-plus-BSP اللي حوالي 950 دولار في الشهر — حوالي 45,600 جنيه</strong> مع surcharges لكل رسالة. ستاك الـ BSP بيخليك تضرب أسرع، والسرعة دي هي الخطر. أربع تيمات أنا أونبردتهم جم برقم BSP محظور وليستة 40,000 صف خايفين يلمسوها. الأربعة النهاردة بيبعتوا حملات أبطأ وأصغر وأنضف من OT1-Pro مع reply rates أعلى. ولو بتقارن أدوات bulk-first، اقرا <a href="https://ot1-pro.com/vs/wati">مقارنة OT1-Pro ضد WATI</a> قبل ما تلتزم بمنصة إيرادها بيكبر لما انت تبعت زيادة.</p>

<h2>7. دفتر كريدت الذكاء الاصطناعي: كل كريدت معاه إيصال</h2>

<p>كل أكشن ذكاء اصطناعي — رد اتبعت، ثريد اتحلل، سكور تأهيل، متابعة اتكتبت — بيتخصم من رصيد كل تيم، وكل خصم بيكتب صف في <strong>دفتر</strong> تقدر تفتحه وتقراه. نوعين بيغطوا كل حاجة تقريبا: <strong>Message</strong> للشغل لكل رد و<strong>DeepAnalysis</strong> للاستدلال الأتقل على مستوى الثريد. كل صف بيوري المحادثة والأكشن وسلسلة الموديل اللي خدمت والتكلفة. لما فاوندر يسألني 300 كريدت راحوا فين في أبريل، بفتح الدفتر بتاعه وبمشي معاه سطر سطر.</p>

<p>تلات قرارات واجهة مخلياها صادقة. <strong>شريحة العداد</strong> في الهيدر بتوري الرصيد اللايف في كل حتة، فالصرف باين قبل ما يحصل. أي أكشن واحد بيحرق <strong>أكتر من 5 كريدت بيطلع modal تأكيد</strong> بالتكلفة بالظبط وزرار إلغاء؛ والتحليلات التقيلة لثريدات 200 رسالة هي المحفز المعتاد. والسلم مبني <strong>4-tier</strong> معلن مع توب أب <strong>بتحويلات بنكية يدوية</strong> بيراجعها بني آدم، مش كارت بيتسحب أوتوماتيك. فاوندرز المنطقة طلبوا ده صراحة: الكروت بتفشل والليمتس بتضرب، ومحدش عايز وكيل ذكاء اصطناعي ماسك خط مفتوح على الفيزا بتاعته. كل تحويل بيوري الرقم المرجعي وحالة الاعتماد والمبلغ المتضاف، وكلها قابلة للتسوية ضد الدفتر.</p>

<p>التلات ضوابط دول كلهم scar tissue من ويك إند واحدة لما خصومات ساكتة خلت أتمتة معلقة تعيد تحليل نفس الثريد 400 مرة وتاكل كوتة شهر. الدفتر وشريحة العداد وmodal فوق-5-كريدت موجودين عشان الويك إند دي متتكررش أبدا.</p>

<h2>8. حوض الموديلات اللي بيعالج نفسه ورا كل كريدت</h2>

<p>الكريدت بتفضل جديرة بالثقة طول ما الموديلات اللي وراها صاحية. OT1-Pro مش بينده موديل واحد؛ بينده حوض عبر NaraRouter، مع جوب ليلي بيجدد الليستة المتاحة من الـ live endpoint بتاع <code>/v1/models</code>. <strong>مفيش أسماء موديلات متثبتة</strong> في مسار التقديم، عشان الأسماء المتثبتة هي اللي بتصحيك على بروفايدر ميت: الفندور يغير اسم موديل، والسترنج المتثبت يعمل 404، وكل رسالة زبون تفشل لحد ما بني آدم ياخد باله. الحوض بتاعنا بيقدم بس اللي الـ endpoint رجعه فعلا الليلة دي.</p>

<p>ولما الحوض كله يخلص مرة واحدة — كوتة فضيت أو عطل upstream — بيدخل <strong>تهدئة شاملة 30 دقيقة</strong>، تايم ستامب واحد الأسطول كله بيقراه في ميكروثانية. <code>SendAiResponse</code> بيحترمها مباشرة: بدل ما يرزع حوض ميت بـ retries تاكل الكوتة والـ latency، الجوب <strong>بيحرر نفسه على الـ queue مع delay و jitter، محدود بـ tries = 2</strong>. الرسالة بتستنى بهدوء بدل ما تفشل بصوت عالي، وبعدين بتاخد محاولتين عادلتين بعد النافذة. التلاتين دقيقة مطابقة لملف التعافي من 3 حوادث NaraRouter حقيقية: النوافذ الأقصر كانت بترجع لحوض لسه ميت، والأطول كانت بتحتجز الردود رهينة. الكريدت بتتخصم بس على الشغل المتنفذ، عمرها ما بتتخصم على retries ضد حوض ميت. ولو حريق OAuth بتاع ميتا هو اللي شاغلك دلوقتي، ابدأ <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">بدليل توثيق تطبيقات ميتا</a> بتاعي، أكتر حاجة اتقرت لي لسبب وجيه.</p>

<h2>9. الحسبة: 400 استفسار و74 بيعة زيادة و6,290 دولار في الشهر</h2>

<p>شهر عادي لمحل صغير أو عيادة على إعلانات واتساب وإنستجرام: <strong>400 استفسار وارد</strong>. الرد اليدوي في ساعات العمل بيقفل حوالي 12%، يعني 48 بيعة، عشان الليالي والجمعات ونمط الاختفاء-بعد-السعر بياكلوا الباقي. مع أول رد ذكاء اصطناعي فوري، وسيكوينس المتابعة الـ 3-touch، وهندلة الاعتراضات، وكل قفلة بتتدفع للشيت فمفيش حاجة بتقع، نفس الـ 400 استفسار بيقفلوا 122 بيعة. يعني <strong>74 بيعة زيادة</strong>، وعلى <strong>متوسط أوردر 85 دولار — يعني حوالي 4,080 جنيه</strong>، 74 × 85 = <strong>6,290 دولار في الشهر</strong> — يعني حوالي 302,000 جنيه — إيراد مسترد ضد اشتراك Pro بـ 79 دولار.</p>

<table>
<thead>
<tr><th>حجم الاستفسارات الشهري</th><th>ساعات الهندلة اليدوية</th><th>التكلفة المؤتمتة على OT1-Pro</th></tr>
</thead>
<tbody>
<tr><td>100 استفسار</td><td>~9 ساعات شغل مناديب</td><td>0$ زيادة على Free (حملة واحدة شهريا مشمولة)</td></tr>
<tr><td>400 استفسار</td><td>~36 ساعة شغل مناديب</td><td>79$ Pro — حوالي 3,800 جنيه — 25 حملة شهريا والدفتر مشمول</td></tr>
<tr><td>1,500 استفسار</td><td>~135 ساعة شغل مناديب</td><td>79$ Pro — نفس السقف — الخنقة بتحمي المرسل</td></tr>
<tr><td>5,000 استفسار</td><td>~450 ساعة شغل مناديب</td><td>79$ Pro + توب أب كريدت بأسعار السلم</td></tr>
</tbody>
</table>

<p>ساعات اليدوي محسوبة على 5 لـ 6 دقايق انتباه بشري لكل استفسار عبر أول رد والتأهيل والمتابعات وكتابة الشيت؛ عند 400 استفسار دي ~36 ساعة، يعني أسبوع شغل كامل الـ connector والـ wizard والوكيل بيمتصوه دلوقتي. والتكلفة بتفضل ثابتة 79 دولار لحد 1,500 استفسار عشان الخنقة والدفتر بيكبروا مع عمق الطابور مش مع عدد الموظفين. ضد ستاك الـ 950 دولار بتاع HubSpot-plus-BSP، الـ payback بيحصل من أول شهر: 6,290 دولار مستردة — حوالي 302 ألف جنيه — ضد 79 دولار مصروفة.</p>

<p>وخليني أحسبهالك بالمصري على بلاطة: متوسط أوردر 85 دولار يعني حوالي 4,080 جنيه على سعر 48 جنيه للدولار. الـ 74 بيعة زيادة يعني حوالي 302,000 جنيه إضافي في الشهر ضد اشتراك 3,800 جنيه. حتى لو متوسط الأوردر بتاعك 2,000 جنيه بس وبتقفل نص الـ 74، لسه بتتكلم في 74,000 جنيه ضد 3,800 جنيه. الحسبة بتستحمل إنك تغلط فيها جامد ولسه تكسب.</p>

<h2>10. هتعمل إيه يوم الاتنين الصبح</h2>

<p>ترتيب الأونبوردنج بالظبط اللي بمشي بيه التيمات الجديدة، في أقل من ساعة:</p>

<ol>
<li><strong>وصل الشيت الأول.</strong> في تبويبة الـ Connectors جوه الـ AI-config، الزق لينك شيت الأوبريتور وأكد إن صف تجربة واحد <code>EVENT_LEAD_CAPTURED</code> نزل، وبعدين اقفل محادثة تجربة وأكد إن صف <code>EVENT_DEAL_CLOSED</code> نزل مع الـ deep link بتاعه.</li>
<li><strong>استورد أصغر ليستة مش أكبر واحدة.</strong> مشي ملف 200 صف في Upload و Map و Compose و Test و Launch على الحملة الشهرية الواحدة بتاعة Free. اقرا عدادات الـ skipped والـ invalid، صلح الملف المصدر، واتفرج على الحملة التانية وهي بتكسب الأولى في الـ reply rate.</li>
<li><strong>راجع الدفتر كل جمعة.</strong> خمس دقايق: بص على نسبة Message لـ DeepAnalysis، وأكد إن مفيش ثريد معلق تحليلات، ووافق على modals فوق-5-كريدت بقصد.</li>
<li><strong>سيب الحوض يعالج نفسه.</strong> لو الردود وقفت على مستوى الأسطول، بص على حالة التهدئة قبل ما تلمس الإعدادات. نافذة الـ 30 دقيقة غالبا بتكون بتعد تنازلي والرسايل بتتصرف بمحاولتيها السالمتين.</li>
<li><strong>قيس الـ 74.</strong> عد القفلات من الشيت آخر الشهر، واضرب الزيادة في متوسط الأوردر الحقيقي بتاعك، وقارن ضد 79 دولار. السيستم بيستاهل تمنه عند أي متوسط أوردر فوق 20 دولار — يعني حوالي 960 جنيه — عشان البيعات المستردة بتتراكم شهريا.</li>
</ol>

<p>أنا بدأت OT1-Pro عشان أبطل أضيع بيعات بين الشات والشيت. تبويبة الـ Connectors والـ wizard المخنوق ودفتر الكريدت الشفاف قفلوا الفجوة دي للتيم بتاعي، وحوض المعالجة الذاتية بيخليهم صاحيين طول الليل. ابدأ free، ووصل شيت واحد، واستورد ليستة صغيرة واحدة، واتفرج على الدفتر. <a href="https://ot1-pro.com/register"><strong>ابدأ ببلاش النهاردة ←</strong></a></p>

{{CTA}}
HTMLP10,
                'meta_title'        => 'مزامنة جوجل شيت لبيعات واتساب',
                'meta_description'  => 'كل بيعة واتساب مقفولة بتنزل في شيت الأوبريتور أوتوماتيك، وكل كريدت ذكاء اصطناعي ليه إيصال في دفتر شفاف، والإرسال الجماعي مخنوق وآمن مع مزامنة جوجل شيت',
                'category'          => 'أتمتة',
                'author'            => 'Omar Eltak',
                'language'          => 'ar',
                'is_rtl'            => true,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

        ];
    }
}
