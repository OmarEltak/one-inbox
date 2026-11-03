@php
$source = 'P5-AE';
$content = [
    'en' => [
        'heading' => "Shopify shows cart abandonment. It hides DM abandonment.",
        'lines' => [
            '🛒 Custom AI widget for Shopify / WooCommerce (we build it with you)',
            '🔗 Same AI handles your Instagram & WhatsApp DMs — unified pre-sale',
            '💼 White-Glove: starts at 5,000 AED setup + 1,999 AED/month',
        ],
        'cta' => 'Talk to Omar on WhatsApp',
        'wa' => 'Hi, I run a Shopify brand and want the widget - P5-AE',
        'title' => 'Pre-sale AI widget for Shopify & WooCommerce - OT1-Pro',
        'desc' => 'Custom-built pre-sale AI that unifies your site widget and Instagram DMs. Built with you in 2-4 weeks.',
    ],
    'ar' => [
        'heading' => 'Shopify بيوريك الـ cart abandonment. مش بيوريك الـ DM abandonment.',
        'lines' => [
            '🛒 ودجت ذكاء اصطناعي مخصص لـ Shopify / WooCommerce (بنبنيه معاك)',
            '🔗 نفس الذكاء بيرد على Instagram و WhatsApp — تجربة موحدة',
            '💼 White-Glove: من ٥،٠٠٠ درهم إعداد + ١،٩٩٩ شهرياً',
        ],
        'cta' => 'كلم عمر على الواتس',
        'wa' => 'مرحبا، عندي براند Shopify ومحتاج ودجت - P5-AE',
        'title' => 'ذكاء اصطناعي للـ pre-sale على Shopify و WooCommerce - OT1-Pro',
        'desc' => 'ودجت ذكاء اصطناعي مخصص بيوحد موقعك مع Instagram DMs. بنبنيه معاك في ٢-٤ أسابيع.',
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
