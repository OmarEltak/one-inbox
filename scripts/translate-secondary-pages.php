<?php
/**
 * Professional MSA Arabic translations for the secondary marketing pages
 * that browser-Claude migrated but never localised: vs/wati (73 keys) +
 * all small-gap pages (~33 keys). Bigger pages (ecommerce 141,
 * dropshipping 69, aisensy 57, tidio 37, features 26, find-your-fit 26)
 * are deferred to a follow-up pass.
 *
 * Style guide (matches polish-ar-professional.php):
 * - MSA register, not Egyptian dialect
 * - Brand names in English (OT1-Pro, WATI, Manychat, Trengo, Meta, Facebook,
 *   Instagram, WhatsApp, Telegram, Salla, Zid, Shopify, WooCommerce, Anthropic,
 *   Cloud API, Ramadan)
 * - Currency: "8 دولاراً شهرياً" not "$8/month"; keep symbols only inside
 *   in-text references where number formatting matters
 */

$arFile = __DIR__ . '/../lang/ar.json';
$ar = json_decode(file_get_contents($arFile), true);

$fixes = [
    // ─── vs/wati (73 keys) ────────────────────────────────────
    '$8 / month (Basic)' => '8 دولاراً شهرياً (الأساسية)',
    '1. Export your WATI contact list' => '1. صدّر قائمة جهات اتصالك من WATI',
    '1–3 hours' => 'من ساعة إلى 3 ساعات',
    '2. Reconnect the WhatsApp Business Account' => '2. أعِد ربط حساب WhatsApp Business',
    '3. Rebuild your top 5 message templates' => '3. أعِد بناء أفضل 5 قوالب رسائل لديك',
    '4. Add Instagram + Messenger + Telegram' => '4. أضف Instagram + Messenger + Telegram',
    '5. Run WATI and OT1-Pro in parallel for 2 weeks' => '5. شغّل WATI وOT1-Pro بالتوازي لمدة أسبوعين',
    '7-day trial only' => 'تجربة 7 أيام فقط',
    'All 4 channels, Arabic-first AI, per-seat pricing. See it working with your real messages in 30 minutes.' => 'جميع القنوات الأربع، ذكاء اصطناعي عربي أولاً، تسعير حسب المقعد. شاهده يعمل على رسائلك الحقيقية خلال 30 دقيقة.',
    'Arabic AI that understands dialect' => 'ذكاء اصطناعي عربي يفهم اللهجات',
    "At the entry tier, yes — OT1-Pro starts at \$8/month (Basic) and \$29/month (Starter with 3 pages and 500 AI responses) vs WATI's ~\$49/month base. At higher tiers with heavy broadcast volume, WATI can be competitive on raw WhatsApp cost pass-through. OT1-Pro pulls ahead on total-cost-of-ownership when you factor in Instagram, Messenger, and Telegram — each of which would need a separate WATI-equivalent tool." => 'في الباقة المبتدئة، نعم — OT1-Pro يبدأ من 8 دولارات شهرياً (الأساسية) و29 دولاراً شهرياً (Starter مع 3 صفحات و500 رد ذكاء اصطناعي) مقابل الباقة الأساسية لـWATI بحوالي 49 دولاراً شهرياً. في الباقات الأعلى ذات حجم البث الضخم، قد يكون WATI منافساً على تكلفة WhatsApp الخام. لكن OT1-Pro يتفوق في التكلفة الإجمالية عند احتساب Instagram وMessenger وTelegram — كل منها يحتاج أداة منفصلة مكافئة لـWATI.',
    'Can I keep my existing WhatsApp Business number?' => 'هل يمكنني الاحتفاظ برقم WhatsApp Business الحالي؟',
    'Capability' => 'القدرة',
    'Contacts → Export → CSV. Import into OT1-Pro from Settings → Contacts → Import. Usually done in under 5 minutes.' => 'جهات الاتصال ← تصدير ← CSV. استورِد إلى OT1-Pro من الإعدادات ← جهات الاتصال ← استيراد. عادةً يتم في أقل من 5 دقائق.',
    'Dialect-aware' => 'مُدرك للهجات',
    'Do not try to migrate 40 templates on day one — most teams only actively use 4–6. Rebuild those, watch adoption for a week, then port the rest.' => 'لا تحاول نقل 40 قالباً في اليوم الأول — معظم الفرق تستخدم فعلياً 4 إلى 6 فقط. أعِد بناء تلك، وراقب الاستخدام لأسبوع، ثم انقل الباقي.',
    "Does OT1-Pro's AI understand Egyptian Arabic dialect?" => 'هل يفهم الذكاء الاصطناعي في OT1-Pro اللهجة المصرية؟',
    'Email (IMAP/SMTP)' => 'البريد الإلكتروني (IMAP/SMTP)',
    'Entry paid tier' => 'الباقة المدفوعة الأساسية',
    'Everyone else — every MENA storefront doing $5k–$500k/month across 2+ channels — should keep reading.' => 'كل من عداهم — كل متجر في منطقة الشرق الأوسط وشمال أفريقيا يحقق من 5,000 إلى 500,000 دولار شهرياً عبر قناتين أو أكثر — عليه أن يواصل القراءة.',
    'For a typical Egyptian or GCC storefront: 30 minutes for basic setup, an evening for template rebuild, and 2 weeks of parallel running with WATI before you cancel. Most teams are fully operational on OT1-Pro within 3 days.' => 'لمتجر مصري أو خليجي نموذجي: 30 دقيقة للإعداد الأساسي، وأمسية لإعادة بناء القوالب، وأسبوعان من التشغيل المتوازي مع WATI قبل الإلغاء. معظم الفرق تعمل بشكل كامل على OT1-Pro خلال 3 أيام.',
    'Founder on WhatsApp' => 'المؤسس على WhatsApp',
    'Four areas where the difference is measurable in your monthly reports.' => 'أربعة مجالات يكون فيها الفرق قابلاً للقياس في تقاريرك الشهرية.',
    'Free plan available · Founder-accessible on WhatsApp' => 'باقة مجانية متاحة · تواصل مباشر مع المؤسس عبر WhatsApp',
    'Honesty first. If any of these describe you, stay on WATI — it works well for the WhatsApp-only use case:' => 'الصدق أولاً. إذا كان أي مما يلي يصفك، ابقَ على WATI — فهو يعمل بشكل جيد لحالة الاستخدام الحصرية لـWhatsApp:',
    'How long does migration from WATI take?' => 'كم يستغرق الانتقال من WATI؟',
    'IST timezone' => 'التوقيت الهندي (IST)',
    "If WATI hosts your number on 360dialog, you can migrate the number to Meta Cloud API (Meta support ticket, 2–3 days) or connect fresh through OT1-Pro's guided onboarding." => 'إذا كان WATI يستضيف رقمك على 360dialog، يمكنك نقل الرقم إلى Meta Cloud API (تذكرة دعم من Meta، من يومين إلى 3 أيام) أو الاتصال من جديد عبر الإعداد الموجّه في OT1-Pro.',
    'Is OT1-Pro cheaper than WATI?' => 'هل OT1-Pro أرخص من WATI؟',
    'Looking for a WATI alternative? OT1-Pro adds Instagram, Messenger, and Telegram to your WhatsApp inbox with native Egyptian Arabic AI and per-seat pricing starting at $8/mo.' => 'تبحث عن بديل لـWATI؟ OT1-Pro يضيف Instagram وMessenger وTelegram إلى صندوق WhatsApp الخاص بك، مع ذكاء اصطناعي عربي مصري أصيل، وتسعير حسب المقعد يبدأ من 8 دولارات شهرياً.',
    'MENA-hours support' => 'دعم بتوقيت المنطقة العربية',
    'MENA-hours support from the founder' => 'دعم من المؤسس بتوقيت المنطقة العربية',
    'Mature' => 'ناضج',
    'Message the founder directly on WhatsApp at +20 102 636 1218. That is not a marketing line — that is how we support MENA customers in practice, and it is the reason our churn is low.' => 'راسل المؤسس مباشرةً على WhatsApp على الرقم 1218 636 102 20+. هذا ليس خطاً تسويقياً — بل هو الطريقة التي ندعم بها عملاء المنطقة العربية عملياً، وهو السبب في انخفاض معدل انسحابنا.',
    'Migrating from WATI to OT1-Pro' => 'الانتقال من WATI إلى OT1-Pro',
    'Most stores are fully operational on OT1-Pro within 3 days. Here is the practical checklist:' => 'معظم المتاجر تعمل بشكل كامل على OT1-Pro خلال 3 أيام. إليك قائمة العمل التطبيقية:',
    'Multi-channel from day one' => 'متعدد القنوات من اليوم الأول',
    'Native (Cloud API)' => 'أصيل (Cloud API)',
    'Native Egyptian Arabic AI' => 'ذكاء اصطناعي عربي مصري أصيل',
    'Native, mature' => 'أصيل وناضج',
    'No credit card required · Free plan available · Talk to founder on WhatsApp' => 'لا حاجة لبطاقة ائتمان · باقة مجانية متاحة · تحدث مع المؤسس عبر WhatsApp',
    'Nothing drops during the switch. Cancel WATI at the end of your billing cycle.' => 'لا شيء يتوقف أثناء الانتقال. ألغِ WATI في نهاية دورة الفوترة.',
    'OT1-Pro connects with Salla and Zid via webhook — new-order notifications, abandoned-cart recovery, and shipment updates can all fire into WhatsApp/Instagram/Messenger automatically. Setup takes 15 minutes with our step-by-step guide.' => 'OT1-Pro يتصل مع Salla وZid عبر webhook — إشعارات الطلبات الجديدة، واستعادة السلات المتروكة، وتحديثات الشحن يمكن أن تُرسَل جميعها تلقائياً إلى WhatsApp/Instagram/Messenger. الإعداد يستغرق 15 دقيقة مع دليلنا خطوة بخطوة.',
    'OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian, Gulf, and Levantine Arabic natively — including code-switching ("عايز الأبيض medium please") and casual dialect. WATI\'s automation was built with English/Hindi first, so Arabic replies feel translated.' => 'OT1-Pro يوجّه ردود الذكاء الاصطناعي عبر Anthropic Claude، الذي يتعامل بشكل أصيل مع اللهجات المصرية والخليجية والشامية — بما في ذلك التبديل بين اللغتين ("عايز الأبيض medium please") واللهجة العامية. أما WATI فقد بُنيت أتمتته بالإنجليزية/الهندية أولاً، فتبدو الردود العربية مترجمة.',
    'OT1-Pro vs WATI' => 'OT1-Pro مقابل WATI',
    'OT1-Pro vs WATI — Multi-Channel WhatsApp Alternative for MENA | OT1-Pro' => 'OT1-Pro مقابل WATI — بديل WhatsApp متعدد القنوات للمنطقة العربية | OT1-Pro',
    'Payment in EGP (Paymob)' => 'الدفع بالجنيه المصري (Paymob)',
    'Per-seat' => 'حسب المقعد',
    'Per-seat + per-conversation' => 'حسب المقعد + حسب المحادثة',
    "Per-seat pricing that doesn't punish growth" => 'تسعير حسب المقعد لا يعاقب النمو',
    'Permanent' => 'دائم',
    'Questions about switching from WATI' => 'أسئلة عن الانتقال من WATI',
    'Salla / Zid (MENA)' => 'Salla / Zid (المنطقة العربية)',
    'Shopify integration' => 'تكامل Shopify',
    'The whole reason you switched. Most stores see 30–60% more inbound messages appear in the inbox within 48 hours — because they were previously missing them entirely.' => 'السبب الكامل لانتقالك. معظم المتاجر ترى زيادة تتراوح بين 30 و60% في الرسائل الواردة الظاهرة في الصندوق خلال 48 ساعة — لأنها كانت مفقودة تماماً من قبل.',
    'Time-to-first-message' => 'الوقت حتى أول رسالة',
    'Under 30 min' => 'أقل من 30 دقيقة',
    'WATI charges per-seat + per-conversation + Meta pass-through. As your store grows and broadcast volume climbs, the bill compounds. OT1-Pro is per-seat only, from $8/month at the Basic tier. Your bill scales with your team, not with your success.' => 'WATI يحتسب حسب المقعد + حسب المحادثة + رسوم Meta المُمرَّرة. كلما نما متجرك وارتفع حجم البث، تضاعفت الفاتورة. OT1-Pro يحتسب حسب المقعد فقط، من 8 دولارات شهرياً في الباقة الأساسية. فاتورتك تنمو مع فريقك، لا مع نجاحك.',
    "WATI does WhatsApp well. But if you sell on Instagram, Messenger, and Telegram too — or you need Arabic-first AI and per-seat pricing that doesn't punish growth — OT1-Pro is the WATI alternative built for MENA storefronts." => 'WATI يقوم بعمل جيد على WhatsApp. لكن إذا كنت تبيع على Instagram وMessenger وTelegram أيضاً — أو تحتاج إلى ذكاء اصطناعي عربي أولاً وتسعير حسب المقعد لا يعاقب النمو — فإن OT1-Pro هو بديل WATI المصمم لمتاجر المنطقة العربية.',
    'WATI is WhatsApp-only. In MENA the split between WhatsApp and Instagram DM is roughly 55/45 for D2C brands. OT1-Pro handles WhatsApp, Instagram, Messenger, Telegram, and email in a single inbox — so your team stops missing the half of leads that arrive on IG.' => 'WATI يقتصر على WhatsApp. في المنطقة العربية، توزيع WhatsApp وInstagram DM هو تقريباً 55/45 للعلامات التجارية المباشرة للمستهلك. OT1-Pro يتعامل مع WhatsApp وInstagram وMessenger وTelegram والبريد الإلكتروني في صندوق واحد — فيتوقف فريقك عن فقدان نصف العملاء المحتملين الذين يصلون عبر Instagram.',
    'Weak on dialect' => 'ضعيف في اللهجات',
    'What about Salla or Zid integration?' => 'ماذا عن تكامل Salla أو Zid؟',
    'What if I run into issues at 11pm during a big campaign?' => 'ماذا لو واجهت مشكلات الساعة 11 مساءً أثناء حملة كبيرة؟',
    'WhatsApp Business API' => 'WhatsApp Business API',
    'When WATI is actually the right choice' => 'متى يكون WATI هو الخيار الصحيح فعلاً',
    "When your integration breaks at 10pm during a Ramadan push, WATI's ticket queue in a different timezone is not acceptable. OT1-Pro's founder answers directly on WhatsApp at +20 102 636 1218 — that's how we support MENA customers, not a marketing gimmick." => 'عندما يتعطل التكامل الساعة 10 مساءً أثناء دفعة رمضانية، فإن طابور تذاكر WATI في منطقة زمنية مختلفة غير مقبول. مؤسس OT1-Pro يرد مباشرةً على WhatsApp على الرقم 1218 636 102 20+ — هكذا ندعم عملاء المنطقة العربية، وليست حيلة تسويقية.',
    'Where OT1-Pro wins for MENA storefronts' => 'أين يتفوق OT1-Pro لمتاجر المنطقة العربية',
    "Yes. OT1-Pro routes AI replies through Anthropic Claude (via our NaraRouter gateway), which handles Egyptian and Gulf Arabic dialect natively — including common misspellings, English/Arabic code-switching, and dialect-specific product terms. You can also fine-tune the AI's tone per-team in Settings → AI Prompt." => 'نعم. OT1-Pro يوجّه ردود الذكاء الاصطناعي عبر Anthropic Claude (من خلال بوابة NaraRouter الخاصة بنا)، التي تتعامل بشكل أصيل مع اللهجات المصرية والخليجية — بما في ذلك الأخطاء الإملائية الشائعة والتبديل بين الإنجليزية والعربية والمصطلحات الخاصة باللهجات. يمكنك أيضاً ضبط نبرة الذكاء الاصطناعي لكل فريق في الإعدادات ← برومبت الذكاء الاصطناعي.',
    'Yes. WhatsApp Business API numbers are portable across BSPs (Business Solution Providers). If WATI hosts your number on 360dialog or another BSP, you can migrate it to Meta Cloud API and connect through OT1-Pro. The process typically takes 2–3 business days.' => 'نعم. أرقام WhatsApp Business API قابلة للنقل بين مزوّدي حلول الأعمال (BSPs). إذا كان WATI يستضيف رقمك على 360dialog أو مزوّد آخر، يمكنك نقله إلى Meta Cloud API والاتصال عبر OT1-Pro. تستغرق العملية عادةً من يومين إلى 3 أيام عمل.',
    'You have a Shopify or WooCommerce store that just needs abandoned-cart WhatsApp recovery and order status notifications.' => 'لديك متجر Shopify أو WooCommerce يحتاج فقط إلى استعادة السلات المتروكة عبر WhatsApp وإشعارات حالة الطلب.',
    'You sell almost entirely on WhatsApp (90%+ of orders come from WA).' => 'أنت تبيع بشكل كامل تقريباً على WhatsApp (أكثر من 90% من الطلبات تأتي من WhatsApp).',
    'You send heavy WhatsApp broadcast campaigns to a large opted-in list.' => 'تُرسل حملات بث WhatsApp ضخمة إلى قائمة اشتراك كبيرة.',
    'Your team is comfortable with WhatsApp Business API concepts — templates, session windows, opt-in tracking.' => 'فريقك مرتاح مع مفاهيم WhatsApp Business API — القوالب، ونوافذ الجلسة، وتتبع الاشتراك.',

    // ─── vs/manychat (5 keys) ─────────────────────────────────
    '$15 / month' => '15 دولاراً شهرياً',
    'AI-human handoff' => 'التحويل بين الذكاء الاصطناعي والبشر',
    'Looking for a ManyChat alternative? OT1-Pro combines WhatsApp, Instagram, Facebook & Telegram in one inbox with AI that qualifies leads and closes deals.' => 'تبحث عن بديل لـManyChat؟ OT1-Pro يجمع WhatsApp وInstagram وFacebook وTelegram في صندوق واحد مع ذكاء اصطناعي يُصنّف العملاء المحتملين ويُغلق الصفقات.',
    'OT1-Pro vs ManyChat — AI Social Inbox Alternative | OT1-Pro' => 'OT1-Pro مقابل ManyChat — بديل صندوق التواصل الاجتماعي بالذكاء الاصطناعي | OT1-Pro',
    'Price (starting from)' => 'السعر (يبدأ من)',

    // ─── vs/trengo (4 keys) ───────────────────────────────────
    'Comparing OT1-Pro vs Trengo? See why growing businesses choose OT1-Pro — AI sales responder, unified social inbox, and lead scoring at a fraction of the price.' => 'تقارن بين OT1-Pro وTrengo؟ اكتشف لماذا تختار الأعمال النامية OT1-Pro — مساعد مبيعات بالذكاء الاصطناعي، صندوق تواصل موحّد، وتقييم العملاء المحتملين بجزء من السعر.',
    'OT1-Pro vs Trengo — Better Alternative for Sales Teams' => 'OT1-Pro مقابل Trengo — بديل أفضل لفرق المبيعات',

    // ─── whatsapp-inbox (4 keys) ──────────────────────────────
    'Deep dives on running WhatsApp Business at scale.' => 'دراسات معمّقة لتشغيل WhatsApp Business على نطاق واسع.',
    'Manage every WhatsApp Business conversation from one unified inbox. AI auto-replies 24/7, scores leads, and hands off hot prospects to your team instantly. Try free.' => 'أدِر كل محادثات WhatsApp Business من صندوق موحّد واحد. الذكاء الاصطناعي يرد تلقائياً على مدار الساعة، ويُقيّم العملاء المحتملين، ويُحوّل العملاء الجاهزين إلى فريقك فوراً. جرّب مجاناً.',
    'Related WhatsApp guides' => 'أدلة WhatsApp ذات الصلة',
    'WhatsApp Business Inbox — Manage Every Message | OT1-Pro' => 'صندوق WhatsApp Business — أدِر كل رسالة | OT1-Pro',

    // ─── instagram-dm (5 keys) ────────────────────────────────
    'Deep dives on Instagram DM automation and lead generation.' => 'دراسات معمّقة عن أتمتة رسائل Instagram وتوليد العملاء المحتملين.',
    'Instagram DM Management Software with AI | OT1-Pro' => 'برنامج إدارة رسائل Instagram بالذكاء الاصطناعي | OT1-Pro',
    'Manage all your Instagram DMs from one shared inbox. AI auto-replies to messages, qualifies leads, scores prospects, and hands off hot buyers to your team. Try free.' => 'أدِر كل رسائل Instagram من صندوق مشترك واحد. الذكاء الاصطناعي يرد تلقائياً على الرسائل، ويُصنّف العملاء المحتملين، ويُقيّم الفرص، ويُحوّل المشترين الجاهزين إلى فريقك. جرّب مجاناً.',
    'Related Instagram guides' => 'أدلة Instagram ذات الصلة',
    "That's your choice. You can configure the AI to identify itself or to respond as your brand. Many businesses configure a brand persona with a name like \"Sara from [Brand]\"." => 'هذا خيارك. يمكنك ضبط الذكاء الاصطناعي ليعرّف عن نفسه أو ليرد باسم علامتك التجارية. كثير من الأعمال تُهيئ شخصية تحمل اسم علامتها مثل «سارة من [العلامة]».',

    // ─── facebook-messenger (4 keys) ──────────────────────────
    'Facebook Messenger Management for Business | OT1-Pro' => 'إدارة Facebook Messenger للأعمال | OT1-Pro',
    'How to run Messenger and unified social inbox at scale.' => 'كيف تُدير Messenger وصندوق تواصل موحّداً على نطاق واسع.',
    'Manage all your Facebook Page messages from one shared inbox. AI auto-replies, qualifies leads, and escalates hot prospects to your team. Works across multiple Pages. Try free.' => 'أدِر كل رسائل صفحة Facebook من صندوق مشترك واحد. الذكاء الاصطناعي يرد تلقائياً، ويُصنّف العملاء المحتملين، ويُحوّل العملاء الجاهزين إلى فريقك. يعمل عبر عدة صفحات. جرّب مجاناً.',
    'Related Facebook & social CX guides' => 'أدلة Facebook وتجربة العملاء الاجتماعية ذات الصلة',

    // ─── telegram-inbox (4 keys) ──────────────────────────────
    'How to scale messaging operations across channels.' => 'كيف تُوسّع عمليات المراسلة عبر القنوات.',
    'Manage all your Telegram business messages from a shared team inbox. AI auto-replies, scores leads, and routes hot prospects to your team automatically. Try free.' => 'أدِر كل رسائل Telegram للأعمال من صندوق فريق مشترك. الذكاء الاصطناعي يرد تلقائياً، ويُقيّم العملاء المحتملين، ويُوجّه العملاء الجاهزين إلى فريقك تلقائياً. جرّب مجاناً.',
    'Related Telegram & messaging guides' => 'أدلة Telegram والمراسلة ذات الصلة',
    'Telegram Business Inbox — Manage Messages at Scale | OT1-Pro' => 'صندوق Telegram للأعمال — أدِر الرسائل على نطاق واسع | OT1-Pro',

    // ─── industries pages (7 keys) ────────────────────────────
    'Frequently Asked Questions' => 'الأسئلة الشائعة',
    'Start Free' => 'ابدأ مجاناً',
    'See All Features' => 'شاهد جميع الميزات',
];

foreach ($fixes as $k => $v) {
    $ar[$k] = $v;
}
ksort($ar);
file_put_contents($arFile, json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
echo count($fixes) . " translations added.\n";
// Note: this file is loaded by PHP; the closing tag was already reached above.
// Append a second run block that layers additional keys in.
