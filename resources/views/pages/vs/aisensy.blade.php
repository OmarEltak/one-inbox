<x-layouts.marketing
    :title="__('OT1-Pro vs AiSensy — Multi-Channel WhatsApp Alternative for MENA | OT1-Pro')"
    :description="__('AiSensy is WhatsApp-only and built for the Indian market. OT1-Pro adds Instagram, Messenger, Telegram, and native Egyptian Arabic AI — with EGP payment via Paymob. Honest comparison inside.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700 dark:border-indigo-200 dark:bg-indigo-50/50 dark:text-indigo-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('OT1-Pro vs AiSensy') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-600">
                {{ __('AiSensy is a strong WhatsApp tool built for the Indian market. If you run a MENA storefront that needs Instagram DMs, Messenger, Telegram, and Arabic-first AI in the same inbox — here is the honest tradeoff comparison.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:bg-indigo-700">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available · EGP + USD payment') }}</p>
        </div>
    </section>

    {{-- When AiSensy is right --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('When AiSensy is the right choice') }}</h2>
            <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Credit where credit is due. AiSensy is a reasonable pick if:') }}</p>
            <ul class="mt-6 space-y-3 text-zinc-700 dark:text-zinc-700">
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('You are an Indian D2C brand doing 90%+ of orders on WhatsApp.') }}</li>
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('You pay in INR and want aggressive entry-tier pricing.') }}</li>
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('Your customers write in English or Hindi (not Arabic).') }}</li>
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('You are comfortable with a WhatsApp-only stack and separate tools for IG/Messenger.') }}</li>
            </ul>
            <p class="mt-6 text-sm text-zinc-500">{{ __('If any of those do not match you — especially the Arabic and multi-channel parts — keep reading.') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="py-16">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold tracking-tight sm:text-4xl">{{ __('OT1-Pro vs AiSensy at a glance') }}</h2>
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-200 dark:bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-200">
                            <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-700">{{ __('Capability') }}</th>
                            <th class="px-6 py-4 text-center font-semibold text-indigo-600">OT1-Pro</th>
                            <th class="px-6 py-4 text-center font-semibold text-zinc-500">AiSensy</th>
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
                        <tr class="{{ $i % 2 === 0 ? 'bg-zinc-50 dark:bg-zinc-100' : '' }} border-b border-zinc-100 last:border-0 dark:border-zinc-200">
                            <td class="px-6 py-4 font-medium text-zinc-700 dark:text-zinc-700">{{ $row[0] }}</td>
                            <td class="px-6 py-4 text-center text-zinc-700 dark:text-zinc-700">{{ $row[1] }}</td>
                            <td class="px-6 py-4 text-center text-zinc-500">{{ $row[2] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- The 3-question decision framework --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-200 dark:bg-white lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('The 3-question decision framework') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Skip the pros/cons debate. Ask yourself these three questions.') }}</p>
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
                <div class="rounded-2xl border border-indigo-100 bg-white p-6 dark:border-indigo-200 dark:bg-white">
                    <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-800">{{ $q[0] }}</h3>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-600">{{ $q[1] }}</p>
                </div>
                @endforeach
            </div>
            <p class="mt-8 text-center text-sm text-zinc-500">{{ __('Answer "no" to any → AiSensy is workable. Answer "yes" to any → OT1-Pro is the better fit.') }}</p>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from AiSensy') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
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
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-200 dark:bg-white" x-data>
                    <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between px-6 py-4 text-left cursor-pointer">
                        <span class="font-medium">{{ $faq[0] }}</span>
                        <svg class="size-5 flex-shrink-0 text-zinc-600 transition-transform" :class="open === {{ $i }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse>
                        <div class="px-6 pb-4 text-sm text-zinc-600 dark:text-zinc-600">{{ $faq[1] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-10 text-center sm:p-16">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('Try OT1-Pro free — no credit card required') }}</h2>
                <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('All 4 channels, Arabic AI, EGP + USD payment. See it working with your real messages in 30 minutes.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-indigo-800">{{ __('Free plan available · Founder-accessible on WhatsApp') }}</p>
            </div>
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

</x-layouts.marketing>
