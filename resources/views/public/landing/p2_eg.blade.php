@php
$source = 'P2-EG';
$content = [
    'en' => [
        'heading' => 'Spending big on Facebook leads and losing them in WhatsApp?',
        'lines' => [
            '🏢 AI qualifies every lead: budget, payment, timeline, unit size',
            '📅 Books hot leads directly on your calendar — within minutes',
            '💰 Founding Partner: 7,500 EGP setup + 2,500 EGP/month',
        ],
        'cta' => 'Talk to Omar on WhatsApp',
        'wa' => 'Hi, I am a real estate broker from Egypt - P2-EG',
        'title' => 'AI lead qualifier for Egyptian real estate brokers - OT1-Pro',
        'desc' => 'AI qualifies every FB lead the moment it submits. Books hot ones on your calendar. Built for Cairo & North Coast brokers.',
    ],
    'ar' => [
        'heading' => 'بتصرف عشرات الآلاف إعلانات والمبيعات بتضيع في الواتس؟',
        'lines' => [
            '🏢 ذكاء اصطناعي بيأهل كل ليد: ميزانية، تقسيط، إمتى، مساحة',
            '📅 بيحجز ميعاد مع الـ Hot Leads أوتوماتيكي خلال دقايق',
            '💰 الشريك المؤسس: ٧،٥٠٠ ج إعداد + ٢،٥٠٠ شهرياً',
        ],
        'cta' => 'كلم عمر على الواتس',
        'wa' => 'مرحبا، أنا بروكر عقاري وعايز أعرف أكتر - P2-EG',
        'title' => 'ذكاء اصطناعي يفلتر ليدز العقارات - OT1-Pro',
        'desc' => 'ذكاء اصطناعي بيأهل كل ليد قبل ما يوصلك، ويحجزلك ميعاد مع اللي بيعدي بس. للسماسرة في القاهرة والساحل الشمالي.',
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
