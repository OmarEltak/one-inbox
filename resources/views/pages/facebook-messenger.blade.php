<x-layouts.brand-marketing
    :title="__('Facebook Messenger Management for Business | OT1-Pro')"
    :description="__('Manage all your Facebook Page messages from one shared inbox. AI auto-replies, qualifies leads, and escalates hot prospects to your team. Works across multiple Pages. Try free.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-6 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-emer-700">
                        <svg class="size-5 text-emer-700" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        {{ __('Facebook Messenger Management') }}
                    </div>
                    <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {{ __('Facebook Messenger Management for') }} <span class="serif-it text-emer-700">{{ __('Growing Businesses') }}</span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Thousands of businesses use Facebook Messenger as their primary customer channel. OT1-Pro gives you a shared team inbox, AI auto-replies, and lead scoring — so no message ever goes unanswered.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Connect Facebook Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-4 text-sm text-ink/60">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="space-y-4">
                    <div class="rounded-2xl border border-line bg-cream p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-ink/80">{{ __('Facebook Page Inbox') }}</p>
                            <span class="rounded-full bg-emer-100 px-2 py-0.5 text-xs font-medium text-emer-700">{{ __('AI Active') }}</span>
                        </div>
                        @foreach([
                            ['Mohamed A.', __('What\'s your delivery time to Cairo?'), __('2s ago'), '82'],
                            ['Fatima R.', __('I want to place a bulk order for my store'), __('5m ago'), '95'],
                            ['Ahmed S.', __('Do you offer a warranty?'), __('12m ago'), '67'],
                        ] as $msg)
                        <div class="flex items-center gap-3 border-t border-line py-3">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emer-100 text-xs font-bold text-emer-700">{{ substr($msg[0], 0, 1) }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ $msg[0] }}</p>
                                <p class="truncate text-xs text-ink/60">{{ $msg[1] }}</p>
                            </div>
                            <div class="text-right text-xs text-ink/70">
                                <p>{{ $msg[2] }}</p>
                                <p class="font-semibold text-emer-700">{{ $msg[3] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Everything you need to master Facebook Messenger') }}</h2>
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
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Facebook Messenger inbox — FAQs') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
                @php
                $faqs = [
                    [__('Does OT1-Pro work with the official Facebook API?'), __('Yes. OT1-Pro uses Meta\'s official Messenger Platform API. Your Facebook Page and account are fully compliant — no unofficial tools or at-risk integrations.')],
                    [__('Can I manage multiple Facebook Pages in one inbox?'), __('Yes. Connect as many Facebook Pages as your plan allows and manage all their conversations from a single unified inbox with separate AI configurations per Page.')],
                    [__('How do I train the AI for my Facebook Page?'), __('After connecting your Page, you fill in a simple AI configuration form: your business description, product details, pricing, common questions, and brand tone. The AI is ready in minutes.')],
                    [__('What if a customer sends a complaint or negative message?'), __('You can configure the AI to flag sensitive conversations (complaints, refund requests, angry messages) for immediate human review. The AI won\'t try to handle situations it\'s not trained for.')],
                    [__('Does it work with Facebook ads (click-to-Messenger)?'), __('Yes. Any conversation started from a Facebook ad that clicks into Messenger will land in your OT1-Pro — with the same AI handling, lead scoring, and team routing.')],
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
            <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Related Facebook & social CX guides') }}</h2>
            <p class="mt-2 text-ink/70">{{ __('How to run Messenger and unified social inbox at scale.') }}</p>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $related = [
                    ['facebook-messenger-business-guide', __('Facebook Messenger for Business: Complete Guide'), __('5 min read')],
                    ['connect-facebook-page-crm', __('How to Connect a Facebook Page to a CRM'), __('5 min read')],
                    ['instagram-vs-facebook-customer-service', __('Instagram vs Facebook for Customer Service'), __('5 min read')],
                    ['unified-inbox-vs-separate-apps', __('Unified Inbox vs Separate Apps: Cost Analysis'), __('5 min read')],
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
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Take control of your Facebook Messenger inbox') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Connect your Facebook Page in minutes. AI handles every message from day one.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Connect Facebook Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
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
            "name": "Does OT1-Pro work with the official Facebook API?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro uses Meta's official Messenger Platform API. Your Facebook Page and account are fully compliant — no unofficial tools or at-risk integrations."}
        },
        {
            "@@type": "Question",
            "name": "Can I manage multiple Facebook Pages in one inbox?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Connect as many Facebook Pages as your plan allows and manage all their conversations from a single unified inbox with separate AI configurations per Page."}
        },
        {
            "@@type": "Question",
            "name": "Does it work with Facebook ads (click-to-Messenger)?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Any conversation started from a Facebook ad that clicks into Messenger will land in your OT1-Pro — with AI handling, lead scoring, and team routing."}
        }
    ]
}
</script>
@endpush

</x-layouts.brand-marketing>
