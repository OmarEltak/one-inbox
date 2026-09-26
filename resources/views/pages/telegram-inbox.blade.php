<x-layouts.brand-marketing
    :title="__('Telegram Business Inbox — Manage Messages at Scale | OT1-Pro')"
    :description="__('Manage all your Telegram business messages from a shared team inbox. AI auto-replies, scores leads, and routes hot prospects to your team automatically. Try free.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-6 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-emer-700">
                        <svg class="size-5 text-emer-700" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        {{ __('Telegram Business Inbox') }}
                    </div>
                    <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {{ __('Telegram Business Inbox for') }} <span class="serif-it text-emer-700">{{ __('Sales & Support Teams') }}</span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Telegram is the fastest-growing business messaging platform — especially in the Middle East, Eastern Europe, and Southeast Asia. OT1-Pro gives you a professional shared inbox with AI automation so you never miss a business conversation.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Connect Telegram Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-4 text-sm text-ink/60">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-cream p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-3 border-b border-line pb-4">
                        <div class="flex size-9 items-center justify-center rounded-full bg-emer-500 text-cream">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">@YourBusinessBot</p>
                            <p class="text-xs text-ink/60">{{ __('Telegram Business Bot · AI Active') }}</p>
                        </div>
                    </div>
                    @foreach([
                        ['Sergei K.', __('Interested in your wholesale pricing'), '91'],
                        ['Layla M.', __('Can I get a demo of your software?'), '86'],
                        ['Ahmad T.', __('What payment methods do you accept?'), '73'],
                        ['User_7821', __('Hello'), '15'],
                    ] as $msg)
                    <div class="flex items-center justify-between border-t border-line py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $msg[0] }}</p>
                            <p class="truncate text-xs text-ink/60">{{ $msg[1] }}</p>
                        </div>
                        <span class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-xs font-bold {{ (int)$msg[2] >= 80 ? 'bg-emer-100 text-emer-700' : ((int)$msg[2] >= 50 ? 'bg-line text-ink/80' : 'bg-cream2 text-ink/60') }}">{{ $msg[2] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Professional Telegram inbox for serious businesses') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Telegram gives you direct access to highly engaged customers. OT1-Pro makes sure you convert them.') }}</p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['🤖', __('AI Bot Replies'), __('Connect your Telegram bot to OT1-Pro. AI takes over — answering questions, sharing product info, and guiding customers toward a purchase automatically.')],
                    ['🗂️', __('Unified Team Inbox'), __('All Telegram conversations land in one shared inbox. Assign threads to team members, add internal notes, and track every interaction.')],
                    ['📊', __('Lead Scoring'), __('AI scores every Telegram conversation by purchase intent. Focus your team\'s time on the leads most likely to convert — not everyone who says "hello."')],
                    ['🔀', __('Smart Routing'), __('High-score leads get automatically assigned to your best closers. Support questions go to your support team. Everything routes to the right person.')],
                    ['🌐', __('100+ Languages'), __('Telegram is global. Your AI responds in Russian, Arabic, Persian, Turkish, English, and 100+ more languages — automatically matching the customer\'s language.')],
                    ['📁', __('Contact Management'), __('Every Telegram user who messages your bot becomes a contact in OT1-Pro — with their full conversation history, lead score, and notes attached.')],
                ];
                @endphp
                @foreach($features as $feature)
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <div class="mb-3 text-3xl">{{ $feature[0] }}</div>
                    <h3 class="serif text-2xl leading-snug text-ink">{{ $feature[1] }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $feature[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-24 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Telegram inbox questions answered') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
                @php
                $faqs = [
                    [__('How does OT1-Pro connect to Telegram?'), __('You connect via a Telegram Bot. You create a free bot through Telegram\'s BotFather, paste the bot token into OT1-Pro, and your bot\'s conversations flow into your shared inbox instantly.')],
                    [__('Does it work with Telegram channels and groups?'), __('OT1-Pro currently handles direct messages to your Telegram bot. Channel and group management is on the roadmap for a future update.')],
                    [__('Can the AI handle complex product questions?'), __('Yes. You train the AI by providing your product catalog, FAQs, pricing, and policies. The more detail you give it, the better it handles complex questions without human intervention.')],
                    [__('Is Telegram compliant with business messaging rules?'), __('Telegram has no restrictions on business bots — unlike WhatsApp or Instagram. You can send messages freely as long as users initiated the conversation with your bot.')],
                    [__('Can I use OT1-Pro for Telegram and other platforms simultaneously?'), __('Yes. OT1-Pro unifies Telegram with Facebook, Instagram, and WhatsApp in one dashboard. Your team manages all channels from a single interface.')],
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

    {{-- Related Reading --}}
    <section class="border-t border-line bg-cream2 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Related Telegram & messaging guides') }}</h2>
            <p class="mt-2 text-ink/70">{{ __('How to scale messaging operations across channels.') }}</p>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $related = [
                    ['manage-telegram-business-scale', __('How to Manage Telegram Business at Scale'), __('5 min read')],
                    ['unified-inbox-vs-separate-apps', __('Unified Inbox vs Separate Apps: Cost Analysis'), __('5 min read')],
                    ['social-inbox-setup-1-hour', __('Social Inbox Setup: Zero to Automated in 1 Hour'), __('6 min read')],
                    ['social-response-time-benchmarks', __('Response Time Benchmarks by Industry'), __('5 min read')],
                ];
                @endphp
                @foreach($related as $r)
                <a href="{{ url('/blog/' . $r[0]) }}" class="group block rounded-2xl border border-line bg-cream p-5 transition hover:border-emer-400 hover:shadow-md">
                    <div class="mb-2 text-xs font-medium uppercase tracking-wider text-emer-700">{{ $r[2] }}</div>
                    <h3 class="serif text-xl leading-snug text-ink group-hover:text-emer-700">{{ $r[1] }}</h3>
                    <span class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-emer-700">{{ __('Read more') }} →</span>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════ FINAL CTA ═══════ --}}
    <section class="bg-ink text-cream py-24 grain relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Start managing Telegram like a business pro') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Connect your Telegram bot and give your team a professional inbox with AI automation from day one.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Connect Telegram Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
            <p class="mt-4 text-sm text-cream/60">{{ __('No credit card required') }}</p>
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
            "name": "How does OT1-Pro connect to Telegram?",
            "acceptedAnswer": {"@@type": "Answer", "text": "You connect via a Telegram Bot. Create a free bot through Telegram's BotFather, paste the bot token into OT1-Pro, and your bot's conversations flow into your shared inbox instantly."}
        },
        {
            "@@type": "Question",
            "name": "Can the AI handle complex product questions on Telegram?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. You train the AI by providing your product catalog, FAQs, pricing, and policies. The more detail you give it, the better it handles complex questions without human intervention."}
        },
        {
            "@@type": "Question",
            "name": "Can I use OT1-Pro for Telegram and other platforms simultaneously?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro unifies Telegram with Facebook, Instagram, and WhatsApp in one dashboard. Your team manages all channels from a single interface."}
        }
    ]
}
</script>
@endpush

</x-layouts.brand-marketing>
