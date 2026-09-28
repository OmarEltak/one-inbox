<?php
/**
 * Per-locale demo content: names, cities, currencies, messages
 * localized for the audience.
 *
 * English → Western names, London/NYC/Toronto, GBP
 * Arabic  → MENA names, Cairo/Riyadh/Dubai, EGP/AED
 */

// ─── EN ────────────────────────────────────────────
$enFile = __DIR__ . '/../lang/en.json';
$en = json_decode(file_get_contents($enFile), true) ?: [];
$enFixes = [
    // Dashboard welcome
    'Welcome back, :name' => 'Welcome back, :name',

    // Trust bar
    'Trusted by shops from Cairo to Dubai to London' => 'Trusted by shops from London to New York to Toronto',

    // Inbox preview cards (right column, 4 rows)
    'demo.inbox.name1'    => 'Emma',
    'demo.inbox.preview1' => '"Is the small tote still…"',
    'demo.inbox.name2'    => 'James',
    'demo.inbox.preview2' => '"Ok I\'ll take it — please…"',
    'demo.inbox.name3'    => 'Marco',
    'demo.inbox.preview3' => '"Do you deliver in Rome?"',
    'demo.inbox.name4'    => 'Sarah',
    'demo.inbox.preview4' => '"Hi, do you have red in the small…"',

    // Main chat mockup (top left, cycles via JS)
    'demo.chat.customer1_name' => 'Sarah Chen',
    'demo.chat.customer1_msg1' => 'Hi! Do you have the leather crossbody in black?',
    'demo.chat.customer1_ai1'  => 'Hey Sarah! Yes, black is in stock — £62, ships free across the UK. Want the checkout link?',
    'demo.chat.customer1_msg2' => 'Yes please 🙌',
    'demo.chat.customer2_name' => 'Marco Rossi',
    'demo.chat.customer2_msg1' => 'Ciao, do you deliver to Rome?',
    'demo.chat.customer2_ai1'  => 'Ciao Marco! Yes — 4 days to Rome via DHL, €14 shipping. What are you looking at?',
    'demo.chat.customer2_msg2' => 'The mustard tote 😍',
    'demo.chat.customer3_name' => 'James Wilson',
    'demo.chat.customer3_msg1' => 'Hi, do you have red in the small bag?',
    'demo.chat.customer3_ai1'  => 'Hi James! Red small is available — £48, delivery to London in 2 days. Reserve one?',
    'demo.chat.customer3_msg2' => 'Yes please',

    // Hover-any-card demo (middle section)
    'demo.hover.name'         => 'Emma',
    'demo.hover.customer_msg' => 'Hey! Do you still have the small crossbody in mustard? Need it for Friday 🙏',
    'demo.hover.ai_reply'     => 'Yes — mustard small is in stock! I can ship priority for Friday delivery, £4 extra. Reserve it now?',
];
foreach ($enFixes as $k => $v) { $en[$k] = $v; }
ksort($en);
file_put_contents($enFile, json_encode($en, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

// ─── AR ────────────────────────────────────────────
$arFile = __DIR__ . '/../lang/ar.json';
$ar = json_decode(file_get_contents($arFile), true);
$arFixes = [
    // Dashboard welcome
    'Welcome back, :name' => 'مرحباً بعودتك، :name',

    // Trust bar — Cairo/Riyadh/Dubai instead of the mixed Cairo-Dubai-London
    'Trusted by shops from Cairo to Dubai to London' => 'تثق به متاجر من القاهرة إلى الرياض إلى دبي',

    // Inbox preview cards (4 rows)
    'demo.inbox.name1'    => 'ليلى',
    'demo.inbox.preview1' => '"لسه الشنطة الصغيرة موجودة؟"',
    'demo.inbox.name2'    => 'أحمد',
    'demo.inbox.preview2' => '"حاخدها — من فضلك…"',
    'demo.inbox.name3'    => 'محمد',
    'demo.inbox.preview3' => '"بتوصلوا للرياض؟"',
    'demo.inbox.name4'    => 'فاطمة',
    'demo.inbox.preview4' => '"السلام، عندك الأحمر في الشنطة الصغيرة؟"',

    // Main chat mockup (top left, cycles via JS)
    'demo.chat.customer1_name' => 'سارة أحمد',
    'demo.chat.customer1_msg1' => 'السلام عليكم، عندك الشنطة الجلد الكروسبودي بالأسود؟',
    'demo.chat.customer1_ai1'  => 'وعليكم السلام! نعم، الأسود متوفر — 620 جنيه، الشحن مجاناً داخل مصر. أرسل لك رابط الدفع؟',
    'demo.chat.customer1_msg2' => 'نعم من فضلك 🙌',
    'demo.chat.customer2_name' => 'خالد الحربي',
    'demo.chat.customer2_msg1' => 'السلام، هل تشحنون إلى الرياض؟',
    'demo.chat.customer2_ai1'  => 'وعليكم السلام خالد! نعم — 3 أيام للرياض عبر أرامكس، الشحن مجاني للطلبات فوق 300 ريال. ما الذي تنظر إليه؟',
    'demo.chat.customer2_msg2' => 'الشنطة الخردلي 😍',
    'demo.chat.customer3_name' => 'فاطمة الراشد',
    'demo.chat.customer3_msg1' => 'السلام، عندك الأحمر في الشنطة الصغيرة؟',
    'demo.chat.customer3_ai1'  => 'وعليكم السلام! الأحمر مقاس صغير متوفر — 240 درهم، التوصيل لدبي خلال 3 أيام. أحجزها لك؟',
    'demo.chat.customer3_msg2' => 'نعم من فضلك',

    // Hover-any-card demo
    'demo.hover.name'         => 'ليلى',
    'demo.hover.customer_msg' => 'السلام! لسه الشنطة الصغيرة موجودة باللون الخردلي؟ محتاجاها للجمعة 🙏',
    'demo.hover.ai_reply'     => 'وعليكم السلام! نعم، مقاس صغير خردلي متوفر — أقدر أشحنه لك مع الشحن السريع للتسليم يوم الجمعة، بفرق 40 جنيه. أحجزه لك؟',
];
foreach ($arFixes as $k => $v) { $ar[$k] = $v; }
ksort($ar);
file_put_contents($arFile, json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");

echo "EN: " . count($enFixes) . " keys, AR: " . count($arFixes) . " keys.\n";
