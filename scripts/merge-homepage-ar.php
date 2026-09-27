<?php
/**
 * Merge Arabic translations for the homepage (pages/ai-campaign-manager.blade.php)
 * into lang/ar.json. Preserves existing keys (only fills where missing or
 * where value === key = untranslated).
 *
 * Usage: php scripts/merge-homepage-ar.php
 */

$arFile = __DIR__ . '/../lang/ar.json';
$existing = json_decode(file_get_contents($arFile), true);
if (!is_array($existing)) {
    fwrite(STDERR, "Could not read lang/ar.json\n");
    exit(1);
}

// Founder-voice Arabic (Egyptian/MSA hybrid, natural conversational).
// Names/brands (OT1-Pro, Facebook, Instagram, WhatsApp, Telegram, Manychat, Respond.io, Dubai, Cairo, London) stay in English/Latin form.
$translations = [
    '1 platform, 100 AI replies / month' => 'منصة واحدة، 100 رد ذكاء اصطناعي شهرياً',
    '1 team seat' => 'مقعد واحد للفريق',
    '14 days. No card. Pay by bank transfer only when you' => '14 يوم. من غير كارت. تدفع تحويل بنكي بس لما',
    '3 platforms, 2,500 AI replies / mo' => '3 منصات، 2,500 رد ذكاء اصطناعي شهرياً',
    '3 seconds' => '3 ثواني',
    '3 team seats' => '3 مقاعد للفريق',
    '3.1s reply · in your voice' => 'رد في 3.1 ثانية · بأسلوبك',
    '7 hours' => '7 ساعات',
    'A closer.' => 'بيقفل الصفقة.',
    'A customer messages your Instagram at 9pm. You see it at 9:47am the next day. By then they' => 'عميل بيبعتلك على Instagram الساعة 9 مساءً. إنت بتشوف الرسالة 9:47 صباح تاني يوم. قبلها بكتير',
    'A promise' => 'وعد',
    'AI Autopilot' => 'الذكاء الاصطناعي شغال',
    'AI · replying now' => 'الذكاء الاصطناعي · بيرد دلوقتي',
    'Active now' => 'متصل الآن',
    'Advanced routing' => 'توجيه متقدم للرسائل',
    'After OT1-Pro' => 'بعد OT1-Pro',
    'All conversations · 3 unread' => 'كل المحادثات · 3 غير مقروءة',
    'All platforms, 10,000 AI replies / mo' => 'كل المنصات، 10,000 رد ذكاء اصطناعي شهرياً',
    'Answer three questions' => 'رد على 3 أسئلة',
    'Answers in your voice' => 'يرد بأسلوبك',
    'Auto-sent' => 'مُرسل تلقائياً',
    'Before OT1-Pro' => 'قبل OT1-Pro',
    'Built for founders who close in DMs' => 'مصمم للمؤسسين اللي بيقفلوا الصفقات في الرسائل',
    'Chat with your own AI before any real customer sees it. Tweak the tone. Then flip it live.' => 'دردش مع الذكاء الاصطناعي بتاعك قبل ما أي عميل حقيقي يشوفه. عدّل النبرة. بعدين شغّله.',
    'Code-switch mid-sentence, we handle it' => 'يتكلم عربي وإنجليزي في نفس الجملة، إحنا مسيطرين',
    'Connect a channel' => 'اربط قناة',
    'Connected · 3 seconds ago' => 'متصل · من 3 ثواني',
    'Custom AI + workflows' => 'ذكاء اصطناعي مخصص + سير عمل مخصص',
    'Custom AI voice tuning' => 'ضبط نبرة الذكاء الاصطناعي',
    'Custom volume, on-prem, or a franchise?' => 'حجم مخصص، استضافة داخلية، أو امتياز؟',
    'Do I have to add a credit card?' => 'لازم أضيف كارت ائتمان؟',
    'Do you ship to Dubai?' => 'بتوصلوا لدبي؟',
    'Does the AI sound like a robot?' => 'الذكاء الاصطناعي بيتكلم بشكل آلي؟',
    'Email Omar' => 'ابعت لعمر إيميل',
    'Escalates only when needed' => 'بيحول للبشر بس لما يلزم',
    'Every channel' => 'كل قناة',
    'Every edit you make trains it further' => 'كل تعديل بتعمله بيدربه أكتر',
    'Every one' => 'كل واحدة',
    'Everywhere.' => 'في كل مكان.',
    'Facebook Messenger (official Business API), Instagram DMs (official), WhatsApp Business (both official API and QR-code Personal for small shops), Telegram bots, embeddable web chat widget, and email. New platforms added as customer demand justifies — LINE and Discord are next.' => 'Facebook Messenger (الـAPI الرسمي للأعمال)، Instagram DMs (رسمي)، WhatsApp Business (الـAPI الرسمي + طريقة QR للمحلات الصغيرة)، بوتات Telegram، ودجت شات للموقع، وإيميل. بنضيف منصات جديدة لما يزيد الطلب — LINE وDiscord جايين قريب.',
    'Facebook Messenger. Instagram DMs. WhatsApp Business. Telegram bot. Web chat widget. Email. All arrive in the same clean feed. Reply from one screen, keep context, never lose a conversation to another tab.' => 'Facebook Messenger. رسائل Instagram. WhatsApp Business. بوت Telegram. ودجت الويب شات. الإيميل. كلها بتوصلك في نفس الصندوق النظيف. رد من شاشة واحدة، محتفظ بالسياق، مش هتوَه محادثة في تاب تاني.',
    'Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI in your voice, closing while you sleep.' => 'Facebook وInstagram وWhatsApp وTelegram — كل رسالة عميل في مكان واحد، بيرد عليها ذكاء اصطناعي بأسلوبك، بيقفل الصفقات وإنت نايم.',
    'Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI that sounds like' => 'Facebook وInstagram وWhatsApp وTelegram — كل رسالة عميل في مكان واحد، بيرد عليها ذكاء اصطناعي شبهك',
    'Facebook, Instagram, WhatsApp, Telegram. Whichever one your customers actually use. Add more later.' => 'Facebook وInstagram وWhatsApp وTelegram. اللي عملاءك فعلاً بيستخدموها. زوّد الباقي بعدين.',
    'For growing DMs' => 'للي رسايلهم بتكبر',
    'Founder' => 'المؤسس',
    'Founder email support' => 'دعم مباشر من المؤسس على الإيميل',
    'Free.' => 'مجاناً.',
    'Handles Arabic + English' => 'بيتعامل مع العربي والإنجليزي',
    'Handmade Egyptian leather bags' => 'شنط جلد مصري صناعة يدوية',
    'Hover any card' => 'مرّر الماوس على أي كارت',
    'How is this different from Manychat or Respond.io?' => 'إيه الفرق بينه وبين Manychat أو Respond.io؟',
    'How many messages does your business get per month?' => 'بتيجي لبيزنسك كام رسالة في الشهر؟',
    'I built OT1-Pro because I lost' => 'عملت OT1-Pro لأني خسرت',
    'Is my customer data safe?' => 'بيانات عملائي آمنة؟',
    'Learns from your corrections' => 'بيتعلم من تصحيحاتك',
    'Manychat is a marketing tool — it blasts sequences. Respond.io is enterprise-priced and takes weeks to configure. OT1-Pro is built for a founder with a phone full of unread DMs who needs the AI to actually close sales by tomorrow, not next quarter. Signup-to-live time is 90 seconds.' => 'Manychat أداة تسويق — بتبعت سلاسل رسائل جاهزة. Respond.io سعرها للشركات الكبيرة وبتاخد أسابيع في الإعداد. OT1-Pro مصمم للمؤسس اللي موبايله مليان رسائل مش مقروءة، محتاج الذكاء الاصطناعي يقفل مبيعات بكرة، مش الربع اللي جاي. من الاشتراك للتشغيل 90 ثانية.',
    'Median reply time drops from' => 'متوسط وقت الرد ينزل من',
    'Meet-Your-AI wizard' => 'معالج «تعرّف على الذكاء الاصطناعي بتاعك»',
    'Message-to-sale conversion climbs from' => 'نسبة تحويل الرسالة لبيع بتطلع من',
    'Most shops start here' => 'أغلب المحلات بتبدأ من هنا',
    'Multi-page and multi-account, one team at the seat price' => 'صفحات متعددة وحسابات متعددة، فريق واحد بسعر المقعد',
    'Ninety seconds from now, your AI can be replying to customers in your voice. Free to try. Pay only when it' => 'بعد 90 ثانية من دلوقتي، الذكاء الاصطناعي بتاعك يبقى بيرد على العملاء بأسلوبك. جرّبه مجاناً. ادفع بس لما',
    'Ninety seconds to' => 'تسعين ثانية علشان',
    'No. That' => 'لأ. ده',
    'No. Try any plan free for 14 days. If you decide to keep it, we send bank transfer details. You pay by wire. No auto-renew, no card on file, no surprise charge. If you want to cancel, you literally just don' => 'لأ. جرّب أي باقة مجاناً 14 يوم. لو قررت تكمّل، هنبعتلك تفاصيل التحويل البنكي. بتدفع تحويل. من غير تجديد تلقائي، من غير كارت محفوظ، من غير مفاجآت. لو عايز تلغي، ببساطة',
    'Not a chatbot.' => 'مش شات بوت.',
    'Not before.' => 'قبل كده لأ.',
    "Numbers from ot1-pro.com customer telemetry (Aug-Sep 2026, n=42 active accounts). Individual results depend on message volume, catalog size, and how weird your customers\\" => 'أرقام من بيانات عملاء ot1-pro.com (أغسطس-سبتمبر 2026، عدد 42 حساب نشط). النتائج الفردية بتعتمد على حجم الرسائل وحجم الكتالوج وشخصية عملائك',
    'OT1-Pro — Every message. One closing inbox.' => 'OT1-Pro — كل رسالة. صندوق واحد بيقفل.',
    'One closing' => 'صندوق واحد',
    'One inbox for every message your business gets.' => 'صندوق وارد واحد لكل رسالة بتوصل بيزنسك.',
    'Pay by bank transfer, only when it' => 'تدفع تحويل بنكي، بس لما',
    'Pay in cash, once it' => 'ادفع نقداً، بمجرد ما',
    'Plugs into the tools you already use' => 'بيتربط مع الأدوات اللي بتستخدمها فعلاً',
    'Priority support < 4hr' => 'دعم أولوية أقل من 4 ساعات',
    'Reachable at' => 'تلاقيه على',
    'Real answers.' => 'إجابات حقيقية.',
    'Real chats.' => 'محادثات حقيقية.',
    'Real closes.' => 'صفقات مقفولة حقيقية.',
    'Real ones.' => 'حقيقيين.',
    'Real shops.' => 'محلات حقيقية.',
    'Real-time sync, messages appear in less than a second' => 'مزامنة فورية، الرسائل بتظهر في أقل من ثانية',
    'Recommended:' => 'موصى به:',
    'Repeat customer · 3 orders' => 'عميل متكرر · 3 طلبات',
    'Reply time' => 'وقت الرد',
    'Same day, all messages' => 'نفس اليوم، كل الرسائل',
    'Scale without hiring' => 'كبّر من غير ما توظّف',
    'See how it works' => 'شوف إزاي بيشتغل',
    'See plans' => 'شوف الباقات',
    'Start 14-day trial' => 'ابدأ تجربة 14 يوم',
    'Start free — no card' => 'ابدأ مجاناً — من غير كارت',
    'Stop losing sales' => 'بلاش تخسر مبيعات',
    'Stories' => 'قصص',
    'Test it with a fake customer' => 'جرّبه مع عميل وهمي',
    'The AI' => 'الذكاء الاصطناعي',
    'The problem' => 'المشكلة',
    'Trained on how you talk to customers, not a generic template' => 'متدرب على أسلوبك في التعامل مع العملاء، مش قالب جاهز',
    'Trusted by shops from Cairo to Dubai to London' => 'محلات من القاهرة لدبي للندن بتعتمد علينا',
    'Try any plan' => 'جرّب أي باقة',
    "Try any plan free for 14 days. No card needed. When you decide to keep it, we send bank details and you pay by transfer. That\\" => 'جرّب أي باقة مجاناً 14 يوم. من غير كارت. لما تقرر تكمّل، بنبعتلك تفاصيل الحساب البنكي وتدفع تحويل. ده',
    'Try any plan free.' => 'جرّب أي باقة مجاناً.',
    'Try it: type a customer message…' => 'جرّبها: اكتب رسالة عميل…',
    'Try the flow, no strings' => 'جرّب التجربة، من غير التزام',
    'Unlimited team seats' => 'مقاعد فريق غير محدودة',
    'Vintage clothing reseller' => 'بيع ملابس فينتاج',
    'What do you sell?' => 'إنت بتبيع إيه؟',
    'What if the AI gets it wrong?' => 'لو الذكاء الاصطناعي غلط؟',
    'What you sell. What customers ask most. Your voice. That' => 'إنت بتبيع إيه. العملاء بيسألوا في إيه أكتر. أسلوبك. ده',
    'Which platforms exactly?' => 'إيه المنصات بالظبط؟',
    'Wholesale spice import' => 'استيراد بهارات بالجملة',
    'Yes — 3 days, £8 shipping. Which bag caught your eye?' => 'أيوه — 3 أيام، الشحن 8 جنيه إسترليني. عجبتك أنهي شنطة؟',
    'Yes. Data is stored in Frankfurt, encrypted at rest, and never used to train models across tenants. Each business' => 'أيوه. البيانات متخزنة في فرانكفورت، مشفّرة، وما بتُستخدَمش أبداً في تدريب موديلات مشتركة بين عملاء. كل بيزنس',
    'You' => 'إنت',
    'You get pinged for the 5% that actually need a human' => 'بيوصلك تنبيه للـ5% اللي فعلاً محتاجين إنسان',
    "You get pinged. Every reply the AI isn" => 'بيوصلك تنبيه. كل رد الذكاء الاصطناعي مش',
    "Your AI reads the customer, checks your catalog, knows your shipping rules, and writes a reply in your business voice. If it doesn" => 'الذكاء الاصطناعي بتاعك بيقرا العميل، بيراجع كتالوجك، عارف قواعد الشحن بتاعتك، وبيكتب رد بأسلوب بيزنسك. لو ما',
    'Zero-friction connection via official OAuth on every platform' => 'اتصال بدون تعقيد عن طريق OAuth الرسمي في كل منصة',
    'answered' => 'مجاوَبة',
    'business, closing while you sleep.' => 'بيزنسك، بيقفل الصفقات وإنت نايم.',
    'extra revenue per week for the average shop by week 3' => 'إيراد إضافي في الأسبوع للمحل المتوسط بحلول الأسبوع 3',
    'go live.' => 'يشتغل بشكل حقيقي.',
    'he handles enterprise personally.' => 'هو بيتعامل مع طلبات الشركات الكبيرة بنفسه.',
    'in your voice.' => 'بأسلوبك.',
    'inbox.' => 'بيقفل الصفقات.',
    'less time spent switching between messaging apps' => 'وقت أقل بيتضيّع في التنقل بين تطبيقات المراسلة',
    'msgs' => 'رسالة',
    'of messages become paying customers, up from ~4%' => 'من الرسائل بتتحول لعملاء دافعين، بعد ما كانت حوالي 4%',
    'of my own sales to unanswered DMs last year. So this is the tool I needed.' => 'من مبيعاتي الشخصية بسبب رسائل ما جاوبتش عليها السنة اللي فاتت. فده الأداة اللي كنت محتاجها.',
    'or $290/yr — 2 months free' => 'أو 290 دولار/سنة — شهرين مجاناً',
    'or $790/yr — 2 months free' => 'أو 790 دولار/سنة — شهرين مجاناً',
    'or email the founder directly' => 'أو ابعت للمؤسس على الإيميل مباشرة',
    'plenty of headroom for a growing shop' => 'مساحة كافية لمحل بيكبر',
    'to see the actual conversation that led to a sale.' => 'علشان تشوف المحادثة الحقيقية اللي وصلت لبيع.',
    'to your notifications.' => 'في التنبيهات بتاعتك.',
    'trained on' => 'متدرب على',
    'typical AI reply, day or night, in your business voice' => 'رد ذكاء اصطناعي عادي، ليل نهار، بأسلوب بيزنسك',
    'usually replies in an hour' => 'بيرد عادةً في خلال ساعة',
    'you' => 'إنت',
    'your' => 'بتاعك',
    '→ Closed 3,400 AED · zero human touch' => '← اتقفلت 3,400 درهم · من غير أي تدخل إنساني',
    '→ Closed £48 · 4 min from first message' => '← اتقفلت 48 جنيه إسترليني · 4 دقايق من أول رسالة',
    '→ Closed £55 · reserved before 3 other buyers asked' => '← اتقفلت 55 جنيه إسترليني · محجوزة قبل ما 3 مشترين تانيين يسألوا',
];

$added = 0;
$updated = 0;
foreach ($translations as $en => $ar) {
    if (!isset($existing[$en]) || $existing[$en] === $en) {
        $existing[$en] = $ar;
        $added++;
    } else {
        $updated++;
    }
}

// Sort by key for stable diffs
ksort($existing);
file_put_contents($arFile, json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

echo "Added: $added new keys.\n";
echo "Skipped (already translated): $updated keys.\n";
echo "Total in ar.json now: " . count($existing) . " keys.\n";
