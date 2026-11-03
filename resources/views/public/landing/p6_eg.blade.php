@php
$source = 'P6-EG';
$content = [
    'en' => [
        'heading' => 'Selling courses on Messenger? 50% of leads vanish in slow replies.',
        'lines' => [
            '📚 AI runs your sales script on Messenger in your voice',
            '💰 Closes the sale, sends payment instructions, escalates when needed',
            '✨ 14-day pilot for 3,500 EGP · full refund guarantee',
        ],
        'cta' => 'Message Omar on WhatsApp',
        'wa' => 'Hi, I sell online courses on Messenger - P6-EG',
        'title' => 'AI sales script for Messenger infoproduct sellers - OT1-Pro',
        'desc' => 'AI runs your sales script on Facebook Messenger in your voice, closes the sale, sends payment instructions.',
    ],
    'ar' => [
        'heading' => 'بتبيع كورسات على Messenger؟ ٥٠٪ من الليدز بتضيع.',
        'lines' => [
            '📚 ذكاء اصطناعي بيشغّل سكريبت مبيعاتك بأسلوبك',
            '💰 بيقفل البيع، بيبعت تعليمات الدفع، بيحوّل اللي محتاج تدخّل',
            '✨ تجربة ١٤ يوم بـ ٣،٥٠٠ ج - ضمان استرداد',
        ],
        'cta' => 'كلم عمر على الواتس',
        'wa' => 'مرحبا، أنا بابيع كورسات على Messenger - P6-EG',
        'title' => 'ذكاء اصطناعي لمبيعات Messenger - OT1-Pro',
        'desc' => 'ذكاء اصطناعي بيشغّل سكريبت مبيعاتك على Facebook Messenger بأسلوبك، بيقفل البيع، بيبعت تعليمات الدفع.',
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
