# OT1-Pro — Meta video ads, Egypt (Nov 2026)

Two 90-second vertical (9:16, 1080×1920) ads, Egyptian Arabic voiceover (male voices only) + burned-in Arabic captions, no people on screen.

| | File | Voice (ElevenLabs, `eleven_v4`) | Angle |
|---|---|---|---|
| **A — General** | `out/ot1-pro-general-9x16.mp4` | Omarii (Egyptian, persuasive sales) | Owner who can't afford a moderator team, loses 2 AM orders |
| **B — Real estate** | `out/ot1-pro-realestate-9x16.mp4` | Omda (Egyptian, premium/real-estate) | Paying for FB leads, losing them in the first hour |

Both end on the same offer as `/pricing` and the OT1 page bot (`scripts/update-ot1-ai-20261109-v3.php`): **AI Pilot 3,500 EGP / 14 days, full refund under 50 real conversations → Founding Partner 7,500 EGP setup + 2,500 EGP/month → White-Glove for custom/multi-brand/CRM**. Positioning line on every ad: **«شريكك في المبيعات، مش شات بوت»**.

---

## Campaign setup (both ads)

- **Objective:** Engagement → **Messages**, destination **WhatsApp** (+20 102 636 1218). We're concierge, not self-serve, so a conversation is the conversion — not a `/register` signup. (This supersedes the "Conversions → Trial signup" objective in `docs/meta-ads-brief.md`, which predates the Founding Partner model.)
- **Prefilled WhatsApp message** (tags the source so you know which ad paid for the chat):
  - A: `عايز أعرف أكتر عن الشريك المؤسس - VID-GEN-EG`
  - B: `عندي شركة/مكتب عقارات وعايز أعرف أكتر - VID-RE-EG`
- **Placements:** Advantage+ placements, but make sure Reels, Stories and Feeds are on. Layout respects the 9:16 safe zones (no text in the top 14% / bottom 35%), and the middle 4:5 crop works in Feed. If Ads Manager flags a placement for the 90s length, let it drop that placement.
- **Budget:** one ad set per ad, **$10/day each for 7 days**, as the brief says. After day 3, compare *cost per WhatsApp conversation* and, more importantly, *conversations that ask about price or book the 90-min call*. Kill the loser and move the money.
- **Retargeting (the money layer):** create a custom audience **"Video viewers ≥ 50%, last 30 days"** for each video, exclude anyone who already messaged, and retarget them at $3–5/day with the same video or a short cut. People who sit through 45+ seconds of a price-stated ad are your warmest buyers.
- **Thumbnail:** A → the 2 AM lock screen (≈ 1s). B → "المشكلة في أول ساعة" (≈ 13s).

---

## Ad A — General small business

**Primary text (post caption):**

```
صاحب بيزنس لسه بيبدأ؟ مش محتاج تعيّن فريق موديريتورز عشان ترد على الرسايل وتتابع العملاء.

OT1-Pro مش شات بوت بيقول «تم استلام رسالتك».
ده مساعد مبيعات بالذكاء الاصطناعي، شغال 24 ساعة على واتساب وإنستجرام وفيسبوك:

✅ بيرد على الأسعار والمقاسات والمتاح، ولو العميل قال «غالي» بيعرف يرد لحد ما يأكد الأوردر
✅ بيسمع الفويس نوت ويفهم الصور والسكرين شوتس
✅ بيرد على كومنتات البوستات ويحوّل المهتمين للخاص
✅ بيقولك مين العميل الجاد ومين بيتفرج
✅ بيوريك أكتر حاجة بتتطلب، وليه الناس بتتردد
✅ الأوردرات الجاهزة بتنزل في الـ Google Sheet بتاعك: الاسم والرقم والعنوان والطلب. انت بس تشحن 📦

وإحنا مش هنبيعلك برنامج ونمشي. إحنا شركا: بنبني الـ AI معاك في مكالمة 90 دقيقة بأسلوب البيزنس بتاعك، ونفضل معاك 30 يوم نظبطه كل أسبوع.

💰 جرّب 14 يوم بـ 3,500 جنيه، ولو ما اتعاملش مع 50 محادثة حقيقية فلوسك ترجعلك كاملة.
🤝 الشريك المؤسس: 7,500 جنيه إعداد + 2,500 جنيه في الشهر.
🛠️ عايز ربط بمتجرك أو الـ CRM أو كذا براند؟ بنبنيهولك.

ابعتلنا كلمة «شريك» على واتساب 👇
```

- **Headline:** `مساعد مبيعات AI.. مش شات بوت`
- **Description:** `تجربة 14 يوم بضمان استرداد كامل`
- **CTA button:** `إرسال رسالة` (Send WhatsApp message)

**Audience A**

| Setting | Value |
|---|---|
| Location | Egypt: Cairo, Giza, Alexandria (+ Mansoura, Tanta once it's profitable) |
| Age | 23–50 |
| Gender | All. The no-women rule applies to the creative, not to who sees the ad, since women run a large share of Egyptian IG stores. Narrow it only if you want to. |
| Language | Arabic |
| Detailed targeting (include any) | Interests: Small business, Entrepreneurship, Online shopping, E-commerce, Shopify, WooCommerce, Facebook for Business, Instagram for Business, WhatsApp Business, Bosta, Aramex |
| **Narrow — must also match** | Behaviors: Small business owners / Facebook Page admins · *or* Job titles: Owner, Founder, Store owner, Entrepreneur |
| Exclude | Existing customers, people who already messaged, ages < 22 |

---

## Ad B — Real estate

**Primary text (post caption):**

```
بتصرف آلافات على إعلانات فيسبوك عشان الليدز… والسيلز بيرد تاني يوم؟ 🏢

الليد اللي بعت الساعة 11 بالليل «الشقة دي سعرها كام؟ وفيه تقسيط؟» بيكون كلّم 3 شركات غيرك قبل ما حد يرد عليه.

OT1-Pro بيرد على كل ليد في ثواني، 24 ساعة، على واتساب وماسنجر وإنستجرام، وحتى على كومنتات الإعلان:

✅ بيسأل أسئلة السيلز بتاعك: الميزانية، المنطقة، شقة ولا فيلا، كاش ولا تقسيط، الاستلام إمتى
✅ بيبعت تفاصيل المشروع ونظام السداد، وبيسمع الفويس نوت
✅ بيقسّم الليدز سخن / دافي / بارد، عشان فريقك يكلم الجاهز للشرا الأول
✅ كل ليد جاهز بينزل في الـ Google Sheet: الاسم والرقم والميزانية والمنطقة. السيلز يمسك التليفون ويقفل 📞
✅ بيقولك أنهي مشروع عليه طلب، وإيه أكتر اعتراض بيوقف العملا

وإحنا مش أداة وخلاص. إحنا شركا: بنبني الـ AI معاك على مشاريعك وأسعارك في مكالمة 90 دقيقة، ونفضل معاك 30 يوم نظبطه أسبوع بأسبوع.

💰 تجربة 14 يوم بـ 3,500 جنيه، ولو ما اتعاملش مع 50 محادثة حقيقية فلوسك ترجعلك.
🤝 الشريك المؤسس: 7,500 جنيه إعداد + 2,500 جنيه في الشهر.
🏗️ عندك كذا مشروع أو محتاج الليدز تروح على الـ CRM (Zoho / HubSpot / Salesforce)؟ باقة مخصوص بنسعّرها على قد شغلك.

ابعتلنا كلمة «عقارات» على واتساب 👇
```

- **Headline:** `ولا ليد يستنى للصبح تاني`
- **Description:** `AI بيرد ويأهّل ليدز العقارات 24 ساعة`
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

⚠️ **The biggest risk on B:** "Real estate" interests are mostly *home buyers*, not sellers. Keep the job-title / page-admin narrowing ON. If the first 20 chats are buyers asking about apartments, switch to a job-titles-only audience.

---

## Claims check (every on-screen claim is backed by the product)

| Claim in ad | Where it's real |
|---|---|
| Voice notes understood, images understood | `app/Jobs/TranscribeAudio.php`, `app/Jobs/DescribeImage.php` |
| Replies to comments, moves them to DM | `app/Jobs/SendAiCommentReplyJob.php` (comments pipeline) |
| Hot / warm / cold lead scoring | `app/Models/Contact.php` (`lead_score ≥ 71 → hot`), `app/Jobs/ScoreLeadJob.php` |
| Top requested / top objections | `app/Livewire/Analytics.php::getTopObjections()` |
| Ready orders/leads land in your Google Sheet | `app/Services/SalesConnectors/SalesConnectors.php` (deal-closed / lead-captured rows → Sheet) |
| Prices, refund guarantee, 90-min call, 30 days co-admin, CRM push in White-Glove | `resources/views/pages/pricing.blade.php`, `lang/ar.json` |
| "Less than half one employee's salary" | 2,500 EGP/month vs Egypt's private-sector minimum wage (7,000 EGP/month since March 2025) |

All chat contents, names, sheet rows and chart numbers are illustrative and labelled **«مثال توضيحي»** on screen.

⚠️ **One thing to align before launch:** the page bot (v3 prompt) leads with a **free 2-day trial**. The `/pricing` page says "No free trial". The ads quote the paid Pilot, which filters for buyers, and leave the free trial as a closer inside the chat. That's a fine funnel, but make sure whoever answers the WhatsApp chats knows both offers exist.

---

## Re-rendering / editing

Everything is code, so you can change a price, a line or a colour and re-render in ~2 minutes.

```
scripts/<ad>.txt           # exact text sent to ElevenLabs (one line = one sentence = one cue)
audio/<ad>-raw.mp3         # ElevenLabs take (sped up ×1.07 at mux time to land on 90s)
align.py                   # pause-based line alignment → engine/timing-<ad>.json
engine/ad.html             # renderer: renderAt(t) is a pure function of time
engine/shared.js           # partner timeline, pricing, custom, CTA scenes (shared)
engine/scenes-<ad>.js      # per-ad scenes + on-screen captions
render.mjs                 # Playwright frame capture → ffmpeg (H.264/AAC, −14 LUFS)
```

```bash
python3 align.py scripts/general.txt audio/general-raw.mp3 engine/timing-general.json 1.07
node render.mjs general                       # → out/ot1-pro-general-9x16.mp4
node render.mjs general --stills 5,30,60      # quick PNG previews
```

If you change the spoken script, regenerate the voice in ElevenLabs, save it over `audio/<ad>-raw.mp3`, and re-run `align.py` with a tempo of `raw_duration / 89.4`.
