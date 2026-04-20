<x-layouts.marketing
    :title="__('One Inbox — Unified Social Inbox with AI Sales Responder')"
    :description="__('Manage all your social conversations from Facebook, Instagram, WhatsApp, and Telegram in one place. AI-powered sales responder closes deals 24/7.')"
>

    {{-- Hero Section --}}
    <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-32">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/2 top-0 -translate-x-1/2 -translate-y-1/4 size-[600px] rounded-full bg-purple-500/10 blur-3xl animate-float"></div>
            <div class="absolute right-0 top-1/4 size-[400px] rounded-full bg-blue-500/10 blur-3xl animate-float" style="animation-delay: 1.5s"></div>
        </div>

        <div class="mx-auto max-w-6xl px-6 text-center">
            <div class="mx-auto max-w-3xl">
                {{-- Badge --}}
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-purple-200 bg-purple-50 px-4 py-1.5 text-sm text-purple-700 dark:border-purple-800 dark:bg-purple-950/50 dark:text-purple-300 animate-fade-in-up">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-purple-400 opacity-75"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-purple-500"></span>
                    </span>
                    {{ __('AI-Powered Sales Automation') }}
                </div>

                {{-- Heading --}}
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl animate-fade-in-up anim-delay-1">
                    {{ __('Every message.') }}<br>
                    <span class="bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">{{ __('One inbox.') }}</span><br>
                    {{ __('Zero missed sales.') }}
                </h1>

                {{-- Subheading --}}
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400 sm:text-xl animate-fade-in-up anim-delay-2">
                    {{ __('Unify Facebook, Instagram, WhatsApp, and Telegram in a single inbox. Let AI handle conversations, qualify leads, and close deals — even while you sleep.') }}
                </p>

                {{-- CTA Buttons --}}
                <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row animate-fade-in-up anim-delay-3">
                    @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="arrow-slide btn-shimmer group w-full rounded-xl bg-purple-600 px-8 py-3.5 text-center font-semibold text-white shadow-lg shadow-purple-500/25 transition-all hover:bg-purple-700 hover:shadow-xl hover:shadow-purple-500/30 sm:w-auto flex items-center justify-center gap-2">
                            {{ __('Get Started Free') }}
                            <svg class="arrow-icon size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    @endif
                    <a href="{{ route('features') }}" class="group flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-purple-700 dark:hover:bg-purple-950/30 dark:hover:text-purple-300 sm:w-auto">
                        {{ __('See How It Works') }}
                        <svg class="size-4 transition-transform group-hover:translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </a>
                </div>

                <p class="mt-4 text-sm text-zinc-500 animate-fade-in anim-delay-4">{{ __('No credit card required') }}</p>
            </div>
        </div>
    </section>

    {{-- Platform Logos Strip --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-8 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <p class="mb-6 text-center text-sm font-medium uppercase tracking-wider text-zinc-500 animate-fade-in">{{ __('All your channels in one place') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-16">
                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 animate-fade-in-up anim-delay-1">
                    <div class="flex size-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <svg class="size-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <span class="font-medium">Facebook</span>
                </div>
                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 animate-fade-in-up anim-delay-2">
                    <div class="flex size-10 items-center justify-center rounded-full bg-pink-100 dark:bg-pink-900/30">
                        <svg class="size-5 text-pink-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </div>
                    <span class="font-medium">Instagram</span>
                </div>
                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 animate-fade-in-up anim-delay-3">
                    <div class="flex size-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg class="size-5 text-green-600" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </div>
                    <span class="font-medium">WhatsApp</span>
                </div>
                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 animate-fade-in-up anim-delay-4">
                    <div class="flex size-10 items-center justify-center rounded-full bg-cyan-100 dark:bg-cyan-900/30">
                        <svg class="size-5 text-cyan-600" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    </div>
                    <span class="font-medium">Telegram</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Everything you need to sell through DMs') }}</h2>
                <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">{{ __('Stop switching between apps. One Inbox brings all your customer conversations together with AI that sells for you.') }}</p>
            </div>

            @php
            $features = [
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />', 'color' => 'purple', 'title' => __('AI Sales Responder'), 'desc' => __('Your AI sales agent qualifies leads, handles objections, and pushes conversations toward a close — in any language, 24/7.')],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />', 'color' => 'blue', 'title' => __('Unified Inbox'), 'desc' => __('Facebook, Instagram, WhatsApp, Telegram — all conversations in one clean interface. Never miss a message again.')],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" />', 'color' => 'orange', 'title' => __('Lead Scoring'), 'desc' => __('AI automatically scores every lead based on their messages. Know who\'s hot and ready to buy at a glance.')],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />', 'color' => 'green', 'title' => __('AI-Human Handoff'), 'desc' => __('AI handles routine conversations. When a human touch is needed, it seamlessly passes the conversation to your team.')],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z" />', 'color' => 'cyan', 'title' => __('Analytics Dashboard'), 'desc' => __('Track AI performance, response times, conversion rates, and lead pipeline — all in real-time dashboards.')],
                ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />', 'color' => 'pink', 'title' => __('Team Collaboration'), 'desc' => __('Invite your team, assign conversations, and collaborate on deals. Everyone stays on the same page.')],
            ];
            @endphp

            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($features as $i => $feature)
                    <div x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0; animation-delay: {{ $i * 100 }}ms"
                         class="card-hover rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-{{ $feature['color'] }}-100 dark:bg-{{ $feature['color'] }}-900/30">
                            <svg class="size-6 text-{{ $feature['color'] }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $feature['icon'] !!}</svg>
                        </div>
                        <h3 class="text-lg font-semibold">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 lg:py-28 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Up and running in minutes') }}</h2>
                <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">{{ __('Three simple steps to automate your sales conversations.') }}</p>
            </div>
            <div class="mt-16 grid gap-8 lg:grid-cols-3">
                @php
                $steps = [
                    ['num' => '1', 'title' => __('Connect Your Channels'), 'desc' => __('Link your Facebook Page, Instagram, WhatsApp Business, or Telegram bot in one click.')],
                    ['num' => '2', 'title' => __('Configure Your AI'), 'desc' => __('Tell the AI about your products, pricing, and sales style. It learns your brand voice.')],
                    ['num' => '3', 'title' => __('Watch Sales Roll In'), 'desc' => __('AI responds instantly, qualifies leads, and guides customers to purchase.')],
                ];
                @endphp
                @foreach($steps as $i => $step)
                    <div x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0; animation-delay: {{ $i * 150 }}ms" class="text-center">
                        <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-purple-600 text-xl font-bold text-white shadow-lg shadow-purple-500/25">{{ $step['num'] }}</div>
                        <h3 class="text-lg font-semibold">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Stats Section --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                {{-- 24/7 --}}
                <div x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0" class="text-center">
                    <p class="text-4xl font-bold text-purple-600 lg:text-5xl">24/7</p>
                    <p class="mt-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('AI Never Sleeps') }}</p>
                </div>
                {{-- <5s count-up --}}
                <div x-data="{ count: 0, done: false }"
                     x-intersect.once="let c = 0; let iv = setInterval(() => { c++; count = c; if(c >= 5) { clearInterval(iv); done = true; } }, 150)"
                     class="text-center animate-fade-in-up" style="opacity:0; animation-delay: 100ms">
                    <p class="text-4xl font-bold text-purple-600 lg:text-5xl">&lt;<span x-text="count">0</span>s</p>
                    <p class="mt-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Average Response Time') }}</p>
                </div>
                {{-- 4 platforms count-up --}}
                <div x-data="{ count: 0 }"
                     x-intersect.once="let c = 0; let iv = setInterval(() => { c++; count = c; if(c >= 4) clearInterval(iv); }, 200)"
                     class="text-center animate-fade-in-up" style="opacity:0; animation-delay: 200ms">
                    <p class="text-4xl font-bold text-purple-600 lg:text-5xl"><span x-text="count">0</span></p>
                    <p class="mt-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Platforms Supported') }}</p>
                </div>
                {{-- 100+ count-up --}}
                <div x-data="{ count: 0 }"
                     x-intersect.once="let c = 0; let iv = setInterval(() => { c += 5; count = c; if(c >= 100) clearInterval(iv); }, 30)"
                     class="text-center animate-fade-in-up" style="opacity:0; animation-delay: 300ms">
                    <p class="text-4xl font-bold text-purple-600 lg:text-5xl"><span x-text="count">0</span>+</p>
                    <p class="mt-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('Languages Supported') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials Section --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 lg:py-28 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Trusted by growing businesses') }}</h2>
            </div>

            @php
            $testimonials = [
                ['text' => __('\"One Inbox transformed how we handle customer messages. Our response time dropped from hours to seconds.\"'), 'name' => 'Sarah M.', 'role' => __('E-commerce Owner')],
                ['text' => __('\"The AI handles 80% of our conversations perfectly. We only step in for complex deals. Game changer.\"'), 'name' => 'Ahmed K.', 'role' => __('Sales Manager')],
                ['text' => __('\"Lead scoring alone was worth it. We can finally focus on the leads that matter most.\"'), 'name' => 'Maria L.', 'role' => __('Agency Owner')],
            ];
            @endphp

            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($testimonials as $i => $t)
                    <div x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0; animation-delay: {{ $i * 100 }}ms"
                         class="card-hover rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center gap-1 text-yellow-400">
                            @for($j = 0; $j < 5; $j++)
                                <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $t['text'] }}</p>
                        <div class="mt-4">
                            <p class="text-sm font-semibold">{{ $t['name'] }}</p>
                            <p class="text-xs text-zinc-500">{{ $t['role'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Frequently asked questions') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ openFaq: null }">
                @php
                $faqs = [
                    [__('What platforms does One Inbox support?'), __('One Inbox supports Facebook Messenger, Instagram DMs, WhatsApp Business, and Telegram. Connect all four from a single dashboard.')],
                    [__('How does the AI sales responder work?'), __('You configure the AI with your product information, pricing, and brand voice. It then automatically responds to incoming messages, qualifies leads based on conversation signals, and guides customers toward a purchase.')],
                    [__('Can I take over from the AI mid-conversation?'), __('Absolutely. You can pause the AI on any conversation and take over manually at any time. When you\'re done, resume AI and it picks up where you left off.')],
                    [__('Is there a free plan?'), __('Yes! Our Starter plan is free and includes 1 connected channel and 100 AI responses per month. Upgrade anytime as your business grows.')],
                    [__('How accurate is the lead scoring?'), __('Our AI analyzes conversation signals like purchase intent, urgency, budget mentions, and engagement to score leads from 0-100. It gets smarter over time as it processes more conversations.')],
                ];
                @endphp

                @foreach($faqs as $index => $faq)
                    <div x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0; animation-delay: {{ $index * 50 }}ms"
                         class="rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <button
                            @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="flex w-full items-center justify-between px-6 py-4 text-left cursor-pointer"
                        >
                            <span class="font-medium">{{ $faq[0] }}</span>
                            <svg class="size-5 flex-shrink-0 text-zinc-400 transition-transform" :class="openFaq === {{ $index }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="openFaq === {{ $index }}" x-collapse>
                            <div class="px-6 pb-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $faq[1] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6" x-data x-intersect.once="$el.classList.add('animate-fade-in-up')" style="opacity:0">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-purple-700 to-blue-600 animate-gradient-shift p-10 text-center text-white sm:p-16 relative">
                {{-- Decorative elements --}}
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-24 -right-24 size-64 rounded-full bg-white/5 blur-2xl"></div>
                    <div class="absolute -bottom-24 -left-24 size-64 rounded-full bg-white/5 blur-2xl"></div>
                </div>

                <div class="relative">
                    <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Ready to stop missing sales?') }}</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('Join businesses that use AI to respond to every message, qualify every lead, and close more deals.') }}</p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-shimmer arrow-slide group w-full rounded-xl bg-white px-8 py-3.5 font-semibold text-purple-700 shadow-lg transition-all hover:bg-purple-50 hover:shadow-xl sm:w-auto flex items-center justify-center gap-2">
                                {{ __('Get Started Free') }}
                                <svg class="arrow-icon size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                            </a>
                        @endif
                        {{-- <a href="{{ route('pricing') }}" class="w-full rounded-xl border-2 border-white/30 px-8 py-3.5 font-semibold text-white transition-all hover:border-white/60 hover:bg-white/10 sm:w-auto">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-4 text-sm text-purple-200">{{ __('No credit card required. Free trial included.') }}</p>
                </div>
            </div>
        </div>
    </section>

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "What platforms does One Inbox support?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "One Inbox supports Facebook Messenger, Instagram DMs, WhatsApp Business, and Telegram. Connect all four from a single dashboard."
            }
        },
        {
            "@type": "Question",
            "name": "How does the AI sales responder work?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "You configure the AI with your product information, pricing, and brand voice. It then automatically responds to incoming messages, qualifies leads based on conversation signals, and guides customers toward a purchase."
            }
        },
        {
            "@type": "Question",
            "name": "Can I take over from the AI mid-conversation?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Absolutely. You can pause the AI on any conversation and take over manually at any time. When you're done, resume AI and it picks up where you left off."
            }
        },
        {
            "@type": "Question",
            "name": "Is there a free plan?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Yes! Our Free plan includes 1 connected channel and 50 AI responses per month. Upgrade anytime as your business grows."
            }
        },
        {
            "@type": "Question",
            "name": "How accurate is the lead scoring?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Our AI analyzes conversation signals like purchase intent, urgency, budget mentions, and engagement to score leads from 0-100. It gets smarter over time as it processes more conversations."
            }
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
