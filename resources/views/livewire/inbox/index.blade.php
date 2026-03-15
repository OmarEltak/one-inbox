<div class="flex h-full overflow-hidden"
     x-data="{
         init() {
             if (window.Echo) {
                 const teamId = @js(auth()->user()->currentTeam?->id);
                 if (teamId) {
                     window.Echo.private('team.' + teamId)
                         .listen('.message.received', (e) => { $wire.$refresh(); })
                         .listen('.ai.response', (e) => { $wire.$refresh(); })
                         .listen('.conversation.updated', (e) => { $wire.$refresh(); });
                 }
             }
         }
     }"
>
    {{-- Conversation List Sidebar --}}
    <div wire:poll.30s class="flex-shrink-0 border-e border-zinc-200 dark:border-zinc-700 flex flex-col w-full md:w-80 {{ $selectedConversationId ? 'hidden md:flex' : 'flex' }}">
        {{-- Filters --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700 p-3 space-y-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search conversations..." icon="magnifying-glass" size="sm" />

            <div class="flex gap-1 flex-wrap items-center">
                <flux:badge as="button" wire:click="setFilter('all')" :variant="$filter === 'all' ? 'solid' : 'outline'" color="zinc" size="sm">All</flux:badge>
                <flux:badge as="button" wire:click="setFilter('unread')" :variant="$filter === 'unread' ? 'solid' : 'outline'" color="red" size="sm">
                    Unread {{ $this->unreadCount > 0 ? "({$this->unreadCount})" : '' }}
                </flux:badge>
                <flux:badge as="button" wire:click="setFilter('mine')" :variant="$filter === 'mine' ? 'solid' : 'outline'" color="purple" size="sm">Mine</flux:badge>
                @if(!$pageId)
                <flux:badge as="button" wire:click="setFilter('facebook')" :variant="$filter === 'facebook' ? 'solid' : 'outline'" color="blue" size="sm">FB</flux:badge>
                <flux:badge as="button" wire:click="setFilter('instagram')" :variant="$filter === 'instagram' ? 'solid' : 'outline'" color="pink" size="sm">IG</flux:badge>
                <flux:badge as="button" wire:click="setFilter('whatsapp')" :variant="$filter === 'whatsapp' ? 'solid' : 'outline'" color="green" size="sm">WA</flux:badge>
                <flux:badge as="button" wire:click="setFilter('telegram')" :variant="$filter === 'telegram' ? 'solid' : 'outline'" color="cyan" size="sm">TG</flux:badge>
                @endif

                {{-- Label filters — collapsed into a dropdown --}}
                @php $activeLabel = collect(\App\Livewire\Inbox\Index::LABELS)->keys()->contains($filter) ? $filter : null; @endphp
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="inline-flex items-center gap-1 rounded-full border text-xs px-2 py-0.5 transition-colors {{ $activeLabel ? 'bg-zinc-700 border-zinc-500 text-white' : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                        {{ $activeLabel ? ucfirst($activeLabel) : 'Labels' }}
                        <svg class="w-3 h-3 transition-transform" x-bind:class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" @click.outside="open = false" class="absolute left-0 top-full mt-1 z-50 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg min-w-32 overflow-hidden">
                        <div class="p-1">
                            @if($activeLabel)
                                <button wire:click="setFilter('all')" @click="open = false" class="w-full text-left px-3 py-1.5 text-xs rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-500">Clear label</button>
                                <div class="border-t border-zinc-100 dark:border-zinc-700 my-1"></div>
                            @endif
                            @foreach(\App\Livewire\Inbox\Index::LABELS as $slug => $color)
                                <button wire:click="setFilter('{{ $slug }}')" @click="open = false" class="w-full text-left px-3 py-1.5 text-xs rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2 {{ $filter === $slug ? 'font-semibold' : '' }}">
                                    <span class="w-2 h-2 rounded-full bg-{{ $color }}-500 flex-shrink-0"></span>
                                    {{ ucfirst($slug) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Conversation List --}}
        <div class="flex-1 overflow-y-auto" x-data="{ loading: false }" x-on:scroll.debounce.150ms="
            if (!loading && $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 200) {
                @if($hasMoreConversations)
                    loading = true;
                    $wire.loadMoreConversations().then(() => { loading = false; });
                @endif
            }
        ">
            @forelse($this->conversations as $conversation)
                <button
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="w-full text-left p-3 border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors cursor-pointer {{ $selectedConversationId === $conversation->id ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}"
                >
                    <div class="flex items-start gap-3">
                        <div class="relative flex-shrink-0">
                            <flux:avatar :name="$conversation->contact?->name ?? 'Unknown'" size="sm" />
                            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-zinc-900 {{ match($conversation->platform) { 'facebook' => 'bg-blue-500', 'instagram' => 'bg-pink-500', 'whatsapp' => 'bg-green-500', 'telegram' => 'bg-cyan-500', default => 'bg-gray-400' } }}"></span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $conversation->contact?->name ?? 'Unknown' }}
                                </span>
                                <span class="text-xs text-zinc-500 flex-shrink-0 ml-2">
                                    {{ $conversation->last_message_at?->shortAbsoluteDiffForHumans() }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-0.5">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                    {{ $conversation->last_message_preview ?? 'No messages yet' }}
                                </p>
                                <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                                    @if($conversation->contact && $conversation->contact->lead_score > 0)
                                        <span class="text-xs font-bold {{ match(true) {
                                            $conversation->contact->lead_score >= 86 => 'text-red-500',
                                            $conversation->contact->lead_score >= 71 => 'text-orange-500',
                                            $conversation->contact->lead_score >= 51 => 'text-yellow-500',
                                            $conversation->contact->lead_score >= 26 => 'text-blue-500',
                                            default => 'text-gray-400',
                                        } }}">{{ $conversation->contact->lead_score }}</span>
                                    @endif
                                    @if($conversation->ai_paused)
                                        <flux:icon name="pause-circle" class="w-4 h-4 text-orange-500" title="AI Paused" />
                                    @else
                                        <flux:icon name="sparkles" class="w-3.5 h-3.5 text-green-500" title="AI Active" />
                                    @endif
                                    @if($conversation->assigned_to)
                                        <flux:avatar :name="$conversation->assignedUser?->name ?? '?'" size="xs" class="w-4 h-4 text-[8px]" title="Assigned to {{ $conversation->assignedUser?->name }}" />
                                    @endif
                                    @foreach(array_slice($conversation->labels ?? [], 0, 2) as $label)
                                        <span class="text-[9px] px-1 py-0.5 rounded font-medium bg-{{ \App\Livewire\Inbox\Index::LABELS[$label] ?? 'zinc' }}-100 text-{{ \App\Livewire\Inbox\Index::LABELS[$label] ?? 'zinc' }}-700 dark:bg-{{ \App\Livewire\Inbox\Index::LABELS[$label] ?? 'zinc' }}-900/30 dark:text-{{ \App\Livewire\Inbox\Index::LABELS[$label] ?? 'zinc' }}-400">{{ $label }}</span>
                                    @endforeach
                                    @if($conversation->unread_count > 0)
                                        <span class="bg-blue-500 text-white text-xs rounded-full px-1.5 py-0.5 min-w-[1.25rem] text-center">
                                            {{ $conversation->unread_count }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </button>
            @empty
                <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                    <flux:icon name="inbox" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-3" />
                    <flux:heading size="sm" class="text-zinc-500">No conversations yet</flux:heading>
                    <flux:text size="sm" class="text-zinc-400 mt-1">
                        Connect your social accounts to start receiving messages.
                    </flux:text>
                </div>
            @endforelse

            @if($hasMoreConversations)
                <div class="p-4 flex justify-center" wire:loading wire:target="loadMoreConversations">
                    <flux:icon name="arrow-path" class="w-4 h-4 text-zinc-400 animate-spin" />
                </div>
                <div class="p-1" wire:loading.remove wire:target="loadMoreConversations"></div>
            @endif
        </div>
    </div>

    {{-- Message Thread --}}
    <div class="flex-1 flex flex-col min-w-0 {{ $selectedConversationId ? 'flex' : 'hidden md:flex' }}">
        @if($this->selectedConversation)
            @php $conv = $this->selectedConversation; @endphp

            {{-- Thread Header --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 p-3 flex items-center gap-2 flex-wrap">
                <button wire:click="$set('selectedConversationId', null)" class="md:hidden cursor-pointer">
                    <flux:icon name="arrow-left" class="w-5 h-5" />
                </button>
                <flux:avatar :name="$conv->contact?->name ?? 'Unknown'" size="sm" />
                <div class="flex-1 min-w-0">
                    <flux:heading size="sm">{{ $conv->contact?->name ?? 'Unknown' }}</flux:heading>
                    <flux:text size="xs">
                        {{ ucfirst($conv->platform) }}
                        @if($conv->page) &middot; {{ $conv->page->name }} @endif
                    </flux:text>
                </div>

                {{-- Lead score --}}
                @if($conv->contact && $conv->contact->lead_score > 0)
                    <div x-data="{ showScoreHistory: false }" class="relative">
                        <button
                            @click="showScoreHistory = !showScoreHistory; if(showScoreHistory) { $wire.loadScoreHistory({{ $conv->contact->id }}) }"
                            class="flex items-center gap-1.5 px-2.5 py-1 rounded-full flex-shrink-0 cursor-pointer transition-colors {{ match(true) {
                                $conv->contact->lead_score >= 86 => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-200',
                                $conv->contact->lead_score >= 71 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 hover:bg-orange-200',
                                $conv->contact->lead_score >= 51 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 hover:bg-yellow-200',
                                $conv->contact->lead_score >= 26 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 hover:bg-blue-200',
                                default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200',
                            } }}"
                        >
                            <span class="text-xs font-bold">{{ $conv->contact->lead_score }}</span>
                            <span class="text-xs">{{ ucfirst($conv->contact->lead_status) }}</span>
                            <flux:icon name="chevron-down" class="w-3 h-3 transition-transform" x-bind:class="showScoreHistory && 'rotate-180'" />
                        </button>
                        <div
                            x-show="showScoreHistory"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            @click.outside="showScoreHistory = false"
                            class="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 z-50 overflow-hidden"
                        >
                            <div class="p-3 border-b border-zinc-100 dark:border-zinc-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-zinc-500">Score History</span>
                                    <span class="text-lg font-bold">{{ $conv->contact->lead_score }}/100</span>
                                </div>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div wire:loading wire:target="loadScoreHistory" class="px-3 py-4 text-center text-xs text-zinc-400">Loading...</div>
                                <div wire:loading.remove wire:target="loadScoreHistory">
                                    @forelse($scoreHistory as $event)
                                        <div class="px-3 py-2 border-b border-zinc-50 dark:border-zinc-700/50 last:border-0">
                                            <div class="flex items-start gap-2">
                                                <span class="text-xs font-bold mt-0.5 flex-shrink-0 {{ $event['score_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $event['score_change'] >= 0 ? '+' : '' }}{{ $event['score_change'] }}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-xs text-zinc-700 dark:text-zinc-300 truncate">{{ $event['reason'] }}</p>
                                                    <span class="text-xs text-zinc-400">{{ \Carbon\Carbon::parse($event['created_at'])->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-3 py-4 text-center text-xs text-zinc-400">No score events yet</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Assign to --}}
                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button @click="open = !open" wire:loading.attr="disabled" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs cursor-pointer transition-colors bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 disabled:opacity-50">
                        @if($conv->assignedUser)
                            <flux:avatar :name="$conv->assignedUser->name" size="xs" />
                            <span class="max-w-20 truncate">{{ $conv->assignedUser->name }}</span>
                        @else
                            <flux:icon name="user-plus" class="w-3.5 h-3.5" />
                            <span>Assign</span>
                        @endif
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg z-50 min-w-44 overflow-hidden">
                        <div class="p-1">
                            @foreach($teamMembers as $member)
                                <button
                                    wire:click="assignConversation({{ $conv->id }}, {{ $member->id }})"
                                    wire:loading.attr="disabled"
                                    @click="open = false"
                                    class="w-full text-left px-3 py-1.5 text-sm rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2 disabled:opacity-50 {{ $conv->assigned_to === $member->id ? 'font-semibold' : '' }}"
                                >
                                    <flux:avatar :name="$member->name" size="xs" />
                                    {{ $member->name }}
                                    @if($conv->assigned_to === $member->id)
                                        <flux:icon name="check" class="w-3 h-3 ml-auto text-green-500" />
                                    @endif
                                </button>
                            @endforeach
                            @if($conv->assigned_to)
                                <div class="border-t border-zinc-100 dark:border-zinc-700 mt-1 pt-1">
                                    <button wire:click="assignConversation({{ $conv->id }}, null)" wire:loading.attr="disabled" @click="open = false" class="w-full text-left px-3 py-1.5 text-sm rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-red-500 flex items-center gap-2 disabled:opacity-50">
                                        <flux:icon name="x-mark" class="w-3.5 h-3.5" />
                                        Unassign
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Labels picker --}}
                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button @click="open = !open" wire:loading.attr="disabled" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs cursor-pointer transition-colors bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 disabled:opacity-50">
                        <flux:icon name="tag" class="w-3.5 h-3.5" />
                        <span>Labels{{ count($conv->labels ?? []) > 0 ? ' (' . count($conv->labels) . ')' : '' }}</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" class="absolute right-0 top-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg z-50 min-w-36 overflow-hidden">
                        <div class="p-1">
                            @foreach(\App\Livewire\Inbox\Index::LABELS as $slug => $color)
                                <button
                                    wire:click="toggleLabel({{ $conv->id }}, '{{ $slug }}')"
                                    wire:loading.attr="disabled"
                                    class="w-full text-left px-3 py-1.5 text-sm rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2 disabled:opacity-50"
                                >
                                    <span class="w-2 h-2 rounded-full bg-{{ $color }}-500 flex-shrink-0"></span>
                                    {{ ucfirst($slug) }}
                                    @if(in_array($slug, $conv->labels ?? []))
                                        <flux:icon name="check" class="w-3 h-3 ml-auto text-green-500" />
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- AI pause/resume toggle --}}
                @if(auth()->user()->isHeadAdmin() || auth()->user()->hasPermission('ai-control'))
                    <button
                        wire:click="toggleAiPause({{ $conv->id }})"
                        wire:loading.attr="disabled"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full flex-shrink-0 cursor-pointer transition-colors disabled:opacity-50 {{ $conv->ai_paused ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 hover:bg-orange-200' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200' }}"
                        title="{{ $conv->ai_paused ? 'Resume AI' : 'Pause AI' }}"
                    >
                        @if($conv->ai_paused)
                            <flux:icon name="pause-circle" class="w-4 h-4" />
                            <span class="text-xs font-medium">AI Paused</span>
                        @else
                            <flux:icon name="sparkles" class="w-4 h-4" />
                            <span class="text-xs font-medium">AI Active</span>
                        @endif
                    </button>
                @else
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full flex-shrink-0 {{ $conv->ai_paused ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                        @if($conv->ai_paused)
                            <flux:icon name="pause-circle" class="w-4 h-4" />
                            <span class="text-xs font-medium">AI Paused</span>
                        @else
                            <flux:icon name="sparkles" class="w-4 h-4" />
                            <span class="text-xs font-medium">AI Active</span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3 flex flex-col" id="message-thread"
                 x-data="{
                     isNearBottom: true,
                     showNewMessageBadge: false,
                     checkScroll() {
                         this.isNearBottom = (this.$el.scrollHeight - this.$el.scrollTop - this.$el.clientHeight) < 100;
                         if (this.isNearBottom) this.showNewMessageBadge = false;
                     },
                     scrollToBottom() {
                         this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight; });
                     }
                 }"
                 x-init="scrollToBottom()"
                 @scroll.debounce.50ms="checkScroll()"
                 x-on:message-sent.window="scrollToBottom()"
                 x-on:conversation-selected.window="isNearBottom = true; scrollToBottom()"
                 x-on:livewire:morph.window="if (isNearBottom) { scrollToBottom() } else { showNewMessageBadge = true }">

                <div x-show="showNewMessageBadge" x-transition class="sticky top-2 z-10 flex justify-center">
                    <button @click="scrollToBottom(); showNewMessageBadge = false"
                            class="rounded-full bg-purple-600 px-4 py-1.5 text-xs font-medium text-white shadow-lg cursor-pointer hover:bg-purple-700">
                        New messages
                    </button>
                </div>

                <div wire:loading wire:target="selectConversation" class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <flux:icon name="arrow-path" class="w-8 h-8 text-zinc-400 animate-spin mx-auto mb-2" />
                        <flux:text size="sm" class="text-zinc-400">Loading messages...</flux:text>
                    </div>
                </div>

                <div wire:loading.remove wire:target="selectConversation" class="flex flex-col space-y-3 mt-auto">
                @if($hasOlderMessages)
                    <div class="text-center py-2">
                        <button wire:click="loadOlderMessages" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer">
                            <flux:icon name="arrow-path" class="w-4 h-4 inline animate-spin" wire:loading wire:target="loadOlderMessages" />
                            <span wire:loading.remove wire:target="loadOlderMessages">Load older messages</span>
                        </button>
                    </div>
                @endif
                @foreach($conv->messages as $message)
                    @if($message->isActivityNote())
                        <div class="flex justify-center my-1">
                            <span class="text-xs text-zinc-400 dark:text-zinc-500 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
                                {{ $message->content }}
                            </span>
                        </div>
                    @else
                    <div class="flex w-full {{ $message->isInbound() ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[80%] sm:max-w-[70%] rounded-2xl px-4 py-2 break-words {{ $message->isInbound()
                            ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100'
                            : ($message->isFromAi() ? 'bg-purple-500 text-white' : 'bg-blue-500 text-white')
                        }}" style="overflow-wrap: anywhere;">
                            @if($message->isFromAi())
                                <div class="flex items-center gap-1 mb-0.5">
                                    <flux:icon name="sparkles" class="w-3 h-3 opacity-70" />
                                    <span class="text-xs opacity-70">AI</span>
                                </div>
                            @elseif($message->isFromUser() && $message->sentByUser)
                                <div class="flex items-center gap-1 mb-0.5">
                                    <flux:icon name="user" class="w-3 h-3 opacity-70" />
                                    <span class="text-xs opacity-70">{{ $message->sentByUser->name }}</span>
                                </div>
                            @endif
                            @if($message->media_url)
                                @if($message->content_type === 'image' || str_starts_with($message->media_type ?? '', 'image/'))
                                    <img src="{{ $message->media_url }}" alt="Shared image" class="max-w-full rounded-lg mb-1 cursor-pointer" onclick="window.open(this.src, '_blank')" loading="lazy" />
                                @elseif($message->content_type === 'video' || str_starts_with($message->media_type ?? '', 'video/'))
                                    <video src="{{ $message->media_url }}" controls class="max-w-full rounded-lg mb-1" preload="metadata"></video>
                                @elseif($message->content_type === 'audio' || str_starts_with($message->media_type ?? '', 'audio/'))
                                    <audio src="{{ $message->media_url }}" controls class="w-full mb-1"></audio>
                                @else
                                    <a href="{{ $message->media_url }}" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded-lg mb-1 {{ $message->isInbound() ? 'bg-zinc-200 dark:bg-zinc-700' : 'bg-white/20' }}">
                                        <flux:icon name="document-arrow-down" class="w-5 h-5 flex-shrink-0" />
                                        <span class="text-sm truncate">{{ basename($message->media_url) }}</span>
                                    </a>
                                @endif
                            @endif
                            @if($message->content)
                                <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                            @elseif(! $message->media_url)
                                <p class="text-sm italic opacity-60">📎 Media</p>
                            @endif
                            <span class="text-xs opacity-60 mt-1 block">
                                {{ ($message->platform_sent_at ?? $message->created_at)->format('M j, g:i A') }}
                                @if($message->isOutbound() && $message->delivered_at) &middot; Delivered @endif
                                @if($message->isOutbound() && $message->read_at) &middot; Read @endif
                            </span>
                        </div>
                    </div>
                    @endif
                @endforeach
                </div>
            </div>

            {{-- Composer --}}
            <div class="border-t border-zinc-200 dark:border-zinc-700 p-3">
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

                <form wire:submit="sendMessage" class="flex items-end gap-2"
                      x-data="{
                          ...emojiPicker('messageText'),
                          showQuickReplies: false,
                          qrSearch: '',
                          quickReplies: @js($quickReplies->toArray()),
                          get filteredReplies() {
                              if (!this.qrSearch) return this.quickReplies;
                              const q = this.qrSearch.toLowerCase();
                              return this.quickReplies.filter(r => r.title.toLowerCase().includes(q) || r.content.toLowerCase().includes(q));
                          },
                          insertReply(content) {
                              $wire.set('messageText', content);
                              this.showQuickReplies = false;
                              this.qrSearch = '';
                          }
                      }"
                >
                    <input type="file" wire:model="attachment" class="hidden" x-ref="fileInput"
                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" />

                    <button type="button" @click="$refs.fileInput.click()" class="flex-shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer p-1 mb-1">
                        <flux:icon name="paper-clip" class="h-5 w-5" />
                    </button>

                    <button type="button" x-ref="emojiBtn" @click="togglePicker()" class="flex-shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 cursor-pointer p-1 mb-1">
                        <flux:icon name="face-smile" class="h-5 w-5" />
                    </button>

                    {{-- Quick replies button --}}
                    @if($quickReplies->isNotEmpty())
                        <div class="relative flex-shrink-0 mb-1" x-data>
                            <button type="button" @click="showQuickReplies = !showQuickReplies" class="p-1 text-zinc-400 hover:text-blue-500 cursor-pointer" title="Quick Replies">
                                <flux:icon name="bolt" class="h-5 w-5" />
                            </button>
                            <div
                                x-show="showQuickReplies"
                                @click.outside="showQuickReplies = false"
                                class="absolute bottom-full left-0 mb-2 w-72 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl z-50 overflow-hidden"
                            >
                                <div class="p-2 border-b border-zinc-100 dark:border-zinc-700">
                                    <input x-model="qrSearch" type="text" placeholder="Search quick replies..." class="w-full text-sm px-2 py-1 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 outline-none" />
                                </div>
                                <div class="max-h-56 overflow-y-auto">
                                    <template x-for="reply in filteredReplies" :key="reply.id">
                                        <button
                                            type="button"
                                            @click="insertReply(reply.content)"
                                            class="w-full text-left px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700 border-b border-zinc-50 dark:border-zinc-700/50 last:border-0"
                                        >
                                            <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200" x-text="reply.title"></p>
                                            <p class="text-xs text-zinc-400 truncate mt-0.5" x-text="reply.content"></p>
                                        </button>
                                    </template>
                                    <template x-if="filteredReplies.length === 0">
                                        <p class="text-xs text-zinc-400 text-center py-4">No quick replies found</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex-1" x-ref="textInput">
                        <flux:textarea
                            wire:model="messageText"
                            placeholder="Type a message..."
                            rows="1"
                            class="resize-none max-h-32 text-sm"
                            x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage() }"
                            x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 128) + 'px'"
                        />
                    </div>
                    <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane" class="mb-0.5" wire:loading.attr="disabled" />
                </form>
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center p-6">
                <flux:icon name="chat-bubble-left-right" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mb-4" />
                <flux:heading size="lg" class="text-zinc-500">Select a conversation</flux:heading>
                <flux:text class="text-zinc-400 mt-2">Choose a conversation from the sidebar to start messaging.</flux:text>
            </div>
        @endif
    </div>
</div>
