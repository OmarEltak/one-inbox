<x-layouts.marketing :title="__('Pricing') . ' — One Inbox'" :description="__('Simple, transparent pricing. Start free, upgrade when you grow.')">

    {{-- Hero --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <span class="inline-flex items-center rounded-full bg-purple-100 px-4 py-1.5 text-sm font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                    {{ __('No credit card required to start') }}
                </span>
                <h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-5xl">
                    {{ __('One price for every message,') }}<br>
                    <span class="text-purple-600">{{ __('every platform, every lead.') }}</span>
                </h1>
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                    {{ __('Stop paying for 4 different tools. One Inbox unifies Facebook, Instagram, WhatsApp & Telegram with an AI that converts conversations into customers — automatically.') }}
                </p>
            </div>

            {{-- Platform badges --}}
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                {{-- Facebook --}}
                <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-1.5 text-sm font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Facebook
                </span>
                {{-- Instagram --}}
                <span class="inline-flex items-center gap-2 rounded-full border border-pink-200 bg-pink-50 px-4 py-1.5 text-sm font-medium text-pink-700 dark:border-pink-800 dark:bg-pink-900/20 dark:text-pink-300">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    Instagram
                </span>
                {{-- WhatsApp --}}
                <span class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-1.5 text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </span>
                {{-- Telegram --}}
                <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-4 py-1.5 text-sm font-medium text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    Telegram
                </span>
                {{-- TikTok --}}
                <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-4 py-1.5 text-sm font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.76a4.85 4.85 0 01-1.01-.07z"/></svg>
                    TikTok <span class="text-xs opacity-60">(soon)</span>
                </span>
                {{-- LinkedIn --}}
                <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-4 py-1.5 text-sm font-medium text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    LinkedIn <span class="text-xs opacity-60">(soon)</span>
                </span>
            </div>

            {{-- Pricing cards --}}
            <div class="mt-16 grid gap-6 lg:grid-cols-5">

                {{-- Free --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900 flex flex-col">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Free') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ __('Try before you commit') }}</p>
                        <p class="mt-6"><span class="text-4xl font-bold">$0</span><span class="text-zinc-500">/{{ __('mo') }}</span></p>
                        <p class="mt-2 text-xs text-zinc-400">{{ __('No credit card needed') }}</p>
                        <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('1 connected page') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('50 AI responses/mo') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unified inbox') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('1 team member') }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-auto pt-8">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="block w-full rounded-lg border border-zinc-300 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                {{ __('Get Started Free') }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Starter --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900 flex flex-col">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Starter') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ __('Your AI sales rep, 24/7') }}</p>
                        <p class="mt-6"><span class="text-4xl font-bold">$29</span><span class="text-zinc-500">/{{ __('mo') }}</span></p>
                        <p class="mt-2 text-xs text-zinc-400">{{ __('Pays for itself with 1 closed lead') }}</p>
                        <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('3 connected pages') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('500 AI responses/mo') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('All 4 platforms') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Lead scoring') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('3 team members') }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-auto pt-8">
                        <a href="https://wa.me/201026361218?text={{ urlencode('Hi, I\'m interested in the Starter plan ($29/mo)') }}" target="_blank" class="block w-full rounded-lg border border-purple-300 py-2.5 text-center text-sm font-semibold text-purple-700 transition-colors hover:bg-purple-50 dark:border-purple-700 dark:text-purple-300 dark:hover:bg-purple-900/20">
                            {{ __('Get Started') }}
                        </a>
                    </div>
                </div>

                {{-- Pro --}}
                <div class="relative rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900 flex flex-col">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Pro') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ __('Built for teams that close deals') }}</p>
                        <p class="mt-6"><span class="text-4xl font-bold">$79</span><span class="text-zinc-500">/{{ __('mo') }}</span></p>
                        <p class="mt-2 text-xs text-zinc-400">{{ __('Replaces a part-time sales hire') }}</p>
                        <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('5 connected pages') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('2,000 AI responses/mo') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('All platforms + TikTok (soon)') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Advanced analytics') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('AI bulk campaigns') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('10 team members') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Priority support') }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-auto pt-8">
                        <a href="https://wa.me/201026361218?text={{ urlencode('Hi, I\'m interested in the Pro plan ($79/mo)') }}" target="_blank" class="block w-full rounded-lg bg-purple-600 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Get Started') }}
                        </a>
                    </div>
                </div>

                {{-- Business --}}
                <div class="relative rounded-2xl border-2 border-purple-600 bg-white p-8 dark:bg-zinc-900 flex flex-col">
                    <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-purple-600 px-4 py-1 text-xs font-semibold text-white whitespace-nowrap">{{ __('Most Popular') }}</span>
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Business') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ __('For growing brands & multi-location') }}</p>
                        <p class="mt-6"><span class="text-4xl font-bold">$199</span><span class="text-zinc-500">/{{ __('mo') }}</span></p>
                        <p class="mt-2 text-xs text-zinc-400">{{ __('Replaces a full-time sales rep') }}</p>
                        <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('15 connected pages') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('10,000 AI responses/mo') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('All platforms') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unlimited team members') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Custom AI voice & training') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('AI bulk campaigns') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Priority support') }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-auto pt-8">
                        <a href="https://wa.me/201026361218?text={{ urlencode('Hi, I\'m interested in the Business plan ($199/mo)') }}" target="_blank" class="block w-full rounded-lg bg-purple-600 py-2.5 text-center text-sm font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Get Started') }}
                        </a>
                    </div>
                </div>

                {{-- Agency --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900 flex flex-col">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Agency') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ __('Manage all your clients in one place') }}</p>
                        <p class="mt-6"><span class="text-4xl font-bold">$499</span><span class="text-zinc-500">/{{ __('mo') }}</span></p>
                        <p class="mt-2 text-xs text-zinc-400">{{ __('One tool to run your entire agency') }}</p>
                        <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unlimited pages') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('50,000 AI responses/mo') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('All platforms') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('White-label option') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Dedicated account manager') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unlimited team members') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('SLA & onboarding support') }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-auto pt-8">
                        <a href="https://wa.me/201026361218?text={{ urlencode('Hi, I\'m interested in the Agency plan ($499/mo)') }}" target="_blank" class="block w-full rounded-lg border border-zinc-300 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            {{ __('Get Started') }}
                        </a>
                    </div>
                </div>

                {{-- Enterprise --}}
                <div class="rounded-2xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900 flex flex-col">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Enterprise') }}</h3>
                        <p class="mt-2 text-sm text-zinc-500">{{ __('For agencies & large teams') }}</p>
                        <p class="mt-6"><span class="text-4xl font-bold">{{ __('Custom') }}</span></p>
                        <p class="mt-2 text-xs text-zinc-400">{{ __('Tailored to your scale') }}</p>
                        <ul class="mt-8 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unlimited pages') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unlimited AI responses') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('All platforms') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Custom AI voice & training') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('White-label option') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Unlimited team members') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Dedicated onboarding & SLA') }}
                            </li>
                        </ul>
                    </div>
                    <div class="mt-auto pt-8">
                        <a href="https://wa.me/201026361218?text={{ urlencode('Hi, I\'m interested in an Enterprise plan') }}" target="_blank" class="block w-full rounded-lg border border-zinc-300 py-2.5 text-center text-sm font-semibold transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                            {{ __('Talk to Sales') }}
                        </a>
                    </div>
                </div>

            </div>

            {{-- Trust line --}}
            <p class="mt-10 text-center text-sm text-zinc-500">
                {{ __('All plans include: Unified inbox · AI auto-responder · Lead scoring · Real-time notifications · SSL & 99.9% uptime') }}
            </p>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-t border-zinc-200 py-20 dark:border-zinc-800">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-center text-3xl font-bold">{{ __('Common questions') }}</h2>
            <div class="mt-12 space-y-8">
                <div>
                    <h3 class="font-semibold">{{ __('Do I need a credit card to start?') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('No. The Free plan requires no payment. Paid plans are activated after a conversation with our sales team on WhatsApp.') }}</p>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('What counts as an "AI response"?') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Every time the AI automatically replies to an incoming message counts as one AI response. Manual replies by your team do not count.') }}</p>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('Can I connect Facebook, Instagram, WhatsApp and Telegram at the same time?') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Yes. Each connected page or account counts toward your page limit, regardless of platform. Mix and match as you like.') }}</p>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('Can I upgrade or downgrade anytime?') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Yes. Contact us on WhatsApp and we\'ll switch your plan immediately with prorated billing.') }}</p>
                </div>
                <div>
                    <h3 class="font-semibold">{{ __('What platforms are coming next?') }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('TikTok DMs and LinkedIn Messages are in active development. Enterprise customers get early access.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-purple-600 py-20">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold text-white">{{ __('Still deciding? Let\'s talk.') }}</h2>
            <p class="mt-4 text-purple-200">{{ __('Our team will help you pick the right plan and get you set up in minutes.') }}</p>
            <a href="https://wa.me/201026361218" target="_blank" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-white px-8 py-3 text-sm font-semibold text-purple-700 transition-colors hover:bg-purple-50">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                {{ __('Chat with us on WhatsApp') }}
            </a>
        </div>
    </section>

</x-layouts.marketing>
