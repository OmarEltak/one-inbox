<x-layouts.marketing
    :title="__('Instagram DM Management Software — Auto-Reply & Organize DMs | One Inbox')"
    :description="__('Manage all your Instagram DMs from one shared inbox. AI auto-replies to messages, qualifies leads, scores prospects, and hands off hot buyers to your team. Try free.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-0 size-[500px] rounded-full bg-pink-500/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/3 size-[400px] rounded-full bg-purple-500/8 blur-3xl"></div>
        </div>
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-pink-200 bg-pink-50 px-4 py-1.5 text-sm font-medium text-pink-700 dark:border-pink-800 dark:bg-pink-950/50 dark:text-pink-300">
                        <svg class="size-5 text-pink-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        {{ __('Instagram DM Management') }}
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ __('Instagram DM Management That Turns') }} <span class="bg-gradient-to-r from-pink-600 to-purple-600 bg-clip-text text-transparent">{{ __('Followers Into Customers') }}</span>
                    </h1>
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Your Instagram DMs are full of potential buyers asking questions, checking prices, and ready to buy. One Inbox makes sure every single one gets a reply — instantly, intelligently, and automatically.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-600 to-purple-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-pink-500/25 transition-all hover:opacity-90">
                            {{ __('Connect Instagram Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-pink-300 hover:text-pink-700 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-3 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <div class="size-8 rounded-full bg-gradient-to-br from-pink-500 to-purple-600"></div>
                        <div>
                            <p class="text-sm font-semibold">{{ __('Instagram DMs') }}</p>
                            <p class="text-xs text-zinc-500">24 new · AI handling 18</p>
                        </div>
                    </div>
                    @foreach([
                        ['@amira_style', 'How much is the gold necklace in your last post?', '🟢', '94'],
                        ['@khalid.buys', 'Do you ship to Saudi Arabia?', '🟡', '61'],
                        ['@fashion_lover99', 'What sizes do you have available?', '🟢', '78'],
                        ['@new_follower_22', 'Love your page! 😍', '⚪', '12'],
                    ] as $dm)
                    <div class="flex items-center justify-between border-b border-zinc-50 py-3 last:border-0 dark:border-zinc-800">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $dm[0] }}</p>
                            <p class="truncate text-xs text-zinc-500">{{ $dm[1] }}</p>
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
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Stop leaving Instagram sales on the table') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Every unanswered DM is a potential customer lost. One Inbox makes sure that never happens.') }}</p>
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
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 text-3xl">{{ $feature[0] }}</div>
                    <h3 class="text-lg font-semibold">{{ $feature[1] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $feature[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Set up Instagram DM automation in 3 steps') }}</h2>
            </div>
            <div class="mt-16 space-y-8">
                @php
                $steps = [
                    ['01', __('Connect your Instagram account'), __('Link your Instagram Business account to One Inbox with one click via Facebook Login. Takes under 2 minutes.')],
                    ['02', __('Train your AI sales agent'), __('Tell the AI about your products, prices, shipping policy, and brand personality. Upload a product catalog or paste your FAQ — done.')],
                    ['03', __('Let AI handle the DMs'), __('AI starts responding immediately. You review the dashboard, check lead scores, and step in only when a deal needs the human touch.')],
                ];
                @endphp
                @foreach($steps as $step)
                <div class="flex gap-6">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-600 to-purple-600 text-lg font-bold text-white">{{ $step[0] }}</div>
                    <div class="pt-1">
                        <h3 class="text-lg font-semibold">{{ $step[1] }}</h3>
                        <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $step[2] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Instagram DM questions answered') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [__('Does Instagram allow automated DM replies?'), __('Yes. One Inbox uses Meta\'s official Instagram Messaging API, which is fully compliant with Instagram\'s terms of service. Your account is safe — no grey-area tools or unofficial access.')],
                    [__('Can the AI reply to Instagram comments too?'), __('One Inbox currently handles Instagram DMs. Comment-to-DM flows (where you reply to a comment and trigger a DM) are on the roadmap.')],
                    [__('Will my followers know they\'re talking to an AI?'), __('That\'s your choice. You can configure the AI to identify itself or to respond as your brand. Many businesses configure a brand persona with a name like "Sara from [Brand]."')],
                    [__('How does it handle multiple languages?'), __('The AI automatically detects the language of the incoming message and replies in the same language. No configuration needed — it works out of the box.')],
                    [__('Can I see all my DMs across multiple Instagram accounts?'), __('Yes. Connect multiple Instagram Business accounts and manage all their DMs from one unified dashboard with separate AI configs per account.')],
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
            <div class="rounded-3xl bg-gradient-to-br from-pink-600 to-purple-600 p-10 text-center text-white sm:p-16">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Turn your Instagram DMs into a sales machine') }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-pink-100">{{ __('Connect your Instagram account and let AI handle the conversations while you focus on growing your business.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-pink-700 shadow-lg transition-all hover:bg-pink-50">
                    {{ __('Connect Instagram Free') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-pink-200">{{ __('No credit card required') }}</p>
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
            "name": "Does Instagram allow automated DM replies?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. One Inbox uses Meta's official Instagram Messaging API, which is fully compliant with Instagram's terms of service. Your account is safe."}
        },
        {
            "@type": "Question",
            "name": "Will my followers know they're talking to an AI?",
            "acceptedAnswer": {"@type": "Answer", "text": "That's your choice. You can configure the AI to identify itself or to respond as your brand with a custom persona name."}
        },
        {
            "@type": "Question",
            "name": "How does it handle multiple languages?",
            "acceptedAnswer": {"@type": "Answer", "text": "The AI automatically detects the language of the incoming message and replies in the same language. No configuration needed — it works out of the box."}
        },
        {
            "@type": "Question",
            "name": "Can I manage multiple Instagram accounts?",
            "acceptedAnswer": {"@type": "Answer", "text": "Yes. Connect multiple Instagram Business accounts and manage all their DMs from one unified dashboard with separate AI configs per account."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
