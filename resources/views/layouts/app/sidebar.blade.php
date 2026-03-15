<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @php $user = auth()->user(); $team = $user?->currentTeam; @endphp

            <flux:sidebar.nav>
                {{-- Inbox Section --}}
                @if($user->isHeadAdmin() || $user->hasPermission('inbox'))
                    <flux:sidebar.group :heading="__('Inbox')" class="grid">
                        <flux:sidebar.item icon="inbox" :href="route('inbox')" :current="request()->routeIs('inbox*') && !request('pageId')" wire:navigate>
                            {{ __('All Messages') }}
                        </flux:sidebar.item>

                        @if($team)
                            @foreach($team->getActivePages() as $page)
                                <flux:sidebar.item
                                    :icon="match($page->platform) {
                                        'instagram' => 'camera',
                                        'whatsapp' => 'phone',
                                        'telegram' => 'paper-airplane',
                                        default => 'chat-bubble-left-right',
                                    }"
                                    :href="route('inbox', ['pageId' => $page->id])"
                                    :current="request('pageId') == $page->id"
                                    wire:navigate
                                    class="pl-8"
                                >
                                    {{ $page->name }}
                                </flux:sidebar.item>
                            @endforeach
                        @endif

                        @if($user->isHeadAdmin() || $user->hasPermission('contacts'))
                            <flux:sidebar.item icon="users" :href="route('contacts.index')" :current="request()->routeIs('contacts*')" wire:navigate>
                                {{ __('Contacts') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- Manage Section --}}
                @php
                    $showManage = $user->isHeadAdmin()
                        || $user->hasPermission('dashboard')
                        || $user->hasPermission('analytics')
                        || $user->hasPermission('connections')
                        || $user->hasPermission('ai-chat');
                @endphp
                @if($showManage)
                    <flux:sidebar.group :heading="__('Manage')" class="grid">
                        @if($user->isHeadAdmin() || $user->hasPermission('dashboard'))
                            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:sidebar.item>
                        @endif
                        @if($user->isHeadAdmin() || $user->hasPermission('analytics'))
                            <flux:sidebar.item icon="chart-bar" :href="route('analytics')" :current="request()->routeIs('analytics')" wire:navigate>
                                {{ __('Analytics') }}
                            </flux:sidebar.item>
                        @endif
                        @if($user->isHeadAdmin() || $user->hasPermission('connections'))
                            <flux:sidebar.item icon="link" :href="route('connections.index')" :current="request()->routeIs('connections*')" wire:navigate>
                                {{ __('Connections') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="paper-airplane" :href="route('campaigns.index')" :current="request()->routeIs('campaigns*')" wire:navigate class="pl-8">
                                {{ __('Campaigns') }}
                            </flux:sidebar.item>
                        @endif
                        @if($user->isHeadAdmin() || $user->hasPermission('ai-chat'))
                            <flux:sidebar.item icon="sparkles" :href="route('ai-chat')" :current="request()->routeIs('ai-chat')" wire:navigate>
                                {{ __('AI Chat') }}
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                @if($user->isHeadAdmin() || $user->hasPermission('ai-settings'))
                    <flux:sidebar.item
                        icon="{{ $team?->ai_enabled ? 'bolt' : 'pause' }}"
                        :href="route('settings.ai')"
                        :current="request()->routeIs('settings.ai') && !request()->routeIs('settings.ai.*')"
                        wire:navigate
                    >
                        {{ __('AI') }} <span class="ml-1 text-xs {{ $team?->ai_enabled ? 'text-green-500' : 'text-red-500' }}">({{ $team?->ai_enabled ? 'ON' : 'OFF' }})</span>
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="cog-6-tooth"
                        :href="route('settings.ai.config')"
                        :current="request()->routeIs('settings.ai.config')"
                        wire:navigate
                        class="pl-8"
                    >
                        {{ __('AI Config') }}
                    </flux:sidebar.item>
                @endif

                @if($user->isHeadAdmin() || $user->hasPermission('ai-settings'))
                    <flux:sidebar.item
                        icon="bolt"
                        :href="route('settings.quick-replies')"
                        :current="request()->routeIs('settings.quick-replies')"
                        wire:navigate
                    >
                        {{ __('Quick Replies') }}
                    </flux:sidebar.item>
                @endif

                @if($user->canManageAdmins())
                    <flux:sidebar.item
                        icon="user-group"
                        :href="route('settings.admins')"
                        :current="request()->routeIs('settings.admins')"
                        wire:navigate
                    >
                        {{ __('Admins') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="queue-list"
                        :href="route('settings.webhook-logs')"
                        :current="request()->routeIs('settings.webhook-logs')"
                        wire:navigate
                    >
                        {{ __('Webhook Logs') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts

        @auth
        @php $notifTeamId = auth()->user()->currentTeam?->id; @endphp
        @if($notifTeamId)
        <script>
        (function () {
            const teamId = @json($notifTeamId);

            // Request browser notification permission once
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            function setupEchoListener() {
                if (!window.Echo) return;

                window.Echo.private('team.' + teamId)
                    .listen('.message.received', function (e) {
                        // Show browser notification if tab is not focused
                        if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
                            new Notification('New message from ' + (e.contactName || 'a contact'), {
                                body: e.preview || '',
                                icon: '/favicon.ico',
                                tag: 'conversation-' + e.conversationId,
                            });
                        }

                        // Refresh Livewire inbox list if component is on the page
                        Livewire.dispatch('refreshInbox');
                    });
            }

            // Echo may not be ready synchronously — wait for it
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
