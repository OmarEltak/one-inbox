<div class="flex h-full flex-col" x-data="{
    isNearBottom: true,
    showNewMessageBadge: false,
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
    }
}" x-init="scrollToBottom()" @message-sent.window="scrollToBottom()"
   x-on:livewire:morph.window="onNewContent()">

    {{-- Header --}}
    <div class="px-6 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.07);">
        <div class="flex items-center gap-3">
            <div class="size-9 rounded-xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, #7C3AED, #06B6D4); box-shadow: 0 0 16px rgba(124,58,237,0.35);">
                <svg class="size-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-white">{{ __('Marketing & Analytics Assistant') }}</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="size-1.5 rounded-full bg-green-400"></span>
                    <span class="text-xs text-white/35">{{ __('Manages campaigns, outreach & analytics') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Messages Area --}}
    <div class="flex-1 overflow-y-auto px-6 py-4" x-ref="chatContainer" @scroll.debounce.50ms="checkScroll()">
        {{-- New messages badge --}}
        <div x-show="showNewMessageBadge" x-transition class="sticky top-2 z-10 flex justify-center">
            <button @click="scrollToBottom(); showNewMessageBadge = false"
                    class="rounded-full bg-[#8b5cf6] px-4 py-1.5 text-xs font-medium text-white shadow-lg cursor-pointer hover:bg-purple-500">
                New messages
            </button>
        </div>

        @if(empty($messages))
            {{-- Welcome state --}}
            <div class="flex h-full flex-col items-center justify-center text-center py-12 max-w-xl mx-auto">
                {{-- AI Avatar --}}
                <div class="size-20 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#8b5cf6] flex items-center justify-center mb-6 shadow-lg shadow-purple-500/20">
                    <svg class="size-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-white/80 mb-2">{{ __('Marketing & Analytics Assistant') }}</h2>
                <p class="text-white/40 text-sm mb-1">{{ __('Powered by AI') }}</p>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#1e2536] text-xs text-white/40 mb-8">
                    <span class="size-1.5 rounded-full bg-green-400"></span>
                    Gemini Pro
                </span>

                {{-- Welcome message box --}}
                <div class="w-full rounded-xl aio-card p-5 mb-8 text-left">
                    <p class="text-sm text-white/80 font-medium mb-3">{{ __('I can help you with:') }}</p>
                    <ul class="space-y-2 text-sm text-white/40">
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-[#3b82f6]"></span>
                            {{ __('Analyzing campaign performance and reply rates') }}
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-[#8b5cf6]"></span>
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

                {{-- Quick action chips --}}
                <div class="grid grid-cols-2 gap-3 w-full">
                    <button
                        wire:click="$set('message', 'Show me all my campaigns and their performance')"
                        class="flex items-center gap-3 rounded-xl aio-card p-3 text-left hover:border-[#3b82f6] transition-colors cursor-pointer group"
                    >
                        <div class="rounded-lg bg-[#3b82f6]/10 p-2 group-hover:bg-[#3b82f6]/20 transition-colors">
                            <svg class="size-4 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-white/80">Campaign overview</p>
                            <p class="text-xs text-white/40">Performance & stats</p>
                        </div>
                    </button>

                    <button
                        wire:click="$set('message', 'Send a promotional message to all hot leads')"
                        class="flex items-center gap-3 rounded-xl aio-card p-3 text-left hover:border-[#8b5cf6] transition-colors cursor-pointer group"
                    >
                        <div class="rounded-lg bg-[#8b5cf6]/10 p-2 group-hover:bg-[#8b5cf6]/20 transition-colors">
                            <svg class="size-4 text-[#8b5cf6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-white/80">Blast hot leads</p>
                            <p class="text-xs text-white/40">Targeted outreach</p>
                        </div>
                    </button>

                    <button
                        wire:click="$set('message', 'Analyze my performance this week and suggest improvements')"
                        class="flex items-center gap-3 rounded-xl aio-card p-3 text-left hover:border-green-500 transition-colors cursor-pointer group"
                    >
                        <div class="rounded-lg bg-green-500/10 p-2 group-hover:bg-green-500/20 transition-colors">
                            <svg class="size-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-white/80">Weekly analysis</p>
                            <p class="text-xs text-white/40">Insights & tips</p>
                        </div>
                    </button>

                    <button
                        wire:click="$set('message', 'Who are my top 5 hottest leads right now?')"
                        class="flex items-center gap-3 rounded-xl aio-card p-3 text-left hover:border-orange-500 transition-colors cursor-pointer group"
                    >
                        <div class="rounded-lg bg-orange-500/10 p-2 group-hover:bg-orange-500/20 transition-colors">
                            <svg class="size-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-white/80">Hot leads</p>
                            <p class="text-xs text-white/40">Top opportunities</p>
                        </div>
                    </button>
                </div>
            </div>
        @else
            <div class="mx-auto max-w-3xl space-y-4">
                @foreach($messages as $msg)
                    @if($msg['role'] === 'user')
                        <div class="flex justify-end">
                            <div class="max-w-[80%] rounded-2xl rounded-br-md bg-[#8b5cf6] px-4 py-2.5 text-sm text-white">
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
                                <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-[#3b82f6] to-[#8b5cf6]">
                                    <svg class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="max-w-[80%] rounded-2xl rounded-bl-md bg-[#161b27] border border-white/[0.07] px-4 py-2.5 text-sm text-white/80">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Loading indicator --}}
                <div wire:loading wire:target="sendMessage" class="flex justify-start gap-2">
                    <div class="mt-1 flex-shrink-0">
                        <div class="flex size-7 items-center justify-center rounded-full bg-gradient-to-br from-[#3b82f6] to-[#8b5cf6]">
                            <svg class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="rounded-2xl rounded-bl-md bg-[#161b27] border border-white/[0.07] px-4 py-3">
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
                        <p class="text-xs text-white/60 truncate">{{ $pendingActionSummary }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            wire:click="cancelAction"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white/50 hover:text-white/80 hover:bg-white/5 transition-colors cursor-pointer"
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

    {{-- Input Bar --}}
    <div class="border-t border-white/[0.07] px-6 py-4">
        <div class="mx-auto max-w-3xl">
            @if($attachment)
                <div class="mb-2 flex items-center gap-2 rounded-lg bg-[#161b27] border border-white/[0.07] px-3 py-2">
                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                        <img src="{{ $attachment->temporaryUrl() }}" class="h-12 w-12 rounded object-cover" alt="Preview" />
                    @else
                        <flux:icon name="document" class="h-8 w-8 text-white/40" />
                    @endif
                    <span class="flex-1 truncate text-sm text-white/80">{{ $attachment->getClientOriginalName() }}</span>
                    <button type="button" wire:click="removeAttachment" class="text-white/40 hover:text-red-400 cursor-pointer">
                        <flux:icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
            @endif
            <form wire:submit="sendMessage" class="flex items-end gap-2" x-data="emojiPicker('message')">
                <input type="file" wire:model="attachment" class="hidden" x-ref="fileInput"
                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" />
                <button type="button" @click="$refs.fileInput.click()" class="flex-shrink-0 text-white/40 hover:text-white/80 cursor-pointer p-1 mb-1.5 transition-colors">
                    <flux:icon name="paper-clip" class="h-5 w-5" />
                </button>
                <button type="button" x-ref="emojiBtn" @click="togglePicker()" class="flex-shrink-0 text-white/40 hover:text-white/80 cursor-pointer p-1 mb-1.5 transition-colors">
                    <flux:icon name="face-smile" class="h-5 w-5" />
                </button>
                <div class="flex-1" x-ref="textInput">
                    <flux:textarea
                        wire:model="message"
                        placeholder="{{ __('Ask about your analytics...') }}"
                        autocomplete="off"
                        wire:loading.attr="disabled"
                        rows="1"
                        class="resize-none max-h-32"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage() }"
                        x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"
                    />
                </div>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="mb-0.5">
                    <flux:icon name="paper-airplane" variant="micro" class="size-4" />
                </flux:button>
            </form>
        </div>
    </div>
</div>
