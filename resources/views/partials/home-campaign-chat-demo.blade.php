{{--
    Live campaign-chat demo (light theme): hero centerpiece for /ai-campaign-manager.
    Same visual chrome as home-inbox-demo — white card, indigo shadow, timed message reveal,
    typing dots, footer stats. Respects prefers-reduced-motion (jumps to final frame).
--}}
<div
    x-data="{
        step: 0,
        timer: null,
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.step = 6;
                return;
            }
            const schedule = [
                { at: 500,  to: 1 },
                { at: 1900, to: 2 },
                { at: 3000, to: 3 },
                { at: 4300, to: 4 },
                { at: 5300, to: 5 },
                { at: 6500, to: 6 },
            ];
            const cycle = () => {
                this.step = 0;
                schedule.forEach(s => setTimeout(() => { this.step = s.to; }, s.at));
                this.timer = setTimeout(cycle, 10500);
            };
            cycle();
        },
        destroy() { clearTimeout(this.timer); }
    }"
    role="img"
    aria-label="Live demo: an operator launching a Ramadan campaign by chatting with Nara, the AI marketing manager"
    class="relative rounded-2xl border border-zinc-200 bg-white p-5 shadow-[0_24px_60px_-20px_rgba(79,70,229,0.18)]"
>
    {{-- Top bar: Nara chip + live status --}}
    <div class="mb-5 flex items-center justify-between">
        <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 text-xs">
            <span class="flex size-5 items-center justify-center rounded-md text-[10px] font-bold"
                  style="background: rgba(79,70,229,0.12); color: #4f46e5;">N</span>
            <span class="font-medium text-zinc-800">Nara</span>
            <span class="text-zinc-300">·</span>
            <span class="text-zinc-500">{{ __('Campaign chat') }}</span>
        </div>
        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-zinc-500">
            <span class="relative flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400/70"></span>
                <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
            </span>
            {{ __('Live') }}
        </span>
    </div>

    {{-- Message thread --}}
    <div class="min-h-[280px] space-y-3 sm:min-h-[300px]">

        {{-- 1. Operator: the ask --}}
        <div x-show="step >= 1"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-end">
            <div class="max-w-[85%] rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm leading-snug text-white">
                {{ __('Ramadan promo → Cairo regulars from last 90 days. 20% off. Send Thu 9am.') }}
            </div>
        </div>

        {{-- 2. Typing dots --}}
        <div x-show="step === 2"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="flex justify-start">
            <div class="inline-flex items-center gap-1 rounded-2xl bg-zinc-100 px-4 py-3">
                <span class="size-1.5 rounded-full bg-zinc-500 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="size-1.5 rounded-full bg-zinc-500 animate-bounce" style="animation-delay: 150ms"></span>
                <span class="size-1.5 rounded-full bg-zinc-500 animate-bounce" style="animation-delay: 300ms"></span>
            </div>
        </div>

        {{-- 3. AI: audience count + draft preview --}}
        <div x-show="step >= 3"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-start">
            <div class="max-w-[88%] rounded-2xl bg-zinc-100 px-4 py-2.5 text-sm leading-snug text-zinc-800">
                <div class="mb-1.5 flex items-center gap-1.5 text-[11px] text-indigo-600">
                    <svg class="size-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="font-semibold">Nara</span>
                    <span class="text-zinc-400">· {{ __('drafted a campaign') }}</span>
                </div>
                <span class="font-semibold text-zinc-900">847 {{ __('customers match.') }}</span>
                <div class="mt-2 flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-[12px] text-zinc-600">
                    <span class="inline-block size-1.5 rounded-full" style="background:#25D366"></span>
                    <span class="font-mono">"Ramadan Kareem — 20% off with RAMADAN20…"</span>
                </div>
            </div>
        </div>

        {{-- 4. Operator: approve --}}
        <div x-show="step >= 4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-end">
            <div class="max-w-[50%] rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm leading-snug text-white">
                {{ __('Ship it.') }}
            </div>
        </div>

        {{-- 5. AI: scheduled confirmation --}}
        <div x-show="step >= 5"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-start">
            <div class="max-w-[85%] rounded-2xl bg-emerald-50 px-4 py-2.5 text-sm leading-snug text-emerald-900">
                <div class="mb-0.5 flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('Scheduled Thu 09:00 Cairo') }}
                </div>
                {{ __("I'll handle every reply. You get the hot leads.") }}
            </div>
        </div>

        {{-- 6. Confirmation pill --}}
        <div x-show="step >= 6"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="flex justify-center pt-1">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700">
                <span class="size-1.5 rounded-full bg-indigo-500"></span>
                {{ __('Campaign live · 847 recipients') }}
            </span>
        </div>

    </div>

    {{-- Footer stats --}}
    <div class="mt-5 grid grid-cols-3 gap-3 border-t border-zinc-100 pt-4">
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-400">{{ __('Reach') }}</div>
            <div class="mt-0.5 text-sm font-semibold text-zinc-900">847</div>
        </div>
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-400">{{ __('Est. replies') }}</div>
            <div class="mt-0.5 text-sm font-semibold text-zinc-900">~120</div>
        </div>
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-400">{{ __('Time to ship') }}</div>
            <div class="mt-0.5 text-sm font-semibold text-zinc-900">90 sec</div>
        </div>
    </div>
</div>
