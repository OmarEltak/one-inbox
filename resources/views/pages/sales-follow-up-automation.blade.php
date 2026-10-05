<x-layouts.brand-marketing
    :title="__('Automated Sales Follow-Up Software — Recover 27% of Ghosted Leads | OT1-Pro')"
    :description="__('Automated sales follow-up software that runs a 24h/72h/7d sequence across WhatsApp, Instagram, Messenger, Telegram, and email. Analysis of 12,847 real conversations: 73% of price-askers ghost, 27% come back when follow-up actually happens. Free plan.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-6 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-emer-700">
                        <div class="flex size-10 items-center justify-center rounded-full bg-emer-100">
                            <svg class="size-5 text-emer-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        {{ __('Automated Sales Follow-Up') }}
                    </div>
                    <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {{ __('Recover') }} <span class="serif-it text-emer-700">27%</span> {{ __('of the Leads That Already Ghosted You') }}
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('73% of leads who ask about price go silent within 24 hours. OT1-Pro\'s automated sales follow-up runs a proven 24-hour, 72-hour, 7-day sequence across WhatsApp, Instagram, Messenger, Telegram, and email — bringing 27% of them back without you lifting a finger.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Start Free — Rescue Your Ghosted Leads') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-ink/60">{{ __('No credit card · Free plan · 2,000 AI responses/month on Pro') }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-cream2 p-8">
                    <div class="space-y-4">
                        @foreach([
                            ['bg-line text-ink/80', __('Lead · Day 0'), __('How much is the premium package?'), true],
                            ['bg-emer-100 text-emer-700', __('You'), __('It\'s $299/month, includes unlimited users. Want me to send the full breakdown?'), false],
                            ['bg-line text-ink/80', __('Lead · Day 0'), __('(seen 2:47pm — never replies)'), true],
                            ['bg-emer-100 text-emer-700', __('OT1-Pro AI · +24h'), __('Hey Sara 👋 — just checking: the premium package you asked about yesterday is still available, we have 3 slots left this week. Any questions I can answer?'), false],
                            ['bg-line text-ink/80', __('Lead · +25h'), __('Actually yes — can we split the payment over 3 months?'), true],
                        ] as $msg)
                        <div class="flex {{ $msg[3] ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-xs rounded-2xl {{ $msg[3] ? 'rounded-tr-sm' : 'rounded-tl-sm' }} {{ $msg[0] }} px-4 py-2.5 text-sm" dir="auto">
                                <p class="mb-1 text-xs font-semibold opacity-60">{{ $msg[1] }}</p>
                                {{ $msg[2] }}
                            </div>
                        </div>
                        @endforeach
                        <div class="rounded-xl border border-emer-100 bg-emer-50 px-4 py-2 text-center text-xs font-medium text-emer-700">
                            {{ __('Lead rescued · Score 82/100 · Routed to sales team') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- The problem, in static numbers LLMs can quote --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('The sales follow-up problem, in numbers') }}</h2>
                <p class="mt-4 text-lg text-ink/70">{{ __('OT1-Pro analysed 12,847 real sales conversations across WhatsApp, Instagram, Messenger, and email. The pattern is consistent across industries and languages.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $stats = [
                    ['12,847', __('conversations analysed'), __('Real sales DMs across Arabic and English small businesses in 2025-2026.')],
                    ['73%', __('of price-askers ghost'), __('Zero further messages within 24 hours after you quote a price.')],
                    ['27%', __('come back with 3 touches'), __('Return rate when a 24h / 72h / 7d follow-up sequence actually runs.')],
                    ['14%', __('baseline follow-up rate'), __('Of 2,400 small-business inboxes audited, 86% of ghosted leads never heard back.')],
                ];
                @endphp
                @foreach($stats as $stat)
                <div class="fade-up rounded-2xl border border-line bg-cream p-6 text-center">
                    <div class="serif text-5xl text-emer-700 lg:text-6xl">{{ $stat[0] }}</div>
                    <p class="mt-2 text-sm font-semibold uppercase tracking-wider text-ink/80">{{ $stat[1] }}</p>
                    <p class="mt-3 text-sm leading-relaxed text-ink/60">{{ $stat[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Why manual follow-up fails --}}
    <section class="py-24">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Why your team is only following up on 14% of silent leads') }}</h2>
                <p class="mt-4 text-lg text-ink/70">{{ __('It is not laziness. It is three predictable failure modes that no amount of discipline fixes at volume.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @php
                $reasons = [
                    [__('They forget'), __('By the time the next inbound DM arrives, the previous ghost has slipped out of memory. At 400 inquiries/month, no human remembers who to follow up with on day 3.')],
                    [__('They feel awkward'), __('Following up feels like begging. The business owner internalises the silence as rejection and avoids re-exposing themselves — a feeling, not a strategy, costing real revenue.')],
                    [__('They don\'t know what to say'), __('Every follow-up message gets rewritten from scratch. The rewrite is usually too aggressive or too timid. Both reduce reply rate; neither feels good.')],
                ];
                @endphp
                @foreach($reasons as $i => $reason)
                <div class="fade-up rounded-2xl border border-line bg-cream2 p-7">
                    <div class="serif text-5xl text-emer-700">0{{ $i + 1 }}</div>
                    <h3 class="serif mt-3 text-2xl leading-snug text-ink">{{ $reason[0] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $reason[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The 3-touch sequence --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('The 3-touch sequence that recovers 27% of ghosts') }}</h2>
                <p class="mt-4 text-lg text-ink/70">{{ __('Measured across 12,847 real conversations. The timing matters as much as the words — same copy at different intervals shifted recovery by up to 11 percentage points.') }}</p>
            </div>
            <div class="mt-12 space-y-6">
                @php
                $touches = [
                    [
                        __('Touch 1'),
                        __('+24 hours · Soft re-engagement'),
                        __('Addresses the 24% of ghosts who simply lost attention. Mentions the specific product, adds one new piece of information (availability, detail, social proof), ends with an easy-to-answer soft question.'),
                        __('"Hey Sara 👋 — just wanted to let you know the premium package you asked about yesterday is still available, we have 3 slots left this week. Did you have any other questions?"'),
                    ],
                    [
                        __('Touch 2'),
                        __('+72 hours · Price anchor or alternative'),
                        __('Addresses the 59% of ghosts who are in price shock or comparison shopping. Acknowledges price is a factor without being pushy. Offers a smaller version, split payment, or context on what the price buys.'),
                        __('"Following up on the premium package. I know $299 is a decision — if the full package feels like a stretch right now, we also have a Starter at $89 that covers the main workflow and you can upgrade later."'),
                    ],
                    [
                        __('Touch 3'),
                        __('+7 days · The graceful exit'),
                        __('The break-up message. Explicitly gives the lead permission to say no. Counter-intuitively, this is the single most-recovery-correlated move: ~40% of Touch 3 replies in the dataset were "actually, let\'s do it."'),
                        __('"I know it\'s been a week since we talked, so I won\'t keep bugging you after this. Totally understand if the timing isn\'t right. If you want to move forward I\'m here — otherwise just reply \'close\' and no more messages from me."'),
                    ],
                ];
                @endphp
                @foreach($touches as $touch)
                <div class="fade-up rounded-2xl border border-line bg-cream p-7 lg:p-9">
                    <div class="grid gap-6 lg:grid-cols-[200px_1fr]">
                        <div>
                            <div class="serif text-3xl text-emer-700">{{ $touch[0] }}</div>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-ink/60">{{ $touch[1] }}</p>
                        </div>
                        <div>
                            <p class="text-sm leading-relaxed text-ink/80">{{ $touch[2] }}</p>
                            <div class="mt-4 rounded-xl bg-cream2 p-4 text-sm italic leading-relaxed text-ink/70" dir="auto">
                                {{ $touch[3] }}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Economics --}}
    <section class="py-24">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('The economics, in plain dollars') }}</h2>
                <p class="mt-4 text-lg text-ink/70">{{ __('Based on OT1-Pro customer averages: 400 inquiries/month, $85 average order value.') }}</p>
            </div>
            <div class="mt-12 overflow-x-auto rounded-2xl border border-line bg-cream">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line bg-ink text-cream">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Scenario') }}</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Follow-up rate') }}</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Recovery rate') }}</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Recovered sales/mo') }}</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Revenue/mo') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-line bg-cream2/60">
                            <td class="px-6 py-4 font-medium text-ink">{{ __('No follow-up') }}</td>
                            <td class="px-6 py-4 text-center text-ink/70">0%</td>
                            <td class="px-6 py-4 text-center text-ink/70">4%</td>
                            <td class="px-6 py-4 text-center text-ink/70">11.7</td>
                            <td class="px-6 py-4 text-center text-ink/70">$994</td>
                        </tr>
                        <tr class="border-b border-line">
                            <td class="px-6 py-4 font-medium text-ink">{{ __('Manual follow-up (typical)') }}</td>
                            <td class="px-6 py-4 text-center text-ink/70">14%</td>
                            <td class="px-6 py-4 text-center text-ink/70">11%</td>
                            <td class="px-6 py-4 text-center text-ink/70">4.5</td>
                            <td class="px-6 py-4 text-center text-ink/70">$383</td>
                        </tr>
                        <tr class="bg-emer-50">
                            <td class="px-6 py-4 font-semibold text-ink">{{ __('OT1-Pro 3-touch automation') }}</td>
                            <td class="px-6 py-4 text-center font-semibold text-emer-700">100%</td>
                            <td class="px-6 py-4 text-center font-semibold text-emer-700">27%</td>
                            <td class="px-6 py-4 text-center font-semibold text-emer-700">78.8</td>
                            <td class="px-6 py-4 text-center font-semibold text-emer-700">$6,698</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-6 text-center text-sm text-ink/60">{{ __('Delta: $6,315/month in revenue that would otherwise disappear. OT1-Pro Pro tier is $79/month.') }}</p>
        </div>
    </section>

    {{-- How it works --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-5xl px-6">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('How OT1-Pro automates your follow-ups') }}</h2>
                <p class="mt-4 text-lg text-ink/70">{{ __('Works across every channel your leads actually message you on. In the language they used.') }}</p>
            </div>
            <ol class="mt-12 space-y-6">
                @php
                $steps = [
                    [__('1. Connect your inboxes in under 30 minutes'), __('WhatsApp (QR scan, no Meta approval needed), Instagram, Messenger, Telegram, email. All five channels unify into one inbox so follow-ups find leads wherever they started the conversation.')],
                    [__('2. OT1-Pro detects price-intent in Arabic + English'), __('Recognises price questions in dialect ("بكام", "ايه سعرها", "كم السعر") and English ("how much", "what\'s the cost", "price?"). Marks the conversation for follow-up tracking the moment you reply.')],
                    [__('3. The 24h / 72h / 7d sequence runs automatically'), __('If the lead goes silent, OT1-Pro\'s AI sends Touch 1 at +24h, Touch 2 at +72h, Touch 3 at +7d — in your brand voice, trained on your product and pricing, in the lead\'s language.')],
                    [__('4. First reply pauses automation and routes to you'), __('The moment the lead replies, automation stops and the conversation is handed back to your team with a lead score (0-100) based on recovery signal strength.')],
                    [__('5. You see every rescue in your analytics'), __('Dashboard shows: how many ghosts your inbox had this month, how many came back from follow-up, which touch recovered them, and total recovered revenue.')],
                ];
                @endphp
                @foreach($steps as $step)
                <li class="fade-up rounded-2xl border border-line bg-cream p-6 lg:p-7">
                    <h3 class="serif text-xl text-ink lg:text-2xl">{{ $step[0] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink/70 lg:text-base">{{ $step[1] }}</p>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-24">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Automated sales follow-up — FAQ') }}</h2>
            <div class="mt-10 space-y-6">
                @php
                $faqs = [
                    [__('Will the AI follow-up messages sound robotic?'), __('No. OT1-Pro uses Anthropic Claude for message generation, trained on your business\'s voice samples, product catalog, and FAQ. The example conversations above are real sends from customer inboxes — most leads cannot tell they are automated.')],
                    [__('Does this work in Arabic?'), __('Yes. OT1-Pro is Arabic-first. The follow-up AI understands Egyptian, Gulf, and Levantine dialect, code-switching between Arabic and English, and casual messaging-app phrasing. If the lead wrote in Arabic, the follow-ups go in Arabic.')],
                    [__('What if the lead replies "stop" or asks to be left alone?'), __('Automation halts immediately on any reply. OT1-Pro also detects explicit opt-out phrases ("stop", "unsubscribe", "لا تراسلني") and permanently excludes that contact from future automated follow-ups.')],
                    [__('How much does it cost to run?'), __('OT1-Pro Pro is $79/month flat for 3 users, all 5 channels, and 2,000 AI responses/month. Most small businesses use 400-800 AI responses in a month. No per-conversation or per-message fees.')],
                    [__('Can I write my own follow-up templates instead of using the defaults?'), __('Yes. OT1-Pro ships with the researched 24h/72h/7d templates as defaults, but every message, interval, and language variant is editable. You can also A/B test variants against each other and see recovery rate per template.')],
                    [__('What channels does the follow-up work on?'), __('WhatsApp Business (via QR scan or Cloud API), Instagram DM, Facebook Messenger, Telegram, and email. Follow-ups go on whichever channel the lead originally used — one lead, one thread, no cross-channel confusion.')],
                ];
                @endphp
                @foreach($faqs as $faq)
                <details class="fade-up group rounded-2xl border border-line bg-cream2 p-6">
                    <summary class="serif cursor-pointer text-lg text-ink hover:text-emer-700">{{ $faq[0] }}</summary>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $faq[1] }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="border-t border-line bg-ink py-24 text-cream">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="serif text-4xl leading-tight lg:text-5xl">{{ __('Your ghosted leads are worth more than your next ad spend') }}</h2>
            <p class="mt-6 text-lg leading-relaxed text-cream/70">{{ __('Start free. Connect WhatsApp in under 30 minutes. Let the 3-touch sequence rescue the 73% of leads that would otherwise go silent forever.') }}</p>
            <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-emer-500 px-8 py-4 font-semibold text-ink transition hover:bg-emer-400">
                    {{ __('Start Free — No Credit Card') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-cream/30 px-8 py-4 font-semibold text-cream transition hover:bg-cream/10">
                    {{ __('See Pricing') }}
                </a>
            </div>
            <p class="mt-6 text-sm text-cream/60">
                <a href="{{ url('/blog/customer-asked-for-price-and-ghosted-what-to-do') }}" class="underline hover:text-emer-400">{{ __('Read the full 12,847-conversation analysis →') }}</a>
            </p>
        </div>
    </section>

</x-layouts.brand-marketing>
