<div wire:poll.30s
     x-data="{
         init() {
             if (window.Echo) {
                 const teamId = @js(auth()->user()->currentTeam?->id);
                 if (teamId) {
                     window.Echo.private('team.' + teamId)
                         .listen('.message.received', () => { $wire.$refresh(); })
                         .listen('.ai.response', () => { $wire.$refresh(); })
                         .listen('.conversation.updated', () => { $wire.$refresh(); });
                 }
             }
         }
     }">
    @if(! $stats)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <flux:icon name="building-office" class="mb-4 size-16 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('No team selected') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-400">{{ __('Create or join a team to get started.') }}</flux:text>
        </div>
    @else
        <div class="space-y-6">
            {{-- Welcome header --}}
            <div>
                <flux:heading size="xl">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
                <flux:text class="mt-1">{{ __("Here's what's happening with your business today.") }}</flux:text>
            </div>

            {{-- Top Stats Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Unread Messages --}}
                <a href="{{ route('inbox', ['filter' => 'unread']) }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-blue-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-blue-600">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Unread') }}</flux:text>
                        <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                            <flux:icon name="inbox" class="size-5 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['unreadCount'] }}</span>
                        <flux:text class="mt-1 text-xs">{{ __('conversations need attention') }}</flux:text>
                    </div>
                </a>

                {{-- Messages Today --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Messages Today') }}</flux:text>
                        <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                            <flux:icon name="chat-bubble-left-right" class="size-5 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['messagesToday'] }}</span>
                        <flux:text class="mt-1 text-xs">{{ $stats['totalMessages'] }} {{ __('total') }}</flux:text>
                    </div>
                </div>

                {{-- Total Contacts --}}
                <a href="{{ route('contacts.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-purple-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Contacts') }}</flux:text>
                        <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                            <flux:icon name="users" class="size-5 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['totalContacts'] }}</span>
                        @if($stats['newContactsWeek'] > 0)
                            <flux:text class="mt-1 text-xs text-green-600 dark:text-green-400">+{{ $stats['newContactsWeek'] }} {{ __('this week') }}</flux:text>
                        @else
                            <flux:text class="mt-1 text-xs">{{ __('total leads') }}</flux:text>
                        @endif
                    </div>
                </a>

                {{-- Conversations --}}
                <a href="{{ route('inbox') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-orange-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-orange-600">
                    <div class="flex items-center justify-between">
                        <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Conversations') }}</flux:text>
                        <div class="rounded-lg bg-orange-100 p-2 dark:bg-orange-900/30">
                            <flux:icon name="chat-bubble-left" class="size-5 text-orange-600 dark:text-orange-400" />
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['totalConversations'] }}</span>
                        @if($stats['newConversationsToday'] > 0)
                            <flux:text class="mt-1 text-xs text-green-600 dark:text-green-400">+{{ $stats['newConversationsToday'] }} {{ __('today') }}</flux:text>
                        @else
                            <flux:text class="mt-1 text-xs">{{ $stats['newConversationsWeek'] }} {{ __('this week') }}</flux:text>
                        @endif
                    </div>
                </a>
            </div>

            {{-- Middle Row: AI Performance + Platform Breakdown --}}
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- AI vs Human --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('AI Performance') }}</flux:heading>
                    @php
                        $totalResponses = $stats['aiMessages'] + $stats['humanMessages'];
                        $aiPercent = $totalResponses > 0 ? round(($stats['aiMessages'] / $totalResponses) * 100) : 0;
                    @endphp
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:icon name="sparkles" class="size-4 text-purple-500" />
                                <flux:text class="text-sm">{{ __('AI Responses') }}</flux:text>
                            </div>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($stats['aiMessages']) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full rounded-full bg-purple-500 transition-all" style="width: {{ $aiPercent }}%"></div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:icon name="user" class="size-4 text-blue-500" />
                                <flux:text class="text-sm">{{ __('Human Responses') }}</flux:text>
                            </div>
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($stats['humanMessages']) }}</span>
                        </div>
                        <div class="mt-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-700/50">
                            <flux:text class="text-center text-sm">
                                {{ __('AI handles') }} <span class="font-bold text-purple-600 dark:text-purple-400">{{ $aiPercent }}%</span> {{ __('of all responses') }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                {{-- Platform Breakdown --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Channels') }}</flux:heading>
                    @php
                        $platformColors = [
                            'facebook' => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-600 dark:text-blue-400'],
                            'instagram' => ['bg' => 'bg-pink-500', 'light' => 'bg-pink-100 dark:bg-pink-900/30', 'text' => 'text-pink-600 dark:text-pink-400'],
                            'whatsapp' => ['bg' => 'bg-green-500', 'light' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-400'],
                            'telegram' => ['bg' => 'bg-cyan-500', 'light' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-cyan-600 dark:text-cyan-400'],
                        ];
                        $platformTotal = array_sum($stats['platformStats']);
                    @endphp
                    @if($platformTotal > 0)
                        <div class="space-y-3">
                            @foreach($stats['platformStats'] as $platform => $count)
                                @php
                                    $colors = $platformColors[$platform] ?? ['bg' => 'bg-gray-500', 'light' => 'bg-gray-100 dark:bg-gray-800', 'text' => 'text-gray-600'];
                                    $percent = round(($count / $platformTotal) * 100);
                                @endphp
                                <div>
                                    <div class="mb-1 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="size-2.5 rounded-full {{ $colors['bg'] }}"></div>
                                            <flux:text class="text-sm font-medium">{{ ucfirst($platform) }}</flux:text>
                                        </div>
                                        <flux:text class="text-sm">{{ $count }} <span class="text-zinc-400">({{ $percent }}%)</span></flux:text>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div class="h-full rounded-full {{ $colors['bg'] }} transition-all" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="signal" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No conversations yet') }}</flux:text>
                            <a href="{{ route('connections.index') }}" wire:navigate class="mt-2 text-sm font-medium text-blue-500 hover:text-blue-600">{{ __('Connect a channel') }}</a>
                        </div>
                    @endif

                    {{-- Connected pages count --}}
                    <div class="mt-4 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-700/50">
                        <flux:text class="text-center text-sm">
                            <span class="font-bold">{{ $stats['connectedPages'] }}</span> {{ __('connected pages/bots') }}
                        </flux:text>
                    </div>
                </div>
            </div>

            {{-- Bottom Row: Hot Leads + Recent Conversations --}}
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- Hot Leads --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex items-center justify-between">
                        <flux:heading size="sm">{{ __('Hot Leads') }}</flux:heading>
                        <a href="{{ route('contacts.index') }}" wire:navigate class="text-xs font-medium text-blue-500 hover:text-blue-600">{{ __('View all') }}</a>
                    </div>
                    @if($stats['hotLeads']->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($stats['hotLeads'] as $lead)
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$lead->name" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $lead->name }}</p>
                                        <p class="text-xs text-zinc-500">{{ ucfirst($lead->lead_status) }}</p>
                                    </div>
                                    <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-bold {{ match(true) {
                                        $lead->lead_score >= 86 => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        $lead->lead_score >= 71 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        default => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    } }}">{{ $lead->lead_score }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <flux:icon name="fire" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No hot leads yet') }}</flux:text>
                        </div>
                    @endif
                </div>

                {{-- Recent Conversations --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex items-center justify-between">
                        <flux:heading size="sm">{{ __('Recent Conversations') }}</flux:heading>
                        <a href="{{ route('inbox') }}" wire:navigate class="text-xs font-medium text-blue-500 hover:text-blue-600">{{ __('Open inbox') }}</a>
                    </div>
                    @if($stats['recentConversations']->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($stats['recentConversations'] as $conv)
                                <a href="{{ route('inbox', ['conversation' => $conv->id]) }}" wire:navigate class="flex items-center gap-3 rounded-lg p-1 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <div class="relative flex-shrink-0">
                                        <flux:avatar :name="$conv->contact?->name ?? 'Unknown'" size="sm" />
                                        <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-white dark:border-zinc-800 {{ match($conv->platform) {
                                            'facebook' => 'bg-blue-500',
                                            'instagram' => 'bg-pink-500',
                                            'whatsapp' => 'bg-green-500',
                                            'telegram' => 'bg-cyan-500',
                                            default => 'bg-gray-400',
                                        } }}"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between">
                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $conv->contact?->name ?? 'Unknown' }}</p>
                                            <span class="flex-shrink-0 text-xs text-zinc-400">{{ $conv->last_message_at?->shortAbsoluteDiffForHumans() }}</span>
                                        </div>
                                        <p class="truncate text-xs text-zinc-500">{{ $conv->last_message_preview ?? 'No messages' }}</p>
                                    </div>
                                    @if($conv->unread_count > 0)
                                        <span class="flex-shrink-0 rounded-full bg-blue-500 px-1.5 py-0.5 text-xs text-white">{{ $conv->unread_count }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <flux:icon name="chat-bubble-left-right" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No conversations yet') }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Lead Status Breakdown --}}
            @if(! empty($stats['leadStats']))
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Lead Pipeline') }}</flux:heading>
                    @php
                        $statusColors = [
                            'new' => 'bg-gray-500',
                            'cold' => 'bg-blue-500',
                            'warm' => 'bg-yellow-500',
                            'hot' => 'bg-orange-500',
                            'converted' => 'bg-green-500',
                            'lost' => 'bg-red-500',
                        ];
                        $leadTotal = array_sum($stats['leadStats']);
                    @endphp
                    <div class="flex gap-1 overflow-hidden rounded-full">
                        @foreach($stats['leadStats'] as $status => $count)
                            @php $pct = $leadTotal > 0 ? round(($count / $leadTotal) * 100) : 0; @endphp
                            <div class="{{ $statusColors[$status] ?? 'bg-gray-400' }} h-3 transition-all" style="width: {{ max($pct, 2) }}%" title="{{ ucfirst($status) }}: {{ $count }}"></div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex flex-wrap gap-4">
                        @foreach($stats['leadStats'] as $status => $count)
                            <div class="flex items-center gap-1.5">
                                <div class="size-2 rounded-full {{ $statusColors[$status] ?? 'bg-gray-400' }}"></div>
                                <flux:text class="text-xs">{{ ucfirst($status) }}: {{ $count }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="grid gap-3 sm:grid-cols-3">
                <a href="{{ route('inbox') }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition-colors hover:border-blue-300 hover:bg-blue-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-blue-600 dark:hover:bg-blue-900/10">
                    <div class="rounded-lg bg-blue-100 p-2.5 dark:bg-blue-900/30">
                        <flux:icon name="inbox" class="size-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Open Inbox') }}</p>
                        <p class="text-xs text-zinc-500">{{ __('View and reply to messages') }}</p>
                    </div>
                </a>
                <a href="{{ route('ai-chat') }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition-colors hover:border-purple-300 hover:bg-purple-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600 dark:hover:bg-purple-900/10">
                    <div class="rounded-lg bg-purple-100 p-2.5 dark:bg-purple-900/30">
                        <flux:icon name="sparkles" class="size-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('AI Chat') }}</p>
                        <p class="text-xs text-zinc-500">{{ __('Ask about your business') }}</p>
                    </div>
                </a>
                <a href="{{ route('connections.index') }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition-colors hover:border-green-300 hover:bg-green-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-green-600 dark:hover:bg-green-900/10">
                    <div class="rounded-lg bg-green-100 p-2.5 dark:bg-green-900/30">
                        <flux:icon name="link" class="size-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Connections') }}</p>
                        <p class="text-xs text-zinc-500">{{ __('Manage your channels') }}</p>
                    </div>
                </a>
            </div>
        </div>
    @endif
</div>
