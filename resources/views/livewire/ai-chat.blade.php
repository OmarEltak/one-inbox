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
    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <flux:icon name="sparkles" variant="outline" class="size-5 text-purple-500" />
            <flux:heading size="lg">{{ __('AI Chat') }}</flux:heading>
        </div>
        <flux:text class="mt-1 text-sm">{{ __('Ask questions about your business analytics') }}</flux:text>
    </div>

    {{-- Messages Area --}}
    <div class="flex-1 overflow-y-auto px-6 py-4" x-ref="chatContainer" @scroll.debounce.50ms="checkScroll()">
        {{-- New messages badge --}}
        <div x-show="showNewMessageBadge" x-transition class="sticky top-2 z-10 flex justify-center">
            <button @click="scrollToBottom(); showNewMessageBadge = false"
                    class="rounded-full bg-purple-600 px-4 py-1.5 text-xs font-medium text-white shadow-lg cursor-pointer hover:bg-purple-700">
                New messages
            </button>
        </div>
        @if(empty($messages))
            <div class="flex h-full flex-col items-center justify-center text-center">
                <flux:icon name="sparkles" variant="outline" class="mb-4 size-12 text-zinc-300 dark:text-zinc-600" />
                <flux:heading size="lg" class="text-zinc-400 dark:text-zinc-500">{{ __('Ask me anything about your business') }}</flux:heading>
                <flux:text class="mt-2 max-w-md text-zinc-400 dark:text-zinc-500">
                    {{ __('Try: "How many messages today?", "Who are my hottest leads?", or "Which platform gets the most conversations?"') }}
                </flux:text>
            </div>
        @else
            <div class="mx-auto max-w-3xl space-y-4">
                @foreach($messages as $msg)
                    @if($msg['role'] === 'user')
                        {{-- User message --}}
                        <div class="flex justify-end">
                            <div class="max-w-[80%] rounded-2xl rounded-br-md bg-purple-600 px-4 py-2.5 text-sm text-white">
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
                        {{-- AI message --}}
                        <div class="flex justify-start gap-2">
                            <div class="mt-1 flex-shrink-0">
                                <div class="flex size-7 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <flux:icon name="sparkles" variant="micro" class="size-4 text-purple-500" />
                                </div>
                            </div>
                            <div class="max-w-[80%] rounded-2xl rounded-bl-md bg-zinc-100 px-4 py-2.5 text-sm text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Loading indicator --}}
                <div wire:loading wire:target="sendMessage" class="flex justify-start gap-2">
                    <div class="mt-1 flex-shrink-0">
                        <div class="flex size-7 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <flux:icon name="sparkles" variant="micro" class="size-4 text-purple-500" />
                        </div>
                    </div>
                    <div class="rounded-2xl rounded-bl-md bg-zinc-100 px-4 py-3 dark:bg-zinc-700">
                        <div class="flex items-center gap-1">
                            <div class="size-2 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.3s]"></div>
                            <div class="size-2 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.15s]"></div>
                            <div class="size-2 animate-bounce rounded-full bg-zinc-400"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Input Bar --}}
    <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
        <div class="mx-auto max-w-3xl">
            {{-- Attachment Preview --}}
            @if($attachment)
                <div class="mb-2 flex items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 dark:bg-zinc-800">
                    @if(str_starts_with($attachment->getMimeType(), 'image/'))
                        <img src="{{ $attachment->temporaryUrl() }}" class="h-12 w-12 rounded object-cover" alt="Preview" />
                    @else
                        <flux:icon name="document" class="h-8 w-8 text-zinc-400" />
                    @endif
                    <span class="flex-1 truncate text-sm text-zinc-600 dark:text-zinc-300">{{ $attachment->getClientOriginalName() }}</span>
                    <button type="button" wire:click="removeAttachment" class="text-zinc-400 hover:text-red-500 cursor-pointer">
                        <flux:icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
            @endif
            <form wire:submit="sendMessage" class="flex items-end gap-2" x-data="emojiPicker('message')">
                <input type="file" wire:model="attachment" class="hidden" x-ref="fileInput"
                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" />
                <button type="button" @click="$refs.fileInput.click()" class="flex-shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer p-1 mb-1.5">
                    <flux:icon name="paper-clip" class="h-5 w-5" />
                </button>
                <button type="button" x-ref="emojiBtn" @click="togglePicker()" class="flex-shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer p-1 mb-1.5">
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
