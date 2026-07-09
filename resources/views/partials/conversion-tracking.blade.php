{{-- Conversion tracking — fires GA4 + Google Ads events for signup, WhatsApp click, form submit. --}}
{{-- gtag() is defined by partials/head.blade.php (GA4). Google Ads pixel loads only when GOOGLE_ADS_ID is set. --}}
@php($googleAdsId = config('services.google_ads.conversion_id'))
@php($signupLabel = config('services.google_ads.signup_label'))
@php($waLabel = config('services.google_ads.whatsapp_label'))
@php($formLabel = config('services.google_ads.form_label'))
@php($justRegistered = session()->pull('track_signup_conversion'))

@if($googleAdsId)
<script>gtag('config', @json($googleAdsId));</script>
@endif

<script>
(function () {
  const fire = function (name, params) {
    if (typeof gtag !== 'function') return;
    gtag('event', name, params || {});
  };

  @if($justRegistered)
    fire('sign_up', { method: 'email', user_id: @json((string) $justRegistered) });
    @if($googleAdsId && $signupLabel)
      gtag('event', 'conversion', { send_to: @json($googleAdsId . '/' . $signupLabel) });
    @endif
  @endif

  document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href*="wa.me"], a[href*="api.whatsapp.com/send"]');
    if (!link) return;
    fire('whatsapp_click', { link_url: link.href, page_path: location.pathname });
    @if($googleAdsId && $waLabel)
      gtag('event', 'conversion', { send_to: @json($googleAdsId . '/' . $waLabel) });
    @endif
  }, true);

  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || !form.matches('form[data-track-form]')) return;
    const name = form.dataset.trackForm || 'form_submit';
    fire('generate_lead', { form_name: name, page_path: location.pathname });
    @if($googleAdsId && $formLabel)
      gtag('event', 'conversion', { send_to: @json($googleAdsId . '/' . $formLabel) });
    @endif
  }, true);
})();
</script>
