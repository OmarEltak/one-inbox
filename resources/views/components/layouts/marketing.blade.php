<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark scroll-smooth">
<head>
    {{-- Google Analytics --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-WHWVHWKR3T"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-WHWVHWKR3T');
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'One Inbox — Unified Social Inbox with AI Sales Responder' }}</title>
    <meta name="description" content="{{ $description ?? 'Manage all your social conversations from Facebook, Instagram, WhatsApp, and Telegram in one place. AI-powered sales responder closes deals 24/7.' }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? 'One Inbox — Unified Social Inbox with AI Sales Responder' }}">
    <meta property="og:description" content="{{ $description ?? 'Manage all your social conversations from Facebook, Instagram, WhatsApp, and Telegram in one place.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="One Inbox">
    @if(isset($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'One Inbox' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Unified Social Inbox with AI Sales Responder' }}">

    {{-- Hreflang --}}
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="ar" href="{{ url()->current() }}?lang=ar">
    <link rel="alternate" hreflang="de" href="{{ url()->current() }}?lang=de">
    <link rel="alternate" hreflang="es" href="{{ url()->current() }}?lang=es">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    <link rel="icon" href="/logo.png" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js + plugins for marketing pages (plugins must load before core) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "One Inbox",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "{{ $description ?? 'Unified social inbox with AI-powered sales responder for Facebook, Instagram, WhatsApp, and Telegram.' }}",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "creator": {
            "@type": "Organization",
            "name": "One Inbox"
        }
    }
    </script>
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

    {{-- Navigation --}}
    <nav x-data="{ mobileOpen: false }" class="fixed top-0 z-50 w-full border-b border-zinc-200/50 bg-white/80 backdrop-blur-lg dark:border-zinc-800/50 dark:bg-zinc-950/80">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold">
                <img src="/logo.png" alt="OT1 Pro" class="size-8 rounded-lg object-cover" />
                OT1 Pro
            </a>

            {{-- Desktop Nav Links --}}
            <div class="hidden items-center gap-6 md:flex">
                <a href="{{ route('features') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Features') }}</a>
                {{-- <a href="{{ route('pricing') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Pricing') }}</a> --}}
                <a href="{{ route('about') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('About') }}</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Contact') }}</a>
            </div>

            {{-- Desktop Right: Language + Auth (hidden on mobile) --}}
            <div class="hidden items-center gap-3 md:flex">
                {{-- Language Switcher --}}
                @php $currentLocale = app()->getLocale(); @endphp
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white cursor-pointer">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        {{ strtoupper($currentLocale) }}
                        <svg class="size-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute end-0 mt-1.5 w-36 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                        <a href="?lang=en" @click="open = false" class="flex items-center gap-2 px-4 py-2 text-sm transition-colors {{ $currentLocale === 'en' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                            @if($currentLocale === 'en')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                            English
                        </a>
                        <a href="?lang=ar" @click="open = false" class="flex items-center gap-2 px-4 py-2 text-sm transition-colors {{ $currentLocale === 'ar' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                            @if($currentLocale === 'ar')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                            العربية
                        </a>
                        <a href="?lang=de" @click="open = false" class="flex items-center gap-2 px-4 py-2 text-sm transition-colors {{ $currentLocale === 'de' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                            @if($currentLocale === 'de')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                            Deutsch
                        </a>
                        <a href="?lang=es" @click="open = false" class="flex items-center gap-2 px-4 py-2 text-sm transition-colors {{ $currentLocale === 'es' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                            @if($currentLocale === 'es')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                            Español
                        </a>
                    </div>
                </div>

                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-purple-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-purple-700">{{ __('Dashboard') }}</a>
                @else
                    @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Log in') }}</a>
                    @endif
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-lg bg-purple-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-purple-700">{{ __('Get Started Free') }}</a>
                    @endif
                @endauth
            </div>

            {{-- Mobile Hamburger Button --}}
            <button @click="mobileOpen = !mobileOpen" class="flex size-10 items-center justify-center rounded-lg text-zinc-600 transition-colors hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 md:hidden cursor-pointer">
                <svg x-show="!mobileOpen" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                <svg x-show="mobileOpen" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Mobile Menu Panel --}}
        <div x-show="mobileOpen" x-cloak
             @click.outside="mobileOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="border-t border-zinc-200 bg-white px-6 pb-6 pt-4 dark:border-zinc-800 dark:bg-zinc-950 md:hidden">

            {{-- Nav Links --}}
            <div class="space-y-1">
                <a href="{{ route('features') }}" @click="mobileOpen = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('Features') }}</a>
                {{-- <a href="{{ route('pricing') }}" @click="mobileOpen = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('Pricing') }}</a> --}}
                <a href="{{ route('about') }}" @click="mobileOpen = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('About') }}</a>
                <a href="{{ route('contact') }}" @click="mobileOpen = false" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('Contact') }}</a>
            </div>

            {{-- Language Switcher --}}
            @php $currentLocale = app()->getLocale(); @endphp
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <p class="mb-2 px-3 text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('Language') }}</p>
                <div class="grid grid-cols-2 gap-1">
                    <a href="?lang=en" @click="mobileOpen = false" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm {{ $currentLocale === 'en' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                        @if($currentLocale === 'en')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                        English
                    </a>
                    <a href="?lang=ar" @click="mobileOpen = false" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm {{ $currentLocale === 'ar' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                        @if($currentLocale === 'ar')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                        العربية
                    </a>
                    <a href="?lang=de" @click="mobileOpen = false" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm {{ $currentLocale === 'de' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                        @if($currentLocale === 'de')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                        Deutsch
                    </a>
                    <a href="?lang=es" @click="mobileOpen = false" class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm {{ $currentLocale === 'es' ? 'font-semibold text-purple-700 bg-purple-50 dark:text-purple-300 dark:bg-purple-900/20' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                        @if($currentLocale === 'es')<svg class="size-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                        Español
                    </a>
                </div>
            </div>

            {{-- Auth Buttons --}}
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                @auth
                    <a href="{{ route('dashboard') }}" class="block w-full rounded-lg bg-purple-600 px-4 py-2.5 text-center text-sm font-medium text-white transition-colors hover:bg-purple-700">{{ __('Dashboard') }}</a>
                @else
                    <div class="flex flex-col gap-2">
                        @if(Route::has('login'))
                            <a href="{{ route('login') }}" class="block rounded-lg border border-zinc-300 px-4 py-2.5 text-center text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('Log in') }}</a>
                        @endif
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="block rounded-lg bg-purple-600 px-4 py-2.5 text-center text-sm font-medium text-white transition-colors hover:bg-purple-700">{{ __('Get Started Free') }}</a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    <main class="pt-20">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-zinc-200 bg-zinc-50 py-12 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Brand --}}
                <div>
                    <div class="flex items-center gap-2 text-lg font-bold">
                        <img src="/logo.png" alt="OT1 Pro" class="size-8 rounded-lg object-cover" />
                        OT1 Pro
                    </div>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('Unified social inbox with AI-powered sales responder.') }}</p>
                </div>

                {{-- Product --}}
                <div>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Product') }}</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="{{ route('features') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Features') }}</a></li>
                        {{-- <li><a href="{{ route('pricing') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Pricing') }}</a></li> --}}
                    </ul>
                </div>

                {{-- Company --}}
                <div>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Company') }}</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="{{ route('about') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('About') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Contact') }}</a></li>
                    </ul>
                </div>

                {{-- Legal --}}
                <div>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Legal') }}</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="{{ route('privacy') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Privacy Policy') }}</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Terms of Service') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-zinc-200 pt-8 text-center dark:border-zinc-800">
                <p class="text-sm text-zinc-500">&copy; {{ date('Y') }} OT1 Pro. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </footer>

</body>
</html>
