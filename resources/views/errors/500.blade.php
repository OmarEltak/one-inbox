@include('errors.layout', [
    'badge' => __('Something broke'),
    'heading' => __('Something went wrong on our side'),
    'body' => __("We've already been notified and we're looking at it. Your work is safe — try reloading in a moment, or head home and come back shortly."),
    'primaryHref' => url()->previous() ?: url('/'),
    'primaryLabel' => __('Try again'),
    'secondaryHref' => url('/'),
    'secondaryLabel' => __('Back to home'),
    'footLabel' => 'Error 500 · ' . __('Server error'),
])
