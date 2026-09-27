<x-layouts.brand-marketing
    :solidNav="true"
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
    <section class="relative overflow-hidden pt-32 pb-20 text-ink lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-emer-700">
                        {{ __('Real Estate') }}
                    </span>
                    <h1 class="serif mt-5 text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {!! __('Close More Property Deals with a <span class="serif-it text-emer-700">WhatsApp Inbox</span> for Real Estate') !!}
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Buyers and renters message you on WhatsApp, Instagram, and Facebook — often at night, on weekends, when your agents are unavailable. OT1-Pro and its AI responder make sure every lead gets an instant, intelligent reply.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Start Free') }}
                        </a>
                        <a href="{{ route('features') }}" class="inline-flex items-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                            {{ __('See All Features') }}
                        </a>
                    </div>
                </div>
                <div class="relative rounded-2xl border border-line bg-cream2 p-6 shadow-sm">
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
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $chat['from'] === 'AI' ? 'bg-emer-600' : 'bg-ink' }} text-xs font-bold text-cream">
                            {{ $chat['from'] === 'AI' ? 'AI' : substr($chat['from'], 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-ink/80">{{ $chat['from'] }}</span>
                                @if($chat['channel'])
                                <span class="rounded-full bg-cream px-2 py-0.5 text-xs text-ink/60">{{ $chat['channel'] }}</span>
                                @endif
                                <span class="text-xs text-ink/70">{{ $chat['time'] }}</span>
                            </div>
                            <p class="mt-1 rounded-lg {{ $chat['from'] === 'AI' ? 'bg-emer-100 text-emer-900' : 'bg-cream text-ink/80' }} px-3 py-2 text-sm">{{ $chat['msg'] }}</p>
                        </div>
                    </div>
                    @endforeach
                    <div class="mt-3 rounded-lg bg-emer-100 px-3 py-2 text-center text-xs font-medium text-emer-700">
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
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('The Real Estate Lead Problem') }}</h2>
                <p class="mt-3 text-ink/70">{{ __('Property buyers don\'t wait. If you\'re slow, they move to the next listing.') }}</p>
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
                <div class="fade-up rounded-2xl border border-line bg-cream2 p-6">
                    <h3 class="serif text-xl text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink/70">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-cream2 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Built for Real Estate Teams') }}</h2>
                <p class="mt-3 text-ink/70">{{ __('Everything a real estate agency needs to handle leads at speed.') }}</p>
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
                <div class="fade-up rounded-2xl p-6 shadow-sm transition-colors {{ $loop->first
 ? 'lg:col-span-2 lg:p-8 border border-emer-100 bg-emer-50/60'
 : 'bg-cream' }}">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="serif mt-3 text-2xl leading-snug text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('Frequently Asked Questions') }}</h2>
            @php
            $faqs = [
                [__('Can I manage WhatsApp leads from multiple property listings in one inbox?'), __('Yes. OT1-Pro connects your WhatsApp Business API number alongside Instagram, Facebook, and Telegram. All leads from all platforms appear in one unified inbox that your whole team shares.')],
                [__('How does the AI handle real estate inquiries?'), __('You provide your property listings, pricing, and key FAQs. The AI answers questions about availability, pricing, location, and amenities — and qualifies buyers by budget and timeline before connecting them to a human agent.')],
                [__('Can multiple agents work the same WhatsApp number?'), __('Yes. With the WhatsApp Business API, your entire team works from one number simultaneously. Conversations are assigned to specific agents so nothing falls through the cracks.')],
                [__('Does it work for rental agencies as well as property sales?'), __('Absolutely. The AI adapts to rental or sales workflows. Configure it with your available units, pricing, lease terms, and it handles inquiries for both.')],
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
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Stop Losing Real Estate Leads After Hours') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Set up your unified inbox and AI responder in minutes. Free to start.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Get Started Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
        </div>
    </section>

</x-layouts.brand-marketing>
