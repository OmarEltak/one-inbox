<x-layouts.marketing
    :title="__('Telegram Business Inbox — Manage Telegram Messages at Scale | One Inbox')"
    :description="__('Manage all your Telegram business messages from a shared team inbox. AI auto-replies, scores leads, and routes hot prospects to your team automatically. Try free.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-0 size-[500px] rounded-full bg-cyan-500/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/3 size-[400px] rounded-full bg-sky-500/8 blur-3xl"></div>
        </div>
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-4 py-1.5 text-sm font-medium text-cyan-700 dark:border-cyan-800 dark:bg-cyan-950/50 dark:text-cyan-300">
                        <svg class="size-5 text-cyan-600" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        {{ __('Telegram Business Inbox') }}
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ __('Telegram Business Inbox for') }} <span class="bg-gradient-to-r from-cyan-600 to-sky-500 bg-clip-text text-transparent">{{ __('Sales & Support Teams') }}</span>
                    </h1>
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Telegram is the fastest-growing business messaging platform — especially in the Middle East, Eastern Europe, and Southeast Asia. One Inbox gives you a professional shared inbox with AI automation so you never miss a business conversation.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-cyan-500/25 transition-all hover:bg-cyan-700">
                            {{ __('Connect Telegram Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-cyan-300 hover:text-cyan-700 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-3 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <div class="flex size-9 items-center justify-center rounded-full bg-cyan-500 text-white">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold">@YourBusinessBot</p>
                            <p class="text-xs text-zinc-500">{{ __('Telegram Business Bot · AI Active') }}</p>
                        </div>
                    </div>
                    @foreach([
                        ['Sergei K.', 'Interested in your wholesale pricing', '91'],
                        ['Layla M.', 'Can I get a demo of your software?', '86'],
                        ['Ahmad T.', 'What payment methods do you accept?', '73'],
                        ['User_7821', 'Hello', '15'],
                    ] as $msg)
                    <div class="flex items-center justify-between border-t border-zinc-100 py-3 dark:border-zinc-800">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $msg[0] }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ $msg[1] }}</p>
                        </div>
                        <span class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-xs font-bold {{ (int)$msg[2] >= 80 ? 'bg-green-100 text-green-700' : ((int)$msg[2] >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-zinc-100 text-zinc-500') }}">{{ $msg[2] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Professional Telegram inbox for serious businesses') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Telegram gives you direct access to highly engaged customers. One Inbox makes sure you convert them.') }}</p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['🤖', __('AI Bot Replies'), __('Connect your Telegram bot to One Inbox. AI takes over — answering questions, sharing product info, and guiding customers toward a purchase automatically.')],
                    ['🗂️', __('Unified Team Inbox'), __('All Telegram conversations land in one shared inbox. Assign threads to team members, add internal notes, and track every interaction.')],
                    ['📊', __('Lead Scoring'), __('AI scores every Telegram conversation by purchase intent. Focus your team\'s time on the leads most likely to convert — not everyone who says "hello."')],
                    ['🔀', __('Smart Routing'), __('High-score leads get automatically assigned to your best closers. Support questions go to your support team. Everything routes to the right person.')],
                    ['🌐', __('100+ Languages'), __('Telegram is global. Your AI responds in Russian, Arabic, Persian, Turkish, English, and 100+ more languages — automatically matching the customer\'s language.')],
                    ['📁', __('Contact Management'), __('Every Telegram user who messages your bot becomes a contact in One Inbox — with their full conversation history, lead score, and notes attached.')],
                ];
                @endphp
                @foreach($features as $feature)
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 text-3xl">{{ $feature[0] }}</div>
                    <h3 class="text-lg font-semibold">{{ $feature[1] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $feature[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Telegram inbox questions answered') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [__('How does One Inbox connect to Telegram?'), __('You connect via a Telegram Bot. You create a free bot through Telegram\'s BotFather, paste the bot token into One Inbox, and your bot\'s conversations flow into your shared inbox instantly.')],
                    [__('Does it work with Telegram channels and groups?'), __('One Inbox currently handles direct messages to your Telegram bot. Channel and group management is on the roadmap for a future update.')],
                    [__('Can the AI handle complex product questions?'), __('Yes. You train the AI by providing your product catalog, FAQs, pricing, and policies. The more detail you give it, the better it handles complex questions without human intervention.')],
                    [__('Is Telegram compliant with business messaging rules?'), __('Telegram has no restrictions on business bots — unlike WhatsApp or Instagram. You can send messages freely as long as users initiated the conversation with your bot.')],
                    [__('Can I use One Inbox for Telegram and other platforms simultaneously?'), __('Yes. One Inbox unifies Telegram with Facebook, Instagram, and WhatsApp in one dashboard. Your team manages all channels from a single interface.')],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800" x-data>
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
    <section class="pb-20 lg:pb-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="rounded-3xl bg-gradient-to-br from-cyan-600 to-sky-500 p-10 text-center text-white sm:p-16">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Start managing Telegram like a business pro') }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-cyan-100">{{ __('Connect your Telegram bot and give your team a professional inbox with AI automation from day one.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-cyan-700 shadow-lg transition-all hover:bg-cyan-50">
                    {{ __('Connect Telegram Free') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-cyan-200">{{ __('No credit card required') }}</p>
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
            "name": "How does One Inbox connect to Telegram?",
            "acceptedAnswer": {"@type": "Answer", "text": "You connect via a Telegram Bot. Create a free bot through Telegram's BotFather, paste the bot token into One Inbox, and your bot's conversations flow into your shared inbox instantly."}
        },
        {
            "@type": "Question",
            "name": "Can the AI handle complex product questions on Telegram?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. You train the AI by providing your product catalog, FAQs, pricing, and policies. The more detail you give it, the better it handles complex questions without human intervention."}
        },
        {
            "@type": "Question",
            "name": "Can I use One Inbox for Telegram and other platforms simultaneously?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. One Inbox unifies Telegram with Facebook, Instagram, and WhatsApp in one dashboard. Your team manages all channels from a single interface."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
