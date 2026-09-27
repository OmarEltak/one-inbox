<x-layouts.brand-marketing
    :solidNav="true"
    :title="__('About OT1-Pro — AI-Powered Social Inbox for Growing Businesses')"
    :description="__('Learn the story behind OT1-Pro — the AI-powered unified social inbox that helps businesses manage Facebook, Instagram, WhatsApp, and Telegram conversations and close more sales.')">

    <section class="pt-32 pb-24 lg:pt-40 lg:pb-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h1 class="serif text-5xl leading-[1.02] text-ink lg:text-7xl">{{ __('About OT1-Pro') }}</h1>
                <p class="mt-6 text-lg text-ink/70">
                    {{ __('We believe every business deserves an AI-powered sales team that never sleeps.') }}
                </p>
            </div>

            <div class="mt-16 space-y-12">
                <div>
                    <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Our Mission') }}</h2>
                    <p class="mt-4 text-ink/70">
                        {{ __('OT1-Pro was built to solve a simple problem: businesses lose sales because they can\'t respond fast enough across multiple social platforms. We unify Facebook, Instagram, WhatsApp, and Telegram into a single inbox, powered by AI that responds instantly, qualifies leads, and drives conversations toward a close.') }}
                    </p>
                </div>

                <div>
                    <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Why OT1-Pro?') }}</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div class="fade-up rounded-2xl border border-line bg-cream2 p-6">
                            <h3 class="serif text-2xl leading-snug text-ink">{{ __('Instant Response') }}</h3>
                            <p class="mt-2 text-sm text-ink/70">{{ __('AI responds to every message in seconds, not hours. No customer waits, no sale lost.') }}</p>
                        </div>
                        <div class="fade-up rounded-2xl border border-line bg-cream2 p-6">
                            <h3 class="serif text-2xl leading-snug text-ink">{{ __('Multi-Platform') }}</h3>
                            <p class="mt-2 text-sm text-ink/70">{{ __('Connect all your social channels and manage conversations from one unified interface.') }}</p>
                        </div>
                        <div class="fade-up rounded-2xl border border-line bg-cream2 p-6">
                            <h3 class="serif text-2xl leading-snug text-ink">{{ __('Lead Intelligence') }}</h3>
                            <p class="mt-2 text-sm text-ink/70">{{ __('AI scores and qualifies leads automatically so you know who\'s ready to buy.') }}</p>
                        </div>
                        <div class="fade-up rounded-2xl border border-line bg-cream2 p-6">
                            <h3 class="serif text-2xl leading-snug text-ink">{{ __('Seamless Handoff') }}</h3>
                            <p class="mt-2 text-sm text-ink/70">{{ __('AI handles routine conversations. Your team steps in when it matters most.') }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="serif text-3xl leading-tight text-ink lg:text-4xl">{{ __('Our Story') }}</h2>
                    <p class="mt-4 text-ink/70">
                        {{ __('OT1-Pro was born from the frustration of managing customer conversations across multiple platforms. We saw businesses losing deals simply because messages fell through the cracks. Our solution: bring everything together and let AI handle the heavy lifting.') }}
                    </p>
                </div>
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

</x-layouts.brand-marketing>
