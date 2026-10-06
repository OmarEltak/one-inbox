@include('errors.layout', [
    'badge' => __('Slow down'),
    'heading' => __('Too many requests'),
    'body' => __("You're sending requests a little faster than we can keep up with. Wait a few seconds and try again — your account is fine."),
    'primaryHref' => url()->previous() ?: url('/'),
    'primaryLabel' => __('Try again'),
    'secondaryHref' => url('/'),
    'secondaryLabel' => __('Back to home'),
    'footLabel' => 'Error 429 · ' . __('Rate limited'),
])
