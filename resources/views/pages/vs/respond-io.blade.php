<x-layouts.marketing
    :title="__('One Inbox vs Respond.io — Affordable WhatsApp CRM Alternative | One Inbox')"
    :description="__('Looking for a respond.io alternative? One Inbox offers the same WhatsApp & social inbox capabilities with a simpler setup and better pricing for small and mid-size businesses.')"
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
                {{ __('One Inbox vs Respond.io') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                {{ __('Respond.io is a powerful omnichannel platform built for larger enterprises. One Inbox delivers the same WhatsApp and social inbox capabilities with a simpler setup, a free plan, and pricing that makes sense for growing teams.') }}
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
                            <th class="px-6 py-4 text-center font-semibold text-zinc-500">Respond.io</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('WhatsApp support'), '✅', '✅'],
                            [__('Instagram DMs'), '✅', '✅'],
                            [__('Facebook Messenger'), '✅', '✅'],
                            [__('Telegram'), '✅', '✅'],
                            [__('AI sales responder'), '✅ ' . __('Built-in'), '⚠️ ' . __('Add-on required')],
                            [__('Lead scoring'), '✅ ' . __('AI-powered'), '⚠️ ' . __('Manual tagging')],
                            [__('AI-human handoff'), '✅ ' . __('Automatic'), '✅ ' . __('Workflow-based')],
                            [__('Free plan'), '✅', '❌ ' . __('Trial only')],
                            [__('Price (starting from)'), __('$0 / month'), __('~$79 / month')],
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
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Three areas where One Inbox gives you more value than Respond.io for small and mid-size businesses.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '⚡',
                        __('Simpler Setup, Faster Onboarding'),
                        __('Respond.io is powerful but complex — setting up workflows, automations, and AI features can take days of configuration. One Inbox is designed to be up and running in minutes. Connect your channels, describe your business to the AI, and your sales agent is live. No consultants needed, no steep learning curve.'),
                    ],
                    [
                        '💰',
                        __('No Enterprise Pricing for Small Teams'),
                        __('Respond.io starts at ~$79/month with no free plan, and costs scale significantly as you add users and features. One Inbox has a free tier and paid plans starting at $29/month. For a small or growing team, you get the same core capabilities at a fraction of the cost.'),
                    ],
                    [
                        '🤖',
                        __('AI Sales Agent Included by Default'),
                        __('On Respond.io, AI capabilities are typically add-ons or require workflow setup. On One Inbox, the AI sales responder is built in and ready to go from day one — trained on your product information, scoring leads automatically, and handing off hot prospects without any extra configuration.'),
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
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Respond.io') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is One Inbox a good Respond.io alternative for small businesses?'),
                        __('Yes. Respond.io is designed for larger teams with complex omnichannel needs. One Inbox focuses on exactly the channels that drive social sales — WhatsApp, Instagram, Facebook, and Telegram — with an AI agent included out of the box. For small and mid-size businesses, One Inbox delivers 80% of what Respond.io offers at 20% of the cost.'),
                    ],
                    [
                        __('Does One Inbox have the same WhatsApp capabilities as Respond.io?'),
                        __('Yes. Both platforms connect via the official WhatsApp Business API. One Inbox supports multiple WhatsApp numbers, shared team inbox, AI automation, and full conversation history. For most business use cases, the WhatsApp experience on One Inbox is equivalent — with the added advantage of AI lead scoring built in.'),
                    ],
                    [
                        __('Can I migrate from Respond.io to One Inbox?'),
                        __('Yes. The migration is straightforward. Reconnect your WhatsApp Business API number, Instagram, Facebook, and Telegram accounts to One Inbox, configure your AI sales agent, and move your team across. One Inbox offers onboarding support to make the transition smooth.'),
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
                <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('Get the WhatsApp and social inbox power of Respond.io without the enterprise price tag.') }}</p>
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
            "name": "Is One Inbox a good Respond.io alternative for small businesses?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. One Inbox focuses on exactly the channels that drive social sales — WhatsApp, Instagram, Facebook, and Telegram — with an AI agent included out of the box. For small and mid-size businesses, One Inbox delivers 80% of what Respond.io offers at 20% of the cost."}
        },
        {
            "@@type": "Question",
            "name": "Does One Inbox have the same WhatsApp capabilities as Respond.io?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Both platforms connect via the official WhatsApp Business API. One Inbox supports multiple WhatsApp numbers, shared team inbox, AI automation, and full conversation history, with the added advantage of AI lead scoring built in."}
        },
        {
            "@@type": "Question",
            "name": "Can I migrate from Respond.io to One Inbox?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Reconnect your WhatsApp Business API number, Instagram, Facebook, and Telegram accounts to One Inbox, configure your AI sales agent, and move your team across. One Inbox offers onboarding support to make the transition smooth."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
