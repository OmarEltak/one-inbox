{{--
    Shared layout for /l/* UTM landing pages (ad traffic entry points).

    Required @yield / view data:
      $title       — <title> + og:title
      $description — meta description
      $source      — pixel + source tag (e.g. "P1-EG-A")
      $heading     — H1 (should be already-translated via __())
      $lines       — array<string> of 3 trust-building bullets
      $ctaLabel    — WhatsApp button label
      $waText      — raw text that gets URL-encoded into the WhatsApp link

    Behavior:
    - Fires Meta Pixel PageView + Lead if META_PIXEL_ID is set in config
    - Auto-redirects to WhatsApp after 2 seconds (enough for pixel to fire)
    - Respects ?lang=ar / ?lang=en via SetLocale middleware (RTL for ar)
--}}
@php
    $locale        = app()->getLocale();
    $isRtl         = $locale === 'ar';
    $waNumber      = '201026361218';
    $waText        = $waText ?? ($isRtl ? "مرحبا جايلك من {$source}" : "Hi, coming from {$source}");
    $waUrl         = 'https://wa.me/' . $waNumber . '?text=' . urlencode($waText);
    $pixelId       = config('services.meta.pixel_id');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="noindex,nofollow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Google Fonts: Cairo for AR, Inter for EN. Both load; one is used. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700;800&family=Inter:wght@500;700;800&display=swap" rel="stylesheet">

    {{-- Meta Pixel (loaded only if META_PIXEL_ID env set) --}}
    @if ($pixelId)
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', @json($pixelId));
    fbq('track', 'PageView');
    fbq('track', 'Lead', { source: @json($source) });
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1"/></noscript>
    @endif

    {{-- 2-second redirect to WhatsApp (lets Pixel fire first) --}}
    <meta http-equiv="refresh" content="2;url={{ $waUrl }}">

    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; min-height: 100vh; }
        body {
            font-family: {{ $isRtl ? "'Cairo'" : "'Inter'" }}, system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1e3a 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .logo {
            width: 72px;
            height: 72px;
            margin-bottom: 1.5rem;
            border-radius: 16px;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: #10d9a0;
        }
        h1 {
            font-size: clamp(1.4rem, 4.5vw, 2rem);
            font-weight: 800;
            line-height: 1.25;
            margin: 0 0 1.5rem 0;
            max-width: 32rem;
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem 0;
            max-width: 32rem;
            width: 100%;
        }
        li {
            padding: 0.9rem 1.1rem;
            margin-bottom: 0.6rem;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            font-size: 0.95rem;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }
        .cta {
            background: #25D366;
            color: #0a0e27;
            padding: 1rem 2rem;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.3);
            transition: transform 0.15s;
        }
        .cta:hover, .cta:active { transform: translateY(-1px); }
        .note {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
        }
        .lang-switch {
            position: absolute;
            top: 1rem;
            {{ $isRtl ? 'left' : 'right' }}: 1rem;
            display: flex;
            gap: 0.25rem;
        }
        .lang-switch a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .lang-switch a.active { color: #10d9a0; border-color: #10d9a0; }
    </style>
</head>
<body>
    <div class="lang-switch">
        <a href="?lang=ar" class="{{ $locale === 'ar' ? 'active' : '' }}">AR</a>
        <a href="?lang=en" class="{{ $locale === 'en' ? 'active' : '' }}">EN</a>
    </div>

    <div class="logo">O1</div>

    <h1>{{ $heading }}</h1>

    <ul>
        @foreach ($lines as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ul>

    <a href="{{ $waUrl }}" class="cta" id="wa-cta">
        💬 {{ $ctaLabel }}
    </a>

    <p class="note">
        {{ __('Redirecting you to WhatsApp in 2 seconds…') }}
    </p>

    <script>
        // Fire conversion when user clicks early (vs waiting 2s)
        document.getElementById('wa-cta').addEventListener('click', function () {
            if (typeof fbq === 'function') fbq('track', 'Contact', { source: @json($source) });
        });
    </script>
</body>
</html>
