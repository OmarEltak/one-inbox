# OT1-Pro — Meta video ads, Egypt (Nov 2026) · v2

Two 90-second vertical (9:16, 1080×1920) ads. Egyptian Arabic male voiceover, burned-in Arabic captions, music bed + sound effects, no people on screen.

v2 replaces the v1 "missed message at 2 AM" ads. That story was the same as the previous Saudi ad (`OT1Pro_Ad2_saudi_v2`) and the default angle of every chatbot. Each v2 ad is built on a concept where the **hook, the pain points and what makes us different are shown, not just told**.

| | Concept | Hook (first 3s) | Pain | What makes us different | Voice (ElevenLabs `eleven_v4`) |
|---|---|---|---|---|---|
| **A — General** | «مطلوب موظف»: a job ad | A job poster: «مطلوب موظف خدمة عملا» | The 6 "requirements" are everything a human moderator can't do | The punchline: the "salary" is 2,500 EGP/month, then the reveal: it's OT1-Pro, built with you, partners not software | Mostafa (energetic Egyptian male) |
| **B — Real estate** | Same lead, two endings: a split screen | «نفس الليد. نفس الرسالة. نفس الساعة… 23:04» | Sales asleep; replies at 1 PM "السعر في الخاص"; the lead already talked to 3 other companies | Same lead in OT1-Pro: answered in seconds, qualified, voice note handled, in your sheet with **score 92/100 + an AI note**, all by 00:30 | Omda (premium Egyptian male) |

Never mentions any person by name (no founder name in voice, visuals or captions).

## Status

| | File | State |
|---|---|---|
| A | `out/ot1-pro-general-9x16-preview.mp4` | Visuals, captions, music, SFX. **Needs the voiceover.** |
| B | `out/ot1-pro-realestate-9x16-preview.mp4` | Visuals, captions, music, SFX. **Needs the voiceover.** |

**Blocker: the ElevenLabs workspace is out of credits** (*"You have 425 credits remaining"*). A take costs ~1,100 (A) and ~1,170 (B) credits on `eleven_v4`. Once credits are added, finishing takes ~5 minutes per ad:

```bash
# 1. generate the voice (ElevenLabs flow, eleven_v4, voice + text from scripts/<ad>.txt), save as audio/<ad>-raw.mp3
# 2. align and render
python3 align.py scripts/general.txt audio/general-raw.mp3 engine/timing-general.json <raw_seconds/89.4>
node render.mjs general            # → out/ot1-pro-general-9x16.mp4
```

The previews use estimated timing (`estimate_timing.py`). The real take re-times every scene and caption to the voice automatically.

---

## Campaign setup (both ads)

- **Objective:** Engagement → **Messages**, destination **WhatsApp** (+20 102 636 1218). We're concierge, not self-serve, so a conversation is the conversion.
- **Prefilled WhatsApp message** (tags which ad paid for the chat):
  - A: `شريك - عايز أعرف أكتر - VID-JOB-EG`
  - B: `عقارات - عايز أعرف أكتر - VID-RE-EG`
- **Placements:** Advantage+, with Reels, Stories and Feeds on. Text stays out of the 9:16 top 14% / bottom 35% safe zones, and the middle 4:5 crop works in Feed. If Ads Manager flags a placement for the 90s length, let it drop that placement.
- **Budget:** one ad set per ad, **$10/day each for 7 days**. Judge on *conversations that ask about price or book the 90-min call*, not cost per conversation alone. Kill the loser and move the money.
- **Retargeting:** custom audience "Video viewers ≥ 50%, last 30 days" per ad, excluding anyone who already messaged, at $3–5/day.
- **Thumbnail:** A → the «مطلوب» poster (≈ 1s). B → the split screen with both clocks at 23:04 (≈ 7s).

---

## Ad A — «مطلوب موظف» (general)

**Primary text (post caption):**

```
مطلوب موظف خدمة عملا 👇

✅ يشتغل 24 ساعة، من غير إجازة ولا جمعة ولا عيد
✅ يرد في ثواني على واتساب وإنستجرام وفيسبوك… وحتى الكومنتات
✅ يحفظ كل منتجاتك وأسعارك، ويسمع الفويس نوت ويفهم الصور
✅ لما العميل يقول «غالي» يقنعه لحد ما يأكد الأوردر، مش «السعر في الخاص»
✅ يدّي كل عميل سكور من 100 + ملاحظة على كل محادثة: جاد ولا بيتفرج، وليه
✅ ينزّل الأوردرات الجاهزة في الـ Google Sheet بتاعك… انت بس تشحن 📦

المرتب: 2,500 جنيه في الشهر. مستحيل؟

ده مش موظف. ده OT1-Pro: مساعد مبيعات بالذكاء الاصطناعي، مبني مخصوص للبيزنس بتاعك.
مش تمبلت جاهز: بنبنيه معاك في مكالمة 90 دقيقة، ونفضل معاك 30 يوم نظبطه كل أسبوع. إحنا شركا، مش برنامج.

💰 جرّبه 14 يوم بـ 3,500 جنيه، ولو ما اتعاملش مع 50 محادثة حقيقية فلوسك ترجعلك كاملة.
🤝 الشريك المؤسس: 7,500 جنيه إعداد + 2,500 جنيه في الشهر.
🛠️ ربط بمتجرك أو الـ CRM أو كذا براند؟ بنبنيهولك.

الوظيفة اتملت ✅ ابعتلنا كلمة «شريك» على واتساب 👇
```

- **Headline:** `مطلوب موظف بـ 2,500 جنيه.. شغال 24 ساعة`
- **Description:** `مساعد مبيعات AI مبني مخصوص لبيزنسك`
- **CTA button:** `إرسال رسالة` (Send WhatsApp message)

**Audience A**

| Setting | Value |
|---|---|
| Location | Egypt: Cairo, Giza, Alexandria (+ Mansoura, Tanta once profitable) |
| Age | 23–50 |
| Gender | All (the no-women rule applies to the creative, not to who sees it) |
| Language | Arabic |
| Detailed targeting (include any) | Small business, Entrepreneurship, Online shopping, E-commerce, Shopify, WooCommerce, Facebook for Business, Instagram for Business, WhatsApp Business, Bosta, Aramex |
| **Narrow — must also match** | Behaviors: Small business owners / Facebook Page admins · *or* Job titles: Owner, Founder, Store owner, Entrepreneur |
| Exclude | Existing customers, people who already messaged, ages < 22 |

---

## Ad B — Same lead, two endings (real estate)

**Primary text (post caption):**

```
نفس الليد. نفس الرسالة. نفس الساعة… 11 بالليل 🏠
«الشقة دي سعرها كام؟ وفيه تقسيط؟»

😴 السيلز: نايم. رد تاني يوم الضهر «السعر في الخاص»… والليد كان اتكلم مع 3 شركات غيرك.

⚡ OT1-Pro: رد في ثواني، بعت السعر ونظام السداد والبروشور، سأل عن الميزانية والمنطقة والكاش ولا التقسيط والاستلام، سمع الفويس نوت… ونزّل الليد في الشيت بتاعك بسكور 92 من 100 وملاحظة: «جاد، ميزانيته جاهزة، وعايز معاينة الأسبوع ده».

الفرق مش في الإعلان. الفرق في أول ساعة.

✅ سكور + ملاحظة على كل محادثة، عشان فريقك يكلم الجاهز للشرا الأول
✅ أكتر اعتراض بيوقف عملاك (السعر؟ المكان؟ المُقَدَّم؟) عشان إعلانك الجاي يبقى أذكى
✅ واتساب وماسنجر وإنستجرام وكومنتات الإعلان، 24 ساعة

إحنا شركا مش أداة: بنبنيه معاك على مشاريعك في مكالمة 90 دقيقة، ونفضل معاك 30 يوم نظبطه.

💰 تجربة 14 يوم بـ 3,500 جنيه بضمان استرداد كامل.
🤝 الشريك المؤسس: 7,500 جنيه إعداد + 2,500 جنيه في الشهر.
🏗️ كذا مشروع أو ربط بالـ CRM (Zoho / HubSpot / Salesforce)؟ باقة مخصوص على قد شغلك.

ابعتلنا كلمة «عقارات» على واتساب 👇
```

- **Headline:** `نفس الليد.. نهايتين مختلفين`
- **Description:** `AI بيرد ويأهّل ليدز العقارات في ثواني`
- **CTA button:** `إرسال رسالة` (Send WhatsApp message)

**Audience B**

| Setting | Value |
|---|---|
| Location | Egypt: Cairo (esp. New Cairo / 5th Settlement), Giza (Sheikh Zayed, 6th of October), Alexandria |
| Age | 25–55 |
| Gender | All |
| Language | Arabic |
| Detailed targeting (include any) | Job titles: Real estate agent, Real estate broker, Property consultant, Sales manager, Real estate developer, Marketing manager · Interests: Real estate, Real estate investing, Commercial real estate, Aqarmap, Property Finder, Nawy |
| **Narrow — must also match** | Behaviors: Facebook Page admins / Small business owners · *or* the job titles above |
| Exclude | Existing customers, people who already messaged |

⚠️ "Real estate" interests are mostly *home buyers*. Keep the job-title / page-admin narrowing ON. If the first 20 chats are buyers asking about apartments, switch to job titles only.

---

## Claims check (every on-screen claim is backed by the product)

| Claim | Where it's real |
|---|---|
| Voice notes understood, images understood | `app/Jobs/TranscribeAudio.php`, `app/Jobs/DescribeImage.php` |
| Replies to comments | `app/Jobs/SendAiCommentReplyJob.php` |
| Score per lead + a note on every conversation | `app/Jobs/ScoreLeadJob.php` → `LeadScoreEvent.reason` / `ai_analysis` on every score change |
| Top objections | `app/Livewire/Analytics.php::getTopObjections()` |
| Orders/leads land in your Google Sheet | `app/Services/SalesConnectors/SalesConnectors.php` |
| Prices, refund guarantee, 90-min call, 30 days, CRM push in White-Glove | `resources/views/pages/pricing.blade.php`, `lang/ar.json` |
| "2,500/month salary" | It is the Founding Partner monthly fee. The 7,500 setup fee is stated in the same ad. |

All chats, names, prices of example products, sheet rows and charts are illustrative and labelled **«مثال توضيحي»** on screen.

⚠️ The page bot (v3 prompt) offers a **free 2-day trial**, while `/pricing` says "No free trial". The ads quote the paid Pilot, which filters for buyers. Make sure whoever answers WhatsApp knows both offers exist.

---

## How it's built (edit anything, re-render in ~2 min)

```
scripts/<ad>.txt           # exact text sent to ElevenLabs (one line = one sentence = one cue)
audio/<ad>-raw.mp3         # the ElevenLabs take (missing until credits are added)
estimate_timing.py         # placeholder timing for previews (no voice)
align.py                   # real timing: pause-based line alignment → engine/timing-<ad>.json
engine/ad.html             # renderer: renderAt(t) is a pure function of time; poster themes, split screen
engine/shared.js           # partner timeline, pricing, CTA, bar charts (shared)
engine/scenes-<ad>.js      # per-ad scenes, on-screen captions, SFX cues
soundtrack.py              # synthesised music bed + SFX (no samples, no licences)
render.mjs                 # Playwright frame capture → ffmpeg (H.264/AAC), voice + bed mix
```

```bash
node render.mjs general --stills 2,20,45      # quick PNG previews
```
