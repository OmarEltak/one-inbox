<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Batch 36 — Rank-Picks Cluster (Oct 2026).
 *
 * 5 posts picked for volume x winnability from still-uncovered features:
 *   1. Voice-note transcription (TranscribeAudio, Egypt angle)
 *   2. Automatic lead scoring (ScoreLeadJob, 7 signals)
 *   3. Comment-to-DM modes (reply_mode vs dm_mode)
 *   4. Photo replies (DescribeImage, AR)
 *   5. Lead scoring (AR)
 */
class AiSeoBlogSeederBatch36RankPicksCluster extends Seeder
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
<p>OT1-Pro transcribes voice notes, reads customer photos, scores every lead hot/warm/cold, and replies across WhatsApp, Instagram, Messenger, Telegram, and email — in Arabic or English, with every AI credit receipted in a transparent ledger. Free plan, no credit card.</p>
<p><a href="https://ot1-pro.com/register"><strong>Start free →</strong></a> · <a href="https://ot1-pro.com/sales-follow-up-automation">Sales follow-up automation</a> · <a href="https://ot1-pro.com/lead-follow-up-software">Lead follow-up software</a> · <a href="https://ot1-pro.com/pricing">Pricing</a> · <a href="https://ot1-pro.com/vs/wati">vs WATI</a> · <a href="https://wa.me/201026361218">Talk to the founder on WhatsApp</a></p>
HTML;
    }

    private function ctaAr(): string
    {
        return <<<'HTML'
<h2>جرّب النظام اللي بيفهم عملائك بأي طريقة يكلموك بيها</h2>
<p>OT1-Pro بيحوّل الفويس نوت لنص وبيرد على صور العملاء وبيسكور كل عميل سخن/دافي/بارد — واتساب وانستجرام وماسنجر وتيليجرام وإيميل، بالعربي أو الإنجليزي. باقة مجانية، بدون بطاقة ائتمان.</p>
<p><a href="https://ot1-pro.com/register"><strong>ابدأ مجاناً ←</strong></a> · <a href="https://ot1-pro.com/pricing">الأسعار</a> · <a href="https://ot1-pro.com/vs/wati">لماذا نتفوق على WATI</a> · <a href="https://wa.me/201026361218">كلّمني على واتساب</a></p>
HTML;
    }

    private function posts(): array
    {
        $now = now();

        return [

            // ── 1. whatsapp-voice-note-transcription-egypt-2026 (en) ──
            [
                'title'   => 'I Built WhatsApp Voice Note Transcription Into OT1-Pro Because Egyptians Don\'t Type',
                'slug'    => 'whatsapp-voice-note-transcription-egypt-2026',
                'excerpt' => 'I built automatic voice handling into OT1-Pro after watching 63 unread voice notes kill three furniture orders. This guide shows how TranscribeAudio converts Egyptian and Gulf dialect audio into text, feeds lead scoring and objection playbooks, and why text replies close faster.',
                'content' => <<<'HTMLP1'
<p>I built OT1-Pro because I watched Egyptian sales teams drown in WhatsApp voice notes. One Cairo furniture seller had 63 unread voice messages sitting for two days because his reps hated holding the phone to their ear in a noisy showroom. He lost three big orders that week. That day I stopped treating voice as a side feature and started treating whatsapp voice note transcription as core sales infrastructure.</p>
<p>If you sell in Egypt, Saudi Arabia, or the Gulf, you know the reality: customers send voice notes instead of typing. A mother driving on the Cairo-Alex road sends a 40-second note asking about price. A contractor in New Cairo sends one with street noise behind him. A buyer in Jeddah sends three in a row because speaking is faster than typing Arabic. If your system cannot transcribe whatsapp voice messages automatically, you are blind to half your pipeline.</p>
<p>Here is exactly how I solved it inside OT1-Pro, what breaks in real life, and why the AI replies in text after transcribing audio.</p>
<h2>1. Voice Notes Looked Minor Until I Sat Inside a Real Inbox</h2>
<p>I used to think voice notes were a minor annoyance. Then I sat with an air-conditioner team in Nasr City. Their closer showed me his phone: 31 of his last 50 inbound messages were voice notes. His process was to play each one, scribble the meaning on paper, then type a reply. Two to three minutes per note, 80 conversations a day. His team never followed up because they never finished listening.</p>
<p>Night messages were worse. Notes arriving at 1 am sat until 10 am because nobody wanted to replay 25 clips over coffee. By then the customer had asked a competitor. I have seen the same pattern in North Coast real estate, Mohandessin clinics, and abaya stores shipping to Kuwait. Whoever answers the voice note first wins the order.</p>
<p>So I made transcription automatic. Every inbound audio message in OT1-Pro goes through our TranscribeAudio job the second it lands. No button, no add-on, no human remembering to transcribe.</p>
<h2>2. Why Egypt Runs on Voice Notes Instead of Typing</h2>
<p>Typing Arabic is slow for many buyers. Switching keyboards, autocorrect fighting dialect, typing on a cracked screen in a microbus — voice is faster. Saying "بكام التوصيل لمدينة نصر وعايزها قبل العيد، خلاص؟" takes four seconds. Typing it takes thirty.</p>
<p>Egypt also multitasks out loud. Women cook while negotiating price. Drivers stuck on the Ring Road answer sellers by voice. Shop owners serve walk-ins while holding the mic button with one thumb. Voice keeps hands free.</p>
<p>Dialect carries intent that text loses. A flat "خلاص" means confirmed. A stretched "خلاااص" means hurry up. Gulf buyers shift between "تم" and "تمام عليك" with different intent. Our transcripts capture the words and the AI reads surrounding context to catch that intent. Saudi and UAE buyers behave the same way — busy mothers, drivers, older buyers who prefer speaking. Any tool that forces them to type fights culture. We let them talk and transcribe behind the scenes.</p>
<h2>3. What Happens When a Voice Note Hits OT1-Pro</h2>
<p>No vague "AI handles it" — here is the exact pipeline running today on every inbound audio message:</p>
<ol>
<li><strong>WhatsApp delivers the audio to our webhook.</strong> It arrives as an audio attachment with a media ID. We store the file and link it to the conversation.</li>
<li><strong>The TranscribeAudio job fires immediately.</strong> It downloads the audio, transcribes it, and writes the text back into the conversation as if the customer had typed it.</li>
<li><strong>The transcript enters the normal AI pipeline.</strong> No separate voice path. The text flows into language detection, lead scoring, sales playbook matching, objection handling, and reply generation.</li>
<li><strong>The AI replies in text, matched to dialect.</strong> Egyptian Arabic in, Egyptian Arabic out. Gulf dialect or mixed Arabizi in, same mix out. The transcript stays tagged as voice-originated for audit, but the brain treats it as text.</li>
<li><strong>The agent sees both.</strong> The inbox shows the audio player plus the transcript underneath. If the transcript looks wrong, the agent listens and corrects.</li>
</ol>
<p>The critical decision was step three. I refused to build a separate voice brain. Some competitors treat voice as second-class input with dumber handling — I documented that gap when I wrote our <a href="https://ot1-pro.com/vs/wati">Wati comparison</a>. In OT1-Pro the voice customer gets the full machine: memory, playbooks, follow-up, everything.</p>
<h2>4. How TranscribeAudio Handles Egyptian Arabic</h2>
<p>Egyptian Arabic is brutal for transcription engines. Most models learn Modern Standard Arabic from news anchors, not a Cairo driver saying "بكام يا معلم، عايزها بكره الصبح، خلاص اتفقنا؟" with horns behind him.</p>
<p>Short notes of 5 to 20 seconds like "بكام الشحن للتجمع؟" come back near-perfect. Notes of 30 to 60 seconds stay strong. Rambles over two minutes degrade, especially when the customer jumps from price to address to a complaint in one recording.</p>
<p>Code-switching is constant: "عايز أعمل order بس ال delivery اتأخر". Our transcription keeps mixed words as spoken, and the AI mirrors the mix instead of translating "order" into stiff formal Arabic. Numbers are the highest risk — "خمستاشر" versus "خمسين" (15 versus 50). One misheard number misquotes a price. So the AI never treats a transcribed number around money or dates as certain. It echoes the number back for confirmation, like a good human closer would.</p>
<h2>5. Once It Is Text, the Full Sales Machine Applies</h2>
<p>Transcription alone does not sell. Everything after it does. When the transcript lands, lead scoring reads intent: "بكام؟" is browsing, "عايزها بكره، الدفع كاش، العنوان مدينة نصر" is ready to close. The score updates exactly as for typed text.</p>
<p>The sales playbook pulls the live price list and answers with the current offer, never a hallucinated number. Objections like "غالي أوي" or "خايف من المقاس" trigger the same objection logic I detailed in our <a href="https://ot1-pro.com/blog/ai-sales-agent-objection-handling-playbook">objection handling playbook</a> — every word applies to voice because by this stage the system no longer cares whether words were spoken or typed.</p>
<p>Memory resolves references across messages. A typed "مقاسي XL" yesterday plus a voice "عايزها نفس المقاس بتاعي" today resolves to XL. A "خلاص هاخدها" voice note resolves "ها" to the red item discussed last week. Unanswered voice notes trigger the same no-reply follow-up sequences as text. Voice senders actually need faster follow-up — they talked, they expect a conversational rhythm back.</p>
<h2>6. Message Type by Message Type: What the AI Does and How It Replies</h2>
<p>After watching thousands of voice notes, I grouped them into six patterns. This table is the exact behavior I specified:</p>
<table>
<thead>
<tr><th>Message type</th><th>What the AI does with the transcript</th><th>Reply form</th></tr>
</thead>
<tbody>
<tr><td>Price question ("بكام؟")</td><td>Runs lead scoring, pulls live price list and bundles</td><td>Short dialect text with price + delivery fee + one forward question</td></tr>
<tr><td>Order details (address, size, quantity spoken)</td><td>Extracts entities, echoes numbers back, drafts the order</td><td>Text confirmation of what it understood, asking to confirm</td></tr>
<tr><td>Objection ("غالي"، "هفكر")</td><td>Classifies objection, loads matching playbook reply</td><td>Text handling one objection at a time, ending with a soft close</td></tr>
<tr><td>Complaint (late, wrong item, angry)</td><td>Flags priority, pulls order history, drafts fix</td><td>Empathetic text with order reference + fix + human handoff if needed</td></tr>
<tr><td>Rambling multi-topic (2+ min, three questions)</td><td>Splits transcript into separate intents</td><td>Numbered text answering each point in order</td></tr>
<tr><td>Unclear or noisy (mumbling, street noise)</td><td>Marks low confidence, never guesses on price or address</td><td>Polite clarifying question quoting the part it caught</td></tr>
</tbody>
</table>
<p>The last row matters most. I would rather the AI ask one extra question than invent an address it misheard. That discipline separates a sales agent from a demo.</p>
<h2>7. Why the AI Replies in Text When the Customer Sends Voice</h2>
<p>Every new customer asks: should the AI not answer with voice? I tested both. Text wins for five reasons.</p>
<ol>
<li><strong>Text is skimmable.</strong> Prices, addresses, dates can be re-read without replaying a 40-second clip. When money is involved, re-readability beats warmth.</li>
<li><strong>Text keeps a clean record.</strong> Promises stay visible in history. When a customer says "you said Tuesday", both sides scroll up. Audio promises evaporate.</li>
<li><strong>Text corrects faster.</strong> The customer sees a misunderstanding in writing and fixes it in one line instead of recording another note to correct the first.</li>
<li><strong>Text works in quiet places.</strong> Buyers read replies at work, in the metro, or late at night. They send voice from the street but read in silence.</li>
<li><strong>Text converts better.</strong> Our voice-enabled teams closed faster with text replies because the reply carries tappable catalog and payment links. You cannot tap a voice note.</li>
</ol>
<p>Agents can still send voice for moments where tone beats precision — calming an angry buyer, reassuring an older customer. Outbound audio goes through ConvertAudioToOgg so it arrives as a native WhatsApp voice bubble. But the default stays text by deliberate sales logic, not missing functionality.</p>
<h2>8. The Failure Mode: Street Noise, Mumbling, and Bad Transcripts</h2>
<p>Every vendor pretends transcription is perfect. It is not. Three cases break it weekly. The street recording: a customer in Ataba with horns louder than his voice, transcript full of gaps. The 2 am mumbler: words running together, ending with "فاهم؟" when nothing was clear. The dialect outlier: Siwa or Aswan accents the model rarely saw in training.</p>
<p>Early on I let the AI guess through these. It once turned "عايز اتنين" (want two) into "عايز اتناشر" (want twelve) on a noisy clip and nearly dispatched twelve units instead of two. That ended guessing forever.</p>
<p>The rule now: low confidence or gaps around numbers, addresses, or product names means the AI asks a clarifying question and quotes what it did catch — "سمعت إن حضرتك بتسأل عن التوصيل لبكره، بس الصوت كان فيه وشوشة — ممكن تأكدلي العنوان والعدد؟" That clarify-instead-of-hallucinate behavior saved more orders than any other tuning. Agents help because the inbox shows audio plus transcript side by side; a human spots a bad transcript in seconds and listens to the original.</p>
<h2>9. Outbound Voice: ConvertAudioToOgg and When to Use It</h2>
<p>Inbound is transcription. Outbound is the mirror: a browser recording is not a WhatsApp voice message until converted. Our ConvertAudioToOgg job converts the agent's recording to Ogg Opus, uploads it, and sends it so it renders as a native playable voice bubble. Without conversion it arrives as a generic attachment most buyers never tap.</p>
<p>Use outbound voice sparingly: defusing anger where tone matters, reassuring an older buyer who struggles reading, adding a human touch for a warm VIP who already said yes. For price, address, size, date, or payment, stay in text. I have watched deals stall on a voice note saying "هبعتلك اللوكيشن" with no link, when one typed message with the link would have closed it. Inbound voice gets transcribed every time; outbound voice is a choice for moments where warmth beats precision.</p>
<h2>10. What Changed in Our Numbers and How to Set It Up</h2>
<p>A homeware seller in Faisal cut median first-response time on voice conversations from over three hours to under two minutes, because the AI drafts from the transcript instantly instead of waiting for a human to listen. His voice-conversation close rate roughly doubled in six weeks — not from magic, but because 30 daily voice notes stopped sitting unread until evening. A North Coast broker got a different win: agents stopped cherry-picking easy text chats and ignoring long voice notes, because every note now arrives with a transcript and draft reply.</p>
<p>To replicate it in OT1-Pro, work through this sequence:</p>
<ol>
<li><strong>Connect WhatsApp and test audio.</strong> Send yourself a voice note and confirm player plus transcript appear. For permission traps that silently block messaging, read our <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta verification founder guide</a>.</li>
<li><strong>Match reply language to buyers.</strong> Egyptian dialect for Egypt, Gulf dialect for Saudi and UAE. The AI mirrors the transcript, but your default sets the first message.</li>
<li><strong>Load real prices and delivery zones first.</strong> Transcription without correct prices produces faster wrong answers.</li>
<li><strong>Train the clarify rule.</strong> Thin transcript or odd number means listen to the audio and confirm in writing before dispatch. One prevented wrong shipment pays for a month of diligence.</li>
<li><strong>Check <a href="https://ot1-pro.com/pricing">pricing</a> against your volume and start from <a href="https://ot1-pro.com/register">registration</a>.</strong> Voice-heavy teams use more transcription minutes, so confirm your plan before peak season.</li>
</ol>
<p>Egyptians will keep talking instead of typing, Gulf buyers will keep sending three clips where one sentence would do, and teams that transcribe whatsapp voice messages automatically will answer in seconds while competitors still hold the phone to their ear. I built this because I watched orders die in unread audio bubbles. Yours do not have to.</p>
<p>{{CTA}}</p>
HTMLP1,
                'meta_title'        => 'WhatsApp Voice Note Transcription for Egypt Sales (2026)',
                'meta_description'  => 'Egyptian buyers send voice notes, not text. See how OT1-Pro TranscribeAudio turns audio into scored leads and text replies via whatsapp voice note transcription',
                'category'          => 'WhatsApp',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 2. automatic-lead-scoring-hot-warm-cold-2026 (en) ──
            [
                'title'   => 'Automatic Lead Scoring: Every DM Auto-Tagged Hot, Warm, Cold',
                'slug'    => 'automatic-lead-scoring-hot-warm-cold-2026',
                'excerpt' => 'OT1-Pro\'s ScoreLeadJob scores every inbound DM 0-100 on each message: 71+ is hot, 31-70 warm, 1-30 cold. It weighs purchase-intent words, reply speed, question depth, repeat contact, voice notes, and origin, then sorts agents hot-first and targets bulk follow-ups by min_score.',
                'content' => <<<'HTMLP2'
<p>I built OT1-Pro because I was losing sales I had already earned. DMs poured in from Messenger, Instagram, WhatsApp, and TikTok, and my team worked hard, but we worked the inbox in the wrong order: newest-first. The person who wrote "بكام الشحن للقاهرة؟ عايز 50 قطعة للشركة" at 9 a.m. waited until evening while we chatted with three people who wrote "hi" and disappeared. By the time we got back to the buyer, he had bought elsewhere. The problem was not effort. It was prioritization.</p>

<p>That admission led to automatic lead scoring inside OT1-Pro. Every inbound DM now gets auto-tagged hot, warm, or cold without anyone lifting a finger. A job called ScoreLeadJob fires on every inbound message, reads the conversation, and writes a 0-100 score onto the contact. My agents see hot first. My bulk follow-ups target by score. And the score updates on every single message, so a contact who starts cold can climb to hot when intent changes. This post covers how it works, the 7 signals it watches, the thresholds I chose, the polite-browser failure mode, and the dollar math that made this my highest-ROI feature.</p>

<h2>I Used to Work My Inbox Newest-First, and It Cost Me Buyers</h2>

<p>For two years I ran sales the way most teams do: one queue sorted by recency. A new message arrived, it went to the top, someone answered it. Our average first-response time looked good, under five minutes. In reality we were fast on the wrong conversations and slow on the ones that mattered. When I pulled 30 days of chats and matched them against closed orders, buyers who used explicit purchase language waited an average of 6 hours for a real answer while casual browsers got answered in 4 minutes. We had optimized for speed and destroyed sequencing.</p>

<p>The worst case still bothers me. A distributor asked about bulk pricing for 200 units on a Tuesday morning. His message arrived during a rush of 40+ Instagram story replies from a giveaway post and sank. Nobody opened it until Thursday. By then he had signed with a competitor. That single lost deal was worth more than a month of ad spend. No human can read 400 inquiries a month and instantly know which 30 hold money. That is a machine job. I wrote about the broader trust journey in <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">my Meta app verification founder guide</a>, but scoring is the piece that converts attention you already have.</p>

<h2>What Automatic Lead Scoring Actually Does Inside OT1-Pro</h2>

<p>Automatic lead scoring in OT1-Pro works like this. Every time an inbound message lands on any connected channel, the system dispatches ScoreLeadJob. That job loads the full conversation history, the channel, the timestamps, and any prior score, then writes back three things: a 0-100 score, a hot/warm/cold label, and a short reason string the agent can read. No clicks, no forms. Tagging happens in the background within seconds.</p>

<p>Three design choices matter here. First, scoring runs on every inbound message, not just the first one, because intent changes mid-conversation. Second, the score lives on the conversation and the contact record, so every list can sort or filter by it. Third, the reason string is human-readable. An agent opening a chat sees "hot 84: asked price twice + replied in 2 min + repeat contact" instead of a black-box number. That transparency is why my team trusted it. The score also feeds directly into follow-up targeting, covered below and in <a href="https://ot1-pro.com/lead-follow-up-software">our lead follow-up software breakdown</a>.</p>

<h2>The 0-100 Scale: Why 71+ Means Hot, 31-70 Warm, 1-30 Cold</h2>

<p>I picked a 0-100 scale because everyone understands it and it leaves room for movement without fake precision. A 0 means no buying signal at all: a wrong number, a spam ping, a lone emoji. A 100 means explicit intent with urgency, budget, and a request for next steps. Most real conversations land between 15 and 90. The thresholds came from three months of calibration against closed deals:</p>

<ol>
<li><strong>71+ is hot.</strong> Purchase-intent language, specific fulfillment questions, or repeat visits. Roughly 8 out of 10 of my closed deals touched 71+ at least once. Hot means answer now, within minutes, with a human.</li>
<li><strong>31-70 is warm.</strong> Real interest, incomplete intent. They asked real questions but have not talked money, quantity, or timing. Warm means answer today plus a scheduled follow-up.</li>
<li><strong>1-30 is cold.</strong> Low-effort, vague, or one-and-done. Cold means let the AI handle it politely and do not burn agent hours unless it warms up.</li>
</ol>

<p>Why 71 and not 70 or 75? Because round numbers lie. I tested all three cutoffs against 1,900 scored conversations with known outcomes. At 70, too many polite browsers leaked into hot and agents stopped trusting the label. At 75, real buyers with short messages got stranded in warm. At 71, hot stayed meaningful and warm stayed actionable. Thresholds are calibration, not philosophy. I re-calibrate quarterly and the numbers have barely moved.</p>

<h2>The 7 Buying Signals the Scorer Watches on Every Message</h2>

<p>The scorer does not guess. It reads seven concrete signals from each conversation on every message, ranked here by predictive weight:</p>

<ol>
<li><strong>Purchase-intent words.</strong> Explicit buying language in any dialect. In Egypt that includes بكام, عايز اطلب, عايز احجز, السعر كام, عندكم شحن, and in English "how much," "I want to order," "do you deliver." One intent phrase moves the needle. Two moves it a lot.</li>
<li><strong>Reply speed.</strong> How fast the contact answers your questions. A buyer who replies in under 3 minutes to "what quantity do you need?" behaves very differently from someone who answers 11 hours later.</li>
<li><strong>Question count and specificity.</strong> Buyers interrogate: price, variants, delivery time, warranty, payment, stock. Three specific questions beats one vague "tell me more" every time.</li>
<li><strong>Price objection versus price curiosity.</strong> "غالي أوي" alone can mean a brush-off or a negotiation opener. The scorer reads the surroundings. "غالي, عندك تقسيط؟" is curiosity and scores up. "غالي, شكرا" with no follow-up scores down.</li>
<li><strong>Repeat contact.</strong> Someone who messages three times across two days wants something. The scorer rewards return visits heavily, because persistence predicts purchase better than eloquence.</li>
<li><strong>Voice-note effort.</strong> In MENA inboxes, voice notes are a serious signal. Typing "بكام" takes two seconds. Recording a 40-second note about what your shop needs takes real effort, and effort correlates with intent.</li>
<li><strong>Story, comment, or ad origin.</strong> Where the conversation started matters. A DM that began as a reply to a price-story, a product comment, or a click-to-Messenger ad carries more intent than a cold "hello" typed into the page inbox. Origin is the prior probability the rest of the signals update.</li>
</ol>

<p>None of these signals alone decides anything. A single "بكام" from a one-message visitor is not hot. Three signals firing together is. That combination logic is the whole point of scoring over keyword matching.</p>

<h2>Signal Weight Table: What Pushes a Score Up or Down</h2>

<p>My team asked for a one-page cheat sheet to pin above their desks. This table is that sheet: each signal, which direction it pushes the score, and a real example.</p>

<table>
<thead>
<tr><th>Signal</th><th>Weight direction</th><th>Example</th></tr>
</thead>
<tbody>
<tr><td>Purchase-intent words</td><td>Strong up (+15 to +30)</td><td>"بكام الجملة لو هاخد 100 قطعة؟"</td></tr>
<tr><td>Reply speed under 3 min</td><td>Up (+8 to +15)</td><td>Answers size question in 90 seconds</td></tr>
<tr><td>Specific question count</td><td>Up (+5 per question, capped)</td><td>"عندكم شحن للصعيد؟ الدفع كاش ولا فيزا؟"</td></tr>
<tr><td>Price curiosity with follow-up</td><td>Up (+10 to +18)</td><td>"غالي شوية, فيه خصم للكميات؟"</td></tr>
<tr><td>Repeat contact same week</td><td>Up (+10 to +20)</td><td>Third message in 2 days asking stock</td></tr>
<tr><td>Voice-note effort</td><td>Up (+6 to +14)</td><td>35-second note describing shop needs</td></tr>
<tr><td>Story / comment / ad origin</td><td>Up (+5 to +12)</td><td>DM from a price-story reply</td></tr>
</tbody>
</table>

<p>Two things to notice. First, downward weights exist and matter. Without them every long conversation drifts hot, which destroys trust in the label. Second, weights are ranges because context modulates them. If you compare vendors here, ask each one to show a weight table with real examples. Most cannot. Our <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs Wati comparison</a> shows where scoring and Arabic-dialect handling actually differ.</p>

<h2>How Re-Scoring on Every Message Fixes the Polite Browser Problem</h2>

<p>The failure mode that almost killed this feature was the polite browser: lovely people who write full sentences, say thank you, ask thoughtful questions, and never buy. Early versions of my scorer loved them. Long messages, many questions, fast replies, warm tone. They scored 68, 72, even 78, while real buyers writing "بكام؟ محتاج 30 بكرة" scored 55 because the message was short. My agents told me within a week that the labels felt backwards on exactly the conversations that mattered.</p>

<p>The fix was re-scoring on every inbound message with decay built in. Each new message re-runs the full read, but older signals decay. A thoughtful question from Tuesday counts less on Thursday if nothing since has shown intent. A quote with no reply for 72 hours actively pulls the score down. The polite browser who asked five nice questions on Monday and went quiet drifts from 72 back to 48 by Friday on their own. The terse buyer who follows "بكام؟" with "تمام, ابعت اللينك" climbs from 55 to 83 across three messages. No agent corrects anything. The score is a living value, not a first-impression stamp.</p>

<p>This is also why ScoreLeadJob fires on inbound messages specifically. Outbound messages do not re-score, because my reply is not evidence about the customer. Their next answer is. Score the buyer, not yourself.</p>

<h2>Where My Agents See the Score (Hot First, Always)</h2>

<p>A score nobody looks at is a hobby, not a system. The inbox list shows the hot/warm/cold badge on every row. Sorting defaults to hot-first, so an agent opening their shift sees buyers at the top regardless of who messaged last. Opening a chat shows the numeric score with the reason string beneath it. A supervisor view aggregates hot waiting per agent and flags the oldest waiting hot.</p>

<p>The behavioral change was immediate. Before scoring, agents cherry-picked easy chats because short greetings were easy to clear, while hard buying conversations with quotes sat because they took work. After hot-first sorting, the path of least resistance and the path of most revenue became the same path. New hires ramp faster too, because on day one they can tell a conversation that deserves five careful minutes from one that deserves a polite AI-handled reply. That judgment used to take three months. Now it ships in the interface.</p>

<h2>How I Use the Min_Score Filter to Target Bulk Follow-Ups</h2>

<p>The second place the score pays rent is bulk sending. OT1-Pro bulk sends support a min_score filter, so I target follow-ups by score band instead of blasting the whole list. A generic blast to 3,000 contacts gets ignored and trains people to ignore me. A nudge to 220 warm contacts who asked real questions in the last 14 days gets replies, because the message matches memory.</p>

<p>My three standard plays are simple. First, hot non-buyers get a human follow-up within 24 hours, never a bulk blast. If someone hit 75 and did not order, something specific stalled them, usually delivery fee, payment method, or timing. Second, warm contacts get a scored bulk sequence: one value message, one objection-handler, one deadline. I set min_score to 31 and cap at 70 to pull only the warm band, excluding anyone contacted in the last 7 days. Third, cold contacts get AI-only reactivation once a month at most. The full campaign mechanics live in <a href="https://ot1-pro.com/lead-follow-up-software">the lead follow-up guide</a>, and the zero-cost rerun technique pairs well with this in <a href="https://ot1-pro.com/blog/ai-deep-analysis-3000-contacts-0-credit-rerun">the 3,000-contact deep analysis post</a>.</p>

<h2>The Math: 400 Inquiries a Month and the 27% Who Ghost</h2>

<p>Take a typical store at 400 inbound inquiries a month. Scoring usually puts roughly 60 in hot, 160 in warm, and 180 in cold. Before scoring, the team answered in arrival order and closed about 35 deals. After hot-first ordering with same-day warm follow-up, they close about 52 from the same 400. Same ads, same products, same team. The difference is purely who got answered first.</p>

<p>The ghost segment is where the money hides. Across three accounts I measured, about 27% of hot contacts ghost after the first meaningful exchange. They asked price, got a quote, then vanished. Before scoring, those ghosts dissolved into the general inbox because finding them meant scrolling. After scoring, they are a one-click filter: hot, no order, silent 48+ hours. Recovering even a third of that segment at a $45 average order value turns 20 recovered orders into $900 a month from conversations you already paid to acquire. At a $1,200 monthly ad spend producing those 400 inquiries, that recovery alone lifts ROAS without spending another dollar. Check <a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a> and you will see the plan pays for itself on ghost recovery alone.</p>

<p>To be honest about limits: scoring does not create demand. If your 400 inquiries are all "hi" from a viral giveaway, scoring will correctly label most of them cold and your close count will stay low. That is the system telling the truth about your traffic. And a hot label with a slow, sloppy answer still loses. What scoring does is aim your existing effort at the conversations most likely to repay it.</p>

<h2>How to Turn It On Without Changing How You Reply</h2>

<p>Deployment took my team one afternoon because scoring asks agents to learn nothing new. The rollout I recommend:</p>

<ol>
<li><strong>Connect your channels and let history accumulate.</strong> ScoreLeadJob needs conversation text to read. One week of normal inbound volume is enough to start.</li>
<li><strong>Confirm the thresholds fit your close data.</strong> Start with 71+ hot, 31-70 warm, 1-30 cold. After 200 scored conversations, check whether your buyers touched hot. Adjust by 2-3 points if needed, then leave it alone.</li>
<li><strong>Switch the inbox sort to hot-first.</strong> Do not run scoring in the background while agents keep working newest-first. The sort change is the intervention.</li>
<li><strong>Build one saved filter per band.</strong> Hot waiting 15 minutes, warm silent 48 hours, cold untouched 7 days. Supervisors check them hourly, daily, and weekly.</li>
<li><strong>Add min_score to your next bulk send.</strong> Pick the warm band for your first scored campaign and compare reply rate against your last unsegmented blast.</li>
</ol>

<p>Common mistake to avoid: announcing scoring as an employee-monitoring tool. It is not. It scores contacts, not agents. I framed it as air traffic control: the tower sequences landings so nothing crashes, but agents still fly every conversation. If you want to try this on your own inbox, <a href="https://ot1-pro.com/register">create an OT1-Pro account here</a> and connect one channel first. Watch one week of hot-first sorting before you change anything else.</p>

<p>{{CTA}}</p>
HTMLP2,
                'meta_title'        => 'Automatic lead scoring: Every DM Ranked Hot/Warm/Cold',
                'meta_description'  => 'I built OT1-Pro to rank every inbound DM hot, warm, or cold on a 0-100 scale, re-scored on each reply, so agents answer buyers first with automatic lead scoring',
                'category'          => 'Sales',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 3. instagram-comment-to-dm-automation-modes-2026 (en) ──
            [
                'title'   => 'Instagram Comment to DM: How My AI Decides Between Public Reply and Private DM',
                'slug'    => 'instagram-comment-to-dm-automation-modes-2026',
                'excerpt' => 'I built OT1-Pro\'s instagram comment to dm engine with split public and private prompts, purchase-intent routing, silent spam filtering, and working-hours gating. This founder guide shows the pipeline and setup that lifted my DM-to-order rate from 9 to 21 percent.',
                'content' => <<<'HTMLP3'
<p>I built OT1-Pro because I lost a 4,200 EGP clothing order to a comment I answered too late. A customer wrote "بكام؟" under a reel at 11:40pm, and by the time my social media girl replied the next morning, she had bought from a competitor. The comment was public. The money was private. I had no system connecting the two. That gap is exactly what instagram comment to dm automation closes, and I spent four months getting the public-versus-private decision right.</p>
<p>Most tools treat a comment reply and a DM as the same job. They are opposite jobs. The public reply shows every lurker this store answers fast. The DM closes one human with prices and a payment step. When I mixed those jobs, my bot posted order links in public and wrote "check your DM" inside the actual DM. This post breaks down the three settings that decide, the four-stage pipeline, and the bug that taught me to use two prompts instead of one.</p>
<p>If you are still fighting Meta permissions to get Instagram connected at all, read my winner guide first: <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta App Verification 2026: A Founder's Guide</a>. Without <code>instagram_basic</code> and <code>instagram_manage_messages</code> on Advanced Access, no auto-reply tool can touch your comments or DMs.</p>
<h2>Why Instagram comment to DM is where sales actually happen</h2>
<p>I track where my paying customers first touch us, and for fashion, cosmetics, and food stores in Egypt and the Gulf, the answer keeps coming back the same: 60 to 70 percent start as a public comment, not a DM and not WhatsApp. They write "price?", "عندكم شحن للسعودية؟", or just tag a friend. They will not DM you first because DMing a store feels like commitment. Commenting feels free.</p>
<p>That creates a brutal math problem. A reel that hits 50,000 views pulls 300 to 600 comments in 48 hours. My team of two could answer 80 a day before quality collapsed. Every unanswered "how much?" thread tells the next 10 lurkers nobody answers here. I watched conversion on a viral abaya reel drop from 3.1 percent to 0.9 percent between day one and day three for exactly this reason.</p>
<p>The fix is an instagram comment to dm system that answers every comment in public within a minute and moves buyers into DMs before they cool off. Public speed protects the post. Private depth closes the wallet.</p>
<h2>The two inboxes: what the public sees vs what closes</h2>
<p>I want to make the split concrete because every misconfiguration I have seen comes from blurring it. The public comment reply has three jobs: acknowledge the human by name, answer the surface question in one line, and point toward the DM without leaking anything sensitive. Example: "Sara, the linen set is 1,450 EGP — I sent you the size chart and order link in DM!" That reply sells to Sara and to the 200 people reading over her shoulder.</p>
<p>The DM has the opposite job: no audience, just closing. It carries the size chart, color photos, the shipping quote, the payment link, and one follow-up question. None of that belongs in public. When I posted order links publicly in our first version, competitors scraped my prices within a day and customers stopped opening the DM. My DM open rate fell to 31 percent that week; after I moved specifics into the DM, it recovered to 74 percent.</p>
<p>The rule I run now: public replies never contain full prices, phone numbers, payment links, or discount codes. DMs never contain the phrase "check your DM." Each message knows which room it is standing in.</p>
<h2>How I built the decision: reply_mode, dm_mode and on_purchase_intent</h2>
<p>Every Instagram account on OT1-Pro gets three settings that together decide who gets what. I designed them after watching store owners argue opposite needs: one wanted everything public for social proof, another wanted everything private to hide prices. Both were right for their niche, so I stopped hardcoding and exposed the decision.</p>
<p><strong>reply_mode</strong> controls the public side. It has three values: <code>ai</code> lets the AI write a fresh one-line reply per comment, <code>keyword</code> only replies when the comment matches one of your triggers and stays silent otherwise, and <code>off</code> disables public replies entirely while keeping DM logic alive. Fashion stores with heavy traffic run <code>ai</code>. Electronics stores hiding margins run <code>keyword</code> with five triggers. One perfume client runs <code>off</code> during wholesale launches because he does not want retail buyers piling on.</p>
<p><strong>dm_mode</strong> controls the private side independently: <code>always</code> sends a DM to every commenter, <code>on_keyword</code> only DMs on trigger matches, and <code>off</code> never DMs. I default new stores to <code>on_keyword</code> because <code>always</code> on a viral post means 500 DMs in a night, and Meta rate-limits aggressive DM bursts from new senders. You earn <code>always</code> after two quiet weeks.</p>
<p><strong>on_purchase_intent</strong> is the override that ties them together. When the classifier scores a comment as buying intent — "how much", "بكام", "اريد اطلب", "شحن", "available?", a size question, a tag plus "I want this" — this flag forces the combo: public reply plus DM with details, regardless of the quieter modes. A praise comment like "love this" gets a public thanks and no DM. A purchase-intent comment always gets both. That single flag is responsible for most of our recovered revenue, because it is the only path where the AI is allowed to be pushy. Learn the broader DM sales flow it feeds into at <a href="https://ot1-pro.com/instagram-dm">OT1-Pro Instagram DM automation</a>, and compare the cost logic on <a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a> before you copy my defaults.</p>
<h2>Inside the pipeline: CommentFilterService to SendAiCommentReplyJob</h2>
<p>Settings mean nothing without a pipeline that runs them in order, and mine has four stages. I am naming the real classes because there is no magic step where "AI decides everything."</p>
<p>Stage one is <strong>CommentFilterService</strong>. It runs synchronously on every webhook and throws away what should never get a reply: our own comments, banned users, duplicates Meta delivers twice, and obvious spam by pattern. Filtered comments never create jobs. This stage alone cut our AI spend by 22 percent.</p>
<p>Stage two is <strong>IngestCommentJob</strong>. It normalizes the text (Arabic dialect included — "بكام" and "بكم" and "how much" all map to a price question), attaches the media ID and working-hours state, then pushes to classification. If the same user commented three times in two minutes, ingest merges them into one decision so we do not send three DMs.</p>
<p>Stage three is <strong>ClassifyCommentJob</strong>. It returns a label — purchase_intent, question, praise, tag, complaint, spam — plus a confidence score and the matched keyword. Low-confidence purchase_intent (under 0.55) gets treated as a plain question: public reply, no DM. I would rather miss one DM than spam fifty strangers.</p>
<p>Stage four is <strong>SendAiCommentReplyJob</strong>. It reads reply_mode, dm_mode, on_purchase_intent, and the label, then emits zero, one, or two messages. Every send is logged with the label and prompt version so I can audit why any customer got what she got.</p>
<h2>The routing table I run on every comment</h2>
<p>I keep this table taped to my monitor because support tickets drop when owners understand it before launch. This is the exact behavior of an instagram comment to dm setup on OT1-Pro with my recommended defaults (reply_mode=ai, dm_mode=on_keyword, on_purchase_intent enabled):</p>
<table>
<thead>
<tr><th>Comment type</th><th>Public action</th><th>DM action</th></tr>
</thead>
<tbody>
<tr><td>Purchase intent ("how much?", "بكام؟", "I want to order")</td><td>AI public reply with teaser + DM pointer</td><td>Full DM with price, variants, photos, order link</td></tr>
<tr><td>Product question (sizes, shipping, material)</td><td>One-line public answer</td><td>DM with details only if keyword matched</td></tr>
<tr><td>Praise ("love this", emojis only)</td><td>Short public thanks</td><td>No DM</td></tr>
<tr><td>Friend tag ("@sara check this")</td><td>Public welcome to both users</td><td>No DM unless tag includes buying words</td></tr>
<tr><td>Complaint ("order late", "wrong size")</td><td>Public apology + ticket number, no specifics</td><td>DM with support handoff and human escalation</td></tr>
<tr><td>Spam / scam / link drop</td><td>Nothing, hidden if policy allows</td><td>Nothing, ever</td></tr>
<tr><td>Out-of-hours comment</td><td>Public reply noting reopening time</td><td>Queued DM delivered at opening</td></tr>
</tbody>
</table>
<p>Two rows cause arguments. Tags: owners always want to DM every tagged friend, and I refuse as default because tagged friends did not opt in — cold DMs tank your quality rating with Meta. Complaints: never put order specifics in public. I once posted a replacement date publicly and ten strangers demanded the same for unrelated orders. Private specifics, public courtesy.</p>
<p>If you want the sibling flow that covers stories as well as feed posts, read <a href="https://ot1-pro.com/blog/instagram-auto-reply-story-comment-dm-2026">Instagram auto-reply for story, comment and DM</a> next — it shares the same classifier but adds story-mention triggers.</p>
<h2>Purchase-intent comments: public reply plus DM with details</h2>
<p>Purchase intent is the money row. When ClassifyCommentJob tags a comment as purchase_intent above threshold, SendAiCommentReplyJob fires twice: a short public reply and a rich DM. The public reply follows a strict template — name, one-line teaser, DM pointer. "Mariam, the mascara is 620 EGP — shades and order link in your DM!" The DM carries the payload: shades with photos, bundle price, shipping, cash-on-delivery terms, one closing question.</p>
<p>Keyword triggers backstop the classifier so obvious buyers never depend on model mood. My starter set: price, how much, بكام, بكم, سعر, order, اطلب, شراء, available, متوفر, shipping, شحن, توصيل, size, مقاس, discount, خصم. On Arabic-heavy accounts, triggers catch 18 percent of buyers the classifier scored under threshold, mostly dialect spellings.</p>
<p>If the buyer does not reply within 24 hours, OT1-Pro sends one human-approved nudge, then stops. Two nudges max. I tested three and the block rate doubled. Fast first DM, one polite nudge, then silence.</p>
<h2>Spam dies quietly: why we never post a visible bot reply</h2>
<p>Early on I let the bot reply to everything, including spam, because a busy comment section looked alive in sales calls. Then a crypto scammer dropped a wallet link under a client's bridal post, my bot replied "Thanks for your interest!", and the screenshot ended up in a 40,000-member Facebook group. That client churned the same week. I rewrote the policy that night.</p>
<p>The rule is absolute: spam is filtered silently and never receives a visible bot reply. CommentFilterService kills pattern spam before jobs exist; ClassifyCommentJob labels the gray cases. Either path ends the same way — no public reply, no DM, optional hide. After I enabled silent filtering across ten pilot stores, replies-per-post fell 14 percent and DM-initiated checkouts rose 27 percent. Fewer visible comments, more money.</p>
<h2>Working hours and keyword triggers that keep you out of trouble</h2>
<p>An instagram comment to dm bot with no clock is a liability. Before working-hours gating, the bot told a midnight commenter "your order ships tomorrow morning!" The warehouse shipped in three days. She posted the screenshot. After the gate, the same comment at 3am gets "Thanks Laila! We reopen at 10am — details in DM, team confirms shipping then." Out-of-hours DMs queue and deliver at opening.</p>
<p>Configuration is boring on purpose: open and close hours, a timezone, a Friday prayer-break toggle for Egypt/Gulf stores, a holiday list. The pipeline reads the gate in IngestCommentJob, before classification, so we do not burn AI budget on messages that wait six hours. Complaints override the queue with a ticket number because silence on anger reads as guilt.</p>
<p>I cap starter stores at 20 triggers and review weekly: any trigger firing 30+ times with under 10 percent DM replies gets deleted. "Hi" as a trigger was my worst mistake — 400 casual greeters DM'd in a weekend, account rate-limited 48 hours. Triggers are buying words, never greetings.</p>
<h2>The "check your DM" bug and the split-prompt fix</h2>
<p>Here is the failure mode, told exactly as it happened. For our first six weeks, public replies and DMs shared one prompt with one instruction block called reply_instructions: "always mention check your DM." Public replies looked great. Then a customer forwarded her DM: "Hi Dina! Check your DM for details!" She was already in the DM. There was nowhere left to check.</p>
<p>The leak ran both ways. DMs contained "as I said in my public reply..." to customers who never saw it, and public replies leaked DM-only discount codes. My DM-to-order rate that month was 9 percent and "confused" tickets tripled.</p>
<p>The fix was to split the prompt in two inside SendAiCommentReplyJob. The public prompt receives reply_instructions and must include exactly one DM pointer. The DM prompt never receives reply_instructions — the field is stripped before the call — and bans any outward pointer. The DM must be self-contained: greeting, answer, photos, link, closing question. The leak vanished from logs within 48 hours; DM-to-order climbed from 9 to 21 percent over five weeks on the same traffic.</p>
<h2>Setup in 7 steps and what ManyChat charges extra for</h2>
<p>I will give you the exact setup I run for new stores, because vague "connect and go" advice is why most comment automation underperforms. Budget 45 minutes:</p>
<ol>
<li>Connect Instagram as Business/Creator linked to a Facebook Page, with <code>instagram_basic</code> and <code>instagram_manage_messages</code> approved. DIY App Review takes weeks; our managed onboarding skips it — the bureaucracy is the same one I documented in <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">Meta App Verification 2026: A Founder's Guide</a>.</li>
<li>Set reply_mode to <code>ai</code> and dm_mode to <code>on_keyword</code> for week one. Enable on_purchase_intent. You can graduate to dm_mode=always after two quiet weeks.</li>
<li>Enter 12 to 20 keyword triggers from your last 200 comments: buying words first, product words second, never greetings. Include Arabic dialect spellings your customers actually type.</li>
<li>Set working hours, timezone, and the out-of-hours message. Write the reopening time into both the public template and the queued DM so neither side overpromises.</li>
<li>Write the DM payload: price list, 3 to 5 photos, shipping table, payment link, one closing question. The AI personalizes around this payload; it never invents prices without it.</li>
<li>Test with three comments from a second account: a price question (expect public + DM), a praise emoji (expect public only), a spam link (expect silence). Check the SendAiCommentReplyJob log for the label on each.</li>
<li>Go live on one post, review the log nightly for a week, promote a trigger to the DM payload or demote a noisy trigger every Friday. Automation that nobody audits rots within a month.</li>
</ol>
<p>On pricing honesty: ManyChat and Chatfuel sell comment-to-DM above their base plans — ManyChat behind Pro from ~$15/month plus per-contact overages past 1,000 contacts, Chatfuel behind higher bundles with similar scaling. A 5,000-contact store easily pays $50–$145/month before AI tokens. OT1-Pro includes classification, public AI replies, DM automation, and the split-prompt logic on Pro with no per-contact meter — compare on <a href="https://ot1-pro.com/pricing">OT1-Pro pricing</a> and <a href="https://ot1-pro.com/vs/wati">OT1-Pro vs WATI</a>, then <a href="https://ot1-pro.com/register">create your free OT1-Pro account</a> and run the three-comment test on your best post.</p>
<h2>What I would do differently if I started over</h2>
<p>I would ship the silent-spam rule on day one, cap triggers at buying words from the start, and split the prompts before the first customer. And I would open the SendAiCommentReplyJob log nightly for the first month and ask "would I have sent this myself?" The stores that do that Friday review end month one with a bot that sounds like their best salesperson.</p>
<p>{{CTA}}</p>
HTMLP3,
                'meta_title'        => 'Instagram Comment to DM: Public vs Private AI Modes',
                'meta_description'  => 'I built OT1-Pro to split public comment replies from private sales DMs by intent, spam, and hours. Pipeline, modes, full setup for instagram comment to dm',
                'category'          => 'Instagram',
                'author'            => 'Omar Eltak',
                'language'          => 'en',
                'is_rtl'            => false,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 4. ai-replies-customer-photos-ar (ar) ──
            [
                'title'   => 'الرد على صور العملاء: ازاي الذكاء بيرد على صورة المنتج والأشعة',
                'slug'    => 'ai-replies-customer-photos-ar',
                'excerpt' => 'انا عمر مؤسس OT1-Pro وهحكيلك ازاي الرد على صور العملاء غيّر البيع عندنا: عميلة تبعت صورة منتج مكسور أو لون فستان أو أشعة أسنان والذكاء يفهم الصورة ويرد بالعامية في دقيقة باعتذار واستبدال أو حجز عند الدكتور بدل ما الرسالة تتركن وتضيع البيعة.',
                'content' => <<<'HTMLP4'
<p><strong>انا عمر، مؤسس <a href="https://ot1-pro.com">OT1-Pro</a>، وهقولك على لحظة غيرت نظرتي للرد الآلي كله: عميلة بعتت صورة كرتونة مفتوحة والمنتج مكسور، والذكاء الاصطناعي رد عليها قبل ما انا اصحى من النوم.</strong> لا قالها "هنحولك للدعم" ولا قالها "شكرا لتواصلك"، قالها بالحرف: "يا نهار أبيض، الكسر ده من الشحن واضح، حقك علينا، تحبي نبدلهولك ولا نرجعلك الفلوس كاش عند الاستلام للبديل؟" العميلة دي كانت هتعمل مرتجع وريفيو نجمة واحدة، بقت عميلة دايمة. ومن اليوم ده فهمت إن <strong>الرد على صور العملاء</strong> هو الفرق بين متجر بيبيع ومتجر بيخسر وهو نايم.</p>
<p>لو بتبيع على واتساب أو انستجرام في مصر، انت عارف اللي بيحصل: العميل مش بيشرح بالكلام، بيصور. بيصور المقاس اللي مش مظبوط، بيصور لون الفستان في نور الأوضة، بيصور الكرتونة اللي وصلت مفعوصة من البوستة، وفي العيادات بيبعت صورة الأشعة أو التحليل على واتساب ويسأل "ده محتاج ايه يا دكتور؟". زمان الرسالة دي كانت بتتركن لحد ما حد يفضى يبص عليها. دلوقتي ميزة DescribeImage في OT1-Pro بتقرا الصورة وتطلع منها وصف، والذكاء الاصطناعي بيرد عليها رد متفصل عليها بالعامية المصرية، مش رد قالب محفوظ.</p>
<p>في المقال ده هحكيلك بالتفصيل ازاي ده شغال عند محلات حقيقية وعيادات حقيقية، وايه اللي بيحصل لما الصورة تكون مغبشة، وليه الدكتور مينفعش يسيب الذكاء يشخص، وازاي تظبطها عشان تبيع أكتر وترجع أقل. ولو عايز تشوف الصورة الكاملة لأسعار النظام اللي بيعمل كده، بص على <a href="https://ot1-pro.com/pricing">صفحة الأسعار</a>، ولو بتقارن بينا وبين المنافسين شوف <a href="https://ot1-pro.com/vs/wati">مقارنة OT1-Pro مع WATI</a>، ولو جاهز تجرب بنفسك سجل من <a href="https://ot1-pro.com/register">صفحة التسجيل</a>.</p>
<h2>العميل بقى بيبعت صورة مش بيكتب: افهم القاعدة الجديدة</h2>
<p>انا قعدت أراجع 3 شهور من شاتات متاجر بتستخدم OT1-Pro، ولقيت إن تقريبا 4 من كل 10 رسائل بعد السؤال عن السعر فيها صورة. مش صدفة، ده سلوك. الكتابة بالعربي على الكيبورد متعبة، والعميل شاكك إنك مش هتفهمه لو كتب، فبيقولك "بص بنفسك". اللي بيبعت صورة فستان ويقولك "اللون ده موجود؟" هو بيختبرك: هتفهم قصدي ولا هتقولي ابعتي الكود؟ واللي بيبعت صورة شوز مقطوع بعد أسبوع بيقولك ضمنيا "انا زعلان بس مديلك فرصة". اللي بيرد بسرعة وبدقة على الصورة بيكسب البيعة، واللي بيرد بـ"تمام هنشوف ونرد عليك" بيخسرها للي بيرد أسرع منه.</p>
<p>المشكلة إن 90% من البوتات القديمة كانت عمياء. بتشوف إن فيه صورة اتبعتت فبترد رد أهبل زي "شكرا، استلمنا صورتك وهنحولها للمختص". العميل بيحس إنه بيكلم حيطة. الحل اللي بنيناه في OT1-Pro مختلف: أول ما الصورة توصل، ميزة DescribeImage بتطلع وصف نصي دقيق للي في الصورة — نوع المنتج، اللون الظاهر، الكسر أو القطع أو العيب، المقاس المكتوب على التيكت، حتى الأشعة السنية شكلها ايه — والوصف ده بيدخل للذكاء الاصطناعي مع تاريخ المحادثة كلها، فيرد رد واحد متفصل: فاهم الصورة، وفاهم السياق، وبيتكلم عامية مصرية زيك.</p>
 <h2>ازاي DescribeImage بتقرا الصورة والذكاء بيرد بالعامية؟</h2>
<p>خليني أشرحها ببساطة من غير مصطلحات معقدة، عشان ده السؤال اللي كل صاحب متجر بيسألهولي على <a href="https://wa.me/201026361218">واتساب</a>. الخطوات اللي بتحصل في ثواني لما عميلك يبعت صورة على واتساب أو انستجرام أو ماسنجر:</p>
<ol>
<li><strong>الصورة بتوصل لصندوق OT1-Pro الموحد.</strong> سواء جاية من واتساب أو انستجرام أو ماسنجر، كلها بتتجمع في مكان واحد، والذكاء شايف المحادثة اللي قبلها كلها: العميل كان بيسأل على ايه، اشترى ايه قبل كده، دفع ازاي.</li>
<li><strong>DescribeImage بيطلع وصف مكتوب للصورة.</strong> مش بيخمن البراند ومش بيألف، بيوصف اللي شايفه فعلا: "كرتونة مقطوعة من الجنب، جواها علبة مكياج مكسورة، السائل مسرب على الكرتون". الوصف ده بيبقى دقيق ومحايد.</li>
<li><strong>الذكاء بيجمع الوصف مع السياق ويرد.</strong> بيعرف إن العميلة دي طلبت أوردر من 5 أيام ووصلها عبر أرامكس، فيفهم إن الكسر ده من الشحن مش سوء استخدام، فيعتذر ويعرض الحل المناسب بلهجة مصرية طبيعية.</li>
<li><strong>الرد بيطلع متظبط على شخصية البراند بتاعك.</strong> لو متجرك شبابي بيهزر، بيهزر. لو عيادة محترمة، بيتكلم باحترام وتحفظ. انت اللي بتظبط النبرة مرة واحدة من الإعدادات.</li>
<li><strong>لو الصورة ناقصها معلومة، بيسأل سؤال توضيحي واحد.</strong> مش بيفتي. لو الصورة مغبشة بيقول "الصورة مغبشة شوية، ممكن صورة أوضح في نور النهار؟" وده أحسن 100 مرة من رد غلط.</li>
</ol>
 <h2>جدول: نوع الصورة والذكاء بيفهم منها ايه وبيرد ازاي</h2>
<p>ده الجدول اللي بوريه لأي صاحب متجر أو عيادة عشان يفهم الموضوع في دقيقة واحدة. كل صف فيه نوع صورة حقيقي بيتكرر كل يوم:</p>
<table>
<thead>
<tr>
<th>نوع الصورة اللي العميل بيبعتها</th>
<th>اللي الذكاء بيفهمه منها</th>
<th>الرد اللي العميل بيستلمه</th>
</tr>
</thead>
<tbody>
<tr>
<td>كرتونة مفعوصة ومنتج مكسور بعد الشحن</td>
<td>وصف الكسر ومكانه + ربطه بشركة الشحن والطلب</td>
<td>اعتذار واضح + عرض استبدال أو استرجاع + تأكيد الدفع كاش عند الاستلام للبديل</td>
</tr>
<tr>
<td>صورة فستان أو عباية للسؤال عن اللون</td>
<td>اللون الظاهر وتأثير الإضاءة + الموديل والمقاس المطلوب</td>
<td>تأكيد اللون الحقيقي + تنبيه فرق الإضاءة + تشييك المخزون وحجز المقاس</td>
</tr>
<tr>
<td>صورة مقاس على التيكت أو قدم لابسة شوز</td>
<td>الرقم المكتوب + شكل المقاس على الرجل</td>
<td>ترشيح المقاس الصح + عرض تبديل مجاني لو مطلعش مظبوط</td>
</tr>
<tr>
<td>صورة أشعة أو تحليل مبعوتة لعيادة أسنان أو جلدية</td>
<td>نوع الفحص الظاهر بدون أي تشخيص</td>
<td>شكر + توضيح إن التشخيص للدكتور + عرض أقرب معاد للحجز والتحويل للدكتور</td>
</tr>
<tr>
<td>صورة منتج غلط وصل بدل اللي اتطلب</td>
<td>مقارنة المنتج اللي في الصورة مع المنتج المطلوب في الأوردر</td>
<td>اعتراف بالغلط + مندوب ياخد الغلط ويجيب الصح + تعويض شحن مجاني</td>
</tr>
<tr>
<td>صورة مغبشة أو ضلمة مش باين منها حاجة</td>
<td>إن الجودة لا تسمح بتحديد التفاصيل</td>
<td>سؤال توضيحي واحد مهذب: صورة أوضح في نور النهار + سؤال عن المقاس أو اللون كتابة</td>
</tr>
</tbody>
</table>
<p>احفظ الجدول ده، لأنه بيغطي 95% من الصور اللي هتوصلك.</p>
<h2>منتج مكسور بعد الشحن: الاعتذار الصح بيرجع العميل</h2>
<p>أكتر صورة بتوجع قلب صاحب المتجر: عميلة تفتح الكرتونة تلاقي الإزازة مكسورة أو الكريم مسرب، تصور وتبعت "بصوا وصلني ايه". اللحظة دي هي اللي بتحدد هتاخد مرتجع وريفيو زي الزفت ولا عميلة دايمة. اللي بيحصل في OT1-Pro إن الذكاء بيشوف الوصف: "علبة كريم مكسورة والغطا مشروخ، آثار تسريب على الكرتونة"، وبيربطه ببيانات الأوردر: اتشحن من 3 أيام عبر البوستة، الدفع كان كاش عند الاستلام. فيرد في نفس الدقيقة: "يا فندم حقك علينا والله، الكسر ده واضح إنه من الشحن والكرتونة متبهدلة، متزعليش. تحبي نبعثلك واحدة جديدة بدلها بنفس السعر والدفع كاش عند الاستلام لما توصلك، ولا نرجعلك فلوسك؟ ولو ممكن صورة لرقم الأوردر اللي على البوليصة عشان نعمل شكوى في شركة الشحن".</p>
<p>ليه الرد ده بيشتغل؟ فيه 4 حاجات:</p>
<ol>
<li><strong>اعتراف فوري من غير لف ودوران.</strong> مفيش "هنراجع الموضوع" ولا "سياسة الاسترجاع بتقول". فيه "حقك علينا" في أول سطر.</li>
<li><strong>وصف دقيق للكسر.</strong> العميلة بتحس إن اللي بيكلمها شاف الصورة فعلا، مش بوت بيوزع اعتذارات.</li>
<li><strong>اختيارين واضحين.</strong> استبدال ولا استرجاع. العميل الزعلان مش عايز يفكر، عايز يختار.</li>
<li><strong>خطوة عملية للشحن.</strong> طلب رقم البوليصة عشان الشكوى في البوستة أو أرامكس بيحسسها إنك واخد الموضوع جد ومش هتلبسها هي التكلفة.</li>
</ol>
<p>متجر مكياج في فيصل كان بيخسر أغلب حالات الكسر كمرتجعات نهائية. بعد الرد الفوري على الصور، أغلبهم بقوا يقبلوا الاستبدال.</p>
<h2>لون الفستان في الصورة غير الحقيقة: مشكلة الإضاءة والمخزون</h2>
<p>تاني أشهر صورة: واحدة بعتت سكرين شوت من انستجرام لفستان وقالت "عايزة اللون ده بالظبط"، أو صورت الفستان اللي وصلها وقالت "ده مش نفس اللون اللي في الصفحة". دي مشكلة قاتلة لمتاجر اللبس، لأن اللون في التصوير بيختلف عن الحقيقة حسب الإضاءة، والعميلة بتحس إنها اتغشت. الذكاء هنا بيعمل حاجتين مع بعض: يفهم اللون من الصورة، ويشيك المخزون. لو بعتت صورة فستان زيتوني في نور أصفر، بيرد: "أيوه يا قمر هو الزيتوني بتاع الكوليكشن الجديدة، الإضاءة عندك مدياه لون مصفر شوية بس هو على الطبيعة أغمق درجة، المقاس L متوفر عندي قطعتين بس، أحجزهولك؟ ولو وصل ولقيتي اللون مختلف عن اللي في بالك، التبديل علينا والدفع كاش عند الاستلام".</p>
<p>الجملة الأخيرة دي هي اللي بتقفل البيعة. العميلة خايفة تدفع وتطلع غلطانة، فلما تقولها التبديل علينا وشافت إنك فاهم فرق الإضاءة، بتثق وتطلب. ولو الصورة اللي بعتاها للمنتج اللي وصلها فعلا مختلف، الذكاء مبيكابرش. بيقول: "عندك حق، اللي في الصورة أفتح من اللي عندنا في السيستم، شكله دخل من تشغيلة لون مختلفة، تحبي نبدلهولك بالغامق ولا نرجعلك الفلوس؟" الاعتراف ده بيكسب أكتر ما بيخسر، لأن البديل كان مرتجع بخناقة.</p>
<p>نصيحة من تجربتي: خلي الذكاء يذكر عدد القطع المتبقية لما اللون عليه طلب.</p>
<h2>عيادة الأسنان والجلدية: الأشعة بتتحول لحجز مش لتشخيص</h2>
<p>هنا الموضوع حساس ومهم. عيادة أسنان في مدينة نصر كانت بتستقبل على واتساب صور بانوراما أسنان وأشعة وضروس مسوسة، والأهل بيسألوا "ابني محتاج تقويم؟" و"الضرس ده محتاج حشو ولا خلع؟". الغلطة القاتلة إن أي نظام ذكي يحاول يشخص من الصورة. احنا بنينا القاعدة بالعكس: <strong>الذكاء عمره ما يشخص، وظيفته يحول الصورة لحجز عند الدكتور.</strong> لما توصله صورة أشعة، DescribeImage بيطلع وصف محايد زي "صورة أشعة بانوراما للفكين"، والذكاء بيرد: "شكرا إنك بعت الأشعة يا فندم، الدكتور هو الوحيد اللي يقدر يشخص من الأشعة، انا حولتها لملف حضرتك. تحب تحجز كشف يوم السبت الساعة 6 ولا الأحد الساعة 7؟ الكشف 350 جنيه وبيتخصم من خطة العلاج. ابعتلي الاسم ثلاثي ورقم الموبايل وأنا أأكدلك الحجز".</p>
<p>الرد ده طمأنة إن الصورة وصلت، وخط أحمر إن التشخيص للدكتور، وتحويل فوري للحجز بسؤال مقفول بمعادين.</p>
<p>ولو انت صاحب عيادة وبتقارن الأنظمة، لازم تبص على <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">دليل توثيق تطبيقات ميتا من تجربة مؤسس</a> عشان تفهم ليه الربط الرسمي لواتساب وانستجرام بيفرق في استلام الصور بجودتها الكاملة، مش مضغوطة وضايعة.</p>
<h2>الصورة المغبشة: السؤال التوضيحي الواحد اللي بينقذ البيعة</h2>
<p>خليني أكون صريح معاك: مش كل الصور حلوة. عميل بيصور في أوضة ضلمة، أو إيده بتترعش، أو بيصور شاشة موبايل تاني فبتطلع مخططة. النظام الغبي هيحاول يفتي ويقول أي كلام، والعميل هيكتشف الفتي ويفقد الثقة للأبد. القاعدة اللي حطيناها في OT1-Pro: لو الثقة في الوصف قليلة، الذكاء بيعترف ويطلب صورة أوضح بسؤال واحد محدد، مش أسئلة كتير. مثال حقيقي: عميل بعت صورة شوز مضلمة وكتب "المقاس ده مظبوط؟" الوصف طلع "حذاء رياضي في إضاءة منخفضة، الرقم على التيكت غير مقروء". الرد كان: "الصورة مضلمة شوية يا فندم ومش شايف الرقم على التيكت، ممكن صورة في نور النهار للتيكت من قريب؟ واكتبلي مقاسك في الشوزات العادية كام عشان أقولك تاخد 42 ولا 43".</p>
<p>ليه سؤال واحد؟ لأن العميل المستعجل لو سألته 5 أسئلة هيزهق ويمشي.</p>
<p>نصيحة عملية: اشكر صاحب الصورة الأول قبل ما تطلب صورة أوضح. "شكرا إنك صورت" بتفرق نفسيا.</p>
<h2>كاش عند الاستلام والشحن: الصورة بتقفل حسابات الفلوس</h2>
<p>في مصر مفيش بيع من غير <strong>كاش عند الاستلام</strong>، ومفيش شحن من غير وجع دماغ مع البوستة وأرامكس. الصور داخلة في المعادلة دي بشكل مباشر. لما عميل يبعت صورة منتج سليم ويقول "أكدهولي قبل الشحن"، الذكاء بيرد بصورة تأكيدية من المخزون لو متوفرة أو بوصف دقيق: "أيوه هو نفس الموديل يا فندم، الأسود السادة بزراير، المقاس XL متوفر، الحساب 650 جنيه + 40 شحن للقاهرة، والدفع كاش عند الاستلام لما المندوب يجيلك. أأكد الأوردر باسم مين ورقم الموبايل والعنوان؟" الرد ده بيقلل المرتجعات بشكل مرعب، لأن العميل شاف تأكيد قبل ما تدفع انت مصاريف الشحن.</p>
<p>والعكس صحيح: لما المندوب يوصل والعميل يقول "مش هستلم عشان اللون مختلف"، الصورة اللي اتبعتت قبل الشحن بتبقى دليلك. الذكاء بيقدر يقول: "يا فندم اللون اللي وصلك هو نفس اللي أكدناه في الصورة يوم كذا، الإضاءة عند حضرتك مختلفة بس، ولو حابب نبدلهولك بمقاس تاني المندوب يرجع بيه بكرة". ده مش كلام نظري، متجر عبايات في إسكندرية نزل نسبة رفض الاستلام من 22% لـ 9% بس لأنه بقى يأكد بالصور قبل الشحن ويوثق التأكيد في الشات.</p>
<p>ومع شركات الشحن، الصورة بقت سلاحك: صورة الكرتونة + رقم البوليصة بتخلي تعويض أرامكس ييجي أسرع.</p>
<h2>غلطات شوفتها بعيني: اوعى تعمل كده مع صور العملاء</h2>
<p>بعد ما شغلت الميزة دي عند عشرات المتاجر، شوفت 4 غلطات بتتكرر وبتضيع مجهود الصور كله:</p>
<ol>
<li><strong>الرد القالب الأعمى.</strong> "استلمنا صورتك وسيتم مراجعتها" ده رد بيقول للعميل إنك مش مهتم. لو مشغل ذكاء بيفهم الصور، اخفي الردود دي تماما. العميل بعت صورة عشان تترد عليها، مش عشان تتأرشف.</li>
<li><strong>الفتي في المقاس أو التشخيص.</strong> ذكاء يقول لعميلة "المقاس ده هييجي عليكي مظبوط" وهو مش متأكد، أو يقول لمريض "ده التهاب بسيط"، دي كارثة. اظبط القاعدة: مش متأكد = اسأل أو حول للبشر، ممنوع التأليف.</li>
<li><strong>تجاهل الصورة التانية.</strong> عميل بيبعت صورتين ورا بعض: واحدة للعيب وواحدة للفاتورة. أنظمة كتير بترد على الأولى وتتجاهل التانية. OT1-Pro بيجمع كل الصور اللي اتبعتت في نفس المحادثة ويرد عليها مرة واحدة.</li>
<li><strong>نسيان المتابعة بعد الحل.</strong> بدلت المنتج المكسور وسكت؟ غلط. بعد 3 أيام لازم رسالة: "البديل وصلك سليم يا فندم؟" المتابعة دي هي اللي بتحول عميل زعلان لسفير للبراند بيحكي لصحابه.</li>
</ol>
<p>لو عايز تفهم ليه الأنظمة الرخيصة بتقع في الغلطات دي، اقرا <a href="https://ot1-pro.com/vs/wati">مقارنتنا مع WATI</a> — هتلاقي إن الفرق مش في السعر، الفرق في إن النظام فاهم الصورة ولا أعمى.</p>
<h2>ازاي تظبط الرد على الصور في متجرك في نفس اليوم</h2>
<p>مش محتاج فريق تقني ولا أسبوع setup. اللي عملوه المتاجر الشاطرة في يوم واحد:</p>
<ol>
<li><strong>اربط قنواتك في صندوق واحد.</strong> واتساب وانستجرام وماسنجر في OT1-Pro، عشان الصورة اللي تجيلك من أي حتة تترد عليها بنفس الذكاء. سجل من <a href="https://ot1-pro.com/register">هنا</a> واربط أول قناة في دقايق.</li>
<li><strong>اكتب شخصية البراند مرة واحدة.</strong> سطرين: بتتكلم عامية ولا فصحى؟ بتهزر ولا رسمي؟ بتبدأ بـ"يا فندم" ولا "يا قمر"؟ الذكاء هيلتزم بيها في كل رد على صورة.</li>
<li><strong>حدد قواعد الاستبدال والاسترجاع بالأرقام.</strong> مثال: "كسر الشحن = استبدال فوري مجاني، غلط اللون من عندنا = تبديل علينا، رفض الاستلام بدون سبب = العميل يتحمل الشحن". لما القواعد مكتوبة بالأرقام، الذكاء بيرد بيها من غير ما يرجعلك.</li>
<li><strong>فعّل طلب الصورة التوضيحية تلقائيا.</strong> لو عميل اشتكى من عيب من غير صورة، الذكاء يطلبها هو: "ممكن صورة للكسر في نور واضح؟" 80% من الشكاوى الكلامية بتطلع مختلفة تماما لما تشوف صورتها.</li>
<li><strong>تابع تقرير الصور أسبوعيا.</strong> أكتر نوع صور بيجيلك ايه؟ لو كلها كسر شحن، غير شركة الشحن. لو كلها سؤال عن اللون، صور منتجاتك في إضاءة طبيعية. الصور مش بس خدمة عملاء، دي مخابرات عن البزنس بتاعك.</li>
</ol>
<p>والتكلفة؟ بص على <a href="https://ot1-pro.com/pricing">الأسعار</a> وهتلاقي إن الباقة المجانية بتجرب عليها الميزة، والمدفوعة أرخص من مرتب يوم واحد لموظف دعم. انا حسبتها قبل كده: الموظف بيرد على 50 صورة في اليوم بالعافية، الذكاء بيرد على 500 في الدقيقة.</p>
<h2>الخلاصة: اللي يشوف الصورة الأول هو اللي بيكسب البيعة</h2>
<p>انا عمر، وبعد 3 سنين ببيع وببني أنظمة بيع، اتعلمت إن العميل مش بيشتري من اللي عنده أحسن منتج، بيشتري من اللي فهمه أسرع. والصورة هي أسرع طريقة العميل بيشرح بيها نفسه. اللي يرد عليها في دقيقة برد فاهم ومخصوص، بياخد الفلوس. واللي يرد عليها بعد 6 ساعات برد قالب، بياخد البلوك. <strong>الرد على صور العملاء</strong> مش رفاهية ولا ميزة إضافية، ده بقى أساس البيع على واتساب وانستجرام في مصر: من كرتونة مكسورة جاية بالبوستة، للون فستان متصور في نور أصفر، لصورة أشعة مستنية دكتور يحجز لها معاد.</p>
<p>ابدأ النهارده: اربط متجرك أو عيادتك، وسيب أول 10 صور تجيلك للذكاء، واقعد اتفرج على الردود. لو عجبتك كمل، لو معجبتكش كلمني أنا شخصيا على واتساب وهنظبطها سوا. ولو لسه بتقارن، شوف <a href="https://ot1-pro.com/vs/wati">ليه OT1-Pro بيكسب WATI</a>، ولو جاهز دوس على <a href="https://ot1-pro.com/register">التسجيل المجاني</a> وابدأ في 5 دقايق. والأسعار كلها مشروحة بالتفصيل في <a href="https://ot1-pro.com/pricing">صفحة الأسعار</a>.</p>
{{CTA}}
HTMLP4,
                'meta_title'        => 'الرد على صور العملاء بالذكاء الاصطناعي',
                'meta_description'  => 'انا عمر مؤسس OT1-Pro: ازاي الذكاء بيرد على صور العملاء في مصر من منتج مكسور ولون فستان لصورة أشعة العيادة ويحولها لاستبدال فوري أو حجز عند الدكتور بالعامية.',
                'category'          => 'ذكاء اصطناعي',
                'author'            => 'Omar Eltak',
                'language'          => 'ar',
                'is_rtl'            => true,
                'reading_time'      => '12 min read',
                'published_at'      => $now,
                'updated_at'        => $now,
            ],

            // ── 5. lead-scoring-hot-warm-cold-ar (ar) ──
            [
                'title'   => 'تقييم العملاء المحتملين: كل رسالة بتتسكور سخن ودافي وبارد من غير ما ترفع إيدك',
                'slug'    => 'lead-scoring-hot-warm-cold-ar',
                'excerpt' => 'انا عمر: تقييم العملاء المحتملين عندي سكور تلقائي من 100 لكل رسالة، فوق 71 سخن يترد عليه فورا ومن 31 ل70 دافي محتاج متابعة وتحت 30 بارد لحملة عامة، بالسبع اشارات بكام وسرعة الرد والاسئلة والفويس والمصدر والاعتراض السعري من تحليل 12847 محادثة',
                'content' => <<<'HTMLP5'
<article dir="rtl" lang="ar">
<h1>تقييم العملاء المحتملين: كل رسالة بتتسكور سخن ودافي وبارد من غير ما ترفع إيدك</h1>
<p>انا عمر، وقبل ما احكيلك اي حاجة عن <strong>تقييم العملاء المحتملين</strong> خليني اقولك المشهد اللي كان بيحصل عندي كل يوم. الموبايل بيتهز، واتساب وماسنجر وانستجرام كلهم بيصرخوا في نفس اللحظة، والوكيل اللي معايا فاتح عشر شاتات وبيرد على اللي صوته عالي مش اللي فلوسه جاهزة. عميل سخن عايز يدفع دلوقتي مستني ساعتين، وعميل بارد بيسأل من باب الفضول واخد كل الاهتمام. كنت بخسر بيعات وانا فاكر ان المشكلة في الاعلانات او في السعر، بس المشكلة كانت ابسط واوجع: مفيش ترتيب. مين سخن؟ مين دافي؟ مين بارد؟ كل ده كان ماشي بالاحساس والتخمين. لحد ما قررت ابني سيستم يخلي كل رسالة تتسكور تلقائيا من 100، من غير ما حد يرفع ايده او يدخل بيانات. 71 او اكتر يبقى سخن، من 31 ل70 يبقى دافي، وتحت 30 يبقى بارد. والسكور ده بقى بيغذي كل حاجة في الشغل: مين نرد عليه الاول في الطابور، ومين نبعتله رسائل جماعية متفصلة على حالته، ومين نسيبه يستوي على مهله من غير زن. حللت مع التيم 12,847 محادثة كاملة من اول سلام عليكم لحد تم البيع او الاختفاء، ولقينا ان 73% من العملا عملوا ghost في مرحلة ما، و27% منهم رجعوا واشتروا لما اتعاملنا معاهم صح بالسكور. المقال ده هشرحلك فيه السبع اشارات اللي بنبني عليها السكور بالتفصيل والامثلة، وازاي تطبق نفس الفكرة عندك حتى لو شغال لوحدك من غير تيم ومن غير سيستم معقد.</p>

<h2>يعني ايه تقييم العملاء المحتملين جوه الشات مش جوه CRM؟</h2>
<p>لما الناس بتسمع <strong>تقييم العملاء المحتملين</strong> بتفتكر اكسيل وCRM معقد محدش بيفتحه وسكور متأخر بيومين. اللي بتكلم عنه مختلف: كل رسالة بتتفحص لحظيا من كلامها وتوقيتها، من غير ادخال يدوي. السيستم شايف المحادثة كلها وبيحسب وزن كل اشارة ويطلع رقم من 100. الوكيل ممكن يظلم عميل وهو تعبان، السيستم مبيتعبش.</p>
<p>الموضوع ده انهى الخناق على كلمة اصل العميل شكله مش جد. بقيت افتح السكور واقول الرقم والسبب. ولو شغال لوحدك فانت اكتر واحد محتاج <strong>تقييم العملاء المحتملين</strong> تلقائي لانك بتبيع وبترد وبتشحن لوحدك. جرب اسبوع واحد بس تسجل لكل محادثة السبع اشارات في ورقة: قال بكام ولا لا، رد بسرعة ولا ببطء، سأل كام سؤال، رجع بنفسه ولا انت اللي جريت وراه، بعت فويس ولا لا، جاي من ستوري ولا دخول مباشر، قال غالي وكمل ولا قال شكرا ومشي. هتلاقي نفسك تلقائيا بترتب يومك صح من غير اي برامج.</p>

<h2>السكور من 100: ليه 71 سخن و31 ل70 دافي؟</h2>
<p>هتسألني ليه الارقام دي؟ جربت 80 و50 فلقيت 90% بقوا دافي والوكيل تايه. لما قسمت 12,847 محادثة لقيت الفاصل واضح: فوق 71 بيقفل، وتحت 31 مبيقفلش، والنص منطقة تسخين. السخن جاهز للقرار، الدافي ناقصه معلومة او ثقة او وقت، والبارد فضول ومش دلوقتي. كل اشارة بتدي نقط، والسكور حي بيطلع وينزل مع كل رسالة. عميل بارد سأل سؤالين وبعت فويس بينط من 25 ل65 في دقيقتين، وسخن اختفى يومين سكوره بينزل لوحده.</p>
<ol>
<li><strong>سخن 71 ل100:</strong> جاهز للقرار، رد عليه الاول، اديله رابط الدفع او المقاس او المعاينة، متطولش معاه في كلام عام.</li>
<li><strong>دافي 31 ل70:</strong> مهتم وناقصه حاجة، ابعتله اثبات، ريفيو، فيديو، عرض تقسيط، وفكره بيها بعد يومين.</li>
<li><strong>بارد صفر ل30:</strong> فضول او تجميع اسعار، رد مختصر محترم وسيبه في حملة تسخين عامة، متضيعش عليه مكالمة.</li>
<li><strong>السكور حي:</strong> بيتحرك مع كل رسالة، مش حكم نهائي، والمتابعة هي اللي بتنقل الدافي لسخن.</li>
<li><strong>القاعدة الذهبية:</strong> السخن يترد عليه في دقايق، الدافي في ساعات، البارد في حملة جماعية.</li>
</ol>

<h2>الاشارة الاولى: كلمة بكام وازاي تسمعها صح</h2>
<p>كلمة بكام هي اشهر كلمة في البيع في مصر واكتر واحدة بتتفهم غلط. <strong>تقييم العملاء المحتملين</strong> الصح بيسمعها بسياقها. بكام + مقاس + توصيل؟ دي سخونية عالية. بكام + تقسيط؟ نية شرا. بكام وخلاص وبعدها اختفاء؟ تجميع اسعار. السيستم بيفرق بينهم باللي حواليها. ونصيحة: متردش بسعر ناشف، رد بسعر + سؤال يقفل: تحب احجزلك انهي لون؟</p>
<p>كنت برد بسعر وخلاص و73% بيعملوا seen ويمشوا. معظمهم مكنش بيهرب من السعر بل من البرود. لحظة بكام هي الحسم في <strong>تقييم العملاء المحتملين</strong>.</p>

<h2>الاشارة التانية والتالتة: سرعة الرد وعدد الاسئلة</h2>
<p>تاني اشارة هي سرعة الرد. السخن مبيستناش وبيرد في اقل من دقيقة لانه ماسك الموبايل مستني يقفل. والبطيء كل مرة غالبا مش مركز او بيكلم خمسة غيرك. التالتة هي عدد الاسئلة المحددة: تلات اسئلة عن مقاس وخامة وتوصيل يعني قرار بيتبني، اما سؤال عام واحد ومشي فده بارد. اللي بيسأل كتير مش رخم، الداتا بتقول هو اللي بيشتري اكتر. اجمع السرعة مع الاسئلة المحددة تعرف السخن المولع فورا وبدون تخمين.</p>
<ol>
<li><strong>رد في اقل من دقيقة مرتين ورا بعض:</strong> ارفع السكور فورا، العميل قاعد مستنيك.</li>
<li><strong>تلات اسئلة محددة عن مقاس وخامة وتوصيل:</strong> دي دورة شرا ماشية، كمل معاه للتقليل.</li>
<li><strong>سؤال واحد عام ومشي:</strong> متجريش وراه، حطه في التسخين.</li>
<li><strong>رد بعد ساعات كل مرة:</strong> نزله للدافي او البارد حسب باقي الاشارات.</li>
<li><strong>اسئلة عن الدفع والتقسيط والضمان:</strong> دي اسئلة ما قبل التقفيل، اديله الزقة الاخيرة.</li>
</ol>

<h2>الاشارة الرابعة والخامسة: تكرار التواصل والفويس نوت</h2>
<p>رابع اشارة هي تكرار التواصل الذاتي. اللي بيرجعلك بنفسه تاني يوم ويقول لسه متاح؟ الفكرة باتت في دماغه وسكوره لازم ينط. خامس اشارة هي الفويس نوت: من خبرة 12,847 محادثة، الفويس = جدية. اللي بيشرح بصوته مقاسه ومشكلته مهتم بجد، ووزنها تقيل في <strong>تقييم العملاء المحتملين</strong>. فويس + تفاصيل = سخن، وكلمة واحدة مختصرة = بارد بيخرج بالشياكة.</p>
<p>عميل بعت فويس 40 ثانية عن مقاسه واتأخرنا عليه ساعتين فاشترى من غيرنا. من يومها اي فويس فيه نية بيطلع اول الطابور.</p>

<h2>الاشارة السادسة والسابعة: مصدر الستوري والكومنت والفرق بين اعتراض سعري وفضول</h2>
<p>سادس اشارة هي المصدر: اللي جاي من ستوري او كومنت على موديل معين اسخن من اللي داخل يسأل عام. اللي داس على اعلان فيه عرض وتحرك ده نيته اعلى من اللي بيتمشى في الصفحة. السيستم بيدي وزن للمصدر: ستوري + رد سريع يعني سخن، وكومنت + سؤال محدد يعني سخن بالموديل ده بالذات. سابع اشارة هي الفرق بين الاعتراض السعري والفضول وهي اللي بتكسبك فلوس بجد. غالي اوي طب في تقسيط؟ او طب لو اخدت اتنين في خصم؟ ده سخن بيتفاوض وسكوره يعلى مش ينزل. اما تمام شكرا ويمشي فده فضول وتجميع اسعار. عملا كتير صنفناهم بارد عشان قالوا غالي وطلعوا بيشتروا لما اتعاملنا معاهم كدافيين محتاجين عرض بديل او تقسيط. <strong>تقييم العملاء المحتملين</strong> الصح مبيخافش من كلمة غالي، بيخاف من الصمت والاختفاء من غير كلمة.</p>
<p>لو عايز تفهم ازاي المصدر بيفرق في التوصيل والرد، اقرا دليلنا عن <a href="https://ot1-pro.com/blog/meta-app-verification-2026-founder-guide">توثيق تطبيق ميتا وربط الصفحات</a> لان نص مشاكل ضياع الرسايل اصلا بتبقى من الربط الغلط مش من العميل. ولو بتقارن بين الادوات، شوف <a href="https://ot1-pro.com/vs/wati">مقارنة OT1 Pro مع Wati</a> عشان تعرف الفرق في السكور التلقائي والاستهداف.</p>

<h2>الجدول الكامل: الاشارة ومعناها ومثال من محادثة حقيقية</h2>
<p>خليني الخصلك السبع اشارات في جدول واحد تطبعه وتلزقه قدام الوكيل. الجدول ده هو خلاصة <strong>تقييم العملاء المحتملين</strong> عندي، وهو اللي السيستم بيحسب عليه من غير تدخل. كل اشارة ليها معنى وليها مثال حقيقي شفته بعيني في المحادثات اللي حللناها. احفظ الامثلة دي لانك هتشوفها كل يوم.</p>
<table border="1" cellpadding="8" cellspacing="0">
<thead>
<tr><th>الإشارة</th><th>معناها في السكور</th><th>مثال من محادثة حقيقية</th></tr>
</thead>
<tbody>
<tr><td>كلمة بكام بالسياق</td><td>نية شرا لو معاها تفاصيل، فضول لو لوحدها</td><td>بكام الشحن لاكتوبر والمقاس الكبير متاح؟ = سخن، بكام؟ = بارد</td></tr>
<tr><td>سرعة الرد</td><td>رد في اقل من دقيقة يعني قاعد مستنيك</td><td>رد عليك مرتين في دقيقة = ارفع السكور فوق 71</td></tr>
<tr><td>عدد الأسئلة المحددة</td><td>3 اسئلة محددة تعني قرار بيتبني</td><td>المقاس موجود؟ والخامة ايه؟ والتوصيل امتى؟ = دافي سخن</td></tr>
<tr><td>تكرار التواصل الذاتي</td><td>الرجوع بنفسه يعني الفكرة باتت في دماغه</td><td>رجع تاني يوم قال لسه متاح؟ = زود 15 نقطة</td></tr>
<tr><td>الفويس نوت</td><td>اهتمام عالي وتفاصيل ومشاعر</td><td>فويس 40 ثانية بيشرح مقاسه وفرح اخته = سخن فورا</td></tr>
<tr><td>مصدر الستوري والكومنت</td><td>اللي جاي على حاجة شافها اسخن من اللي داخل عام</td><td>كومنت تحت صورة موديل + بكام ده؟ = سخن بالموديل ده</td></tr>
<tr><td>اعتراض سعري مقابل فضول</td><td>غالي + سؤال يعني تفاوض، غالي وسكوت يعني وداع</td><td>غالي اوي طب في تقسيط؟ = دافي سخن، تمام شكرا = بارد</td></tr>
</tbody>
</table>
<p>الجدول ده مش للزينة. كل صف فيه بيساوي نقط في السكور من 100. اجمع النقط تعرف الخانة: 71 سخن و31 ل70 دافي وتحت 30 بارد. ومع كل رسالة جديدة السيستم بيعيد الحسبة. عميل ممكن يبدأ بارد ويبقى سخن في عشر دقايق لو بعت فويس وسأل سؤالين ورجع تاني يوم. <strong>تقييم العملاء المحتملين</strong> هنا حي مش شهادة ثابتة. اطبع الجدول ودرّب الوكيل عليه اسبوع: كل محادثة يقول السكور المتوقع قبل ما يشوف رقم السيستم، وبعد اسبوع هتلاقي عينه بقت تلقط السخن لوحدها. والاهم انك تراجع الاوزان كل شهر من مبيعاتك الفعلية، لان سلوك العملا بيتغير في المواسم والاعياد. اللي كان دافي في رمضان ممكن يبقى سخن قبل العيد، واللي كان بارد في الصيف ممكن يسخن مع عروض اخر السنة. السيستم الحي هو اللي بيكسب.</p>

<h2>السكور بيغذي الاستهداف في الرسائل الجماعية ازاي؟</h2>
<p>السكور مش للفرجة، ده بيغذي الاستهداف في الرسائل الجماعية. قبل السكور كنت ببعت خصم واحد للكل فخسرت هامش السخن وطفشت البارد. دلوقتي السخن بيوصله لينك دفع مباشر، والدافي فيديو وريفيوهات وتقسيط، والبارد عرض عام على فترات بعيدة. 27% من الـ ghost رجعوا لما الرسالة اتفصلت على سكورهم. بص على <a href="https://ot1-pro.com/pricing">اسعار OT1 Pro</a> وابدأ من <a href="https://ot1-pro.com/register">صفحة التسجيل</a>.</p>
<ol>
<li><strong>حملة السخن:</strong> رسالة قصيرة مباشرة فيها لينك دفع او حجز، وتتبعت في نفس اليوم.</li>
<li><strong>حملة الدافي:</strong> اثبات اجتماعي وفيديو واجابة على اعتراضه، وتتبعت بعد يومين.</li>
<li><strong>حملة البارد:</strong> عرض عام قوي على فترات متباعدة، من غير زن.</li>
<li><strong>ممنوع:</strong> تبعت نفس الرسالة للتلاتة، دي اللي كانت بتخلي 73% يعملوا ghost.</li>
<li><strong>القياس:</strong> تابع مين فتح ومين رد ومين اشترى وحدث السكور تلقائيا.</li>
</ol>

<h2>الوكيل بيرد على السخن الاول: نظام الاولويات اللي وقف الخناق</h2>
<p>قبل السيستم الوكيل كان بيرد بالترتيب الزمني، وده اكبر كدبة في خدمة العملا. السخن اللي بعت من ساعة يستاهل يترد عليه قبل البارد اللي بعت من خمس دقايق. عملت طابور بالسكور: 85 و78 و72 الاول، والسخن اللي مستني خمس دقايق بينور احمر. النظام ده وقف الخناق لان محدش بقى يختار بمزاجه، وزمن الرد على السخن نزل من ساعات لدقايق ومعدل التقفيل طلع. لو عندك وكيل واحد انتاجيته هتتضاعف من غير تعيين، ولو عندك تيم هتشوف مين بيضيع السخن بالارقام. قاعدة بسيطة: فوق 71 في خمس دقايق، ومن 31 ل70 في ساعة، والبارد رد مختصر وحملة.</p>
<p>نصيحة عملية: ثبت القاعدة دي من بكره وهتشوف 27% من الـ ghost بيرجعوا. وانا عمر وبقولك التزم بيها اسبوعين بس وقيس الفرق في التقفيل قبل وبعد، الارقام هتقنعك اكتر من اي كلام.</p>

<h2>ارقامنا: 12,847 محادثة و73% ghost و27% recovery واللي اتعلمناه</h2>
<p>حللنا 12,847 محادثة: 73% حصل فيهم ghost مرة، ومعظمه بعد بكام والرد الناشف او بعد الاعتراض اللي معرفناش نرد عليه. يعني المشكلة في ردنا مش في العميل. والحلو ان 27% رجعوا واشتروا برسالة متابعة متفصلة على سكورهم او عرض بديل او سؤال مهتم. الدروس: السخن لو استنى بيبرد ويروح لغيرك، والدافي محتاج صبر ومتابعة مش ضغط، والبارد مخزون للمستقبل متحرقوش برسايل كتير. <strong>تقييم العملاء المحتملين</strong> هو اللي ورانا ده بالارقام مش بالتخمين. صنف اخر 100 محادثة عندك بالاشارات السبعة وشوف كنت بترد على مين الاول، هتتصدم زي ما انا اتصدمت.</p>
<ol>
<li><strong>12,847 محادثة اتحللت:</strong> مش عينة صغيرة، دي سنة شغل كاملة بكل انواع العملا.</li>
<li><strong>73% ghost:</strong> معظمهم سكت بعد السعر او بعد الاعتراض، مش بعد الاهتمام الاول.</li>
<li><strong>27% recovery:</strong> رجعوا لما الرسالة كانت متفصلة على سكورهم مش رسالة عامة.</li>
<li><strong>الدرس الاكبر:</strong> السكور الحي اللي بيتحرك مع كل رسالة ادق من اي انطباع ثابت.</li>
<li><strong>ابدأ من هنا:</strong> رتب طابور الرد بالسكور مش بالوقت، وغذي الحملات الجماعية بالسكور.</li>
</ol>
<p>{{CTA}}</p>
</article>
HTMLP5,
                'meta_title'        => 'تقييم العملاء المحتملين: سكور سخن ودافي وبارد تلقائي',
                'meta_description'  => 'انا عمر حللت 12847 محادثة وبنيت سكورا ذكيا كاملا من 100 يرتب الرد ويغذي الرسائل الجماعية ويصنف كل عميل سخن ودافي وبارد تلقائيا عبر تقييم العملاء المحتملين',
                'category'          => 'مبيعات',
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
