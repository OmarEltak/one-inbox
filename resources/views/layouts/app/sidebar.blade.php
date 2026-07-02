<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen" style="background: linear-gradient(135deg, #0A0A0F 0%, #0D0D1A 30%, #111127 60%, #0A0A0F 100%);">

        @php
            $user = auth()->user();
            $team = $user?->currentTeam;
        @endphp

        {{-- ══ SIDEBAR ══ --}}
        <flux:sidebar sticky collapsible="mobile"
            class="border-e border-white/15"
            style="background: rgba(10,10,20,0.95); backdrop-filter: blur(12px); box-shadow: 4px 0 30px rgba(0,0,0,0.6);">

            {{-- Logo + Team chip --}}
            @php
                // Deterministic team hue from slug; same team always gets the same color.
                $teamHue = $team ? (crc32($team->slug ?? $team->name) % 360) : 280;
                $teamInitial = $team ? strtoupper(mb_substr($team->name, 0, 1)) : '?';
                $userTeams = $team ? $user->teams()->orderBy('name')->get() : collect();
                $canSwitch = $userTeams->count() > 1 || $user->isSuperAdmin();
            @endphp
            <flux:sidebar.header class="px-4 py-5">
                <a href="{{ route('dashboard') }}" wire:navigate.hover class="flex items-center gap-2.5 group min-w-0 flex-1">
                    <img src="/logo.png" alt="OT1-Pro" class="size-8 rounded-lg flex-shrink-0 object-cover" />
                    <p class="text-sm font-bold text-white leading-tight truncate">{{ __('OT1-Pro') }}</p>
                </a>
                <flux:sidebar.collapse class="lg:hidden ml-1 text-white/40 hover:text-white/70" />
            </flux:sidebar.header>

            {{-- Team identity chip (loud) --}}
            @if($team)
                <div class="px-3 pb-3">
                    @if($canSwitch)
                        <flux:dropdown position="bottom" align="start" class="w-full">
                            <button
                                class="flex items-center gap-2.5 w-full px-2.5 py-2 rounded-xl border border-white/15 hover:border-white/25 hover:bg-white/[0.04] transition-colors group cursor-pointer"
                                style="background: linear-gradient(135deg, hsla({{ $teamHue }}, 65%, 55%, 0.10), hsla({{ $teamHue }}, 65%, 45%, 0.06));"
                                title="{{ __('Switch workspace') }}"
                            >
                                <span class="size-7 rounded-lg flex items-center justify-center text-[13px] font-bold text-white flex-shrink-0 shadow-sm"
                                      style="background: hsl({{ $teamHue }}, 65%, 50%);">
                                    {{ $teamInitial }}
                                </span>
                                <span class="min-w-0 flex-1 text-left">
                                    <span class="block text-[10px] uppercase tracking-widest text-white/40 font-semibold leading-tight">{{ __('Workspace') }}</span>
                                    <span class="block text-sm font-semibold text-white truncate leading-tight">{{ $team->name }}</span>
                                </span>
                                <svg class="size-3.5 text-white/40 group-hover:text-white/70 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7l3-3 3 3M7 17l3 3 3-3" />
                                </svg>
                            </button>
                            <flux:menu>
                                <div class="px-3 py-2 text-[11px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400 font-semibold">{{ __('Switch workspace') }}</div>
                                @foreach($userTeams as $userTeam)
                                    @php $ut_hue = crc32($userTeam->slug ?? $userTeam->name) % 360; @endphp
                                    <form method="POST" action="{{ route('teams.switch', $userTeam) }}" class="w-full">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors cursor-pointer {{ $userTeam->id === $team->id ? 'bg-zinc-50 dark:bg-zinc-700/30' : '' }}">
                                            <span class="size-6 rounded-md flex items-center justify-center text-[11px] font-bold text-white flex-shrink-0"
                                                  style="background: hsl({{ $ut_hue }}, 65%, 50%);">
                                                {{ strtoupper(mb_substr($userTeam->name, 0, 1)) }}
                                            </span>
                                            <span class="flex-1 text-left text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $userTeam->name }}</span>
                                            @if($userTeam->id === $team->id)
                                                <svg class="size-4 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                                <flux:menu.separator />
                                <flux:menu.item :href="route('teams.create')" icon="plus" wire:navigate.hover>{{ __('New workspace') }}</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    @else
                        <div
                            class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl border border-white/15"
                            style="background: linear-gradient(135deg, hsla({{ $teamHue }}, 65%, 55%, 0.10), hsla({{ $teamHue }}, 65%, 45%, 0.06));"
                        >
                            <span class="size-7 rounded-lg flex items-center justify-center text-[13px] font-bold text-white flex-shrink-0 shadow-sm"
                                  style="background: hsl({{ $teamHue }}, 65%, 50%);">
                                {{ $teamInitial }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[10px] uppercase tracking-widest text-white/40 font-semibold leading-tight">{{ __('Workspace') }}</span>
                                <span class="block text-sm font-semibold text-white truncate leading-tight">{{ $team->name }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Navigation --}}
            <flux:sidebar.nav class="px-3 space-y-0.5 flex-1">

                @php
                    $navItems = [];
                    $showInbox = $user->isHeadAdmin() || $user->hasPermission('inbox');

                    // Onboarding gate — super-admins bypass so they can navigate any team.
                    $hasConnections = $user->isSuperAdmin() ? true : ($team?->hasAnyConnection() ?? false);
                    $lockedRoutes = ['inbox', 'contacts.index', 'campaigns.index', 'content.index', 'analytics', 'ai-chat', 'settings.ai'];
                    $lockIf = fn (string $route): bool => ! $hasConnections && in_array($route, $lockedRoutes, true);

                    if ($user->isHeadAdmin() || $user->hasPermission('dashboard')) {
                        $navItems[] = ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'home', 'match' => 'dashboard', 'locked' => false];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('contacts')) {
                        $navItems[] = ['route' => 'contacts.index', 'label' => 'Contacts', 'icon' => 'users', 'match' => 'contacts*', 'locked' => $lockIf('contacts.index')];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('connections')) {
                        $navItems[] = ['route' => 'campaigns.index', 'label' => 'Campaigns', 'icon' => 'paper-airplane', 'match' => 'campaigns*', 'locked' => $lockIf('campaigns.index')];
                        $navItems[] = ['route' => 'content.index', 'label' => 'Content', 'icon' => 'photo', 'match' => 'content*', 'locked' => $lockIf('content.index')];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('analytics')) {
                        $navItems[] = ['route' => 'analytics', 'label' => 'Analytics', 'icon' => 'chart-bar', 'match' => 'analytics', 'locked' => $lockIf('analytics')];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('ai-chat')) {
                        $navItems[] = ['route' => 'ai-chat', 'label' => 'AI Chat', 'icon' => 'sparkles', 'match' => 'ai-chat', 'locked' => $lockIf('ai-chat')];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('ai-settings')) {
                        $navItems[] = ['route' => 'settings.ai', 'label' => 'AI Settings', 'icon' => 'cog-6-tooth', 'match' => 'settings.ai*', 'locked' => $lockIf('settings.ai')];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('connections')) {
                        $navItems[] = ['route' => 'connections.index', 'label' => 'Connections', 'icon' => 'link', 'match' => 'connections*', 'locked' => false];
                    }
                    if ($user->canManageAdmins()) {
                        $navItems[] = ['route' => 'settings.admins', 'label' => 'Settings', 'icon' => 'adjustments-horizontal', 'match' => 'settings.admins*', 'locked' => false];
                    }
                    if ($user->isSuperAdmin()) {
                        $navItems[] = ['route' => 'super-admin.customers', 'label' => 'Customers', 'icon' => 'building-office-2', 'match' => 'super-admin.customers', 'locked' => false];
                        $navItems[] = ['route' => 'super-admin.page-assignments', 'label' => 'Page Assignments', 'icon' => 'rectangle-stack', 'match' => 'super-admin.page-assignments', 'locked' => false];
                        $navItems[] = ['route' => 'super-admin.onboarding-requests', 'label' => 'Onboarding Requests', 'icon' => 'inbox-arrow-down', 'match' => 'super-admin.onboarding-requests', 'locked' => false];
                    }

                    // Load pages for inbox dropdown. Cached for 5 min — the sidebar
                    // renders on EVERY navigate, and page list rarely changes intra-session.
                    // Team::clearActivePagesCache() invalidates on connect/disconnect.
                    $inboxPages = $showInbox && $team
                        ? \Illuminate\Support\Facades\Cache::remember(
                            "team.{$team->id}.inbox_sidebar_pages",
                            300,
                            fn () => $team->pages()->where('is_active', true)->orderBy('platform')->orderBy('name')->get()
                        )
                        : collect();

                    $platformColors = [
                        'facebook'  => '#1877F2',
                        'instagram' => '#E1306C',
                        'whatsapp'  => '#25D366',
                        'telegram'  => '#0088CC',
                        'tiktok'    => '#EE1D52',
                        'snapchat'  => '#FFFC00',
                        'email'     => '#F97316',
                    ];
                    $isInboxActive = request()->routeIs('inbox*');
                @endphp

                {{-- Home nav item --}}
                @php $firstItem = array_shift($navItems); @endphp
                @if($firstItem)
                    @php $isCurrent = request()->routeIs($firstItem['match']); @endphp
                    <a href="{{ route($firstItem['route']) }}" wire:navigate.hover
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group
                              {{ $isCurrent ? 'text-white shadow-lg' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}"
                       @if($isCurrent) style="background: linear-gradient(135deg, rgba(124,58,237,0.85) 0%, rgba(109,40,217,0.85) 100%); box-shadow: 0 2px 12px rgba(124,58,237,0.35);" @endif>
                        <flux:icon name="{{ $firstItem['icon'] }}" class="size-4.5 flex-shrink-0 {{ $isCurrent ? 'text-white' : 'text-white/40 group-hover:text-white/70' }}" />
                        <span>{{ $firstItem['label'] }}</span>
                    </a>
                @endif

                {{-- Inbox with collapsible dropdown --}}
                @if($showInbox)
                @php $inboxLocked = ! $hasConnections; @endphp
                @if($inboxLocked)
                <button type="button" @click="$dispatch('needs-connection')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group w-full cursor-pointer text-white/40 hover:text-white/60 opacity-60 hover:opacity-80">
                    <flux:icon name="inbox" class="size-4.5 flex-shrink-0 text-white/30" />
                    <span class="flex-1 text-left">Inbox</span>
                    <flux:icon name="lock-closed" class="size-3.5 flex-shrink-0 text-white/25" />
                </button>
                @else
                <div x-data="{ open: {{ $isInboxActive ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group w-full cursor-pointer
                               {{ $isInboxActive ? 'text-white shadow-lg' : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}"
                        @if($isInboxActive) style="background: linear-gradient(135deg, rgba(124,58,237,0.85) 0%, rgba(109,40,217,0.85) 100%); box-shadow: 0 2px 12px rgba(124,58,237,0.35);" @endif>
                        <flux:icon name="inbox" class="size-4.5 flex-shrink-0 {{ $isInboxActive ? 'text-white' : 'text-white/40 group-hover:text-white/70' }}" />
                        <span class="flex-1 text-left">Inbox</span>
                        @if(isset($unreadCount) && $unreadCount > 0)
                            <span class="flex-shrink-0 rounded-full bg-[#FB2C36] px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @endif
                        <svg x-bind:class="open ? 'rotate-180' : ''"
                             class="size-3.5 flex-shrink-0 ml-1 transition-transform duration-200 {{ $isInboxActive ? 'text-white/70' : 'text-white/25' }}"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="mt-0.5 ml-3 pl-4 space-y-0.5 border-l border-white/15">

                        {{-- All Inbox --}}
                        @php $allActive = $isInboxActive && !request()->query('pageId'); @endphp
                        <a href="{{ route('inbox') }}" wire:navigate.hover
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150 group
                                  {{ $allActive ? 'text-white bg-white/[0.07]' : 'text-white/40 hover:text-white/70 hover:bg-white/[0.04]' }}">
                            <svg class="size-3.5 flex-shrink-0 {{ $allActive ? 'text-[#C27AFF]' : 'text-white/25 group-hover:text-white/50' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span>All Messages</span>
                        </a>

                        {{-- Per-page sub-items --}}
                        @foreach($inboxPages as $page)
                            @php
                                $pageActive = $isInboxActive && request()->query('pageId') == $page->id;
                                $color = $platformColors[$page->platform] ?? '#6B7280';
                                $initials = strtoupper(substr($page->platform, 0, 2));
                            @endphp
                            <a href="{{ route('inbox') }}?pageId={{ $page->id }}" wire:navigate.hover
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150 group
                                      {{ $pageActive ? 'text-white bg-white/[0.07]' : 'text-white/40 hover:text-white/70 hover:bg-white/[0.04]' }}">
                                <span class="inline-flex items-center justify-center size-4 rounded-md text-[9px] font-bold flex-shrink-0"
                                      style="background: {{ $color }}22; color: {{ $color }};">
                                    {{ $initials }}
                                </span>
                                <span class="truncate max-w-[120px]">{{ $page->name }}</span>
                            </a>
                        @endforeach

                        @if($inboxPages->isEmpty())
                            <p class="px-3 py-2 text-[11px] text-white/20 italic">No pages connected</p>
                        @endif
                    </div>
                </div>
                @endif
                @endif

                {{-- Remaining nav items --}}
                @foreach($navItems as $item)
                    @php $isCurrent = request()->routeIs($item['match']); @endphp
                    @if(! empty($item['locked']))
                        <button type="button" @click="$dispatch('needs-connection')"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group w-full cursor-pointer text-white/40 hover:text-white/60 opacity-60 hover:opacity-80">
                            <flux:icon name="{{ $item['icon'] }}" class="size-4.5 flex-shrink-0 text-white/30" />
                            <span class="flex-1 text-left">{{ $item['label'] }}</span>
                            <flux:icon name="lock-closed" class="size-3.5 flex-shrink-0 text-white/25" />
                        </button>
                    @else
                        <a href="{{ route($item['route']) }}"
                           wire:navigate.hover
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group
                                  {{ $isCurrent
                                     ? 'text-white shadow-lg'
                                     : 'text-white/50 hover:text-white/80 hover:bg-white/[0.05]' }}"
                           @if($isCurrent)
                           style="background: linear-gradient(135deg, rgba(124,58,237,0.85) 0%, rgba(109,40,217,0.85) 100%); box-shadow: 0 2px 12px rgba(124,58,237,0.35);"
                           @endif
                        >
                            <flux:icon name="{{ $item['icon'] }}"
                                class="size-4.5 flex-shrink-0 {{ $isCurrent ? 'text-white' : 'text-white/40 group-hover:text-white/70' }}" />
                            <span>{{ $item['label'] }}</span>

                            {{-- AI ON/OFF indicator --}}
                            @if($item['route'] === 'settings.ai')
                                <span class="ml-auto flex-shrink-0 size-1.5 rounded-full {{ $team?->ai_enabled ? 'bg-green-400' : 'bg-red-500' }}"></span>
                            @endif
                        </a>
                    @endif
                @endforeach

            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Bottom: User info --}}
            <div class="px-3 pb-4">
                <div class="border-t border-white/15 pt-4">
                    <flux:dropdown position="top" align="start" class="w-full">
                        <button class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl hover:bg-white/[0.05] transition-colors group cursor-pointer">
                            <div class="size-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background: linear-gradient(135deg, #7C3AED, #06B6D4);">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1 text-left">
                                <p class="text-xs font-semibold text-white/80 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-white/35 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <svg class="size-3.5 text-white/30 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="px-2 py-2 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5">
                                        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                            <flux:text class="truncate text-xs">{{ auth()->user()->email }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>
                            <flux:menu.separator />
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate.hover>Settings</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    Log Out
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </flux:sidebar>

        {{-- ══ HEADER ══ --}}
        <flux:header sticky
            class="border-b border-white/15"
            style="background: rgba(10,10,20,0.85); backdrop-filter: blur(16px);">

            {{-- Mobile toggle --}}
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            {{-- Breadcrumb: team identity is the load-bearing anchor --}}
            <div class="hidden lg:flex items-center gap-2.5 text-sm min-w-0">
                @if($team)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border border-white/15"
                          style="background: hsla({{ $teamHue }}, 65%, 50%, 0.10);"
                          title="{{ __('Current workspace') }}">
                        <span class="size-4 rounded-md flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                              style="background: hsl({{ $teamHue }}, 65%, 50%);">
                            {{ $teamInitial }}
                        </span>
                        <span class="font-semibold text-white/85 truncate max-w-[180px]">{{ $team->name }}</span>
                    </span>
                @else
                    <span class="font-semibold text-white/70">{{ __('OT1-Pro') }}</span>
                @endif
                <span class="text-white/20">/</span>
                <span class="text-white/60 font-medium truncate">{{ $title ?? __('Dashboard') }}</span>
            </div>

            {{-- ⌘K command palette intentionally deferred. Removed the
                 fake search-bar chip; ship a real palette later as its
                 own feature instead of placeholder UI. --}}
            <flux:spacer />

            {{-- Notification bell --}}
            <div class="relative">
                <button class="relative p-2 rounded-xl text-white/40 hover:text-white/70 transition-colors cursor-pointer"
                        style="background: rgba(255,255,255,0.04);">
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>
            </div>

            {{-- Avatar dropdown --}}
            <flux:dropdown position="top" align="end">
                <button class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-white/[0.05] transition-colors cursor-pointer">
                    <div class="size-7 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
                         style="background: linear-gradient(135deg, #7C3AED, #06B6D4);">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="hidden lg:block text-sm font-medium text-white/70 max-w-[100px] truncate">
                        {{ auth()->user()->name }}
                    </span>
                    <svg class="size-3.5 text-white/30 hidden lg:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-3 py-2.5">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate text-xs">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate.hover>Settings</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                            Log Out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        @include('partials.ai-quota-banner')

        {{-- Onboarding gate modal — fires when a locked sidebar item is clicked. --}}
        <div x-data="{ open: false }"
             @needs-connection.window="open = true"
             x-show="open" x-cloak
             x-transition.opacity
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm"
             style="display: none;">
            <div @click.outside="open = false"
                 class="max-w-md w-[92vw] rounded-2xl border border-white/15 bg-[#0D0D1A] p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-white">Connect your first page</h3>
                <p class="mt-2 text-sm text-white/60">
                    To see messages, contacts, and analytics, first connect at least one platform
                    (Facebook, Instagram, WhatsApp, Telegram, or Email).
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="open = false"
                            class="rounded-lg px-3 py-2 text-sm text-white/60 hover:text-white transition-colors">
                        Cancel
                    </button>
                    <a href="{{ route('connections.index') }}" wire:navigate.hover @click="open = false"
                       class="rounded-lg bg-purple-600 px-3 py-2 text-sm font-medium text-white hover:bg-purple-500 transition-colors">
                        Go to Connections →
                    </a>
                </div>
            </div>
        </div>

        {{ $slot }}

        @fluxScripts

        @auth
        @php $notifTeamId = auth()->user()->currentTeam?->id; @endphp
        @if($notifTeamId)
        <script>
        (function () {
            const teamId = @json($notifTeamId);

            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            function setupEchoListener() {
                if (!window.Echo) return;

                window.Echo.private('team.' + teamId)
                    .listen('.message.received', function (e) {
                        if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
                            new Notification('New message from ' + (e.contactName || 'a contact'), {
                                body: e.preview || '',
                                icon: '/logo.png',
                                tag: 'conversation-' + e.conversationId,
                            });
                        }
                        Livewire.dispatch('refreshInbox');
                    })
                    .listen('.ai.limit', function () {
                        window.dispatchEvent(new CustomEvent('ai-limit-reached'));
                    });
            }

            if (window.Echo) {
                setupEchoListener();
            } else {
                document.addEventListener('DOMContentLoaded', setupEchoListener);
            }
        })();
        </script>
        @endif
        @endauth
    </body>
</html>
