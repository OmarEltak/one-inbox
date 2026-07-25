<x-layouts.marketing
    :title="__('OT1-Pro vs WATI — Multi-Channel WhatsApp Alternative for MENA | OT1-Pro')"
    :description="__('Looking for a WATI alternative? OT1-Pro adds Instagram, Messenger, and Telegram to your WhatsApp inbox with native Egyptian Arabic AI and per-seat pricing starting at $8/mo.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700 dark:border-indigo-200 dark:bg-indigo-50/50 dark:text-indigo-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                {{ __('OT1-Pro vs WATI') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-600">
                {{ __('WATI does WhatsApp well. But if you sell on Instagram, Messenger, and Telegram too — or you need Arabic-first AI and per-seat pricing that doesn\'t punish growth — OT1-Pro is the WATI alternative built for MENA storefronts.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:bg-indigo-700">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available · Talk to founder on WhatsApp') }}</p>
        </div>
    </section>

    {{-- Who WATI is right for (honest positioning) --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('When WATI is actually the right choice') }}</h2>
            <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Honesty first. If any of these describe you, stay on WATI — it works well for the WhatsApp-only use case:') }}</p>
            <ul class="mt-6 space-y-3 text-zinc-700 dark:text-zinc-700">
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('You sell almost entirely on WhatsApp (90%+ of orders come from WA).') }}</li>
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('Your team is comfortable with WhatsApp Business API concepts — templates, session windows, opt-in tracking.') }}</li>
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('You send heavy WhatsApp broadcast campaigns to a large opted-in list.') }}</li>
                <li class="flex gap-3"><span class="text-indigo-600">✓</span>{{ __('You have a Shopify or WooCommerce store that just needs abandoned-cart WhatsApp recovery and order status notifications.') }}</li>
            </ul>
            <p class="mt-6 text-sm text-zinc-500">{{ __('Everyone else — every MENA storefront doing $5k–$500k/month across 2+ channels — should keep reading.') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="py-16">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Feature-by-feature comparison') }}</h2>
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-200 dark:bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-200">
                            <th class="px-6 py-4 text-left font-semibold text-zinc-700 dark:text-zinc-700">{{ __('Capability') }}</th>
                            <th class="px-6 py-4 text-center font-semibold text-indigo-600">OT1-Pro</th>
                            <th class="px-6 py-4 text-center font-semibold text-zinc-500">WATI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('WhatsApp Business API'), '✅ ' . __('Native (Cloud API)'), '✅ ' . __('Native, mature')],
                            [__('Instagram DMs'), '✅', '❌'],
                            [__('Facebook Messenger'), '✅', '❌'],
                            [__('Telegram'), '✅', '❌'],
                            [__('Email (IMAP/SMTP)'), '✅', '❌'],
                            [__('Native Egyptian Arabic AI'), '✅ ' . __('Dialect-aware'), '⚠️ ' . __('Weak on dialect')],
                            [__('AI sales responder'), '✅ ' . __('Built-in'), '⚠️ ' . __('Basic')],
                            [__('Free plan'), '✅ ' . __('Permanent'), '❌ ' . __('7-day trial only')],
                            [__('Entry paid tier'), __('$8 / month (Basic)'), '~$49 / month'],
                            [__('Pricing model'), __('Per-seat'), __('Per-seat + per-conversation')],
                            [__('Payment in EGP (Paymob)'), '✅', '❌'],
                            [__('Shopify integration'), '✅', '✅ ' . __('Mature')],
                            [__('Salla / Zid (MENA)'), '✅', '⚠️ ' . __('Limited')],
                            [__('MENA-hours support'), '✅ ' . __('Founder on WhatsApp'), '⚠️ ' . __('IST timezone')],
                            [__('Time-to-first-message'), __('Under 30 min'), __('1–3 hours')],
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
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-200 dark:bg-white lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Where OT1-Pro wins for MENA storefronts') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Four areas where the difference is measurable in your monthly reports.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-2">
                @php
                $wins = [
                    [
                        '🌍',
                        __('Multi-channel from day one'),
                        __('WATI is WhatsApp-only. In MENA the split between WhatsApp and Instagram DM is roughly 55/45 for D2C brands. OT1-Pro handles WhatsApp, Instagram, Messenger, Telegram, and email in a single inbox — so your team stops missing the half of leads that arrive on IG.'),
                    ],
                    [
                        '🗣️',
                        __('Arabic AI that understands dialect'),
                        __('OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian, Gulf, and Levantine Arabic natively — including code-switching ("عايز الأبيض medium please") and casual dialect. WATI\'s automation was built with English/Hindi first, so Arabic replies feel translated.'),
                    ],
                    [
                        '💰',
                        __('Per-seat pricing that doesn\'t punish growth'),
                        __('WATI charges per-seat + per-conversation + Meta pass-through. As your store grows and broadcast volume climbs, the bill compounds. OT1-Pro is per-seat only, from $8/month at the Basic tier. Your bill scales with your team, not with your success.'),
                    ],
                    [
                        '📞',
                        __('MENA-hours support from the founder'),
                        __('When your integration breaks at 10pm during a Ramadan push, WATI\'s ticket queue in a different timezone is not acceptable. OT1-Pro\'s founder answers directly on WhatsApp at +20 102 636 1218 — that\'s how we support MENA customers, not a marketing gimmick.'),
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

    {{-- Migration path --}}
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Migrating from WATI to OT1-Pro') }}</h2>
            <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Most stores are fully operational on OT1-Pro within 3 days. Here is the practical checklist:') }}</p>
            <ol class="mt-8 space-y-6">
                @php
                $steps = [
                    [__('1. Export your WATI contact list'), __('Contacts → Export → CSV. Import into OT1-Pro from Settings → Contacts → Import. Usually done in under 5 minutes.')],
                    [__('2. Reconnect the WhatsApp Business Account'), __('If WATI hosts your number on 360dialog, you can migrate the number to Meta Cloud API (Meta support ticket, 2–3 days) or connect fresh through OT1-Pro\'s guided onboarding.')],
                    [__('3. Rebuild your top 5 message templates'), __('Do not try to migrate 40 templates on day one — most teams only actively use 4–6. Rebuild those, watch adoption for a week, then port the rest.')],
                    [__('4. Add Instagram + Messenger + Telegram'), __('The whole reason you switched. Most stores see 30–60% more inbound messages appear in the inbox within 48 hours — because they were previously missing them entirely.')],
                    [__('5. Run WATI and OT1-Pro in parallel for 2 weeks'), __('Nothing drops during the switch. Cancel WATI at the end of your billing cycle.')],
                ];
                @endphp
                @foreach($steps as $step)
                <li class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-200 dark:bg-white">
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-900">{{ $step[0] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ $step[1] }}</p>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Questions about switching from WATI') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Is OT1-Pro cheaper than WATI?'),
                        __('At the entry tier, yes — OT1-Pro starts at $8/month (Basic) and $29/month (Starter with 3 pages and 500 AI responses) vs WATI\'s ~$49/month base. At higher tiers with heavy broadcast volume, WATI can be competitive on raw WhatsApp cost pass-through. OT1-Pro pulls ahead on total-cost-of-ownership when you factor in Instagram, Messenger, and Telegram — each of which would need a separate WATI-equivalent tool.'),
                    ],
                    [
                        __('Can I keep my existing WhatsApp Business number?'),
                        __('Yes. WhatsApp Business API numbers are portable across BSPs (Business Solution Providers). If WATI hosts your number on 360dialog or another BSP, you can migrate it to Meta Cloud API and connect through OT1-Pro. The process typically takes 2–3 business days.'),
                    ],
                    [
                        __('Does OT1-Pro\'s AI understand Egyptian Arabic dialect?'),
                        __('Yes. OT1-Pro routes AI replies through Anthropic Claude (via our NaraRouter gateway), which handles Egyptian and Gulf Arabic dialect natively — including common misspellings, English/Arabic code-switching, and dialect-specific product terms. You can also fine-tune the AI\'s tone per-team in Settings → AI Prompt.'),
                    ],
                    [
                        __('How long does migration from WATI take?'),
                        __('For a typical Egyptian or GCC storefront: 30 minutes for basic setup, an evening for template rebuild, and 2 weeks of parallel running with WATI before you cancel. Most teams are fully operational on OT1-Pro within 3 days.'),
                    ],
                    [
                        __('What about Salla or Zid integration?'),
                        __('OT1-Pro connects with Salla and Zid via webhook — new-order notifications, abandoned-cart recovery, and shipment updates can all fire into WhatsApp/Instagram/Messenger automatically. Setup takes 15 minutes with our step-by-step guide.'),
                    ],
                    [
                        __('What if I run into issues at 11pm during a big campaign?'),
                        __('Message the founder directly on WhatsApp at +20 102 636 1218. That is not a marketing line — that is how we support MENA customers in practice, and it is the reason our churn is low.'),
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
                <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('All 4 channels, Arabic-first AI, per-seat pricing. See it working with your real messages in 30 minutes.') }}</p>
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
            "name": "Is OT1-Pro cheaper than WATI?",
            "acceptedAnswer": {"@@type": "Answer", "text": "At the entry tier, yes — OT1-Pro starts at $8/month (Basic) and $29/month (Starter) vs WATI's ~$49/month base. On total-cost-of-ownership, OT1-Pro wins because it includes Instagram, Messenger, and Telegram in one price."}
        },
        {
            "@@type": "Question",
            "name": "Can I keep my existing WhatsApp Business number when switching from WATI?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. WhatsApp Business API numbers are portable across BSPs. Migration to Meta Cloud API through OT1-Pro typically takes 2–3 business days."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro's AI understand Egyptian Arabic dialect?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian, Gulf, and Levantine Arabic natively — including English/Arabic code-switching and dialect-specific product terms."}
        },
        {
            "@@type": "Question",
            "name": "How long does WATI to OT1-Pro migration take?",
            "acceptedAnswer": {"@@type": "Answer", "text": "For a typical MENA storefront: 30 minutes basic setup, one evening for template rebuild, and 2 weeks of parallel running before cancelling WATI. Most teams are fully operational on OT1-Pro within 3 days."}
        },
        {
            "@@type": "Question",
            "name": "Does OT1-Pro integrate with Salla and Zid?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro connects with Salla and Zid via webhook for order notifications, abandoned-cart recovery, and shipment updates. Setup takes about 15 minutes."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
