@php
$source = 'P1-EG';
$content = [
    'en' => [
        'heading' => 'Instagram boutique? Never miss another DM order.',
        'lines' => [
            '💬 AI replies in your brand voice on Instagram & WhatsApp',
            '🔥 Only pings you when the customer is ready to pay',
            '✨ 14-day pilot for 3,500 EGP · full refund guarantee',
        ],
        'cta' => 'Message us on WhatsApp',
        'wa' => 'Hi, saw your Instagram ad - P1-EG',
        'title' => 'AI assistant for Instagram boutiques - OT1-Pro',
        'desc' => 'Custom AI that replies to your Instagram & WhatsApp DMs in your voice. 14-day trial, full refund guarantee.',
    ],
    'ar' => [
        'heading' => 'بوتيك على Instagram؟ خليكي ما تخسريش ولا طلب تاني.',
        'lines' => [
            '💬 ذكاء اصطناعي يرد على رسايل Instagram و WhatsApp بأسلوبك',
            '🔥 يبعتلك إشعار بس لما العميلة جاهزة تدفع',
            '✨ تجربة ١٤ يوم بـ ٣،٥٠٠ جنيه - مع ضمان استرداد كامل',
        ],
        'cta' => 'ابعتي رسالة واتس',
        'wa' => 'مرحبا، شفت إعلان Instagram، عايزة أعرف أكتر - P1-EG',
        'title' => 'ذكاء اصطناعي لبوتيكات Instagram - OT1-Pro',
        'desc' => 'ذكاء اصطناعي مخصص بيرد على رسايلك على Instagram و WhatsApp بأسلوبك. تجربة ١٤ يوم مع ضمان استرداد كامل.',
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
