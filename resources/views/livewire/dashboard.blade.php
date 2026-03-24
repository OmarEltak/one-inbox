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
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="size-20 rounded-2xl flex items-center justify-center mb-6"
                 style="background: linear-gradient(135deg, rgba(124,58,237,0.2), rgba(6,182,212,0.2)); border: 1px solid rgba(124,58,237,0.3);">
                <svg class="size-10 text-[#7C3AED]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white/80 mb-2">{{ __('No team selected') }}</h2>
            <p class="text-white/40 text-sm">{{ __('Create or join a team to get started.') }}</p>
        </div>
    @else
        <div class="p-6 space-y-6">

            {{-- ── Welcome Header ── --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ __('Welcome to All in One') }}
                        <span class="inline-block text-xl ml-1" style="filter: drop-shadow(0 0 8px rgba(250,204,21,0.6));">✦</span>
                    </h1>
                    <p class="mt-1 text-sm text-white/40">{{ __("Here's your business overview for today.") }}</p>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-xs text-white/30">
                    <div class="size-1.5 rounded-full bg-green-400 animate-pulse"></div>
                    {{ __('Live') }}
                </div>
            </div>

            {{-- ── Top 4 Stat Cards (Figma layout) ── --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                {{-- Total Messages --}}
                <a href="{{ route('inbox') }}" wire:navigate
                   class="aio-card aio-stat-purple rounded-2xl p-5 block hover:scale-[1.01] transition-transform">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-white/40 uppercase tracking-widest">{{ __('Total Messages') }}</p>
                        <div class="aio-icon-purple rounded-xl p-2">
                            <svg class="size-4 text-[#C27AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-3xl font-bold text-white">{{ number_format($stats['totalMessages']) }}</span>
                            <p class="mt-1 text-xs text-white/35">{{ $stats['messagesToday'] }} {{ __('today') }}</p>
                        </div>
                        <svg class="h-10 w-20 text-[#7C3AED]/40" viewBox="0 0 80 40" fill="none">
                            <polyline points="0,35 13,28 26,30 39,18 52,22 65,12 80,8" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                </a>

                {{-- Unread --}}
                <a href="{{ route('inbox', ['filter' => 'unread']) }}" wire:navigate
                   class="aio-card aio-stat-cyan rounded-2xl p-5 block hover:scale-[1.01] transition-transform">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-white/40 uppercase tracking-widest">{{ __('Unread') }}</p>
                        <div class="aio-icon-cyan rounded-xl p-2">
                            <svg class="size-4 text-[#06B6D4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-3xl font-bold text-white">{{ $stats['unreadCount'] }}</span>
                            <p class="mt-1 text-xs text-white/35">{{ __('need attention') }}</p>
                        </div>
                        <svg class="h-10 w-20 text-[#06B6D4]/40" viewBox="0 0 80 40" fill="none">
                            <polyline points="0,38 13,30 26,35 39,20 52,25 65,14 80,10" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                </a>

                {{-- Contacts --}}
                <a href="{{ route('contacts.index') }}" wire:navigate
                   class="aio-card aio-stat-green rounded-2xl p-5 block hover:scale-[1.01] transition-transform">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-white/40 uppercase tracking-widest">{{ __('Contacts') }}</p>
                        <div class="aio-icon-green rounded-xl p-2">
                            <svg class="size-4 text-[#00D492]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-3xl font-bold text-white">{{ $stats['totalContacts'] }}</span>
                            @if($stats['newContactsWeek'] > 0)
                                <p class="mt-1 text-xs text-[#00D492]">+{{ $stats['newContactsWeek'] }} {{ __('this week') }}</p>
                            @else
                                <p class="mt-1 text-xs text-white/35">{{ __('total leads') }}</p>
                            @endif
                        </div>
                        <svg class="h-10 w-20 text-[#00D492]/40" viewBox="0 0 80 40" fill="none">
                            <polyline points="0,32 13,25 26,28 39,15 52,18 65,10 80,6" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                </a>

                {{-- Conversations --}}
                <a href="{{ route('inbox') }}" wire:navigate
                   class="aio-card aio-stat-blue rounded-2xl p-5 block hover:scale-[1.01] transition-transform">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-semibold text-white/40 uppercase tracking-widest">{{ __('Conversations') }}</p>
                        <div class="aio-icon-blue rounded-xl p-2">
                            <svg class="size-4 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="text-3xl font-bold text-white">{{ $stats['totalConversations'] }}</span>
                            @if($stats['newConversationsToday'] > 0)
                                <p class="mt-1 text-xs text-[#3b82f6]">+{{ $stats['newConversationsToday'] }} {{ __('today') }}</p>
                            @else
                                <p class="mt-1 text-xs text-white/35">{{ $stats['newConversationsWeek'] }} {{ __('this week') }}</p>
                            @endif
                        </div>
                        <svg class="h-10 w-20 text-[#3b82f6]/40" viewBox="0 0 80 40" fill="none">
                            <polyline points="0,36 13,28 26,33 39,16 52,20 65,11 80,7" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                </a>
            </div>

            {{-- ── Platform Overview + AI Performance ── --}}
            <div class="grid gap-4 lg:grid-cols-2">

                {{-- Platform Overview (Figma grid style) --}}
                <div class="aio-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-semibold text-white">{{ __('Platform Overview') }}</h3>
                        <a href="{{ route('connections.index') }}" wire:navigate
                           class="text-xs font-medium text-[#C27AFF] hover:text-purple-300 transition-colors">{{ __('View All') }}</a>
                    </div>
                    @php
                        $platformConfig = [
                            'facebook'  => ['label' => 'Facebook',  'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)',  'short' => 'F'],
                            'instagram' => ['label' => 'Instagram', 'color' => '#EC4899', 'bg' => 'rgba(236,72,153,0.12)',  'short' => 'IG'],
                            'whatsapp'  => ['label' => 'WhatsApp',  'color' => '#22C55E', 'bg' => 'rgba(34,197,94,0.12)',   'short' => 'WA'],
                            'telegram'  => ['label' => 'Telegram',  'color' => '#06B6D4', 'bg' => 'rgba(6,182,212,0.12)',   'short' => 'TG'],
                            'tiktok'    => ['label' => 'TikTok',    'color' => '#F43F5E', 'bg' => 'rgba(244,63,94,0.12)',   'short' => 'TT'],
                            'snapchat'  => ['label' => 'Snapchat',  'color' => '#FACC15', 'bg' => 'rgba(250,204,21,0.12)',  'short' => 'SC'],
                            'email'     => ['label' => 'Email',     'color' => '#F59E0B', 'bg' => 'rgba(245,158,11,0.12)',  'short' => 'EM'],
                        ];
                        $platformTotal = array_sum($stats['platformStats']);
                    @endphp
                    @if($platformTotal > 0)
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($stats['platformStats'] as $platform => $count)
                                @php $cfg = $platformConfig[$platform] ?? ['label' => ucfirst($platform), 'color' => '#6b7280', 'bg' => 'rgba(107,114,128,0.12)', 'short' => strtoupper(substr($platform,0,1))]; @endphp
                                <div class="flex items-center gap-3 rounded-xl p-3 transition-colors"
                                     style="background: {{ $cfg['bg'] }}; border: 1px solid {{ $cfg['color'] }}22;">
                                    <div class="size-9 rounded-xl flex items-center justify-center text-xs font-bold flex-shrink-0"
                                         style="background: {{ $cfg['color'] }}22; color: {{ $cfg['color'] }}; border: 1px solid {{ $cfg['color'] }}33;">
                                        {{ $cfg['short'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-white/80">{{ $cfg['label'] }}</p>
                                        <p class="text-xs text-white/40">{{ $count }} {{ __('messages') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <svg class="mb-3 size-10 text-white/10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                            <p class="text-sm text-white/30 mb-3">{{ __('No conversations yet') }}</p>
                            <a href="{{ route('connections.index') }}" wire:navigate
                               class="text-xs font-medium text-[#C27AFF] hover:text-purple-300">{{ __('Connect a channel →') }}</a>
                        </div>
                    @endif
                    <div class="mt-4 rounded-xl p-3 text-center" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <span class="text-sm font-bold text-white">{{ $stats['connectedPages'] }}</span>
                        <span class="text-xs text-white/35 ml-1">{{ __('connected pages') }}</span>
                    </div>
                </div>

                {{-- AI Performance --}}
                <div class="aio-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-semibold text-white">{{ __('AI Performance') }}</h3>
                        @php $aiOn = auth()->user()->currentTeam?->ai_enabled; @endphp
                        <a href="{{ route('settings.ai') }}" wire:navigate class="flex items-center gap-1.5 text-xs">
                            <span class="size-1.5 rounded-full {{ $aiOn ? 'bg-green-400' : 'bg-red-400' }}"></span>
                            <span class="text-white/40">{{ $aiOn ? 'AI ON' : 'AI OFF' }}</span>
                        </a>
                    </div>
                    @php
                        $totalResponses = $stats['aiMessages'] + $stats['humanMessages'];
                        $aiPercent = $totalResponses > 0 ? round(($stats['aiMessages'] / $totalResponses) * 100) : 0;
                        $humanPercent = 100 - $aiPercent;
                    @endphp
                    <div class="space-y-5">
                        {{-- AI bar --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="size-6 rounded-lg flex items-center justify-center" style="background: rgba(124,58,237,0.15);">
                                        <svg class="size-3.5 text-[#C27AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-white/70">{{ __('AI Responses') }}</span>
                                </div>
                                <span class="text-sm font-bold text-white">{{ number_format($stats['aiMessages']) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.06);">
                                <div class="h-full rounded-full transition-all duration-700"
                                     style="width: {{ $aiPercent }}%; background: linear-gradient(90deg, #7C3AED, #C27AFF);"></div>
                            </div>
                        </div>

                        {{-- Human bar --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="size-6 rounded-lg flex items-center justify-center" style="background: rgba(59,130,246,0.15);">
                                        <svg class="size-3.5 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm text-white/70">{{ __('Human Responses') }}</span>
                                </div>
                                <span class="text-sm font-bold text-white">{{ number_format($stats['humanMessages']) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.06);">
                                <div class="h-full rounded-full transition-all duration-700"
                                     style="width: {{ $humanPercent }}%; background: linear-gradient(90deg, #2563EB, #3b82f6);"></div>
                            </div>
                        </div>

                        {{-- Summary chip --}}
                        <div class="rounded-xl p-4 text-center" style="background: rgba(124,58,237,0.08); border: 1px solid rgba(124,58,237,0.2);">
                            <p class="text-sm text-white/60">
                                {{ __('AI handles') }}
                                <span class="font-bold text-[#C27AFF] text-base">{{ $aiPercent }}%</span>
                                {{ __('of all responses') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Recent Messages + Hot Leads ── --}}
            <div class="grid gap-4 lg:grid-cols-2">

                {{-- Recent Messages (Figma style) --}}
                <div class="aio-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-semibold text-white">{{ __('Recent Messages') }}</h3>
                        <a href="{{ route('inbox') }}" wire:navigate
                           class="text-xs font-medium text-[#C27AFF] hover:text-purple-300 transition-colors">{{ __('Open Inbox') }}</a>
                    </div>
                    @if($stats['recentConversations']->isNotEmpty())
                        <div class="space-y-1">
                            @foreach($stats['recentConversations'] as $conv)
                                @php
                                    $platformColors = [
                                        'facebook' => '#3b82f6', 'instagram' => '#EC4899',
                                        'whatsapp' => '#22C55E', 'telegram' => '#06B6D4',
                                        'tiktok' => '#F43F5E', 'snapchat' => '#FACC15', 'email' => '#F59E0B',
                                    ];
                                    $pColor = $platformColors[$conv->platform] ?? '#6b7280';
                                @endphp
                                <a href="{{ route('inbox', ['conversation' => $conv->id]) }}" wire:navigate
                                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all hover:bg-white/[0.04] group">
                                    <div class="relative flex-shrink-0">
                                        <div class="size-9 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                             style="background: linear-gradient(135deg, rgba(124,58,237,0.4), rgba(6,182,212,0.4)); border: 1px solid rgba(255,255,255,0.1);">
                                            {{ strtoupper(substr($conv->contact?->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2"
                                              style="background: {{ $pColor }}; border-color: #0A0A0F;"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-white/80 truncate group-hover:text-white transition-colors">
                                                {{ $conv->contact?->name ?? 'Unknown' }}
                                            </p>
                                            <span class="flex-shrink-0 text-[11px] text-white/30">
                                                {{ $conv->last_message_at?->shortAbsoluteDiffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-white/35 truncate">{{ $conv->last_message_preview ?? 'No messages' }}</p>
                                    </div>
                                    @if($conv->unread_count > 0)
                                        <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white"
                                              style="background: #7C3AED;">{{ $conv->unread_count }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <svg class="mb-3 size-10 text-white/10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="text-sm text-white/30">{{ __('No conversations yet') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Hot Leads --}}
                <div class="aio-card rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-sm font-semibold text-white">{{ __('Hot Leads') }}</h3>
                        <a href="{{ route('contacts.index') }}" wire:navigate
                           class="text-xs font-medium text-[#C27AFF] hover:text-purple-300 transition-colors">{{ __('View All') }}</a>
                    </div>
                    @if($stats['hotLeads']->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($stats['hotLeads'] as $lead)
                                @php
                                    $scoreColor = match(true) {
                                        $lead->lead_score >= 86 => ['text' => '#FB7185', 'bg' => 'rgba(244,63,94,0.12)', 'border' => 'rgba(244,63,94,0.25)'],
                                        $lead->lead_score >= 71 => ['text' => '#FBBF24', 'bg' => 'rgba(245,158,11,0.12)', 'border' => 'rgba(245,158,11,0.25)'],
                                        default                 => ['text' => '#818CF8', 'bg' => 'rgba(99,102,241,0.12)', 'border' => 'rgba(99,102,241,0.25)'],
                                    };
                                @endphp
                                <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-white/[0.03]"
                                     style="border: 1px solid rgba(255,255,255,0.04);">
                                    <div class="size-9 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                         style="background: linear-gradient(135deg, {{ $scoreColor['bg'] }}, rgba(124,58,237,0.15));">
                                        {{ strtoupper(substr($lead->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-white/80 truncate">{{ $lead->name }}</p>
                                        <p class="text-xs text-white/35">{{ ucfirst($lead->lead_status) }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                        <span class="text-sm font-bold" style="color: {{ $scoreColor['text'] }};">{{ $lead->lead_score }}</span>
                                        <div class="w-12 h-1 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.07);">
                                            <div class="h-full rounded-full" style="width: {{ $lead->lead_score }}%; background: {{ $scoreColor['text'] }};"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <svg class="mb-3 size-10 text-white/10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                            <p class="text-sm text-white/30">{{ __('No hot leads yet') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Lead Pipeline ── --}}
            @if(! empty($stats['leadStats']))
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white mb-4">{{ __('Lead Pipeline') }}</h3>
                    @php
                        $statusConfig = [
                            'new'       => ['color' => '#99A1AF', 'bg' => 'bg-gray-400'],
                            'cold'      => ['color' => '#818CF8', 'bg' => 'bg-indigo-400'],
                            'warm'      => ['color' => '#FBBF24', 'bg' => 'bg-amber-400'],
                            'hot'       => ['color' => '#FB7185', 'bg' => 'bg-rose-400'],
                            'converted' => ['color' => '#34D399', 'bg' => 'bg-emerald-400'],
                            'lost'      => ['color' => '#6A7282', 'bg' => 'bg-gray-500'],
                        ];
                        $leadTotal = array_sum($stats['leadStats']);
                    @endphp
                    <div class="flex gap-0.5 overflow-hidden rounded-full h-3 mb-4">
                        @foreach($stats['leadStats'] as $status => $count)
                            @php $pct = $leadTotal > 0 ? round(($count / $leadTotal) * 100) : 0; @endphp
                            <div class="{{ $statusConfig[$status]['bg'] ?? 'bg-gray-400' }} h-full transition-all"
                                 style="width: {{ max($pct, 1) }}%" title="{{ ucfirst($status) }}: {{ $count }}"></div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                        @foreach($stats['leadStats'] as $status => $count)
                            <div class="flex items-center gap-1.5">
                                <div class="size-2 rounded-full {{ $statusConfig[$status]['bg'] ?? 'bg-gray-400' }}"></div>
                                <span class="text-xs text-white/40">{{ ucfirst($status) }}</span>
                                <span class="text-xs font-semibold text-white/70">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Quick Actions ── --}}
            <div class="grid gap-3 sm:grid-cols-3">
                <a href="{{ route('inbox') }}" wire:navigate
                   class="aio-card flex items-center gap-4 rounded-2xl p-4 transition-all hover:border-[#3b82f6]/30 hover:bg-[#3b82f6]/5 group">
                    <div class="aio-icon-blue rounded-xl p-3">
                        <svg class="size-5 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white/80 group-hover:text-white transition-colors">{{ __('Open Inbox') }}</p>
                        <p class="text-xs text-white/35">{{ __('View and reply') }}</p>
                    </div>
                </a>
                <a href="{{ route('ai-chat') }}" wire:navigate
                   class="aio-card flex items-center gap-4 rounded-2xl p-4 transition-all hover:border-[#7C3AED]/30 hover:bg-[#7C3AED]/5 group">
                    <div class="aio-icon-purple rounded-xl p-3">
                        <svg class="size-5 text-[#C27AFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white/80 group-hover:text-white transition-colors">{{ __('AI Chat') }}</p>
                        <p class="text-xs text-white/35">{{ __('Ask your assistant') }}</p>
                    </div>
                </a>
                <a href="{{ route('connections.index') }}" wire:navigate
                   class="aio-card flex items-center gap-4 rounded-2xl p-4 transition-all hover:border-[#00D492]/30 hover:bg-[#00D492]/5 group">
                    <div class="aio-icon-green rounded-xl p-3">
                        <svg class="size-5 text-[#00D492]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white/80 group-hover:text-white transition-colors">{{ __('Connections') }}</p>
                        <p class="text-xs text-white/35">{{ __('Manage channels') }}</p>
                    </div>
                </a>
            </div>

        </div>
    @endif
</div>
