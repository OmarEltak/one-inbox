@props([
    'title'       => null,
    'description' => null,
    'canonical'   => null,
    'htmlLang'    => null,
    'htmlDir'     => null,
    'ogImage'     => null,
    'noindex'     => false,
    // Pass true when the page has NO dark hero at top (blog, contact, most
    // vs/*). Ensures the nav starts in solid state so the ink logo is visible
    // over cream instead of the dark logo being invisible on cream.
    'solidNav'    => false,
])
{{--
    <x-layouts.brand-marketing>
        page content
    </x-layouts.brand-marketing>

    Phase-2 marketing layout. Same SEO surface as <x-layouts.marketing>
    (title, meta description, canonical, Open Graph, Twitter, JSON-LD)
    but swaps the built-in nav + footer for the brand components
    (<x-brand.nav /> + <x-brand.footer />) that render in emerald + ink
    + cream per the Phase-1 design system.

    Do NOT re-add the old <nav> here — the brand nav sits directly on the
    body and morphs from transparent-over-hero to solid-cream on scroll.
    Adding a wrapping <main class="pt-20"> would push the hero away from
    the transparent nav; leave <main> unpadded and let each page decide
    its own top spacing (usually pt-32 to clear the fixed nav).
--}}
<!DOCTYPE html>
<html lang="{{ $htmlLang ?? str_replace('_', '-', app()->getLocale()) }}" dir="{{ $htmlDir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}" class="scroll-smooth">
<head>
    {{-- Google Analytics --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-WHWVHWKR3T"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-WHWVHWKR3T');
    </script>

    {{-- Microsoft Clarity: free session recordings + heatmaps. --}}
    @if(config('services.clarity.project_id'))
        <script>
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "{{ config('services.clarity.project_id') }}");
        </script>
    @endif

    @include('partials.heronsignal')
    @include('partials.conversion-tracking')

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'OT1-Pro — Every message. One closing inbox.' }}</title>
    <meta name="description" content="{{ $description ?? 'Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI in your voice, closing while you sleep.' }}">
    @php
        // Canonical MUST strip ?lang= — otherwise EN and AR versions of the same page
        // canonical to themselves and Google splits ranking signal between them.
        $canonicalUrl = $canonical ?? url()->current();
        $canonicalUrl = preg_replace('/([?&])lang=[^&]*(&|$)/', '$1', $canonicalUrl);
        $canonicalUrl = rtrim($canonicalUrl, '?&');
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif
    @if(config('services.google.site_verification'))
        <meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
    @endif
    @if(config('services.bing.site_verification'))
        <meta name="msvalidate.01" content="{{ config('services.bing.site_verification') }}">
    @endif
    @if(config('services.ahrefs.site_verification'))
        <meta name="ahrefs-site-verification" content="{{ config('services.ahrefs.site_verification') }}">
    @endif

    {{-- Open Graph --}}
    @php $ogImageUrl = $ogImage ?? config('app.url') . '/og-image.png'; @endphp
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? 'OT1-Pro — Every message. One closing inbox.' }}">
    <meta property="og:description" content="{{ $description ?? 'Every customer message in one place, answered by an AI in your voice.' }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="OT1-Pro">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'OT1-Pro' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Every message. One closing inbox.' }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    <link rel="icon" href="/logo.png" type="image/png">

    {{-- Brand typography: Inter (body) + Instrument Serif (headline treatments) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/brand.css', 'resources/js/app.js'])

    {{-- Alpine.js + plugins --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        /* Brand palette — extracted from homepage-redesign-preview.html so the brand
           pages don't need to pull tailwind cdn at runtime. */
        :root {
            --ink: #0a1f1c; --ink2: #132824;
            --cream: #faf7f2; --cream2: #f1ede4; --line: #e6dfd0;
            --emer-50:#ecfdf5; --emer-100:#d1fae5; --emer-400:#34d399;
            --emer-500:#10b981; --emer-600:#059669; --emer-700:#047857;
            --emer-900:#064e3b;
        }
        body.brand { font-family: 'Inter', system-ui, sans-serif; background: var(--cream); color: var(--ink); -webkit-font-smoothing: antialiased; }
        .bg-ink { background-color: var(--ink); }
        .bg-ink2 { background-color: var(--ink2); }
        .bg-cream { background-color: var(--cream); }
        .bg-cream2 { background-color: var(--cream2); }
        .text-ink { color: var(--ink); }
        .text-ink\/70 { color: rgba(10,31,28,0.7); }
        .text-ink\/60 { color: rgba(10,31,28,0.6); }
        .text-ink\/50 { color: rgba(10,31,28,0.5); }
        .text-ink\/40 { color: rgba(10,31,28,0.4); }
        .text-ink\/80 { color: rgba(10,31,28,0.8); }
        .text-cream { color: var(--cream); }
        .text-cream\/90 { color: rgba(250,247,242,0.9); }
        .text-cream\/80 { color: rgba(250,247,242,0.8); }
        .text-cream\/70 { color: rgba(250,247,242,0.7); }
        .text-cream\/60 { color: rgba(250,247,242,0.6); }
        .text-cream\/50 { color: rgba(250,247,242,0.5); }
        .text-cream\/40 { color: rgba(250,247,242,0.4); }
        .border-line { border-color: var(--line); }
        .border-cream\/10 { border-color: rgba(250,247,242,0.1); }
        .border-cream\/20 { border-color: rgba(250,247,242,0.2); }
        .border-cream\/30 { border-color: rgba(250,247,242,0.3); }
        .divide-line > * + * { border-color: var(--line); }
        .bg-emer-50 { background-color: var(--emer-50); }
        .bg-emer-100 { background-color: var(--emer-100); }
        .bg-emer-400 { background-color: var(--emer-400); }
        .bg-emer-500 { background-color: var(--emer-500); }
        .bg-emer-600 { background-color: var(--emer-600); }
        .hover\:bg-emer-400:hover { background-color: var(--emer-400); }
        .text-emer-400 { color: var(--emer-400); }
        .text-emer-500 { color: var(--emer-500); }
        .text-emer-600 { color: var(--emer-600); }
        .text-emer-700 { color: var(--emer-700); }
        .ring-emer-400 { --tw-ring-color: var(--emer-400); }
        .accent-emer-500 { accent-color: var(--emer-500); }
    </style>

    {{-- JSON-LD Structured Data (site-wide) --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "Organization",
        "name": "OT1-Pro",
        "alternateName": "OT1 Pro",
        "url": "https://ot1-pro.com",
        "logo": "https://ot1-pro.com/logo.png",
        "description": "Unified social inbox with AI-powered sales responder for Facebook, Instagram, WhatsApp, and Telegram.",
        "sameAs": [
            "https://www.facebook.com/oneinbox",
            "https://www.instagram.com/oneinbox",
            "https://twitter.com/oneinbox",
            "https://www.linkedin.com/company/oneinbox"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+20-102-636-1218",
            "contactType": "sales",
            "availableLanguage": ["English", "Arabic"],
            "url": "https://wa.me/201026361218"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "OT1-Pro",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "{{ $description ?? 'Unified social inbox with AI-powered sales responder.' }}",
        "offers": {
            "@type": "AggregateOffer",
            "priceCurrency": "USD",
            "lowPrice": "0",
            "highPrice": "79",
            "offerCount": "4"
        },
        "creator": {
            "@type": "Organization",
            "name": "OT1-Pro",
            "url": "https://ot1-pro.com"
        }
    }
    </script>

    @stack('schema')
    @stack('head')
</head>
<body class="brand min-h-screen">

    <x-brand.nav :solid="$solidNav" />

    {{-- No <main class="pt-20"> — each brand page controls its own top spacing
         (usually pt-32 on the hero to clear the fixed nav). --}}
    {{ $slot }}

    <x-brand.footer />

    @stack('scripts')

</body>
</html>
