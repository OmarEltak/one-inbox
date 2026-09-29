<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen">

        @php
            $user = auth()->user();
            $team = $user?->currentTeam;
        @endphp

        {{-- ══ SIDEBAR ══ --}}
        <flux:sidebar sticky collapsible="mobile"
            class="border-e border-zinc-200">

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
                    {{-- Wide brand mark (bot + wordmark). Fits inside the sidebar header on its own,
                         so the redundant text label was removed. --}}
                    <img src="/logo/logo-light.png" alt="OT1-Pro" class="h-8 w-auto flex-shrink-0" />
                </a>
                <flux:sidebar.collapse class="lg:hidden ml-1 text-zinc-400 hover:text-zinc-600" />
            </flux:sidebar.header>

            {{-- Team identity chip (loud) --}}
            @if($team)
                <div class="px-3 pb-3">
                    @if($canSwitch)
                        <flux:dropdown position="bottom" align="start" class="w-full">
                            <button
                                class="flex items-center gap-2.5 w-full px-2.5 py-2 rounded-xl border border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50 transition-colors group cursor-pointer"
                                style="background: linear-gradient(135deg, hsla({{ $teamHue }}, 65%, 55%, 0.08), hsla({{ $teamHue }}, 65%, 45%, 0.04));"
                                title="{{ __('Switch workspace') }}"
                            >
                                <span class="size-7 rounded-lg flex items-center justify-center text-[13px] font-bold text-white flex-shrink-0 shadow-sm"
                                      style="background: hsl({{ $teamHue }}, 65%, 50%);">
                                    {{ $teamInitial }}
                                </span>
                                <span class="min-w-0 flex-1 text-left">
                                    <span class="block text-[10px] uppercase tracking-widest text-zinc-400 font-semibold leading-tight">{{ __('Workspace') }}</span>
                                    <span class="block text-sm font-semibold text-zinc-800 truncate leading-tight">{{ $team->name }}</span>
                                    @php $plan = $team->subscription_plan ?? 'free'; @endphp
                                    <span class="inline-block mt-0.5 text-[9px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded-full leading-none
                                        {{ $plan === 'enterprise' ? 'bg-emerald-100 text-emerald-700' : ($plan === 'pro' ? 'bg-blue-100 text-blue-700' : ($plan === 'starter' ? 'bg-green-100 text-green-700' : 'bg-zinc-100 text-zinc-500')) }}">
                                        {{ ucfirst($plan) }}
                                    </span>
                                </span>
                                <svg class="size-3.5 text-zinc-400 group-hover:text-zinc-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                                                <svg class="size-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                        @php $plan = $team->subscription_plan ?? 'free'; @endphp
                        <div
                            class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl border border-zinc-200"
                            style="background: linear-gradient(135deg, hsla({{ $teamHue }}, 65%, 55%, 0.08), hsla({{ $teamHue }}, 65%, 45%, 0.04));"
                        >
                            <span class="size-7 rounded-lg flex items-center justify-center text-[13px] font-bold text-white flex-shrink-0 shadow-sm"
                                  style="background: hsl({{ $teamHue }}, 65%, 50%);">
                                {{ $teamInitial }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[10px] uppercase tracking-widest text-zinc-400 font-semibold leading-tight">{{ __('Workspace') }}</span>
                                <span class="block text-sm font-semibold text-zinc-800 truncate leading-tight">{{ $team->name }}</span>
                                <span class="inline-block mt-0.5 text-[9px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded-full leading-none
                                    {{ $plan === 'enterprise' ? 'bg-emerald-100 text-emerald-700' : ($plan === 'pro' ? 'bg-blue-100 text-blue-700' : ($plan === 'starter' ? 'bg-green-100 text-green-700' : 'bg-zinc-100 text-zinc-500')) }}">
                                    {{ ucfirst($plan) }}
                                </span>
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
                    $lockedRoutes = ['inbox', 'contacts.index', 'campaigns.index', 'analytics', 'ai-chat', 'settings.ai'];
                    $lockIf = fn (string $route): bool => ! $hasConnections && in_array($route, $lockedRoutes, true);

                    if ($user->isHeadAdmin() || $user->hasPermission('dashboard')) {
                        $navItems[] = ['route' => 'dashboard', 'label' => 'Home', 'icon' => 'home', 'match' => 'dashboard', 'locked' => false];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('contacts')) {
                        $navItems[] = ['route' => 'contacts.index', 'label' => 'Contacts', 'icon' => 'users', 'match' => 'contacts*', 'locked' => $lockIf('contacts.index')];
                    }
                    if ($user->isHeadAdmin() || $user->hasPermission('connections')) {
                        $navItems[] = ['route' => 'campaigns.index', 'label' => 'Campaigns', 'icon' => 'paper-airplane', 'match' => 'campaigns*', 'locked' => $lockIf('campaigns.index')];
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
                        $navItems[] = ['route' => 'super-admin.subscriptions', 'label' => 'Subscriptions', 'icon' => 'key', 'match' => 'super-admin.subscriptions', 'locked' => false];
                        $navItems[] = ['route' => 'super-admin.page-assignments', 'label' => 'Page Assignments', 'icon' => 'rectangle-stack', 'match' => 'super-admin.page-assignments', 'locked' => false];
                        $navItems[] = ['route' => 'super-admin.onboarding-requests', 'label' => 'Onboarding Requests', 'icon' => 'inbox-arrow-down', 'match' => 'super-admin.onboarding-requests', 'locked' => false];
                        $navItems[] = ['route' => 'super-admin.billing', 'label' => 'Billing', 'icon' => 'banknotes', 'match' => 'super-admin.billing', 'locked' => false];
                        $navItems[] = ['route' => 'super-admin.blog.index', 'label' => 'Blog', 'icon' => 'pencil-square', 'match' => 'super-admin.blog.*', 'locked' => false];
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
                              {{ $isCurrent ? 'text-white shadow-lg' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100' }}"
                       @if($isCurrent) style="background: linear-gradient(135deg, rgba(5,150,105,0.92) 0%, rgba(4,120,87,0.92) 100%); box-shadow: 0 2px 12px rgba(5,150,105,0.28);" @endif>
                        <flux:icon name="{{ $firstItem['icon'] }}" class="size-4.5 flex-shrink-0 {{ $isCurrent ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-600' }}" />
                        <span>{{ __($firstItem['label']) }}</span>
                    </a>
                @endif

                {{-- Inbox with collapsible dropdown --}}
                @if($showInbox)
                @php $inboxLocked = ! $hasConnections; @endphp
                @if($inboxLocked)
                <button type="button" @click="$dispatch('needs-connection')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group w-full cursor-pointer text-zinc-400 hover:text-zinc-500 opacity-60 hover:opacity-80">
                    <flux:icon name="inbox" class="size-4.5 flex-shrink-0 text-zinc-300" />
                    <span class="flex-1 text-left">{{ __('Inbox') }}</span>
                    <flux:icon name="lock-closed" class="size-3.5 flex-shrink-0 text-zinc-300" />
                </button>
                @else
                <div x-data="{ open: {{ $isInboxActive ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group w-full cursor-pointer
                               {{ $isInboxActive ? 'text-white shadow-lg' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100' }}"
                        @if($isInboxActive) style="background: linear-gradient(135deg, rgba(5,150,105,0.92) 0%, rgba(4,120,87,0.92) 100%); box-shadow: 0 2px 12px rgba(5,150,105,0.28);" @endif>
                        <flux:icon name="inbox" class="size-4.5 flex-shrink-0 {{ $isInboxActive ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-600' }}" />
                        <span class="flex-1 text-left">{{ __('Inbox') }}</span>
                        @if(isset($unreadCount) && $unreadCount > 0)
                            <span class="flex-shrink-0 rounded-full bg-[#FB2C36] px-1.5 py-0.5 text-[10px] font-bold text-white leading-none">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @endif
                        <svg x-bind:class="open ? 'rotate-180' : ''"
                             class="size-3.5 flex-shrink-0 ml-1 transition-transform duration-200 {{ $isInboxActive ? 'text-white/70' : 'text-zinc-400' }}"
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
                         class="mt-0.5 ml-3 pl-4 space-y-0.5 border-l border-zinc-200">

                        {{-- All Inbox --}}
                        @php $allActive = $isInboxActive && !request()->query('pageId'); @endphp
                        <a href="{{ route('inbox') }}" wire:navigate.hover
                           class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-all duration-150 group
                                  {{ $allActive ? 'text-zinc-800 bg-zinc-100' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50' }}">
                            <svg class="size-3.5 flex-shrink-0 {{ $allActive ? 'text-emerald-600' : 'text-zinc-400 group-hover:text-zinc-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span>{{ __('All Messages') }}</span>
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
                                      {{ $pageActive ? 'text-zinc-800 bg-zinc-100' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50' }}">
                                <span class="inline-flex items-center justify-center size-4 rounded-md text-[9px] font-bold flex-shrink-0"
                                      style="background: {{ $color }}22; color: {{ $color }};">
                                    {{ $initials }}
                                </span>
                                <span class="truncate max-w-[120px]">{{ $page->name }}</span>
                            </a>
                        @endforeach

                        @if($inboxPages->isEmpty())
                            <p class="px-3 py-2 text-[11px] text-zinc-400 italic">{{ __('No pages connected') }}</p>
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
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group w-full cursor-pointer text-zinc-400 hover:text-zinc-500 opacity-60 hover:opacity-80">
                            <flux:icon name="{{ $item['icon'] }}" class="size-4.5 flex-shrink-0 text-zinc-300" />
                            <span class="flex-1 text-left">{{ __($item['label']) }}</span>
                            <flux:icon name="lock-closed" class="size-3.5 flex-shrink-0 text-zinc-300" />
                        </button>
                    @else
                        <a href="{{ route($item['route']) }}"
                           wire:navigate.hover
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group
                                  {{ $isCurrent
                                     ? 'text-white shadow-lg'
                                     : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100' }}"
                           @if($isCurrent)
                           style="background: linear-gradient(135deg, rgba(5,150,105,0.92) 0%, rgba(4,120,87,0.92) 100%); box-shadow: 0 2px 12px rgba(5,150,105,0.28);"
                           @endif
                        >
                            <flux:icon name="{{ $item['icon'] }}"
                                class="size-4.5 flex-shrink-0 {{ $isCurrent ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-600' }}" />
                            <span>{{ __($item['label']) }}</span>

                            {{-- AI ON/OFF indicator --}}
                            @if($item['route'] === 'settings.ai')
                                <span class="ml-auto flex-shrink-0 size-1.5 rounded-full {{ $team?->ai_enabled ? 'bg-green-400' : 'bg-red-500' }}"></span>
                            @endif
                        </a>
                    @endif
                @endforeach

            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Contact support — always visible so users can reach the founder
                 directly when frustrated (e.g. upload limit hits, plan quota,
                 anything unclear). Opens WhatsApp in a new tab with a prefilled
                 message tagged with the team + user context. Emerald-100/900
                 pair per the contrast-guardrails skill so text stays legible. --}}
            <div class="px-3 pb-2">
                @php
                    $supportMsg = 'Hi Omar, I need help with OT1-Pro.'
                        . ($team ? "\nWorkspace: {$team->name}" : '')
                        . "\nMy account: " . auth()->user()->email;
                    $supportUrl = 'https://wa.me/201026361218?text=' . rawurlencode($supportMsg);
                @endphp
                <a href="{{ $supportUrl }}" target="_blank" rel="noopener"
                   class="flex items-center gap-2.5 w-full px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors group">
                    <span class="inline-flex items-center justify-center size-7 rounded-lg bg-emerald-600 text-white flex-shrink-0 shadow-sm">
                        <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M17.5 14.4c-.3-.1-1.6-.8-1.9-.9-.2-.1-.4-.1-.6.1-.2.3-.7.9-.8 1.1-.2.2-.3.2-.6.1s-1.2-.4-2.3-1.4c-.9-.8-1.4-1.7-1.6-2s-.02-.4.1-.5c.1-.1.2-.3.4-.4.1-.1.2-.2.3-.4.1-.2.05-.3-.02-.4-.1-.1-.6-1.5-.8-2s-.4-.5-.6-.5h-.5c-.2 0-.5.05-.7.3-.3.3-.9.9-.9 2.2s1 2.6 1.1 2.7c.1.2 1.9 3 4.7 4.2.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.6-.7 1.9-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3zM12 3a9 9 0 0 0-7.8 13.5L3 21l4.7-1.2A9 9 0 1 0 12 3zm0 16.5a7.5 7.5 0 0 1-3.8-1l-.3-.2-2.8.7.7-2.7-.2-.3A7.5 7.5 0 1 1 12 19.5z"/>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="block text-[10px] uppercase tracking-widest text-emerald-700 font-semibold leading-tight">{{ __('Need help?') }}</span>
                        <span class="block text-sm font-semibold text-emerald-900 leading-tight">{{ __('Message the founder') }}</span>
                    </span>
                </a>
            </div>

            {{-- Bottom: User info --}}
            <div class="px-3 pb-4">
                <div class="border-t border-zinc-200 pt-4">
                    <flux:dropdown position="top" align="start" class="w-full">
                        <button class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl hover:bg-zinc-100 transition-colors group cursor-pointer">
                            <div class="size-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background: linear-gradient(135deg, #059669, #10b981);">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1 text-left">
                                <p class="text-xs font-semibold text-zinc-700 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-zinc-400 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <svg class="size-3.5 text-zinc-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate.hover>{{ __('Settings') }}</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    {{ __('Log Out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </flux:sidebar>

        {{-- ══ HEADER ══ --}}
        <flux:header sticky
            class="border-b border-zinc-200">

            {{-- Mobile toggle --}}
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            {{-- Breadcrumb: team identity is the load-bearing anchor --}}
            <div class="hidden lg:flex items-center gap-2.5 text-sm min-w-0">
                @if($team)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border border-zinc-200"
                          style="background: hsla({{ $teamHue }}, 65%, 50%, 0.08);"
                          title="{{ __('Current workspace') }}">
                        <span class="size-4 rounded-md flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                              style="background: hsl({{ $teamHue }}, 65%, 50%);">
                            {{ $teamInitial }}
                        </span>
                        <span class="font-semibold text-zinc-800 truncate max-w-[180px]">{{ $team->name }}</span>
                    </span>
                @else
                    <span class="font-semibold text-zinc-600">{{ __('OT1-Pro') }}</span>
                @endif
                <span class="text-zinc-300">/</span>
                <span class="text-zinc-500 font-medium truncate">{{ $title ?? __('Dashboard') }}</span>
            </div>

            {{-- ⌘K command palette intentionally deferred. Removed the
                 fake search-bar chip; ship a real palette later as its
                 own feature instead of placeholder UI. --}}
            <flux:spacer />

            {{-- Onboarding progress pill (Phase C). Renders nothing when
                 the team is 100% complete. Lives here so it appears on
                 every authenticated page's top bar. --}}
            @if($team)
                <x-onboarding.progress-pill :team="$team" />
            @endif

            {{-- Language switcher — writes ?lang= to current URL; SetLocale middleware
                 persists the choice in a long-lived cookie so it survives CDN cache strip. --}}
            @php
                $currentLocale = app()->getLocale();
                $languages = ['en' => 'English', 'ar' => 'العربية'];
                $currentLangUrlBase = request()->fullUrlWithoutQuery('lang');
                $joiner = str_contains($currentLangUrlBase, '?') ? '&' : '?';
            @endphp
            <flux:dropdown position="bottom" align="end">
                <button class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 transition-colors cursor-pointer"
                        title="{{ __('Language') }}">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                    </svg>
                    <span class="text-[11px] font-semibold uppercase tracking-wider">{{ $currentLocale }}</span>
                </button>
                <flux:menu>
                    @foreach($languages as $code => $label)
                        <flux:menu.item as="a"
                                        href="{{ $currentLangUrlBase . $joiner . 'lang=' . $code }}"
                                        :icon="$currentLocale === $code ? 'check' : null">
                            {{ $label }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>

            {{-- Notification bell --}}
            <div class="relative">
                <button class="relative p-2 rounded-xl text-zinc-400 hover:text-zinc-600 transition-colors cursor-pointer bg-zinc-100">
                    <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>
            </div>

            {{-- Avatar dropdown --}}
            <flux:dropdown position="top" align="end">
                <button class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-zinc-100 transition-colors cursor-pointer">
                    <div class="size-7 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
                         style="background: linear-gradient(135deg, #059669, #10b981);">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="hidden lg:block text-sm font-medium text-zinc-600 max-w-[100px] truncate">
                        {{ auth()->user()->name }}
                    </span>
                    <svg class="size-3.5 text-zinc-400 hidden lg:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate.hover>{{ __('Settings') }}</flux:menu.item>
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
                 class="max-w-md w-[92vw] rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-zinc-900">{{ __('Connect your first page') }}</h3>
                <p class="mt-2 text-sm text-zinc-500">
                    {{ __('To see messages, contacts, and analytics, first connect at least one platform (Facebook, Instagram, WhatsApp, Telegram, or Email).') }}
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="open = false"
                            class="rounded-lg px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <a href="{{ route('connections.index') }}" wire:navigate.hover @click="open = false"
                       class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-500 transition-colors">
                        {{ __('Go to Connections') }} →
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

        {{-- ============================================================
             INBOX MEDIA HANDLERS (audio player + voice recorder)

             Loaded on EVERY authenticated page — cheap (both IIFEs
             immediately return if their window flag is set, they only
             install listeners once).

             They CAN'T live in the individual Blade components because
             Livewire's morphdom preserves <script> tag contents but does
             NOT execute them when the component re-renders. Result:
             clicking a mic button did nothing because the installer IIFE
             never ran on the initial page load (composer wasn't rendered
             until a conversation was clicked, then it came in via morph).

             Delegated click handlers + MutationObserver ensure new buttons
             and players wired up by Livewire later are auto-attached.
             ============================================================ --}}
        @auth
        <script>
        // ---- audio player ------------------------------------------------
        (function () {
            if (window.__inboxAudioPlayerInstalled) return;
            window.__inboxAudioPlayerInstalled = true;

            function fmt(sec) {
                if (!sec || !isFinite(sec)) return '0:00';
                var m = Math.floor(sec / 60);
                var s = Math.floor(sec % 60);
                return m + ':' + (s < 10 ? '0' + s : s);
            }

            function playIcon(svg, playing) {
                if (!svg) return;
                var path = svg.querySelector('path');
                if (!path) return;
                if (playing) {
                    path.setAttribute('d', 'M6 5h4v14H6zM14 5h4v14h-4z');
                    svg.classList.remove('translate-x-[1px]');
                } else {
                    path.setAttribute('d', 'M8 5v14l11-7z');
                    svg.classList.add('translate-x-[1px]');
                }
            }

            function updateBars(playerEl, progress) {
                var bars = playerEl.querySelectorAll('.ap-bar');
                bars.forEach(function (bar, i) {
                    var threshold = i / bars.length;
                    if (progress > threshold) {
                        bar.classList.remove('opacity-40');
                        bar.classList.add('opacity-100');
                    } else {
                        bar.classList.remove('opacity-100');
                        bar.classList.add('opacity-40');
                    }
                });
            }

            function wirePlayer(playerEl) {
                if (playerEl.__apWired) return;
                playerEl.__apWired = true;
                var uid   = playerEl.getAttribute('data-player-id');
                var audio = document.getElementById(uid + '-audio');
                var icon  = document.getElementById(uid + '-icon');
                var time  = document.getElementById(uid + '-time');
                var track = document.getElementById(uid + '-track');
                if (!audio) return;

                audio.addEventListener('play',  function () { playIcon(icon, true); });
                audio.addEventListener('pause', function () { playIcon(icon, false); });
                audio.addEventListener('ended', function () {
                    playIcon(icon, false);
                    if (time) time.textContent = fmt(audio.duration);
                    updateBars(playerEl, 1);
                });
                audio.addEventListener('loadedmetadata', function () {
                    if (time && audio.paused) time.textContent = fmt(audio.duration);
                });
                audio.addEventListener('timeupdate', function () {
                    if (time) time.textContent = fmt(audio.currentTime);
                    var prog = audio.duration > 0 ? audio.currentTime / audio.duration : 0;
                    updateBars(playerEl, prog);
                });
                if (track) {
                    track.addEventListener('click', function (ev) {
                        if (!audio.duration) return;
                        var r = track.getBoundingClientRect();
                        var ratio = Math.max(0, Math.min(1, (ev.clientX - r.left) / r.width));
                        audio.currentTime = ratio * audio.duration;
                    });
                }
            }

            document.addEventListener('click', function (ev) {
                var btn = ev.target.closest('[data-ap-toggle]');
                if (!btn) return;
                ev.preventDefault();
                var uid   = btn.getAttribute('data-ap-toggle');
                var audio = document.getElementById(uid + '-audio');
                if (!audio) return;
                var playerEl = btn.closest('.inbox-audio-player');
                if (playerEl) wirePlayer(playerEl);
                if (audio.paused) {
                    document.querySelectorAll('audio').forEach(function (a) {
                        if (a !== audio) a.pause();
                    });
                    audio.play().catch(function (e) {
                        console.error('[audio-player] play failed', e, audio.currentSrc);
                    });
                } else {
                    audio.pause();
                }
            });

            var mo = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('.inbox-audio-player')) wirePlayer(node);
                        if (node.querySelectorAll) node.querySelectorAll('.inbox-audio-player').forEach(wirePlayer);
                    });
                });
            });
            mo.observe(document.body, { childList: true, subtree: true });
            document.querySelectorAll('.inbox-audio-player').forEach(wirePlayer);
        })();

        // ---- voice recorder ---------------------------------------------
        (function () {
            if (window.__inboxVoiceRecorderInstalled) return;
            window.__inboxVoiceRecorderInstalled = true;

            var state = new WeakMap();

            function applyVisualState(btn, s) {
                btn.dataset.vrState = s.recording ? 'recording' : (s.uploading ? 'uploading' : 'idle');
                btn.classList.remove(
                    'text-zinc-400', 'hover:text-zinc-600', 'dark:hover:text-zinc-300',
                    'text-red-500', 'animate-pulse', 'text-zinc-300'
                );
                if (s.recording) {
                    btn.classList.add('text-red-500', 'animate-pulse');
                    btn.title = 'Stop & send';
                    btn.disabled = false;
                } else if (s.uploading) {
                    btn.classList.add('text-zinc-300');
                    btn.title = 'Sending…';
                    btn.disabled = true;
                } else {
                    btn.classList.add('text-zinc-400', 'hover:text-zinc-600', 'dark:hover:text-zinc-300');
                    btn.title = 'Record voice note';
                    btn.disabled = false;
                }
            }

            function setState(btn, next) {
                var s = state.get(btn) || {};
                Object.assign(s, next);
                state.set(btn, s);
                applyVisualState(btn, s);
            }

            function releaseStream(s) {
                if (s.stream) {
                    s.stream.getTracks().forEach(function (t) { t.stop(); });
                    s.stream = null;
                }
            }

            async function startRecording(btn) {
                try {
                    var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    var mr = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
                    var chunks = [];
                    mr.ondataavailable = function (e) { chunks.push(e.data); };
                    mr.onstop = function () { upload(btn, chunks); };
                    setState(btn, {
                        recording: true, uploading: false,
                        mediaRecorder: mr, stream: stream, chunks: chunks,
                    });
                    mr.start();
                } catch (e) {
                    console.error('[voice-recorder] getUserMedia failed', e);
                    alert('Microphone access denied.');
                }
            }

            function stopRecording(btn) {
                var s = state.get(btn);
                if (!s || !s.recording) return;
                setState(btn, { recording: false });
                if (s.mediaRecorder && s.mediaRecorder.state !== 'inactive') {
                    s.mediaRecorder.stop();
                }
            }

            async function upload(btn, chunks) {
                var s = state.get(btn) || {};
                releaseStream(s);
                if (!chunks || chunks.length === 0) {
                    setState(btn, { uploading: false });
                    return;
                }
                setState(btn, { uploading: true });
                try {
                    var blob = new Blob(chunks, { type: 'audio/webm' });
                    var form = new FormData();
                    form.append('file', blob, 'voice.webm');
                    form.append('kind', 'audio');
                    var csrf = document.querySelector('meta[name="csrf-token"]');
                    var headers = csrf ? { 'X-CSRF-TOKEN': csrf.content } : {};
                    var res = await fetch('/api/media/upload', {
                        method: 'POST', body: form, headers: headers, credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        alert('Voice note upload failed.');
                        return;
                    }
                    var asset = await res.json();
                    var wireMethod = btn.getAttribute('data-vr-uploaded') || 'sendWithMedia';
                    if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                        window.Livewire.dispatch(wireMethod, { mediaAssetId: asset.id });
                    } else {
                        console.error('[voice-recorder] Livewire not available');
                    }
                } catch (e) {
                    console.error('[voice-recorder] upload failed', e);
                    alert('Voice note upload failed.');
                } finally {
                    setState(btn, { uploading: false, chunks: [] });
                }
            }

            document.addEventListener('click', function (ev) {
                var btn = ev.target.closest('[data-vr-toggle]');
                if (!btn) return;
                ev.preventDefault();
                var s = state.get(btn) || {};
                if (s.uploading) return;
                if (s.recording) stopRecording(btn); else startRecording(btn);
            });

            function initButton(btn) {
                if (btn.__vrInit) return;
                btn.__vrInit = true;
                applyVisualState(btn, {});
            }

            var mo = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('[data-vr-toggle]')) initButton(node);
                        if (node.querySelectorAll) node.querySelectorAll('[data-vr-toggle]').forEach(initButton);
                    });
                });
            });
            mo.observe(document.body, { childList: true, subtree: true });
            document.querySelectorAll('[data-vr-toggle]').forEach(initButton);
        })();
        </script>
        @endauth
    </body>
</html>
