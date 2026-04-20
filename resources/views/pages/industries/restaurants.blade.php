<x-layouts.marketing
    :title="__('WhatsApp for Restaurants: Orders, Reservations & Delivery — One Inbox')"
    :description="__('Take reservations, handle delivery orders, and answer menu questions via WhatsApp and Instagram — with AI that works around the clock, even during dinner rush.')"
    :canonical="route('industry.restaurants')"
>

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "{{ addslashes(__('Can customers place orders via WhatsApp?')) }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ addslashes(__('Yes. The AI can take orders, confirm details, and collect payment instructions. It handles the entire order conversation so your team only steps in for special requests.')) }}" }
        },
        {
            "@type": "Question",
            "name": "{{ addslashes(__('Can I take reservations through WhatsApp?')) }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ addslashes(__('Yes. The AI collects party size, date, time preference, and contact info — then queues it for your team to confirm. Customers get an immediate reply even at midnight.')) }}" }
        }
    ]
}
</script>
@endpush

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-zinc-950 via-purple-950/40 to-zinc-950 py-24 text-white">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-purple-500/30 bg-purple-500/10 px-4 py-1.5 text-sm font-medium text-purple-300">
                        {{ __('Restaurants & Food') }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {!! __('<span class="text-purple-400">WhatsApp Orders, Reservations,</span> and Delivery — All on Autopilot') !!}
                    </h1>
                    <p class="mt-5 text-lg text-zinc-300">
                        {{ __('Your kitchen is busy. Your team is busy. But customers are messaging you on WhatsApp and Instagram for menus, delivery times, and table bookings — right now. Let the AI handle it.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-purple-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Start Free') }}
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border border-zinc-700/50 bg-zinc-900/60 p-6">
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
                        <div class="size-8 shrink-0 rounded-full {{ $who === __('AI') ? 'bg-purple-600' : 'bg-zinc-700' }} flex items-center justify-center text-sm">{{ $icon }}</div>
                        <div class="max-w-xs rounded-xl {{ $who === __('AI') ? 'bg-purple-900/40 text-purple-100' : 'bg-zinc-800 text-zinc-300' }} px-3 py-2 text-sm">{{ $text }}</div>
                    </div>
                    @endforeach
                    <p class="mt-3 text-center text-xs text-zinc-600">{{ __('AI handled this — no staff needed') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold">{{ __('Everything Your Restaurant Needs') }}</h2>
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
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-zinc-50 py-20 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold">{{ __('Frequently Asked Questions') }}</h2>
            @php
            $faqs = [
                [__('Can customers place orders via WhatsApp?'), __('Yes. The AI can take orders, confirm details, and collect payment instructions. It handles the entire order conversation so your team only steps in for special requests.')],
                [__('Can I take reservations through WhatsApp?'), __('Yes. The AI collects party size, date, time preference, and contact info — then queues it for your team to confirm. Customers get an immediate reply even at midnight.')],
                [__('How does it handle the dinner rush when the team is too busy to reply?'), __('The AI handles all incoming WhatsApp and Instagram messages independently. Your staff never has to touch their phone during service — the AI has it covered.')],
                [__('Can I customize the menu information the AI uses?'), __('Yes. You provide your menu, prices, daily specials, allergen info, and delivery zones. The AI answers accurately based on what you\'ve given it.')],
            ];
            @endphp
            <div class="space-y-4">
                @foreach($faqs as [$q, $a])
                <div x-data="{ open: false }" class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <button @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left font-medium">
                        <span>{{ $q }}</span>
                        <svg class="size-5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" x-collapse class="border-t border-zinc-100 px-5 py-4 text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                        {{ $a }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-gradient-to-br from-purple-600 to-blue-600 py-20 text-white">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold">{{ __('Let AI Handle Your WhatsApp While You Focus on the Food') }}</h2>
            <p class="mt-3 text-purple-100">{{ __('Set up in minutes. AI starts handling messages immediately. Free to start.') }}</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 font-semibold text-purple-700 transition-all hover:bg-purple-50">
                {{ __('Get Started Free') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

</x-layouts.marketing>
