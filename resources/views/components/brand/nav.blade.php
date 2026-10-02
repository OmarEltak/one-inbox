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

    Pages without a dark hero (blog, most /vs/*, dashboards) should pass
    `solid` so the nav starts already in the light-logo/ink-text state:
        <x-brand.nav solid />
--}}

@props(['solid' => false])

@once
    @push('head')
        @vite('resources/css/brand.css')
    @endpush
@endonce

@php
    $currentLocale      = app()->getLocale();
    $currentLangUrlBase = request()->fullUrlWithoutQuery('lang');
    $joiner             = str_contains($currentLangUrlBase, '?') ? '&' : '?';
    $otherLocale        = $currentLocale === 'ar' ? 'en' : 'ar';
    $otherLocaleLabel   = $otherLocale === 'ar' ? 'ع' : 'EN';
    $otherLocaleUrl     = $currentLangUrlBase . $joiner . 'lang=' . $otherLocale;
@endphp

<nav id="topnav"
     x-data="{ open: false }"
     @keydown.escape.window="open = false"
     class="fixed top-0 inset-x-0 z-50 border-b @if($solid) solid @endif"
     @if($solid) data-solid-pinned="1" @endif>
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-2 px-4 sm:px-6 py-3 sm:py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
            {{-- Logo swaps based on nav state: dark PNG over dark hero, light PNG when nav turns cream. --}}
            <img src="/logo/logo-dark.png"  alt="OT1-Pro" class="nav-logo nav-logo-dark  h-9 sm:h-10 w-auto {{ $solid ? 'hidden' : '' }}" />
            <img src="/logo/logo-light.png" alt="OT1-Pro" class="nav-logo nav-logo-light h-9 sm:h-10 w-auto {{ $solid ? '' : 'hidden' }}" />
        </a>

        <div class="hidden md:flex items-center gap-8 text-sm nav-text">
            <a href="{{ route('features') }}"    class="u-link opacity-80 hover:opacity-100">{{ __('Platforms') }}</a>
            <a href="{{ route('features') }}#howitworks" class="u-link opacity-80 hover:opacity-100">{{ __('How it works') }}</a>
            <a href="{{ route('pricing') }}"     class="u-link opacity-80 hover:opacity-100">{{ __('Pricing') }}</a>
            <a href="{{ route('blog.index') }}"  class="u-link opacity-80 hover:opacity-100">{{ __('Stories') }}</a>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3">
            {{-- Language switcher (desktop). Two anchors EN·AR — works without JS. --}}
            <div class="hidden sm:flex items-center gap-1 text-xs font-semibold nav-text">
                <a href="{{ $currentLangUrlBase . $joiner . 'lang=en' }}"
                   class="px-2 py-1 rounded-md {{ $currentLocale === 'en' ? 'opacity-100 underline underline-offset-4' : 'opacity-60 hover:opacity-100' }}">EN</a>
                <span class="opacity-30">·</span>
                <a href="{{ $currentLangUrlBase . $joiner . 'lang=ar' }}"
                   class="px-2 py-1 rounded-md {{ $currentLocale === 'ar' ? 'opacity-100 underline underline-offset-4' : 'opacity-60 hover:opacity-100' }}">AR</a>
            </div>

            {{-- Compact language toggle (mobile only) — one tap flips to the other locale. --}}
            <a href="{{ $otherLocaleUrl }}"
               aria-label="{{ __('Switch language') }}"
               class="sm:hidden inline-flex items-center justify-center h-9 w-9 rounded-full nav-text opacity-80 hover:opacity-100 border border-current/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/>
                </svg>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="nav-cta hidden lg:inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold">
                    {{ __('Dashboard') }}
                    <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium nav-text u-link">{{ __('Sign in') }}</a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="nav-cta hidden lg:inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-semibold">
                        {{ __('Start free') }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            @endauth

            {{-- Hamburger (mobile only) — opens the drawer below. --}}
            <button type="button"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    aria-label="{{ __('Menu') }}"
                    class="md:hidden inline-flex items-center justify-center h-9 w-9 rounded-full nav-text opacity-90 hover:opacity-100 border border-current/20">
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M6 18L18 6"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile drawer — slides down under the nav bar. Uses solid cream + ink so
         it is legible whether the nav is transparent-over-hero or scrolled-solid. --}}
    <div x-show="open"
         x-cloak
         @click.outside="open = false"
         style="background: var(--cream); color: var(--ink); border-top: 1px solid var(--line);"
         class="md:hidden shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-1 text-sm" style="color: var(--ink);">
            <a href="{{ route('features') }}" style="color: var(--ink);" class="px-3 py-2.5 rounded-lg hover:bg-cream2 font-medium">{{ __('Platforms') }}</a>
            <a href="{{ route('features') }}#howitworks" style="color: var(--ink);" class="px-3 py-2.5 rounded-lg hover:bg-cream2 font-medium">{{ __('How it works') }}</a>
            <a href="{{ route('pricing') }}" style="color: var(--ink);" class="px-3 py-2.5 rounded-lg hover:bg-cream2 font-medium">{{ __('Pricing') }}</a>
            <a href="{{ route('blog.index') }}" style="color: var(--ink);" class="px-3 py-2.5 rounded-lg hover:bg-cream2 font-medium">{{ __('Stories') }}</a>
            @guest
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" style="color: var(--ink); border-top: 1px solid var(--line);" class="px-3 py-2.5 rounded-lg hover:bg-cream2 font-medium mt-1 pt-3">{{ __('Sign in') }}</a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" style="background: var(--emer-600); color: var(--cream);" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-full text-sm font-semibold mt-2 mx-3">
                        {{ __('Start free') }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            @endguest
            @auth
                <a href="{{ route('dashboard') }}" style="background: var(--emer-600); color: var(--cream);" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-full text-sm font-semibold mt-2 mx-3">
                    {{ __('Dashboard') }}
                    <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endauth
            <div style="border-top: 1px solid var(--line);" class="flex items-center gap-1 px-3 pt-3 mt-1 text-xs font-semibold">
                <span class="me-2" style="color: rgba(10,31,28,0.7);">{{ __('Language') }}:</span>
                <a href="{{ $currentLangUrlBase . $joiner . 'lang=en' }}"
                   style="color: {{ $currentLocale === 'en' ? 'var(--ink)' : 'rgba(10,31,28,0.6)' }};"
                   class="px-2 py-1 rounded-md {{ $currentLocale === 'en' ? 'underline underline-offset-4' : 'hover:opacity-100' }}">EN</a>
                <span style="color: rgba(10,31,28,0.3);">·</span>
                <a href="{{ $currentLangUrlBase . $joiner . 'lang=ar' }}"
                   style="color: {{ $currentLocale === 'ar' ? 'var(--ink)' : 'rgba(10,31,28,0.6)' }};"
                   class="px-2 py-1 rounded-md {{ $currentLocale === 'ar' ? 'underline underline-offset-4' : 'hover:opacity-100' }}">AR</a>
            </div>
        </div>
    </div>
</nav>

@once
    @push('scripts')
        <script>
            // Sticky nav morph + logo swap. Toggles .solid on #topnav past 40px scroll,
            // swaps the two <img> logos so the visible one matches the surface.
            (function () {
                // Scroll-triggered fade-up (paired with .fade-up rule in brand.css).
                // MUST run before any early-return below: solid-pinned pages
                // (about, contact, vs/*, …) return early for the nav morph but
                // their .fade-up cards still need observing, otherwise they
                // stay at opacity:0 forever (invisible content gap).
                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (e) {
                            if (e.isIntersecting) e.target.classList.add('on');
                        });
                    }, { threshold: 0.1 });
                    document.querySelectorAll('.fade-up').forEach(function (el) { io.observe(el); });
                }
                var nav = document.getElementById('topnav');
                if (!nav) return;
                // Pages without a dark hero pin the nav in solid state via
                // data-solid-pinned. Skip the scroll morph entirely on those.
                if (nav.dataset.solidPinned === '1') return;
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
            })();
        </script>
    @endpush
@endonce
