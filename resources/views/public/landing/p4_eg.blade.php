@php
$source = 'P4-EG';
$content = [
    'en' => [
        'heading' => 'Friday night. Your restaurant is on fire. WhatsApp is dropping orders.',
        'lines' => [
            '🍕 AI takes the full order: name, address, items, payment',
            '📣 Posts the order to your kitchen WhatsApp group automatically',
            '💰 Founding Partner: 7,500 EGP setup + 2,500 EGP/month',
        ],
        'cta' => 'Talk to Omar on WhatsApp',
        'wa' => 'Hi, I own a restaurant in Egypt - P4-EG',
        'title' => 'AI order-taker for restaurants - OT1-Pro',
        'desc' => 'AI takes WhatsApp orders in full and posts to your kitchen group. Zero dropped orders during Friday night rush.',
    ],
    'ar' => [
        'heading' => 'الجمعة ٩ المسا. المطعم مولع. الواتس بيضيع طلبات.',
        'lines' => [
            '🍕 ذكاء اصطناعي بياخد الطلب كامل: الاسم، العنوان، الدفع',
            '📣 بيبعت الطلب لجروب المطبخ أوتوماتيكي',
            '💰 الشريك المؤسس: ٧،٥٠٠ ج إعداد + ٢،٥٠٠ شهرياً',
        ],
        'cta' => 'كلم عمر على الواتس',
        'wa' => 'مرحبا، عندي مطعم وعايز أعرف أكتر - P4-EG',
        'title' => 'ذكاء اصطناعي لطلبات المطاعم - OT1-Pro',
        'desc' => 'ذكاء اصطناعي بياخد طلبات الواتس كاملة ويبعتها لجروب المطبخ. ولا طلب يضيع في الزحمة.',
    ],
];
$c = $content[app()->getLocale()] ?? $content['ar'];
@endphp
@include('public.landing._layout', [
    'title'       => $c['title'],
    'description' => $c['desc'],
    'source'      => $source,
    'heading'     => $c['heading'],
    'lines'       => $c['lines'],
    'ctaLabel'    => $c['cta'],
    'waText'      => $c['wa'],
])
