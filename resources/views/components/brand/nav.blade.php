{{--
    <x-brand.nav />

    Phase 1 shared marketing nav. Ports the "#topnav" pattern from
    homepage-redesign-preview.html. Self-contained: pulls in brand.css
    (only once per page) and its own scroll-listener script.

    Usage:
        <x-brand.nav />

    Requires resources/css/brand.css and Instrument Serif font (loaded
    in layouts/marketing.blade.php + layouts/app.blade.php <head>).

    Nav morphs from transparent-over-hero (cream text, dark logo) to
    solid-cream-scrolled (ink text, light logo) at scrollY > 40.
--}}

@once
    @push('head')
        @vite('resources/css/brand.css')
    @endpush
@endonce

<nav id="topnav" class="fixed top-0 inset-x-0 z-50 border-b border-transparent">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            {{-- Logo swaps based on nav state: dark PNG over dark hero, light PNG when nav turns cream. --}}
            <img src="/logo/logo-dark.png"  alt="OT1-Pro" class="nav-logo nav-logo-dark  h-10 w-auto" />
            <img src="/logo/logo-light.png" alt="OT1-Pro" class="nav-logo nav-logo-light h-10 w-auto hidden" />
        </a>

        <div class="hidden md:flex items-center gap-8 text-sm nav-text">
            <a href="{{ route('features') }}"    class="u-link opacity-80 hover:opacity-100">{{ __('Platforms') }}</a>
            <a href="{{ route('features') }}#howitworks" class="u-link opacity-80 hover:opacity-100">{{ __('How it works') }}</a>
            <a href="{{ route('pricing') }}"     class="u-link opacity-80 hover:opacity-100">{{ __('Pricing') }}</a>
            <a href="{{ route('blog.index') }}"  class="u-link opacity-80 hover:opacity-100">{{ __('Stories') }}</a>
        </div>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="nav-cta inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold">
                    {{ __('Dashboard') }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium nav-text u-link">{{ __('Sign in') }}</a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="nav-cta inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold">
                        {{ __('Start free') }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            @endauth
        </div>
    </div>
</nav>

@once
    @push('scripts')
        <script>
            // Sticky nav morph + logo swap. Toggles .solid on #topnav past 40px scroll,
            // swaps the two <img> logos so the visible one matches the surface.
            (function () {
                var nav = document.getElementById('topnav');
                if (!nav) return;
                function onScroll() {
                    var solid = window.scrollY > 40;
                    nav.classList.toggle('solid', solid);
                    var dark  = nav.querySelector('.nav-logo-dark');
                    var light = nav.querySelector('.nav-logo-light');
                    if (dark && light) {
                        dark.classList.toggle('hidden', solid);
                        light.classList.toggle('hidden', !solid);
                    }
                }
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();

                // Scroll-triggered fade-up (paired with .fade-up rule in brand.css)
                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (e) {
                            if (e.isIntersecting) e.target.classList.add('on');
                        });
                    }, { threshold: 0.1 });
                    document.querySelectorAll('.fade-up').forEach(function (el) { io.observe(el); });
                }
            })();
        </script>
    @endpush
@endonce
