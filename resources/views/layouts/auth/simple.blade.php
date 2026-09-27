<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @vite('resources/css/brand.css')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    </head>
    <body class="min-h-screen bg-cream text-ink antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">

            {{-- LEFT: dark ink brand panel --}}
            <div class="hidden lg:flex flex-col justify-between p-12 relative overflow-hidden bg-ink text-cream grain">

                {{-- Logo --}}
                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3" wire:navigate>
                        <img src="/logo/logo-dark.png" alt="{{ config('app.name') }}" class="h-10 w-auto" />
                    </a>
                </div>

                {{-- Welcome copy (serif, big, calm) --}}
                <div class="relative z-10 max-w-md">
                    <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-emer-400 mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-emer-400"></span>
                        <span>{{ __('One inbox') }}</span>
                    </div>
                    <h1 class="serif text-5xl leading-[1.05] mb-6">
                        {{ __('Every message.') }}<br>
                        <span class="serif-it text-emer-400">{{ __('One quiet inbox.') }}</span>
                    </h1>
                    <p class="text-cream/70 text-base leading-relaxed">
                        {{ __('Facebook, Instagram, WhatsApp, Telegram — all in one place, answered by an AI trained on your business, closing while you sleep.') }}
                    </p>
                </div>

                {{-- Trust signal --}}
                <div class="relative z-10">
                    <p class="text-cream/50 text-xs">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
                </div>
            </div>

            {{-- RIGHT: cream form panel --}}
            <div class="flex flex-col items-center justify-center p-8 md:p-12 bg-cream">
                {{-- Mobile logo (small screens only) --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 mb-8 lg:hidden" wire:navigate>
                    <img src="/logo/logo-light.png" alt="{{ config('app.name') }}" class="h-8 w-auto" />
                </a>

                <div class="w-full max-w-sm auth-form-panel">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
