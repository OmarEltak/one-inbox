<?php
/**
 * Professional-tone polish pass on ar.json — targeted rewrite of the ~80 most
 * visible marketing strings from casual Egyptian dialect to professional
 * MSA-flavored Arabic.
 *
 * Kept dialectal (on purpose): testimonial quotes (they are real Egyptian
 * shopkeeper voices — dialect is authentic there) and the founder note
 * pull-quote.
 */

$arFile = __DIR__ . '/../lang/ar.json';
$ar = json_decode(file_get_contents($arFile), true);

$fixes = [
    // ─── Section titles + eyebrows ───────────────────────────
    'The problem' => 'المشكلة',
    'The AI' => 'الذكاء الاصطناعي',
    'Every channel' => 'كل قناة',
    'Everywhere.' => 'في كل مكان.',
    'A closer.' => 'يُغلق الصفقات.',
    'Not a chatbot.' => 'ليس شات بوت.',
    'One inbox for every message your business gets.' => 'صندوق وارد واحد لكل رسالة يتلقاها نشاطك التجاري.',
    'Free.' => 'مجاناً.',
    'Real answers.' => 'إجابات حقيقية.',
    'Real chats.' => 'محادثات حقيقية.',
    'Real closes.' => 'صفقات مُغلقة حقيقية.',
    'Real ones.' => 'حقيقيون.',
    'Real shops.' => 'متاجر حقيقية.',
    'A promise' => 'وعد',
    'go live.' => 'يعمل بشكل حقيقي.',
    'in your voice.' => 'بأسلوبك.',
    // ─── How it works cards ──────────────────────────────────
    'Connect a channel' => 'اربط قناة',
    'Answer three questions' => 'أجب عن ثلاثة أسئلة',
    'Test it with a fake customer' => 'جرّبه مع عميل وهمي',
    'Ninety seconds to' => 'تسعون ثانية حتى',
    'Chat with your own AI before any real customer sees it. Tweak the tone. Then flip it live.' => 'تحدث مع الذكاء الاصطناعي الخاص بك قبل أن يراه أي عميل حقيقي. عدّل النبرة. ثم قم بتشغيله.',
    'What do you sell?' => 'ما الذي تبيعه؟',
    'Facebook, Instagram, WhatsApp, Telegram. Whichever one your customers actually use. Add more later.' => 'Facebook وInstagram وWhatsApp وTelegram. أياً كانت المنصة التي يستخدمها عملاؤك فعلاً. أضف المزيد لاحقاً.',
    // ─── AI feature bullets ──────────────────────────────────
    'Answers in your voice' => 'يرد بأسلوبك',
    'Trained on how you talk to customers, not a generic template' => 'مُدرَّب على أسلوبك في التعامل مع العملاء، وليس قالباً جاهزاً',
    'Escalates only when needed' => 'يُحوّل للبشر فقط عند الحاجة',
    'You get pinged for the 5% that actually need a human' => 'يصلك تنبيه للـ5% التي تحتاج فعلاً إلى تدخل بشري',
    'Learns from your corrections' => 'يتعلم من تصحيحاتك',
    'Every edit you make trains it further' => 'كل تعديل تقوم به يُدرّبه أكثر',
    'Handles Arabic + English' => 'يتعامل مع العربية والإنجليزية',
    'Code-switch mid-sentence, we handle it' => 'التبديل بين اللغتين في نفس الجملة — نتعامل مع الأمر',
    // ─── Every-channel bullets ───────────────────────────────
    'Zero-friction connection via official OAuth on every platform' => 'اتصال دون تعقيد عبر OAuth الرسمي في كل منصة',
    'Multi-page and multi-account, one team at the seat price' => 'صفحات وحسابات متعددة، فريق واحد بسعر المقعد',
    'Real-time sync, messages appear in less than a second' => 'مزامنة فورية، الرسائل تظهر في أقل من ثانية',
    // ─── Before/After stat labels ────────────────────────────
    'Same day, all messages' => 'في نفس اليوم، جميع الرسائل',
    'Every one' => 'كل واحدة',
    'answered' => 'مُجاب عنها',
    'Median reply time drops from' => 'متوسط وقت الرد ينخفض من',
    'Message-to-sale conversion climbs from' => 'نسبة تحويل الرسالة إلى بيع ترتفع من',
    'less time spent switching between messaging apps' => 'وقت أقل في التنقل بين تطبيقات المراسلة',
    'typical AI reply, day or night, in your business voice' => 'رد ذكاء اصطناعي نموذجي، ليلاً ونهاراً، بأسلوب نشاطك التجاري',
    'of messages become paying customers, up from ~4%' => 'من الرسائل تتحول إلى عملاء دافعين، ارتفاعاً من نحو 4%',
    'extra revenue per week for the average shop by week 3' => 'إيراد إضافي أسبوعياً للمحل المتوسط بحلول الأسبوع الثالث',
    'You' => 'أنت',
    'you' => 'أنت',
    'your' => 'الخاص بك',
    'Before OT1-Pro' => 'قبل OT1-Pro',
    'After OT1-Pro' => 'بعد OT1-Pro',
    'Reply time' => 'وقت الرد',
    '3 seconds' => '3 ثوانٍ',
    '7 hours' => '7 ساعات',
    'seconds' => 'ثانية',
    // ─── Composer + demo strings ─────────────────────────────
    'Try it: type a customer message…' => 'جرّبه: اكتب رسالة عميل…',
    'AI Autopilot' => 'الذكاء الاصطناعي نشط',
    'AI · replying now' => 'الذكاء الاصطناعي · يرد الآن',
    'Active now' => 'متصل الآن',
    'Auto-sent' => 'مُرسَل تلقائياً',
    'Connected · 3 seconds ago' => 'مُتصل · قبل 3 ثوانٍ',
    'All conversations · 3 unread' => 'جميع المحادثات · 3 غير مقروءة',
    '3.1s reply · in your voice' => 'رد في 3.1 ثانية · بأسلوبك',
    'Do you ship to Dubai?' => 'هل تشحنون إلى دبي؟',
    "Yes — 3 days, £8 shipping. Which bag caught your eye?" => 'نعم — 3 أيام، الشحن 8 جنيه إسترليني. أي شنطة أعجبتك؟',
    'Handmade leather bags shipped to UAE and Saudi within 3 days' => 'شنط جلد يدوية تُشحن للإمارات والسعودية خلال 3 أيام',
    // ─── Long narratives ─────────────────────────────────────
    "A customer messages your Instagram at 9pm. You see it at 9:47am the next day. By then they've already bought from three other shops. Multiply that by every channel, every hour." => 'عميل يراسلك على Instagram الساعة 9 مساءً. تراه في الساعة 9:47 صباح اليوم التالي. في هذا الوقت يكون قد اشترى بالفعل من ثلاثة متاجر أخرى. اضرب ذلك في كل قناة، وكل ساعة.',
    "You're not losing sales because your product is bad." => 'أنت لا تخسر مبيعات لأن منتجك سيء.',
    "You're losing them at reply #2." => 'أنت تخسرها في الرد رقم 2.',
    "Ninety seconds from now, your AI can be replying to customers in your voice. Free to try. Pay only when it's working." => 'بعد تسعين ثانية من الآن، يمكن للذكاء الاصطناعي الخاص بك أن يرد على عملائك بأسلوبك. جرّبه مجاناً. ادفع فقط عندما يعمل.',
    "What you sell. What customers ask most. Your voice. That's it. We build your AI's brain from there." => 'ما تبيعه. ما يسأل عنه العملاء أكثر. أسلوبك. هذا كل شيء. نبني عقل الذكاء الاصطناعي الخاص بك من هنا.',
    "Your AI reads the customer, checks your catalog, knows your shipping rules, and writes a reply in your business voice. If it doesn't know something, it asks you. If the customer's ready to buy, it sends the checkout link." => 'الذكاء الاصطناعي الخاص بك يقرأ العميل، ويراجع كتالوجك، ويعرف قواعد الشحن، ويكتب رداً بأسلوب نشاطك التجاري. إذا لم يعرف شيئاً، يسألك. إذا كان العميل مستعداً للشراء، يُرسل رابط الدفع.',
    "No. That's the whole point. The wizard asks you three questions on signup — what you sell, what customers ask most, and how you talk. We build the system prompt from your answers, then let you chat with your own AI before any real customer sees it. Tweak until it sounds like you." => 'لا. هذا هو الهدف كله. المعالج يسألك ثلاثة أسئلة عند التسجيل — ما تبيعه، وما يسأل عنه العملاء أكثر، وكيف تتحدث. نبني برومبت النظام من إجاباتك، ثم نتيح لك التحدث مع الذكاء الاصطناعي الخاص بك قبل أن يراه أي عميل حقيقي. عدّل حتى يبدو مثلك.',
    "You get pinged. Every reply the AI isn't sure about is escalated to your inbox and paused until you confirm. Every correction you make trains it further, so the escalation rate drops every week." => 'يصلك تنبيه. كل رد لا يكون الذكاء الاصطناعي متأكداً منه يُحوَّل إلى صندوقك ويتوقف حتى تُؤكّد. كل تصحيح تقوم به يُدرّبه أكثر، فمعدل التحويل ينخفض كل أسبوع.',
    "Yes. Data is stored in Frankfurt, encrypted at rest, and never used to train models across tenants. Each business's AI is trained only on your own message history and your own answers. GDPR-compliant, SOC 2 in progress." => 'نعم. البيانات مُخزَّنة في فرانكفورت، مُشفَّرة، ولا تُستخدم أبداً لتدريب نماذج مشتركة بين العملاء. الذكاء الاصطناعي لكل نشاط تجاري يتدرب فقط على سجل رسائلك وإجاباتك. متوافق مع GDPR، وSOC 2 قيد الإنجاز.',
    // ─── FAQ questions ───────────────────────────────────────
    'Does the AI sound like a robot?' => 'هل الذكاء الاصطناعي يبدو آلياً؟',
    'What if the AI gets it wrong?' => 'ماذا لو أخطأ الذكاء الاصطناعي؟',
    'Which platforms exactly?' => 'ما المنصات بالتحديد؟',
    'How is this different from Manychat or Respond.io?' => 'ما الفرق بينه وبين Manychat أو Respond.io؟',
    'Is my customer data safe?' => 'هل بيانات عملائي آمنة؟',
    "Manychat is a marketing tool — it blasts sequences. Respond.io is enterprise-priced and takes weeks to configure. OT1-Pro is built for a founder with a phone full of unread DMs who needs the AI to actually close sales by tomorrow, not next quarter. Signup-to-live time is 90 seconds." => 'Manychat أداة تسويقية — تُرسل سلاسل رسائل جاهزة. Respond.io سعرها للشركات الكبيرة وتحتاج أسابيع للإعداد. OT1-Pro مُصمَّم للمؤسس الذي هاتفه مليء برسائل غير مقروءة، ويحتاج الذكاء الاصطناعي لإغلاق مبيعات غداً، وليس الربع القادم. من التسجيل إلى التشغيل 90 ثانية.',
    "Facebook Messenger (official Business API), Instagram DMs (official), WhatsApp Business (both official API and QR-code Personal for small shops), Telegram bots, embeddable web chat widget, and email. New platforms added as customer demand justifies — LINE and Discord are next." => 'Facebook Messenger (الـAPI الرسمي للأعمال)، رسائل Instagram (رسمي)، WhatsApp Business (الـAPI الرسمي + طريقة QR للمحلات الصغيرة)، بوتات Telegram، ودجت شات للموقع، والبريد الإلكتروني. نُضيف منصات جديدة حسب طلب العملاء — LINE وDiscord قادمان قريباً.',
    "Facebook Messenger. Instagram DMs. WhatsApp Business. Telegram bot. Web chat widget. Email. All arrive in the same clean feed. Reply from one screen, keep context, never lose a conversation to another tab." => 'Facebook Messenger. رسائل Instagram. WhatsApp Business. بوت Telegram. ودجت الويب شات. البريد الإلكتروني. جميعها تصل في نفس التغذية النظيفة. رد من شاشة واحدة، احتفظ بالسياق، ولا تفقد أبداً محادثة في تاب آخر.',
    // ─── Numbers disclaimer ──────────────────────────────────
    "Numbers from ot1-pro.com customer telemetry (Aug-Sep 2026, n=42 active accounts). Individual results depend on message volume, catalog size, and how weird your customers' questions get." => 'أرقام من بيانات عملاء ot1-pro.com (أغسطس-سبتمبر 2026، عدد 42 حساب نشط). النتائج الفردية تعتمد على حجم الرسائل وحجم الكتالوج وطبيعة أسئلة عملائك.',
    // ─── Founder note ────────────────────────────────────────
    'I built OT1-Pro because I lost' => 'بنيت OT1-Pro لأنني خسرت',
    'of my own sales to unanswered DMs last year. So this is the tool I needed.' => 'من مبيعاتي الشخصية بسبب رسائل لم أُجب عنها العام الماضي. لذا فهذه هي الأداة التي احتجتُها.',
    'Try any plan free.' => 'جرّب أي باقة مجاناً.',
    'to your notifications.' => 'إلى تنبيهاتك.',
    // ─── Testimonial industry labels (dialect kept — real shops) ─
    'Vintage clothing reseller' => 'بيع ملابس فينتاج',
    'Wholesale spice import' => 'استيراد بهارات بالجملة',
    // ─── Trust bar + Plugs into ──────────────────────────────
    'Plugs into the tools you already use' => 'يتكامل مع الأدوات التي تستخدمها بالفعل',
    'Hover any card' => 'مرّر الفأرة على أي كارت',
    'to see the actual conversation that led to a sale.' => 'لرؤية المحادثة الحقيقية التي أدّت إلى بيع.',
    // ─── Extra bits ──────────────────────────────────────────
    'Advanced routing' => 'توجيه متقدم للرسائل',
    'Custom AI voice tuning' => 'ضبط مخصص لنبرة الذكاء الاصطناعي',
    'Meet-Your-AI wizard' => 'معالج «تعرّف على الذكاء الاصطناعي الخاص بك»',
    'Repeat customer · 3 orders' => 'عميل متكرر · 3 طلبات',
    '→ Closed 3,400 AED · zero human touch' => '← أُغلقت بـ3,400 درهم · دون أي تدخل بشري',
    '→ Closed £48 · 4 min from first message' => '← أُغلقت بـ48 جنيه إسترليني · 4 دقائق من أول رسالة',
    '→ Closed £55 · reserved before 3 other buyers asked' => '← أُغلقت بـ55 جنيه إسترليني · محجوزة قبل أن يسأل 3 مشترين آخرين',
];

foreach ($fixes as $k => $v) {
    $ar[$k] = $v;
}
ksort($ar);
file_put_contents($arFile, json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
echo count($fixes) . " keys polished to professional Arabic.\n";
