@include('errors.layout', [
    'badge' => __('Maintenance'),
    'heading' => __("I'm giving the site a quick tune-up"),
    'body' => __("OT1-Pro is briefly down for maintenance. I'll be back in a few minutes — no action needed on your end."),
    'primaryHref' => url('/'),
    'primaryLabel' => __('Reload'),
    'secondaryHref' => null,
    'secondaryLabel' => null,
    'footLabel' => 'Error 503 · ' . __('Service unavailable'),
])
