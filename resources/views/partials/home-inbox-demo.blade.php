{{--
    Live inbox demo (light theme): hero centerpiece.
    Mimics the product chrome with a conversation that animates message-by-message.
    Respects prefers-reduced-motion (jumps to final frame, no loop).
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
                { at: 600,  to: 1 },
                { at: 1800, to: 2 },
                { at: 3000, to: 3 },
                { at: 4400, to: 4 },
                { at: 5400, to: 5 },
                { at: 6800, to: 6 },
            ];
            const cycle = () => {
                this.step = 0;
                schedule.forEach(s => setTimeout(() => { this.step = s.to; }, s.at));
                this.timer = setTimeout(cycle, 11000);
            };
            cycle();
        },
        destroy() {
            clearTimeout(this.timer);
        }
    }"
    role="img"
    aria-label="Live demo: an AI agent qualifying a Facebook DM inquiry into a hot lead"
    class="relative rounded-2xl border border-zinc-200 bg-white p-5 shadow-[0_24px_60px_-20px_rgba(79,70,229,0.18)]"
>
    {{-- Top bar: channel chip + status --}}
    <div class="mb-5 flex items-center justify-between">
        <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 py-1.5 text-xs">
            <span class="flex size-5 items-center justify-center rounded-md text-[10px] font-bold"
                  style="background: rgba(24,119,242,0.12); color: #1877F2;">FB</span>
            <span class="font-medium text-zinc-800">Acme Apparel</span>
            <span class="text-zinc-300">·</span>
            <span class="text-zinc-500">Cairo</span>
        </div>
        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-zinc-500">
            <span class="relative flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400/70"></span>
                <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
            </span>
            Live
        </span>
    </div>

    {{-- Message thread --}}
    <div class="min-h-[300px] space-y-3 sm:min-h-[340px]">

        {{-- Contact 1 --}}
        <div x-show="step >= 1"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-start">
            <div class="max-w-[78%] rounded-2xl bg-zinc-100 px-4 py-2.5 text-sm leading-snug text-zinc-800">
                Hi! Is the linen shirt still in medium?
            </div>
        </div>

        {{-- Typing indicator --}}
        <div x-show="step === 2"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="flex justify-end">
            <div class="inline-flex items-center gap-1 rounded-2xl bg-indigo-100 px-4 py-3">
                <span class="size-1.5 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="size-1.5 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 150ms"></span>
                <span class="size-1.5 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 300ms"></span>
            </div>
        </div>

        {{-- AI 1 --}}
        <div x-show="step >= 3"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-end">
            <div class="max-w-[78%] rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm leading-snug text-white">
                <div class="mb-1 flex items-center gap-1.5 text-[11px] text-indigo-100">
                    <svg class="size-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="font-semibold">Replied by AI</span>
                    <span class="text-indigo-200">· 96% confidence</span>
                </div>
                Hi! Yes, 3 left in medium. Want me to hold one for you?
            </div>
        </div>

        {{-- Contact 2 --}}
        <div x-show="step >= 4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-start">
            <div class="max-w-[78%] rounded-2xl bg-zinc-100 px-4 py-2.5 text-sm leading-snug text-zinc-800">
                Yes please. Shipping to Cairo?
            </div>
        </div>

        {{-- AI 2 --}}
        <div x-show="step >= 5"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-end">
            <div class="max-w-[78%] rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm leading-snug text-white">
                <div class="mb-1 flex items-center gap-1.5 text-[11px] text-indigo-100">
                    <svg class="size-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="font-semibold">Replied by AI</span>
                    <span class="text-indigo-200">· 91% confidence</span>
                </div>
                2-3 business days, free over EGP 1,500. Want a payment link?
            </div>
        </div>

        {{-- Hot lead badge --}}
        <div x-show="step >= 6"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="flex justify-center pt-1">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[11px] font-semibold text-rose-700">
                <span class="size-1.5 rounded-full bg-rose-500"></span>
                Hot lead detected · score 87/100
            </span>
        </div>

    </div>

    {{-- Footer stats --}}
    <div class="mt-5 grid grid-cols-3 gap-3 border-t border-zinc-100 pt-4">
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-400">Replied in</div>
            <div class="mt-0.5 text-sm font-semibold text-zinc-900">11 sec</div>
        </div>
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-400">Human taps</div>
            <div class="mt-0.5 text-sm font-semibold text-zinc-900">0</div>
        </div>
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-400">Lead score</div>
            <div class="mt-0.5 text-sm font-semibold text-zinc-900">87 / 100</div>
        </div>
    </div>
</div>
