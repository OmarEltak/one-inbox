<x-layouts.brand-marketing
    :title="__('OT1-Pro vs AiSensy — Multi-Channel WhatsApp Alternative for MENA | OT1-Pro')"
    :description="__('AiSensy is WhatsApp-only and built for the Indian market. OT1-Pro adds Instagram, Messenger, Telegram, and native Egyptian Arabic AI — with EGP payment via Paymob. Honest comparison inside.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-6 text-xs uppercase tracking-[0.2em] text-emer-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-7xl">
                {{ __('OT1-Pro vs AiSensy') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink/70">
                {{ __('AiSensy is a strong WhatsApp tool built for the Indian market. If you run a MENA storefront that needs Instagram DMs, Messenger, Telegram, and Arabic-first AI in the same inbox — here is the honest tradeoff comparison.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <p class="mt-4 text-sm text-ink/60">{{ __('No credit card required · Free plan available · EGP + USD payment') }}</p>
        </div>
    </section>

    {{-- When AiSensy is right --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('When AiSensy is the right choice') }}</h2>
            <p class="mt-4 leading-relaxed text-ink/70">{{ __('Credit where credit is due. AiSensy is a reasonable pick if:') }}</p>
            <ul class="mt-6 space-y-3 text-ink/80">
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('You are an Indian D2C brand doing 90%+ of orders on WhatsApp.') }}</li>
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('You pay in INR and want aggressive entry-tier pricing.') }}</li>
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('Your customers write in English or Hindi (not Arabic).') }}</li>
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('You are comfortable with a WhatsApp-only stack and separate tools for IG/Messenger.') }}</li>
            </ul>
            <p class="mt-6 text-sm text-ink/60">{{ __('If any of those do not match you — especially the Arabic and multi-channel parts — keep reading.') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="py-20">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('OT1-Pro vs AiSensy at a glance') }}</h2>
            <div class="overflow-x-auto rounded-2xl border border-line bg-cream">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line bg-ink text-cream">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Capability') }}</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-emer-400">OT1-Pro</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-cream/70">AiSensy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('WhatsApp Business API'), '✅ ' . __('Native (Cloud API)'), '✅ ' . __('Native')],
                            [__('Instagram DMs'), '✅', '❌'],
                            [__('Facebook Messenger'), '✅', '❌'],
                            [__('Telegram'), '✅', '❌'],
                            [__('Email inbox'), '✅', '❌'],
                            [__('Arabic AI quality'), '✅ ' . __('Dialect-aware'), '⚠️ ' . __('Weak on Arabic')],
                            [__('Free plan'), '✅ ' . __('Permanent, 20 AI responses/mo'), '❌ ' . __('Trial only')],
                            [__('Entry paid tier'), __('$8 / month (Basic)'), '~$25–40 / month'],
                            [__('Payment in EGP (Paymob)'), '✅', '❌ ' . __('Card only (INR/USD)')],
                            [__('Payment in USD globally'), '✅ ' . __('Paddle'), '✅'],
                            [__('Shopify'), '✅', '✅'],
                            [__('Salla / Zid (MENA)'), '✅', '❌'],
                            [__('MENA-hours support'), '✅ ' . __('Founder on WhatsApp'), '❌ ' . __('IST timezone')],
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

    {{-- The 3-question decision framework --}}
    <section class="border-y border-line bg-cream2 py-24 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('The 3-question decision framework') }}</h2>
                <p class="mt-4 leading-relaxed text-ink/70">{{ __('Skip the pros/cons debate. Ask yourself these three questions.') }}</p>
            </div>
            <div class="mt-12 space-y-6">
                @php
                $questions = [
                    [
                        __('1. Where do your customers actually message you?'),
                        __('Open Meta Business Suite → Inbox → filter last 30 days. Count WhatsApp vs Instagram vs Messenger. If less than 85% is WhatsApp, an AiSensy-style WhatsApp-only tool will leak sales.'),
                    ],
                    [
                        __('2. What language do 80%+ of your customers write in?'),
                        __('If it is Arabic (any dialect), your AI needs to speak Arabic natively — not translated from English or trained on Hindi corpora.'),
                    ],
                    [
                        __('3. Do you sell in EGP, SAR, or AED and need to pay locally?'),
                        __('OT1-Pro accepts EGP through Paymob and USD through Paddle. AiSensy is card-only in INR/USD, which adds FX friction and accounting overhead for MENA storefronts.'),
                    ],
                ];
                @endphp
                @foreach($questions as $q)
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <h3 class="serif text-2xl leading-snug text-ink">{{ $q[0] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $q[1] }}</p>
                </div>
                @endforeach
            </div>
            <p class="mt-8 text-center text-sm text-ink/60">{{ __('Answer "no" to any → AiSensy is workable. Answer "yes" to any → OT1-Pro is the better fit.') }}</p>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-24">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Common questions about switching from AiSensy') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
                @php
                $faqs = [
                    [
                        __('Is OT1-Pro cheaper than AiSensy?'),
                        __('At the entry tier, yes — OT1-Pro starts at $8/month vs AiSensy\'s ~$25–40/month base. On higher volumes, both charge Meta WhatsApp conversation costs as pass-through. OT1-Pro pulls ahead when you factor in the multi-channel value — Instagram, Messenger, and Telegram are included, whereas with AiSensy you would need separate tools for each.'),
                    ],
                    [
                        __('Can OT1-Pro handle Indian-style WhatsApp broadcasting?'),
                        __('Yes. OT1-Pro supports segment-based WhatsApp campaigns with template messages, opt-in tracking, and Meta compliance. If you send 20k+ broadcast messages per month, talk to us so we can walk through queueing and delivery-window strategy — but the capability is there.'),
                    ],
                    [
                        __('Does OT1-Pro integrate with Shopify, Salla, and Zid?'),
                        __('Yes to all three. Shopify via native app, Salla and Zid via webhook. Order events, abandoned carts, and shipment updates trigger WhatsApp/IG/Messenger flows automatically.'),
                    ],
                    [
                        __('How is OT1-Pro\'s Arabic AI different from AiSensy\'s?'),
                        __('OT1-Pro routes AI replies through Anthropic Claude via the NaraRouter gateway. Claude handles Egyptian, Gulf, and Levantine Arabic dialects natively — including English/Arabic code-switching ("عايز الأبيض medium please") which is how MENA customers actually write. AiSensy\'s AI is trained primarily on English and Indian-language corpora.'),
                    ],
                    [
                        __('What about payment in EGP or SAR?'),
                        __('OT1-Pro accepts EGP through Paymob for Egyptian customers and USD globally through Paddle (which handles VAT/tax compliance and issues proper invoices). AiSensy is card-only in INR/USD, which typically means 3–5% FX markup on your bill and no local invoicing.'),
                    ],
                    [
                        __('What if I run into issues at 11pm during a big campaign?'),
                        __('Message the founder directly on WhatsApp at +20 102 636 1218. That is how MENA customers get support — not a marketing gimmick.'),
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
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('All 4 channels, Arabic AI, EGP + USD payment. See it working with your real messages in 30 minutes.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start Free with OT1-Pro') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
            <p class="mt-4 text-sm text-cream/60">{{ __('Free plan available · Founder-accessible on WhatsApp') }}</p>
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
            "name": "Is OT1-Pro cheaper than AiSensy?",
            "acceptedAnswer": {"@@type": "Answer", "text": "At the entry tier, yes — OT1-Pro starts at $8/month vs AiSensy's ~$25–40/month base. OT1-Pro also includes Instagram, Messenger, and Telegram in one price."}
        },
        {
            "@@type": "Question",
            "name": "Can OT1-Pro handle WhatsApp broadcasting like AiSensy?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro supports segment-based WhatsApp campaigns with template messages, opt-in tracking, and Meta compliance."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro integrate with Shopify, Salla, and Zid?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes to all three. Shopify via native app, Salla and Zid via webhook."}
        },
        {
            "@@type": "Question",
            "name": "How is OT1-Pro's Arabic AI different from AiSensy's?",
            "acceptedAnswer": {"@@type": "Answer", "text": "OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian, Gulf, and Levantine Arabic dialects natively — including English/Arabic code-switching that is common in MENA."}
        },
        {
            "@@type": "Question",
            "name": "Can I pay in EGP or other MENA currencies?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro accepts EGP through Paymob and USD globally through Paddle. AiSensy is card-only in INR/USD."}
        }
    ]
}
</script>
@endpush

</x-layouts.brand-marketing>
