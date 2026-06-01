<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Connections') }}</h1>
            <p class="mt-1 text-sm text-white/40">{{ __('Connect your social media accounts to start receiving messages.') }}</p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-500/30 bg-green-500/10 p-4">
            <p class="text-sm text-green-400">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('syncing'))
        <div class="mb-6 rounded-xl border border-[#3b82f6]/30 bg-[#3b82f6]/10 p-4 flex items-center gap-3">
            <svg class="w-4 h-4 text-[#3b82f6] animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm text-[#3b82f6]">Syncing conversations in the background — this may take a minute. Check your inbox shortly.</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/10 p-4">
            <p class="text-sm text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Available Platforms --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-8">

        {{-- Facebook --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center text-white font-bold text-lg">f</div>
                <div>
                    <h3 class="font-semibold text-white/80">Facebook</h3>
                    <p class="text-xs text-white/40">Messenger</p>
                </div>
            </div>

            @php $facebookAccounts = $this->connectedAccounts->where('platform', 'facebook'); @endphp
            @foreach($facebookAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        @if($account->avatar)
                            <img src="{{ $account->avatar }}" class="w-5 h-5 rounded-full flex-shrink-0" />
                        @endif
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect '{{ addslashes($account->name) }}'? This will deactivate all pages linked to this account."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $facebookAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                @if(empty(config('services.meta.app_id')))
                    <p class="text-xs text-white/40">Requires META_APP_ID and META_APP_SECRET in .env</p>
                @else
                    <flux:button as="a" href="{{ route('connections.facebook.redirect') }}" variant="primary" size="sm" class="w-full">
                        {{ $facebookAccounts->isNotEmpty() ? 'Add Another Account' : 'Connect with Facebook' }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Instagram --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-sm">IG</div>
                <div>
                    <h3 class="font-semibold text-white/80">Instagram</h3>
                    <p class="text-xs text-white/40">Direct Messages</p>
                </div>
            </div>

            @php $instagramAccounts = $this->connectedAccounts->where('platform', 'instagram'); @endphp
            @foreach($instagramAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        @if($account->avatar)
                            <img src="{{ $account->avatar }}" class="w-5 h-5 rounded-full flex-shrink-0" />
                        @endif
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect '{{ addslashes($account->name) }}'? Instagram DMs will stop coming in."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
                @foreach($this->pages->where('platform', 'instagram')->where('connected_account_id', $account->id) as $igPage)
                    <div class="pl-2 py-1 border-t border-white/15">
                        <p class="text-xs text-white/40">
                            {{ $igPage->name }}{{ isset($igPage->metadata['username']) ? ' (@' . $igPage->metadata['username'] . ')' : '' }}
                        </p>
                    </div>
                @endforeach
            @endforeach

            <div class="{{ $instagramAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                @if(empty(config('services.meta.app_id')))
                    <p class="text-xs text-white/40">Requires META_APP_ID and META_APP_SECRET in .env</p>
                @else
                    <flux:button as="a" href="{{ route('connections.instagram-via-facebook.redirect') }}" variant="primary" size="sm" class="w-full" style="background: linear-gradient(135deg, #833AB4, #E1306C); border: none;">
                        {{ $instagramAccounts->isNotEmpty() ? 'Add via Meta' : 'Connect via Meta' }}
                    </flux:button>
                    <flux:button as="a" href="{{ route('connections.instagram.redirect') }}" variant="outline" size="sm" class="w-full">
                        {{ $instagramAccounts->isNotEmpty() ? 'Add Direct (IG Login)' : 'Connect Direct (IG Login)' }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- WhatsApp --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-green-500 flex items-center justify-center text-white font-bold text-sm">WA</div>
                <div>
                    <h3 class="font-semibold text-white/80">WhatsApp</h3>
                    <p class="text-xs text-white/40">Business API &amp; QR Connect</p>
                </div>
            </div>

            @php $whatsappAccounts = $this->connectedAccounts->where('platform', 'whatsapp'); @endphp
            @foreach($whatsappAccounts as $account)
                @php
                    $instanceName = $account->metadata['gateway_instance'] ?? null;
                    $isGateway    = ! empty($account->metadata['gateway_mode']);
                    $isOnline     = $isGateway && $instanceName && isset($waInstanceStates[$instanceName]);
                @endphp
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        @if($isGateway)
                            @if($isOnline)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-yellow-500/20 text-yellow-400">Disconnected</span>
                            @endif
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400">QR</span>
                        @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                        @if($isGateway && ! $isOnline)
                            <flux:button
                                wire:click="reconnectGateway({{ $account->id }})"
                                wire:loading.attr="disabled"
                                size="xs"
                                variant="ghost"
                                class="text-yellow-400 hover:text-yellow-300"
                            >
                                Reconnect
                            </flux:button>
                        @endif
                        <flux:button
                            wire:click="disconnect({{ $account->id }})"
                            wire:confirm="Disconnect '{{ addslashes($account->name) }}'? Messages from this number will stop coming in."
                            wire:loading.attr="disabled"
                            size="xs"
                            variant="ghost"
                            class="text-red-400 hover:text-red-300"
                        >
                            Disconnect
                        </flux:button>
                    </div>
                </div>
            @endforeach

            <div class="{{ $whatsappAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                {{-- Cloud API: official Meta path. Currently the only enabled connect option;
                     QR is hidden until we ship a more reliable gateway. --}}
                <flux:modal.trigger name="whatsapp-connect">
                    <flux:button variant="primary" size="sm" class="w-full" icon="shield-check">
                        {{ $whatsappAccounts->isNotEmpty() ? 'Add via Cloud API' : 'Connect via Cloud API' }}
                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-green-500/30 text-green-100 border border-green-400/30">Official</span>
                    </flux:button>
                </flux:modal.trigger>

                {{-- QR Scan — second connection path. Hidden when WUZAPI_QR_ENABLED=false. --}}
                @if(config('services.wuzapi.qr_enabled'))
                    <flux:button
                        variant="outline"
                        size="sm"
                        class="w-full"
                        icon="qr-code"
                        wire:click="$dispatch('open-whatsapp-qr')"
                    >
                        {{ $whatsappAccounts->isNotEmpty() ? 'Add via QR Scan' : 'Connect via QR Scan' }}
                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-semibold bg-yellow-400/15 text-yellow-300 border border-yellow-400/20">Personal use</span>
                    </flux:button>
                @endif

                <p class="text-[10px] text-white/40 leading-relaxed text-center px-2">
                    Cloud API is Meta's official WhatsApp Business pipe — stable, supports message templates, never drops because of WhatsApp updates.
                </p>
            </div>
        </div>

        {{-- Telegram --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-cyan-500 flex items-center justify-center text-white font-bold text-sm">TG</div>
                <div>
                    <h3 class="font-semibold text-white/80">Telegram</h3>
                    <p class="text-xs text-white/40">Bot API</p>
                </div>
            </div>

            @php $telegramAccounts = $this->connectedAccounts->where('platform', 'telegram'); @endphp
            @foreach($telegramAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect Telegram bot '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $telegramAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="telegram-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $telegramAccounts->isNotEmpty() ? 'Add Another Bot' : 'Connect Telegram Bot' }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- Web Chat Widget --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center text-white font-bold text-sm">WC</div>
                <div>
                    <h3 class="font-semibold text-white/80">Web Chat</h3>
                    <p class="text-xs text-white/40">Embed on your site</p>
                </div>
            </div>

            @php $webchatAccounts = $this->connectedAccounts->where('platform', 'webchat'); @endphp
            @foreach($webchatAccounts as $account)
                @php $page = $account->pages->where('platform', 'webchat')->first(); @endphp
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <div class="flex gap-1 flex-shrink-0 ml-2">
                        @if($page)
                            <flux:button wire:click="showWebChatSnippetFor({{ $page->id }})" size="xs" variant="ghost">Snippet</flux:button>
                        @endif
                        <flux:button
                            wire:click="disconnect({{ $account->id }})"
                            wire:confirm="Remove Web Chat widget '{{ addslashes($account->name) }}'? Your visitors will see the bubble disappear."
                            wire:loading.attr="disabled"
                            size="xs"
                            variant="ghost"
                            class="text-red-400 hover:text-red-300"
                        >
                            Disconnect
                        </flux:button>
                    </div>
                </div>
            @endforeach

            <div class="{{ $webchatAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                <flux:input
                    wire:model="webChatSiteName"
                    size="sm"
                    placeholder="Site name (e.g. Acme Store)"
                />
                <flux:button wire:click="connectWebChat" variant="primary" size="sm" class="w-full">
                    {{ $webchatAccounts->isNotEmpty() ? 'Add Another Widget' : 'Create Web Chat Widget' }}
                </flux:button>
                <p class="text-[10px] text-white/40 leading-relaxed text-center px-2">
                    Free, never breaks. Visitors chat from a bubble on your site; messages land here in real time.
                </p>
            </div>
        </div>

        {{-- Slack --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#4A154B] flex items-center justify-center text-white font-bold text-sm">SL</div>
                <div>
                    <h3 class="font-semibold text-white/80">Slack</h3>
                    <p class="text-xs text-white/40">Workspace bot</p>
                </div>
            </div>

            @php $slackAccounts = $this->connectedAccounts->where('platform', 'slack'); @endphp
            @foreach($slackAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect Slack workspace '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $slackAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="slack-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $slackAccounts->isNotEmpty() ? 'Add Another Workspace' : 'Connect Slack' }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- Discord --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-[#5865F2] flex items-center justify-center text-white font-bold text-sm">DC</div>
                <div>
                    <h3 class="font-semibold text-white/80">Discord</h3>
                    <p class="text-xs text-white/40">Bot via /support</p>
                </div>
            </div>

            @php $discordAccounts = $this->connectedAccounts->where('platform', 'discord'); @endphp
            @foreach($discordAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect Discord bot '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $discordAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="discord-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $discordAccounts->isNotEmpty() ? 'Add Another Bot' : 'Connect Discord' }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- TikTok --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-black flex items-center justify-center text-white font-bold text-sm border border-white/15">TT</div>
                <div>
                    <h3 class="font-semibold text-white/80">TikTok</h3>
                    <p class="text-xs text-white/40">Direct Messages</p>
                </div>
            </div>

            @php $tiktokAccounts = $this->connectedAccounts->where('platform', 'tiktok'); @endphp
            @foreach($tiktokAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect TikTok account '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $tiktokAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                @if(empty(config('services.tiktok.client_key')))
                    <p class="text-xs text-white/40">Requires TIKTOK_CLIENT_KEY in .env</p>
                @else
                    <flux:button as="a" href="{{ route('connections.tiktok.redirect') }}" variant="primary" size="sm" class="w-full">
                        {{ $tiktokAccounts->isNotEmpty() ? 'Add Another Account' : 'Connect TikTok' }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Snapchat — Coming Soon --}}
        <div class="aio-card rounded-2xl p-5 opacity-60">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-yellow-400/30 flex items-center justify-center text-yellow-600 font-bold text-sm">SC</div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-white/50">Snapchat</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-400/10 text-yellow-400/70 border border-yellow-400/20">Coming Soon</span>
                    </div>
                    <p class="text-xs text-white/25">Business Messaging</p>
                </div>
            </div>
            <p class="text-xs text-white/30 leading-relaxed">Snapchat's messaging API requires partner approval. We're working on it — stay tuned.</p>
        </div>

        {{-- Email --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white font-bold text-sm">@</div>
                <div>
                    <h3 class="font-semibold text-white/80">Email</h3>
                    <p class="text-xs text-white/40">Gmail, Outlook, IMAP</p>
                </div>
            </div>

            @php $emailAccounts = $this->connectedAccounts->where('platform', 'email'); @endphp
            @foreach($emailAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/15">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect email account '{{ addslashes($account->name) }}'?"
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <details class="{{ $emailAccounts->isNotEmpty() ? 'mt-3' : '' }} group">
                <summary class="flex items-center justify-center gap-2 w-full px-3 py-2 rounded-xl text-sm font-semibold cursor-pointer select-none list-none
                                text-white border border-white/[0.12] hover:border-orange-500/50 hover:bg-orange-500/5 transition-all"
                         style="background: rgba(255,255,255,0.04);">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ $emailAccounts->isNotEmpty() ? 'Add Another Email' : 'Connect Email' }}
                </summary>

                <div class="mt-3 space-y-4">

                    {{-- Gmail steps --}}
                    <div class="rounded-xl border border-white/15 overflow-hidden" style="background: rgba(255,255,255,0.02);">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-white/15" style="background: rgba(234,67,53,0.08);">
                            <svg class="size-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" style="color:#EA4335;">
                                <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 010 19.366V5.457c0-.886.716-1.542 1.601-1.542.49 0 .918.206 1.226.54L12 11.73l9.173-7.274c.308-.335.737-.541 1.226-.541.885 0 1.601.656 1.601 1.542z"/>
                            </svg>
                            <span class="text-xs font-semibold text-white/70">Gmail setup — 2 steps</span>
                        </div>
                        <div class="px-4 py-3 space-y-2.5">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#EA4335;">1</span>
                                <div>
                                    <p class="text-xs text-white/70 font-medium">Enable 2-Step Verification</p>
                                    <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] text-[#4285F4] hover:underline mt-0.5">
                                        myaccount.google.com → Security → 2-Step Verification
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#EA4335;">2</span>
                                <div>
                                    <p class="text-xs text-white/70 font-medium">Create an App Password</p>
                                    <a href="https://myaccount.google.com/apppasswords" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] text-[#4285F4] hover:underline mt-0.5">
                                        myaccount.google.com → App Passwords
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <p class="text-[11px] text-white/30 mt-0.5">Select app: "Mail" → generate → copy the 16-char password</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Outlook steps --}}
                    <div class="rounded-xl border border-white/15 overflow-hidden" style="background: rgba(255,255,255,0.02);">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-white/15" style="background: rgba(0,120,212,0.08);">
                            <svg class="size-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" style="color:#0078D4;">
                                <path d="M7.88 12.04q0 .45-.11.87-.1.41-.33.74-.22.33-.58.52-.37.2-.87.2t-.85-.2q-.35-.21-.57-.55-.22-.33-.33-.75-.1-.42-.1-.86t.1-.87q.1-.43.34-.76.22-.34.59-.54.36-.2.87-.2t.86.2q.35.21.57.55.22.34.32.77.1.43.1.88zM24 12v9.38q0 .46-.33.8-.33.32-.8.32H7.13q-.46 0-.8-.33-.32-.33-.32-.8V18H1q-.41 0-.7-.3-.3-.29-.3-.7V7q0-.41.3-.7Q.58 6 1 6h6.1V2.55q0-.44.3-.75.3-.3.75-.3h12.9q.44 0 .75.3.3.3.3.75V10.85l1.24.72q.07.04.07.13z"/>
                            </svg>
                            <span class="text-xs font-semibold text-white/70">Outlook / Hotmail setup</span>
                        </div>
                        <div class="px-4 py-3 space-y-2.5">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#0078D4;">1</span>
                                <div>
                                    <p class="text-xs text-white/70 font-medium">Enable IMAP access</p>
                                    <a href="https://outlook.live.com/mail/0/options/mail/accounts/popImap" target="_blank"
                                       class="inline-flex items-center gap-1 text-[11px] text-[#4285F4] hover:underline mt-0.5">
                                        Outlook Settings → Mail → Sync → POP and IMAP
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 size-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white" style="background:#0078D4;">2</span>
                                <p class="text-xs text-white/60 pt-0.5">Use your regular Outlook password below. No app password needed.</p>
                            </div>
                        </div>
                    </div>

                    {{-- The form --}}
                    <form method="POST" action="{{ route('connections.email.connect') }}" class="space-y-3 rounded-xl border border-white/15 p-4" style="background: rgba(255,255,255,0.02);">
                        @csrf
                        <p class="text-xs font-semibold text-white/60">Enter your credentials</p>
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1">Email Address</label>
                            <input type="email" name="email" placeholder="you@gmail.com" required
                                   class="w-full rounded-lg px-3 py-2 text-sm text-white/80 placeholder-white/20 focus:outline-none"
                                   style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1">Password / App Password</label>
                            <input type="password" name="password" placeholder="Paste the 16-char app password here" required
                                   class="w-full rounded-lg px-3 py-2 text-sm text-white/80 placeholder-white/20 focus:outline-none"
                                   style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);" />
                        </div>
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-white transition-all aio-btn-primary">
                            Connect Email
                        </button>
                    </form>

                </div>
            </details>
        </div>

    </div>

    {{-- Connected Pages & Accounts Table --}}
    @if($this->pages->isNotEmpty())
        <div class="aio-card rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-white/15">
                <h3 class="font-semibold text-white/80">Connected Pages & Accounts</h3>
                <p class="text-xs text-white/40 mt-0.5">All active pages receiving messages</p>
            </div>
            <div class="divide-y divide-[#1e2536]">
                @foreach($this->pages as $page)
                    <div class="flex items-center gap-4 px-5 py-3">
                        <div class="size-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 {{ match($page->platform) {
                            'facebook' => 'bg-blue-500',
                            'instagram' => 'bg-pink-500',
                            'whatsapp' => 'bg-green-500',
                            'telegram' => 'bg-cyan-500',
                            'tiktok' => 'bg-red-500',
                            'snapchat' => 'bg-yellow-400 text-yellow-900',
                            'email' => 'bg-orange-500',
                            default => 'bg-gray-500',
                        } }}">
                            {{ strtoupper(substr($page->platform, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white/80 truncate">{{ $page->name }}</p>
                            <p class="text-xs text-white/40">{{ ucfirst($page->platform) }} {{ isset($page->metadata['category']) ? '· ' . $page->metadata['category'] : '' }}</p>
                            @if(($page->metadata['subscription_error'] ?? null) === 'twofa_required')
                                <p class="text-xs text-yellow-400 mt-0.5">
                                    ⚠ Not receiving messages — Two-Factor Authentication required on Facebook.
                                    <a href="https://www.facebook.com/settings?tab=security" target="_blank" class="underline hover:text-yellow-300">Enable 2FA on Facebook</a>,
                                    then <button wire:click="retryPageSubscription({{ $page->id }})" class="underline hover:text-yellow-300 cursor-pointer">retry here</button>.
                                </p>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $page->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                            {{ $page->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- WhatsApp Cloud API (Meta) Modal — guided onboarding --}}
    <flux:modal name="whatsapp-connect" class="w-full max-w-2xl">
        <div class="space-y-5" x-data="{ help: null }">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-white/90">Connect WhatsApp via Cloud API</h3>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-green-500/15 text-green-300 border border-green-400/20">Official Meta API</span>
                </div>
                <p class="text-sm text-white/50 mt-1">
                    Meta's official WhatsApp Business pipe. Stable, supports message templates, never drops because of WhatsApp protocol updates.
                </p>
            </div>

            {{-- Prerequisites strip --}}
            <div class="rounded-xl border border-blue-400/15 bg-blue-400/5 p-3 text-xs text-blue-100/85 leading-relaxed">
                <strong class="text-blue-50">Before you start, make sure you have:</strong>
                <ul class="mt-1.5 ml-4 list-disc space-y-0.5">
                    <li>A Facebook account that owns (or is admin of) a Meta Business account</li>
                    <li>A phone number that is <strong class="text-yellow-200">not currently active</strong> on any WhatsApp / WhatsApp Business app — Cloud API takes the number over</li>
                    <li>Permission to verify the business in Meta Business Manager (your Meta Verified status, if needed for Egyptian / KSA / UAE numbers)</li>
                </ul>
            </div>

            {{-- Step-by-step setup --}}
            <div class="rounded-xl border border-white/15 bg-white/[0.02] p-4 space-y-4">
                <p class="text-xs font-semibold text-white/70 uppercase tracking-wide">Setup (≈ 20–40 min, one-time)</p>

                {{-- Step 1 --}}
                <div class="text-xs text-white/60 leading-relaxed">
                    <p class="font-semibold text-white/85">① Create a WhatsApp Business Account in Meta Business Manager</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-white/30">
                        <li>Open <a href="https://business.facebook.com/settings/whatsapp-business-accounts" target="_blank" rel="noopener" class="text-blue-400 hover:underline">business.facebook.com/settings/whatsapp-business-accounts</a> (direct link to the WhatsApp Accounts page)</li>
                        <li>Top-right click <strong class="text-white/80">Add → Create a WhatsApp Account</strong> (skip if you already have one)</li>
                        <li>Pick the business that owns it; give it a display name (this is what your customers will see in WhatsApp)</li>
                    </ol>
                </div>

                {{-- Step 2 --}}
                <div class="text-xs text-white/60 leading-relaxed">
                    <p class="font-semibold text-white/85">② Add and verify your phone number</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-white/30">
                        <li>Click your WhatsApp account → <strong class="text-white/80">Phone numbers → Add phone number</strong></li>
                        <li>Pick a verification method (SMS or call) — Meta will read out / text you a 6-digit code</li>
                        <li>Pick a display name (e.g. "Acme Support"). Meta reviews it for ~24h; you can keep going while it's pending</li>
                    </ol>
                    <p class="mt-1 ml-4 text-yellow-200/70">
                        ⚠ The phone <em>cannot</em> be in use on the regular WhatsApp app simultaneously. Log it out everywhere first.
                    </p>
                </div>

                {{-- Step 3 — WABA ID --}}
                <div class="text-xs text-white/60 leading-relaxed">
                    <p class="font-semibold text-white/85">③ Copy the WhatsApp Business Account ID (WABA ID) — paste it below</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-white/30">
                        <li>Still on <a href="https://business.facebook.com/settings/whatsapp-business-accounts" target="_blank" rel="noopener" class="text-blue-400 hover:underline">the WhatsApp Accounts page</a>, click your WhatsApp Business Account</li>
                        <li>Look at the top of the panel — under the account name there's a 15- or 16-digit number labelled <strong class="text-white/80">"WhatsApp Business Account ID"</strong>. Click the copy icon next to it.</li>
                        <li>Alternative: open <a href="https://business.facebook.com/wa/manage" target="_blank" rel="noopener" class="text-blue-400 hover:underline">WhatsApp Manager</a> → top of any page shows the same ID</li>
                    </ol>
                    <p class="mt-1.5 ml-4">
                        <button type="button" @click="help = (help === 'waba_id' ? null : 'waba_id')" class="text-blue-400 hover:underline">
                            <span x-text="help === 'waba_id' ? '▼ Hide' : '▶ Show'"></span> what the ID looks like
                        </button>
                    </p>
                    <div x-show="help === 'waba_id'" x-cloak class="mt-2 ml-4 p-2 rounded bg-black/30 text-white/70">
                        Looks like <code class="bg-black/40 px-1 rounded">110424298547381</code>. Always digits, no dashes, 15–17 chars.
                        Don't confuse it with the <em>Phone Number ID</em> (which is a different value also visible on the WABA page).
                    </div>
                </div>

                {{-- Step 4 — App + System User --}}
                <div class="text-xs text-white/60 leading-relaxed">
                    <p class="font-semibold text-white/85">④ Create / open a Meta App and link it to your WABA</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-white/30">
                        <li>Go to <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener" class="text-blue-400 hover:underline">developers.facebook.com/apps</a></li>
                        <li>If you don't have one yet: <strong class="text-white/80">Create App → Business → Next</strong>, give it a name, link it to your business</li>
                        <li>In the app's left sidebar: <strong class="text-white/80">Add Products → WhatsApp → Set Up</strong></li>
                        <li>WhatsApp Setup screen → <strong class="text-white/80">"Select a WhatsApp Business Account"</strong> → pick the WABA from step ①</li>
                    </ol>
                </div>

                {{-- Step 5 — System User Token --}}
                <div class="text-xs text-white/60 leading-relaxed">
                    <p class="font-semibold text-white/85">⑤ Generate a permanent System User access token — paste it below</p>
                    <ol class="mt-1 ml-4 space-y-1 list-decimal list-outside marker:text-white/30">
                        <li>Open <a href="https://business.facebook.com/settings/system-users" target="_blank" rel="noopener" class="text-blue-400 hover:underline">business.facebook.com/settings/system-users</a></li>
                        <li>Click <strong class="text-white/80">Add → Create System User</strong>. Name it (e.g. "OT1-Pro API"), set role to <strong class="text-white/80">Admin</strong></li>
                        <li>With the system user selected, click <strong class="text-white/80">Add Assets → Apps</strong> → choose your WhatsApp app from step ④, toggle <strong class="text-white/80">"Develop app"</strong> to on, save</li>
                        <li>Click <strong class="text-white/80">Add Assets → WhatsApp Accounts</strong> → choose the WABA, toggle <strong class="text-white/80">"Manage WhatsApp account"</strong> to on, save</li>
                        <li>Now click <strong class="text-white/80">Generate New Token</strong></li>
                        <li>App: pick the WhatsApp app from step ④</li>
                        <li>Token expiration: <strong class="text-yellow-200">"Never"</strong> (highly recommended — otherwise you'll have to re-paste a new token every 60 days)</li>
                        <li>Permissions — check both:
                            <ul class="mt-0.5 ml-4 list-disc list-outside marker:text-white/30">
                                <li><code class="text-[11px] bg-black/30 px-1 rounded">whatsapp_business_messaging</code></li>
                                <li><code class="text-[11px] bg-black/30 px-1 rounded">whatsapp_business_management</code></li>
                            </ul>
                        </li>
                        <li>Click <strong class="text-white/80">Generate Token</strong>, then <strong class="text-yellow-200">copy it immediately</strong> — Meta will not show it again. The token starts with <code>EAA</code> and is roughly 200 characters long.</li>
                    </ol>
                    <p class="mt-1.5 ml-4">
                        <button type="button" @click="help = (help === 'token' ? null : 'token')" class="text-blue-400 hover:underline">
                            <span x-text="help === 'token' ? '▼ Hide' : '▶ Show'"></span> "Generate New Token isn't there" / I see an error
                        </button>
                    </p>
                    <div x-show="help === 'token'" x-cloak class="mt-2 ml-4 p-2 rounded bg-black/30 text-white/70 space-y-1">
                        <p>The button only appears once the system user has the WhatsApp app <em>and</em> the WABA assigned to it (steps 3–4 above). If it's greyed out or missing:</p>
                        <ul class="ml-4 list-disc">
                            <li>Confirm your business is the <strong>owner</strong> (not just admin) of both the app and the WABA</li>
                            <li>Confirm the system user role is <strong>Admin</strong>, not Employee</li>
                            <li>If your business is in Egypt / KSA / UAE / similar, Meta Verified business status may be required — Settings → Security Center → Business Verification</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Pricing teaser --}}
            <div class="rounded-xl border border-yellow-400/20 bg-yellow-400/5 p-3">
                <p class="text-xs text-yellow-200/85 leading-relaxed">
                    <strong class="text-yellow-100">Pricing:</strong> Meta bills your WABA directly — not us.
                    Replies sent within 24 hours of a customer's message are <strong class="text-yellow-100">free, unlimited</strong>.
                    Marketing / utility templates outside that window cost cents per message and depend on the recipient's country.
                    Live estimates show up in the Campaigns page when you build a broadcast.
                    <a href="https://developers.facebook.com/docs/whatsapp/pricing" target="_blank" rel="noopener" class="underline hover:text-yellow-100">Meta's pricing reference →</a>
                </p>
            </div>

            <form method="POST" action="{{ route('connections.whatsapp.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-white/70 mb-1.5">
                        WhatsApp Business Account ID (WABA ID)
                        <span class="text-white/40 font-normal">— from step ③</span>
                    </label>
                    <input type="text" name="waba_id" placeholder="110424298547381" required pattern="[0-9]{12,18}"
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                    <p class="mt-1 text-[11px] text-white/40">15- to 17-digit number. Digits only — no dashes or spaces.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/70 mb-1.5">
                        System User Access Token
                        <span class="text-white/40 font-normal">— from step ⑤</span>
                    </label>
                    <textarea name="access_token" rows="3" placeholder="EAA..." required
                              class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono break-all"></textarea>
                    <p class="mt-1 text-[11px] text-white/40">
                        Starts with <code>EAA</code>, around 200 characters. We store this encrypted in our database; only your team can use it.
                    </p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="shield-check">Connect WhatsApp</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Telegram Bot Modal --}}
    <flux:modal name="telegram-connect" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-white/80">Connect Telegram Bot</h3>
                <p class="text-sm text-white/40 mt-1">A Telegram bot is a free account that messages people in your name. Anyone who chats with the bot lands in your inbox.</p>
            </div>

            <div class="rounded-lg border border-white/15 bg-white/[0.03] p-3 text-xs text-white/70 space-y-1.5 leading-relaxed">
                <p class="font-semibold text-white/85">How to get a bot token (3 minutes)</p>
                <p>① Open Telegram on your phone or desktop. Search for <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="text-emerald-300 hover:underline"><strong>@BotFather</strong></a> and start a chat.</p>
                <p>② Send the message <code class="text-emerald-300">/newbot</code></p>
                <p>③ BotFather asks for a <strong class="text-white/85">name</strong> — type whatever you want (e.g. "Acme Support").</p>
                <p>④ Then it asks for a <strong class="text-white/85">username</strong> — must end in <code class="text-emerald-300">bot</code> (e.g. <code class="text-emerald-300">acme_support_bot</code>).</p>
                <p>⑤ BotFather replies with a <strong class="text-white/85">token</strong> that looks like <code class="text-emerald-300">123456789:ABCdefGHIjklMNOpqrSTUvwxyz</code>. <strong>Copy the entire token</strong> and paste it below.</p>
                <p class="text-yellow-300/80 mt-2">⚠️ Keep this token private — it's the equivalent of a password for your bot.</p>
            </div>

            <form method="POST" action="{{ route('connections.telegram.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Bot Token (from BotFather)</label>
                    <input type="text" name="bot_token" placeholder="123456789:ABCdef..." required
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>

                <div class="rounded-lg border border-white/15 bg-white/[0.03] p-3 text-xs text-white/60 leading-relaxed">
                    <p class="font-semibold text-white/80 mb-1">After you click Connect Bot:</p>
                    <p>• Anyone who searches for <code class="text-emerald-300">@your_bot_username</code> on Telegram and starts a chat → lands in your inbox.</p>
                    <p>• Share the bot link with your customers: <code class="text-emerald-300">https://t.me/your_bot_username</code></p>
                    <p>• Replies you send from the inbox arrive in their Telegram.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Connect Bot</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Slack connect modal --}}
    <flux:modal name="slack-connect" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-white/80">Connect Slack Workspace</h3>
                <p class="text-sm text-white/40 mt-1">
                    Create a Slack App at <a href="https://api.slack.com/apps" target="_blank" rel="noopener" class="text-emerald-300 hover:underline">api.slack.com/apps</a>, install it to your workspace, then paste the credentials below.
                </p>
            </div>

            <div class="rounded-lg border border-white/15 bg-white/[0.03] p-3 text-xs text-white/60 space-y-1.5">
                <p class="font-semibold text-white/80">Setup checklist</p>
                <p>① Create app → "From scratch" → pick a workspace</p>
                <p>② OAuth &amp; Permissions → add Bot Token Scopes: <code class="text-emerald-300">chat:write</code>, <code class="text-emerald-300">channels:history</code>, <code class="text-emerald-300">groups:history</code>, <code class="text-emerald-300">im:history</code>, <code class="text-emerald-300">users:read</code></p>
                <p>③ Install to workspace → copy the <strong class="text-white/80">Bot User OAuth Token</strong> (starts with <code class="text-emerald-300">xoxb-</code>)</p>
                <p>④ Basic Information → copy the <strong class="text-white/80">Signing Secret</strong></p>
                <p>⑤ Event Subscriptions → enable, set Request URL to <code class="text-emerald-300">{{ url('/api/webhooks/slack') }}</code>, subscribe to bot events: <code class="text-emerald-300">message.channels</code>, <code class="text-emerald-300">message.groups</code>, <code class="text-emerald-300">message.im</code></p>
                <p>⑥ Invite the bot to any channels you want messages to flow from</p>
            </div>

            <form method="POST" action="{{ route('connections.slack.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Bot User OAuth Token</label>
                    <input type="text" name="bot_token" placeholder="xoxb-..." required
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Signing Secret</label>
                    <input type="text" name="signing_secret" placeholder="32-character hex string" required
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Connect Slack</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Discord connect modal --}}
    <flux:modal name="discord-connect" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-white/80">Connect Discord Bot</h3>
                <p class="text-sm text-white/40 mt-1">
                    Create an Application at <a href="https://discord.com/developers/applications" target="_blank" rel="noopener" class="text-emerald-300 hover:underline">discord.com/developers/applications</a>, add a Bot user, then paste the credentials below.
                </p>
            </div>

            <div class="rounded-lg border border-white/15 bg-white/[0.03] p-3 text-xs text-white/60 space-y-1.5">
                <p class="font-semibold text-white/80">Setup checklist</p>
                <p>① New Application → name it → copy <strong class="text-white/80">Application ID</strong> from General Information</p>
                <p>② Copy the <strong class="text-white/80">Public Key</strong> from the same page</p>
                <p>③ Bot tab → Reset Token → copy the <strong class="text-white/80">Bot Token</strong> (shown only once)</p>
                <p>④ General Information → set <strong class="text-white/80">Interactions Endpoint URL</strong> = <code class="text-emerald-300">{{ url('/api/webhooks/discord') }}</code></p>
                <p>⑤ Installation tab → enable Guild Install with <code class="text-emerald-300">applications.commands</code> scope, then use the Install Link to add the bot to your server</p>
                <p class="text-emerald-300/80">After connecting, your members type <code>/support &lt;message&gt;</code> in any channel; messages land in this inbox and your replies DM them back.</p>
            </div>

            <form method="POST" action="{{ route('connections.discord.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Application ID</label>
                    <input type="text" name="application_id" placeholder="18-digit numeric snowflake" required
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Public Key</label>
                    <input type="text" name="public_key" placeholder="64-character hex" required
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Bot Token</label>
                    <input type="text" name="bot_token" placeholder="MTI..." required
                           class="w-full rounded-lg border border-white/15 bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Connect Discord</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- WhatsApp QR Modal Component --}}
    @livewire('connections.whats-app-qr-modal')

    {{-- Web Chat: embed-snippet modal (shown after creating a widget OR via "Snippet" button) --}}
    <flux:modal wire:model="showWebChatModal" class="w-full max-w-2xl">
        @if($newWebChatId)
            @php
                $widgetSrc = url('/widget.js');
                $snippet = '<script src="' . $widgetSrc . '" data-widget-id="' . $newWebChatId . '" defer></script>';
                $testUrl = url('/webchat-test.html?wid=' . $newWebChatId);
            @endphp
            <div class="space-y-5" x-data="{ tab: 'wordpress' }">
                {{-- Header --}}
                <div>
                    <flux:heading size="lg">Your Web Chat widget is ready</flux:heading>
                    <flux:text class="mt-1">Add the snippet below to your website and a green chat bubble appears in the bottom-right corner. Visitors who click it can chat with you — their messages land in this inbox.</flux:text>
                </div>

                {{-- Snippet with copy button --}}
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-2">① Copy this snippet</p>
                    <div class="rounded-xl border border-white/10 bg-zinc-950/60 p-3">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <span class="text-[10px] text-white/40">embed snippet</span>
                            <button
                                type="button"
                                x-data
                                x-on:click="navigator.clipboard.writeText($refs.snippet.innerText); $el.innerText = '✓ Copied'; setTimeout(() => $el.innerText = 'Copy', 1500)"
                                class="text-[11px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30"
                            >
                                Copy
                            </button>
                        </div>
                        <pre x-ref="snippet" class="text-xs text-emerald-200 whitespace-pre-wrap break-all leading-relaxed">{{ $snippet }}</pre>
                    </div>
                </div>

                {{-- Platform-specific install instructions (tabs) --}}
                <div>
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-2">② Paste it on your site — pick where your site lives</p>
                    <div class="flex flex-wrap gap-1 mb-3 border-b border-white/10">
                        @foreach(['wordpress' => 'WordPress', 'shopify' => 'Shopify', 'wix' => 'Wix', 'squarespace' => 'Squarespace', 'webflow' => 'Webflow', 'html' => 'Custom HTML'] as $key => $label)
                            <button
                                type="button"
                                x-on:click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'bg-emerald-500/15 text-emerald-300 border-b-2 border-emerald-400' : 'text-white/50 hover:text-white/80'"
                                class="px-3 py-1.5 text-xs font-medium transition rounded-t-md"
                            >{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4 text-xs text-white/70 leading-relaxed space-y-2 min-h-[140px]">
                        {{-- WordPress --}}
                        <div x-show="tab === 'wordpress'" x-cloak>
                            <p class="text-white/85 font-semibold mb-2">WordPress (easiest way)</p>
                            <p>① Install the free plugin <strong class="text-white/85">"Insert Headers and Footers"</strong> by WPBeginner (or "WPCode").</p>
                            <p>② In your WP admin: <strong class="text-white/85">Settings → Insert Headers and Footers</strong>.</p>
                            <p>③ Paste the snippet into the <strong class="text-white/85">"Scripts in Footer"</strong> box.</p>
                            <p>④ Click <strong class="text-white/85">Save</strong>. The bubble appears on every page within ~30 seconds (browser cache may delay).</p>
                            <p class="text-white/45 mt-2">No FTP / no theme editing needed.</p>
                        </div>

                        {{-- Shopify --}}
                        <div x-show="tab === 'shopify'" x-cloak>
                            <p class="text-white/85 font-semibold mb-2">Shopify</p>
                            <p>① In your Shopify admin: <strong class="text-white/85">Online Store → Themes</strong>.</p>
                            <p>② On your active theme click <strong class="text-white/85">Actions → Edit code</strong>.</p>
                            <p>③ Open <code class="text-emerald-300">layout/theme.liquid</code> in the file list.</p>
                            <p>④ Find the line with <code class="text-emerald-300">&lt;/body&gt;</code> (near the bottom). Paste the snippet on the line just above it.</p>
                            <p>⑤ Click <strong class="text-white/85">Save</strong>. Refresh your storefront — the bubble shows up.</p>
                        </div>

                        {{-- Wix --}}
                        <div x-show="tab === 'wix'" x-cloak>
                            <p class="text-white/85 font-semibold mb-2">Wix</p>
                            <p>① In your Wix dashboard: <strong class="text-white/85">Settings → Custom Code</strong> (under "Advanced").</p>
                            <p>② Click <strong class="text-white/85">+ Add Custom Code</strong>.</p>
                            <p>③ Paste the snippet into the code box. Set:</p>
                            <p class="ml-4">• Name: <code class="text-emerald-300">OT1-Pro Chat</code></p>
                            <p class="ml-4">• Add Code to Pages: <strong class="text-white/85">All pages</strong></p>
                            <p class="ml-4">• Place Code in: <strong class="text-white/85">Body — end</strong></p>
                            <p>④ Click <strong class="text-white/85">Apply</strong>. Publish your site if needed.</p>
                            <p class="text-white/45 mt-2">Note: Wix free plans don't allow custom code — you need a Premium plan.</p>
                        </div>

                        {{-- Squarespace --}}
                        <div x-show="tab === 'squarespace'" x-cloak>
                            <p class="text-white/85 font-semibold mb-2">Squarespace</p>
                            <p>① <strong class="text-white/85">Settings → Advanced → Code Injection</strong>.</p>
                            <p>② Paste the snippet into the <strong class="text-white/85">Footer</strong> box.</p>
                            <p>③ Click <strong class="text-white/85">Save</strong>.</p>
                            <p class="text-white/45 mt-2">Note: Code Injection requires a Business plan or higher.</p>
                        </div>

                        {{-- Webflow --}}
                        <div x-show="tab === 'webflow'" x-cloak>
                            <p class="text-white/85 font-semibold mb-2">Webflow</p>
                            <p>① Open your project → <strong class="text-white/85">Project Settings → Custom Code</strong>.</p>
                            <p>② Paste the snippet in the <strong class="text-white/85">Footer Code</strong> box.</p>
                            <p>③ Click <strong class="text-white/85">Save Changes</strong>, then publish your site.</p>
                        </div>

                        {{-- Custom HTML --}}
                        <div x-show="tab === 'html'" x-cloak>
                            <p class="text-white/85 font-semibold mb-2">Plain HTML / your own framework</p>
                            <p>Open the HTML file (or template) for every page you want the bubble on. Find the closing <code class="text-emerald-300">&lt;/body&gt;</code> tag and paste the snippet on the line right before it. Example:</p>
                            <pre class="mt-2 p-2 bg-zinc-950/60 rounded text-[11px] text-emerald-200/90 overflow-x-auto"><code>&lt;body&gt;
  ... your page content ...
  {{ $snippet }}
&lt;/body&gt;</code></pre>
                            <p class="mt-2 text-white/45">Works with React/Vue/Next.js too — drop it in your root layout / <code>_document.tsx</code> / <code>app.html</code>.</p>
                        </div>
                    </div>
                </div>

                {{-- Test it --}}
                <div class="rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-4">
                    <p class="text-[10px] uppercase tracking-wider text-emerald-300/80 mb-1">③ Test it now (without your site)</p>
                    <p class="text-xs text-white/70">We hosted a demo page that already has your widget embedded. Open it, click the green bubble, send a test message — then watch your inbox.</p>
                    <a href="{{ url('/webchat-test.html') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 mt-2 text-xs font-medium text-emerald-300 hover:text-emerald-200">
                        <flux:icon.arrow-top-right-on-square class="w-3.5 h-3.5" />
                        Open test page
                    </a>
                </div>

                {{-- Where messages go --}}
                <div class="rounded-xl border border-white/10 bg-white/[0.03] p-3 text-xs text-white/60">
                    <p><strong class="text-white/80">Where messages go:</strong> Site visitors' messages appear in your <a href="{{ route('inbox') }}" class="text-emerald-300 hover:underline">Inbox</a> under the channel <strong class="text-white/85">"Web Chat"</strong>. Your replies are pushed back to the chat bubble within ~1.5 seconds.</p>
                </div>

                {{-- Troubleshooting --}}
                <details class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
                    <summary class="text-xs text-white/60 cursor-pointer hover:text-white/80">Bubble isn't appearing? Click to troubleshoot</summary>
                    <div class="mt-3 space-y-1.5 text-xs text-white/55 leading-relaxed">
                        <p><strong class="text-white/80">Wait 30 seconds and refresh.</strong> Many CDNs (Cloudflare, etc.) cache HTML — your snippet update may not be live yet.</p>
                        <p><strong class="text-white/80">Check browser console</strong> (F12). If you see a CSP error, your site has a Content-Security-Policy that needs <code class="text-emerald-300">{{ parse_url(url('/'), PHP_URL_HOST) }}</code> added to <code class="text-emerald-300">script-src</code>.</p>
                        <p><strong class="text-white/80">Ad blockers</strong> can sometimes block widgets. Disable yours and reload to confirm.</p>
                        <p><strong class="text-white/80">Snippet in the wrong place?</strong> It must be in the page <code class="text-emerald-300">&lt;body&gt;</code> — not <code class="text-emerald-300">&lt;head&gt;</code>. The <code>defer</code> attribute means it loads after the page is ready.</p>
                    </div>
                </details>

                {{-- Widget id reference --}}
                <div class="text-[11px] text-white/40 border-t border-white/5 pt-3">
                    Widget ID: <code class="text-emerald-300/80">{{ $newWebChatId }}</code> — keep this private; anyone with it can post messages to your inbox under this widget.
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeWebChatModal">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
