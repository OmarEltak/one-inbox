<x-layouts.brand-marketing
    :title="__('WhatsApp Business Inbox — Manage Every Message | OT1-Pro')"
    :description="__('Manage every WhatsApp Business conversation from one unified inbox. AI auto-replies 24/7, scores leads, and hands off hot prospects to your team instantly. Try free.')"
    :solidNav="true"
>

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <div class="mb-6 inline-flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-emer-700">
                        <div class="flex size-10 items-center justify-center rounded-full bg-emer-100">
                            <svg class="size-5 text-emer-700" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </div>
                        {{ __('WhatsApp Business Inbox') }}
                    </div>
                    <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {{ __('The WhatsApp Business Inbox Built for') }} <span class="serif-it text-emer-700">{{ __('Sales Teams') }}</span>
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Stop managing WhatsApp in your phone. OT1-Pro gives your entire team a shared WhatsApp Business inbox with AI that replies instantly, qualifies every lead, and never misses a sale — 24/7.') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Connect WhatsApp Free') }}
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                            {{ __('View Pricing') }}
                        </a> --}}
                    </div>
                    <p class="mt-4 text-sm text-ink/60">{{ __('No credit card required · Free plan available') }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-cream2 p-8">
                    <div class="space-y-4">
                        @foreach([
                            ['bg-emer-100 text-emer-700', __('AI'), __('Hi! Thanks for reaching out. What product are you interested in today?'), false],
                            ['bg-line text-ink/80', __('Lead'), __('I want to know the price of your premium package'), true],
                            ['bg-emer-100 text-emer-700', __('AI'), __('Great choice! The premium package is $299/mo and includes unlimited users. Are you looking for monthly or annual billing? (Annual saves 20%)'), false],
                            ['bg-line text-ink/80', __('Lead'), __('Annual sounds good. How do I sign up?'), true],
                        ] as $msg)
                        <div class="flex {{ $msg[3] ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-xs rounded-2xl {{ $msg[3] ? 'rounded-tr-sm' : 'rounded-tl-sm' }} {{ $msg[0] }} px-4 py-2.5 text-sm">
                                <p class="mb-1 text-xs font-semibold opacity-60">{{ $msg[1] }}</p>
                                {{ $msg[2] }}
                            </div>
                        </div>
                        @endforeach
                        <div class="rounded-xl border border-emer-100 bg-emer-50 px-4 py-2 text-center text-xs font-medium text-emer-700">
                            {{ __('Lead Score: 87/100 — Hot prospect · Handed off to sales team') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Problem / Why section --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('WhatsApp is your best sales channel. But managing it is a mess.') }}</h2>
                <p class="mt-4 text-lg text-ink/70">{{ __('Every business faces the same WhatsApp problems. OT1-Pro solves all of them.') }}</p>
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
                <div class="fade-up rounded-2xl border border-line bg-cream p-6">
                    <p class="text-sm text-ink/60">❌ {{ $item[0] }}</p>
                    <p class="mt-2 text-sm font-medium text-emer-700">✅ {{ $item[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-24 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Everything your WhatsApp sales team needs') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Built specifically for businesses that sell and support through WhatsApp.') }}</p>
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
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <div class="mb-3 text-3xl">{{ $feature[0] }}</div>
                    <h3 class="serif text-2xl leading-snug text-ink">{{ $feature[1] }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $feature[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Use Cases --}}
    <section class="border-y border-line bg-cream2 py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Who uses OT1-Pro for WhatsApp?') }}</h2>
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
                <div class="fade-up rounded-2xl border border-line bg-cream p-6">
                    <h3 class="serif text-xl text-emer-700">{{ $useCase[0] }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $useCase[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-24 lg:py-28">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('WhatsApp inbox — common questions') }}</h2>
            </div>
            <div class="mt-12 divide-y divide-line border-y border-line">
                @php
                $faqs = [
                    [__('Does OT1-Pro work with WhatsApp Business API?'), __('Yes. OT1-Pro connects via the WhatsApp Business API, which means unlimited messages, no phone-number restrictions, and full automation capability. We handle the API setup for you.')],
                    [__('Can multiple team members use the same WhatsApp number?'), __('Absolutely. OT1-Pro gives your entire team shared access to a single WhatsApp Business number. Conversations can be assigned to specific agents, and everyone sees the full history.')],
                    [__('Will the AI sound robotic to my customers?'), __('No. You train the AI with your brand voice, product details, and communication style. It sounds like your best sales rep — not a generic chatbot.')],
                    [__('Can I take over a conversation from the AI?'), __('Yes, at any time. You can pause AI on any conversation and reply manually. When you\'re done, re-enable AI and it picks up where it left off with full context.')],
                    [__('What happens when the AI can\'t answer a question?'), __('The AI recognizes when it\'s out of its depth and automatically flags the conversation for human review. Your team gets notified instantly.')],
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
            <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Related WhatsApp guides') }}</h2>
            <p class="mt-2 text-ink/70">{{ __('Deep dives on running WhatsApp Business at scale.') }}</p>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $related = [
                    ['whatsapp-crm-complete-guide', __('WhatsApp CRM: The Complete Guide for 2026'), __('8 min read')],
                    ['whatsapp-business-api-setup', __('WhatsApp Business API: How to Get Access'), __('7 min read')],
                    ['whatsapp-lead-generation-strategies', __('WhatsApp Lead Generation: 7 Proven Strategies'), __('7 min read')],
                    ['whatsapp-ecommerce-cart-recovery', __('WhatsApp Cart Recovery for E-commerce'), __('6 min read')],
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
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Start managing WhatsApp like a pro') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Connect your WhatsApp Business number in minutes. AI starts handling conversations immediately.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Connect WhatsApp Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
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
            "name": "Does OT1-Pro work with WhatsApp Business API?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Yes. OT1-Pro connects via the WhatsApp Business API, which means unlimited messages, no phone-number restrictions, and full automation capability. We handle the API setup for you."}
        },
        {
            "@@type": "Question",
            "name": "Can multiple team members use the same WhatsApp number?",
            "acceptedAnswer": {"@@type": "Answer", "text": "Absolutely. OT1-Pro gives your entire team shared access to a single WhatsApp Business number. Conversations can be assigned to specific agents, and everyone sees the full history."}
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

</x-layouts.brand-marketing>
