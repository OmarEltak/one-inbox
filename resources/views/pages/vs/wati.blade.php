<x-layouts.brand-marketing
    :title="__('OT1-Pro vs WATI — Multi-Channel WhatsApp Alternative for MENA | OT1-Pro')"
    :description="__('Looking for a WATI alternative? OT1-Pro adds Instagram, Messenger, and Telegram to your WhatsApp inbox with native Egyptian Arabic AI and per-seat pricing starting at $8/mo.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="mb-6 text-xs uppercase tracking-[0.2em] text-emer-700">
                {{ __('Comparison') }}
            </div>
            <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-7xl">
                {{ __('OT1-Pro vs WATI') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink/70">
                {{ __('WATI does WhatsApp well. But if you sell on Instagram, Messenger, and Telegram too — or you need Arabic-first AI and per-seat pricing that doesn\'t punish growth — OT1-Pro is the WATI alternative built for MENA storefronts.') }}
            </p>
            <div class="mt-8 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>
            <p class="mt-4 text-sm text-ink/60">{{ __('No credit card required · Free plan available · Talk to founder on WhatsApp') }}</p>
        </div>
    </section>

    {{-- Who WATI is right for (honest positioning) --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('When WATI is actually the right choice') }}</h2>
            <p class="mt-4 leading-relaxed text-ink/70">{{ __('Honesty first. If any of these describe you, stay on WATI — it works well for the WhatsApp-only use case:') }}</p>
            <ul class="mt-6 space-y-3 text-ink/80">
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('You sell almost entirely on WhatsApp (90%+ of orders come from WA).') }}</li>
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('Your team is comfortable with WhatsApp Business API concepts — templates, session windows, opt-in tracking.') }}</li>
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('You send heavy WhatsApp broadcast campaigns to a large opted-in list.') }}</li>
                <li class="flex gap-3"><span class="text-emer-600">✓</span>{{ __('You have a Shopify or WooCommerce store that just needs abandoned-cart WhatsApp recovery and order status notifications.') }}</li>
            </ul>
            <p class="mt-6 text-sm text-ink/60">{{ __('Everyone else — every MENA storefront doing $5k–$500k/month across 2+ channels — should keep reading.') }}</p>
        </div>
    </section>

    {{-- Comparison Table --}}
    <section class="py-20">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('Feature-by-feature comparison') }}</h2>
            <div class="overflow-x-auto rounded-2xl border border-line bg-cream">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line bg-ink text-cream">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Capability') }}</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-emer-400">OT1-Pro</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-cream/70">WATI</th>
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

    {{-- Where OT1-Pro Wins --}}
    <section class="border-y border-line bg-cream2 py-24 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Where OT1-Pro wins for MENA storefronts') }}</h2>
                <p class="mt-4 leading-relaxed text-ink/70">{{ __('Four areas where the difference is measurable in your monthly reports.') }}</p>
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
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <div class="mb-3 text-3xl">{{ $win[0] }}</div>
                    <h3 class="serif text-2xl leading-snug text-ink">{{ $win[1] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $win[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Migration path --}}
    <section class="py-24">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Migrating from WATI to OT1-Pro') }}</h2>
            <p class="mt-4 leading-relaxed text-ink/70">{{ __('Most stores are fully operational on OT1-Pro within 3 days. Here is the practical checklist:') }}</p>
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
                <li class="fade-up rounded-2xl border border-line bg-cream2 p-6">
                    <h3 class="serif text-xl text-ink">{{ $step[0] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $step[1] }}</p>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Questions about switching from WATI') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
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
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('All 4 channels, Arabic-first AI, per-seat pricing. See it working with your real messages in 30 minutes.') }}</p>
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

</x-layouts.brand-marketing>
