<x-layouts.marketing
    :title="__('OT1-Pro vs Freshchat — Social CRM Alternative | OT1-Pro')"
    :description="__('Comparing OT1-Pro vs Freshchat? OT1-Pro is built specifically for social messaging sales — WhatsApp, Instagram, Facebook & Telegram with AI lead qualification.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700 dark:border-indigo-200 dark:bg-indigo-50/50 dark:text-indigo-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('OT1-Pro vs Freshchat') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-600">
                {{ __('Freshchat is a powerful customer support platform built for enterprise ticketing. OT1-Pro is built specifically for social messaging sales — giving you an AI that qualifies leads, scores prospects, and closes deals across WhatsApp, Instagram, Facebook, and Telegram.') }}
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
                <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Three areas where OT1-Pro is the better choice over Freshchat for social sales teams.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">
                @php
                $wins = [
                    [
                        '🎯',
                        __('Built for Sales, Not Support Tickets'),
                        __('Freshchat is built around customer support workflows — tickets, SLAs, resolution times, and help desk routing. OT1-Pro is built around sales — AI that qualifies leads, scores buying intent, and pushes toward a close. If your goal is to generate revenue from DMs, OT1-Pro is the right tool.'),
                    ],
                    [
                        '💸',
                        __('Flat Team Pricing vs. Per-Agent Costs'),
                        __('Freshchat charges per agent per month — costs grow rapidly as your team expands. OT1-Pro charges a flat team price. A 5-person sales team costs the same as a 10-person team on OT1-Pro. As you scale, the cost advantage compounds significantly.'),
                    ],
                    [
                        '🔌',
                        __('WhatsApp & Telegram Without Add-Ons'),
                        __('With Freshchat, WhatsApp requires additional setup and often extra costs. Telegram isn\'t supported at all. With OT1-Pro, WhatsApp, Instagram, Facebook, and Telegram are all included in every plan — no add-ons, no surprises on your invoice.'),
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
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Common questions about switching from Freshchat') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is OT1-Pro cheaper than Freshchat?'),
                        __('For most small and mid-size teams, yes. Freshchat\'s per-agent pricing adds up quickly — a team of 5 agents can easily exceed $100/month on Freshchat. OT1-Pro Pro is $79/month for the whole team with unlimited AI responses. For growing sales teams, the math is straightforward.'),
                    ],
                    [
                        __('Does OT1-Pro work for customer support too?'),
                        __('OT1-Pro handles both sales and support conversations in the same inbox. You can assign support conversations to specific team members, use the AI for FAQ-style questions, and escalate complex cases to human agents. It\'s not a ticketing system, but it covers most support use cases for businesses that operate through social messaging.'),
                    ],
                    [
                        __('Can I migrate from Freshchat to OT1-Pro?'),
                        __('Yes. Connect your WhatsApp, Instagram, Facebook, and Telegram accounts to OT1-Pro, train your AI agent with your product and support information, and onboard your team. The transition is fast — most teams are fully operational in OT1-Pro within a day.'),
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
                <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('See why social sales teams choose OT1-Pro over Freshchat for lead qualification and deal closing.') }}</p>
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
            "name": "Is OT1-Pro cheaper than Freshchat?",
            "acceptedAnswer": {"@@type": "Answer", "text": "For most small and mid-size teams, yes. Freshchat's per-agent pricing adds up quickly. OT1-Pro Pro is $79/month for the whole team with unlimited AI responses."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro work for customer support too?",
            "acceptedAnswer": {"@@type": "Answer", "text": "OT1-Pro handles both sales and support conversations in the same inbox. You can assign support conversations to specific team members, use the AI for FAQ-style questions, and escalate complex cases to human agents."}
        },
        {
            "@@type": "Question",
            "name": "Can I migrate from Freshchat to OT1-Pro?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Connect your WhatsApp, Instagram, Facebook, and Telegram accounts to OT1-Pro, train your AI agent with your product and support information, and onboard your team. Most teams are fully operational within a day."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
