<x-layouts.marketing
    :title="__('Find your OT1-Pro fit — 60-second interactive quiz')"
    :description="__('Answer 3 short questions and get a personalized AI + inbox setup recommendation for your business, industry, and goals. No email required.')"
>

    {{-- ═══════════════════════════════════════════════════════════════════
         Find-Your-Fit interactive quiz
         Status: BETA — CTAs deliberately NOT wired to signup until Meta
         App Review completes (see CLAUDE.md pin #1). The final card shows
         a "coming soon" message + WhatsApp handoff instead of a real signup
         button. Do not wire the CTAs before flipping META_APP_VERIFIED=true.
         ═══════════════════════════════════════════════════════════════════ --}}

    <section class="relative min-h-[calc(100vh-4rem)] py-16 lg:py-24 bg-gradient-to-b from-white via-indigo-50/40 to-white"
             x-data="findYourFit()"
             x-cloak>

        <div class="mx-auto max-w-4xl px-6">

            {{-- Header --}}
            <div class="text-center mb-10">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600 mb-3">
                    {{ __('60-second personalized setup') }}
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-zinc-900 tracking-tight">
                    {{ __('Find the AI setup') }}
                    <span class="text-indigo-600">{{ __('built for you.') }}</span>
                </h1>
                <p class="mt-4 max-w-xl mx-auto text-zinc-600 text-base sm:text-lg">
                    {{ __('Three questions. Zero email. A personalized recommendation for your business, your goals, and the brand you want to look like in 12 months.') }}
                </p>
            </div>

            {{-- Progress bar --}}
            <div class="mb-10">
                <div class="flex justify-between text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">
                    <span :class="step >= 1 ? 'text-indigo-600' : ''">{{ __('Who') }}</span>
                    <span :class="step >= 2 ? 'text-indigo-600' : ''">{{ __('Goal') }}</span>
                    <span :class="step >= 3 ? 'text-indigo-600' : ''">{{ __('Vision') }}</span>
                    <span :class="step >= 4 ? 'text-indigo-600' : ''">{{ __('Result') }}</span>
                </div>
                <div class="h-2 bg-zinc-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500 ease-out"
                         :style="`width: ${(step / 4) * 100}%`"></div>
                </div>
            </div>

            {{-- STEP 1: Who are you? --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 text-center mb-8">
                    {{ __('First — who are you?') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="option in whoOptions" :key="option.id">
                        <button type="button"
                                @click="answers.who = option.id; nextStep()"
                                class="group relative text-left p-6 bg-white border-2 border-zinc-200 hover:border-indigo-500 rounded-2xl transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                            <div class="text-3xl mb-3" x-text="option.icon"></div>
                            <div class="font-semibold text-zinc-900 text-lg" x-text="option.label"></div>
                            <div class="text-sm text-zinc-500 mt-1" x-text="option.sub"></div>
                            <div class="absolute top-4 right-4 w-6 h-6 rounded-full border-2 border-zinc-300 group-hover:border-indigo-500 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- STEP 2: What's your goal? --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 text-center mb-2">
                    {{ __('What are you losing sleep over?') }}
                </h2>
                <p class="text-center text-zinc-500 mb-8">{{ __('Pick the one that stings the most.') }}</p>
                <div class="space-y-3">
                    <template x-for="option in goalOptions" :key="option.id">
                        <button type="button"
                                @click="answers.goal = option.id; nextStep()"
                                class="group w-full text-left p-5 bg-white border-2 border-zinc-200 hover:border-indigo-500 rounded-xl transition-all duration-200 hover:shadow-md flex items-start gap-4">
                            <div class="text-2xl flex-shrink-0" x-text="option.icon"></div>
                            <div class="flex-1">
                                <div class="font-semibold text-zinc-900" x-text="option.label"></div>
                                <div class="text-sm text-zinc-500 mt-0.5" x-text="option.sub"></div>
                            </div>
                            <svg class="w-5 h-5 text-zinc-300 group-hover:text-indigo-500 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </template>
                </div>
                <div class="mt-6 text-center">
                    <button @click="prevStep()" class="text-sm text-zinc-500 hover:text-indigo-600 font-medium">
                        ← {{ __('Back') }}
                    </button>
                </div>
            </div>

            {{-- STEP 3: Who do you want to look like? --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 text-center mb-2">
                    {{ __('Who do you want to look like in 12 months?') }}
                </h2>
                <p class="text-center text-zinc-500 mb-8">{{ __('Your competitor, hero, or the brand you quietly admire.') }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template x-for="option in visionOptions" :key="option.id">
                        <button type="button"
                                @click="answers.vision = option.id; nextStep()"
                                class="group text-left p-5 bg-white border-2 border-zinc-200 hover:border-indigo-500 rounded-xl transition hover:shadow-md">
                            <div class="text-2xl mb-2" x-text="option.icon"></div>
                            <div class="font-semibold text-zinc-900" x-text="option.label"></div>
                            <div class="text-sm text-zinc-500 mt-1" x-text="option.sub"></div>
                        </button>
                    </template>
                </div>
                <div class="mt-6 text-center">
                    <button @click="prevStep()" class="text-sm text-zinc-500 hover:text-indigo-600 font-medium">
                        ← {{ __('Back') }}
                    </button>
                </div>
            </div>

            {{-- STEP 4: Personalized result --}}
            <div x-show="step === 4" x-transition:enter="transition ease-out duration-700"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <div class="bg-white border border-zinc-200 rounded-3xl shadow-xl overflow-hidden">

                    {{-- Result header --}}
                    <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 text-white p-8 sm:p-10">
                        <div class="text-xs uppercase tracking-[0.18em] text-indigo-200 mb-2">
                            {{ __('Your personalized setup') }}
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold" x-text="result.headline"></h2>
                        <p class="mt-3 text-indigo-100 text-base sm:text-lg" x-text="result.subhead"></p>
                    </div>

                    {{-- The recommended 3-step setup --}}
                    <div class="p-8 sm:p-10">
                        <div class="text-xs uppercase tracking-wider font-semibold text-zinc-500 mb-4">
                            {{ __('Your 3-step setup') }}
                        </div>
                        <div class="space-y-4">
                            <template x-for="(step, i) in result.steps" :key="i">
                                <div class="flex gap-4 p-4 bg-indigo-50/50 rounded-xl">
                                    <div class="flex-shrink-0 w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-semibold text-sm"
                                         x-text="i + 1"></div>
                                    <div>
                                        <div class="font-semibold text-zinc-900" x-text="step.title"></div>
                                        <div class="text-sm text-zinc-600 mt-0.5" x-text="step.desc"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Recommended plan --}}
                        <div class="mt-8 p-5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl">
                            <div class="text-xs uppercase tracking-wider font-semibold text-amber-700 mb-1">
                                {{ __('Recommended plan for you') }}
                            </div>
                            <div class="text-lg font-bold text-zinc-900" x-text="result.plan"></div>
                            <div class="text-sm text-zinc-600 mt-1" x-text="result.planWhy"></div>
                        </div>

                        {{-- CTA — deliberately not wired to signup until Meta App Verified.
                             Once META_APP_VERIFIED=true and OAuth is live, swap the "Coming soon"
                             card below for real /register links with ?fit=... query params so
                             we know which quiz answers led to signup. --}}
                        <div class="mt-8 p-6 bg-zinc-50 border-2 border-dashed border-zinc-300 rounded-xl text-center">
                            <div class="text-sm font-semibold text-zinc-900 mb-2">
                                🚧 {{ __('Personalized signup — coming soon') }}
                            </div>
                            <p class="text-sm text-zinc-600 mb-4">
                                {{ __('We\'re finishing the last piece of Meta verification. For now, message me directly on WhatsApp and I\'ll set your account up personally based on your answers above.') }}
                            </p>
                            <a href="https://wa.me/201026361218?text={{ urlencode(__('Hi! I just did the quiz on ot1-pro.com and I\'d like the setup you recommended for me.')) }}"
                               target="_blank"
                               rel="noopener"
                               class="inline-flex items-center gap-2 px-6 py-3 bg-[#25D366] text-white font-semibold rounded-xl hover:bg-[#20b658] transition shadow-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                {{ __('Talk to the founder on WhatsApp') }}
                            </a>
                            <div class="mt-3 text-xs text-zinc-500">
                                {{ __('Or') }}
                                <a href="{{ route('pricing') }}" class="underline hover:text-indigo-600">{{ __('see plans') }}</a>
                                ·
                                <a href="{{ route('blog.index') }}" class="underline hover:text-indigo-600">{{ __('read the blog') }}</a>
                            </div>
                        </div>

                        {{-- Restart --}}
                        <div class="mt-6 text-center">
                            <button @click="restart()" class="text-sm text-zinc-500 hover:text-indigo-600 font-medium">
                                ↻ {{ __('Start over') }}
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <script>
            function findYourFit() {
                return {
                    step: 1,
                    answers: { who: null, goal: null, vision: null },

                    whoOptions: [
                        { id: 'owner',      icon: '🏪', label: "Store owner",         sub: "I own a Shopify, WooCommerce, or Instagram store" },
                        { id: 'agency',     icon: '🎯', label: "Agency / freelancer", sub: "I run comms for clients" },
                        { id: 'developer',  icon: '💻', label: "Developer / builder", sub: "I build for my own project or a client" },
                        { id: 'other',      icon: '🌱', label: "Someone else",        sub: "Restaurant, clinic, real estate, or something else" },
                    ],

                    goalOptions: [
                        { id: 'missed_dms',   icon: '😴', label: "I'm losing sales at night when DMs go unanswered", sub: "The single biggest reason people switch to AI." },
                        { id: 'slow_replies', icon: '🐢', label: "My replies are too slow and customers go to competitors", sub: "Every extra minute costs 4-6% of conversion." },
                        { id: 'team_cost',    icon: '💸', label: "My team is expensive and can't cover every hour", sub: "The classic small-team scaling wall." },
                        { id: 'chaos',        icon: '🌀', label: "Messages are scattered across 5 apps and I'm drowning", sub: "WhatsApp + Instagram + Facebook + email + Telegram = chaos." },
                        { id: 'quality',      icon: '✨', label: "I want to reply better, not just faster", sub: "Sharper qualifying, better close rate." },
                    ],

                    visionOptions: [
                        { id: 'big_brand',  icon: '👑', label: "Look like a big brand",   sub: "Namshi, Vinted, Shein-level polish and speed." },
                        { id: 'local_hero', icon: '🌟', label: "Own my local niche",      sub: "The go-to store in my city or category." },
                        { id: 'solo_king',  icon: '🧘', label: "Run it all solo, calmly", sub: "Small business, low overhead, high quality." },
                        { id: 'scale',      icon: '🚀', label: "Scale to a real team",    sub: "Grow past $50k/month without chaos." },
                    ],

                    get result() {
                        return this.computeResult();
                    },

                    computeResult() {
                        // Personalization matrix keyed on the (who × goal × vision) tuple.
                        // Keep phrasing warm/direct — this is the reward for finishing the quiz.
                        const who = this.answers.who || 'owner';
                        const goal = this.answers.goal || 'missed_dms';
                        const vision = this.answers.vision || 'big_brand';

                        const whoLabels = {
                            owner: "For a store owner",
                            agency: "For an agency",
                            developer: "For a developer",
                            other: "For your business",
                        };

                        const goalHeadlines = {
                            missed_dms:   "An AI that closes sales while you sleep.",
                            slow_replies: "Sub-60-second replies that beat every competitor.",
                            team_cost:    "The AI stack that quietly replaces a 4-person team.",
                            chaos:        "One inbox. Every channel. Zero more app-switching.",
                            quality:      "Sharper replies. Higher close rate. Same volume.",
                        };

                        const visionSteps = {
                            big_brand: [
                                { title: "Connect all 5 channels",  desc: "WhatsApp + Instagram + Messenger + Telegram + email in one inbox." },
                                { title: "Train the AI on 20 past conversations", desc: "Your brand voice, your product catalog, your policies." },
                                { title: "Set clean escalation rules", desc: "AI handles 80%, you handle only the high-value 20%." },
                            ],
                            local_hero: [
                                { title: "Start with WhatsApp Business", desc: "The channel that matters most in your local market." },
                                { title: "Add Arabic AI responses",      desc: "The moat vs bigger, English-only competitors." },
                                { title: "Track response time weekly",   desc: "Under 60 seconds is the new floor." },
                            ],
                            solo_king: [
                                { title: "Start on the free plan", desc: "20 AI replies/month, no credit card. Prove the model on real customers." },
                                { title: "Automate only the top 5 questions", desc: "Order status, delivery, sizes, prices, returns. That's 80% of volume." },
                                { title: "Escalate everything else to your phone", desc: "15 minutes of curated attention per day, not 8 hours." },
                            ],
                            scale: [
                                { title: "Deploy the shared team inbox", desc: "Assign conversations, add notes, track SLAs across the team." },
                                { title: "AI handles the top-of-funnel",  desc: "Humans handle the qualified pipeline." },
                                { title: "Instrument every conversation", desc: "Weekly analytics tell you what to fix in product, ads, or shipping." },
                            ],
                        };

                        const planMap = {
                            solo_king:  { plan: "Free → Basic ($8/mo when you outgrow it)", why: "Prove the AI works on real customers before you pay." },
                            big_brand:  { plan: "Starter ($29/mo)",                         why: "All 5 channels, lead scoring, up to 500 AI replies/month." },
                            local_hero: { plan: "Starter ($29/mo)",                         why: "Arabic AI + WhatsApp priority. Perfect for a local hero brand." },
                            scale:      { plan: "Pro ($79/mo)",                             why: "2,000 AI replies/month + advanced analytics + 10 team seats." },
                        };

                        const plan = planMap[vision] || planMap.big_brand;

                        return {
                            headline: goalHeadlines[goal],
                            subhead:  whoLabels[who] + ' · ' + this.visionShortLabel(vision),
                            steps:    visionSteps[vision],
                            plan:     plan.plan,
                            planWhy:  plan.why,
                        };
                    },

                    visionShortLabel(v) {
                        const m = {
                            big_brand:  "big-brand energy",
                            local_hero: "local-hero focus",
                            solo_king:  "solo-founder calm",
                            scale:      "ready to scale",
                        };
                        return m[v] || '';
                    },

                    nextStep() { if (this.step < 4) this.step++; },
                    prevStep() { if (this.step > 1) this.step--; },
                    restart()  {
                        this.step = 1;
                        this.answers = { who: null, goal: null, vision: null };
                    },
                };
            }
        </script>

    </section>

</x-layouts.marketing>
