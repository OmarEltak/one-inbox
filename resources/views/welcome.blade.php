<x-layouts.marketing
    :title="__('OT1-Pro — The AI Sales Floor for DM-Driven Businesses')"
    :description="__('Plug in Facebook, Instagram, WhatsApp, Telegram. OT1-Pro\'s AI qualifies leads, handles objections, and closes deals while you sleep. Built for agencies and operators.')"
>

    {{-- ───── 1. Hero ───── --}}
    <section class="relative pt-12 pb-20 lg:pt-20 lg:pb-28">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">

                {{-- Left: copy --}}
                <div class="lg:col-span-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">
                        {{ __('The AI sales floor') }}
                    </div>
                    <h1 class="mt-4 text-4xl font-bold leading-[1.05] tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl">
                        {{ __('Your DMs') }}<br>{{ __('close themselves.') }}
                    </h1>
                    <p class="mt-6 max-w-[36ch] text-lg leading-relaxed text-zinc-600">
                        {{ __('Plug in Facebook, Instagram, WhatsApp, and Telegram. Our AI qualifies leads, handles objections, and closes simple deals while you sleep.') }}
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-3">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ __('Start closing on autopilot') }}
                                <svg class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endif
                        <a href="{{ route('pricing') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-6 py-3.5 text-base font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:bg-zinc-50 hover:text-zinc-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-400">
                            {{ __('See pricing') }}
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-zinc-500">
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
    <section class="border-y border-zinc-200 bg-[#FAF8F4] py-20 lg:py-24">
        <div class="mx-auto max-w-5xl px-6">
            <p class="text-center text-[28px] leading-[1.18] text-zinc-900 sm:text-4xl lg:text-[44px]"
               style="font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif; font-weight: 400; font-style: italic;">
                {{ __('Every DM you miss at 2am is a sale your competitor closed at 9.') }}
            </p>
            <div class="mx-auto mt-14 grid max-w-3xl gap-10 sm:grid-cols-2 sm:gap-8">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('The math') }}</div>
                    <h3 class="mt-3 text-base font-semibold text-zinc-900">{{ __("Humans need to sleep. Your customers don't.") }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600">
                        {{ __('Roughly 38% of inbound DMs land outside business hours. Without an answer in 5 minutes, conversion drops by more than half.') }}
                    </p>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ __('The tax') }}</div>
                    <h3 class="mt-3 text-base font-semibold text-zinc-900">{{ __('Six tabs is a job, not a tool.') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-600">
                        {{ __('Switching between WhatsApp, Instagram, Facebook, Telegram, and email costs ~20 minutes per cycle. Repeat thirty times a day and the day is gone.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ───── 3. How it works ───── --}}
    <section id="how" class="bg-white py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('How it works') }}</div>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                    {{ __('One inbox. Every channel. An AI that closes.') }}
                </h2>
                <p class="mt-4 text-base text-zinc-600">
                    {{ __('Three steps. About thirty minutes from sign-up to your first AI-closed deal.') }}
                </p>
            </div>
            <div class="mt-16 grid gap-12 lg:grid-cols-3 lg:gap-10">

                {{-- Step 1 --}}
                <div class="relative">
                    <div class="flex items-baseline gap-3">
                        <span class="font-mono text-3xl font-light text-indigo-600">01</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('Connect') }}</span>
                    </div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('Plug in every channel') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-600">
                        {{ __('Facebook Pages, Instagram Business, WhatsApp Cloud API, Telegram bots, email inbox. OAuth in under 60 seconds per channel.') }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
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
                        <span class="font-mono text-3xl font-light text-indigo-600">02</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('Train') }}</span>
                    </div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('Teach the AI your business') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-600">
                        {{ __('Paste your knowledge, set tone, define escalation rules. The AI talks like a senior rep on your team. Never like a chatbot.') }}
                    </p>
                    <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-xs">
                        <div class="text-zinc-500">{{ __('Persona') }}</div>
                        <div class="mt-1 font-mono text-[11px] leading-relaxed text-indigo-700">{{ __('Warm, technical, never pushy. Defaults to product specs over price. Escalates on legal questions.') }}</div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="relative">
                    <div class="flex items-baseline gap-3">
                        <span class="font-mono text-3xl font-light text-indigo-600">03</span>
                        <span class="text-xs font-bold uppercase tracking-widest text-zinc-500">{{ __('Sleep') }}</span>
                    </div>
                    <h3 class="mt-3 text-xl font-semibold text-zinc-900">{{ __('Wake up to closed deals') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-600">
                        {{ __('The AI replies in ~90 seconds, scores every lead, and hands hot prospects to a human at the right moment. You only touch what is worth touching.') }}
                    </p>
                    <div class="mt-4 flex items-center gap-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                        <div>
                            <div class="text-2xl font-bold text-indigo-900">73%</div>
                            <div class="text-[10px] uppercase tracking-widest text-indigo-700">{{ __('AI deflection') }}</div>
                        </div>
                        <div class="h-8 w-px bg-indigo-200"></div>
                        <div class="text-xs leading-snug text-indigo-900/80">
                            {{ __('Typical share of conversations handled end-to-end by AI without human touch.') }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ───── 4. Built for agencies ───── --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-16 lg:grid-cols-12">

                <div class="lg:col-span-7">
                    @include('partials.home-agency-stack')
                </div>

                <div class="lg:col-span-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('For agencies') }}</div>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                        {{ __('Run 40 client inboxes with the headcount you have today.') }}
                    </h2>
                    <p class="mt-6 text-base leading-relaxed text-zinc-600">
                        {{ __('Each client gets their own workspace, AI persona, and reporting view. No data crossover. White-labeled so the work feels like your team.') }}
                    </p>
                    <ul class="mt-8 space-y-4 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-zinc-700">
                                <strong class="font-semibold text-zinc-900">{{ __('Per-client AI personas') }}</strong>
                                {{ __("trained on each brand's voice, catalog, and policies.") }}
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-zinc-700">
                                <strong class="font-semibold text-zinc-900">{{ __('Flat per-workspace pricing.') }}</strong>
                                {{ __('Not per seat. Scale without per-head sticker shock.') }}
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex size-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-zinc-700">
                                <strong class="font-semibold text-zinc-900">{{ __('Auto-generated client reports.') }}</strong>
                                {{ __('Response time, lead conversion, AI deflection rate. Branded for export.') }}
                            </span>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <a href="{{ route('industry.agencies') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition-colors hover:text-indigo-700">
                            {{ __('Read the agency case study') }}
                            <svg class="size-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ───── 5. vs. the alternatives ───── --}}
    <section class="bg-white py-20 lg:py-28">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">{{ __('vs. The alternatives') }}</div>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                    {{ __('OT1-Pro is the only thing in its lane.') }}
                </h2>
                <p class="mt-4 text-base text-zinc-600">
                    {{ __('Chatbot builders configure flows. CRMs store contacts. Inboxes route messages. OT1-Pro replies, qualifies, and closes.') }}
                </p>
            </div>
            <div class="mt-14 overflow-hidden rounded-2xl border border-zinc-200 shadow-sm">
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50">
                        <tr>
                            <th scope="col" class="px-5 py-4 text-left text-[10px] font-semibold uppercase tracking-widest text-zinc-500">&nbsp;</th>
                            <th scope="col" class="px-5 py-4 text-left text-[10px] font-semibold uppercase tracking-widest text-zinc-500">ManyChat</th>
                            <th scope="col" class="px-5 py-4 text-left text-[10px] font-semibold uppercase tracking-widest text-zinc-500">Trengo</th>
                            <th scope="col" class="bg-indigo-50 px-5 py-4 text-left text-[10px] font-semibold uppercase tracking-widest text-indigo-700">OT1-Pro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-[13px]">
                        <tr>
                            <td class="px-5 py-3.5 text-zinc-700">{{ __('Generative AI sales agent') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">{{ __('Rule-based flows') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">{{ __('Canned replies') }}</td>
                            <td class="bg-indigo-50/60 px-5 py-3.5 font-semibold text-indigo-800">{{ __('Native, every plan') }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3.5 text-zinc-700">{{ __('All 4 channels (FB · IG · WA · TG)') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">{{ __('FB + IG only') }}</td>
                            <td class="px-5 py-3.5 text-zinc-700">{{ __('All 4') }}</td>
                            <td class="bg-indigo-50/60 px-5 py-3.5 font-semibold text-indigo-800">{{ __('All 4 + email') }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3.5 text-zinc-700">{{ __('Lead scoring built in') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">—</td>
                            <td class="px-5 py-3.5 text-zinc-400">—</td>
                            <td class="bg-indigo-50/60 px-5 py-3.5 font-semibold text-indigo-800">{{ __('AI-driven') }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3.5 text-zinc-700">{{ __('Pricing model') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">{{ __('Per contact') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">{{ __('Per seat') }}</td>
                            <td class="bg-indigo-50/60 px-5 py-3.5 font-semibold text-indigo-800">{{ __('Flat per workspace') }}</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3.5 text-zinc-700">{{ __('Free plan (no card)') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">{{ __('Limited') }}</td>
                            <td class="px-5 py-3.5 text-zinc-400">—</td>
                            <td class="bg-indigo-50/60 px-5 py-3.5 font-semibold text-indigo-800">{{ __('Yes') }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-6 text-center">
                <a href="{{ route('vs.manychat') }}" class="text-sm text-zinc-500 transition-colors hover:text-zinc-800">
                    {{ __('See the full comparisons') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ───── 6. Final CTA ───── --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">
                {{ __("Your inbox doesn't need to wait for you.") }}
            </h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">
                {{ __('Start closing on autopilot today. Free for 14 days. No credit card.') }}
            </p>
            <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                @if(Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="group inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        {{ __('Start closing on autopilot') }}
                        <svg class="size-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                @endif
                <a href="{{ route('features') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900">
                    {{ __('See all features') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

</x-layouts.marketing>
