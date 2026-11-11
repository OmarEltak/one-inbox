# OT1-Pro — Arabic Video Ads: Master Prompt

> Paste everything below the line into a Claude session that has a Higgsfield connector **with generation enabled** (or hand it to whoever runs Higgsfield). Written 2026-11-11.

---

## ROLE

You are the creative director, scriptwriter, and producer for **OT1-Pro** (https://ot1-pro.com/?lang=ar). Produce two finished Arabic video ads, each **90+ seconds**, with a real-sounding Egyptian voice (no flat, generic AI voice), plus cut-downs, Arabic captions (.srt), and Meta ad copy. Deliver final MP4s, not a plan.

## PRODUCT FACTS (do not invent beyond these)

- **What it is:** an AI sales assistant, not just a chatbot. It works 24/7 and replies in the customer's own dialect (Egyptian Arabic, Gulf, English).
- **Channels, all in one inbox:** WhatsApp, Instagram DMs, Facebook Messenger, Telegram, Email, and **a live chat widget on your own website**.
- **Sales:** answers questions about products, prices, and shipping from the owner's own price list. It understands what the customer needs, handles objections ("غالي", "هفكر", "الشحن بكام؟"), and guides them to confirm the order.
- **Follow-up:** automatic follow-ups at 24h, 72h, and 7 days for leads who go quiet. Real site data: 73% of people who ask about price go silent; 27% come back when the follow-up actually happens.
- **Lead scoring:** ranks customers by how likely they are to buy (hot / warm / cold), so the owner knows who to focus on.
- **Comments:** replies to comments on posts and moves interested people into DMs.
- **Analytics:** most-requested products, frequent questions, why customers hesitate or don't buy, and weak spots in marketing and sales.
- **Orders go to the owner's Google Sheet automatically** (name, phone, captured info): "you just ship."
- **VIP / custom:** trained on each business's own prices, tone, and policies. The founder's team sets it up with you personally (managed onboarding; founder reachable on WhatsApp).
- **Pricing:** Free plan (20 AI replies/month, no credit card). Basic $8/mo, Starter $29/mo, Pro $79/mo.
- **CTA:** "ابدأ مجاناً — من غير فيزا" → ot1-pro.com/register

**Do NOT claim:** AI voice calling (not shipped; at most add "قريباً" as a tease in a separate test ad), guaranteed sales numbers, fake testimonials, or fake customer logos. Any story scene with numbers must show a small "مشهد تمثيلي" label.

## AUDIENCE

Small and new business owners in **Egypt (Tier 1)** and **KSA / UAE / Kuwait (Tier 2)**, aged 22–45, selling through Instagram, WhatsApp, and Facebook, usually already running ads. Many are women running fashion, beauty, home, or food brands. Their pain: they can't afford a team of moderators, they miss messages at night, and leads disappear.

## VOICE (most important: no generic AI voice)

1. **Best option:** record the founder (or a hired Egyptian voice actor, ~$50–150 on Fiverr/Voices) for 2–3 minutes of clean audio. Create a **voice reference element** in Higgsfield, then generate the narration with **Eleven v4** (`elevenlabs_v4`, `voice_type: element`). Use stability ~0.35 and similarity ~0.8 so it sounds natural and lively.
2. **Fallback:** a preset ElevenLabs voice with an Egyptian accent. Test 3 options and choose the one that sounds most like a real Cairo speaker. Do not use MSA/فصحى voices.
3. Video A: warm, confident male voice, early 30s, like a friend giving advice, not a TV announcer. Video B: female voice, energetic, the voice of a brand owner.
4. Write the script in **Egyptian dialect spelled the way it's spoken**. Generate one line at a time so you can control pauses and emotion, then join them.

## VISUALS (Higgsfield models)

- **Story scenes:** `seedance_2_0` (mode std, 1080p, 9:16) with image references so the main character looks the same in every shot. Use `cinematic_studio_3_0` for hero shots.
- **On-camera speaking scenes:** generate the shot, then run `sync_so` (Lipsync 3) with the narration audio.
- **Music:** `elevenlabs_music_v2_5`, `force_instrumental: true`, 100s, modern warm Arabic-pop / oud-electronic, low under the voice.
- **Phone UI screens:** use real screenshots of the OT1-Pro inbox (Arabic interface, RTL) animated as overlays. Do not let the AI invent a fake UI.
- **Look:** realistic, warm, Egyptian/Gulf homes and small shops, a phone always visible, OT1-Pro brand colors on text cards.
- **Formats:** master in **9:16** (Reels/Stories/TikTok), plus 1:1 and 4:5 crops for Feed and 16:9 for YouTube.
- Every scene is 10–15s at most (the model limit). Plan ~8 clips per video and join them in an editor.

---

## VIDEO A — General / brand (~100s) — «فريق مبيعاتك اللي مبينامش»

| Time | Visual | Voiceover (Egyptian Arabic) | On-screen text |
|---|---|---|---|
| 0–4 | Dark bedroom, phone lighting up again and again on the nightstand, notifications 17… 18… | الساعة ٢ بالليل… وفيه ١٧ رسالة مستنية رد. | ١٧ رسالة بدون رد |
| 4–10 | Close-up on messages: "بكام؟" "متاح مقاس L؟" then the messages fade | وكل رسالة منهم ممكن تكون أوردر. بس انت نايم… والعميل مش هيستنى. هيروح للي رد الأول. | |
| 10–20 | Young owner at a small desk, calculator, "moderator salary" sheet, sighs | أي بيزنس لسه بيبدأ، مش محتاج يعيّن فريق موديريتورز ويدفع مرتبات، عشان يرد على الصفحات ويتابع العملاء. | من غير فريق موديريتورز |
| 20–28 | OT1-Pro logo reveal, phone lights up gold | عشان كده عملنا OT1-Pro. ومش شات بوت بيرد وخلاص… ده مساعد مبيعات بالذكاء الاصطناعي، شغال ٢٤ ساعة، وبيتكلم بلهجة عميلك. | مساعد مبيعات AI · ٢٤ ساعة |
| 28–40 | Real conversation UI: customer says "غالي شوية" and the AI replies smartly, then "تم تأكيد الأوردر ✅" | بيرد على أسئلة المنتجات والأسعار، يفهم العميل عايز إيه، يتعامل مع اعتراضاته — «غالي»، «هفكر»، «الشحن بكام» — ويوصّله لحد ما يأكد الأوردر. | 💰 زيادة المبيعات |
| 40–50 | Customer list with colored badges: 🔥 hot, warm, cold | وبيقولك مين العميل الجاد ومين بيتفرج بس… بيصنفهم حسب احتمالية الشراء، عشان توفّر وقتك للي هيدفع. | 🎯 متابعة العملاء |
| 50–58 | Instagram post with comments → AI reply → message in DMs | التعليقات على البوستات؟ بيرد عليها، ويكمل مع المهتم في الخاص، ويحوّل الكومنت لفرصة بيع. | 💬 التعليقات والرسائل |
| 58–68 | Simple dashboard: most-requested product, top 3 questions, "سبب التردد: الشحن" | وتعرف أكتر منتج بيتسأل عليه، والأسئلة اللي بتتكرر، وليه العميل اتردد ومشتريش… عشان تصلّح تسويقك على أرقام، مش تخمين. | 📊 تحليل العملاء |
| 68–76 | Google Sheet filling up row by row by itself, then a hand packing a box | واللي قفل؟ بيتسجل لوحده في الشيت بتاعك: الاسم، الرقم، والأوردر. انت بس تشحن. | الأوردرات جاهزة · اشحن وبس |
| 76–86 | Channel icons gather into one inbox: WhatsApp, Instagram, Messenger, Telegram, Email, website live chat | واتساب، إنستجرام، ماسنجر، تليجرام، الإيميل، والشات اللي على موقعك… كله في مكان واحد. وبيتدرّب على البيزنس بتاعك انت: أسعارك، أسلوبك، سياستك. مش قالب واحد لكل الناس. | مصمم على مقاس البيزنس بتاعك |
| 86–94 | Video call between the owner and an OT1 team member setting things up, smiles (VIP feeling) | وفريقنا بيقعد معاك ويظبطهولك بنفسه… كأن عندك فريق مبيعات خاص، من غير المرتبات. | إعداد شخصي معاك · VIP |
| 94–100 | Morning, the same owner wakes up smiling, phone: "١٢ أوردر جديد" + CTA | ابدأ مجاناً النهارده، من غير فيزا. OT1-Pro… فريق مبيعاتك اللي مبينامش. | ابدأ مجاناً · ot1-pro.com |

## VIDEO B — Niche: online stores (fashion/beauty on Instagram + WhatsApp) (~90s) — «سارة والـ٢٠٠ رسالة»

Main character: **سارة**, 27, owns an online clothing brand in Cairo. She must look the same in every shot (use one image reference). Female voiceover. Speak to the viewer in the feminine form.

| Time | Visual | Voiceover |
|---|---|---|
| 0–5 | سارة filming a reel of a dress, then the phone explodes with notifications | سارة عندها براند لبس… نزّلت ريل، الريل ضرب، و٢٠٠ رسالة في ساعة. |
| 5–10 | Quick bubbles: "المقاس كام؟" "الشحن لإسكندرية بكام؟" "فيه لون تاني؟" | «المقاس كام؟» «الشحن بكام؟» «فيه لون تاني؟» |
| 10–18 | She's tired, replying alone at 1am, messages piling up | لوحدها، ردّت على ٣٠… والباقي؟ راحوا. مش عشان المنتج وحش… عشان محدش رد. |
| 18–28 | She turns on OT1-Pro; replies go out in seconds with the price and shipping zones | دلوقتي OT1-Pro بيرد على كل رسالة في ثواني، من الكتالوج والأسعار ومناطق الشحن بتاعتها… وبنفس أسلوبها، حتى الإيموجي 😉 |
| 28–40 | Customer: "غالي شوية" → smart reply. Customer: "هفكر" → follow-up after a day | العميلة تقول «غالي شوية»؟ بيعرف يرد. تقول «هفكر»؟ بيتابع معاها بعد يوم، وبعد ٣ أيام، من غير ما يزن. |
| 40–50 | "عايزة أطلب" → AI collects name, address, and size → a new row in the Sheet | واللي تكتب «عايزة أطلب»، بياخد الاسم والعنوان والمقاس، يأكد، والأوردر ينزل في الشيت على طول. |
| 50–60 | Morning, سارة with coffee: list of orders ready to ship + "عملاء سخنين" list (label: مشهد تمثيلي) | وسارة الصبح تفتح التليفون: الأوردرات جاهزة للشحن، ولستة بالعميلات الجادين اللي محتاجين لمسة منها هي بس. |
| 60–70 | Dashboard: "الفستان الأسود الأكثر طلباً" · "سبب التردد: مصاريف الشحن" → she posts "شحن مجاني" | وعرفت إن الفستان الأسود هو اللي الكل بيسأل عليه… وإن أكتر سبب للتردد هو الشحن. عملت عرض شحن مجاني… والمبيعات زادت. |
| 70–82 | سارة packing orders, happy, her small team | مش محتاجة تعيّني موديريتورز. مش محتاجة تفضلي صاحية. OT1-Pro بيبقى فريق مبيعاتك… وإحنا بنظبطه معاكي خطوة بخطوة. |
| 82–90 | CTA card | جرّبيه مجاناً النهارده، من غير فيزا. الرابط تحت. |

**Next niches to test after this one:** real estate, clinics, restaurants/food. Swap only the character and the scenario.

---

## CUT-DOWNS (from each video)

- **30s:** hook (0–10) + sales + Sheet + CTA.
- **15s:** hook + "انت بس تشحن" + CTA.
- **6s bumper:** "١٧ رسالة بدون رد؟ OT1-Pro بيرد بدالك ٢٤ ساعة."
- Make 3 different hooks for Video A to A/B test: (1) 2am notifications, (2) "فريق موديريتورز بـ ٠ جنيه", (3) "العميل اللي مردتش عليه… اشترى من المنافس".

## CAPTIONS (required)

- An `.srt` file in Arabic for every version, matched exactly to the voice, max 2 lines and ~35 characters per line.
- Also burn the captions into the video (most people watch on mute): bold white text with a dark outline, centered in the lower third, right-to-left, Cairo or Tajawal font.
- An English `.srt` as an option for Gulf expats.

## META AD COPY (write 3 versions per video)

**Primary text (A, version 1):**
> الساعة ٢ بالليل والعميل بيسأل «بكام؟»… ومحدش بيرد. 😴
> OT1-Pro مساعد مبيعات بالذكاء الاصطناعي بيرد على واتساب وإنستجرام وماسنجر والشات اللي على موقعك، ٢٤ ساعة، وبلهجة عميلك:
> 💰 يرد على الأسعار ويتعامل مع الاعتراضات ويقفل الأوردر
> 🎯 يقولك مين العميل الجاد
> 💬 يرد على التعليقات ويكمل في الخاص
> 📊 يقولك ليه العميل مشتراش
> 📋 والأوردرات تنزل في الشيت بتاعك… انت بس تشحن.
> ابدأ مجاناً، من غير فيزا 👇

**Headlines:** «فريق مبيعاتك اللي مبينامش» · «من غير ما تعيّن موديريتورز» · «ابدأ مجاناً — من غير فيزا»
**Description:** «من ٨$ في الشهر · إعداد شخصي معاك»
**Button:** Sign Up → `ot1-pro.com/register?utm_source=meta&utm_campaign=video_a`

Write versions 2 and 3 in the same Egyptian dialect, plus one Gulf-dialect version for the KSA/UAE ad sets.

## DELIVERABLES (checklist)

- [ ] `OT1_A_general_9x16_100s.mp4` + 1:1 + 16:9
- [ ] `OT1_B_fashion_9x16_90s.mp4` + 1:1
- [ ] Cut-downs: 30s / 15s / 6s for each video + 3 hooks for A
- [ ] `.srt` files in Arabic (and English) for every version
- [ ] Thumbnails / cover frames
- [ ] Ad copy document (3 primary texts + 5 headlines per video, Egyptian + Gulf)
- [ ] Separate audio file (voice + music) for later edits

## QUALITY BAR

- Hook in the first 2 seconds, no logo at the start.
- Natural Egyptian voice. If it sounds like a robot or a news anchor, regenerate it.
- No fake claims (see the "Do NOT claim" list above).
- Clear CTA at the end of every version.
