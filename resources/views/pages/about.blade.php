<x-layouts.marketing
    :title="__('About OT1-Pro — AI-Powered Social Inbox for Growing Businesses')"
    :description="__('Learn the story behind OT1-Pro — the AI-powered unified social inbox that helps businesses manage Facebook, Instagram, WhatsApp, and Telegram conversations and close more sales.')">

    <section class="py-20 lg:py-28">
        <div class="mx-auto max-w-4xl px-6">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('About OT1-Pro') }}</h1>
                <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-600">
                    {{ __('We believe every business deserves an AI-powered sales team that never sleeps.') }}
                </p>
            </div>

            <div class="mt-16 space-y-12">
                <div class="grid gap-12 lg:grid-cols-2 items-center">
                    <div>
                        <h2 class="text-2xl font-bold">{{ __('Our Mission') }}</h2>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-600 leading-relaxed">
                            {{ __('OT1-Pro was built to solve a simple problem: businesses lose sales because they can\'t respond fast enough across multiple social platforms. We unify Facebook, Instagram, WhatsApp, and Telegram into a single inbox, powered by AI that responds instantly, qualifies leads, and drives conversations toward a close.') }}
                        </p>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-600 leading-relaxed">
                            {{ __('Our goal is to empower small-to-mid sized brands to compete with the giants by automating the repetitive parts of sales while keeping the human touch where it matters most.') }}
                        </p>
                    </div>
                    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-2xl p-8 border border-zinc-200 dark:border-zinc-700">
                        <div class="space-y-4">
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                                <div>
                                    <h4 class="font-semibold">Unified Access</h4>
                                    <p class="text-sm text-zinc-500">Stop switching tabs. One inbox for everything.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                                <div>
                                    <h4 class="font-semibold">AI Qualification</h4>
                                    <p class="text-sm text-zinc-500">Our AI identifies high-intent buyers automatically.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                                <div>
                                    <h4 class="font-semibold">24/7 Availability</h4>
                                    <p class="text-sm text-zinc-500">Capture leads at 3 AM without waking up.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-center">{{ __('Why OT1-Pro?') }}</h2>
                    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-200 hover:border-emerald-500 transition-colors">
                            <h3 class="font-semibold text-emerald-600">{{ __('Instant Response') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ __('AI responds to every message in seconds. No customer waits, no sale lost.') }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-200 hover:border-emerald-500 transition-colors">
                            <h3 class="font-semibold text-emerald-600">{{ __('Multi-Platform') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ __('Connect all your social channels and manage conversations from one unified interface.') }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-200 hover:border-emerald-500 transition-colors">
                            <h3 class="font-semibold text-emerald-600">{{ __('Lead Intelligence') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ __('AI scores and qualifies leads automatically so you know who\'s ready to buy.') }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-200 hover:border-emerald-500 transition-colors">
                            <h3 class="font-semibold text-emerald-600">{{ __('Seamless Handoff') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-600">{{ __('AI handles routine conversations. Your team steps in when it matters most.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-900 rounded-3xl p-8 lg:p-12 border border-zinc-200 dark:border-zinc-800">
                    <div class="max-w-2xl mx-auto text-center">
                        <h2 class="text-2xl font-bold">{{ __('Our Story') }}</h2>
                        <p class="mt-6 text-lg text-zinc-600 dark:text-zinc-600 leading-relaxed">
                            {{ __('OT1-Pro was born from the frustration of managing customer conversations across multiple platforms. We saw businesses losing deals simply because messages fell through the cracks. We decided to build the tool we wished we had: a single source of truth for all customer interactions, supercharged by AI to handle the heavy lifting.') }}
                        </p>
                        <div class="mt-8">
                            <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-all">
                                {{ __('Start Scaling Your Sales') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-8 dark:border-zinc-800 dark:bg-zinc-900 lg:p-10">
                    <h2 class="text-2xl font-bold">{{ __('Business information') }}</h2>
                    <p class="mt-3 leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ __('OT1-Pro is the product name of OT Pro, a business based in Cairo, Egypt. These details match the OT Pro business portfolio used to operate our Meta integrations.') }}
                    </p>
                    <dl class="mt-6 grid gap-6 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Business name') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">OT Pro</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Legal business name') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">OT Pro</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Business address') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">Cairo, Cairo 171811, Egypt</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Business phone') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400"><a href="tel:+201026361218" class="hover:text-emerald-600">+20 102 636 1218</a></dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Business portfolio ID') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">2169075923895403</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Primary Page') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">{{ __('None') }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Website') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400"><a href="https://ot1-pro.com/" class="hover:text-emerald-600">ot1-pro.com</a></dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Meta business verification') }}</dt>
                            <dd class="mt-1 text-zinc-600 dark:text-zinc-400">{{ __('Verified — February 18, 2026') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="text-center">
                    <h2 class="text-2xl font-bold">{{ __('Reach us') }}</h2>
                    <p class="mt-4 text-zinc-600 dark:text-zinc-600">
                        {{ __('Questions, partnerships, or just want to say hello — we reply fast.') }}
                    </p>
                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="mailto:support@ot1-pro.com" class="inline-flex items-center justify-center rounded-full border border-zinc-300 px-6 py-3 text-sm font-semibold text-zinc-700 hover:border-emerald-500 hover:text-emerald-700 transition-all">
                            support@ot1-pro.com
                        </a>
                        <a href="https://wa.me/201026361218" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-all">
                            {{ __('WhatsApp the founder') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.marketing>
