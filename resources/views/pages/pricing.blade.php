{{--
    Route: /pricing → name('pricing')

    Concierge partnership pricing (2026-11 pivot). We are not self-serve during
    the Founding Partner cohort. Every CTA routes to WhatsApp +201026361218.
    Multi-currency: USD / EGP / SAR, toggled via Alpine.js.
--}}
<x-layouts.brand-marketing
    :title="__('OT1-Pro Pricing — Concierge AI Partnership (EGP, SAR, USD)')"
    :description="__('OT1-Pro is a concierge AI partnership for small businesses. AI Pilot from 3,500 EGP / 499 SAR / $149. Founding Partner from 7,500 EGP setup + 2,500 EGP/month. White-Glove quoted per scope.')">

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'Product',
    'name'     => 'OT1-Pro',
    'description' => 'Concierge AI partnership for small businesses that sell through social-media DMs. Custom AI built with the owner in a 90-min discovery call, co-admin monitored for 30 days.',
    'brand' => ['@type' => 'Brand', 'name' => 'OT1-Pro'],
    'offers' => [
        ['@type' => 'Offer', 'name' => 'AI Pilot (2-week trial)',    'price' => '149',  'priceCurrency' => 'USD', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
        ['@type' => 'Offer', 'name' => 'AI Pilot (Egypt)',            'price' => '3500', 'priceCurrency' => 'EGP', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
        ['@type' => 'Offer', 'name' => 'AI Pilot (Saudi Arabia)',     'price' => '499',  'priceCurrency' => 'SAR', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
        ['@type' => 'Offer', 'name' => 'Founding Partner (setup)',    'price' => '499',  'priceCurrency' => 'USD', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
        ['@type' => 'Offer', 'name' => 'Founding Partner EG (setup)', 'price' => '7500', 'priceCurrency' => 'EGP', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
        ['@type' => 'Offer', 'name' => 'Founding Partner SA (setup)', 'price' => '1499', 'priceCurrency' => 'SAR', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
        ['@type' => 'Offer', 'name' => 'White-Glove (from)',          'price' => '1499', 'priceCurrency' => 'USD', 'url' => 'https://wa.me/201026361218', 'availability' => 'https://schema.org/InStock'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'Is there a self-serve signup button?',
         'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Not during the Founding Partner cohort. Every AI is hand-built with the owner in a 90-min discovery call. Message us on WhatsApp +201026361218 to begin.']],
        ['@type' => 'Question', 'name' => 'How do I pay?',
         'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Egypt: Instapay or bank transfer. Saudi: Mada or bank transfer. UAE: bank transfer or Wise. International: Wise or Payoneer. No cards on site. Setup fee paid before onboarding begins.']],
        ['@type' => 'Question', 'name' => 'Is there a free trial?',
         'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No free trial, but the AI Pilot is a 2-week trial at 3,500 EGP / 499 SAR / $149 with a full refund guarantee if the AI does not handle 50+ real conversations correctly.']],
        ['@type' => 'Question', 'name' => 'What if I want to cancel?',
         'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Message us on WhatsApp. We remove our admin role from your Page the same day. No minimum term, no clawback, no annual contracts.']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

{{-- ═══════ HERO ═══════ --}}
<section class="bg-ink text-cream pt-32 pb-16 grain relative">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <div class="text-xs uppercase tracking-[0.2em] text-emer-400 mb-6">{{ __('Partnership models') }}</div>
        <h1 class="serif text-5xl lg:text-7xl leading-[1.02] mb-6">
            {{ __('We build it with you.') }}<br>
            <span class="serif-it text-emer-400">{{ __('Not a signup form.') }}</span>
        </h1>
        <p class="text-cream/70 text-lg leading-relaxed max-w-xl mx-auto">
            {{ __("We're a concierge AI partnership — not self-serve during the Founding Partner cohort. Each AI is hand-built with the owner in a 90-minute discovery call, then we stay as co-admin on your Page for 30 days to tune it weekly.") }}
        </p>
    </div>
</section>

{{-- ═══════ CURRENCY SWITCHER + PLANS ═══════ --}}
<section id="plans" class="bg-ink text-cream pb-24 grain relative" x-data="{ currency: 'USD' }">
    <div class="max-w-7xl mx-auto px-6 relative z-10">

        {{-- Currency toggle --}}
        <div class="flex justify-center mb-10 fade-up">
            <div class="inline-flex items-center rounded-full bg-ink2 p-1 border border-cream/15">
                @foreach (['USD' => '🌍 USD', 'EGP' => '🇪🇬 EGP', 'SAR' => '🇸🇦 SAR'] as $code => $label)
                    <button type="button" @click="currency = '{{ $code }}'"
                        :class="currency === '{{ $code }}' ? 'bg-emer-500 text-ink shadow' : 'text-cream/70 hover:text-cream'"
                        class="px-5 py-2 text-xs font-semibold rounded-full transition">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Volume slider — helps the visitor self-select tier --}}
        <div class="mb-10 bg-ink2 rounded-2xl p-6 border border-cream/10 fade-up">
            <div class="flex items-center justify-between mb-3">
                <label for="volume-slider" class="text-sm text-cream/80">{{ __('About how many DMs or WhatsApp messages do you get per day?') }}</label>
                <span class="text-2xl font-semibold text-emer-400"><span id="slider-val">50</span> {{ __('msgs/day') }}</span>
            </div>
            <input id="volume-slider" type="range" min="0" max="10" value="3" class="w-full accent-emer-500">
            <div class="flex justify-between text-[10px] text-cream/60 mt-1">
                <span>5</span><span>20</span><span>50</span><span>100</span><span>200</span><span>500+</span>
            </div>
            <div class="mt-4 text-sm text-cream/80">{{ __('Recommended:') }} <span id="rec-plan" class="text-emer-400 font-semibold">Founding Partner</span> — <span id="rec-reason">{{ __('right in the Partner sweet spot') }}</span></div>
        </div>

        {{-- 3 concierge tiers --}}
        <div class="grid md:grid-cols-3 gap-6">

            {{-- AI Pilot --}}
            <div class="plan bg-ink2 border border-cream/10 rounded-2xl p-8" data-plan="pilot">
                <div class="text-[10px] uppercase tracking-widest text-cream/50 mb-1">{{ __('14-day trial') }}</div>
                <div class="serif text-3xl mb-1">{{ __('AI Pilot') }}</div>
                <div class="text-cream/60 text-sm mb-6">{{ __('Prove the AI works in your voice before committing.') }}</div>

                <div x-show="currency === 'USD'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">$149</div>
                    <div class="text-cream/60 text-xs mb-6">{{ __('one-time, 14 days') }}</div>
                </div>
                <div x-show="currency === 'EGP'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">3,500 <span class="text-2xl">ج</span></div>
                    <div class="text-cream/60 text-xs mb-6">{{ __('one-time, 14 days') }}</div>
                </div>
                <div x-show="currency === 'SAR'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">499 <span class="text-2xl">ر.س</span></div>
                    <div class="text-cream/60 text-xs mb-6">{{ __('one-time, 14 days') }}</div>
                </div>

                <ul class="space-y-2.5 text-sm text-cream/80 mb-8">
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('1 page, 1 platform') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('90-min setup call with Omar') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('14 days monitored daily') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Daily reply tuning') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Full refund if < 50 real conversations handled') }}</span></li>
                </ul>

                <a href="https://wa.me/201026361218?text={{ urlencode('مرحبا، عايز الـ AI Pilot - من صفحة الأسعار') }}" target="_blank" rel="noopener"
                   class="block w-full text-center border border-cream/20 hover:bg-cream/5 rounded-full py-3 text-sm font-semibold transition text-cream">
                    💬 {{ __('Start a Pilot on WhatsApp') }}
                </a>
            </div>

            {{-- Founding Partner --}}
            <div class="plan bg-cream text-ink rounded-2xl p-8 relative shadow-xl" data-plan="partner">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-emer-500 text-ink text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full">
                    {{ __('Most partners start here') }}
                </div>
                <div class="text-[10px] uppercase tracking-widest text-ink/50 mb-1">{{ __('Primary offer') }}</div>
                <div class="serif text-3xl mb-1">{{ __('Founding Partner') }}</div>
                <div class="text-ink/70 text-sm mb-6">{{ __('Custom AI built live. We stay co-admin 30 days.') }}</div>

                <div x-show="currency === 'USD'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">$499</div>
                    <div class="text-ink/60 text-xs mb-6" dir="ltr">+ $199 / {{ __('month') }}</div>
                </div>
                <div x-show="currency === 'EGP'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">7,500 <span class="text-2xl">ج</span></div>
                    <div class="text-ink/60 text-xs mb-6" dir="ltr">+ 2,500 ج / {{ __('month') }}</div>
                </div>
                <div x-show="currency === 'SAR'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">1,499 <span class="text-2xl">ر.س</span></div>
                    <div class="text-ink/60 text-xs mb-6" dir="ltr">+ 599 ر.س / {{ __('month') }}</div>
                </div>

                <ul class="space-y-2.5 text-sm text-ink/80 mb-8">
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('Up to 3 pages across platforms') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('Custom AI persona built live') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('Weekly tuning for first month') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('30 days co-admin monitoring') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('Priority Meta review submission') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('WhatsApp support channel') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-600 mt-0.5">✓</span> <span>{{ __('Cancel anytime, no lock-in') }}</span></li>
                </ul>

                <a href="https://wa.me/201026361218?text={{ urlencode('مرحبا، عايز الـ Founding Partner - من صفحة الأسعار') }}" target="_blank" rel="noopener"
                   class="block w-full text-center bg-ink text-cream hover:bg-ink2 rounded-full py-3 text-sm font-semibold transition">
                    💬 {{ __('Become a Partner on WhatsApp') }}
                </a>
            </div>

            {{-- White-Glove --}}
            <div class="plan bg-ink2 border border-cream/10 rounded-2xl p-8" data-plan="whiteglove">
                <div class="text-[10px] uppercase tracking-widest text-cream/50 mb-1">{{ __('Multi-brand / custom') }}</div>
                <div class="serif text-3xl mb-1">{{ __('White-Glove') }}</div>
                <div class="text-cream/60 text-sm mb-6">{{ __('For chains, groups, agencies, custom widgets.') }}</div>

                <div x-show="currency === 'USD'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">{{ __('from') }} $1,499</div>
                    <div class="text-cream/60 text-xs mb-6" dir="ltr">+ $599 / {{ __('month') }}</div>
                </div>
                <div x-show="currency === 'EGP'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">{{ __('from') }} 25,000 <span class="text-2xl">ج</span></div>
                    <div class="text-cream/60 text-xs mb-6" dir="ltr">+ 7,500 ج / {{ __('month') }}</div>
                </div>
                <div x-show="currency === 'SAR'" x-cloak>
                    <div class="serif text-5xl mb-1" dir="ltr">{{ __('from') }} 5,000 <span class="text-2xl">ر.س</span></div>
                    <div class="text-cream/60 text-xs mb-6" dir="ltr">+ 1,999 ر.س / {{ __('month') }}</div>
                </div>

                <ul class="space-y-2.5 text-sm text-cream/80 mb-8">
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Multi-brand / multi-language') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Custom Shopify / WooCommerce widget') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('CRM push (Zoho, HubSpot, Salesforce)') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Dedicated Slack / WhatsApp channel') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('4-hour response SLA') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Monthly review deck') }}</span></li>
                    <li class="flex items-start gap-2"><span class="text-emer-400 mt-0.5">✓</span> <span>{{ __('Scope quoted per project') }}</span></li>
                </ul>

                <a href="https://wa.me/201026361218?text={{ urlencode('مرحبا، عايز White-Glove - من صفحة الأسعار') }}" target="_blank" rel="noopener"
                   class="block w-full text-center border border-cream/20 hover:bg-cream/5 rounded-full py-3 text-sm font-semibold transition text-cream">
                    💬 {{ __('Book a scoping call') }}
                </a>
            </div>
        </div>

        {{-- Payment methods note --}}
        <div class="mt-8 text-center text-sm text-cream/60 fade-up">
            {{ __('Payment: Instapay (Egypt) · Mada / bank transfer (Gulf) · Wise / Payoneer (international). No cards on site. Setup fee paid before onboarding.') }}
        </div>
    </div>
</section>

{{-- ═══════ WHY CONCIERGE (OBJECTION HANDLING) ═══════ --}}
<section class="bg-ink text-cream pb-24 grain relative">
    <div class="max-w-5xl mx-auto px-6 relative z-10">
        <div class="bg-ink2 border border-cream/15 rounded-2xl p-8 md:p-10 fade-up">
            <div class="text-xs uppercase tracking-[0.2em] text-emer-400 mb-3">{{ __('Why not self-serve?') }}</div>
            <h3 class="serif text-3xl md:text-4xl leading-tight mb-6">
                {{ __('Every self-serve AI inbox we tested') }} <span class="serif-it text-emer-400">{{ __('sounded like a bot.') }}</span>
            </h3>
            <div class="grid md:grid-cols-3 gap-6 text-sm text-cream/80 leading-relaxed">
                <div>
                    <div class="text-emer-400 font-semibold mb-2">{{ __('1. Dialect, not translation') }}</div>
                    <p>{{ __("Your customers write in Egyptian, Khaleeji, or Levantine — not Modern Standard Arabic. We tune the AI to your actual dialect in the 90-min discovery call. No signup form can do that.") }}</p>
                </div>
                <div>
                    <div class="text-emer-400 font-semibold mb-2">{{ __('2. Co-admin safety net') }}</div>
                    <p>{{ __("For 30 days we stay as admin on your Facebook or Instagram Page. We watch every AI reply, catch anything weird before your customer sees it twice, and tune weekly.") }}</p>
                </div>
                <div>
                    <div class="text-emer-400 font-semibold mb-2">{{ __('3. Meta review priority') }}</div>
                    <p>{{ __("Meta's per-permission app review blocks non-admin OAuth. We submit the review on your behalf and bridge the gap in the meantime by managing admin access directly.") }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══════ FAQ ═══════ --}}
<section class="py-24 fade-up">
    <div class="max-w-3xl mx-auto px-6">
        <div class="mb-10 text-center">
            <div class="text-xs uppercase tracking-widest text-emer-700 mb-4">{{ __('Common questions') }}</div>
            <h2 class="serif text-4xl lg:text-5xl">{{ __('Partnership questions,') }} <span class="serif-it text-emer-700">{{ __('answered.') }}</span></h2>
        </div>
        <div class="divide-y divide-line border-y border-line">
            @foreach([
                [__('Is there a self-serve signup button?'),
                 __("Not during the Founding Partner cohort. Every AI is hand-built with the owner in a 90-min discovery call. Message us on WhatsApp +201026361218 to begin — we invoice after the call.")],

                [__('Why do you need admin access to my Facebook / Instagram page?'),
                 __("Three reasons. (1) Meta still requires a human admin to approve messaging permissions until our app review completes. (2) Omar personally reviews the AI's conversations in week 1 so weird replies get caught early. (3) If something breaks at 2 AM, we fix it without waiting for you. Remove our admin role any time with one click.")],

                [__('Is there a free trial?'),
                 __("No free trial. The AI Pilot is a 2-week trial at 3,500 EGP / 499 SAR / \$149 with a full refund if the AI doesn't handle 50+ real conversations correctly. That's as close to free as a founder can offer without losing money on every signup.")],

                [__('What if I want to cancel?'),
                 __("Message us on WhatsApp. We remove our admin role from your Page the same day, export your conversation data to CSV if you want it. No minimum term, no clawback, no annual contracts.")],

                [__('How do I pay?'),
                 __("Egypt: Instapay or bank transfer. Saudi: Mada or bank transfer. UAE: bank transfer or Wise. International: Wise or Payoneer. No cards on site. We send an invoice after the discovery call; setup fee is paid before onboarding begins.")],

                [__('Does the AI really speak Egyptian / Khaleeji / Levantine?'),
                 __("Yes — the AI matches the customer's dialect per message. We tune the exact phrasing in your discovery call based on your brand voice. Default is Egyptian Arabic; auto-switches to Khaleeji (Gulf) or Levantine when the customer uses that dialect.")],

                [__('Can you build a Shopify or WooCommerce widget?'),
                 __("Yes — custom widgets are built as part of the White-Glove tier. 2–4 weeks of custom work after the discovery call. Not a plug-and-play shipped feature.")],

                [__('I already have ManyChat / Chatbase / Rasayel / Wati.'),
                 __("Great — then you know the gap. Those tools answer FAQ. We handle sales conversations — qualifying leads, replying in your dialect, handing off to you at the right moment. Keep ManyChat for the funnel, use us for the DMs.")],
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
        <h2 class="serif text-5xl lg:text-6xl leading-none mb-6">
            {{ __('Twenty minutes on WhatsApp.') }}<br>
            <span class="serif-it text-emer-400">{{ __("Let's see if we're a fit.") }}</span>
        </h2>
        <p class="text-cream/70 text-lg mb-8 max-w-xl mx-auto">
            {{ __("Message Omar on WhatsApp — we'll ask what you sell and how many DMs you get, then tell you honestly which tier fits (or if we're the wrong fit).") }}
        </p>
        <a href="https://wa.me/201026361218?text={{ urlencode('مرحبا، عايز أتكلم عن OT1-Pro - من صفحة الأسعار') }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-emer-500 text-ink px-7 py-4 rounded-full font-semibold text-lg hover:bg-emer-400 transition">
            💬 {{ __('Message Omar on WhatsApp') }}
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <p class="mt-4 text-cream/50 text-xs">{{ __('Replies within 15 minutes, 9 AM – 11 PM Cairo time.') }}</p>
    </div>
</section>

@push('scripts')
<script>
(function () {
    var slider = document.getElementById('volume-slider');
    if (!slider) return;
    // 11 buckets, msg/day → recommended concierge tier
    var buckets = [
        { msgs: 5,   plan: 'AI Pilot',         reason: @json(__("very low volume — the Pilot's 14-day refund protects you")) },
        { msgs: 15,  plan: 'AI Pilot',         reason: @json(__("still a Pilot — prove it works before monthly commitment")) },
        { msgs: 30,  plan: 'Founding Partner', reason: @json(__("right in the Partner sweet spot")) },
        { msgs: 50,  plan: 'Founding Partner', reason: @json(__("ideal for Partner — one tuned AI handles your pipeline")) },
        { msgs: 80,  plan: 'Founding Partner', reason: @json(__("Partner is still the fit — this is where it really pays off")) },
        { msgs: 120, plan: 'Founding Partner', reason: @json(__("Partner — you're saving 15–25 hrs/week of manual replies")) },
        { msgs: 180, plan: 'Founding Partner', reason: @json(__("still Partner, but ask about multi-page add-on")) },
        { msgs: 220, plan: 'White-Glove',      reason: @json(__("over 200/day — multi-page or multi-platform makes White-Glove worth it")) },
        { msgs: 300, plan: 'White-Glove',      reason: @json(__("White-Glove — dedicated channel + 4h SLA start to matter")) },
        { msgs: 450, plan: 'White-Glove',      reason: @json(__("multi-brand territory — scope it with us")) },
        { msgs: 700, plan: 'White-Glove',      reason: @json(__("enterprise scope — book a 45-min call")) },
    ];
    function updateSlider() {
        var i = parseInt(slider.value, 10);
        var b = buckets[i];
        document.getElementById('slider-val').textContent = b.msgs.toLocaleString();
        document.getElementById('rec-plan').textContent = b.plan;
        document.getElementById('rec-reason').textContent = b.reason;
        document.querySelectorAll('.plan').forEach(function (p) {
            p.classList.remove('ring-2', 'ring-emer-400');
            var planKey = p.dataset.plan;
            var match = (b.plan === 'AI Pilot' && planKey === 'pilot') ||
                        (b.plan === 'Founding Partner' && planKey === 'partner') ||
                        (b.plan === 'White-Glove' && planKey === 'whiteglove');
            if (match) p.classList.add('ring-2', 'ring-emer-400');
        });
    }
    slider.addEventListener('input', updateSlider);
    updateSlider();
})();
</script>
<style>[x-cloak] { display: none !important; }</style>
@endpush

</x-layouts.brand-marketing>
