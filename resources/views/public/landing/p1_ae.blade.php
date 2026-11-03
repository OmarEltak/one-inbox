@php
$source = 'P1-AE';
$content = [
    'en' => [
        'heading' => 'Instagram boutique in Dubai? Never miss another DM order.',
        'lines' => [
            '💬 AI replies on Instagram & WhatsApp — Arabic + English',
            '🔥 Only pings you when the customer is ready to pay',
            '✨ 14-day pilot for 499 AED · full refund guarantee',
        ],
        'cta' => 'Message us on WhatsApp',
        'wa' => 'Hi, saw your Instagram ad from UAE - P1-AE',
        'title' => 'AI assistant for Instagram boutiques (UAE) - OT1-Pro',
        'desc' => 'Custom AI that replies to your Instagram & WhatsApp DMs in Arabic + English. 499 AED for 14 days.',
    ],
    'ar' => [
        'heading' => 'بوتيك على Instagram في الإمارات؟ لا تفوّتين طلب.',
        'lines' => [
            '💬 ذكاء اصطناعي يرد على Instagram و WhatsApp - عربي + إنجليزي',
            '🔥 ما يصحيكي إلا لما الزبونة جاهزة تدفع',
            '✨ جربيه ١٤ يوم بـ ٤٩٩ درهم - ضمان استرداد',
        ],
        'cta' => 'ابعثي رسالة واتس',
        'wa' => 'مرحبا، جاية من إعلان Instagram الإمارات - P1-AE',
        'title' => 'ذكاء اصطناعي لبوتيكات Instagram (الإمارات) - OT1-Pro',
        'desc' => 'ذكاء اصطناعي مخصص يرد على رسايلك بـ Instagram و WhatsApp بالعربي والإنجليزي. ١٤ يوم بـ ٤٩٩ درهم.',
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
