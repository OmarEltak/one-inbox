<x-layouts.marketing
    :title="__('OT1-Pro — The AI Sales Floor for DM-Driven Businesses')"
    :description="__('Plug in Facebook, Instagram, WhatsApp, Telegram. OT1-Pro\'s AI qualifies leads, handles objections, and closes deals while you sleep. Built for agencies and operators.')"
>

    {{-- ───── 1. Hero ───── --}}
    <section class="relative pt-20 pb-24 lg:pt-32 lg:pb-36">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">

                {{-- Left: copy --}}
                <div class="lg:col-span-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">
                        {{ __('The AI sales floor') }}
                    </div>
                    <h1 class="mt-5 text-5xl font-bold leading-[1.02] tracking-tight text-zinc-900 sm:text-6xl lg:text-7xl">
                        {{ __('Your DMs') }}<br>{{ __('close themselves.') }}
                    </h1>
                    <p class="mt-7 max-w-[36ch] text-lg leading-relaxed text-zinc-600 lg:text-xl">
                        {{ __('Plug in Facebook, Instagram, WhatsApp, and Telegram. Our AI qualifies leads, handles objections, and closes simple deals while you sleep.') }}
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-3">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-4 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ __('Start closing on autopilot') }}
                                <svg class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endif
                        <a href="{{ route('pricing') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-7 py-4 text-base font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-400">
                            {{ __('See pricing') }}
                        </a>
                    </div>
                    <p class="mt-5 text-sm text-zinc-500">
                        {{ __('No credit card. 14-day Pro trial.') }}
                    </p>
                </div>

                {{-- Right: live inbox demo --}}
                <div class="lg:col-span-7">
                    @include('partials.home-inbox-demo')
                </div>

            </div>
        </div>
    </section>

    {{-- ───── 2. Editorial pull-quote: the problem ───── --}}
    <section class="border-y border-zinc-200 bg-[#FAF8F4] py-24 lg:py-32">
        <div class="mx-auto max-w-5xl px-6">
            <p class="text-center text-[32px] leading-[1.18] text-zinc-900 sm:text-4xl lg:text-[52px]"
               style="font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif; font-weight: 400; font-style: italic;">
                {{ __('Every DM you miss at 2am is a sale your competitor closed at 9.') }}
            </p>
            <div class="mx-auto mt-16 grid max-w-3xl gap-10 sm:grid-cols-2 sm:gap-12">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('The math') }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-zinc-900">{{ __("Humans need to sleep. Your customers don't.") }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('Roughly 38% of inbound DMs land outside business hours. Without an answer in 5 minutes, conversion drops by more than half.') }}
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('The tax') }}</div>
                    <h3 class="mt-3 text-lg font-semibold text-zinc-900">{{ __('Six tabs is a job, not a tool.') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('Switching between WhatsApp, Instagram, Facebook, Telegram, and email costs ~20 minutes per cycle. Repeat thirty times a day and the day is gone.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ───── 3. How it works ───── --}}
    <section id="how" class="bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('How it works') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('One inbox. Every channel. An AI that closes.') }}
                </h2>
                <p class="mt-5 text-lg text-zinc-600">
                    {{ __('Three steps. About thirty minutes from sign-up to your first AI-closed deal.') }}
                </p>
            </div>
            <div class="mt-20 grid gap-14 lg:grid-cols-3 lg:gap-12">

                {{-- Step 1 --}}
                <div class="relative">
                    <div class="flex items-baseline gap-3">
                        <span class="font-mono text-4xl font-light text-indigo-600">01</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('Connect') }}</span>
                    </div>
                    <h3 class="mt-4 text-2xl font-semibold text-zinc-900">{{ __('Plug in every channel') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('Facebook Pages, Instagram Business, WhatsApp Cloud API, Telegram bots, email inbox. OAuth in under 60 seconds per channel.') }}
                    </p>
                    <div class="mt-5 flex flex-wrap gap-1.5">
                        @foreach([['FB','#1877F2'],['IG','#E1306C'],['WA','#25D366'],['TG','#0088CC'],['Email','#F97316']] as $ch)
                            <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-white px-2 py-1 text-[10px] font-bold uppercase tracking-wider"
                                  style="color: {{ $ch[1] }};">
                                <span class="size-1 rounded-full" style="background: {{ $ch[1] }};"></span>
                                {{ $ch[0] }}
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="relative">
                    <div class="flex items-baseline gap-3">
                        <span class="font-mono text-4xl font-light text-indigo-600">02</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('Train') }}</span>
                    </div>
                    <h3 class="mt-4 text-2xl font-semibold text-zinc-900">{{ __('Teach the AI your business') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('Paste your knowledge, set tone, define escalation rules. The AI talks like a senior rep on your team. Never like a chatbot.') }}
                    </p>
                    <div class="mt-5 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-xs">
                        <div class="text-zinc-500 text-[11px] uppercase tracking-widest font-semibold">{{ __('Persona') }}</div>
                        <div class="mt-2 font-mono text-[12px] leading-relaxed text-indigo-700">{{ __('Warm, technical, never pushy. Defaults to product specs over price. Escalates on legal questions.') }}</div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="relative">
                    <div class="flex items-baseline gap-3">
                        <span class="font-mono text-4xl font-light text-indigo-600">03</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('Sleep') }}</span>
                    </div>
                    <h3 class="mt-4 text-2xl font-semibold text-zinc-900">{{ __('Wake up to closed deals') }}</h3>
                    <p class="mt-3 text-[15px] leading-relaxed text-zinc-600">
                        {{ __('The AI replies in ~90 seconds, scores every lead, and hands hot prospects to a human at the right moment. You only touch what is worth touching.') }}
                    </p>
                    <div class="mt-5 flex items-center gap-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                        <div>
                            <div class="text-3xl font-bold text-indigo-900">73%</div>
                            <div class="text-[10px] uppercase tracking-widest font-semibold text-indigo-700">{{ __('AI deflection') }}</div>
                        </div>
                        <div class="h-10 w-px bg-indigo-200"></div>
                        <div class="text-xs leading-snug text-indigo-900/80">
                            {{ __('Typical share of conversations handled end-to-end by AI without human touch.') }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ───── 4. Built for agencies ───── --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-24 lg:py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-16 lg:grid-cols-12">

                <div class="lg:col-span-7">
                    @include('partials.home-agency-stack')
                </div>

                <div class="lg:col-span-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('For agencies') }}</div>
                    <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                        {{ __('Run 40 client inboxes with the headcount you have today.') }}
                    </h2>
                    <p class="mt-6 text-lg leading-relaxed text-zinc-600">
                        {{ __('Each client gets their own workspace, AI persona, and reporting view. No data crossover. White-labeled so the work feels like your team.') }}
                    </p>
                    <ul class="mt-10 space-y-5 text-[15px]">
                        <li class="flex items-start gap-3">
                            <span class="mt-1 flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-zinc-700">
                                <strong class="font-semibold text-zinc-900">{{ __('Per-client AI personas') }}</strong>
                                {{ __("trained on each brand's voice, catalog, and policies.") }}
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-1 flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-zinc-700">
                                <strong class="font-semibold text-zinc-900">{{ __('Flat per-workspace pricing.') }}</strong>
                                {{ __('Not per seat. Scale without per-head sticker shock.') }}
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-1 flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-zinc-700">
                                <strong class="font-semibold text-zinc-900">{{ __('Auto-generated client reports.') }}</strong>
                                {{ __('Response time, lead conversion, AI deflection rate. Branded for export.') }}
                            </span>
                        </li>
                    </ul>
                    <div class="mt-10">
                        <a href="{{ route('industry.agencies') }}" class="inline-flex items-center gap-2 text-base font-semibold text-indigo-600 transition-colors hover:text-indigo-700">
                            {{ __('Read the agency case study') }}
                            <svg class="size-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ───── 5. Industries covered ───── --}}
    <section class="bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('Industries') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Built for businesses that sell through conversation.') }}
                </h2>
                <p class="mt-5 text-lg text-zinc-600">
                    {{ __('OT1-Pro is trained on the patterns of these industries. Pick yours to see how.') }}
                </p>
            </div>
            <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:gap-5">
                @php
                $industries = [
                    ['route' => 'industry.agencies',      'label' => __('Agencies'),     'tag' => __('Multi-client'),       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>'],
                    ['route' => 'industry.ecommerce',     'label' => __('E-commerce'),   'tag' => __('Cart recovery'),      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>'],
                    ['route' => 'industry.real-estate',   'label' => __('Real Estate'),  'tag' => __('Lead qualification'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>'],
                    ['route' => 'industry.restaurants',   'label' => __('Restaurants'),  'tag' => __('Reservations'),       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>'],
                    ['route' => 'industry.education',     'label' => __('Education'),    'tag' => __('Enrollment Q&A'),     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>'],
                ];
                @endphp
                @foreach($industries as $ind)
                    <a href="{{ route($ind['route']) }}"
                       class="group relative rounded-xl border border-zinc-200 bg-white p-6 transition-all hover:border-indigo-300 hover:shadow-md">
                        <svg class="size-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">{!! $ind['icon'] !!}</svg>
                        <h3 class="mt-5 text-base font-semibold text-zinc-900">{{ $ind['label'] }}</h3>
                        <p class="mt-1 text-xs text-zinc-500">{{ $ind['tag'] }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 opacity-0 transition-opacity group-hover:opacity-100">
                            {{ __('Read more') }}
                            <svg class="size-3 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───── 6. Pricing teaser ───── --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-24 lg:py-32">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('Pricing') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Flat per workspace. Never per seat.') }}
                </h2>
                <p class="mt-5 text-lg text-zinc-600">
                    {{ __('Start free. Upgrade only when the AI is closing more than your time saves you.') }}
                </p>
            </div>
            <div class="mt-16 grid gap-6 lg:grid-cols-3">

                {{-- Free --}}
                <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8">
                    <div class="text-sm font-semibold uppercase tracking-widest text-zinc-500">{{ __('Free') }}</div>
                    <div class="mt-5 flex items-baseline gap-2">
                        <span class="text-5xl font-bold text-zinc-900">$0</span>
                        <span class="text-sm text-zinc-500">{{ __('forever') }}</span>
                    </div>
                    <p class="mt-4 text-sm text-zinc-600">{{ __('For solo operators kicking the tires.') }}</p>
                    <ul class="mt-8 space-y-3 text-sm">
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('1 workspace · 2 channels') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('500 AI replies / month') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Basic lead scoring') }}</span></li>
                    </ul>
                    <a href="{{ route('register') }}" class="mt-10 block rounded-xl border border-zinc-300 px-5 py-3 text-center text-sm font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-900">
                        {{ __('Start free') }}
                    </a>
                </div>

                {{-- Pro (recommended) --}}
                <div class="relative flex flex-col rounded-2xl border-2 border-indigo-600 bg-white p-8 shadow-md shadow-indigo-100">
                    <span class="absolute -top-3 left-8 inline-flex items-center rounded-full bg-indigo-600 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white">{{ __('Most popular') }}</span>
                    <div class="text-sm font-semibold uppercase tracking-widest text-indigo-600">{{ __('Pro') }}</div>
                    <div class="mt-5 flex items-baseline gap-2">
                        <span class="text-5xl font-bold text-zinc-900">$79</span>
                        <span class="text-sm text-zinc-500">/ {{ __('workspace / month') }}</span>
                    </div>
                    <p class="mt-4 text-sm text-zinc-600">{{ __('For operators running real volume.') }}</p>
                    <ul class="mt-8 space-y-3 text-sm">
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('All 5 channels · unlimited contacts') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Unlimited AI replies') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Advanced AI persona + handoff rules') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Team seats included') }}</span></li>
                    </ul>
                    <a href="{{ route('register') }}" class="mt-10 block rounded-xl bg-indigo-600 px-5 py-3 text-center text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md">
                        {{ __('Start 14-day trial') }}
                    </a>
                </div>

                {{-- Agency --}}
                <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-8">
                    <div class="text-sm font-semibold uppercase tracking-widest text-zinc-500">{{ __('Agency') }}</div>
                    <div class="mt-5 flex items-baseline gap-2">
                        <span class="text-5xl font-bold text-zinc-900">{{ __('Custom') }}</span>
                    </div>
                    <p class="mt-4 text-sm text-zinc-600">{{ __('For multi-client operations.') }}</p>
                    <ul class="mt-8 space-y-3 text-sm">
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Unlimited workspaces') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Per-client AI personas + reports') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('White-label exports') }}</span></li>
                        <li class="flex items-start gap-2"><span class="mt-0.5 text-indigo-600">✓</span> <span class="text-zinc-700">{{ __('Dedicated onboarding') }}</span></li>
                    </ul>
                    <a href="{{ route('contact') }}" class="mt-10 block rounded-xl border border-zinc-300 px-5 py-3 text-center text-sm font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-900">
                        {{ __('Talk to us') }}
                    </a>
                </div>
            </div>
            <p class="mx-auto mt-10 max-w-xl text-center text-sm text-zinc-500">
                {{ __('Full plan details, channel limits, and add-ons') }}
                <a href="{{ route('pricing') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ __('on the pricing page') }} →</a>
            </p>
        </div>
    </section>

    {{-- ───── 7. vs. the alternatives ───── --}}
    <section class="bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('vs. The alternatives') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('OT1-Pro is the only thing in its lane.') }}
                </h2>
                <p class="mt-5 text-lg text-zinc-600">
                    {{ __('Chatbot builders configure flows. CRMs store contacts. Inboxes route messages. OT1-Pro replies, qualifies, and closes.') }}
                </p>
            </div>
            <div class="mt-16 overflow-hidden rounded-2xl border border-zinc-200 shadow-sm">
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50">
                        <tr>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-semibold uppercase tracking-widest text-zinc-500">&nbsp;</th>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-semibold uppercase tracking-widest text-zinc-500">ManyChat</th>
                            <th scope="col" class="px-6 py-5 text-left text-[11px] font-semibold uppercase tracking-widest text-zinc-500">Trengo</th>
                            <th scope="col" class="bg-indigo-50 px-6 py-5 text-left text-[11px] font-semibold uppercase tracking-widest text-indigo-700">OT1-Pro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-sm">
                        <tr>
                            <td class="px-6 py-4 text-zinc-700">{{ __('Generative AI sales agent') }}</td>
                            <td class="px-6 py-4 text-zinc-400">{{ __('Rule-based flows') }}</td>
                            <td class="px-6 py-4 text-zinc-400">{{ __('Canned replies') }}</td>
                            <td class="bg-indigo-50/60 px-6 py-4 font-semibold text-indigo-800">{{ __('Native, every plan') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-zinc-700">{{ __('All 4 channels (FB · IG · WA · TG)') }}</td>
                            <td class="px-6 py-4 text-zinc-400">{{ __('FB + IG only') }}</td>
                            <td class="px-6 py-4 text-zinc-700">{{ __('All 4') }}</td>
                            <td class="bg-indigo-50/60 px-6 py-4 font-semibold text-indigo-800">{{ __('All 4 + email') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-zinc-700">{{ __('Lead scoring built in') }}</td>
                            <td class="px-6 py-4 text-zinc-400">—</td>
                            <td class="px-6 py-4 text-zinc-400">—</td>
                            <td class="bg-indigo-50/60 px-6 py-4 font-semibold text-indigo-800">{{ __('AI-driven') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-zinc-700">{{ __('Pricing model') }}</td>
                            <td class="px-6 py-4 text-zinc-400">{{ __('Per contact') }}</td>
                            <td class="px-6 py-4 text-zinc-400">{{ __('Per seat') }}</td>
                            <td class="bg-indigo-50/60 px-6 py-4 font-semibold text-indigo-800">{{ __('Flat per workspace') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-zinc-700">{{ __('Free plan (no card)') }}</td>
                            <td class="px-6 py-4 text-zinc-400">{{ __('Limited') }}</td>
                            <td class="px-6 py-4 text-zinc-400">—</td>
                            <td class="bg-indigo-50/60 px-6 py-4 font-semibold text-indigo-800">{{ __('Yes') }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-8 text-center">
                <a href="{{ route('vs.manychat') }}" class="text-base text-zinc-500 transition-colors hover:text-zinc-800">
                    {{ __('See the full comparisons') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ───── 8. FAQ ───── --}}
    <section class="border-t border-zinc-200 bg-[#FAF8F4] py-24 lg:py-32">
        <div class="mx-auto max-w-4xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('FAQ') }}</div>
                <h2 class="mt-4 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                    {{ __('Questions we hear every week.') }}
                </h2>
            </div>
            @php
            $faqs = [
                [
                    __('Will the AI sound like a chatbot?'),
                    __('No. The AI is generative and trained on your business voice. You set the persona, tone, and escalation rules. Customers can\'t tell they\'re not talking to a senior rep on your team. If you set the persona well, neither can your competitors.'),
                ],
                [
                    __('What happens when the AI makes a mistake?'),
                    __('Every AI reply ships with a confidence score. Below your threshold, it doesn\'t send — it queues for human review. Above the threshold, it sends and you can correct in-line. Mistakes feed back into the persona training automatically.'),
                ],
                [
                    __('How long to get started?'),
                    __('About thirty minutes. OAuth your first channel in under a minute. Paste your business knowledge into the persona builder. Set escalation rules. Done. The AI starts replying to incoming DMs immediately.'),
                ],
                [
                    __('Can I use OT1-Pro for multiple client brands?'),
                    __('Yes. Each client gets their own workspace with isolated data, its own AI persona, and its own reporting view. White-label exports so client reports come out branded as your agency, not OT1-Pro.'),
                ],
                [
                    __('Does it work in Arabic?'),
                    __('Yes. The AI handles Arabic natively (also German, Spanish, French, Portuguese, Turkish, and more). Customers DM in their language; the AI replies in their language; you read the conversation in any language you want.'),
                ],
                [
                    __('Can I cancel anytime?'),
                    __('Yes. Monthly billing, no annual commitment required. Cancel from the billing page in two clicks. Your data exports to CSV before deletion.'),
                ],
            ];
            @endphp
            <dl class="mx-auto mt-16 max-w-3xl divide-y divide-zinc-200" x-data="{ open: 0 }">
                @foreach($faqs as $i => [$q, $a])
                    <div class="py-6">
                        <dt>
                            <button type="button"
                                    @click="open = (open === {{ $i }} ? null : {{ $i }})"
                                    :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
                                    class="flex w-full items-start justify-between gap-6 text-left">
                                <span class="text-lg font-semibold text-zinc-900">{{ $q }}</span>
                                <span class="mt-1 flex size-6 flex-shrink-0 items-center justify-center rounded-full border border-zinc-300 text-zinc-500 transition-all"
                                      :class="open === {{ $i }} ? 'rotate-45 border-indigo-600 text-indigo-600 bg-indigo-50' : ''">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </span>
                            </button>
                        </dt>
                        <dd x-show="open === {{ $i }}" x-collapse
                            class="mt-4 pe-10 text-[15px] leading-relaxed text-zinc-600">
                            {{ $a }}
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-12 text-center text-sm text-zinc-500">
                {{ __("Didn't answer your question?") }}
                <a href="{{ route('contact') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">{{ __('Talk to us') }} →</a>
            </p>
        </div>
    </section>

    {{-- ───── 9. Final CTA ───── --}}
    <section class="border-t border-zinc-200 bg-white py-24 lg:py-32">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                {{ __("Your inbox doesn't need to wait for you.") }}
            </h2>
            <p class="mx-auto mt-6 max-w-xl text-lg text-zinc-600 lg:text-xl">
                {{ __('Start closing on autopilot today. Free for 14 days. No credit card.') }}
            </p>
            <div class="mt-12 flex flex-wrap items-center justify-center gap-3">
                @if(Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-8 py-4 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        {{ __('Start closing on autopilot') }}
                        <svg class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                @endif
                <a href="{{ route('features') }}" class="text-base font-medium text-zinc-600 transition-colors hover:text-zinc-900">
                    {{ __('See all features') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

</x-layouts.marketing>
