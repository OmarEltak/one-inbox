<x-layouts.brand-marketing
    :title="__('Instagram DM Management Software with AI | OT1-Pro')"
    :description="__('Manage all your Instagram DMs from one shared inbox. AI auto-replies to messages, qualifies leads, scores prospects, and hands off hot buyers to your team. Try free.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-6 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-emer-700">
                        <svg class="size-5 text-emer-700" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        {{ __('Instagram DM Management') }}
                    </div>
                    <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {{ __('Instagram DM Management That Turns') }} <span class="serif-it text-emer-700">{{ __('Followers Into Customers') }}</span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Your Instagram DMs are full of potential buyers asking questions, checking prices, and ready to buy. OT1-Pro makes sure every single one gets a reply — instantly, intelligently, and automatically.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Connect Instagram Free') }}
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
                        <div class="size-8 rounded-full bg-emer-600"></div>
                        <div>
                            <p class="text-sm font-semibold">{{ __('Instagram DMs') }}</p>
                            <p class="text-xs text-ink/60">{{ __('24 new · AI handling 18') }}</p>
                        </div>
                    </div>
                    @foreach([
                        ['@amira_style', 'How much is the gold necklace in your last post?', '🟢', '94'],
                        ['@khalid.buys', 'Do you ship to Saudi Arabia?', '🟡', '61'],
                        ['@fashion_lover99', 'What sizes do you have available?', '🟢', '78'],
                        ['@new_follower_22', 'Love your page! 😍', '⚪', '12'],
                    ] as $dm)
                    <div class="flex items-center justify-between border-b border-line py-3 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $dm[0] }}</p>
                            <p class="truncate text-xs text-ink/60">{{ $dm[1] }}</p>
                        </div>
                        <div class="ml-3 text-right">
                            <span class="text-xs">{{ $dm[2] }} Score: <strong>{{ $dm[3] }}</strong></span>
                        </div>
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
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Stop leaving Instagram sales on the table') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Every unanswered DM is a potential customer lost. OT1-Pro makes sure that never happens.') }}</p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['💬', __('Auto-Reply to DMs'), __('AI responds to every Instagram DM within seconds — answering product questions, sharing prices, and guiding leads toward a purchase, 24 hours a day.')],
                    ['🎯', __('Lead Scoring'), __('Not all DMs are equal. AI scores every conversation based on purchase intent — so your team focuses on the 20% of leads that generate 80% of revenue.')],
                    ['👥', __('Team Inbox'), __('Multiple team members share one Instagram inbox. Assign high-value conversations, leave internal notes, and collaborate without confusion.')],
                    ['🔄', __('Smart Handoff'), __('When a lead is ready to buy, AI passes the conversation to the right sales rep — with full context, score, and conversation history.')],
                    ['📈', __('DM Analytics'), __('See which products get the most DM inquiries, what questions come up most, and how your team\'s response time affects conversion.')],
                    ['🌍', __('Multi-Language'), __('AI responds in the same language as your customer — Arabic, English, French, Spanish, and 100+ more. No language barrier, no lost sales.')],
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

    {{-- How It Works --}}
    <section class="py-24 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Set up Instagram DM automation in 3 steps') }}</h2>
            </div>
            <div class="mt-16 space-y-8">
                @php
                $steps = [
                    ['01', __('Connect your Instagram account'), __('Link your Instagram Business account to OT1-Pro with one click via Facebook Login. Takes under 2 minutes.')],
                    ['02', __('Train your AI sales agent'), __('Tell the AI about your products, prices, shipping policy, and brand personality. Upload a product catalog or paste your FAQ — done.')],
                    ['03', __('Let AI handle the DMs'), __('AI starts responding immediately. You review the dashboard, check lead scores, and step in only when a deal needs the human touch.')],
                ];
                @endphp
                @foreach($steps as $step)
                <div class="flex gap-6">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl text-lg font-bold text-cream bg-emer-600">{{ $step[0] }}</div>
                    <div class="pt-1">
                        <h3 class="serif text-2xl leading-snug text-ink">{{ $step[1] }}</h3>
                        <p class="mt-1 text-ink/70">{{ $step[2] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Instagram DM questions answered') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
                @php
                $faqs = [
                    [__('Does Instagram allow automated DM replies?'), __('Yes. OT1-Pro uses Meta\'s official Instagram Messaging API, which is fully compliant with Instagram\'s terms of service. Your account is safe — no grey-area tools or unofficial access.')],
                    [__('Can the AI reply to Instagram comments too?'), __('OT1-Pro currently handles Instagram DMs. Comment-to-DM flows (where you reply to a comment and trigger a DM) are on the roadmap.')],
                    [__('Will my followers know they\'re talking to an AI?'), __('That\'s your choice. You can configure the AI to identify itself or to respond as your brand. Many businesses configure a brand persona with a name like "Sara from [Brand]."')],
                    [__('How does it handle multiple languages?'), __('The AI automatically detects the language of the incoming message and replies in the same language. No configuration needed — it works out of the box.')],
                    [__('Can I see all my DMs across multiple Instagram accounts?'), __('Yes. Connect multiple Instagram Business accounts and manage all their DMs from one unified dashboard with separate AI configs per account.')],
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
            <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Related Instagram guides') }}</h2>
            <p class="mt-2 text-ink/70">{{ __('Deep dives on Instagram DM automation and lead generation.') }}</p>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $related = [
                    ['ai-sales-chatbot-instagram', __('AI Sales Chatbot for Instagram: 2026 Setup Guide'), __('7 min read')],
                    ['auto-reply-instagram-comments-ai', __('How to Auto-Reply to Instagram Comments with AI'), __('6 min read')],
                    ['instagram-lead-generation-dm-automation', __('Instagram Lead Generation with DM Automation'), __('6 min read')],
                    ['instagram-dm-scripts-convert', __('Instagram DM Scripts That Convert Followers'), __('6 min read')],
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
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Turn your Instagram DMs into a sales machine') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Connect your Instagram account and let AI handle the conversations while you focus on growing your business.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Connect Instagram Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
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
            "name": "Does Instagram allow automated DM replies?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro uses Meta's official Instagram Messaging API, which is fully compliant with Instagram's terms of service. Your account is safe."}
        },
        {
            "@@type": "Question",
            "name": "Will my followers know they're talking to an AI?",
            "acceptedAnswer": {"@@type": "Answer", "text": "That's your choice. You can configure the AI to identify itself or to respond as your brand with a custom persona name."}
        },
        {
            "@@type": "Question",
            "name": "How does it handle multiple languages?",
            "acceptedAnswer": {"@@type": "Answer", "text": "The AI automatically detects the language of the incoming message and replies in the same language. No configuration needed — it works out of the box."}
        },
        {
            "@@type": "Question",
            "name": "Can I manage multiple Instagram accounts?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Connect multiple Instagram Business accounts and manage all their DMs from one unified dashboard with separate AI configs per account."}
        }
    ]
}
</script>
@endpush

</x-layouts.brand-marketing>
