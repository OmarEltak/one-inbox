<x-layouts.marketing
    :title="__('WhatsApp + Instagram Inbox for Dropshippers in Egypt & MENA — OT1-Pro')"
    :description="__('Egyptian and GCC dropshippers get flooded with WhatsApp and Instagram DMs on ad-driven traffic. OT1-Pro\'s AI handles pricing, shipping, and cash-on-delivery questions 24/7 — so no lead is lost overnight.')"
    :canonical="route('industry.dropshipping')"
>

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Does OT1-Pro handle cash-on-delivery (COD) confirmations automatically?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. The AI can confirm the order details, ask for the customer\'s address and preferred delivery window, and hand off a clean order summary to your fulfilment team — all inside the WhatsApp chat.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can the AI reply in Egyptian dialect?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian and Gulf Arabic dialects natively — including code-switching and slang.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Does OT1-Pro connect to Bosta, Aramex, or ShipBlu for shipment tracking?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. Shipment tracking events can be pulled via webhook from Bosta, Aramex, ShipBlu, and other MENA logistics providers, then pushed as WhatsApp updates to the customer automatically.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('What is the cheapest plan for a solo dropshipper?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('$8/month Basic includes 1 connected page and 100 AI responses — enough for a beginner dropshipper testing a product. $29/month Starter covers 3 pages and 500 AI responses for a scaling brand.')) }}" }
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
                        {{ __('Dropshipping') }}
                    </span>
                    <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                        {!! __('Turn Every <span class="text-indigo-400">Instagram Ad Click</span> Into a WhatsApp Sale — Automatically') !!}
                    </h1>
                    <p class="mt-5 text-lg text-zinc-700">
                        {{ __('Egyptian and MENA dropshippers know the drill: run Facebook and Instagram ads → get flooded with "بكام؟" and "الشحن كام؟" messages → lose half the leads because your team is asleep. OT1-Pro\'s AI handles pricing, shipping, and COD confirmation 24/7 in your customer\'s dialect — so overnight ad traffic turns into next-day deliveries.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-indigo-700">
                            {{ __('Start Free') }}
                        </a>
                        <a href="{{ route('pricing') }}" class="rounded-xl border border-zinc-300 px-6 py-3 font-semibold text-zinc-700 transition-colors hover:border-zinc-400 hover:text-indigo-700">
                            {{ __('See Pricing') }}
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-zinc-500">{{ __('From $8/month · Free plan available · Founder on WhatsApp +20 102 636 1218') }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                    @php
                    $metrics = [
                        [__('Overnight leads captured'), '+65%', __('vs manual reply team')],
                        [__('First-response time'), '< 30s', __('AI-handled')],
                        [__('COD confirmation rate'), '3.2x', __('vs unanswered WhatsApp')],
                        [__('AI cost per response'), '~$0.02', __('vs $2+ per manual reply')],
                    ];
                    @endphp
                    <p class="mb-4 text-sm font-semibold text-zinc-600">{{ __('Real results for MENA dropshippers') }}</p>
                    @foreach($metrics as [$label, $value, $sub])
                    <div class="mb-4 rounded-lg bg-zinc-100 px-4 py-3">
                        <p class="text-xs text-zinc-500">{{ $label }}</p>
                        <p class="text-2xl font-bold text-indigo-400">{{ $value }}</p>
                        <p class="text-xs text-zinc-500">{{ $sub }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- The Dropshipper Problem --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('The dropshipper problem, honestly') }}</h2>
            <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Your ad budget produces traffic. Most of that traffic messages you at night — because that is when Egyptians scroll Instagram. Here is what actually happens:') }}</p>
            <ul class="mt-8 space-y-4 text-zinc-700 dark:text-zinc-700">
                <li class="flex gap-3"><span class="text-red-500">✗</span>{{ __('You spend 3,000 EGP on a Facebook ad campaign in one evening.') }}</li>
                <li class="flex gap-3"><span class="text-red-500">✗</span>{{ __('That produces 180 DMs and WhatsApp messages between 8pm and 1am.') }}</li>
                <li class="flex gap-3"><span class="text-red-500">✗</span>{{ __('Your team (or just you) replies to the first 30, then goes to sleep.') }}</li>
                <li class="flex gap-3"><span class="text-red-500">✗</span>{{ __('The other 150 leads see no reply until 10am the next day.') }}</li>
                <li class="flex gap-3"><span class="text-red-500">✗</span>{{ __('By morning, 90+ have gone cold or bought from a competitor who replied faster.') }}</li>
                <li class="flex gap-3"><span class="text-red-500">✗</span>{{ __('Your effective ROAS is half what your ad manager reports — because half your leads were never contacted.') }}</li>
            </ul>
            <p class="mt-8 rounded-xl bg-indigo-50 p-6 text-zinc-800 dark:bg-indigo-50/50 dark:text-zinc-800">{!! __('<strong>The uncomfortable truth:</strong> Most dropshippers don\'t have an ad problem. They have a reply-time problem that makes their ads look worse than they are.') !!}</p>
        </div>
    </section>

    {{-- What OT1-Pro Does For Dropshippers --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('What OT1-Pro does for dropshippers') }}</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Built for solo operators and 2–5 person teams running FB/IG ads on tight margins.') }}</p>
            </div>
            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    [
                        '⚡',
                        __('Instant reply to pricing questions'),
                        __('"بكام؟", "How much?", "الشحن للاسكندرية كام؟" — the AI answers in the customer\'s dialect within 30 seconds, using your price list and shipping zones as its knowledge base.'),
                    ],
                    [
                        '📦',
                        __('COD order capture in-chat'),
                        __('The AI walks the customer through name, phone, address, and preferred delivery window — then hands your fulfilment team a clean order summary. No lost details, no missed digits in phone numbers.'),
                    ],
                    [
                        '🌙',
                        __('Night-shift AI, day-shift human'),
                        __('Between 10pm and 9am, AI handles everything. During business hours, AI handles the easy 60-70% and escalates complex cases to you. You get 24-hour coverage without hiring night staff.'),
                    ],
                    [
                        '💬',
                        __('Comment-to-DM automation'),
                        __('Customers commenting "سعر؟" on your ad get an automatic DM with the price and product link — capturing leads who would never message you directly.'),
                    ],
                    [
                        '🚚',
                        __('Shipment status auto-updates'),
                        __('Webhooks from Bosta, Aramex, ShipBlu, and other MENA carriers trigger WhatsApp updates to the customer — "طلبك في الطريق"—reducing "where is my order?" support load by 70%.'),
                    ],
                    [
                        '💰',
                        __('$8/month entry pricing'),
                        __('Basic tier covers 1 page and 100 AI responses — enough to test a new product without commitment. Scale to Starter ($29) or Pro ($79) when you validate demand.'),
                    ],
                ];
                @endphp
                @foreach($features as $f)
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-200 dark:bg-white">
                    <div class="mb-3 text-3xl">{{ $f[0] }}</div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-900">{{ $f[1] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ $f[2] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The Playbook --}}
    <section class="border-y border-zinc-200 bg-zinc-50 py-20 dark:border-zinc-200 dark:bg-white">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('The 4-step dropshipper setup') }}</h2>
            <p class="mt-4 text-zinc-600 dark:text-zinc-600">{{ __('Most solo dropshippers are live with OT1-Pro in under an hour.') }}</p>
            <ol class="mt-8 space-y-6">
                @php
                $steps = [
                    [__('1. Connect WhatsApp Business + Instagram + Facebook Page'), __('Use OT1-Pro\'s guided onboarding. If your Meta account is straightforward, this takes 15 minutes.')],
                    [__('2. Paste in your product prices + shipping zones'), __('One-time setup. The AI uses this as its knowledge base. You can update it any time from Settings → AI Prompt.')],
                    [__('3. Set your AI tone: Egyptian dialect, casual'), __('Match how your customers actually talk. The AI will reply in the same register — never formal Arabic that feels like a government office.')],
                    [__('4. Enable comment-to-DM automation on your ad posts'), __('One toggle. Every "بكام؟" comment on your Facebook or Instagram ad becomes a private DM with the price and product link — no cold leads left in public comments.')],
                ];
                @endphp
                @foreach($steps as $step)
                <li class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-200 dark:bg-white">
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-900">{{ $step[0] }}</h3>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ $step[1] }}</p>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20">
        <div class="mx-auto max-w-3xl px-6">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Dropshipper FAQ') }}</h2>
            </div>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    [
                        __('Does OT1-Pro handle cash-on-delivery (COD) confirmations automatically?'),
                        __('Yes. The AI can confirm the order details, ask for the customer\'s address and preferred delivery window, and hand off a clean order summary to your fulfilment team — all inside the WhatsApp chat. No more missed digits in phone numbers or wrong-district deliveries.'),
                    ],
                    [
                        __('Can the AI reply in Egyptian dialect?'),
                        __('Yes. OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian and Gulf Arabic dialects natively — including code-switching ("عايز الأبيض medium please"), slang, and casual dialect. You configure the tone once and the AI matches your brand voice.'),
                    ],
                    [
                        __('Does OT1-Pro connect to Bosta, Aramex, or ShipBlu for shipment tracking?'),
                        __('Yes. Shipment tracking events can be pulled via webhook from Bosta, Aramex, ShipBlu, and other MENA logistics providers, then pushed as WhatsApp updates ("طلبك خرج للتوصيل") to the customer automatically. Reduces "where is my order?" support load significantly.'),
                    ],
                    [
                        __('What is the cheapest plan for a solo dropshipper?'),
                        __('$8/month Basic includes 1 connected page and 100 AI responses — enough for a beginner dropshipper testing a product. $29/month Starter covers 3 pages and 500 AI responses for a scaling brand. Free plan is also available with 20 AI responses/month, no credit card required.'),
                    ],
                    [
                        __('Will the AI accidentally give the wrong price?'),
                        __('The AI only knows the prices you provide in the product list. It cannot invent prices. If you update your pricing, update the knowledge base and the AI uses the new prices immediately. Every AI reply is also logged so you can review and correct if needed.'),
                    ],
                    [
                        __('What if a customer wants to negotiate?'),
                        __('You can configure the AI to hand off to you for any message that mentions "خصم", "discount", or contains price negotiation language. You handle the negotiation personally; the AI handles everything else.'),
                    ],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-200 dark:bg-white" x-data>
                    <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between px-6 py-4 text-left cursor-pointer">
                        <span class="font-medium">{{ $faq[0] }}</span>
                        <svg class="size-5 flex-shrink-0 text-zinc-600 transition-transform" :class="open === {{ $i }} && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse>
                        <div class="px-6 pb-4 text-sm text-zinc-600 dark:text-zinc-600">{{ $faq[1] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-10 text-center sm:p-16">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('Stop losing overnight ad clicks') }}</h2>
                <p class="mx-auto mt-5 max-w-xl text-lg text-zinc-600">{{ __('Set up in under an hour. See it capture leads while you sleep, from the very first evening.') }}</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow-md">
                    {{ __('Start Free with OT1-Pro') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
                <p class="mt-3 text-sm text-indigo-800">{{ __('$8/month after free tier · No credit card required · Founder on WhatsApp') }}</p>
            </div>
        </div>
    </section>

</x-layouts.marketing>
