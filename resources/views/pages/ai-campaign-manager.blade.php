{{--
    Route: '/' → name('home')

    Phase-2 brand homepage. Ported from homepage-redesign-preview.html.
    Preserves every section: hero, marquee, before/after tabs, numbers,
    how-it-works, feature blocks, testimonials (flip cards), pricing
    volume-slider + plans, FAQ, final CTA. Uses <x-layouts.brand-marketing>
    so the SEO/meta/JSON-LD wrapper is unchanged and the shared brand nav
    + footer render.

    All interactivity (inbox ticker, composer, AI live-typing, flip cards,
    pricing slider, counters, before/after tabs, fade-up) uses the same
    inline JS as the preview. Do NOT extract to app.js — it is coupled
    to this page's DOM ids.
--}}
<x-layouts.brand-marketing
    :title="__('OT1-Pro — Every message. One closing inbox.')"
    :description="__('Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI in your voice, closing while you sleep.')"
>

@push('head')
<style>
    /* Page-scoped styles that override the mobile-mockup grid.
       Same rules as homepage-redesign-preview.html — kept inline so mobile
       breakpoints stay attached to this view. */
    @media (max-width: 767px) {
        .mockup-rail { display: none !important; }
        .mockup-frame { display: block !important; height: 380px !important; }
        .mockup-chat { width: 100% !important; }
        .hero-mock-wrap { width: 100% !important; }
        .hero-mock-card { width: 100% !important; max-width: none !important; }
    }
    @media (max-width: 640px) {
        .hero-h1 { font-size: 3.25rem !important; line-height: 1 !important; }
        .hero-mock-glow { display: none; }
        .float-stat { position: static !important; margin-top: -0.75rem; margin-left: auto; margin-right: auto; width: fit-content; }
        .mockup-frame { height: 340px !important; }
        .mockup-chat .msg { max-width: 88% !important; }
        .mockup-chat-header { flex-wrap: wrap; }
        .mockup-badge { display: none !important; }
        .mockup-chat-header .subline { flex-wrap: wrap; gap: 0.35rem; }
        #composer-input { font-size: 13px !important; padding: 0.55rem 0.75rem !important; }
    }
    @media (max-width: 380px) {
        .hero-h1 { font-size: 2.75rem !important; }
        .mockup-frame { height: 320px !important; }
        .mockup-chat .msg { max-width: 92% !important; font-size: 12px !important; }
    }
    .ai-typing .dotb { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: white; margin-right: 3px; animation: typing 1.2s infinite; }
    .ai-typing .dotb:nth-child(2) { animation-delay: 0.15s; }
    .ai-typing .dotb:nth-child(3) { animation-delay: 0.3s; }
</style>
@endpush

{{-- ═══════ HERO ═══════ --}}
<section class="relative bg-ink text-cream overflow-hidden pt-32 pb-24 grain">
    <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-12 gap-16 items-center relative z-10">
        <div class="lg:col-span-6">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-emer-400 mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-emer-400"></span>
                <span>{{ __('Built for founders who close in DMs') }}</span>
            </div>
            <h1 class="hero-h1 serif text-6xl lg:text-7xl xl:text-[5.5rem] leading-[0.95] mb-8">
                {{ __('Every message.') }}<br>
                <span class="serif-it text-emer-400">{{ __('One closing') }}</span><br>
                {{ __('inbox.') }}
            </h1>
            <p class="text-lg text-cream/70 max-w-md mb-10 leading-relaxed">
                {{ __('Facebook, Instagram, WhatsApp, Telegram — every customer message in one place, answered by an AI that sounds like') }}
                <em class="serif-it text-cream not-italic">{{ __('you') }}</em>,
                {{ __('trained on') }}
                <em class="serif-it text-cream not-italic">{{ __('your') }}</em>
                {{ __('business, closing while you sleep.') }}
            </p>
            <div class="flex flex-wrap items-center gap-4 mb-10">
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-6 py-3.5 rounded-full font-semibold hover:bg-emer-400 transition">
                        {{ __('Start free — no card') }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
                <a href="#howitworks" class="inline-flex items-center gap-2 text-cream/90 font-medium hover:text-cream transition">
                    <span class="w-8 h-8 rounded-full border border-cream/30 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </span>
                    {{ __('See how it works') }}
                </a>
            </div>
            <div class="flex items-center gap-6 text-xs text-cream/50">
                <div class="flex -space-x-2">
                    <img class="w-8 h-8 rounded-full border-2 border-ink object-cover" src="https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=80&h=80&fit=crop&auto=format" alt="Leather bag shop">
                    <img class="w-8 h-8 rounded-full border-2 border-ink object-cover" src="https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=80&h=80&fit=crop&auto=format" alt="Spice import">
                    <img class="w-8 h-8 rounded-full border-2 border-ink object-cover" src="https://images.unsplash.com/photo-1542060748-10c28b62716f?w=80&h=80&fit=crop&auto=format" alt="Vintage clothing">
                </div>
                <span>{{ __('Trusted by shops from Cairo to Dubai to London') }}</span>
            </div>
        </div>

        <div class="hero-mock-wrap lg:col-span-6 relative">
            <div class="hero-mock-glow absolute -inset-8 bg-emer-500/10 blur-3xl rounded-full"></div>
            <div class="hero-mock-card relative bg-cream rounded-2xl shadow-2xl overflow-hidden text-ink border border-white/10">
                <div class="bg-cream2 border-b border-line px-4 py-3 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-400"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                    <span class="w-3 h-3 rounded-full bg-emer-500"></span>
                    <span class="ml-3 text-xs text-ink/50 font-mono">app.ot1-pro.com/inbox</span>
                </div>
                <div class="mockup-frame grid grid-cols-12 h-[420px]">
                    <div class="mockup-rail col-span-4 bg-cream2 border-r border-line p-3 space-y-2 overflow-hidden">
                        <div class="text-[10px] uppercase tracking-widest text-ink/40 px-2 py-1">{{ __('Inbox') }} · 3</div>
                        <div id="inbox-list" class="space-y-1"></div>
                    </div>
                    <div class="mockup-chat col-span-8 flex flex-col bg-white">
                        <div class="mockup-chat-header flex items-center gap-3 border-b border-line px-4 py-3">
                            <img id="chat-avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0" src="https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=80&h=80&fit=crop&auto=format" alt="">
                            <div class="flex-1 min-w-0">
                                <div id="chat-name" class="text-sm font-semibold truncate">Sara Ahmed</div>
                                <div class="subline text-[11px] text-ink/50 flex items-center gap-1.5">
                                    <span id="chat-plat-name">Instagram DM</span>
                                    <span class="text-ink/30">·</span>
                                    <span class="text-emer-600 font-medium">{{ __('Active now') }}</span>
                                </div>
                            </div>
                            <span class="mockup-badge text-[10px] px-2 py-0.5 rounded-full bg-emer-100 text-emer-700 font-medium flex-shrink-0">{{ __('AI Autopilot') }}</span>
                        </div>
                        <div id="chat-body" class="flex-1 p-4 space-y-3 overflow-y-auto"></div>
                        <form id="composer" class="border-t border-line px-4 py-3 flex items-center gap-2">
                            <input id="composer-input" type="text" placeholder="{{ __('Try it: type a customer message…') }}"
                                class="flex-1 rounded-full bg-cream2 px-3 py-2 text-xs text-ink placeholder:text-ink/40 border border-transparent focus:border-emer-500 focus:outline-none focus:bg-white transition" />
                            <button type="submit" class="w-9 h-9 rounded-full bg-emer-500 hover:bg-emer-400 text-ink flex items-center justify-center transition" aria-label="Send">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="float-stat absolute -bottom-6 -left-6 bg-white rounded-xl shadow-xl px-4 py-3 border border-line flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emer-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emer-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <div class="text-xs text-ink/50">{{ __('Reply time') }}</div>
                    <div class="text-sm font-bold text-ink">3.2 {{ __('seconds') }}<span class="text-emer-600 text-xs font-normal ml-1">↓ 94%</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════ TRUSTED-BY MARQUEE ═══════ --}}
<section class="border-y border-line bg-cream py-8 overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 flex items-center gap-8">
        <span class="text-xs uppercase tracking-widest text-ink/50 whitespace-nowrap">{{ __('Plugs into the tools you already use') }}</span>
        <div class="flex-1 overflow-hidden">
            <div class="marquee flex items-center gap-12 whitespace-nowrap">
                @foreach(['Facebook Messenger','Instagram DMs','WhatsApp Business','Telegram','Web Chat','Email','Facebook Messenger','Instagram DMs','WhatsApp Business','Telegram','Web Chat','Email'] as $item)
                    <span class="text-lg serif italic text-ink/50">{{ $item }}</span>
                    @if(!$loop->last)<span class="text-ink/20">◆</span>@endif
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════ PROBLEM: BEFORE/AFTER TABS ═══════ --}}
<section class="py-24 fade-up">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-14">
            <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('The problem') }}</div>
            <h2 class="serif text-5xl lg:text-6xl leading-[1.05] mb-6">
                {{ __("You're not losing sales because your product is bad.") }}<br>
                <span class="serif-it text-emer-700">{{ __("You're losing them at reply #2.") }}</span>
            </h2>
            <p class="text-ink/70 text-lg leading-relaxed">
                {{ __("A customer messages your Instagram at 9pm. You see it at 9:47am the next day. By then they've already bought from three other shops. Multiply that by every channel, every hour.") }}
            </p>
        </div>

        <div class="bg-cream2 rounded-2xl p-2 inline-flex mb-8">
            <button data-tab="before" class="tab-btn px-5 py-2 rounded-xl text-sm font-medium bg-white shadow text-ink transition-all">{{ __('Before OT1-Pro') }}</button>
            <button data-tab="after" class="tab-btn px-5 py-2 rounded-xl text-sm font-medium text-ink/60 hover:text-ink transition-all">{{ __('After OT1-Pro') }}</button>
        </div>

        <div id="tab-before" class="tab-panel grid md:grid-cols-4 gap-4">
            @foreach([
                ['Tuesday, 3:47am',    'Instagram DM',      '"Do you have the tote in black?"',        'Unread 14 hours.'],
                ['Tuesday, 8:12am',    'WhatsApp',          '"Guys are you open tomorrow?"',           'Missed. They bought elsewhere.'],
                ['Tuesday, 11:20am',   'Facebook Messenger','"Salaam, price for the leather bag?"',    'Answered 6h later. Ghosted.'],
                ['Tuesday, 2:15pm',    'Telegram',          '"Do you ship to Dubai?"',                 'Still typing… 4 days later.'],
            ] as [$time, $platform, $msg, $fail])
                <div class="bg-white border border-line rounded-xl p-5">
                    <div class="text-xs text-ink/50 mb-2">{{ $time }}</div>
                    <div class="text-sm font-semibold mb-1">{{ $platform }}</div>
                    <p class="text-sm text-ink/60">{{ $msg }} <em class="text-red-700 not-italic">{{ $fail }}</em></p>
                </div>
            @endforeach
        </div>

        <div id="tab-after" class="tab-panel hidden">
            <div class="bg-ink rounded-2xl p-8 text-cream relative overflow-hidden grain">
                <div class="grid md:grid-cols-2 gap-8 items-center relative z-10">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-emer-400 mb-3">{{ __('Same day, all messages') }}</div>
                        <div class="serif text-4xl mb-3">{{ __('Every one') }} <span class="serif-it text-emer-400">{{ __('answered') }}</span>, {{ __('in your voice.') }}</div>
                        <p class="text-cream/70">{{ __('Median reply time drops from') }} <mark class="hi">{{ __('7 hours') }}</mark> {{ __('to') }} <mark class="hi">{{ __('3 seconds') }}</mark>. {{ __('Message-to-sale conversion climbs from') }} <mark class="hi">4%</mark> {{ __('to') }} <mark class="hi">19%</mark>.</p>
                    </div>
                    <div class="space-y-2">
                        @foreach([
                            '"Yes, black totes are in stock. £48 with free UK shipping. Want me to send the direct checkout link?"',
                            '"We\'re open tomorrow 10-6, but ordering online means you skip the queue. Do you want a link?"',
                            '"Wa alaikum salaam, the leather crossbody is £62 today, and yes we ship to Dubai in 3 days. Shall I hold one?"',
                        ] as $reply)
                            <div class="bg-white text-ink rounded-lg p-3 text-sm flex items-start gap-2">
                                <span class="w-6 h-6 rounded-full bg-emer-100 text-emer-700 text-[10px] font-bold flex items-center justify-center flex-shrink-0">AI</span>
                                <span>{{ $reply }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════ NUMBERS ═══════ --}}
<section class="bg-ink text-cream py-24 relative grain">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="grid lg:grid-cols-4 gap-10">
            @foreach([
                ['94', '%',  __('less time spent switching between messaging apps'), '0s'],
                ['3',  's',  __('typical AI reply, day or night, in your business voice'), '.1s'],
                ['19', '%',  __('of messages become paying customers, up from ~4%'), '.2s'],
                ['1200', '', __('extra revenue per week for the average shop by week 3'), '.3s'],
            ] as [$val, $suffix, $label, $delay])
                <div class="fade-up" style="transition-delay:{{ $delay }}">
                    <div class="serif text-6xl text-emer-400 mb-2">
                        @if($loop->last)${{ '' }}@endif<span data-counter="{{ $val }}">0</span>{{ $suffix }}
                    </div>
                    <div class="text-cream/60 text-sm">{{ $label }}</div>
                </div>
            @endforeach
        </div>
        <p class="mt-14 text-xs text-cream/40 max-w-2xl">{{ __('Numbers from ot1-pro.com customer telemetry (Aug-Sep 2026, n=42 active accounts). Individual results depend on message volume, catalog size, and how weird your customers\' questions get.') }}</p>
    </div>
</section>

{{-- ═══════ HOW IT WORKS ═══════ --}}
<section id="howitworks" class="py-24 fade-up">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-16">
            <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('How it works') }}</div>
            <h2 class="serif text-5xl lg:text-6xl leading-[1.05]">{{ __('Ninety seconds to') }} <span class="serif-it text-emer-700">{{ __('go live.') }}</span></h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="relative">
                <div class="serif text-8xl text-emer-100 leading-none absolute -top-4 -left-2 -z-10">01</div>
                <h3 class="serif text-3xl mb-3 relative">{{ __('Connect a channel') }}</h3>
                <p class="text-ink/70 leading-relaxed">{{ __('Facebook, Instagram, WhatsApp, Telegram. Whichever one your customers actually use. Add more later.') }}</p>
                <div class="mt-6 bg-white border border-line rounded-xl p-4 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emer-500 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5.5 6.5L11 15l-3.5-3.5 1.5-1.5L11 12l5-5 1.5 1.5z"/></svg>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-semibold">Instagram · @yourshop</div>
                        <div class="text-xs text-emer-700">{{ __('Connected · 3 seconds ago') }}</div>
                    </div>
                </div>
            </div>
            <div class="relative md:mt-16">
                <div class="serif text-8xl text-emer-100 leading-none absolute -top-4 -left-2 -z-10">02</div>
                <h3 class="serif text-3xl mb-3 relative">{{ __('Answer three questions') }}</h3>
                <p class="text-ink/70 leading-relaxed">{{ __("What you sell. What customers ask most. Your voice. That's it. We build your AI's brain from there.") }}</p>
                <div class="mt-6 bg-white border border-line rounded-xl p-4">
                    <div class="text-xs text-ink/50 mb-1">Q1</div>
                    <div class="text-sm font-medium mb-3">{{ __('What do you sell?') }}</div>
                    <div class="text-xs text-ink/70 italic border-l-2 border-emer-500 pl-3">"Handmade leather bags shipped to UAE and Saudi within 3 days"</div>
                </div>
            </div>
            <div class="relative md:mt-32">
                <div class="serif text-8xl text-emer-100 leading-none absolute -top-4 -left-2 -z-10">03</div>
                <h3 class="serif text-3xl mb-3 relative">{{ __('Test it with a fake customer') }}</h3>
                <p class="text-ink/70 leading-relaxed">{{ __('Chat with your own AI before any real customer sees it. Tweak the tone. Then flip it live.') }}</p>
                <div class="mt-6 bg-white border border-line rounded-xl p-4 space-y-2">
                    <div class="text-right"><span class="inline-block bg-emer-600 text-white px-3 py-1.5 rounded-2xl rounded-br-sm text-xs">{{ __('Do you ship to Dubai?') }}</span></div>
                    <div><span class="inline-block bg-cream2 text-ink px-3 py-1.5 rounded-2xl rounded-bl-sm text-xs">{{ __('Yes — 3 days, £8 shipping. Which bag caught your eye?') }}</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════ FEATURE BLOCKS ═══════ --}}
<section id="platforms" class="bg-cream2 py-24">
    <div class="max-w-7xl mx-auto px-6">

        {{-- 1. Every channel --}}
        <div class="grid lg:grid-cols-2 gap-16 items-center mb-32 fade-up">
            <div class="bg-white rounded-2xl border border-line p-4 shadow-sm">
                <div class="rounded-xl bg-ink text-cream p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emer-400"></span>
                        <span class="ml-2 text-[10px] text-cream/50 font-mono">{{ __('All conversations · 3 unread') }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach([
                            ['https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=80&h=80&fit=crop&auto=format', 'Layla',  '"Is the small tote still…"',      'now'],
                            ['https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=80&h=80&fit=crop&auto=format', 'Ahmed',  '"Ok I\'ll take it — please…"',    '2m'],
                            ['https://images.unsplash.com/photo-1533900298318-6b8da08a523e?w=80&h=80&fit=crop&auto=format', 'Marco',  '"Do you deliver in Rome?"',       '5m'],
                            ['https://images.unsplash.com/photo-1591561954557-26941169b49e?w=80&h=80&fit=crop&auto=format', 'Fatima', '"Hi, do you have red in the small…"', '7m'],
                        ] as [$img, $name, $preview, $when])
                            <div class="bg-ink2 rounded-lg p-3 flex items-center gap-3">
                                <img class="w-8 h-8 rounded-full object-cover flex-shrink-0" src="{{ $img }}" alt="">
                                <div class="flex-1 text-sm">{{ $name }} · <span class="text-cream/50">{{ $preview }}</span></div>
                                <div class="text-[10px] text-emer-400">{{ $when }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('Every channel') }}</div>
                <h3 class="serif text-4xl lg:text-5xl mb-6 leading-tight">{{ __('One inbox for every message your business gets.') }} <span class="serif-it text-emer-700">{{ __('Everywhere.') }}</span></h3>
                <p class="text-ink/70 leading-relaxed mb-6">{{ __('Facebook Messenger. Instagram DMs. WhatsApp Business. Telegram bot. Web chat widget. Email. All arrive in the same clean feed. Reply from one screen, keep context, never lose a conversation to another tab.') }}</p>
                <ul class="space-y-3 text-sm text-ink/80">
                    @foreach([
                        __('Zero-friction connection via official OAuth on every platform'),
                        __('Multi-page and multi-account, one team at the seat price'),
                        __('Real-time sync, messages appear in less than a second'),
                    ] as $point)
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-emer-100 text-emer-700 flex items-center justify-center flex-shrink-0 text-[10px]">✓</span>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- 2. The AI --}}
        <div class="grid lg:grid-cols-2 gap-16 items-center mb-32 fade-up">
            <div>
                <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('The AI') }}</div>
                <h3 class="serif text-4xl lg:text-5xl mb-6 leading-tight">{{ __('Not a chatbot.') }} <span class="serif-it text-emer-700">{{ __('A closer.') }}</span></h3>
                <p class="text-ink/70 leading-relaxed mb-6">{{ __("Your AI reads the customer, checks your catalog, knows your shipping rules, and writes a reply in your business voice. If it doesn't know something, it asks you. If the customer's ready to buy, it sends the checkout link.") }}</p>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    @foreach([
                        [__('Answers in your voice'),         __('Trained on how you talk to customers, not a generic template')],
                        [__('Escalates only when needed'),    __('You get pinged for the 5% that actually need a human')],
                        [__('Handles Arabic + English'),      __('Code-switch mid-sentence, we handle it')],
                        [__('Learns from your corrections'),  __('Every edit you make trains it further')],
                    ] as [$title, $body])
                        <div class="border-l-2 border-emer-600 pl-4">
                            <div class="font-semibold mb-1">{{ $title }}</div>
                            <div class="text-ink/60 text-xs">{{ $body }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-line p-6 shadow-sm">
                <div class="flex items-center gap-2 mb-4">
                    <img class="w-8 h-8 rounded-full object-cover" src="https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=80&h=80&fit=crop&auto=format" alt="">
                    <div>
                        <div class="text-sm font-semibold">Layla · Instagram</div>
                        <div class="text-[11px] text-ink/50">{{ __('Repeat customer · 3 orders') }}</div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="text-right"><span class="inline-block bg-cream2 text-ink px-3 py-2 rounded-2xl rounded-br-sm text-sm max-w-[80%]">Hey! Do you still have the small crossbody in mustard? Need it for Friday 🙏</span></div>
                    <div class="text-right text-[10px] text-ink/40">10:24 AM · Read</div>
                    <div><span class="inline-block bg-emer-600 text-white px-3 py-2 rounded-2xl rounded-bl-sm text-sm max-w-[80%]">
                        <span class="text-[10px] uppercase tracking-widest opacity-80 block mb-1">{{ __('AI · replying now') }}</span>
                        <span id="ai-live-text"></span><span class="dot">·</span><span class="dot">·</span><span class="dot">·</span>
                    </span></div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs text-ink/50 border-t border-line pt-3">
                    <span>{{ __('3.1s reply · in your voice') }}</span>
                    <span class="text-emer-700 font-medium">✓ {{ __('Auto-sent') }}</span>
                </div>
            </div>
        </div>

        {{-- 3. Founder promise --}}
        <div class="bg-ink rounded-2xl overflow-hidden text-cream grain fade-up relative">
            <div class="grid lg:grid-cols-5 gap-8 items-center p-10 lg:p-14 relative z-10">
                <div class="lg:col-span-3">
                    <div class="text-xs uppercase tracking-widest text-emer-400 mb-4">{{ __('A promise') }}</div>
                    <blockquote class="serif text-3xl lg:text-4xl leading-tight mb-6">
                        {{ __('I built OT1-Pro because I lost') }} <span class="serif-it text-emer-400">£4,000</span> {{ __('of my own sales to unanswered DMs last year. So this is the tool I needed.') }} <span class="serif-it text-emer-400">{{ __('Try any plan free.') }}</span> {{ __("Pay by bank transfer, only when it's working.") }}
                    </blockquote>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emer-500 flex items-center justify-center text-ink font-bold text-sm">OT</div>
                        <div>
                            <div class="text-sm font-semibold">Omar Eltak · {{ __('Founder') }}</div>
                            <div class="text-xs text-cream/60">{{ __('Reachable at') }} <span class="underline">omareltak7@gmail.com</span> · {{ __('usually replies in an hour') }}</div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-ink2 rounded-2xl p-6 border border-cream/10">
                    <div class="text-xs uppercase tracking-widest text-emer-400 mb-2">{{ __('Try any plan') }}</div>
                    <div class="serif text-4xl mb-2">{{ __('Free.') }}</div>
                    <p class="text-cream/70 text-sm mb-5">{{ __("14 days. No card. Pay by bank transfer only when you're ready to keep it.") }}</p>
                    <a href="#pricing" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-5 py-3 rounded-full font-semibold hover:bg-emer-400 transition w-full justify-center">{{ __('See plans') }} <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════ TESTIMONIALS (FLIP CARDS) ═══════ --}}
<section id="stories" class="py-24 fade-up">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mb-12">
            <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('Stories') }}</div>
            <h2 class="serif text-5xl lg:text-6xl leading-[1.05]">{{ __('Real shops.') }} <span class="serif-it text-emer-700">{{ __('Real chats.') }}</span> {{ __('Real closes.') }}</h2>
            <p class="text-ink/70 mt-4 text-sm"><em class="serif-it">{{ __('Hover any card') }}</em> {{ __('to see the actual conversation that led to a sale.') }}</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @php
                $stories = [
                    [
                        'quote' => __('"We were losing sales at 3am. Now the AI closes them for me, and I read them in the morning like a diary."'),
                        'photo' => 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=80&h=80&fit=crop&auto=format',
                        'name'  => 'Layla · Cairo',
                        'bio'   => __('Handmade Egyptian leather bags'),
                        'ctx'   => 'Instagram DM · 3:47am',
                        'user1' => 'u still have the small crossbody?? for a gift tmrw pls',
                        'ai1'   => 'Yes! One left in mustard. Want me to hold it and send you a checkout link right now? Delivery by tomorrow noon guaranteed.',
                        'user2' => 'yes plz 🙏',
                        'close' => __('→ Closed £48 · 4 min from first message'),
                    ],
                    [
                        'quote' => __('"I was answering the same 5 questions all day. Now I never answer them. And my sales doubled."'),
                        'photo' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=80&h=80&fit=crop&auto=format',
                        'name'  => 'Ahmed · Dubai',
                        'bio'   => __('Wholesale spice import'),
                        'ctx'   => 'WhatsApp · Sunday morning',
                        'user1' => 'Salaam, do you deliver saffron in bulk to Al Ain?',
                        'ai1'   => 'Wa alaikum salaam. Yes — free delivery on 500g+ orders to all UAE. Iranian saffron is 340 AED / 100g wholesale. How much do you need?',
                        'user2' => '1kg',
                        'close' => __('→ Closed 3,400 AED · zero human touch'),
                    ],
                    [
                        'quote' => __('"I stopped using Slack for customer stuff. Everything lives in OT1. My team knows exactly where to look."'),
                        'photo' => 'https://images.unsplash.com/photo-1542060748-10c28b62716f?w=80&h=80&fit=crop&auto=format',
                        'name'  => 'Maya · London',
                        'bio'   => __('Vintage clothing reseller'),
                        'ctx'   => 'Facebook Marketplace inquiry',
                        'user1' => 'is this available? the 90s levis',
                        'ai1'   => 'Yes, size 30W 32L, £55 or nearest offer. Pickup in Hackney or £6 shipping. Want me to reserve?',
                        'user2' => 'will collect tmrw',
                        'close' => __('→ Closed £55 · reserved before 3 other buyers asked'),
                    ],
                ];
            @endphp
            @foreach($stories as $s)
                <div class="flipcard h-80">
                    <div class="flipinner w-full h-full">
                        <div class="flipface absolute inset-0 bg-white border border-line rounded-2xl p-6 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-4"><span class="text-emer-500">★★★★★</span></div>
                                <p class="serif text-2xl leading-snug mb-4">{{ $s['quote'] }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <img class="w-10 h-10 rounded-full object-cover" src="{{ $s['photo'] }}" alt="">
                                <div><div class="text-sm font-semibold">{{ $s['name'] }}</div><div class="text-xs text-ink/60">{{ $s['bio'] }}</div></div>
                            </div>
                        </div>
                        <div class="flipface flipback bg-ink text-cream rounded-2xl p-6 flex flex-col justify-center text-sm space-y-2">
                            <div class="text-[10px] uppercase tracking-widest text-emer-400 mb-2">{{ $s['ctx'] }}</div>
                            <div class="text-right"><span class="bg-cream2 text-ink rounded-2xl rounded-br-sm px-3 py-1.5 inline-block max-w-[80%]">{{ $s['user1'] }}</span></div>
                            <div><span class="bg-emer-600 text-white rounded-2xl rounded-bl-sm px-3 py-1.5 inline-block max-w-[80%]">{{ $s['ai1'] }}</span></div>
                            <div class="text-right"><span class="bg-cream2 text-ink rounded-2xl rounded-br-sm px-3 py-1.5 inline-block">{{ $s['user2'] }}</span></div>
                            <div class="mt-3 text-xs text-emer-400 font-semibold">{{ $s['close'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════ PRICING ═══════ --}}
<section id="pricing" class="bg-ink text-cream py-24 grain relative">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="max-w-2xl mb-12 fade-up">
            <div class="text-xs uppercase tracking-widest text-emer-400 mb-4">{{ __('Pricing') }}</div>
            <h2 class="serif text-5xl lg:text-6xl leading-[1.05] mb-4">{{ __("Pay in cash, once it's working.") }} <span class="serif-it text-emer-400">{{ __('Not before.') }}</span></h2>
            <p class="text-cream/70 leading-relaxed">{{ __('Try any plan free for 14 days. No card needed. When you decide to keep it, we send bank details and you pay by transfer. That\'s it. No auto-renew traps.') }}</p>
        </div>

        <div class="mb-10 bg-ink2 rounded-2xl p-6 border border-cream/10 fade-up">
            <div class="flex items-center justify-between mb-3">
                <label for="volume-slider" class="text-sm text-cream/80">{{ __('How many messages does your business get per month?') }}</label>
                <span class="text-2xl font-semibold text-emer-400"><span id="slider-val">1,200</span> {{ __('msgs') }}</span>
            </div>
            <input id="volume-slider" type="range" min="0" max="10" value="3" class="w-full accent-emer-500">
            <div class="flex justify-between text-[10px] text-cream/60 mt-1">
                <span>~100</span><span>500</span><span>1k</span><span>2.5k</span><span>5k</span><span>10k+</span>
            </div>
            <div class="mt-4 text-sm text-cream/80">{{ __('Recommended:') }} <span id="rec-plan" class="text-emer-400 font-semibold">Starter</span> — <span id="rec-reason">{{ __('plenty of headroom for a growing shop') }}</span></div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- Free --}}
            <div class="plan bg-ink2 border border-cream/10 rounded-2xl p-8" data-plan="free">
                <div class="serif text-3xl mb-1">{{ __('Free') }}</div>
                <div class="text-cream/60 text-sm mb-6">{{ __('Try the flow, no strings') }}</div>
                <div class="serif text-5xl mb-6">$0</div>
                <ul class="space-y-2.5 text-sm text-cream/80 mb-8">
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('1 platform, 100 AI replies / month') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('1 team seat') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Meet-Your-AI wizard') }}</li>
                    <li class="flex items-center gap-2 text-cream/50"><span>—</span> {{ __('Advanced routing') }}</li>
                </ul>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block w-full text-center border border-cream/20 hover:bg-cream/5 rounded-full py-3 text-sm font-semibold transition text-cream">{{ __('Start free') }}</a>
                @endif
            </div>
            {{-- Starter (featured) --}}
            <div class="plan bg-cream text-ink rounded-2xl p-8 relative shadow-xl transform scale-105" data-plan="starter">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-emer-500 text-ink text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full">{{ __('Most shops start here') }}</div>
                <div class="serif text-3xl mb-1">Starter</div>
                <div class="text-ink/70 text-sm mb-6">{{ __('For growing DMs') }}</div>
                <div class="serif text-5xl mb-1">$29<span class="text-lg text-ink/50">/mo</span></div>
                <div class="text-ink/60 text-xs mb-6">{{ __('or $290/yr — 2 months free') }}</div>
                <ul class="space-y-2.5 text-sm text-ink/80 mb-8">
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('3 platforms, 2,500 AI replies / mo') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('3 team seats') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('Custom AI voice tuning') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('Founder email support') }}</li>
                </ul>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block w-full text-center bg-ink text-cream hover:bg-ink2 rounded-full py-3 text-sm font-semibold transition">{{ __('Start 14-day trial') }}</a>
                @endif
            </div>
            {{-- Pro --}}
            <div class="plan bg-ink2 border border-cream/10 rounded-2xl p-8" data-plan="pro">
                <div class="serif text-3xl mb-1">Pro</div>
                <div class="text-cream/60 text-sm mb-6">{{ __('Scale without hiring') }}</div>
                <div class="serif text-5xl mb-1">$79<span class="text-lg text-cream/50">/mo</span></div>
                <div class="text-cream/60 text-xs mb-6">{{ __('or $790/yr — 2 months free') }}</div>
                <ul class="space-y-2.5 text-sm text-cream/80 mb-8">
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('All platforms, 10,000 AI replies / mo') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Unlimited team seats') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Custom AI + workflows') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Priority support < 4hr') }}</li>
                </ul>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block w-full text-center border border-cream/20 hover:bg-cream/5 rounded-full py-3 text-sm font-semibold transition text-cream">{{ __('Start 14-day trial') }}</a>
                @endif
            </div>
        </div>
        <p class="mt-8 text-xs text-cream/60 text-center">{{ __('Custom volume, on-prem, or a franchise?') }} <a href="{{ route('contact') }}" class="underline hover:text-emer-400">{{ __('Email Omar') }}</a> — {{ __('he handles enterprise personally.') }}</p>
    </div>
</section>

{{-- ═══════ FAQ ═══════ --}}
<section class="py-24 fade-up">
    <div class="max-w-3xl mx-auto px-6">
        <div class="mb-10 text-center">
            <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('Common questions') }}</div>
            <h2 class="serif text-4xl lg:text-5xl">{{ __('Real ones.') }} <span class="serif-it text-emer-700">{{ __('Real answers.') }}</span></h2>
        </div>
        <div class="divide-y divide-line border-y border-line">
            @foreach([
                [__('Does the AI sound like a robot?'),                          __("No. That's the whole point. The wizard asks you three questions on signup — what you sell, what customers ask most, and how you talk. We build the system prompt from your answers, then let you chat with your own AI before any real customer sees it. Tweak until it sounds like you.")],
                [__('What if the AI gets it wrong?'),                            __("You get pinged. Every reply the AI isn't sure about is escalated to your inbox and paused until you confirm. Every correction you make trains it further, so the escalation rate drops every week.")],
                [__('Do I have to add a credit card?'),                          __("No. Try any plan free for 14 days. If you decide to keep it, we send bank transfer details. You pay by wire. No auto-renew, no card on file, no surprise charge. If you want to cancel, you literally just don't pay.")],
                [__('Which platforms exactly?'),                                 __("Facebook Messenger (official Business API), Instagram DMs (official), WhatsApp Business (both official API and QR-code Personal for small shops), Telegram bots, embeddable web chat widget, and email. New platforms added as customer demand justifies — LINE and Discord are next.")],
                [__('How is this different from Manychat or Respond.io?'),       __("Manychat is a marketing tool — it blasts sequences. Respond.io is enterprise-priced and takes weeks to configure. OT1-Pro is built for a founder with a phone full of unread DMs who needs the AI to actually close sales by tomorrow, not next quarter. Signup-to-live time is 90 seconds.")],
                [__('Is my customer data safe?'),                                __("Yes. Data is stored in Frankfurt, encrypted at rest, and never used to train models across tenants. Each business's AI is trained only on your own message history and your own answers. GDPR-compliant, SOC 2 in progress.")],
            ] as [$q, $a])
                <details class="py-5">
                    <summary class="flex justify-between items-center">
                        <span class="serif text-xl">{{ $q }}</span>
                        <span class="chev serif text-2xl text-emer-700">+</span>
                    </summary>
                    <p class="mt-3 text-ink/70 text-sm leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════ FINAL CTA ═══════ --}}
<section class="bg-ink text-cream py-32 grain relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="serif text-6xl lg:text-7xl leading-none mb-8">{{ __('Stop losing sales') }}<br><span class="serif-it text-emer-400">{{ __('to your notifications.') }}</span></h2>
        <p class="text-cream/70 text-lg mb-10 max-w-xl mx-auto">{{ __("Ninety seconds from now, your AI can be replying to customers in your voice. Free to try. Pay only when it's working.") }}</p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start free — no card') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
            @endif
            <a href="{{ route('contact') }}" class="text-cream/90 hover:text-cream font-medium u-link">{{ __('or email the founder directly') }}</a>
        </div>
    </div>
</section>

@push('scripts')
<script>
// Animated counters
(function () {
    var counterIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            var el = e.target;
            var target = parseInt(el.dataset.counter, 10);
            var cur = 0, steps = 60, inc = target / steps, done = 0;
            var t = setInterval(function () {
                cur += inc; done++;
                el.textContent = Math.round(cur).toLocaleString();
                if (done >= steps) { el.textContent = target.toLocaleString(); clearInterval(t); }
            }, 20);
            counterIO.unobserve(el);
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('[data-counter]').forEach(function (el) { counterIO.observe(el); });
})();

// Before/After tabs
document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var which = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(function (b) {
            var active = b.dataset.tab === which;
            b.classList.toggle('bg-white', active);
            b.classList.toggle('shadow', active);
            b.classList.toggle('text-ink', active);
            b.classList.toggle('text-ink/60', !active);
        });
        document.getElementById('tab-before').classList.toggle('hidden', which !== 'before');
        document.getElementById('tab-after').classList.toggle('hidden', which !== 'after');
    });
});

// Live inbox ticker
(function () {
    var inboxData = [
        { name: 'Sara Ahmed',
          photo: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=80&h=80&fit=crop&auto=format',
          platform: 'Instagram DM',
          messages: [
            { from: 'user', text: 'Hi! Do you have the leather crossbody in black?' },
            { from: 'ai',   text: 'Hey Sara! Yes, black is in stock — £62, ships free to UK. Want the checkout link?' },
            { from: 'user', text: 'Yes please 🙌' },
        ]},
        { name: 'Marco Rossi',
          photo: 'https://images.unsplash.com/photo-1533900298318-6b8da08a523e?w=80&h=80&fit=crop&auto=format',
          platform: 'Facebook Messenger',
          messages: [
            { from: 'user', text: 'Ciao, do you deliver to Rome?' },
            { from: 'ai',   text: 'Ciao Marco! Yes — 4 days to Rome via DHL, €14 shipping. What are you looking at?' },
            { from: 'user', text: 'The mustard tote 😍' },
        ]},
        { name: 'Fatima Al-Rashid',
          photo: 'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=80&h=80&fit=crop&auto=format',
          platform: 'WhatsApp Business',
          messages: [
            { from: 'user', text: 'Salaam, do you have red in the small bag?' },
            { from: 'ai',   text: 'Wa alaikum salaam! Red small is available — 240 AED, delivery to Riyadh in 3 days. Reserve one?' },
            { from: 'user', text: 'Yes please' },
        ]},
    ];

    var inboxIdx = 0;
    function renderInbox() {
        var d = inboxData[inboxIdx];
        var nameEl = document.getElementById('chat-name'); if (nameEl) nameEl.textContent = d.name;
        var chatAvatar = document.getElementById('chat-avatar'); if (chatAvatar) { chatAvatar.src = d.photo; chatAvatar.alt = d.name; }
        var plat = document.getElementById('chat-plat-name'); if (plat) plat.textContent = d.platform;
        var list = document.getElementById('inbox-list'); if (!list) return;
        list.innerHTML = '';
        inboxData.forEach(function (it, i) {
            var row = document.createElement('div');
            var active = i === inboxIdx;
            row.className = 'px-2 py-2 rounded-lg text-xs cursor-pointer transition ' + (active ? 'bg-white shadow-sm' : 'hover:bg-white/60');
            row.innerHTML = '<div class="flex items-center gap-2"><img class="w-7 h-7 rounded-full object-cover flex-shrink-0" src="' + it.photo + '" alt=""><div class="min-w-0 flex-1"><div class="font-semibold truncate text-ink">' + it.name + '</div><div class="text-ink/60 truncate">' + it.messages[0].text + '</div></div></div>';
            list.appendChild(row);
        });
        var body = document.getElementById('chat-body'); if (!body) return;
        body.innerHTML = '';
        d.messages.forEach(function (m, i) {
            setTimeout(function () {
                var wrap = document.createElement('div');
                var isUser = m.from === 'user';
                wrap.className = 'flex ' + (isUser ? 'justify-start' : 'justify-end');
                wrap.innerHTML = '<div class="msg max-w-[75%] rounded-2xl ' + (isUser ? 'rounded-tl-sm bg-cream2 text-ink' : 'rounded-tr-sm bg-emer-600 text-white') + ' px-3 py-2 text-sm">' + m.text + '</div>';
                body.appendChild(wrap);
            }, i * 900);
        });
    }
    renderInbox();
    var autoCycle = setInterval(function () { inboxIdx = (inboxIdx + 1) % inboxData.length; renderInbox(); }, 6500);

    // Interactive composer
    var composer = document.getElementById('composer');
    var composerInput = document.getElementById('composer-input');
    var chatBody = document.getElementById('chat-body');
    if (!composer || !composerInput || !chatBody) return;

    function appendBubble(text, isUser, isTyping) {
        var wrap = document.createElement('div');
        wrap.className = 'flex ' + (isUser ? 'justify-start' : 'justify-end');
        var bubble = document.createElement('div');
        bubble.className = 'msg max-w-[75%] rounded-2xl px-3 py-2 text-sm ' + (isUser
            ? 'rounded-tl-sm bg-cream2 text-ink'
            : 'rounded-tr-sm bg-emer-600 text-white');
        if (isTyping) {
            bubble.className += ' ai-typing';
            bubble.innerHTML = '<span class="dotb"></span><span class="dotb"></span><span class="dotb"></span>';
        } else {
            bubble.textContent = text;
        }
        wrap.appendChild(bubble);
        chatBody.appendChild(wrap);
        chatBody.scrollTop = chatBody.scrollHeight;
        return wrap;
    }

    function fakeAiReply(msg) {
        var m = msg.toLowerCase();
        if (m.includes('price') || m.includes('cost') || m.includes('how much')) return "Good question! The bag you're looking at is £62 today, free UK shipping. Want the checkout link?";
        if (m.includes('ship') || m.includes('deliver') || m.includes('dubai') || m.includes('uae')) return "Yes — we ship to the UAE in 3 days, £8 flat. Which item are you looking at? I'll confirm stock.";
        if (m.includes('stock') || m.includes('available') || m.includes('have')) return "Let me check for you… yes, in stock in mustard, black, and cream. Which color are you after?";
        if (m.includes('hello') || m.includes('hi') || m.includes('hey') || m.includes('salaam')) return "Hey! Welcome — what caught your eye today? Happy to help find the right one.";
        if (m.includes('open') || m.includes('hours')) return "We're open 10-6 tomorrow, but ordering online means you skip the queue. Want a link?";
        if (m.includes('return') || m.includes('refund')) return "30-day returns, no questions asked. Just DM us the order number and we'll email a prepaid label.";
        return "Got it — let me help. Could you tell me a bit more about what you're looking for? I'll match you to the best option.";
    }

    composer.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = composerInput.value.trim();
        if (!text) return;
        clearInterval(autoCycle);
        appendBubble(text, true);
        composerInput.value = '';
        composerInput.focus();
        var typing = appendBubble('', false, true);
        setTimeout(function () {
            typing.remove();
            appendBubble(fakeAiReply(text), false);
        }, 900 + Math.random() * 600);
    });

    // AI live-typing (feature block 2)
    var aiReplyText = "Yes — mustard small is in stock! I can ship priority for Friday delivery, £4 extra. Reserve it now?";
    var charIdx = 0;
    function typeAi() {
        var el = document.getElementById('ai-live-text');
        if (!el) return;
        if (charIdx <= aiReplyText.length) {
            el.textContent = aiReplyText.slice(0, charIdx);
            charIdx++;
            setTimeout(typeAi, 35);
        } else {
            setTimeout(function () { charIdx = 0; el.textContent = ''; typeAi(); }, 3500);
        }
    }
    setTimeout(typeAi, 1500);
})();

// Pricing volume slider
(function () {
    var slider = document.getElementById('volume-slider');
    if (!slider) return;
    var buckets = [
        { msgs: 100,   plan: 'Free',    reason: 'small volume, casual DMs — the free tier fits' },
        { msgs: 300,   plan: 'Free',    reason: 'still within the free 100/mo cap x 3 platforms' },
        { msgs: 500,   plan: 'Starter', reason: 'extra headroom + custom voice tuning' },
        { msgs: 1200,  plan: 'Starter', reason: 'plenty of headroom for a growing shop' },
        { msgs: 1800,  plan: 'Starter', reason: 'still inside the 2.5k Starter cap' },
        { msgs: 2500,  plan: 'Starter', reason: 'right at the Starter ceiling — check Pro too' },
        { msgs: 3500,  plan: 'Pro',     reason: "over Starter's cap — Pro unlocks 10k/mo + all platforms" },
        { msgs: 5000,  plan: 'Pro',     reason: 'Pro is the sweet spot for your volume' },
        { msgs: 7000,  plan: 'Pro',     reason: "still inside Pro's 10k cap" },
        { msgs: 9000,  plan: 'Pro',     reason: "approaching Pro's ceiling — plan ahead" },
        { msgs: 12000, plan: 'Custom',  reason: 'email Omar for a custom volume plan' },
    ];
    function updateSlider() {
        var i = parseInt(slider.value, 10);
        var b = buckets[i];
        document.getElementById('slider-val').textContent = b.msgs.toLocaleString();
        document.getElementById('rec-plan').textContent = b.plan;
        document.getElementById('rec-reason').textContent = b.reason;
        document.querySelectorAll('.plan').forEach(function (p) {
            p.classList.remove('ring-2', 'ring-emer-400');
            if (p.dataset.plan.toLowerCase() === b.plan.toLowerCase()) {
                p.classList.add('ring-2', 'ring-emer-400');
            }
        });
    }
    slider.addEventListener('input', updateSlider);
    updateSlider();
})();
</script>
@endpush

</x-layouts.brand-marketing>
