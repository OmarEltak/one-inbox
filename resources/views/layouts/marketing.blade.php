<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark scroll-smooth">
<head>
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

    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "One Inbox",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "{{ $description ?? 'Unified social inbox with AI-powered sales responder for Facebook, Instagram, WhatsApp, and Telegram.' }}",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "creator": {
            "@@type": "Organization",
            "name": "One Inbox"
        }
    }
    </script>
    @stack('schema')
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

    {{-- Navigation --}}
    <nav class="fixed top-0 z-50 w-full border-b border-zinc-200/50 bg-white/80 backdrop-blur-lg dark:border-zinc-800/50 dark:bg-zinc-950/80">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold">
                <div class="flex size-8 items-center justify-center rounded-lg bg-purple-600">
                    <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                </div>
                One Inbox
            </a>
            <div class="hidden items-center gap-6 md:flex">
                <a href="{{ route('features') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Features') }}</a>
                {{-- <a href="{{ route('pricing') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Pricing') }}</a> --}}
                <a href="{{ route('blog.index') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Blog') }}</a>
                <a href="{{ route('about') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('About') }}</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Contact') }}</a>
            </div>
            <div class="flex items-center gap-3">
                {{-- Language Switcher --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white cursor-pointer">
                        {{ strtoupper(app()->getLocale()) }}
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition
                         class="absolute end-0 mt-1 w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                        <a href="?lang=en" class="block px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700">English</a>
                        <a href="?lang=ar" class="block px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700">العربية</a>
                        <a href="?lang=de" class="block px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700">Deutsch</a>
                        <a href="?lang=es" class="block px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700">Español</a>
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
                        <div class="flex size-8 items-center justify-center rounded-lg bg-purple-600">
                            <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                        </div>
                        One Inbox
                    </div>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('Unified social inbox with AI-powered sales responder.') }}</p>
                </div>

                {{-- Product --}}
                <div>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Product') }}</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="{{ route('features') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Features') }}</a></li>
                        {{-- <li><a href="{{ route('pricing') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Pricing') }}</a></li> --}}
                        <li><a href="{{ route('blog.index') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Blog') }}</a></li>
                        <li><a href="{{ route('whatsapp-inbox') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('WhatsApp Inbox') }}</a></li>
                        <li><a href="{{ route('instagram-dm') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Instagram DMs') }}</a></li>
                        <li><a href="{{ route('facebook-messenger') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Facebook Messenger') }}</a></li>
                        <li><a href="{{ route('telegram-inbox') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Telegram Inbox') }}</a></li>
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
            {{-- Industries --}}
            <div class="mt-8 grid gap-8 border-t border-zinc-200 pt-8 dark:border-zinc-800 sm:grid-cols-1">
                <div>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Industries') }}</h4>
                    <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-500">
                        <li><a href="{{ route('industry.real-estate') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Real Estate') }}</a></li>
                        <li><a href="{{ route('industry.ecommerce') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('E-commerce') }}</a></li>
                        <li><a href="{{ route('industry.agencies') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Agencies') }}</a></li>
                        <li><a href="{{ route('industry.restaurants') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Restaurants') }}</a></li>
                        <li><a href="{{ route('industry.education') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('Education') }}</a></li>
                    </ul>
                </div>
            </div>

            {{-- Compare --}}
            <div class="mt-8 grid gap-8 border-t border-zinc-200 pt-8 dark:border-zinc-800 sm:grid-cols-1">
                <div>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Compare') }}</h4>
                    <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-500">
                        <li><a href="{{ route('vs.trengo') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('vs Trengo') }}</a></li>
                        <li><a href="{{ route('vs.manychat') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('vs ManyChat') }}</a></li>
                        <li><a href="{{ route('vs.freshchat') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('vs Freshchat') }}</a></li>
                        <li><a href="{{ route('vs.respond-io') }}" class="hover:text-zinc-900 dark:hover:text-white">{{ __('vs Respond.io') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-zinc-200 pt-8 text-center dark:border-zinc-800">
                <p class="text-sm text-zinc-500">&copy; {{ date('Y') }} One Inbox. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </footer>

</body>
</html>
