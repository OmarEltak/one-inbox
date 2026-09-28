<?php
/**
 * Professional SEO-optimized Arabic narrative (from ChatGPT) that repositions
 * OT1-Pro as "مندوب مبيعات ذكي يقفل الصفقات" instead of literal English
 * translations. Plus dashboard + inbox-demo strings.
 */
$arFile = __DIR__ . '/../lang/ar.json';
$ar = json_decode(file_get_contents($arFile), true);

$fixes = [
    // ─── GPT professional SEO narrative rewrite ───────────────
    'Built for founders who close in DMs' => 'صُمّم لمن يحوّلون المحادثات إلى مبيعات',
    'Every message.' => 'كل رسالة.',
    'One closing' => 'صفقة تُحسَم',
    'inbox.' => 'من صندوق وارد واحد.',
    'Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI in your voice, closing while you sleep.' => 'اجمع رسائل عملائك من Facebook وInstagram وWhatsApp وTelegram في صندوق وارد موحّد. مساعد مبيعات بالذكاء الاصطناعي يردّ بصوت علامتك التجارية، ويتابع المحادثات، ويحوّل الاهتمام إلى صفقات مكتملة — حتى وأنت نائم.',
    'Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI that sounds like' => 'اجمع رسائل عملائك من Facebook وInstagram وWhatsApp وTelegram في صندوق واحد. مساعد مبيعات بالذكاء الاصطناعي يشبه',
    'business, closing while you sleep.' => 'علامتك التجارية، ويقفل الصفقات وأنت نائم.',
    'Start free — no card' => 'ابدأ مجاناً — دون بطاقة',
    'See how it works' => 'اكتشف كيف يعمل',
    'Trusted by shops from Cairo to Dubai to London' => 'تثق به متاجر من القاهرة إلى دبي إلى لندن',
    'The problem' => 'المشكلة',
    "You're not losing sales because your product is bad." => 'منتجك ليس المشكلة. المبيعات تضيع بسبب طريقة متابعة العملاء.',
    "You're losing them at reply #2." => 'تخسر عملاءك قبل الرد الثاني.',
    "A customer messages your Instagram at 9pm. You see it at 9:47am the next day. By then they've already bought from three other shops. Multiply that by every channel, every hour." => 'يراسلك عميل عبر Instagram في التاسعة مساءً، ولا ترى رسالته إلا في صباح اليوم التالي. بحلول ذلك الوقت، ربما اشترى من متاجر أخرى. تخيّل كم فرصة بيع تخسرها يومياً مع كل رسالة متأخرة، عبر جميع قنوات التواصل.',
    'The AI' => 'ذكاء اصطناعي يفهم المبيعات',
    'Not a chatbot.' => 'ليس مجرد بوت محادثة.',
    'A closer.' => 'بل مندوب مبيعات يُتقن إتمام الصفقات.',
    "Your AI reads the customer, checks your catalog, knows your shipping rules, and writes a reply in your business voice. If it doesn't know something, it asks you. If the customer's ready to buy, it sends the checkout link." => 'يفهم مساعدك الذكي احتياجات العميل، ويراجع منتجاتك، ويعرف سياسات الشحن، ثم يردّ بأسلوب علامتك التجارية. وعندما يحتاج إلى معلومة، يرجع إليك. وحين يصبح العميل مستعداً للشراء، يرسل إليه رابط الدفع. مندوب مبيعات ذكي يتولى المحادثة ويترك لك فرصة إتمام الصفقة.',
    'Every channel' => 'كل قنوات التواصل',
    'Everywhere.' => 'كلها في مكان واحد.',
    'One inbox for every message your business gets.' => 'صندوق وارد موحّد لإدارة رسائل العملاء من جميع قنوات التواصل، دون التنقل بين التطبيقات.',
    'Ninety seconds to' => '90 ثانية تفصلك عن',
    'go live.' => 'بدء العمل.',
    'A promise' => 'وعد واضح',
    'Free.' => 'مجاني.',
    'Stop losing sales' => 'لا تدع أي فرصة بيع تفوتك',
    'Try any plan free.' => 'جرّب أي باقة مجاناً.',

    // ─── Dashboard (25 keys) ──────────────────────────────────
    'Welcome to All in One' => 'مرحباً بك في All in One',
    "Here's your business overview for today." => 'إليك نظرة عامة على نشاطك اليوم.',
    'Total Messages' => 'إجمالي الرسائل',
    'Hot Leads' => 'العملاء المحتملون الجاهزون',
    'AI Performance' => 'أداء الذكاء الاصطناعي',
    'Recent Messages' => 'الرسائل الأخيرة',
    'Conversations' => 'المحادثات',
    'Platform Overview' => 'نظرة عامة على المنصات',
    'Lead Pipeline' => 'مسار العملاء المحتملين',
    'Open Inbox' => 'فتح الصندوق',
    'View All' => 'عرض الكل',
    'View and reply' => 'اعرض ورد',
    'Manage channels' => 'إدارة القنوات',
    'Connect a channel →' => 'اربط قناة ←',
    'AI handles' => 'الذكاء الاصطناعي يتعامل مع',
    'of all responses' => 'من إجمالي الردود',
    'need attention' => 'تحتاج إلى انتباه',
    'connected pages' => 'صفحة متصلة',
    'messages' => 'رسالة',
    'this week' => 'هذا الأسبوع',
    'total leads' => 'إجمالي العملاء المحتملين',
    'No hot leads yet' => 'لا يوجد عملاء جاهزون بعد',
    'No team selected' => 'لم يتم اختيار فريق',
    'Create or join a team to get started.' => 'أنشئ فريقاً أو انضم إلى فريق للبدء.',
    'Ask your assistant' => 'اسأل مساعدك',

    // ─── Inbox demo mockup strings (Before/After timeline) ────
    'Tuesday, 3:47am' => 'الثلاثاء، 3:47 صباحاً',
    'Tuesday, 8:12am' => 'الثلاثاء، 8:12 صباحاً',
    'Tuesday, 11:20am' => 'الثلاثاء، 11:20 صباحاً',
    'Tuesday, 2:15pm' => 'الثلاثاء، 2:15 مساءً',
    'Instagram DM' => 'رسالة Instagram',
    'WhatsApp' => 'WhatsApp',
    'Facebook Messenger' => 'Facebook Messenger',
    'Telegram' => 'Telegram',
    'Do you have the tote in black?' => 'هل الحقيبة متوفرة باللون الأسود؟',
    'Unread 14 hours.' => 'لم تُقرأ منذ 14 ساعة.',
    'Guys are you open tomorrow?' => 'هل ستفتحون غداً؟',
    'Missed. They bought elsewhere.' => 'فاتتك. اشتروا من مكان آخر.',
    'Salaam, price for the leather bag?' => 'السلام عليكم، كم سعر الحقيبة الجلدية؟',
    'Answered 6h later. Ghosted.' => 'تم الرد بعد 6 ساعات. اختفى.',
    'Still typing… 4 days later.' => 'ما زال يكتب… بعد 4 أيام.',
    'Yes, black totes are in stock. £48 with free UK shipping. Want me to send the direct checkout link?' => 'نعم، الحقائب السوداء متوفرة. 48 جنيهاً إسترلينياً مع الشحن المجاني للمملكة المتحدة. هل ترغب أن أُرسل رابط الدفع المباشر؟',
    "We're open tomorrow 10-6, but ordering online means you skip the queue. Do you want a link?" => 'نحن مفتوحون غداً من 10 صباحاً حتى 6 مساءً، لكن الطلب عبر الإنترنت يجنّبك الانتظار. هل تريد الرابط؟',
    'Wa alaikum salaam, the leather crossbody is £62 today, and yes we ship to Dubai in 3 days. Shall I hold one?' => 'وعليكم السلام، الحقيبة الجلدية الكتفية بـ62 جنيهاً إسترلينياً اليوم، ونعم نشحن إلى دبي خلال 3 أيام. هل أحجزها لك؟',
];

foreach ($fixes as $k => $v) {
    $ar[$k] = $v;
}
ksort($ar);
file_put_contents($arFile, json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
echo count($fixes) . " keys merged (GPT narrative + dashboard + demo mockups).\n";
