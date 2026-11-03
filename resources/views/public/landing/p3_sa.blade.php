@php
$source = 'P3-SA';
$content = [
    'en' => [
        'heading' => 'Your clinic is open 24/7. Your receptionist works 8 hours.',
        'lines' => [
            '💉 AI books appointments on Instagram & WhatsApp — Arabic + English',
            '🩺 Never answers a medical question — routes to the doctor instantly',
            '💼 Founding Partner: 1,499 SAR setup + 599 SAR/month',
        ],
        'cta' => 'Message Omar on WhatsApp',
        'wa' => 'Hi, I own a clinic in Saudi - P3-SA',
        'title' => 'AI appointment booker for Saudi clinics - OT1-Pro',
        'desc' => 'AI books appointments 24/7, answers price & package questions, routes clinical questions to the doctor. Khaleeji dialect.',
    ],
    'ar' => [
        'heading' => 'عيادتك ٢٤ ساعة، موظفة الاستقبال ٨ ساعات.',
        'lines' => [
            '💉 ذكاء اصطناعي يحجز المواعيد بـ Instagram و WhatsApp',
            '🩺 ما يرد على سؤال طبي - يحوّله للدكتور فوراً',
            '💼 الشريك المؤسس: ١،٤٩٩ ريال إعداد + ٥٩٩ شهرياً',
        ],
        'cta' => 'ابعثي للدكتور عمر',
        'wa' => 'مرحبا، عندي عيادة بالسعودية - P3-SA',
        'title' => 'ذكاء اصطناعي لحجز مواعيد العيادات (السعودية) - OT1-Pro',
        'desc' => 'ذكاء اصطناعي يحجز المواعيد ٢٤ ساعة، يرد على الأسعار والباقات، ويحوّل الأسئلة الطبية للدكتور. لهجة خليجية.',
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
