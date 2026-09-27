{{-- Google Analytics --}}
<script async src="https://www.googletagmanager.com/gtag/js?id=G-WHWVHWKR3T"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-WHWVHWKR3T');
</script>

@include('partials.heronsignal')

@include('partials.conversion-tracking')

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

{{-- Favicon cache-buster: bump the ?v= number whenever public/favicon-*.png is regenerated.
     Browsers cache favicons extremely aggressively — without this, users see the old
     purple square for weeks even after the file changes. --}}
<link rel="icon" href="/favicon.ico?v=2" sizes="any">
<link rel="icon" href="/favicon-32.png?v=2" type="image/png" sizes="32x32">
<link rel="icon" href="/favicon-16.png?v=2" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- @fluxAppearance intentionally REMOVED —
     it auto-adds class="dark" to <html> when browser prefers dark, which
     broke every Flux modal (dialog turned zinc-800, killed all our text-ink
     labels). The app is designed light-only; enable this only when we ship
     a real end-to-end dark theme. --}}
