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
                <div class="flex items-center justify-between py-2 border-t border-white/[0.07]">
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
                <div class="flex items-center justify-between py-2 border-t border-white/[0.07]">
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
                    <div class="pl-2 py-1 border-t border-white/[0.07]">
                        <p class="text-xs text-white/40">
                            {{ $igPage->name }}{{ isset($igPage->metadata['username']) ? ' (@' . $igPage->metadata['username'] . ')' : '' }}
                        </p>
                    </div>
                @endforeach
            @endforeach

            <div class="{{ $instagramAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                @if(empty(config('services.meta.app_id')))
                    <p class="text-xs text-white/40">Requires META_APP_ID and META_APP_SECRET in .env</p>
                @else
                    <flux:button as="a" href="{{ route('connections.instagram.redirect') }}" variant="primary" size="sm" class="w-full" style="background: linear-gradient(135deg, #833AB4, #E1306C); border: none;">
                        {{ $instagramAccounts->isNotEmpty() ? 'Add Another Account' : 'Connect Instagram' }}
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
                <div class="flex items-center justify-between py-2 border-t border-white/[0.07]">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-xs text-white/80 truncate">{{ $account->name }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Active</span>
                        @if(! empty($account->metadata['gateway_mode']))
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400">QR</span>
                        @endif
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect '{{ addslashes($account->name) }}'? Messages from this number will stop coming in."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-400 hover:text-red-300 flex-shrink-0 ml-2"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $whatsappAccounts->isNotEmpty() ? 'mt-3' : '' }} space-y-2">
                <flux:modal.trigger name="whatsapp-connect">
                    <flux:button variant="outline" size="sm" class="w-full" icon="link">
                        {{ $whatsappAccounts->isNotEmpty() ? 'Add via Meta API' : 'Connect via Meta API' }}
                    </flux:button>
                </flux:modal.trigger>
                <flux:button
                    variant="primary"
                    size="sm"
                    class="w-full"
                    icon="qr-code"
                    wire:click="$dispatch('open-whatsapp-qr')"
                >
                    {{ $whatsappAccounts->isNotEmpty() ? 'Add via QR Code' : 'Connect via QR Code' }}
                </flux:button>
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
                <div class="flex items-center justify-between py-2 border-t border-white/[0.07]">
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

        {{-- TikTok --}}
        <div class="aio-card rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-black flex items-center justify-center text-white font-bold text-sm border border-white/[0.07]">TT</div>
                <div>
                    <h3 class="font-semibold text-white/80">TikTok</h3>
                    <p class="text-xs text-white/40">Direct Messages</p>
                </div>
            </div>

            @php $tiktokAccounts = $this->connectedAccounts->where('platform', 'tiktok'); @endphp
            @foreach($tiktokAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-white/[0.07]">
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
                <div class="flex items-center justify-between py-2 border-t border-white/[0.07]">
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
                    <div class="rounded-xl border border-white/[0.07] overflow-hidden" style="background: rgba(255,255,255,0.02);">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-white/[0.06]" style="background: rgba(234,67,53,0.08);">
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
                    <div class="rounded-xl border border-white/[0.07] overflow-hidden" style="background: rgba(255,255,255,0.02);">
                        <div class="flex items-center gap-2 px-4 py-3 border-b border-white/[0.06]" style="background: rgba(0,120,212,0.08);">
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
                    <form method="POST" action="{{ route('connections.email.connect') }}" class="space-y-3 rounded-xl border border-white/[0.07] p-4" style="background: rgba(255,255,255,0.02);">
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
            <div class="px-5 py-4 border-b border-white/[0.07]">
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
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $page->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                            {{ $page->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- WhatsApp Meta API Modal --}}
    <flux:modal name="whatsapp-connect" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-white/80">Connect WhatsApp via Meta API</h3>
                <p class="text-sm text-white/40 mt-1">You'll need a WhatsApp Business Account from Meta Business Manager.</p>
            </div>
            <form method="POST" action="{{ route('connections.whatsapp.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">WhatsApp Business Account ID (WABA ID)</label>
                    <input type="text" name="waba_id" placeholder="e.g. 123456789012345" required
                           class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none" />
                    <p class="mt-1 text-xs text-white/40">Found in Meta Business Manager → WhatsApp → Settings</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">System User Access Token</label>
                    <input type="text" name="access_token" placeholder="Your permanent system user token" required
                           class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none" />
                    <p class="mt-1 text-xs text-white/40">Generate from Meta Business Manager → System Users</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Connect WhatsApp</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Telegram Bot Modal --}}
    <flux:modal name="telegram-connect" class="w-full max-w-lg">
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-white/80">Connect Telegram Bot</h3>
                <p class="text-sm text-white/40 mt-1">Create a bot via <strong class="text-white/80">@BotFather</strong> on Telegram to get your bot token.</p>
            </div>
            <form method="POST" action="{{ route('connections.telegram.connect') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Bot Token</label>
                    <input type="text" name="bot_token" placeholder="e.g. 123456:ABCdefGHIjkl..." required
                           class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono" />
                    <p class="mt-1 text-xs text-white/40">Send /newbot to @BotFather → follow prompts → copy the token</p>
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

    {{-- WhatsApp QR Modal Component --}}
    @livewire('connections.whats-app-qr-modal')
</div>
