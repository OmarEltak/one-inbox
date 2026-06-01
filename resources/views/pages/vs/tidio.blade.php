<x-layouts.marketing
    :title="__('OT1-Pro vs Tidio — Better for Social Sales Teams')"
    :description="__('Comparing OT1-Pro vs Tidio? See why social-first businesses choose OT1-Pro — unified WhatsApp, Instagram, Facebook & Telegram inbox with AI sales agent, not just a website chat widget.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700 dark:border-indigo-200 dark:bg-indigo-50/50 dark:text-indigo-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('OT1-Pro vs Tidio') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-600">
                {{ __('Tidio is a website live chat tool with a chatbot bolted on. OT1-Pro is a unified social inbox built for businesses that sell on WhatsApp, Instagram, Facebook, and Telegram — with an AI sales agent that closes deals across every channel.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:bg-indigo-700">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Feature-by-feature comparison') }}</h2>
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-200 dark:bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-200">
                            <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-700">{{ __('Feature') }}</th>
                            <th class="px-6 py-4 text-center font-semibold text-indigo-600">OT1-Pro</th>
                            <th class="px-6 py-4 text-center font-semibold text-zinc-500">Tidio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('Website live chat'), '⚠️ ' . __('Coming soon'), '✅ ' . __('Core feature')],
                            [__('WhatsApp Business'), '✅ ' . __('Cloud API + QR'), '⚠️ ' . __('Add-on, paid plans only')],
                            [__('Instagram DMs'), '✅ ' . __('Native'), '✅'],
                            [__('Facebook Messenger'), '✅', '✅'],
                            [__('Telegram'), '✅', '❌ ' . __('Not supported')],
                            [__('AI sales responder'), '✅ ' . __('Generative, multi-language'), '⚠️ ' . __('Lyro AI — Plus plan only')],
                            [__('AI lead scoring'), '✅ ' . __('Automatic 0–100'), '❌'],
                            [__('AI-human handoff'), '✅ ' . __('Automatic on intent'), '⚠️ ' . __('Manual only')],
                            [__('Free plan with AI'), '✅', '❌ ' . __('AI is paid only')],
                            [__('Pricing model'), __('Flat per team'), __('Per-conversation, scales with traffic')],
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

    {{-- Where OT1-Pro Wins --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Where OT1-Pro wins') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Three areas where OT1-Pro is the better fit if you sell on social.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '📱',
                        __('Built for Social, Not for Widgets'),
                        __('Tidio\'s home turf is the website chat widget. WhatsApp and Instagram are tacked on as upsells. OT1-Pro is built social-first — WhatsApp Business, Instagram DMs, Facebook Messenger, and Telegram are first-class citizens with native API integrations, not bolt-ons.'),
                    ],
                    [
                        '🤖',
                        __('AI Included on the Free Plan'),
                        __('Tidio\'s Lyro AI is locked behind the Plus plan ($749/mo) and charges per conversation. OT1-Pro includes a generative AI sales agent on every plan — including the free tier — that learns your products, qualifies leads, and closes deals in any language.'),
                    ],
                    [
                        '💰',
                        __('Predictable Flat Pricing'),
                        __('Tidio prices per conversation, so a viral campaign or busy week can blow up your bill. OT1-Pro uses flat team-based pricing — your cost is predictable whether you handle 100 messages or 100,000. No surprise overage charges.'),
                    ],
                ];
                @endphp
                @foreach($wins as $win)
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-6 dark:border-indigo-200 dark:bg-indigo-50/40">
                    <div class="mb-3 text-3xl">{{ $win[0] }}</div>
                    <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-800">{{ $win[1] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ $win[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Tidio') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is OT1-Pro a Tidio alternative?'),
                        __('OT1-Pro is the right alternative to Tidio if most of your sales conversations happen on WhatsApp, Instagram, Facebook, or Telegram — not on your website chat widget. Tidio shines as a website live chat tool. OT1-Pro shines for businesses where customers DM, not browse to your homepage to chat.'),
                    ],
                    [
                        __('Does OT1-Pro replace Tidio\'s Lyro AI?'),
                        __('Yes — and it\'s included free. Tidio charges $749/month for Lyro AI plus per-conversation fees. OT1-Pro includes a generative AI sales agent on every plan, including the free tier. The AI is trained on your products, pricing, and brand voice, and it qualifies leads, handles objections, and closes deals across all your channels in any language.'),
                    ],
                    [
                        __('Can I move my existing customers from Tidio to OT1-Pro?'),
                        __('Yes. The migration path is straightforward: connect your WhatsApp Business number, Instagram, Facebook Page, and Telegram bot to OT1-Pro in a few minutes. New conversations flow into OT1-Pro immediately. You can keep Tidio running for the website chat widget if you still need that, or wait for OT1-Pro\'s upcoming web widget.'),
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
            <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-600 p-10 text-center text-white sm:p-16">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Try OT1-Pro free — no credit card required') }}</h2>
                <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('Join social-first businesses that switched from Tidio to close more deals on WhatsApp and Instagram with AI.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-indigo-800">{{ __('No credit card required · Free plan available') }}</p>
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
            "name": "Is OT1-Pro a Tidio alternative?",
            "acceptedAnswer": {"@@type": "Answer", "text": "OT1-Pro is the right alternative to Tidio if most of your sales conversations happen on WhatsApp, Instagram, Facebook, or Telegram — not on your website chat widget. Tidio shines as a website live chat tool. OT1-Pro shines for businesses where customers DM, not browse to your homepage to chat."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro replace Tidio's Lyro AI?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes — and it's included free. Tidio charges $749/month for Lyro AI plus per-conversation fees. OT1-Pro includes a generative AI sales agent on every plan, including the free tier."}
        },
        {
            "@@type": "Question",
            "name": "Can I move my existing customers from Tidio to OT1-Pro?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. The migration path is straightforward: connect your WhatsApp Business number, Instagram, Facebook Page, and Telegram bot to OT1-Pro in a few minutes. New conversations flow into OT1-Pro immediately."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
