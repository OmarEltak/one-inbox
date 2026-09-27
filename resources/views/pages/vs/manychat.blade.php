<x-layouts.brand-marketing
    :title="__('OT1-Pro vs ManyChat — AI Social Inbox Alternative | OT1-Pro')"
    :description="__('Looking for a ManyChat alternative? OT1-Pro combines WhatsApp, Instagram, Facebook & Telegram in one inbox with AI that qualifies leads and closes deals.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-6 text-xs uppercase tracking-[0.2em] text-emer-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-7xl">
                {{ __('OT1-Pro vs ManyChat') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink/70">
                {{ __('ManyChat is great for broadcast campaigns and simple chatbot flows. But if you want a real AI that manages live sales conversations across WhatsApp, Instagram, Facebook, and Telegram — OT1-Pro is the better choice.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                    {{ __('See Pricing') }}
                </a> --}}
            </div>
            <p class="mt-4 text-sm text-ink/60">{{ __('No credit card required · Free plan available') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('Feature-by-feature comparison') }}</h2>
            <div class="overflow-x-auto rounded-2xl border border-line bg-cream">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line bg-ink text-cream">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Feature') }}</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-emer-400">OT1-Pro</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-cream/70">ManyChat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('WhatsApp support'), '✅', '✅'],
                            [__('Instagram DMs'), '✅', '✅'],
                            [__('Facebook Messenger'), '✅', '✅'],
                            [__('Telegram'), '✅', '❌'],
                            [__('AI sales responder'), '✅ ' . __('Built-in'), '⚠️ ' . __('Rule-based flows only')],
                            [__('Lead scoring'), '✅ ' . __('AI-powered'), '❌'],
                            [__('AI-human handoff'), '✅ ' . __('Automatic'), '⚠️ ' . __('Requires manual setup')],
                            [__('Free plan'), '✅', '✅ ' . __('Limited')],
                            [__('Price (starting from)'), __('$0 / month'), __('$15 / month')],
                        ];
                        @endphp
                        @foreach($rows as $i => $row)
                        <tr class="{{ $i % 2 === 0 ? 'bg-cream2/60' : '' }} border-b border-line last:border-0">
                            <td class="px-6 py-4 font-medium text-ink">{{ $row[0] }}</td>
                            <td class="px-6 py-4 text-center text-ink/80">{{ $row[1] }}</td>
                            <td class="px-6 py-4 text-center text-ink/60">{{ $row[2] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Where OT1-Pro Wins --}}
    <section class="py-24 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Where OT1-Pro wins') }}</h2>
                <p class="mt-4 leading-relaxed text-ink/70">{{ __('Three areas where OT1-Pro outperforms ManyChat for sales-focused businesses.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '🧠',
                        __('Real AI vs. Rule-Based Flows'),
                        __('ManyChat relies on pre-built chatbot flows — if a customer says something unexpected, the bot breaks down. OT1-Pro uses a generative AI sales agent that understands context, handles objections, answers new questions, and adapts in real time. No flow building, no dead ends, no frustrated customers.'),
                    ],
                    [
                        '📊',
                        __('Sales Intelligence Built In'),
                        __('ManyChat tracks opens and clicks for broadcasts. OT1-Pro scores every live conversation by purchase intent, identifies hot leads, and routes them to the right sales rep automatically. You don\'t just know who opened a message — you know who\'s ready to buy right now.'),
                    ],
                    [
                        '📱',
                        __('Telegram Support'),
                        __('ManyChat doesn\'t support Telegram at all. OT1-Pro fully integrates Telegram alongside WhatsApp, Instagram, and Facebook — all in one shared inbox. If your customers are on Telegram (especially in the Middle East and Eastern Europe), OT1-Pro is the only option that covers all four channels.'),
                    ],
                ];
                @endphp
                @foreach($wins as $win)
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <div class="mb-3 text-3xl">{{ $win[0] }}</div>
                    <h3 class="serif text-2xl leading-snug text-ink">{{ $win[1] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $win[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Common questions about switching from ManyChat') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
                @php
                $faqs = [
                    [
                        __('Is OT1-Pro a ManyChat alternative for WhatsApp and Instagram?'),
                        __('Yes, and then some. OT1-Pro handles WhatsApp, Instagram DMs, Facebook Messenger, and Telegram — all in a single shared inbox. Instead of building chatbot flows, you configure an AI sales agent that handles every incoming message intelligently.'),
                    ],
                    [
                        __('Does OT1-Pro support broadcast campaigns like ManyChat?'),
                        __('OT1-Pro is focused on inbound conversations and live sales — AI responding to messages that come in. Bulk broadcast campaigns are a different use case. If you need both broadcast marketing and live sales automation, OT1-Pro handles the sales side extremely well.'),
                    ],
                    [
                        __('Can I migrate from ManyChat to OT1-Pro?'),
                        __('Yes. Reconnect your WhatsApp Business, Instagram, Facebook, and Telegram accounts to OT1-Pro, configure your AI agent with your product information, and invite your team. Your AI is ready in minutes — no complex flow building required.'),
                    ],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                <details class="py-5">
                    <summary class="flex items-center justify-between gap-4">
                        <span class="serif text-xl text-ink">{{ $faq[0] }}</span>
                        <span class="chev serif text-2xl text-emer-700">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $faq[1] }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════ FINAL CTA ═══════ --}}
    <section class="bg-ink text-cream py-24 grain relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Try OT1-Pro free — no credit card required') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Move beyond chatbot flows. Use real AI that understands your customers and closes more deals.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start Free with OT1-Pro') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
            <p class="mt-4 text-sm text-cream/60">{{ __('No credit card required · Free plan available') }}</p>
        </div>
    </section>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "Is OT1-Pro a ManyChat alternative for WhatsApp and Instagram?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes, and then some. OT1-Pro handles WhatsApp, Instagram DMs, Facebook Messenger, and Telegram — all in a single shared inbox with a real AI sales agent."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro support broadcast campaigns like ManyChat?",
            "acceptedAnswer": {"@@type": "Answer", "text": "OT1-Pro is focused on inbound conversations and live sales — AI responding to messages that come in. It handles the live sales side extremely well."}
        },
        {
            "@@type": "Question",
            "name": "Can I migrate from ManyChat to OT1-Pro?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Reconnect your WhatsApp Business, Instagram, Facebook, and Telegram accounts to OT1-Pro, configure your AI agent with your product information, and invite your team. Your AI is ready in minutes."}
        }
    ]
}
</script>
@endpush

</x-layouts.brand-marketing>
