<x-layouts.marketing
    :title="__('Facebook Messenger Management for Business — Unified Page Inbox | One Inbox')"
    :description="__('Manage all your Facebook Page messages from one shared inbox. AI auto-replies, qualifies leads, and escalates hot prospects to your team. Works across multiple Pages. Try free.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-0 size-[500px] rounded-full bg-blue-500/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/3 size-[400px] rounded-full bg-indigo-500/8 blur-3xl"></div>
        </div>
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-1.5 text-sm font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                        <svg class="size-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        {{ __('Facebook Messenger Management') }}
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ __('Facebook Messenger Management for') }} <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">{{ __('Growing Businesses') }}</span>
                    </h1>
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Thousands of businesses use Facebook Messenger as their primary customer channel. One Inbox gives you a shared team inbox, AI auto-replies, and lead scoring — so no message ever goes unanswered.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:bg-blue-700">
                            {{ __('Connect Facebook Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-blue-300 hover:text-blue-700 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="space-y-4">
                    <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Facebook Page Inbox') }}</p>
                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ __('AI Active') }}</span>
                        </div>
                        @foreach([
                            ['Mohamed A.', 'What\'s your delivery time to Cairo?', '2s ago', '82'],
                            ['Fatima R.', 'I want to place a bulk order for my store', '5m ago', '95'],
                            ['Ahmed S.', 'Do you offer a warranty?', '12m ago', '67'],
                        ] as $msg)
                        <div class="flex items-center gap-3 border-t border-zinc-100 py-3 dark:border-zinc-800">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ substr($msg[0], 0, 1) }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ $msg[0] }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ $msg[1] }}</p>
                            </div>
                            <div class="text-right text-xs text-zinc-400">
                                <p>{{ $msg[2] }}</p>
                                <p class="font-semibold text-blue-600">{{ $msg[3] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Everything you need to master Facebook Messenger') }}</h2>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['⚡', __('Instant AI Replies'), __('AI responds to every Facebook message in under 5 seconds. No more waiting hours for a human to reply — customers get answers immediately, any time of day.')],
                    ['📋', __('Multi-Page Management'), __('Manage Messenger for multiple Facebook Pages from one dashboard. Perfect for businesses with regional pages or agencies managing client accounts.')],
                    ['🎯', __('Purchase Intent Scoring'), __('AI reads the conversation and assigns a lead score. "I want to buy" gets a 90+. "Just browsing" gets a 20. Your team knows exactly who to prioritize.')],
                    ['🤝', __('Seamless Handoff'), __('Hot leads get flagged and routed to the right sales rep automatically. The rep sees the full conversation history and score before responding.')],
                    ['💡', __('Smart Suggestions'), __('When your team takes over a conversation, AI suggests the best response based on the context — speeding up reply time and keeping tone consistent.')],
                    ['📊', __('Performance Dashboard'), __('Track message volume, response times, AI accuracy, conversion rates, and team performance — all in one real-time dashboard.')],
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
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Facebook Messenger inbox — FAQs') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [__('Does One Inbox work with the official Facebook API?'), __('Yes. One Inbox uses Meta\'s official Messenger Platform API. Your Facebook Page and account are fully compliant — no unofficial tools or at-risk integrations.')],
                    [__('Can I manage multiple Facebook Pages in one inbox?'), __('Yes. Connect as many Facebook Pages as your plan allows and manage all their conversations from a single unified inbox with separate AI configurations per Page.')],
                    [__('How do I train the AI for my Facebook Page?'), __('After connecting your Page, you fill in a simple AI configuration form: your business description, product details, pricing, common questions, and brand tone. The AI is ready in minutes.')],
                    [__('What if a customer sends a complaint or negative message?'), __('You can configure the AI to flag sensitive conversations (complaints, refund requests, angry messages) for immediate human review. The AI won\'t try to handle situations it\'s not trained for.')],
                    [__('Does it work with Facebook ads (click-to-Messenger)?'), __('Yes. Any conversation started from a Facebook ad that clicks into Messenger will land in your One Inbox — with the same AI handling, lead scoring, and team routing.')],
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
            <div class="rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-600 p-10 text-center text-white sm:p-16">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Take control of your Facebook Messenger inbox') }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-blue-100">{{ __('Connect your Facebook Page in minutes. AI handles every message from day one.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-blue-700 shadow-lg transition-all hover:bg-blue-50">
                    {{ __('Connect Facebook Free') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-blue-200">{{ __('No credit card required') }}</p>
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
            "name": "Does One Inbox work with the official Facebook API?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. One Inbox uses Meta's official Messenger Platform API. Your Facebook Page and account are fully compliant — no unofficial tools or at-risk integrations."}
        },
        {
            "@type": "Question",
            "name": "Can I manage multiple Facebook Pages in one inbox?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. Connect as many Facebook Pages as your plan allows and manage all their conversations from a single unified inbox with separate AI configurations per Page."}
        },
        {
            "@type": "Question",
            "name": "Does it work with Facebook ads (click-to-Messenger)?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. Any conversation started from a Facebook ad that clicks into Messenger will land in your One Inbox — with AI handling, lead scoring, and team routing."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
