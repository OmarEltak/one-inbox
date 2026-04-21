<x-layouts.marketing
    :title="__('WhatsApp Business Inbox — Manage All WhatsApp Messages in One Place | One Inbox')"
    :description="__('Manage every WhatsApp Business conversation from one unified inbox. AI auto-replies 24/7, scores leads, and hands off hot prospects to your team instantly. Try free.')"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-16 pb-20 lg:pt-24 lg:pb-32">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/4 top-0 size-[500px] rounded-full bg-green-500/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/3 size-[400px] rounded-full bg-emerald-500/8 blur-3xl"></div>
        </div>
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-1.5 text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-950/50 dark:text-green-300">
                        <div class="flex size-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="size-5 text-green-600" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </div>
                        {{ __('WhatsApp Business Inbox') }}
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ __('The WhatsApp Business Inbox Built for') }} <span class="bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent">{{ __('Sales Teams') }}</span>
                    </h1>
                    <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Stop managing WhatsApp in your phone. One Inbox gives your entire team a shared WhatsApp Business inbox with AI that replies instantly, qualifies every lead, and never misses a sale — 24/7.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-8 py-3.5 font-semibold text-white shadow-lg shadow-green-500/25 transition-all hover:bg-green-700">
                            {{ __('Connect WhatsApp Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-8 py-3.5 font-semibold text-zinc-700 transition-all hover:border-green-300 hover:text-green-700 dark:border-zinc-700 dark:text-zinc-300">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="space-y-4">
                        @foreach([
                            ['bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', 'AI', 'Hi! Thanks for reaching out. What product are you interested in today?'],
                            ['bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200', 'Lead', 'I want to know the price of your premium package'],
                            ['bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', 'AI', 'Great choice! The premium package is $299/mo and includes unlimited users. Are you looking for monthly or annual billing? (Annual saves 20%)'],
                            ['bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200', 'Lead', 'Annual sounds good. How do I sign up?'],
                        ] as $msg)
                        <div class="flex {{ $msg[1] === 'Lead' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-xs rounded-2xl {{ $msg[1] === 'Lead' ? 'rounded-tr-sm' : 'rounded-tl-sm' }} {{ $msg[0] }} px-4 py-2.5 text-sm">
                                <p class="mb-1 text-xs font-semibold opacity-60">{{ $msg[1] }}</p>
                                {{ $msg[2] }}
                            </div>
                        </div>
                        @endforeach
                        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-center text-xs font-medium text-green-700 dark:border-green-800 dark:bg-green-950/50 dark:text-green-300">
                            Lead Score: 87/100 — Hot prospect · Handed off to sales team
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Problem / Why section --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('WhatsApp is your best sales channel. But managing it is a mess.') }}</h2>
                <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">{{ __('Every business faces the same WhatsApp problems. One Inbox solves all of them.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $problems = [
                    [__('Messages going to one person\'s phone'), __('Shared team inbox — everyone sees every message')],
                    [__('Leads falling through the cracks at night'), __('AI replies instantly, 24/7, in any language')],
                    [__('No idea which leads are serious buyers'), __('AI scores every lead 0–100 based on intent')],
                    [__('Switching between WhatsApp and your CRM'), __('All conversations, contacts, and notes in one place')],
                    [__('Slow manual responses losing sales to competitors'), __('Sub-5 second AI response time')],
                    [__('Can\'t tell which team member handled what'), __('Full conversation history with assignment logs')],
                ];
                @endphp
                @foreach($problems as $item)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-sm text-red-500">❌ {{ $item[0] }}</p>
                    <p class="mt-2 text-sm font-medium text-green-600">✅ {{ $item[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Everything your WhatsApp sales team needs') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ __('Built specifically for businesses that sell and support through WhatsApp.') }}</p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['🤖', __('AI Sales Responder'), __('Trained on your products, pricing, and brand voice. Responds to every WhatsApp message instantly — qualifying leads and pushing toward a close without any human intervention.')],
                    ['👥', __('Shared Team Inbox'), __('Your entire sales team works from one WhatsApp inbox. Assign conversations, add internal notes, and see who\'s handling what — all in real time.')],
                    ['🎯', __('Lead Scoring'), __('Every WhatsApp conversation gets an AI lead score from 0–100. Know instantly who\'s ready to buy and who needs nurturing.')],
                    ['🔄', __('AI-Human Handoff'), __('When a lead is hot or needs a personal touch, AI seamlessly hands the conversation to the right team member — with full context.')],
                    ['📊', __('Conversation Analytics'), __('Track response times, conversion rates, AI performance, and team productivity across all your WhatsApp conversations.')],
                    ['💬', __('Quick Replies & Templates'), __('Save your best responses as templates. Your AI uses them — your team can use them too. Consistent messaging at scale.')],
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

    {{-- Use Cases --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Who uses One Inbox for WhatsApp?') }}</h2>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $useCases = [
                    [__('E-commerce Stores'), __('Handle order inquiries, returns, and upsells on WhatsApp. AI answers product questions and checkout issues around the clock.')],
                    [__('Real Estate Agencies'), __('Qualify property buyers and renters on WhatsApp before your agents spend time on calls. AI collects budget, location, and timeline.')],
                    [__('Education & Coaching'), __('Answer enrollment questions, share course details, and follow up with prospective students — all automated on WhatsApp.')],
                    [__('Service Businesses'), __('Capture appointment requests, send quotes, and follow up on leads — without your team being chained to their phones.')],
                    [__('Marketing Agencies'), __('Manage WhatsApp for multiple clients from one platform with separate inboxes, AI configs, and team access per client.')],
                    [__('Retail & Restaurants'), __('Take orders, share menus, confirm reservations, and handle customer questions instantly — even when the store is closed.')],
                ];
                @endphp
                @foreach($useCases as $useCase)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="font-semibold text-green-700 dark:text-green-400">{{ $useCase[0] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $useCase[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('WhatsApp inbox — common questions') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [__('Does One Inbox work with WhatsApp Business API?'), __('Yes. One Inbox connects via the WhatsApp Business API, which means unlimited messages, no phone-number restrictions, and full automation capability. We handle the API setup for you.')],
                    [__('Can multiple team members use the same WhatsApp number?'), __('Absolutely. One Inbox gives your entire team shared access to a single WhatsApp Business number. Conversations can be assigned to specific agents, and everyone sees the full history.')],
                    [__('Will the AI sound robotic to my customers?'), __('No. You train the AI with your brand voice, product details, and communication style. It sounds like your best sales rep — not a generic chatbot.')],
                    [__('Can I take over a conversation from the AI?'), __('Yes, at any time. You can pause AI on any conversation and reply manually. When you\'re done, re-enable AI and it picks up where it left off with full context.')],
                    [__('What happens when the AI can\'t answer a question?'), __('The AI recognizes when it\'s out of its depth and automatically flags the conversation for human review. Your team gets notified instantly.')],
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
            <div class="rounded-3xl bg-gradient-to-br from-green-600 to-emerald-500 p-10 text-center text-white sm:p-16">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ __('Start managing WhatsApp like a pro') }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-green-100">{{ __('Connect your WhatsApp Business number in minutes. AI starts handling conversations immediately.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 font-semibold text-green-700 shadow-lg transition-all hover:bg-green-50">
                    {{ __('Connect WhatsApp Free') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-green-200">{{ __('No credit card required') }}</p>
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
            "name": "Does One Inbox work with WhatsApp Business API?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. One Inbox connects via the WhatsApp Business API, which means unlimited messages, no phone-number restrictions, and full automation capability. We handle the API setup for you."}
        },
        {
            "@@type": "Question",
            "name": "Can multiple team members use the same WhatsApp number?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Absolutely. One Inbox gives your entire team shared access to a single WhatsApp Business number. Conversations can be assigned to specific agents, and everyone sees the full history."}
        },
        {
            "@@type": "Question",
            "name": "Will the AI sound robotic to my customers?",
            "acceptedAnswer": {"@@type": "Answer", "text": "No. You train the AI with your brand voice, product details, and communication style. It sounds like your best sales rep — not a generic chatbot."}
        },
        {
            "@@type": "Question",
            "name": "Can I take over a conversation from the AI?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes, at any time. You can pause AI on any conversation and reply manually. When you're done, re-enable AI and it picks up where it left off with full context."}
        },
        {
            "@@type": "Question",
            "name": "What happens when the AI can't answer a question?",
            "acceptedAnswer": {"@@type": "Answer", "text": "The AI recognizes when it's out of its depth and automatically flags the conversation for human review. Your team gets notified instantly."}
        }
    ]
}
</script>
@endpush

</x-layouts.marketing>
