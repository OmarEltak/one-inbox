{{--
    Route: /features → name('features')

    Phase-2 brand features page. Uses the "everything you need" ink hero
    + the two feature blocks from homepage-redesign-preview.html
    (Every channel + The AI), plus a platform grid and a testimonial-flip
    row scaled down for this page.
--}}
<x-layouts.brand-marketing
    :title="__('Features — Unified inbox, AI responder & lead scoring | OT1-Pro')"
    :description="__('Facebook, Instagram, WhatsApp, Telegram — in one inbox, answered by an AI in your voice. Escalations, lead scoring, team seats, and real-time sync.')">

{{-- ═══════ HERO ═══════ --}}
<section class="bg-ink text-cream pt-32 pb-16 grain relative">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <div class="text-xs uppercase tracking-[0.2em] text-emer-400 mb-6">{{ __('Every feature, in your voice') }}</div>
        <h1 class="serif text-5xl lg:text-7xl leading-[1.02] mb-6">
            {{ __('Everything you need') }}<br>
            <span class="serif-it text-emer-400">{{ __('to close in DMs.') }}</span>
        </h1>
        <p class="text-cream/70 text-lg leading-relaxed max-w-xl mx-auto">
            {{ __('One inbox. Every platform. An AI that writes like you, escalates like a junior, and never sleeps. Below is exactly what you get on any plan.') }}
        </p>
    </div>
</section>

{{-- ═══════ PLATFORM GRID ═══════ --}}
<section id="platforms" class="bg-cream2 py-20 fade-up">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-xs uppercase tracking-widest text-emer-700 mb-4 text-center">{{ __('Every platform') }}</div>
        <h2 class="serif text-4xl lg:text-5xl mb-14 leading-tight text-center">{{ __('Pick the ones your customers actually use.') }}</h2>
        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['whatsapp-inbox',      __('WhatsApp Business'), __('QR-code or official API. Media, buttons, templates.')],
                ['instagram-dm',        __('Instagram DMs'),     __('Story replies, comments, product mentions.')],
                ['facebook-messenger',  __('Facebook Messenger'),__('Multi-page teams, comment escalation.')],
                ['telegram-inbox',      __('Telegram'),          __('Bots and channels, unlimited replies.')],
            ] as [$route, $name, $blurb])
                <a href="{{ Route::has($route) ? route($route) : '#' }}" class="block bg-white border border-line rounded-2xl p-6 hover:border-emer-500 transition group">
                    <div class="w-10 h-10 rounded-full bg-emer-100 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emer-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div class="serif text-2xl mb-2">{{ $name }}</div>
                    <p class="text-ink/70 text-sm leading-relaxed">{{ $blurb }}</p>
                    <div class="mt-4 text-xs text-emer-700 font-semibold group-hover:underline">{{ __('Learn more →') }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════ FEATURE 1: EVERY CHANNEL ═══════ --}}
<section class="py-24 fade-up">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="bg-white rounded-2xl border border-line p-4 shadow-sm order-2 lg:order-1">
                <div class="rounded-xl bg-ink text-cream p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-emer-400"></span>
                        <span class="ml-2 text-[10px] text-cream/60 font-mono">{{ __('All conversations · 3 unread') }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach([
                            ['Layla',  '"Is the small tote still…"',      'now'],
                            ['Ahmed',  '"Ok I\'ll take it — please…"',    '2m'],
                            ['Marco',  '"Do you deliver in Rome?"',       '5m'],
                            ['Fatima', '"Hi, do you have red in the small…"', '7m'],
                        ] as [$name, $preview, $when])
                            <div class="bg-ink2 rounded-lg p-3 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-emer-500 flex-shrink-0"></div>
                                <div class="flex-1 text-sm">{{ $name }} · <span class="text-cream/60">{{ $preview }}</span></div>
                                <div class="text-[10px] text-emer-400">{{ $when }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('Every channel') }}</div>
                <h3 class="serif text-4xl lg:text-5xl mb-6 leading-tight">{{ __('One inbox for every message.') }} <span class="serif-it text-emer-700">{{ __('Everywhere.') }}</span></h3>
                <p class="text-ink/70 leading-relaxed mb-6">{{ __('Facebook Messenger. Instagram DMs. WhatsApp Business. Telegram bot. Web chat widget. Email. All arrive in the same clean feed. Reply from one screen, keep context, never lose a conversation.') }}</p>
                <ul class="space-y-3 text-sm text-ink/80">
                    @foreach([
                        __('Zero-friction connection via official OAuth on every platform'),
                        __('Multi-page and multi-account, one team at the seat price'),
                        __('Real-time sync, messages appear in less than a second'),
                        __('Full history preserved when a conversation moves platforms'),
                    ] as $point)
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-emer-100 text-emer-700 flex items-center justify-center flex-shrink-0 text-[10px]">✓</span>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══════ FEATURE 2: THE AI ═══════ --}}
<section class="bg-cream2 py-24 fade-up">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
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
                        [__('Understands product images'),    __('Vision chain reads the photo the customer sent')],
                        [__('Sends checkout links'),          __('Connects to your store to hand off warm buyers')],
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
                    <div class="w-8 h-8 rounded-full bg-emer-100"></div>
                    <div>
                        <div class="text-sm font-semibold">Layla · Instagram</div>
                        <div class="text-[11px] text-ink/60">{{ __('Repeat customer · 3 orders') }}</div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="text-right"><span class="inline-block bg-cream2 text-ink px-3 py-2 rounded-2xl rounded-br-sm text-sm max-w-[80%]">Hey! Do you still have the small crossbody in mustard? Need it for Friday 🙏</span></div>
                    <div class="text-right text-[10px] text-ink/50">10:24 AM · Read</div>
                    <div><span class="inline-block bg-emer-600 text-white px-3 py-2 rounded-2xl rounded-bl-sm text-sm max-w-[80%]">
                        <span class="text-[10px] uppercase tracking-widest opacity-80 block mb-1">{{ __('AI · replying') }}</span>
                        {{ __('Yes! Mustard small is in stock — I can ship priority for Friday delivery, £4 extra. Reserve it?') }}
                    </span></div>
                </div>
                <div class="mt-4 flex items-center justify-between text-xs text-ink/60 border-t border-line pt-3">
                    <span>{{ __('3.1s reply · in your voice') }}</span>
                    <span class="text-emer-700 font-medium">✓ {{ __('Auto-sent') }}</span>
                </div>
            </div>
        </div>
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
            @foreach([
                ['01', __('Connect a channel'), __('Facebook, Instagram, WhatsApp, Telegram. Whichever one your customers actually use. Add more later.')],
                ['02', __('Answer three questions'), __("What you sell. What customers ask most. Your voice. That's it. We build your AI's brain from there.")],
                ['03', __('Test it with a fake customer'), __('Chat with your own AI before any real customer sees it. Tweak the tone. Then flip it live.')],
            ] as [$num, $title, $body])
                <div class="relative" @if($loop->index === 1) style="margin-top: 4rem" @elseif($loop->index === 2) style="margin-top: 8rem" @endif>
                    <div class="serif text-8xl text-emer-100 leading-none absolute -top-4 -left-2 -z-10">{{ $num }}</div>
                    <h3 class="serif text-3xl mb-3 relative">{{ $title }}</h3>
                    <p class="text-ink/70 leading-relaxed">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════ FINAL CTA ═══════ --}}
<section class="bg-ink text-cream py-24 grain relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Every feature.') }} <span class="serif-it text-emer-400">{{ __('One flat price.') }}</span></h2>
        <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Start free. Pay by bank transfer only when it\'s working. No card, no auto-renew.') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start free — no card') }}</a>
            @endif
            <a href="{{ route('pricing') }}" class="text-cream/90 hover:text-cream font-medium u-link">{{ __('See pricing') }}</a>
        </div>
    </div>
</section>

</x-layouts.brand-marketing>
