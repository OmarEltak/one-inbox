@include('errors.layout', [
    'badge' => __('Access denied'),
    'heading' => __("You don't have access to this page"),
    'body' => __("Your account doesn't have permission for this. If you think this is a mistake, contact your workspace admin or email me."),
    'primaryHref' => auth()->check() ? route('dashboard') : route('login'),
    'primaryLabel' => auth()->check() ? __('Open dashboard') : __('Sign in'),
    'secondaryHref' => url('/'),
    'secondaryLabel' => __('Back to home'),
    'footLabel' => 'Error 403 · ' . __('Access denied'),
])
