<x-layouts.marketing :title="__('Features') . ' — One Inbox'" :description="__('Explore all features of One Inbox — unified inbox, AI sales responder, lead scoring, analytics, and more.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center animate-fade-in-up">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('Powerful features for modern sales teams') }}</h1>
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                    {{ __('Everything you need to manage conversations and close deals across all social platforms.') }}
                </p>
            </div>

            <div class="mt-20 space-y-24">

                {{-- Feature: Unified Inbox --}}
                <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-left')" style="opacity:0">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ __('Core Feature') }}
                        </div>
                        <h2 class="mt-4 text-3xl font-bold">{{ __('Unified Inbox') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                            {{ __('Manage all your Facebook, Instagram, WhatsApp, and Telegram conversations from a single, clean interface. No more switching between apps.') }}
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Real-time message sync') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Filter by platform, status, or contact') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Send images, files, and emoji') }}
                            </li>
                        </ul>
                    </div>
                    <div class="card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        {{-- Decorative grid dots --}}
                        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                        <div class="pointer-events-none absolute -top-12 -right-12 size-32 rounded-full bg-blue-500/10 blur-2xl"></div>
                        <div class="relative space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30">
                                    <svg class="size-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-600">4</p>
                                    <p class="text-sm text-zinc-500">{{ __('Platforms supported') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Real-time') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Message sync') }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">∞</p>
                                    <p class="text-xs text-zinc-500">{{ __('Messages') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Feature: AI Sales Responder --}}
                <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-right')" style="opacity:0">
                    <div class="order-2 lg:order-1 card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                        <div class="pointer-events-none absolute -bottom-12 -left-12 size-32 rounded-full bg-purple-500/10 blur-2xl"></div>
                        <div class="relative space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/30">
                                    <svg class="size-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-purple-600">24/7</p>
                                    <p class="text-sm text-zinc-500">{{ __('Always responding') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">&lt;5s</p>
                                    <p class="text-xs text-zinc-500">{{ __('Avg response') }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">100+</p>
                                    <p class="text-xs text-zinc-500">{{ __('Languages') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-3 py-1 text-sm font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                            {{ __('AI Powered') }}
                        </div>
                        <h2 class="mt-4 text-3xl font-bold">{{ __('AI Sales Responder') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                            {{ __('Your AI sales agent qualifies leads, handles objections, and guides conversations toward a close. Configure it with your products, pricing, and brand voice.') }}
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Custom brand voice and product knowledge') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Automatic lead qualification') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Seamless human handoff') }}
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Feature: Lead Scoring --}}
                <div class="grid items-center gap-12 lg:grid-cols-2" x-data x-intersect.once="$el.classList.add('animate-slide-in-left')" style="opacity:0">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-300">
                            {{ __('Intelligence') }}
                        </div>
                        <h2 class="mt-4 text-3xl font-bold">{{ __('Lead Scoring & Analytics') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                            {{ __('AI automatically scores every lead based on conversation signals. Track AI performance, response times, conversion rates, and your entire lead pipeline in real-time.') }}
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Automatic lead scoring (0-100)') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Conversion funnel tracking') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="size-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('Platform performance comparison') }}
                            </li>
                        </ul>
                    </div>
                    <div class="card-hover relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="pointer-events-none absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
                        <div class="pointer-events-none absolute -top-12 -left-12 size-32 rounded-full bg-orange-500/10 blur-2xl"></div>
                        <div class="relative space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="flex size-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-900/30">
                                    <svg class="size-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-orange-600">100+</p>
                                    <p class="text-sm text-zinc-500">{{ __('Scoring signals tracked') }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">0-100</p>
                                    <p class="text-xs text-zinc-500">{{ __('Lead score') }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-4 py-3 dark:bg-zinc-800">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ __('Live') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Dashboards') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-purple-700 to-blue-600 animate-gradient-shift p-10 text-center text-white sm:p-16 relative">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-24 -right-24 size-64 rounded-full bg-white/5 blur-2xl"></div>
                    <div class="absolute -bottom-24 -left-24 size-64 rounded-full bg-white/5 blur-2xl"></div>
                </div>
                <div class="relative">
                    <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Ready to get started?') }}</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('Start your free trial today. No credit card required.') }}</p>
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-shimmer arrow-slide group mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-purple-700 shadow-lg transition-all hover:bg-purple-50 hover:shadow-xl">
                            {{ __('Get Started Free') }}
                            <svg class="arrow-icon size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
