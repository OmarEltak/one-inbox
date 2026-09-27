<x-layouts.brand-marketing
    :solidNav="true"
    :title="__('WhatsApp for Restaurants: Orders, Reservations & Delivery — OT1-Pro')"
    :description="__('Take reservations, handle delivery orders, and answer menu questions via WhatsApp and Instagram — with AI that works around the clock, even during dinner rush.')"
    :canonical="route('industry.restaurants')"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can customers place orders via WhatsApp?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. The AI can take orders, confirm details, and collect payment instructions. It handles the entire order conversation so your team only steps in for special requests.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can I take reservations through WhatsApp?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. The AI collects party size, date, time preference, and contact info — then queues it for your team to confirm. Customers get an immediate reply even at midnight.')) }}" }
        }
    ]
}
</script>
@endpush

    {{-- Hero --}}
    <section class="relative overflow-hidden pt-32 pb-20 text-ink lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-emer-700">
                        {{ __('Restaurants & Food') }}
                    </span>
                    <h1 class="serif mt-5 text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {!! __('<span class="serif-it text-emer-700">WhatsApp Orders, Reservations,</span> and Delivery — All on Autopilot') !!}
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Your kitchen is busy. Your team is busy. But customers are messaging you on WhatsApp and Instagram for menus, delivery times, and table bookings — right now. Let the AI handle it.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Start Free') }}
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border border-line bg-cream2 p-6 shadow-sm">
                    @php
                    $msgs = [
                        ['👤', __('Do you have delivery to Maadi?'), __('Customer')],
                        ['🤖', __('Yes! We deliver to Maadi. Delivery is free on orders over 300 EGP, 45–60 min. What would you like to order?'), __('AI')],
                        ['👤', __('Can I see the menu?'), __('Customer')],
                        ['🤖', __('Of course! Here\'s our menu link: ot1-pro.com/menu — or I can help you order directly here on WhatsApp. What are you in the mood for?'), __('AI')],
                    ];
                    @endphp
                    @foreach($msgs as [$icon, $text, $who])
                    <div class="mb-3 flex gap-3 {{ $who === __('AI') ? 'flex-row-reverse' : '' }}">
                        <div class="size-8 shrink-0 rounded-full {{ $who === __('AI') ? 'bg-emer-600' : 'bg-ink' }} flex items-center justify-center text-sm">{{ $icon }}</div>
                        <div class="max-w-xs rounded-xl {{ $who === __('AI') ? 'bg-emer-100 text-emer-900' : 'bg-cream text-ink/80' }} px-3 py-2 text-sm">{{ $text }}</div>
                    </div>
                    @endforeach
                    <p class="mt-3 text-center text-xs text-ink/70">{{ __('AI handled this — no staff needed') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('Everything Your Restaurant Needs') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['🍽️', __('Table Reservations'), __('AI collects party size, date, time, and contact info. Queues for staff to confirm. Customers get instant acknowledgment.')],
                    ['🛵', __('Delivery Orders'), __('Handle WhatsApp delivery orders end-to-end: item selection, address, payment method, estimated time.')],
                    ['📋', __('Menu Inquiries'), __('Answers questions about ingredients, allergens, daily specials, and pricing — at any hour.')],
                    ['📍', __('Location & Hours'), __('Customers asking where you are or when you close get instant, accurate answers — not "please call us".')],
                    ['🎉', __('Event & Group Bookings'), __('Large party inquiries routed to your events team with all details captured by AI first.')],
                    ['⭐', __('Review Follow-up'), __('Post-visit AI message to satisfied customers encouraging Google/TripAdvisor reviews.')],
                ];
                @endphp
                @foreach($features as [$icon, $title, $desc])
                <div class="fade-up rounded-2xl border p-6 transition-colors {{ $loop->first
 ? 'lg:col-span-2 lg:p-7 border-emer-100 bg-emer-50/60'
 : 'border-line bg-cream' }}">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="serif mt-3 text-2xl leading-snug text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-cream2 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('Frequently Asked Questions') }}</h2>
            @php
            $faqs = [
                [__('Can customers place orders via WhatsApp?'), __('Yes. The AI can take orders, confirm details, and collect payment instructions. It handles the entire order conversation so your team only steps in for special requests.')],
                [__('Can I take reservations through WhatsApp?'), __('Yes. The AI collects party size, date, time preference, and contact info — then queues it for your team to confirm. Customers get an immediate reply even at midnight.')],
                [__('How does it handle the dinner rush when the team is too busy to reply?'), __('The AI handles all incoming WhatsApp and Instagram messages independently. Your staff never has to touch their phone during service — the AI has it covered.')],
                [__('Can I customize the menu information the AI uses?'), __('Yes. You provide your menu, prices, daily specials, allergen info, and delivery zones. The AI answers accurately based on what you\'ve given it.')],
            ];
            @endphp
            <div class="divide-y divide-line border-y border-line">
                @foreach($faqs as [$q, $a])
                <details class="py-5">
                    <summary class="flex items-center justify-between gap-4">
                        <span class="serif text-xl text-ink">{{ $q }}</span>
                        <span class="chev serif text-2xl text-emer-700">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $a }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════ FINAL CTA ═══════ --}}
    <section class="bg-ink text-cream py-24 grain relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Let AI Handle Your WhatsApp While You Focus on the Food') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Set up in minutes. AI starts handling messages immediately. Free to start.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Get Started Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
        </div>
    </section>

</x-layouts.brand-marketing>
