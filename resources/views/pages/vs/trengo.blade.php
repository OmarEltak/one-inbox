<x-layouts.marketing
    :title="__('OT1-Pro vs Trengo — Better Alternative for Sales Teams')"
    :description="__('Comparing OT1-Pro vs Trengo? See why growing businesses choose OT1-Pro — AI sales responder, unified social inbox, and lead scoring at a fraction of the price.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700 dark:border-indigo-200 dark:bg-indigo-50/50 dark:text-indigo-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('OT1-Pro vs Trengo') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-600">
                {{ __('Trengo is a solid team inbox tool — but it wasn\'t built for social sales. OT1-Pro combines WhatsApp, Instagram, Facebook & Telegram in one inbox with an AI that actively qualifies leads and closes deals for you.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:bg-indigo-700">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-indigo-300 hover:text-indigo-700 dark:border-zinc-200 dark:text-zinc-700">
                    {{ __('See Pricing') }}
                </a> --}}
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
                            <th class="px-6 py-4 text-center font-semibold text-zinc-500">Trengo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('WhatsApp support'), '✅', '✅'],
                            [__('Instagram DMs'), '✅', '✅'],
                            [__('Facebook Messenger'), '✅', '✅'],
                            [__('Telegram'), '✅', '⚠️ ' . __('Limited')],
                            [__('AI sales responder'), '✅ ' . __('Built-in'), '❌ ' . __('Not included')],
                            [__('Lead scoring'), '✅ ' . __('AI-powered'), '❌'],
                            [__('AI-human handoff'), '✅ ' . __('Automatic'), '⚠️ ' . __('Manual only')],
                            [__('Free plan'), '✅', '❌ ' . __('Trial only')],
                            [__('Price (starting from)'), __('$0 / month'), __('~$25 / month')],
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
                <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Three areas where OT1-Pro leaves Trengo behind.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '🤖',
                        __('AI That Actually Sells'),
                        __('Trengo gives you a shared inbox and some automation triggers. OT1-Pro gives you an AI sales agent trained on your products, pricing, and brand voice. It qualifies leads, overcomes objections, and closes deals without your team lifting a finger — 24 hours a day, in any language.'),
                    ],
                    [
                        '🎯',
                        __('Lead Scoring Out of the Box'),
                        __('With Trengo, you manually label and prioritize conversations. With OT1-Pro, every conversation gets an AI lead score from 0–100 the moment it arrives. Your team sees instantly who\'s ready to buy — no manual tagging, no guessing, no missed opportunities.'),
                    ],
                    [
                        '💰',
                        __('Better Pricing for Small Teams'),
                        __('Trengo requires a paid subscription from day one. OT1-Pro offers a free plan that\'s actually useful — 1 connected channel with AI responses included. And our paid plans start at a fraction of what Trengo charges for comparable features.'),
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
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Trengo') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is OT1-Pro cheaper than Trengo?'),
                        __('Yes, significantly. Trengo starts at ~$25/month per user with no free plan. OT1-Pro has a free tier and paid plans starting at $29/month for the entire team — not per seat. As your team grows, the savings multiply.'),
                    ],
                    [
                        __('Does OT1-Pro have everything Trengo has?'),
                        __('OT1-Pro covers all the core features: shared inbox, WhatsApp, Instagram, Facebook, Telegram, team assignments, and internal notes. Where OT1-Pro goes further is AI sales automation — a built-in sales responder, AI lead scoring, and automatic handoff. Trengo\'s automation is rule-based; OT1-Pro\'s AI is generative and context-aware.'),
                    ],
                    [
                        __('Can I migrate from Trengo to OT1-Pro?'),
                        __('Yes. Migrating is straightforward — reconnect your social channels (WhatsApp, Instagram, Facebook, Telegram) to OT1-Pro, configure your AI sales agent, and invite your team. New conversations start flowing in immediately. Historical conversations can be exported from Trengo if needed.'),
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
                <p class="mx-auto mt-4 max-w-xl text-lg text-indigo-100">{{ __('Join businesses that switched from Trengo and now close more deals with less manual effort.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-indigo-700 shadow-lg transition-all hover:bg-indigo-50">
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
            "name": "Is OT1-Pro cheaper than Trengo?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes, significantly. Trengo starts at ~$25/month per user with no free plan. OT1-Pro has a free tier and paid plans starting at $29/month for the entire team — not per seat."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro have everything Trengo has?",
            "acceptedAnswer": {"@@type": "Answer", "text": "OT1-Pro covers all the core features: shared inbox, WhatsApp, Instagram, Facebook, Telegram, team assignments, and internal notes. Where OT1-Pro goes further is AI sales automation — a built-in sales responder, AI lead scoring, and automatic handoff."}
        },
        {
            "@@type": "Question",
            "name": "Can I migrate from Trengo to OT1-Pro?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Migrating is straightforward — reconnect your social channels to OT1-Pro, configure your AI sales agent, and invite your team. New conversations start flowing in immediately."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
