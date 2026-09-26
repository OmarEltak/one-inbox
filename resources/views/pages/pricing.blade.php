{{--
    Route: /pricing → name('pricing')

    Phase-2 brand pricing. Ported from the "PRICING" section of
    homepage-redesign-preview.html: volume slider + 3 plans (Free /
    Starter / Pro) on ink surface. Preserves Product + FAQPage
    JSON-LD (Google's Rich Result eligibility depends on it).
--}}
<x-layouts.brand-marketing
    :title="__('OT1-Pro Pricing — Free social CRM, plans from $0/mo')"
    :description="__('Start free with OT1-Pro. Affordable social CRM pricing for businesses of all sizes — Free, Starter $29/mo, Pro $79/mo, and Enterprise custom plans.')">

@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Product",
    "name": "OT1-Pro",
    "description": "Unified social inbox with AI sales responder. Manage WhatsApp, Instagram, Facebook, and Telegram from one place.",
    "brand": {"@@type": "Brand", "name": "OT1-Pro"},
    "offers": [
        {"@@type": "Offer", "name": "Free",    "price": "0",  "priceCurrency": "USD", "url": "{{ route('register') }}", "availability": "https://schema.org/InStock"},
        {"@@type": "Offer", "name": "Starter", "price": "29", "priceCurrency": "USD", "url": "{{ route('register') }}", "availability": "https://schema.org/InStock"},
        {"@@type": "Offer", "name": "Pro",     "price": "79", "priceCurrency": "USD", "url": "{{ route('register') }}", "availability": "https://schema.org/InStock"}
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {"@@type": "Question", "name": "Is there really a free plan?",
         "acceptedAnswer": {"@@type": "Answer", "text": "Yes. The Free plan includes 1 connected page and 100 AI responses per month with no credit card required."}},
        {"@@type": "Question", "name": "Can I connect Facebook, Instagram, WhatsApp and Telegram at the same time?",
         "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Each connected page or account counts toward your page limit, regardless of platform."}},
        {"@@type": "Question", "name": "Can I upgrade or downgrade anytime?",
         "acceptedAnswer": {"@@type": "Answer", "text": "Yes. Contact us on WhatsApp and we will switch your plan immediately with prorated billing."}},
        {"@@type": "Question", "name": "Do I have to add a credit card?",
         "acceptedAnswer": {"@@type": "Answer", "text": "No. Try any plan free for 14 days. When you decide to keep it we send bank transfer details."}}
    ]
}
</script>
@endpush

{{-- ═══════ HERO ═══════ --}}
<section class="bg-ink text-cream pt-32 pb-16 grain relative">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <div class="text-xs uppercase tracking-[0.2em] text-emer-400 mb-6">{{ __('Pricing') }}</div>
        <h1 class="serif text-5xl lg:text-7xl leading-[1.02] mb-6">
            {{ __("Pay in cash, once it's working.") }}<br>
            <span class="serif-it text-emer-400">{{ __('Not before.') }}</span>
        </h1>
        <p class="text-cream/70 text-lg leading-relaxed max-w-xl mx-auto">
            {{ __("Try any plan free for 14 days. No card needed. When you decide to keep it, we send bank details and you pay by transfer. That's it. No auto-renew traps.") }}
        </p>
    </div>
</section>

{{-- ═══════ VOLUME SLIDER + PLANS ═══════ --}}
<section id="plans" class="bg-ink text-cream pb-24 grain relative">
    <div class="max-w-7xl mx-auto px-6 relative z-10">

        <div class="mb-10 bg-ink2 rounded-2xl p-6 border border-cream/10 fade-up">
            <div class="flex items-center justify-between mb-3">
                <label for="volume-slider" class="text-sm text-cream/80">{{ __('How many messages does your business get per month?') }}</label>
                <span class="text-2xl font-semibold text-emer-400"><span id="slider-val">1,200</span> {{ __('msgs') }}</span>
            </div>
            <input id="volume-slider" type="range" min="0" max="10" value="3" class="w-full accent-emer-500">
            <div class="flex justify-between text-[10px] text-cream/60 mt-1">
                <span>~100</span><span>500</span><span>1k</span><span>2.5k</span><span>5k</span><span>10k+</span>
            </div>
            <div class="mt-4 text-sm text-cream/80">{{ __('Recommended:') }} <span id="rec-plan" class="text-emer-400 font-semibold">Starter</span> — <span id="rec-reason">{{ __('plenty of headroom for a growing shop') }}</span></div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="plan bg-ink2 border border-cream/10 rounded-2xl p-8" data-plan="free">
                <div class="serif text-3xl mb-1">{{ __('Free') }}</div>
                <div class="text-cream/60 text-sm mb-6">{{ __('Try the flow, no strings') }}</div>
                <div class="serif text-5xl mb-6">$0</div>
                <ul class="space-y-2.5 text-sm text-cream/80 mb-8">
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('1 platform, 100 AI replies / month') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('1 team seat') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Meet-Your-AI wizard') }}</li>
                    <li class="flex items-center gap-2 text-cream/50"><span>—</span> {{ __('Advanced routing') }}</li>
                </ul>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block w-full text-center border border-cream/20 hover:bg-cream/5 rounded-full py-3 text-sm font-semibold transition text-cream">{{ __('Start free') }}</a>
                @endif
            </div>

            <div class="plan bg-cream text-ink rounded-2xl p-8 relative shadow-xl transform scale-105" data-plan="starter">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-emer-500 text-ink text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full">{{ __('Most shops start here') }}</div>
                <div class="serif text-3xl mb-1">Starter</div>
                <div class="text-ink/70 text-sm mb-6">{{ __('For growing DMs') }}</div>
                <div class="serif text-5xl mb-1">$29<span class="text-lg text-ink/50">/mo</span></div>
                <div class="text-ink/60 text-xs mb-6">{{ __('or $290/yr — 2 months free') }}</div>
                <ul class="space-y-2.5 text-sm text-ink/80 mb-8">
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('3 platforms, 2,500 AI replies / mo') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('3 team seats') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('Custom AI voice tuning') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-600">✓</span> {{ __('Founder email support') }}</li>
                </ul>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block w-full text-center bg-ink text-cream hover:bg-ink2 rounded-full py-3 text-sm font-semibold transition">{{ __('Start 14-day trial') }}</a>
                @endif
            </div>

            <div class="plan bg-ink2 border border-cream/10 rounded-2xl p-8" data-plan="pro">
                <div class="serif text-3xl mb-1">Pro</div>
                <div class="text-cream/60 text-sm mb-6">{{ __('Scale without hiring') }}</div>
                <div class="serif text-5xl mb-1">$79<span class="text-lg text-cream/50">/mo</span></div>
                <div class="text-cream/60 text-xs mb-6">{{ __('or $790/yr — 2 months free') }}</div>
                <ul class="space-y-2.5 text-sm text-cream/80 mb-8">
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('All platforms, 10,000 AI replies / mo') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Unlimited team seats') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Custom AI + workflows') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emer-400">✓</span> {{ __('Priority support < 4hr') }}</li>
                </ul>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="block w-full text-center border border-cream/20 hover:bg-cream/5 rounded-full py-3 text-sm font-semibold transition text-cream">{{ __('Start 14-day trial') }}</a>
                @endif
            </div>
        </div>
        <p class="mt-8 text-xs text-cream/60 text-center">{{ __('Custom volume, on-prem, or a franchise?') }} <a href="{{ route('contact') }}" class="underline hover:text-emer-400">{{ __('Email Omar') }}</a> — {{ __('he handles enterprise personally.') }}</p>
    </div>
</section>

{{-- ═══════ FAQ ═══════ --}}
<section class="py-24 fade-up">
    <div class="max-w-3xl mx-auto px-6">
        <div class="mb-10 text-center">
            <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('Common questions') }}</div>
            <h2 class="serif text-4xl lg:text-5xl">{{ __('Pricing questions,') }} <span class="serif-it text-emer-700">{{ __('answered.') }}</span></h2>
        </div>
        <div class="divide-y divide-line border-y border-line">
            @foreach([
                [__('Is there really a free plan?'),                     __("Yes. Free means 1 platform + 100 AI replies / month, no card required. If you outgrow it you can upgrade in one click.")],
                [__('Do I have to add a credit card?'),                  __("No. Try any plan free for 14 days. If you decide to keep it we send bank transfer details. You pay by wire. No auto-renew, no surprise charge.")],
                [__('Can I upgrade or downgrade anytime?'),              __("Yes. WhatsApp the founder and we switch your plan instantly with prorated billing.")],
                [__('What if I need more than 10,000 replies / month?'), __("Custom volume, franchise, or on-prem? Email omareltak7@gmail.com — Omar handles enterprise personally.")],
            ] as [$q, $a])
                <details class="py-5">
                    <summary class="flex justify-between items-center">
                        <span class="serif text-xl">{{ $q }}</span>
                        <span class="chev serif text-2xl text-emer-700">+</span>
                    </summary>
                    <p class="mt-3 text-ink/70 text-sm leading-relaxed">{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════ FINAL CTA ═══════ --}}
<section class="bg-ink text-cream py-24 grain relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">{{ __('Ninety seconds to') }} <span class="serif-it text-emer-400">{{ __('go live.') }}</span></h2>
        <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">{{ __('No card. No auto-renew. Pay by transfer only when it\'s working.') }}</p>
        @if(Route::has('register'))
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">{{ __('Start free — no card') }} <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>
        @endif
    </div>
</section>

@push('scripts')
<script>
(function () {
    var slider = document.getElementById('volume-slider');
    if (!slider) return;
    var buckets = [
        { msgs: 100,   plan: 'Free',    reason: 'small volume, casual DMs — the free tier fits' },
        { msgs: 300,   plan: 'Free',    reason: 'still within the free 100/mo cap x 3 platforms' },
        { msgs: 500,   plan: 'Starter', reason: 'extra headroom + custom voice tuning' },
        { msgs: 1200,  plan: 'Starter', reason: 'plenty of headroom for a growing shop' },
        { msgs: 1800,  plan: 'Starter', reason: 'still inside the 2.5k Starter cap' },
        { msgs: 2500,  plan: 'Starter', reason: 'right at the Starter ceiling — check Pro too' },
        { msgs: 3500,  plan: 'Pro',     reason: "over Starter's cap — Pro unlocks 10k/mo + all platforms" },
        { msgs: 5000,  plan: 'Pro',     reason: 'Pro is the sweet spot for your volume' },
        { msgs: 7000,  plan: 'Pro',     reason: "still inside Pro's 10k cap" },
        { msgs: 9000,  plan: 'Pro',     reason: "approaching Pro's ceiling — plan ahead" },
        { msgs: 12000, plan: 'Custom',  reason: 'email Omar for a custom volume plan' },
    ];
    function updateSlider() {
        var i = parseInt(slider.value, 10);
        var b = buckets[i];
        document.getElementById('slider-val').textContent = b.msgs.toLocaleString();
        document.getElementById('rec-plan').textContent = b.plan;
        document.getElementById('rec-reason').textContent = b.reason;
        document.querySelectorAll('.plan').forEach(function (p) {
            p.classList.remove('ring-2', 'ring-emer-400');
            if (p.dataset.plan.toLowerCase() === b.plan.toLowerCase()) {
                p.classList.add('ring-2', 'ring-emer-400');
            }
        });
    }
    slider.addEventListener('input', updateSlider);
    updateSlider();
})();
</script>
@endpush

</x-layouts.brand-marketing>
