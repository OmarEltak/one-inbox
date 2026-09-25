<div
    class="min-h-[calc(100vh-4rem)] p-4 sm:p-6"
    x-data="{
        // Kicked off by the server after step 2 → advances to step 3 and calls generate().
        startGeneration() {
            @this.call('generate');
        },
        // Kicked off after step 3 completes AND after user sends a customer message.
        aiTurn() {
            @this.call('generateAiTurn');
        },
        scrollChatToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('meet-ai-chat-scroll');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
    }"
    x-init="
        // These are dispatched from the PHP component after step transitions.
        window.addEventListener('start-generation', () => startGeneration());
        window.addEventListener('ai-turn', () => aiTurn());
    "
    @chat-updated.window="scrollChatToBottom()"
>
    <div class="mx-auto max-w-3xl">
        {{-- Header + step counter. Skip removed intentionally: onboarding is
             mandatory now, enforced by the RequireOnboarding middleware. Users
             who don't finish this hit the wizard every time they log in. --}}
        <div class="mb-6">
            <flux:heading size="xl" class="!text-zinc-900">
                {{ __('Meet your AI') }}
            </flux:heading>
            <flux:text class="mt-1 !text-zinc-600">
                {{ __('Step :current of :total', ['current' => $step, 'total' => $totalSteps]) }}
            </flux:text>
        </div>

        {{-- Progress bar --}}
        <div class="mb-8 h-1.5 rounded-full bg-zinc-200 overflow-hidden">
            <div
                class="h-full bg-violet-600 transition-all duration-500 ease-out"
                style="width: {{ ($step / $totalSteps) * 100 }}%"
            ></div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 1 — Business type card picker
             ═══════════════════════════════════════════════════════════════ --}}
        @if($step === 1)
            <div>
                <flux:heading size="lg" class="mb-2 !text-zinc-900">
                    {{ __('What kind of business is this?') }}
                </flux:heading>
                <flux:text class="mb-6 !text-zinc-600">
                    {{ __('Pick the closest match — we\'ll tailor the AI to how your customers actually talk.') }}
                </flux:text>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($businessTypeOptions as $opt)
                        <button
                            type="button"
                            wire:click="pickBusinessType('{{ $opt['id'] }}')"
                            class="group relative flex flex-col items-start gap-2 p-5 rounded-xl border-2 border-zinc-200 bg-white hover:border-violet-500 hover:shadow-lg text-left transition-all cursor-pointer"
                            data-test="business-type-{{ $opt['id'] }}"
                        >
                            <div class="size-10 rounded-lg bg-violet-100 flex items-center justify-center group-hover:bg-violet-200">
                                <flux:icon :name="$opt['icon']" class="size-5 text-violet-700" />
                            </div>
                            <div class="font-semibold text-zinc-900">{{ __($opt['label']) }}</div>
                            <div class="text-xs text-zinc-500">{{ __($opt['hint']) }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 2 — Three conversational questions
             ═══════════════════════════════════════════════════════════════ --}}
        @if($step === 2)
            <div class="space-y-6">
                {{-- Editable business type crumb (jumps back to step 1) --}}
                <button
                    type="button"
                    wire:click="backToStep1"
                    class="inline-flex items-center gap-2 text-xs text-zinc-500 hover:text-violet-700 cursor-pointer"
                    data-test="edit-business-type"
                >
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    {{ __('Business type:') }}
                    <span class="font-semibold text-violet-700">{{ __($this->businessTypeLabel) }}</span>
                    <span class="underline underline-offset-2">{{ __('change') }}</span>
                </button>

                {{-- Q1 --}}
                @if($questionIndex >= 1)
                    <div class="flex gap-3">
                        <div class="shrink-0 size-9 rounded-full bg-violet-600 flex items-center justify-center text-white text-sm font-bold">1</div>
                        <div class="flex-1 rounded-2xl rounded-tl-sm bg-white border border-zinc-200 p-4">
                            <div class="text-zinc-900 font-medium mb-3">
                                {{ __('What do you sell or offer?') }}
                            </div>
                            @if($questionIndex === 1)
                                {{-- Hand-rolled textarea: flux:textarea renders typed text as zinc-500
                                     which is unreadable on white. Force zinc-900 + violet focus ring
                                     for parity with step 4's customer input. --}}
                                <textarea
                                    wire:model="q1Offer"
                                    rows="3"
                                    placeholder="{{ __('e.g. Handmade leather bags shipped worldwide') }}"
                                    autofocus
                                    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition resize-y"
                                ></textarea>
                                @error('q1Offer') <div class="text-xs text-red-600 mt-2">{{ $message }}</div> @enderror
                                <div class="flex justify-end mt-3">
                                    <flux:button variant="primary" wire:click="submitQuestion" class="cursor-pointer" data-test="q1-continue">
                                        {{ __('Continue') }}
                                    </flux:button>
                                </div>
                            @else
                                <button
                                    type="button"
                                    wire:click="editQuestion(1)"
                                    class="w-full text-left group cursor-pointer"
                                    data-test="edit-q1"
                                >
                                    <div class="text-zinc-700 text-sm whitespace-pre-wrap">{{ $q1Offer }}</div>
                                    <div class="mt-1.5 text-xs text-zinc-400 group-hover:text-violet-700 underline underline-offset-2">{{ __('Edit') }}</div>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Q2 --}}
                @if($questionIndex >= 2)
                    <div class="flex gap-3">
                        <div class="shrink-0 size-9 rounded-full bg-violet-600 flex items-center justify-center text-white text-sm font-bold">2</div>
                        <div class="flex-1 rounded-2xl rounded-tl-sm bg-white border border-zinc-200 p-4">
                            <div class="text-zinc-900 font-medium mb-3">
                                {{ __('What\'s the #1 question customers ask you?') }}
                            </div>
                            @if($questionIndex === 2)
                                <textarea
                                    wire:model="q2TopQuestion"
                                    rows="2"
                                    placeholder="{{ __('Pick a suggestion or write your own') }}"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition resize-y"
                                ></textarea>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    @foreach($suggestedQuestions as $q)
                                        <button
                                            type="button"
                                            wire:click="useSuggestedQuestion(@js($q))"
                                            class="px-3 py-1.5 rounded-full text-xs bg-violet-50 text-violet-700 border border-violet-200 hover:bg-violet-100 transition cursor-pointer"
                                        >
                                            {{ $q }}
                                        </button>
                                    @endforeach
                                </div>
                                @error('q2TopQuestion') <div class="text-xs text-red-600 mt-2">{{ $message }}</div> @enderror
                                <div class="flex justify-end mt-3">
                                    <flux:button variant="primary" wire:click="submitQuestion" class="cursor-pointer" data-test="q2-continue">
                                        {{ __('Continue') }}
                                    </flux:button>
                                </div>
                            @else
                                <button
                                    type="button"
                                    wire:click="editQuestion(2)"
                                    class="w-full text-left group cursor-pointer"
                                    data-test="edit-q2"
                                >
                                    <div class="text-zinc-700 text-sm whitespace-pre-wrap">{{ $q2TopQuestion }}</div>
                                    <div class="mt-1.5 text-xs text-zinc-400 group-hover:text-violet-700 underline underline-offset-2">{{ __('Edit') }}</div>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Q3 --}}
                @if($questionIndex >= 3)
                    <div class="flex gap-3">
                        <div class="shrink-0 size-9 rounded-full bg-violet-600 flex items-center justify-center text-white text-sm font-bold">3</div>
                        <div class="flex-1 rounded-2xl rounded-tl-sm bg-white border border-zinc-200 p-4">
                            <div class="text-zinc-900 font-medium mb-3">
                                {{ __('How do you want your AI to sound?') }}
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                                @foreach(['formal' => __('Formal'), 'friendly' => __('Friendly'), 'playful' => __('Playful'), 'my_own' => __('My own style')] as $key => $label)
                                    <button
                                        type="button"
                                        wire:click="$set('q3Tone', '{{ $key }}')"
                                        class="px-3 py-2 rounded-lg text-sm border-2 transition cursor-pointer
                                            {{ $q3Tone === $key ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-zinc-700 border-zinc-200 hover:border-violet-300' }}"
                                        data-test="tone-{{ $key }}"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            @if($q3Tone === 'my_own')
                                <textarea
                                    wire:model="q3CustomTone"
                                    rows="2"
                                    placeholder="{{ __('e.g. warm but no-nonsense, uses local Egyptian slang') }}"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition resize-y"
                                ></textarea>
                                @error('q3CustomTone') <div class="text-xs text-red-600 mt-2">{{ $message }}</div> @enderror
                            @endif
                            <div class="flex justify-end mt-3">
                                <flux:button variant="primary" wire:click="submitQuestion" class="cursor-pointer" data-test="q3-continue">
                                    {{ __('Generate my AI') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 3 — Generating (terminal-style animation)
             ═══════════════════════════════════════════════════════════════ --}}
        @if($step === 3)
            <div class="rounded-xl bg-zinc-900 text-green-400 font-mono text-sm p-6 shadow-xl">
                <div class="flex items-center gap-2 text-zinc-500 mb-4">
                    <span class="size-3 rounded-full bg-red-500"></span>
                    <span class="size-3 rounded-full bg-yellow-500"></span>
                    <span class="size-3 rounded-full bg-green-500"></span>
                    <span class="ml-2 text-xs">building your AI…</span>
                </div>

                <div wire:loading.remove wire:target="generate" class="space-y-2">
                    <div>&gt; <span class="animate-pulse">initializing…</span></div>
                </div>
                <div wire:loading wire:target="generate" class="space-y-2">
                    <div>&gt; reading your answers…</div>
                    <div>&gt; drafting your AI's system prompt…</div>
                    <div>&gt; tuning voice and tone…</div>
                    <div>&gt; almost ready…</div>
                    <div><span class="animate-pulse">▊</span></div>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             STEP 4 — Fake customer chat
             ═══════════════════════════════════════════════════════════════ --}}
        @if($step === 4)
            <div class="space-y-4">
                <div class="rounded-xl bg-violet-50 border border-violet-200 p-4 text-sm text-violet-900">
                    <div class="font-semibold mb-1">{{ __('You\'re now chatting as a fake customer.') }}</div>
                    <div>{{ __('Your AI is replying using the voice you just described. Try asking real questions.') }}</div>
                </div>

                <div
                    id="meet-ai-chat-scroll"
                    class="rounded-xl bg-white border border-zinc-200 h-96 overflow-y-auto p-4 space-y-3"
                    x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                >
                    @foreach($chatMessages as $msg)
                        @if($msg['role'] === 'user')
                            {{-- inline-block on the bubble so it shrinks to content width; text-left forces
                                 natural reading alignment regardless of bubble width. --}}
                            <div class="text-right">
                                <div class="inline-block max-w-[75%] text-left rounded-2xl rounded-br-md bg-violet-600 text-white px-3.5 py-2 text-sm leading-relaxed whitespace-pre-wrap break-words">{{ $msg['content'] }}</div>
                            </div>
                        @else
                            <div class="text-left">
                                <div class="inline-block max-w-[75%] text-left rounded-2xl rounded-bl-md bg-zinc-100 text-zinc-900 px-3.5 py-2 text-sm leading-relaxed whitespace-pre-wrap break-words">{{ $msg['content'] }}</div>
                            </div>
                        @endif
                    @endforeach

                    @if($isAiTyping)
                        <div class="text-left">
                            <div class="inline-flex rounded-2xl rounded-bl-md bg-zinc-100 px-3.5 py-2.5 gap-1 items-center">
                                <span class="size-1.5 rounded-full bg-zinc-400 animate-bounce" style="animation-delay: 0ms"></span>
                                <span class="size-1.5 rounded-full bg-zinc-400 animate-bounce" style="animation-delay: 150ms"></span>
                                <span class="size-1.5 rounded-full bg-zinc-400 animate-bounce" style="animation-delay: 300ms"></span>
                            </div>
                        </div>
                    @endif
                </div>

                @if($chatSoftError !== '')
                    <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-sm px-3 py-2">
                        {{ $chatSoftError }}
                    </div>
                @endif

                @if($canSendMoreTurns)
                    {{-- Hand-rolled input instead of flux:input so we can force a visible border at rest
                         (Flux's default only draws on focus, per user feedback 2026-09-23) and a solid
                         zinc-900 text color instead of the near-invisible zinc-500 default. --}}
                    <form wire:submit.prevent="sendCustomerMessage" class="flex gap-2">
                        <input
                            type="text"
                            wire:model="customerInput"
                            placeholder="{{ __('Reply as the customer…') }}"
                            class="flex-1 rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition"
                            data-test="customer-input"
                            @if($isAiTyping) disabled @endif
                        />
                        <flux:button type="submit" variant="primary" class="cursor-pointer" data-test="customer-send" :disabled="$isAiTyping">
                            {{ __('Send') }}
                        </flux:button>
                    </form>
                @else
                    <div class="rounded-lg bg-zinc-50 border border-zinc-200 text-zinc-600 text-sm px-3 py-2 text-center">
                        {{ __('Chat cap reached for the demo — connect a real page to keep going.') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4">
                    <flux:button
                        wire:click="completeAndConnect"
                        variant="primary"
                        class="w-full"
                        data-test="cta-connect"
                    >
                        {{ __('Love it? Connect a real page') }}
                    </flux:button>
                    <flux:button
                        wire:click="completeAndTweak"
                        variant="outline"
                        class="w-full"
                        data-test="cta-tweak"
                    >
                        {{ __('Tweak my AI') }}
                    </flux:button>
                </div>

                <div class="text-center text-xs text-zinc-500 pt-2">
                    {{ __('Something feel off?') }}
                    <a href="mailto:omareltak7@gmail.com?subject=OT1-Pro%20setup%20help" class="underline underline-offset-4 hover:text-zinc-800">
                        {{ __('Email Omar') }}
                    </a>
                </div>
            </div>

            {{-- Fire chat-updated event so Alpine scrolls the transcript to
                 the bottom after every Livewire re-render. --}}
            @script
                <script>
                    $wire.on('chat-updated', () => window.dispatchEvent(new CustomEvent('chat-updated')));
                </script>
            @endscript
        @endif
    </div>
</div>
