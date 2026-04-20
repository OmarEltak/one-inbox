<x-layouts.marketing
    :title="__('One Inbox vs Freshchat — Social CRM Alternative | One Inbox')"
    :description="__('Comparing One Inbox vs Freshchat? One Inbox is built specifically for social messaging sales — WhatsApp, Instagram, Facebook & Telegram with AI lead qualification.')"
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
                {{ __('One Inbox vs Freshchat') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                {{ __('Freshchat is a powerful customer support platform built for enterprise ticketing. One Inbox is built specifically for social messaging sales — giving you an AI that qualifies leads, scores prospects, and closes deals across WhatsApp, Instagram, Facebook, and Telegram.') }}
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
                            <th class="px-6 py-4 text-center font-semibold text-zinc-500">Freshchat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('WhatsApp support'), '✅', '✅ ' . __('Add-on required')],
                            [__('Instagram DMs'), '✅', '⚠️ ' . __('Limited')],
                            [__('Facebook Messenger'), '✅', '✅'],
                            [__('Telegram'), '✅', '❌'],
                            [__('AI sales responder'), '✅ ' . __('Built-in'), '⚠️ ' . __('Support-focused bot only')],
                            [__('Lead scoring'), '✅ ' . __('AI-powered'), '❌'],
                            [__('AI-human handoff'), '✅ ' . __('Automatic'), '✅ ' . __('Support routing')],
                            [__('Free plan'), '✅', '✅ ' . __('Very limited')],
                            [__('Price (starting from)'), __('$0 / month'), __('~$19 / agent / month')],
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
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Three areas where One Inbox is the better choice over Freshchat for social sales teams.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '🎯',
                        __('Built for Sales, Not Support Tickets'),
                        __('Freshchat is built around customer support workflows — tickets, SLAs, resolution times, and help desk routing. One Inbox is built around sales — AI that qualifies leads, scores buying intent, and pushes toward a close. If your goal is to generate revenue from DMs, One Inbox is the right tool.'),
                    ],
                    [
                        '💸',
                        __('Flat Team Pricing vs. Per-Agent Costs'),
                        __('Freshchat charges per agent per month — costs grow rapidly as your team expands. One Inbox charges a flat team price. A 5-person sales team costs the same as a 10-person team on One Inbox. As you scale, the cost advantage compounds significantly.'),
                    ],
                    [
                        '🔌',
                        __('WhatsApp & Telegram Without Add-Ons'),
                        __('With Freshchat, WhatsApp requires additional setup and often extra costs. Telegram isn\'t supported at all. With One Inbox, WhatsApp, Instagram, Facebook, and Telegram are all included in every plan — no add-ons, no surprises on your invoice.'),
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
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Freshchat') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is One Inbox cheaper than Freshchat?'),
                        __('For most small and mid-size teams, yes. Freshchat\'s per-agent pricing adds up quickly — a team of 5 agents can easily exceed $100/month on Freshchat. One Inbox Pro is $79/month for the whole team with unlimited AI responses. For growing sales teams, the math is straightforward.'),
                    ],
                    [
                        __('Does One Inbox work for customer support too?'),
                        __('One Inbox handles both sales and support conversations in the same inbox. You can assign support conversations to specific team members, use the AI for FAQ-style questions, and escalate complex cases to human agents. It\'s not a ticketing system, but it covers most support use cases for businesses that operate through social messaging.'),
                    ],
                    [
                        __('Can I migrate from Freshchat to One Inbox?'),
                        __('Yes. Connect your WhatsApp, Instagram, Facebook, and Telegram accounts to One Inbox, train your AI agent with your product and support information, and onboard your team. The transition is fast — most teams are fully operational in One Inbox within a day.'),
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
                <p class="mx-auto mt-4 max-w-xl text-lg text-purple-100">{{ __('See why social sales teams choose One Inbox over Freshchat for lead qualification and deal closing.') }}</p>
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
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Is One Inbox cheaper than Freshchat?",
            "acceptedAnswer": {"@type": "Answer", "text": "For most small and mid-size teams, yes. Freshchat's per-agent pricing adds up quickly. One Inbox Pro is $79/month for the whole team with unlimited AI responses."}
        },
        {
            "@type": "Question",
            "name": "Does One Inbox work for customer support too?",
            "acceptedAnswer": {"@type": "Answer", "text": "One Inbox handles both sales and support conversations in the same inbox. You can assign support conversations to specific team members, use the AI for FAQ-style questions, and escalate complex cases to human agents."}
        },
        {
            "@type": "Question",
            "name": "Can I migrate from Freshchat to One Inbox?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. Connect your WhatsApp, Instagram, Facebook, and Telegram accounts to One Inbox, train your AI agent with your product and support information, and onboard your team. Most teams are fully operational within a day."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
