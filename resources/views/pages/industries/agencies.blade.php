<x-layouts.marketing
    :title="__('Social Media Inbox for Marketing Agencies — One Inbox')"
    :description="__('Manage multiple client social inboxes from one platform. AI responds to leads across WhatsApp, Instagram, Facebook, and Telegram for all your clients simultaneously.')"
    :canonical="route('industry.agencies')"
>

@push('schema')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "{{ addslashes(__('Can I manage multiple client accounts from one One Inbox login?')) }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ addslashes(__('Yes. Each client gets their own team workspace. You can switch between clients instantly, and the AI is configured separately for each client\'s business context.')) }}" }
        },
        {
            "@type": "Question",
            "name": "{{ addslashes(__('Can each client have their own AI responder persona?')) }}",
            "acceptedAnswer": { "@type": "Answer", "text": "{{ addslashes(__('Yes. Each workspace has its own AI configuration — separate business description, product/service info, and tone guidelines. The AI acts as the client\'s own brand voice, not a generic bot.')) }}" }
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
                        {{ __('Marketing Agencies') }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {!! __('Manage All Your <span class="text-purple-400">Client Inboxes</span> From One Platform') !!}
                    </h1>
                    <p class="mt-5 text-lg text-zinc-300">
                        {{ __('Your clients\' customers are messaging on WhatsApp, Instagram, Facebook, and Telegram — and expecting fast, intelligent replies. One Inbox lets your agency handle all of it, with AI doing the heavy lifting.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-purple-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Start Free') }}
                        </a>
                        {{-- <a href="{{ route('pricing') }}" class="rounded-xl border border-zinc-600 px-6 py-3 font-semibold text-zinc-300 transition-colors hover:border-zinc-400 hover:text-white">
                            {{ __('Agency Pricing') }}
                        </a> --}}
                    </div>
                </div>
                <div class="rounded-2xl border border-zinc-700/50 bg-zinc-900/60 p-6">
                    @php
                    $clients = [
                        ['🏠', __('Real Estate Client'), 'WhatsApp + Instagram', __('12 conversations today')],
                        ['👗', __('Fashion Brand'), 'Instagram + Facebook', __('47 conversations today')],
                        ['🍔', __('Restaurant Chain'), 'WhatsApp + Instagram', __('8 conversations today')],
                        ['🏥', __('Clinic'), 'WhatsApp + Telegram', __('5 conversations today')],
                    ];
                    @endphp
                    <p class="mb-4 text-sm font-semibold text-zinc-400">{{ __('Your client workspaces') }}</p>
                    @foreach($clients as [$icon, $name, $channels, $activity])
                    <div class="mb-3 flex items-center gap-3 rounded-lg bg-zinc-800 px-4 py-3">
                        <div class="text-xl">{{ $icon }}</div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-zinc-200">{{ $name }}</p>
                            <p class="text-xs text-zinc-500">{{ $channels }}</p>
                        </div>
                        <span class="text-xs text-green-400">{{ $activity }}</span>
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
                <h2 class="text-3xl font-bold">{{ __('Why Agencies Choose One Inbox') }}</h2>
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
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Industries Served --}}
    <section class="bg-zinc-50 py-20 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold">{{ __('Clients Across Every Industry') }}</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('One Inbox works for the clients you already have and the ones you\'re pitching.') }}</p>
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
                <span class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium dark:border-zinc-700 dark:bg-zinc-900">{{ $ind }}</span>
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
                [__('Can I manage multiple client accounts from one One Inbox login?'), __('Yes. Each client gets their own team workspace. You can switch between clients instantly, and the AI is configured separately for each client\'s business context.')],
                [__('Can each client have their own AI responder persona?'), __('Yes. Each workspace has its own AI configuration — separate business description, product/service info, and tone guidelines. The AI acts as the client\'s own brand voice, not a generic bot.')],
                [__('Can I white-label the platform for my clients?'), __('Contact us for agency and white-label options. We work with agencies to find the right structure for your business model.')],
                [__('How does billing work for agencies with multiple clients?'), __('Each workspace is billed separately. Enterprise plans cover multiple workspaces at a volume discount. Contact us to discuss your agency\'s needs.')],
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
            <h2 class="text-3xl font-bold">{{ __('Offer AI-Powered Social Inbox as an Agency Service') }}</h2>
            <p class="mt-3 text-purple-100">{{ __('Differentiate your agency with AI inbox management. Start with one client, scale to all of them.') }}</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 font-semibold text-purple-700 transition-all hover:bg-purple-50">
                {{ __('Get Started Free') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

</x-layouts.marketing>
