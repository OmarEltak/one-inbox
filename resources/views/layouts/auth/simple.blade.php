<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">

            {{-- Left: brand panel --}}
            <div class="hidden lg:flex flex-col justify-between p-12 relative overflow-hidden"
                 style="background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 50%, #5b21b6 100%);">

                {{-- Noise texture overlay --}}
                <div class="absolute inset-0 opacity-[0.03]"
                     style="background-image: url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\");"></div>

                {{-- Decorative circles --}}
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full opacity-10" style="background: radial-gradient(circle, #c4b5fd, transparent);"></div>
                <div class="absolute -bottom-32 -left-16 w-80 h-80 rounded-full opacity-10" style="background: radial-gradient(circle, #818cf8, transparent);"></div>

                {{-- Logo + brand --}}
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-10">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                            <x-app-logo-icon class="size-6 fill-current text-white" />
                        </span>
                        <span class="text-xl font-bold text-white tracking-tight">{{ config('app.name', 'One Inbox') }}</span>
                    </div>

                    <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                        One inbox.<br>All your conversations.
                    </h1>
                    <p class="text-lg text-violet-200 leading-relaxed max-w-xs">
                        Connect Facebook, Instagram, WhatsApp, Telegram and more — with an AI that closes deals while you sleep.
                    </p>
                </div>

                {{-- Feature list --}}
                <div class="relative z-10 space-y-4">
                    @foreach([
                        ['icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'text' => 'All platforms in one view — no tab-switching'],
                        ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'text' => 'AI responds 24/7 and pushes leads toward a sale'],
                        ['icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'text' => 'Analytics that show exactly where leads drop off'],
                    ] as $f)
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-violet-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}" />
                                </svg>
                            </div>
                            <p class="text-sm text-violet-100">{{ $f['text'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Bottom quote --}}
                <div class="relative z-10">
                    <p class="text-violet-300 text-xs">&copy; {{ date('Y') }} {{ config('app.name') }}. Built for modern sales teams.</p>
                </div>
            </div>

            {{-- Right: form panel --}}
            <div class="flex flex-col items-center justify-center p-8 md:p-12 bg-white">
                {{-- Mobile logo (only shown on small screens) --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 mb-8 lg:hidden" wire:navigate>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: linear-gradient(135deg, #7c3aed, #5b21b6);">
                        <x-app-logo-icon class="size-5 fill-current text-white" />
                    </span>
                    <span class="text-lg font-bold text-zinc-900">{{ config('app.name') }}</span>
                </a>

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
