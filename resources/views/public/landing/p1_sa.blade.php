@php
$source = 'P1-SA';
$content = [
    'en' => [
        'heading' => 'Instagram boutique in Saudi? Never miss another DM order.',
        'lines' => [
            '💬 AI replies in Khaleeji dialect on Instagram & WhatsApp',
            '🔥 Only pings you when the customer is ready to pay',
            '✨ 14-day pilot for 499 SAR · full refund guarantee',
        ],
        'cta' => 'Message us on WhatsApp',
        'wa' => 'Hi, saw your Instagram ad from Saudi - P1-SA',
        'title' => 'AI assistant for Instagram boutiques (Saudi) - OT1-Pro',
        'desc' => 'Custom AI that replies to your Instagram & WhatsApp DMs in Khaleeji dialect. 499 SAR for 14 days.',
    ],
    'ar' => [
        'heading' => 'عندك بوتيك على Instagram؟ لا تخسرين ولا طلب.',
        'lines' => [
            '💬 ذكاء اصطناعي يرد على رسايل Instagram و WhatsApp بأسلوبك',
            '🔥 ما يصحيكي إلا لما الزبونة جاهزة تدفع',
            '✨ جربيه ١٤ يوم بـ ٤٩٩ ريال - ضمان استرداد كامل',
        ],
        'cta' => 'ابعثي رسالة واتس',
        'wa' => 'مرحبا، جاية من إعلان Instagram السعودية - P1-SA',
        'title' => 'ذكاء اصطناعي لبوتيكات Instagram (السعودية) - OT1-Pro',
        'desc' => 'ذكاء اصطناعي مخصص يرد على رسايلك بـ Instagram و WhatsApp بأسلوبك. تجربة ١٤ يوم بـ ٤٩٩ ريال.',
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
