{{--
    Live inbox demo: hero centerpiece.
    Mimics the actual product chrome with a conversation that animates message-by-message.
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
    class="relative rounded-2xl border border-white/15 bg-zinc-900/60 p-5 shadow-2xl shadow-purple-500/10 backdrop-blur-sm"
>
    {{-- Top bar: channel chip + status --}}
    <div class="mb-5 flex items-center justify-between">
        <div class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-white/[0.03] px-2.5 py-1.5 text-xs">
            <span class="flex size-5 items-center justify-center rounded-md text-[10px] font-bold"
                  style="background: rgba(24,119,242,0.15); color: #4D9DFF;">FB</span>
            <span class="font-medium text-white/85">Acme Apparel</span>
            <span class="text-white/30">·</span>
            <span class="text-white/50">Cairo</span>
        </div>
        <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-white/50">
            <span class="relative flex size-2">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-green-400/60"></span>
                <span class="relative inline-flex size-2 rounded-full bg-green-400"></span>
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
            <div class="max-w-[78%] rounded-2xl bg-zinc-800/90 px-4 py-2.5 text-sm leading-snug text-zinc-100">
                Hi! Is the linen shirt still in medium?
            </div>
        </div>

        {{-- Typing indicator --}}
        <div x-show="step === 2"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="flex justify-end">
            <div class="inline-flex items-center gap-1 rounded-2xl bg-purple-600/40 px-4 py-3">
                <span class="size-1.5 rounded-full bg-white/80 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="size-1.5 rounded-full bg-white/80 animate-bounce" style="animation-delay: 150ms"></span>
                <span class="size-1.5 rounded-full bg-white/80 animate-bounce" style="animation-delay: 300ms"></span>
            </div>
        </div>

        {{-- AI 1 --}}
        <div x-show="step >= 3"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-end">
            <div class="max-w-[78%] rounded-2xl bg-purple-600 px-4 py-2.5 text-sm leading-snug text-white">
                <div class="mb-1 flex items-center gap-1.5 text-[11px] opacity-90">
                    <svg class="size-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="font-semibold">Replied by AI</span>
                    <span class="opacity-75">· 96% confidence</span>
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
            <div class="max-w-[78%] rounded-2xl bg-zinc-800/90 px-4 py-2.5 text-sm leading-snug text-zinc-100">
                Yes please. Shipping to Cairo?
            </div>
        </div>

        {{-- AI 2 --}}
        <div x-show="step >= 5"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex justify-end">
            <div class="max-w-[78%] rounded-2xl bg-purple-600 px-4 py-2.5 text-sm leading-snug text-white">
                <div class="mb-1 flex items-center gap-1.5 text-[11px] opacity-90">
                    <svg class="size-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    <span class="font-semibold">Replied by AI</span>
                    <span class="opacity-75">· 91% confidence</span>
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
            <span class="inline-flex items-center gap-1.5 rounded-full border border-red-500/30 bg-red-500/[0.12] px-3 py-1 text-[11px] font-semibold text-red-300">
                <span class="size-1.5 rounded-full bg-red-500"></span>
                Hot lead detected · score 87/100
            </span>
        </div>

    </div>

    {{-- Footer stats --}}
    <div class="mt-5 grid grid-cols-3 gap-3 border-t border-white/10 pt-4">
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-white/35">Replied in</div>
            <div class="mt-0.5 text-sm font-semibold text-white">11 sec</div>
        </div>
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-white/35">Human taps</div>
            <div class="mt-0.5 text-sm font-semibold text-white">0</div>
        </div>
        <div>
            <div class="text-[10px] font-medium uppercase tracking-widest text-white/35">Lead score</div>
            <div class="mt-0.5 text-sm font-semibold text-white">87 / 100</div>
        </div>
    </div>
</div>
