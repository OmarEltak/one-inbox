{{--
    <x-brand.footer />

    Phase 1 shared marketing footer. Ports the cream-surface footer from
    homepage-redesign-preview.html. Self-contained: pulls brand.css once
    (only added if not already loaded via <x-brand.nav />).

    Usage:
        <x-brand.footer />

    All links resolve to real routes. Compare-vs routes wrapped in
    Route::has() so the component stays safe if a comparison page is
    removed later.
--}}

@once
    @push('head')
        @vite('resources/css/brand.css')
    @endpush
@endonce

<footer class="bg-cream border-t border-line py-12">
    <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-4 gap-8 text-sm">
        <div>
            <div class="mb-3">
                <img src="/logo/logo-light.png" alt="OT1-Pro" class="h-10 w-auto" />
            </div>
            <p class="text-ink/60 leading-relaxed">
                {{ __('One inbox for every message your business gets. Built in Cairo, used worldwide.') }}
            </p>
        </div>

        <div>
            <div class="text-xs uppercase tracking-widest text-ink/50 mb-3">{{ __('Product') }}</div>
            <ul class="space-y-2 text-ink/80">
                <li><a href="{{ route('features') }}"   class="u-link">{{ __('Platforms') }}</a></li>
                <li><a href="{{ route('pricing') }}"    class="u-link">{{ __('Pricing') }}</a></li>
                @if(Route::has('vs.respond-io'))
                    <li><a href="{{ route('vs.respond-io') }}" class="u-link">{{ __('Compare vs Respond.io') }}</a></li>
                @endif
                @if(Route::has('vs.manychat'))
                    <li><a href="{{ route('vs.manychat') }}"   class="u-link">{{ __('Compare vs Manychat') }}</a></li>
                @endif
            </ul>
        </div>

        <div>
            <div class="text-xs uppercase tracking-widest text-ink/50 mb-3">{{ __('Learn') }}</div>
            <ul class="space-y-2 text-ink/80">
                <li><a href="{{ route('blog.index') }}" class="u-link">{{ __('Blog') }}</a></li>
                <li><a href="{{ route('about') }}"      class="u-link">{{ __('About') }}</a></li>
                <li><a href="{{ route('contact') }}"    class="u-link">{{ __('Contact') }}</a></li>
            </ul>
        </div>

        <div>
            <div class="text-xs uppercase tracking-widest text-ink/50 mb-3">{{ __('Reach us') }}</div>
            <ul class="space-y-2 text-ink/80">
                <li>omareltak7@gmail.com</li>
                <li><a href="{{ route('contact') }}" class="u-link">{{ __('WhatsApp the founder') }}</a></li>
                <li class="text-ink/40 text-xs pt-2">&copy; {{ date('Y') }} OT1-Pro. {{ __('Made in Cairo.') }}</li>
            </ul>
        </div>
    </div>
</footer>
