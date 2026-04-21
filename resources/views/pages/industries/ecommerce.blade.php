<x-layouts.marketing
    :title="__('WhatsApp Inbox for E-commerce Stores — One Inbox')"
    :description="__('Manage order inquiries, shipping questions, and returns from WhatsApp, Instagram, and Facebook in one inbox. AI handles 90% of questions automatically.')"
    :canonical="route('industry.ecommerce')"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can I connect my WhatsApp to handle order inquiries?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. Connect your WhatsApp Business API account and the AI instantly handles common e-commerce questions: order status, shipping times, return policies, sizing guides, and product availability.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('How does the AI know about my products?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('You provide your product catalog, pricing, and policies in the business profile. The AI uses this as its knowledge base and answers accurately within those limits.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can I use it for WhatsApp order notifications too?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes — with the WhatsApp Business API you can send outbound messages including order confirmations, shipping updates, and delivery notifications to customers who have opted in.')) }}" }
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
                        {{ __('E-commerce') }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {!! __('Handle <span class="text-purple-400">Every Order Inquiry</span> on WhatsApp and Social — Automatically') !!}
                    </h1>
                    <p class="mt-5 text-lg text-zinc-300">
                        {{ __('E-commerce customers flood WhatsApp, Instagram, and Facebook with "Where\'s my order?" questions all day. One Inbox\'s AI answers them instantly — freeing your team to focus on selling, not support.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-purple-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-purple-700">
                            {{ __('Start Free') }}
                        </a>
                        <a href="{{ route('features') }}" class="rounded-xl border border-zinc-600 px-6 py-3 font-semibold text-zinc-300 transition-colors hover:border-zinc-400 hover:text-white">
                            {{ __('See All Features') }}
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border border-zinc-700/50 bg-zinc-900/60 p-6">
                    @php
                    $metrics = [
                        [__('Avg. first response'), '< 30s', __('vs. 4+ hours before')],
                        [__('Messages handled by AI'), '87%', __('zero agent time')],
                        [__('Missed conversations'), '0', __('after-hours included')],
                    ];
                    @endphp
                    <p class="mb-4 text-sm font-semibold text-zinc-400">{{ __('Real results for e-commerce stores') }}</p>
                    @foreach($metrics as [$label, $value, $sub])
                    <div class="mb-4 rounded-lg bg-zinc-800 px-4 py-3">
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                        <p class="text-2xl font-bold text-purple-400">{{ $value }}</p>
                        <p class="text-xs text-zinc-500">{{ $sub }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Use Cases --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold">{{ __('What the AI Handles for Your Store') }}</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('90% of e-commerce WhatsApp messages fall into these categories — the AI handles them all.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $usecases = [
                    ['📦', __('Order Status'), __('"Where\'s my order?" "When will it arrive?" The AI checks order details and gives accurate updates.')],
                    ['↩️', __('Returns & Exchanges'), __('Explains your return policy, collects order numbers, and initiates return flows.')],
                    ['📐', __('Sizing & Specs'), __('Answers product questions — dimensions, colors, materials, compatibility — from your catalog.')],
                    ['💳', __('Payment Issues'), __('Guides customers through payment steps, failed transactions, and supported methods.')],
                    ['🚚', __('Shipping Queries'), __('Delivery timeframes, shipping costs, international shipping — handled instantly.')],
                    ['🛍️', __('Pre-sale Qualification'), __('Recommends products based on budget and needs — and sends customers directly to the product page.')],
                ];
                @endphp
                @foreach($usecases as [$icon, $title, $desc])
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Platform Channels --}}
    <section class="bg-zinc-50 py-20 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold">{{ __('All Your Sales Channels. One Inbox.') }}</h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('Your customers shop and ask questions across every platform. You shouldn\'t need 4 different apps to keep up.') }}</p>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $channels = [
                    ['WhatsApp', __('Order inquiries, product questions, post-purchase support')],
                    ['Instagram', __('DMs from product posts, story replies, shopping inquiries')],
                    ['Facebook', __('Messenger conversations from ads and Page visitors')],
                    ['Telegram', __('Customers who prefer Telegram for support')],
                ];
                @endphp
                @foreach($channels as [$ch, $desc])
                <div class="rounded-xl border border-zinc-200 bg-white p-5 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="font-semibold">{{ $ch }}</p>
                    <p class="mt-2 text-xs text-zinc-500">{{ $desc }}</p>
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
                [__('Can I connect my WhatsApp to handle order inquiries?'), __('Yes. Connect your WhatsApp Business API account and the AI instantly handles common e-commerce questions: order status, shipping times, return policies, sizing guides, and product availability.')],
                [__('How does the AI know about my products?'), __('You provide your product catalog, pricing, and policies in the business profile. The AI uses this as its knowledge base and answers accurately within those limits.')],
                [__('Can I use it for WhatsApp order notifications too?'), __('Yes — with the WhatsApp Business API you can send outbound messages including order confirmations, shipping updates, and delivery notifications to customers who have opted in.')],
                [__('What happens when the AI can\'t answer?'), __('If a question falls outside the AI\'s knowledge or the customer requests a human, the conversation is immediately flagged and assigned to an available agent.')],
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
            <h2 class="text-3xl font-bold">{{ __('Turn Social Messages Into Sales') }}</h2>
            <p class="mt-3 text-purple-100">{{ __('One inbox for all your channels, AI that works 24/7. Free to start.') }}</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 font-semibold text-purple-700 transition-all hover:bg-purple-50">
                {{ __('Get Started Free') }}
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

</x-layouts.marketing>
