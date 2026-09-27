<x-layouts.brand-marketing
    :solidNav="true"
    :title="__('Social Media Inbox for Marketing Agencies — OT1-Pro')"
    :description="__('Manage multiple client social inboxes from one platform. AI responds to leads across WhatsApp, Instagram, Facebook, and Telegram for all your clients simultaneously.')"
    :canonical="route('industry.agencies')"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can I manage multiple client accounts from one OT1-Pro login?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. Each client gets their own team workspace. You can switch between clients instantly, and the AI is configured separately for each client\'s business context.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can each client have their own AI responder persona?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. Each workspace has its own AI configuration — separate business description, product/service info, and tone guidelines. The AI acts as the client\'s own brand voice, not a generic bot.')) }}" }
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
                        {{ __('Marketing Agencies') }}
                    </span>
                    <h1 class="serif mt-5 text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {!! __('Manage All Your <span class="serif-it text-emer-700">Client Inboxes</span> From One Platform') !!}
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('Your clients\' customers are messaging on WhatsApp, Instagram, Facebook, and Telegram — and expecting fast, intelligent replies. OT1-Pro lets your agency handle all of it, with AI doing the heavy lifting.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Start Free') }}
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="inline-flex items-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                            {{ __('Agency Pricing') }}
                        </a> --}}
                    </div>
                </div>
                <div class="rounded-2xl border border-line bg-cream2 p-6 shadow-sm">
                    @php
                    $clients = [
                        ['🏠', __('Real Estate Client'), 'WhatsApp + Instagram', __('12 conversations today')],
                        ['👗', __('Fashion Brand'), 'Instagram + Facebook', __('47 conversations today')],
                        ['🍔', __('Restaurant Chain'), 'WhatsApp + Instagram', __('8 conversations today')],
                        ['🏥', __('Clinic'), 'WhatsApp + Telegram', __('5 conversations today')],
                    ];
                    @endphp
                    <p class="mb-4 text-sm font-semibold text-ink/70">{{ __('Your client workspaces') }}</p>
                    @foreach($clients as [$icon, $name, $channels, $activity])
                    <div class="mb-3 flex items-center gap-3 rounded-lg bg-cream px-4 py-3">
                        <div class="text-xl">{{ $icon }}</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-ink">{{ $name }}</p>
                            <p class="text-xs text-ink/60">{{ $channels }}</p>
                        </div>
                        <span class="text-xs text-emer-700">{{ $activity }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Value Props --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Why Agencies Choose OT1-Pro') }}</h2>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $props = [
                    ['🏢', __('Separate Client Workspaces'), __('Each client gets their own isolated inbox, settings, and AI configuration. No data mixing between clients.')],
                    ['🤖', __('Per-Client AI Personas'), __('Configure the AI to respond in each client\'s brand voice, with their specific product knowledge and tone.')],
                    ['📱', __('All Channels Covered'), __('WhatsApp, Instagram, Facebook Messenger, and Telegram — across all client accounts, from one login.')],
                    ['👥', __('Team Collaboration'), __('Assign conversations to specific team members. Multiple agents can work the same client inbox simultaneously.')],
                    ['📊', __('Client Reporting'), __('Share response time, volume, and AI performance metrics with clients. Prove the value of your social management service.')],
                    ['💼', __('Scalable Pricing'), __('Add new clients without per-seat costs exploding. Flat pricing makes it easy to grow your agency\'s profit margin.')],
                ];
                @endphp
                @foreach($props as [$icon, $title, $desc])
                <div class="fade-up rounded-2xl border p-6 transition-colors {{ $loop->first
 ? 'lg:col-span-2 lg:p-8 border-emer-100 bg-emer-50/60'
 : 'border-line bg-cream' }}">
                    <div class="text-2xl {{ $loop->first ? 'lg:text-3xl' : '' }}">{{ $icon }}</div>
                    <h3 class="serif mt-3 text-2xl leading-snug text-ink {{ $loop->first ? 'lg:text-3xl' : '' }}">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink/70 {{ $loop->first ? 'lg:text-base' : '' }}">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Industries Served --}}
    <section class="bg-cream2 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Clients Across Every Industry') }}</h2>
                <p class="mt-3 text-ink/70">{{ __('OT1-Pro works for the clients you already have and the ones you\'re pitching.') }}</p>
            </div>
            <div class="mt-10 flex flex-wrap justify-center gap-3">
                @php
                $industries = [
                    __('Real Estate'), __('E-commerce'), __('Restaurants'), __('Healthcare'),
                    __('Education'), __('Automotive'), __('Fashion'), __('Travel & Tourism'),
                    __('Finance'), __('Beauty & Wellness'), __('Legal Services'), __('Events'),
                ];
                @endphp
                @foreach($industries as $ind)
                <span class="rounded-full border border-line bg-cream px-4 py-2 text-sm font-medium">{{ $ind }}</span>
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
                [__('Can I manage multiple client accounts from one OT1-Pro login?'), __('Yes. Each client gets their own team workspace. You can switch between clients instantly, and the AI is configured separately for each client\'s business context.')],
                [__('Can each client have their own AI responder persona?'), __('Yes. Each workspace has its own AI configuration — separate business description, product/service info, and tone guidelines. The AI acts as the client\'s own brand voice, not a generic bot.')],
                [__('Can I white-label the platform for my clients?'), __('Contact us for agency and white-label options. We work with agencies to find the right structure for your business model.')],
                [__('How does billing work for agencies with multiple clients?'), __('Each workspace is billed separately. Enterprise plans cover multiple workspaces at a volume discount. Contact us to discuss your agency\'s needs.')],
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
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Offer AI-Powered Social Inbox as an Agency Service') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Differentiate your agency with AI inbox management. Start with one client, scale to all of them.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Get Started Free') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
        </div>
    </section>

</x-layouts.brand-marketing>
