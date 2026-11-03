@php
$source = 'P2-AE';
$content = [
    'en' => [
        'heading' => 'Dubai broker spending big on FB leads and losing them in WhatsApp?',
        'lines' => [
            '🏢 AI qualifies every lead: budget, payment plan, timeline',
            '📅 Books hot leads on your calendar — within minutes',
            '💰 Founding Partner: 1,499 AED setup + 599 AED/month',
        ],
        'cta' => 'Message Omar on WhatsApp',
        'wa' => 'Hi, I am a Dubai real estate broker - P2-AE',
        'title' => 'AI lead qualifier for Dubai real estate brokers - OT1-Pro',
        'desc' => 'AI qualifies every FB lead the moment it submits. Books hot ones on your calendar. Built for Dubai off-plan brokers.',
    ],
    'ar' => [
        'heading' => 'بروكر دبي بيصرف إعلانات والمبيعات ضايعة في الواتس؟',
        'lines' => [
            '🏢 ذكاء اصطناعي بيأهل كل ليد: ميزانية، خطة دفع، وقت',
            '📅 بيحجز Hot Leads على كالندر خلال دقايق',
            '💰 الشريك المؤسس: ١،٤٩٩ درهم إعداد + ٥٩٩ شهرياً',
        ],
        'cta' => 'كلم عمر على الواتس',
        'wa' => 'مرحبا، أنا بروكر دبي عقاري - P2-AE',
        'title' => 'ذكاء اصطناعي يفلتر ليدز عقارات دبي - OT1-Pro',
        'desc' => 'ذكاء اصطناعي بيأهل كل ليد قبل ما يوصلك ويحجزلك ميعاد. مبني لسماسرة دبي.',
    ],
];
$c = $content[app()->getLocale()] ?? $content['en'];
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
