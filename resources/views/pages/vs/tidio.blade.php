<x-layouts.marketing
    :title="__('One Inbox vs Tidio — Better for Social Sales Teams')"
    :description="__('Comparing One Inbox vs Tidio? See why social-first businesses choose One Inbox — unified WhatsApp, Instagram, Facebook & Telegram inbox with AI sales agent, not just a website chat widget.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-0 size-[500px] rounded-full bg-purple-500/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/3 size-[400px] rounded-full bg-violet-500/8 blur-3xl"></div>
        </div>
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-purple-200 bg-purple-50 px-4 py-1.5 text-sm font-medium text-purple-700 dark:border-purple-800 dark:bg-purple-950/50 dark:text-purple-300">
                {{ __('Comparison') }}
            </div>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('One Inbox vs Tidio') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                {{ __('Tidio is a website live chat tool with a chatbot bolted on. One Inbox is a unified social inbox built for businesses that sell on WhatsApp, Instagram, Facebook, and Telegram — with an AI sales agent that closes deals across every channel.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-purple-500/25 transition-all hover:bg-purple-700">
                    {{ __('Start Free with One Inbox') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Feature-by-feature comparison') }}</h2>
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Feature') }}</th>
                            <th class="px-6 py-4 text-center font-semibold text-purple-600">One Inbox</th>
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
                        <tr class="{{ $i % 2 === 0 ? 'bg-zinc-50 dark:bg-zinc-800/50' : '' }} border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-6 py-4 font-medium text-zinc-700 dark:text-zinc-300">{{ $row[0] }}</td>
                            <td class="px-6 py-4 text-center text-zinc-700 dark:text-zinc-300">{{ $row[1] }}</td>
                            <td class="px-6 py-4 text-center text-zinc-500">{{ $row[2] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Where One Inbox Wins --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Where One Inbox wins') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Three areas where One Inbox is the better fit if you sell on social.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '📱',
                        __('Built for Social, Not for Widgets'),
                        __('Tidio\'s home turf is the website chat widget. WhatsApp and Instagram are tacked on as upsells. One Inbox is built social-first — WhatsApp Business, Instagram DMs, Facebook Messenger, and Telegram are first-class citizens with native API integrations, not bolt-ons.'),
                    ],
                    [
                        '🤖',
                        __('AI Included on the Free Plan'),
                        __('Tidio\'s Lyro AI is locked behind the Plus plan ($749/mo) and charges per conversation. One Inbox includes a generative AI sales agent on every plan — including the free tier — that learns your products, qualifies leads, and closes deals in any language.'),
                    ],
                    [
                        '💰',
                        __('Predictable Flat Pricing'),
                        __('Tidio prices per conversation, so a viral campaign or busy week can blow up your bill. One Inbox uses flat team-based pricing — your cost is predictable whether you handle 100 messages or 100,000. No surprise overage charges.'),
                    ],
                ];
                @endphp
                @foreach($wins as $win)
                <div class="rounded-2xl border border-purple-100 bg-purple-50 p-6 dark:border-purple-900/40 dark:bg-purple-950/20">
                    <div class="mb-3 text-3xl">{{ $win[0] }}</div>
                    <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-200">{{ $win[1] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $win[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Tidio') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is One Inbox a Tidio alternative?'),
                        __('One Inbox is the right alternative to Tidio if most of your sales conversations happen on WhatsApp, Instagram, Facebook, or Telegram — not on your website chat widget. Tidio shines as a website live chat tool. One Inbox shines for businesses where customers DM, not browse to your homepage to chat.'),
                    ],
                    [
                        __('Does One Inbox replace Tidio\'s Lyro AI?'),
                        __('Yes — and it\'s included free. Tidio charges $749/month for Lyro AI plus per-conversation fees. One Inbox includes a generative AI sales agent on every plan, including the free tier. The AI is trained on your products, pricing, and brand voice, and it qualifies leads, handles objections, and closes deals across all your channels in any language.'),
                    ],
                    [
                        __('Can I move my existing customers from Tidio to One Inbox?'),
                        __('Yes. The migration path is straightforward: connect your WhatsApp Business number, Instagram, Facebook Page, and Telegram bot to One Inbox in a few minutes. New conversations flow into One Inbox immediately. You can keep Tidio running for the website chat widget if you still need that, or wait for One Inbox\'s upcoming web widget.'),
                    ],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900" x-data>
                    <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between px-6 py-4 text-left cursor-pointer">
                        <span class="font-medium">{{ $faq[0] }}</span>
                        <svg class="size-5 flex-shrink-0 text-zinc-400 transition-transform" :class="open === {{ $i }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse>
                        <div class="px-6 pb-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $faq[1] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="rounded-3xl bg-gradient-to-br from-purple-600 to-violet-600 p-10 text-center text-white sm:p-16">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Try One Inbox free — no credit card required') }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('Join social-first businesses that switched from Tidio to close more deals on WhatsApp and Instagram with AI.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-purple-700 shadow-lg transition-all hover:bg-purple-50">
                    {{ __('Start Free with One Inbox') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-purple-200">{{ __('No credit card required · Free plan available') }}</p>
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
            "name": "Is One Inbox a Tidio alternative?",
            "acceptedAnswer": {"@@type": "Answer", "text": "One Inbox is the right alternative to Tidio if most of your sales conversations happen on WhatsApp, Instagram, Facebook, or Telegram — not on your website chat widget. Tidio shines as a website live chat tool. One Inbox shines for businesses where customers DM, not browse to your homepage to chat."}
        },
        {
            "@@type": "Question",
            "name": "Does One Inbox replace Tidio's Lyro AI?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes — and it's included free. Tidio charges $749/month for Lyro AI plus per-conversation fees. One Inbox includes a generative AI sales agent on every plan, including the free tier."}
        },
        {
            "@@type": "Question",
            "name": "Can I move my existing customers from Tidio to One Inbox?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. The migration path is straightforward: connect your WhatsApp Business number, Instagram, Facebook Page, and Telegram bot to One Inbox in a few minutes. New conversations flow into One Inbox immediately."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
