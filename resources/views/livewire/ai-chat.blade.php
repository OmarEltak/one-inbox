<div class="flex h-full flex-col" dir="ltr" x-data="{
    isNearBottom: true,
    showNewMessageBadge: false,
    pendingPollTimer: null,
    savedComposerSel: null,
    scrollToBottom() {
        $nextTick(() => {
            const el = $refs.chatContainer;
            if (el) el.scrollTop = el.scrollHeight;
        });
    },
    checkScroll() {
        const el = $refs.chatContainer;
        if (!el) return;
        this.isNearBottom = (el.scrollHeight - el.scrollTop - el.clientHeight) < 100;
        if (this.isNearBottom) this.showNewMessageBadge = false;
    },
    onNewContent() {
        if (this.isNearBottom) { this.scrollToBottom(); } else { this.showNewMessageBadge = true; }
    },
    // 2026-10-13: focus-loss fix. Even with skipRender() guarding pollPendingTurns,
    // when an answer actually arrives the component morphs and the textarea loses
    // focus mid-typing (because sibling structure around it changes — the pending
    // bubble gets replaced by a real answer bubble, and the pendingCount <p>
    // appears/disappears). We save selection on 'livewire:morph' (before swap)
    // and restore it on 'livewire:morphed' (after swap). Only applies when the
    // user was actively typing in the composer textarea.
    preserveComposerFocus() {
        const ta = this.$refs.textInput?.querySelector('textarea');
        if (ta && document.activeElement === ta) {
            this.savedComposerSel = { start: ta.selectionStart, end: ta.selectionEnd };
        } else {
            this.savedComposerSel = null;
        }
    },
    restoreComposerFocus() {
        if (!this.savedComposerSel) return;
        const ta = this.$refs.textInput?.querySelector('textarea');
        if (ta && document.activeElement !== ta) {
            ta.focus();
            try { ta.setSelectionRange(this.savedComposerSel.start, this.savedComposerSel.end); } catch (_) {}
        }
        this.savedComposerSel = null;
    },
    stickToBottom() {
        // One scroll on init lands short: web fonts, images and long Arabic
        // lines keep growing the thread after first paint. Re-pin to the
        // bottom whenever the thread resizes, unless the user scrolled up.
        const content = $refs.chatContent;
        if (!content || !window.ResizeObserver) return;
        new ResizeObserver(() => {
            const el = $refs.chatContainer;
            if (el && this.isNearBottom) el.scrollTop = el.scrollHeight;
        }).observe(content);
    },
    // Reverb delivery backstop. Fires setInterval right after a user action
    // AND when the page loads with existing pending turns. Each tick calls
    // $wire.pollPendingTurns() which does one indexed DB query and swaps any
    // completed-but-not-broadcast placeholders. Interval auto-clears once
    // every pending turn resolves (DOM no longer has .pending class).
    // 2026-10-10: added after user reported typing dots stuck until page refresh.
    syncPendingWatcher() {
        if (this.pendingPollTimer) return; // already running
        const tick = () => {
            // Any .pending bubble still in the DOM? If yes, poll server; if no, stop.
            const stillPending = document.querySelector('[data-pending-bubble]');
            if (!stillPending) {
                clearInterval(this.pendingPollTimer);
                this.pendingPollTimer = null;
                return;
            }
            $wire.pollPendingTurns();
        };
        this.pendingPollTimer = setInterval(tick, 8000);
    }
}" x-init="scrollToBottom(); stickToBottom(); syncPendingWatcher()"
   @message-sent.window="scrollToBottom(); syncPendingWatcher()"
   @deep-analysis-ready-notify.window="
       (() => {
           if (!('Notification' in window)) return;
           const show = () => new Notification('{{ __('Analysis ready') }}', {
               body: '{{ __('Your Deep Analysis is complete. Open OT1-Pro to see the result.') }}',
               icon: '/favicon-32.png',
               tag: 'ot1-deep-analysis',
           });
           if (Notification.permission === 'granted') { show(); }
           else if (Notification.permission !== 'denied') { Notification.requestPermission().then(p => { if (p === 'granted') show(); }); }
       })()
   "
   x-on:livewire:morph.window="preserveComposerFocus(); onNewContent()"
   x-on:livewire:morphed.window="restoreComposerFocus()">

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-zinc-200">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, #059669, #06B6D4); box-shadow: 0 0 16px rgba(5,150,105,0.35);">
                <svg class="size-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-zinc-900">{{ __('Marketing & Analytics Assistant') }}</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="size-1.5 rounded-full bg-green-400"></span>
                    <span class="text-xs text-zinc-500">{{ __('Manages campaigns, outreach & analytics') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Messages Area --}}
    <div class="flex-1 min-h-0 overflow-y-auto px-6 py-4" x-ref="chatContainer" @scroll.debounce.50ms="checkScroll()">
        {{-- New messages badge --}}
        <div x-show="showNewMessageBadge" x-transition class="sticky top-2 z-10 flex justify-center">
            <button @click="scrollToBottom(); showNewMessageBadge = false"
                    class="rounded-full bg-[#10b981] px-4 py-1.5 text-xs font-medium text-white shadow-lg cursor-pointer hover:bg-emerald-500">
                {{ __('New messages') }}
            </button>
        </div>

        @if(empty($messages))
            {{-- Welcome state --}}
            <div class="flex h-full flex-col items-center justify-center text-center py-12 max-w-xl mx-auto">
                {{-- AI Avatar --}}
                <div class="size-20 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#10b981] flex items-center justify-center mb-6 shadow-lg shadow-emerald-500/20">
                    <svg class="size-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-zinc-800 mb-2">{{ __('Marketing & Analytics Assistant') }}</h2>
                <p class="text-zinc-500 text-sm mb-1">{{ __('Powered by AI') }}</p>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-zinc-100 text-xs text-zinc-500 mb-8">
                    <span class="size-1.5 rounded-full bg-green-400"></span>
                    {{ __('AI Assistant') }}
                </span>

                {{-- Welcome message box --}}
                <div class="w-full rounded-xl aio-card p-5 mb-8 text-start">
                    <p class="text-sm text-zinc-700 font-medium mb-3">{{ __('I can help you with:') }}</p>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-[#3b82f6]"></span>
                            {{ __('Analyzing campaign performance and reply rates') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-[#10b981]"></span>
                            {{ __('Sending targeted messages to leads by score or status') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-green-400"></span>
                            {{ __('Pausing or resuming campaigns') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-orange-400"></span>
                            {{ __('Identifying hottest leads and engagement opportunities') }}
                        </li>
                    </ul>
                    <p class="mt-3 text-xs text-amber-400/70 flex items-center gap-1.5">
                        <svg class="size-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                        {{ __('All write actions require your confirmation before executing') }}
                    </p>
                </div>

                {{-- Quick questions: real marketing work over the operator's own chats --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
                    @foreach($this->suggestions() as $i => $suggestion)
                        <button type="button" wire:click="useSuggestion({{ $i }})" wire:loading.attr="disabled"
                                class="rounded-xl aio-card p-3 text-start hover:border-[#10b981] transition-colors cursor-pointer">
                            <p class="text-sm font-medium text-zinc-800">{{ $suggestion['label'] }}</p>
                            <p class="mt-0.5 text-xs text-zinc-600 line-clamp-2">{{ $suggestion['prompt'] }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mx-auto max-w-3xl space-y-4" x-ref="chatContent">
                @foreach($messages as $msg)
                    @if($msg['role'] === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-[80%] rounded-2xl rounded-br-md bg-[#10b981] px-4 py-2.5 text-sm text-white" dir="auto">
                                @if(! empty($msg['media_url']))
                                    @if(str_starts_with($msg['media_type'] ?? '', 'image/'))
                                        <img src="{{ $msg['media_url'] }}" alt="Shared image" class="max-w-full rounded-lg mb-1 cursor-pointer" onclick="window.open(this.src, '_blank')" loading="lazy" />
                                    @else
                                        <a href="{{ $msg['media_url'] }}" target="_blank" class="flex items-center gap-2 rounded-lg bg-white/20 px-3 py-2 mb-1">
                                            <flux:icon name="document-arrow-down" class="w-5 h-5 flex-shrink-0" />
                                            <span class="text-sm truncate">{{ basename($msg['media_url']) }}</span>
                                        </a>
                                    @endif
                                @endif
                                {{ $msg['content'] }}
                            </div>
                        </div>
                    @else
                        <div class="flex justify-start gap-2">
                            <div class="mt-1 flex-shrink-0">
                                <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-[#3b82f6] to-[#10b981]">
                                    <svg class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="min-w-0 max-w-[85%] rounded-2xl rounded-bl-md bg-blue-50 border border-blue-100 px-4 py-2.5 text-sm text-zinc-900">
                                @if(! empty($msg['pending']))
                                    {{-- Server-side typing dots for pending Jobs so a user returning
                                         to the page mid-processing sees their turn is still working,
                                         not a dead empty bubble. data-pending-bubble is read by the
                                         Alpine syncPendingWatcher() on the root — when ≥1 bubble is
                                         present, Alpine polls $wire.pollPendingTurns() every 8s as a
                                         safety net for dropped Reverb AiChatTurnCompleted events. --}}
                                    <div class="flex items-center gap-1" data-pending-bubble>
                                        <div class="size-2 animate-bounce rounded-full bg-[#64748b] [animation-delay:-0.3s]"></div>
                                        <div class="size-2 animate-bounce rounded-full bg-[#64748b] [animation-delay:-0.15s]"></div>
                                        <div class="size-2 animate-bounce rounded-full bg-[#64748b]"></div>
                                    </div>
                                @else
                                    {{-- Markdown, raw HTML stripped: the AI's **bold**, lists and tables used to show as literal symbols.
                                         Tables get wrapped in .ai-md-table-wrap for horizontal scroll on mobile —
                                         the raw markdown-output table has no wrapper of its own and overflowed its parent. --}}
                                    @php
                                        $html = \Illuminate\Support\Str::markdown($msg['content'] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]);
                                        $html = preg_replace('/<table(\s|>)/', '<div class="ai-md-table-wrap"><table$1', $html);
                                        $html = str_replace('</table>', '</table></div>', $html);
                                    @endphp
                                    <div class="ai-md">{!! $html !!}</div>
                                    @if(! empty($msg['cached']) && ! empty($msg['command_id']))
                                        {{-- Cached-result affordance: user can see this was reused
                                             and can force a fresh re-run (full credits) if they need
                                             truly up-to-date insights. --}}
                                        <div class="mt-2 flex items-center gap-2 pt-2 border-t border-blue-100">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2v2a8 8 0 018 8h2A10 10 0 0012 2zM4 12a8 8 0 018-8V2A10 10 0 002 12h2zm16 0a8 8 0 01-8 8v2a10 10 0 0010-10h-2zm-8 8a8 8 0 01-8-8H2a10 10 0 0010 10v-2z"/>
                                                </svg>
                                                {{ __('cached · 0 credits') }}
                                            </span>
                                            <button type="button"
                                                wire:click="reRunCachedAnalysis({{ $msg['command_id'] }})"
                                                wire:loading.attr="disabled"
                                                class="text-[11px] text-zinc-600 hover:text-emerald-700 underline underline-offset-2 cursor-pointer">
                                                {{ __('Re-run with fresh data') }}
                                            </button>
                                        </div>
                                    @endif

                                    {{-- 2026-10-13: AI-offered deep pull. The ProcessAiChatTurn job
                                         extracted a [PULL_OFFER:<pid>:<n>] token from the response and
                                         stashed it on AiCommand.action_taken. Rough minute estimate:
                                         ~1.5s per chat (API + sleep) + 10s/batch delay ≈ 0.6 min per 40. --}}
                                    @if(! empty($msg['pull_offer']) && ! empty($msg['command_id']))
                                        @php
                                            $po = $msg['pull_offer'];
                                            $pullCount = (int) ($po['count'] ?? 0);
                                            $pullDone  = ! empty($po['done']);
                                            $estMin    = max(1, (int) ceil($pullCount * 1.5 / 60));
                                        @endphp
                                        <div class="mt-2 pt-2 border-t border-blue-100">
                                            @if($pullDone)
                                                <div class="flex items-start gap-2 text-xs text-emerald-800">
                                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <span>{{ __('Pulling :count more chats in the background. Track progress on the Connections page — ask again once it finishes.', ['count' => $po['confirmed_count'] ?? $pullCount]) }}</span>
                                                </div>
                                            @else
                                                <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                                                    <p class="text-xs text-amber-900">
                                                        {{ __('Want deeper context? I can pull :count more chats from this page. Takes ~:min min. No credits — the pull itself is free; credits only apply when you next ask an analysis question on the richer data.', ['count' => $pullCount, 'min' => $estMin]) }}
                                                    </p>
                                                    <div class="mt-2 flex items-center gap-2">
                                                        <button type="button"
                                                            wire:click="requestDeepPull({{ $msg['command_id'] }}, {{ (int) $po['page_id'] }}, {{ $pullCount }})"
                                                            wire:loading.attr="disabled"
                                                            class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 text-xs font-semibold cursor-pointer">
                                                            {{ __('Yes, pull :count more', ['count' => $pullCount]) }}
                                                        </button>
                                                        <button type="button"
                                                            wire:click="dismissDeepPullOffer({{ $msg['command_id'] }})"
                                                            wire:loading.attr="disabled"
                                                            class="text-xs text-zinc-600 hover:text-zinc-900 underline underline-offset-2 cursor-pointer">
                                                            {{ __('No, use what I have') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Loading indicator --}}
                {{-- .flex: plain wire:loading shows as inline-block, which stacked the dots under the avatar --}}
                <div wire:loading.flex wire:target="sendMessage" class="justify-start gap-2">
                    <div class="mt-1 flex-shrink-0">
                        <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-[#3b82f6] to-[#10b981]">
                            <svg class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="rounded-2xl rounded-bl-md bg-blue-50 border border-blue-100 px-4 py-3">
                        <div class="flex items-center gap-1">
                            <div class="size-2 animate-bounce rounded-full bg-[#64748b] [animation-delay:-0.3s]"></div>
                            <div class="size-2 animate-bounce rounded-full bg-[#64748b] [animation-delay:-0.15s]"></div>
                            <div class="size-2 animate-bounce rounded-full bg-[#64748b]"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Pending Action Confirmation Bar --}}
    @if($pendingAction)
        <div class="border-t border-amber-500/30 bg-amber-500/5 px-6 py-3">
            <div class="mx-auto max-w-3xl">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="size-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-amber-400 mb-0.5">{{ __('Confirm action') }}</p>
                        <p class="text-xs text-zinc-700 whitespace-pre-line break-words max-h-32 overflow-y-auto" dir="auto">{{ $pendingActionSummary }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            wire:click="cancelAction"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 transition-colors cursor-pointer"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            wire:click="confirmAction"
                            wire:loading.attr="disabled"
                            wire:target="confirmAction"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 hover:bg-amber-400 text-black transition-colors cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="confirmAction">{{ __('Yes, proceed') }}</span>
                            <span wire:loading wire:target="confirmAction">{{ __('Running...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('livewire.ai-chat.partials.confirmation-modal')

    {{-- Input Bar --}}
    <div class="border-t border-zinc-200 px-4 sm:px-6 py-4">
        <div class="mx-auto max-w-3xl">
            @if(! empty($messages))
                <div class="mb-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach($this->suggestions() as $i => $suggestion)
                        <button type="button" wire:click="useSuggestion({{ $i }})" wire:loading.attr="disabled"
                                class="shrink-0 rounded-full border border-zinc-300 bg-white px-3 py-1 text-xs font-medium text-zinc-700 hover:border-[#10b981] hover:text-zinc-900 cursor-pointer">
                            {{ $suggestion['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif
            {{-- Deep Analysis info banner. Shows while an analysis is pending.
                 Does NOT lock the composer (composer locks only on 3+ pending
                 inline turns — see CHAT_CONCURRENT_LIMIT).
                 2026-10-14: wire:poll.10s on the banner div itself calls
                 refreshDeepAnalysisBanner, which skipRender()s when still
                 running (keeps textarea focus) and lets Livewire re-render
                 (dropping the @if div) when the DeepAnalysis has completed
                 or failed. Scoping the poll to this div means NO polling
                 happens when banner is false. Fixes "banner stays stuck
                 after Reverb DeepAnalysisCompleted event drops". --}}
            @if($this->hasRunningDeepAnalysis())
                <div
                    wire:poll.10s="refreshDeepAnalysisBanner"
                    class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 flex items-start gap-3"
                >
                    <svg class="w-4 h-4 text-amber-600 animate-spin flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <div class="flex-1 text-sm">
                        <p class="font-medium text-amber-900">{{ __('Deep Analysis running in the background') }}</p>
                        <p class="text-amber-800/80 mt-0.5">{{ __("I'll notify you here when it's ready. You can keep chatting about other things in the meantime.") }}</p>
                    </div>
                </div>
            @endif
            @if($attachment)
                <div class="mb-2 flex items-center gap-2 rounded-lg bg-zinc-50 border border-zinc-200 px-3 py-2">
                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                        <img src="{{ $attachment->temporaryUrl() }}" class="h-12 w-12 rounded object-cover" alt="Preview" />
                    @else
                        <flux:icon name="document" class="h-8 w-8 text-zinc-400" />
                    @endif
                    <span class="flex-1 truncate text-sm text-zinc-700">{{ $attachment->getClientOriginalName() }}</span>
                    <button type="button" wire:click="removeAttachment" class="text-zinc-400 hover:text-red-400 cursor-pointer">
                        <flux:icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
            @endif
            <form wire:submit="sendMessage" class="flex items-end gap-2" x-data="emojiPicker('message')">
                <input type="file" wire:model="attachment" class="hidden" x-ref="fileInput"
                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" />
                <button type="button" @click="$refs.fileInput.click()" class="flex-shrink-0 text-zinc-400 hover:text-zinc-700 cursor-pointer p-1 mb-1.5 transition-colors">
                    <flux:icon name="paper-clip" class="h-5 w-5" />
                </button>
                <button type="button" x-ref="emojiBtn" @click="togglePicker()" class="flex-shrink-0 text-zinc-400 hover:text-zinc-700 cursor-pointer p-1 mb-1.5 transition-colors">
                    <flux:icon name="face-smile" class="h-5 w-5" />
                </button>
                @php
                    $chatLocked = $this->isChatLocked();
                    $pendingCount = $this->pendingChatTurnsCount();
                @endphp
                <div class="flex-1" x-ref="textInput">
                    <flux:textarea
                        wire:model="message"
                        placeholder="{{ $chatLocked ? __('Wait for an answer to arrive before sending more…') : __('Ask about your analytics...') }}"
                        dir="auto"
                        autocomplete="off"
                        wire:loading.attr="disabled"
                        :disabled="$chatLocked"
                        rows="1"
                        class="resize-none max-h-32 !text-zinc-900 dark:!text-zinc-900"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage() }"
                        x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"
                    />
                    @if($pendingCount > 0)
                        {{-- Live count driven by AiChatTurnCompleted broadcasts. --}}
                        <p class="mt-1 text-[11px] text-zinc-400">
                            {{ trans_choice('{1} 1 answer still arriving…|[2,*] :count answers still arriving…', $pendingCount, ['count' => $pendingCount]) }}
                        </p>
                    @endif
                </div>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" :disabled="$chatLocked" class="mb-0.5">
                    <flux:icon name="paper-airplane" variant="micro" class="size-4" />
                </flux:button>
            </form>
        </div>
    </div>
</div>
