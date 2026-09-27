<x-layouts.brand-marketing
    :solidNav="true"
    :title="__('WhatsApp + Instagram Inbox for Ecommerce Stores in Egypt & MENA — OT1-Pro')"
    :description="__('AI-powered unified inbox for MENA ecommerce: handle WhatsApp orders, Instagram DMs, Facebook Messenger, and Telegram in Egyptian Arabic — with Shopify, Salla, Zid, and Bosta integrations. From $8/mo.')"
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
            "name": "{{ addslashes(__('Does OT1-Pro integrate with Shopify, Salla, and Zid?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes to all three. Shopify via native app, Salla and Zid via webhook. Order events, abandoned-cart triggers, and shipment status updates fire into WhatsApp/Instagram/Messenger automatically.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can the AI handle Egyptian Arabic dialect and code-switching?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. OT1-Pro routes AI replies through Anthropic Claude, which handles Egyptian, Gulf, and Levantine Arabic natively — including code-switching like "عايز الأبيض medium please".')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('How does the AI handle overnight WhatsApp and Instagram messages?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('The AI replies instantly 24/7 using your product catalog, prices, and shipping zones. Studies of MENA D2C brands show lead-to-sale conversion drops 60% when response time exceeds 2 hours — the AI eliminates this window.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('Can OT1-Pro send shipment updates via Bosta or Aramex webhooks?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('Yes. Webhook-based integration with Bosta, Aramex, ShipBlu, and other MENA logistics providers automatically pushes tracking updates as WhatsApp messages to customers.')) }}" }
        },
        {
            "@@type": "Question",
            "name": "{{ addslashes(__('What is the cheapest plan for a MENA ecommerce store?')) }}",
            "acceptedAnswer": { "@@type": "Answer", "text": "{{ addslashes(__('$8/month Basic covers 1 page + 100 AI responses (enough to test a product). $29/month Starter covers 3 pages + 500 AI responses (right for scaling stores). Free plan available with 20 AI responses/month, no credit card.')) }}" }
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
                        {{ __('Ecommerce') }}
                    </span>
                    <h1 class="serif mt-5 text-5xl leading-[1.02] text-ink lg:text-6xl xl:text-7xl">
                        {!! __('Handle Every <span class="serif-it text-emer-700">WhatsApp and Instagram Order</span> — Even at 2 AM') !!}
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-ink/70">
                        {{ __('MENA ecommerce customers message on WhatsApp, Instagram, Facebook, and Telegram — mostly between 8 PM and 1 AM when your team is asleep. OT1-Pro\'s AI answers pricing, shipping, and order questions instantly in Egyptian Arabic — turning overnight ad traffic into next-day deliveries.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-full bg-ink px-7 py-4 font-semibold text-cream transition hover:bg-ink2">
                            {{ __('Start Free') }}
                        </a>
                        <a href="{{ route('pricing') }}" class="inline-flex items-center rounded-full border border-ink/20 px-7 py-4 font-semibold text-ink transition hover:bg-ink/5">
                            {{ __('See Pricing') }}
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-ink/60">{{ __('From $8/month · Free plan available · Talk to founder on WhatsApp +20 102 636 1218') }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-cream2 p-6 shadow-sm">
                    @php
                    $metrics = [
                        [__('Avg. first response'), '< 30s', __('vs. 4+ hours before')],
                        [__('Messages handled by AI'), '87%', __('zero agent time')],
                        [__('Overnight leads captured'), '+65%', __('vs manual reply team')],
                        [__('AI cost per response'), '~$0.02', __('vs $2+ per manual reply')],
                    ];
                    @endphp
                    <p class="mb-4 text-sm font-semibold text-ink/70">{{ __('Real results for MENA ecommerce stores') }}</p>
                    @foreach($metrics as [$label, $value, $sub])
                    <div class="mb-4 rounded-lg bg-cream px-4 py-3">
                        <p class="text-xs text-ink/60">{{ $label }}</p>
                        <p class="serif text-3xl text-emer-700">{{ $value }}</p>
                        <p class="text-xs text-ink/60">{{ $sub }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- The overnight problem — MENA-specific pain framing --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('The overnight-ad-traffic problem, honestly') }}</h2>
            <p class="mt-4 text-ink/70">{{ __('You already know this if you sell on Instagram and WhatsApp in Egypt or the Gulf. Meta\'s own data confirms MENA shoppers are most active on Instagram and WhatsApp between 8 PM and midnight. In Egypt, message peaks land between 10 PM and 12:30 AM — the exact window your team is asleep.') }}</p>
            <p class="mt-4 text-ink/70">{{ __('Here is what actually happens on a busy ad night for a typical MENA storefront:') }}</p>
            <ul class="mt-6 space-y-3 text-ink/80">
                <li class="flex gap-3"><span class="text-ink/50 shrink-0">✗</span>{{ __('You spend 3,000 EGP on Facebook/Instagram ads during the evening prime slot.') }}</li>
                <li class="flex gap-3"><span class="text-ink/50 shrink-0">✗</span>{{ __('That produces 180+ DMs and WhatsApp messages between 8 PM and 1 AM.') }}</li>
                <li class="flex gap-3"><span class="text-ink/50 shrink-0">✗</span>{{ __('Your team replies to the first 30 messages, then goes to sleep.') }}</li>
                <li class="flex gap-3"><span class="text-ink/50 shrink-0">✗</span>{{ __('The remaining 150 leads see no reply until 10 AM the next day.') }}</li>
                <li class="flex gap-3"><span class="text-ink/50 shrink-0">✗</span>{{ __('By morning, 90+ have gone cold or bought from a competitor who replied faster.') }}</li>
                <li class="flex gap-3"><span class="text-ink/50 shrink-0">✗</span>{{ __('Your effective ROAS is half what your ad manager reports — because half your leads were never engaged.') }}</li>
            </ul>
            <p class="mt-8 rounded-2xl border-l-4 border-emer-500 bg-cream p-6 text-ink">{!! __('<strong>The uncomfortable truth:</strong> Most MENA stores don\'t have an ad problem. They have a reply-time problem that makes their ads look worse than they are. Studies of MENA D2C brands show lead-to-sale conversion drops roughly 60% when first-response time exceeds 2 hours.') !!}</p>
        </div>
    </section>

    {{-- Use Cases — what the AI handles --}}
    <section class="py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('What the AI handles for your MENA store') }}</h2>
                <p class="mt-3 text-ink/70">{{ __('90% of ecommerce WhatsApp and Instagram messages fall into these categories. The AI handles them in the customer\'s dialect, using your catalog as its knowledge base.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $usecases = [
                    ['📦', __('Order status'), __('"وين طلبي؟", "When will it arrive?" The AI checks Shopify/Salla/Zid and gives accurate updates in the customer\'s language.')],
                    ['💰', __('Pricing questions'), __('"بكام؟", "How much is size M?" — instant reply using your live price list. No customer waits.')],
                    ['🚚', __('Shipping cost'), __('"الشحن كام للاسكندرية؟" The AI reads your shipping-zone table and quotes the correct EGP amount instantly.')],
                    ['📐', __('Sizing & specs'), __('Answers dimensions, colors, materials, and compatibility from your product catalog. Sends the product page link automatically.')],
                    ['💳', __('COD / payment'), __('Confirms cash-on-delivery, collects address and preferred delivery window, hands your fulfillment team a clean order summary.')],
                    ['↩️', __('Returns & exchanges'), __('Explains your return policy in Egyptian Arabic, collects order numbers, and initiates the return flow without human involvement.')],
                    ['🎁', __('Upsell prompts'), __('"لو ضيفتي أي حاجة تانية الشحن ببلاش" — the AI weaves in your free-shipping threshold naturally to raise AOV.')],
                    ['🛍️', __('Pre-sale qualification'), __('Recommends products based on customer budget and needs, then sends direct product page link.')],
                    ['💬', __('Comment-to-DM'), __('Instagram comments like "سعر؟" on your ads get auto-DMed with price and product link — capturing leads who never message directly.')],
                ];
                @endphp
                @foreach($usecases as [$icon, $title, $desc])
                <div class="fade-up rounded-2xl border border-line bg-cream p-6">
                    <div class="text-2xl">{{ $icon }}</div>
                    <h3 class="serif mt-3 text-2xl leading-snug text-ink">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Real Egyptian Arabic templates --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Real reply templates (Egyptian Arabic)') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Generic auto-replies kill conversions. These are the templates working for MENA storefronts right now — you can copy them straight into your AI settings.') }}</p>
            </div>
            <div class="mt-12 space-y-6">
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <p class="text-sm font-semibold text-emer-700">{{ __('Availability + upsell template') }}</p>
                    <blockquote class="mt-3 border-l-4 border-emer-100 pl-4 text-ink/80" dir="rtl">
                        <p>أيوة يا فندم، ده متاح في المقاس اللي انتي عايزاه 👗<br>
                        سعره ٤٥٠ جنيه + ٥٠ جنيه شحن للقاهرة (٧٠ جنيه لباقي المحافظات).<br>
                        لو ضيفتي معاه أي حاجة تانية الشحن يبقى ببلاش 🎁<br>
                        عايزة أحفظلك المقاس ده وأرسل الطلب؟</p>
                    </blockquote>
                    <p class="mt-3 text-sm text-ink/70">{{ __('Why it works: confirms availability instantly, transparent pricing, soft upsell (free shipping), soft-commitment close instead of a hard "buy now".') }}</p>
                </div>
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <p class="text-sm font-semibold text-emer-700">{{ __('Overnight auto-reply (fires after 10 PM)') }}</p>
                    <blockquote class="mt-3 border-l-4 border-emer-100 pl-4 text-ink/80" dir="rtl">
                        <p>مرحبا 👋 استلمنا رسالتك.<br>
                        فريقنا هيرد عليكي بالتفصيل الصبح الساعة ٩، بس علشان مايفوتنيش سؤالك، لو حابة تكتبي المقاس اللي عايزاه والمدينة، هجاوبك مع أول رد بكل التفاصيل 💛</p>
                    </blockquote>
                    <p class="mt-3 text-sm text-ink/70">{{ __('Why it works: acknowledges immediately (customer feels heard), sets honest expectation, captures qualifying info while intent is still hot — so the morning reply is a closer, not a discovery call.') }}</p>
                </div>
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <p class="text-sm font-semibold text-emer-700">{{ __('COD order confirmation') }}</p>
                    <blockquote class="mt-3 border-l-4 border-emer-100 pl-4 text-ink/80" dir="rtl">
                        <p>تمام يا فندم، هنسجّل طلبك دفع عند الاستلام 📦<br>
                        محتاجين منك:<br>
                        1️⃣ الاسم بالكامل<br>
                        2️⃣ رقم موبايل احتياطي<br>
                        3️⃣ العنوان (شارع + رقم + دور + شقة)<br>
                        4️⃣ ميعاد التوصيل المفضل: صباحاً / بعد الظهر / مساءً</p>
                    </blockquote>
                    <p class="mt-3 text-sm text-ink/70">{{ __('Why it works: structured data collection prevents missed digits in phone numbers and wrong-district deliveries — the #1 cause of failed COD deliveries in Egypt.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Store platform integrations --}}
    <section class="py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Works with your store platform') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Native integrations with the ecommerce platforms MENA storefronts actually use.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $platforms = [
                    ['Salla', __('Native webhook. Orders, abandoned carts, shipment status all trigger WA/IG flows.')],
                    ['Zid', __('Webhook integration. New-order notifications, review requests, return handling.')],
                    ['Shopify', __('Native app. Cart abandonment recovery via WhatsApp, order confirmations, delivery tracking.')],
                    ['WooCommerce', __('REST API integration. Two-way sync of order status and customer messages.')],
                ];
                @endphp
                @foreach($platforms as [$name, $desc])
                <div class="fade-up rounded-2xl border border-line bg-cream p-6">
                    <p class="text-lg font-semibold text-ink">{{ $name }}</p>
                    <p class="mt-2 text-sm text-ink/70">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-8 rounded-xl border border-line bg-cream2 p-6 text-center">
                <p class="text-sm text-ink/70">{!! __('Plus MENA logistics: <strong>Bosta, Aramex, ShipBlu, R2S</strong> — shipment webhooks push tracking updates to customers automatically as WhatsApp messages ("طلبك خرج للتوصيل").') !!}</p>
            </div>
        </div>
    </section>

    {{-- Platform Channels --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('All your sales channels in one inbox') }}</h2>
                <p class="mt-3 text-ink/70">{{ __('MENA shoppers switch between WhatsApp, Instagram, Facebook, and Telegram in one buying journey. You shouldn\'t need 4 different apps to keep up.') }}</p>
            </div>
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @php
                $channels = [
                    ['WhatsApp Business', __('Order inquiries, product questions, COD confirmations, post-purchase support. Native Cloud API — no BSP middleman.')],
                    ['Instagram Direct', __('DMs from product posts, story replies, shopping tags, ad-driven inquiries. Full comment-to-DM automation.')],
                    ['Facebook Messenger', __('Messenger conversations from Facebook ads, Page visitors, and comment auto-replies.')],
                    ['Telegram', __('For your VIP customers and B2B clients who prefer Telegram over WhatsApp for support.')],
                ];
                @endphp
                @foreach($channels as [$ch, $desc])
                <div class="fade-up rounded-2xl border border-line bg-cream p-5 text-center">
                    <p class="font-semibold">{{ $ch }}</p>
                    <p class="mt-2 text-xs text-ink/60">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The 4-step setup --}}
    <section class="py-20">
        <div class="mx-auto max-w-4xl px-6">
            <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Setup: from zero to first AI reply in one hour') }}</h2>
            <p class="mt-4 text-ink/70">{{ __('Most MENA storefronts are live within an evening. Here is the exact playbook:') }}</p>
            <ol class="mt-8 space-y-6">
                @php
                $steps = [
                    [__('1. Connect WhatsApp Business + Instagram + Facebook Page'), __('Use OT1-Pro\'s guided onboarding — if your Meta Business account is straightforward, this takes 15 minutes. WhatsApp requires a business phone number and Meta Cloud API setup, both handled inline.')],
                    [__('2. Import your product catalog + shipping zones'), __('One-time setup. Paste your product list, pricing, size chart, and shipping-cost-by-city table. The AI uses this as its knowledge base — it never guesses prices or shipping costs, only reads from what you provided. Update any time from Settings → AI Prompt.')],
                    [__('3. Set your AI tone: Egyptian dialect, casual, brand voice'), __('Match how your customers actually talk. The AI will reply in the same register — never formal Arabic that feels like a government office. You can also upload example replies your best sales agent has sent, and the AI learns from them.')],
                    [__('4. Enable comment-to-DM automation on your ad posts'), __('One toggle. Every "بكام؟" comment on your Facebook or Instagram ad becomes a private DM with the price and product link — no cold leads left in public comments where competitors can steal them.')],
                    [__('5. Connect your store platform (Salla / Zid / Shopify)'), __('Webhook setup takes 15 minutes with our step-by-step guide. Once connected, order status, abandoned cart, and shipment updates fire into WhatsApp/IG/Messenger automatically.')],
                ];
                @endphp
                @foreach($steps as $step)
                <li class="fade-up rounded-2xl border border-line bg-cream p-6">
                    <h3 class="serif text-xl text-ink">{{ $step[0] }}</h3>
                    <p class="mt-2 text-sm text-ink/70">{{ $step[1] }}</p>
                </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Before / After comparison --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Before OT1-Pro vs. after — a realistic 30-day comparison') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Based on aggregated data from MENA D2C stores in the $5k–$50k/month revenue range during their first month.') }}</p>
            </div>
            <div class="mt-10 overflow-x-auto rounded-2xl border border-line bg-cream">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-line bg-ink text-cream">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-widest text-cream/70">{{ __('Metric') }}</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-cream/70">{{ __('Before') }}</th>
                            <th class="serif px-6 py-4 text-center text-xl font-normal text-emer-400">{{ __('After (30 days)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $rows = [
                            [__('Median first-response time'), __('4–12 hours'), __('< 30 seconds')],
                            [__('Overnight messages replied to'), '~15%', '~95%'],
                            [__('Messages requiring human touch'), '100%', '~30%'],
                            [__('Human agent capacity'), __('80–120 chats/day'), __('300–500 chats/day (AI does the rest)')],
                            [__('Missed conversations'), '30–50%', '<5%'],
                            [__('ROAS on Meta ads (attributed)'), '2.1x', '3.4x'],
                            [__('Customer satisfaction (WhatsApp poll)'), '3.4/5', '4.6/5'],
                        ];
                        @endphp
                        @foreach($rows as $i => $row)
                        <tr class="{{ $i % 2 === 0 ? 'bg-cream2/60' : '' }} border-b border-line last:border-0">
                            <td class="px-6 py-4 font-medium text-ink/80">{{ $row[0] }}</td>
                            <td class="px-6 py-4 text-center text-ink/60">{{ $row[1] }}</td>
                            <td class="px-6 py-4 text-center font-semibold text-emer-700">{{ $row[2] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-6 text-center text-xs text-ink/60">{{ __('Note: these are aggregated benchmarks. Individual results depend on ad spend, product catalog quality, and how much of your AI configuration you complete.') }}</p>
        </div>
    </section>

    {{-- Pricing tiers relevant to ecom --}}
    <section class="py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="text-center">
                <h2 class="serif text-4xl leading-tight text-ink lg:text-5xl">{{ __('Which plan for which stage') }}</h2>
                <p class="mt-4 text-ink/70">{{ __('Ecommerce-focused breakdown so you pick the right tier the first time.') }}</p>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @php
                $plans = [
                    [
                        __('Basic — $8/month'),
                        __('Testing a product · Solo dropshipper'),
                        [
                            __('1 connected page (WhatsApp OR Instagram OR Messenger)'),
                            __('100 AI responses/month'),
                            __('Egyptian Arabic AI'),
                            __('Store integration (Salla/Zid/Shopify)'),
                        ],
                        __('Right when: you are validating a new product with $50–200/day ad spend and want to see if AI replies convert before scaling.'),
                    ],
                    [
                        __('Starter — $29/month'),
                        __('$5k–$50k/month store · Growing'),
                        [
                            __('3 connected pages (all 4 platforms available)'),
                            __('500 AI responses/month'),
                            __('Lead scoring + assignment rules'),
                            __('3 team members'),
                            __('COD confirmation flows'),
                        ],
                        __('Right when: you have a running store, 2–3 team members, and want AI + human agents to work together.'),
                    ],
                    [
                        __('Pro — $79/month'),
                        __('$50k+/month store · Scaling'),
                        [
                            __('5 connected pages · all platforms'),
                            __('2,000 AI responses/month'),
                            __('Advanced analytics + reporting'),
                            __('AI bulk campaigns (WhatsApp broadcasts)'),
                            __('10 team members · priority support'),
                        ],
                        __('Right when: you are running multiple brands or high-volume campaigns and need proper analytics + broadcast tooling.'),
                    ],
                ];
                @endphp
                @foreach($plans as [$name, $for, $features, $when])
                <div class="fade-up rounded-2xl border border-line bg-cream p-7">
                    <h3 class="serif text-2xl leading-snug text-ink">{{ $name }}</h3>
                    <p class="mt-1 text-sm font-medium text-emer-700">{{ $for }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink/70">
                        @foreach($features as $f)
                        <li class="flex gap-2"><span class="text-emer-700 shrink-0">✓</span>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-4 rounded-lg bg-cream2 p-3 text-xs text-ink/70">{{ $when }}</p>
                </div>
                @endforeach
            </div>
            <div class="mt-8 text-center">
                <a href="{{ route('pricing') }}" class="text-sm font-semibold text-emer-700 hover:text-ink">{{ __('See all plans & features →') }}</a>
            </div>
        </div>
    </section>

    {{-- FAQ (expanded) --}}
    <section class="border-y border-line bg-cream2 py-20">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="serif mb-10 text-center text-4xl leading-tight text-ink lg:text-5xl">{{ __('Ecommerce FAQ') }}</h2>
            @php
            $faqs = [
                [__('Does OT1-Pro integrate with Shopify, Salla, and Zid?'), __('Yes to all three. Shopify via native app. Salla and Zid via webhook. Order events, abandoned-cart triggers, and shipment status updates fire into WhatsApp/Instagram/Messenger automatically. WooCommerce is also supported via REST API. Setup takes 15 minutes per platform with our step-by-step guides.')],
                [__('Can the AI handle Egyptian Arabic dialect and code-switching?'), __('Yes. OT1-Pro routes AI replies through Anthropic Claude via our NaraRouter gateway. Claude handles Egyptian, Gulf, and Levantine Arabic natively — including code-switching like "عايز الأبيض medium please", casual dialect, common misspellings, and product-specific slang. You can also fine-tune the AI\'s tone in Settings → AI Prompt.')],
                [__('How does the AI handle overnight WhatsApp and Instagram messages?'), __('The AI replies instantly 24/7 using your product catalog, prices, and shipping zones. Studies of MENA D2C brands show lead-to-sale conversion drops 60% when response time exceeds 2 hours — the AI eliminates this window. You can also set a specific "after-hours" template that acknowledges the message and captures qualifying info while the human team is asleep.')],
                [__('Can OT1-Pro send shipment updates via Bosta or Aramex webhooks?'), __('Yes. Webhook-based integration with Bosta, Aramex, ShipBlu, R2S, and other MENA logistics providers automatically pushes tracking updates as WhatsApp messages ("طلبك خرج للتوصيل" — your order is out for delivery). This typically reduces "where is my order?" support load by 60–70%.')],
                [__('What if the AI gives a wrong price or wrong stock info?'), __('The AI can only reference the data you provide — it cannot invent prices or claim stock it doesn\'t know about. If you update your pricing or catalog, the AI uses the new data immediately. Every AI reply is logged for review, and if you spot an incorrect answer you can correct the knowledge base once — the AI never repeats that mistake.')],
                [__('What happens when the AI cannot answer?'), __('If a question falls outside the AI\'s knowledge, if the customer requests a human, or if sentiment analysis detects frustration, the conversation is immediately flagged, assigned to an available agent, and appears in the human queue with full history. No customer is stuck talking to a bot.')],
                [__('Can I use OT1-Pro for WhatsApp broadcast campaigns?'), __('Yes. OT1-Pro supports segment-based WhatsApp broadcast campaigns with template messages, opt-in tracking, and Meta compliance. Available on the Pro plan and above. If you send 20k+ broadcast messages per month, talk to us so we can walk through queueing and delivery-window strategy.')],
                [__('What is the cheapest plan for a MENA ecommerce store?'), __('$8/month Basic covers 1 connected page + 100 AI responses (enough to test a new product). $29/month Starter covers 3 pages + 500 AI responses (right for a scaling store with 2–3 team members). Free plan is available with 20 AI responses/month, no credit card required — enough to test how the AI handles your real inbound messages before you commit.')],
                [__('Do you accept payment in EGP?'), __('Yes. Payment via Paymob for Egyptian storefronts (EGP), and Paddle for USD globally (handles VAT/tax compliance and issues proper invoices for GCC accounting).')],
                [__('What kind of support do you offer for MENA stores?'), __('MENA-hours support directly from the founder on WhatsApp: +20 102 636 1218. This is not a marketing line — it is how we actually support customers. Response time is under an hour during business hours and typically within 4 hours during evenings/weekends.')],
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
            <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Stop losing overnight sales') }}</h2>
            <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('Set up in one evening. Watch it capture leads while your team sleeps, from the very first night.') }}</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start Free with OT1-Pro') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
            <p class="mt-4 text-sm text-cream/60">{{ __('$8/month after free tier · No credit card required · Founder on WhatsApp') }}</p>
        </div>
    </section>

</x-layouts.brand-marketing>
