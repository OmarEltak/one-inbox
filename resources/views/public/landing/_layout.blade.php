{{--
    /l/* UTM landing — INSTANT redirect to WhatsApp.

    No visible UI. Pixel fires inline before the location change (browsers
    execute inline JS synchronously, so Lead event is on the wire before
    the redirect fires). User sees a blank page for 100-300ms then WhatsApp.

    If META_PIXEL_ID env is blank, we skip the Pixel entirely and redirect
    even faster. The source tag is preserved in the WhatsApp message either way.

    View data required:
      $source  — source tag (e.g. "P1-EG"), passed to Pixel + prefilled in WA message
      $waText  — raw WhatsApp message text (optional, falls back to a default)
--}}
@php
    $locale   = app()->getLocale();
    $waNumber = '201026361218';
    $waText   = $waText ?? ($locale === 'ar' ? "مرحبا جايلك من {$source}" : "Hi, coming from {$source}");
    $waUrl    = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($waText);
    $pixelId  = config('services.meta.pixel_id');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
    <title>→</title>
    <style>html,body{margin:0;padding:0;background:#0a0e27;}</style>
</head>
<body>
    @if ($pixelId)
    <script>
    // Load fbevents.js, init pixel, fire Lead + Contact synchronously, then redirect.
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
    (window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', @json($pixelId));
    fbq('track', 'PageView');
    fbq('track', 'Lead',    { source: @json($source) });
    fbq('track', 'Contact', { source: @json($source) });
    // Pixel fires via async-but-queued script; redirect kicks the browser immediately.
    // Pixel events already pushed to fbq.queue by this point — the GET to facebook.net
    // happens in parallel with the window.location change.
    window.location.replace(@json($waUrl));
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url={{ $waUrl }}">
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=Lead&cd[source]={{ urlencode($source) }}&noscript=1"/>
    </noscript>
    @else
    {{-- No Pixel — pure redirect. --}}
    <script>window.location.replace(@json($waUrl));</script>
    <noscript><meta http-equiv="refresh" content="0;url={{ $waUrl }}"></noscript>
    @endif
</body>
</html>
