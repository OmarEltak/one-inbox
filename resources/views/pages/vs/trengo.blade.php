<x-layouts.marketing
    :title="__('One Inbox vs Trengo — Better Alternative with AI Sales Automation | One Inbox')"
    :description="__('Comparing One Inbox vs Trengo? See why growing businesses choose One Inbox — AI sales responder, unified social inbox, and lead scoring at a fraction of the price.')"
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
                {{ __('One Inbox vs Trengo') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                {{ __('Trengo is a solid team inbox tool — but it wasn\'t built for social sales. One Inbox combines WhatsApp, Instagram, Facebook & Telegram in one inbox with an AI that actively qualifies leads and closes deals for you.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-purple-500/25 transition-all hover:bg-purple-700">
                    {{ __('Start Free with One Inbox') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-purple-300 hover:text-purple-700 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('See Pricing') }}
                </a> --}}
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
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Three areas where One Inbox leaves Trengo behind.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '🤖',
                        __('AI That Actually Sells'),
                        __('Trengo gives you a shared inbox and some automation triggers. One Inbox gives you an AI sales agent trained on your products, pricing, and brand voice. It qualifies leads, overcomes objections, and closes deals without your team lifting a finger — 24 hours a day, in any language.'),
                    ],
                    [
                        '🎯',
                        __('Lead Scoring Out of the Box'),
                        __('With Trengo, you manually label and prioritize conversations. With One Inbox, every conversation gets an AI lead score from 0–100 the moment it arrives. Your team sees instantly who\'s ready to buy — no manual tagging, no guessing, no missed opportunities.'),
                    ],
                    [
                        '💰',
                        __('Better Pricing for Small Teams'),
                        __('Trengo requires a paid subscription from day one. One Inbox offers a free plan that\'s actually useful — 1 connected channel with AI responses included. And our paid plans start at a fraction of what Trengo charges for comparable features.'),
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
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Trengo') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is One Inbox cheaper than Trengo?'),
                        __('Yes, significantly. Trengo starts at ~$25/month per user with no free plan. One Inbox has a free tier and paid plans starting at $29/month for the entire team — not per seat. As your team grows, the savings multiply.'),
                    ],
                    [
                        __('Does One Inbox have everything Trengo has?'),
                        __('One Inbox covers all the core features: shared inbox, WhatsApp, Instagram, Facebook, Telegram, team assignments, and internal notes. Where One Inbox goes further is AI sales automation — a built-in sales responder, AI lead scoring, and automatic handoff. Trengo\'s automation is rule-based; One Inbox\'s AI is generative and context-aware.'),
                    ],
                    [
                        __('Can I migrate from Trengo to One Inbox?'),
                        __('Yes. Migrating is straightforward — reconnect your social channels (WhatsApp, Instagram, Facebook, Telegram) to One Inbox, configure your AI sales agent, and invite your team. New conversations start flowing in immediately. Historical conversations can be exported from Trengo if needed.'),
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
                <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('Join businesses that switched from Trengo and now close more deals with less manual effort.') }}</p>
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
            "name": "Is One Inbox cheaper than Trengo?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes, significantly. Trengo starts at ~$25/month per user with no free plan. One Inbox has a free tier and paid plans starting at $29/month for the entire team — not per seat."}
        },
        {
            "@@type": "Question",
            "name": "Does One Inbox have everything Trengo has?",
            "acceptedAnswer": {"@@type": "Answer", "text": "One Inbox covers all the core features: shared inbox, WhatsApp, Instagram, Facebook, Telegram, team assignments, and internal notes. Where One Inbox goes further is AI sales automation — a built-in sales responder, AI lead scoring, and automatic handoff."}
        },
        {
            "@@type": "Question",
            "name": "Can I migrate from Trengo to One Inbox?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Migrating is straightforward — reconnect your social channels to One Inbox, configure your AI sales agent, and invite your team. New conversations start flowing in immediately."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
