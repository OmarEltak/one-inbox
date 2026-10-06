@include('errors.layout', [
    'badge' => __('Not found'),
    'heading' => __("We couldn't find that page"),
    'body' => __('The link may have moved, or the page you were looking for never existed. Try one of the links below.'),
    'primaryHref' => url('/'),
    'primaryLabel' => __('Back to home'),
    'secondaryHref' => auth()->check() ? route('dashboard') : route('login'),
    'secondaryLabel' => auth()->check() ? __('Open dashboard') : __('Sign in'),
    'footLabel' => 'Error 404 · ' . __('Not found'),
])
