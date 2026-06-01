<x-layouts.marketing
    :title="__('WhatsApp Inbox for Real Estate Agents — OT1-Pro')"
    :description="__('Manage property inquiries from WhatsApp, Instagram, Facebook, and Telegram in one inbox. AI responds 24/7 so you never miss a buyer or renter lead.')"
    :canonical="route('industry.real-estate')"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can I manage WhatsApp leads from multiple property listings in one inbox?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. OT1-Pro connects your WhatsApp Business API number alongside Instagram, Facebook, and Telegram. All leads from all platforms appear in one unified inbox that your whole team shares.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('How does the AI handle real estate inquiries?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('You provide your property listings, pricing, and key FAQs. The AI answers questions about availability, pricing, location, and amenities — and qualifies buyers by budget and timeline before connecting them to a human agent.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can multiple agents work the same WhatsApp number?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. With the WhatsApp Business API, your entire team works from one number simultaneously. Conversations are assigned to specific agents so nothing falls through the cracks.')) }}" }
        }
    ]
}
</script>
@endpush

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-white via-indigo-50/60 to-white py-24 text-zinc-900">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm font-medium text-indigo-700">
                        {{ __('Real Estate') }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {!! __('Close More Property Deals with a <span class="text-indigo-400">WhatsApp Inbox</span> for Real Estate') !!}
                    </h1>
                    <p class="mt-5 text-lg text-zinc-700">
                        {{ __('Buyers and renters message you on WhatsApp, Instagram, and Facebook — often at night, on weekends, when your agents are unavailable. OT1-Pro and its AI responder make sure every lead gets an instant, intelligent reply.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-indigo-700">
                            {{ __('Start Free') }}
                        </a>
                        <a href="{{ route('features') }}" class="rounded-xl border border-zinc-300 px-6 py-3 font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:text-white">
                            {{ __('See All Features') }}
                        </a>
                    </div>
                </div>
                <div class="relative rounded-2xl border border-zinc-200 bg-white p-6 backdrop-blur-sm">
                    @php
                    $chats = [
                        ['from' => 'Ahmed K.', 'msg' => __('Hi, I saw the listing on Instagram. Is the 3BR apartment still available?'), 'channel' => 'Instagram', 'time' => '10:42 PM'],
                        ['from' => 'AI', 'msg' => __('Hi Ahmed! Yes, the 3BR apartment in Zamalek is available. It\'s 2,400 sq ft, EGP 18,000/month, available from June 1st. Would you like to schedule a viewing?'), 'channel' => '', 'time' => '10:42 PM'],
                        ['from' => 'Ahmed K.', 'msg' => __('Yes please. Can we do Saturday morning?'), 'channel' => 'Instagram', 'time' => '10:43 PM'],
                        ['from' => 'Sara M.', 'msg' => __('What\'s the price of the villa in New Cairo?'), 'channel' => 'WhatsApp', 'time' => '10:45 PM'],
                    ];
                    @endphp
                    @foreach($chats as $chat)
                    <div class="mb-3 flex items-start gap-3">
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $chat['from'] === 'AI' ? 'bg-indigo-600' : 'bg-zinc-700' }} text-xs font-bold text-white">
                            {{ $chat['from'] === 'AI' ? 'AI' : substr($chat['from'], 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-zinc-700">{{ $chat['from'] }}</span>
                                @if($chat['channel'])
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">{{ $chat['channel'] }}</span>
                                @endif
                                <span class="text-xs text-zinc-600">{{ $chat['time'] }}</span>
                            </div>
                            <p class="mt-1 rounded-lg {{ $chat['from'] === 'AI' ? 'bg-indigo-900/40 text-indigo-100' : 'bg-zinc-100 text-zinc-700' }} px-3 py-2 text-sm">{{ $chat['msg'] }}</p>
                        </div>
                    </div>
                    @endforeach
                    <div class="mt-3 rounded-lg bg-green-900/30 px-3 py-2 text-center text-xs font-medium text-green-400">
                        {{ __('AI responded in < 5 seconds — no agent needed') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pain Points --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('The Real Estate Lead Problem') }}</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-600">{{ __('Property buyers don\'t wait. If you\'re slow, they move to the next listing.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $pains = [
                    [__('Leads fall through at night'), __('Buyers message at 9 PM after work. Your agents are offline. The lead goes cold by morning.')],
                    [__('Scattered across platforms'), __('Inquiries on WhatsApp, Instagram DMs, Facebook Messenger — all separate, all needing separate attention.')],
                    [__('Agents miss assignments'), __('Two agents respond to the same lead. Or no one does. No clear ownership = lost deals.')],
                    [__('No lead qualification'), __('You spend time on tire-kickers who aren\'t serious buyers, while hot leads wait.')],
                    [__('Slow follow-up'), __('Property inquiries are time-sensitive. A day-old follow-up is a cold lead.')],
                    [__('No visibility for managers'), __('Who handled which inquiry? What was said? No audit trail, no accountability.')],
                ];
                @endphp
                @foreach($pains as [$title, $desc])
                <div class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-900/30 dark:bg-red-950/20">
                    <h3 class="font-semibold text-red-800 dark:text-red-300">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-zinc-50 py-20 dark:bg-zinc-50">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('Built for Real Estate Teams') }}</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-600">{{ __('Everything a real estate agency needs to handle leads at speed.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['💬', __('Unified Inbox'), __('WhatsApp, Instagram, Facebook, and Telegram leads — all in one inbox your whole team shares.')],
                    ['🤖', __('AI Lead Qualifier'), __('The AI asks buyers about budget, timeline, and preferences — and tags hot leads for immediate agent follow-up.')],
                    ['📋', __('Agent Assignment'), __('Automatically assign inquiries to the right agent based on area, property type, or language.')],
                    ['🕐', __('24/7 Coverage'), __('AI handles inquiries at 2 AM so your agents wake up to warm, pre-qualified leads — not cold ones.')],
                    ['📞', __('Instant Viewing Bookings'), __('AI collects contact details and preferred viewing times, then queues them for agent confirmation.')],
                    ['📊', __('Lead Analytics'), __('Track lead volume by platform, response time, and conversion rate — see where your best leads come from.')],
                ];
                @endphp
                @foreach($features as [$icon, $title, $desc])
                <div class="rounded-xl p-6 shadow-sm transition-colors {{ $loop->first
                    ? 'lg:col-span-2 lg:p-8 border border-indigo-200 bg-indigo-50/60 dark:border-indigo-200 dark:bg-indigo-50/60'
                    : 'bg-white dark:bg-white' }}">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="mb-10 text-center text-3xl font-bold">{{ __('Frequently Asked Questions') }}</h2>
            @php
            $faqs = [
                [__('Can I manage WhatsApp leads from multiple property listings in one inbox?'), __('Yes. OT1-Pro connects your WhatsApp Business API number alongside Instagram, Facebook, and Telegram. All leads from all platforms appear in one unified inbox that your whole team shares.')],
                [__('How does the AI handle real estate inquiries?'), __('You provide your property listings, pricing, and key FAQs. The AI answers questions about availability, pricing, location, and amenities — and qualifies buyers by budget and timeline before connecting them to a human agent.')],
                [__('Can multiple agents work the same WhatsApp number?'), __('Yes. With the WhatsApp Business API, your entire team works from one number simultaneously. Conversations are assigned to specific agents so nothing falls through the cracks.')],
                [__('Does it work for rental agencies as well as property sales?'), __('Absolutely. The AI adapts to rental or sales workflows. Configure it with your available units, pricing, lease terms, and it handles inquiries for both.')],
            ];
            @endphp
            <div class="space-y-4">
                @foreach($faqs as [$q, $a])
                <div x-data="{ open: false }" class="rounded-xl border border-zinc-200 dark:border-zinc-200">
                    <button @click="open = !open" class="flex w-full items-center justify-between px-5 py-4 text-left font-medium">
                        <span>{{ $q }}</span>
                        <svg class="size-5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open" x-collapse class="border-t border-zinc-100 px-5 py-4 text-zinc-600 dark:border-zinc-200 dark:text-zinc-600">
                        {{ $a }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-zinc-200 bg-zinc-50 py-20 lg:py-28">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('Stop Losing Real Estate Leads After Hours') }}</h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('Set up your unified inbox and AI responder in minutes. Free to start.') }}</p>
            <a href="{{ route('register') }}" class="mt-10 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md">
                {{ __('Get Started Free') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

</x-layouts.marketing>
