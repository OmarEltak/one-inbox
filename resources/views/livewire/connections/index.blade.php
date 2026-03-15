<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Connections</flux:heading>
            <flux:text class="mt-1">Connect your social media accounts to start receiving messages.</flux:text>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <flux:text class="text-green-700 dark:text-green-400">{{ session('success') }}</flux:text>
        </div>
    @endif
    @if(session('syncing'))
        <div class="mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 flex items-center gap-3">
            <flux:icon name="arrow-path" class="w-4 h-4 text-blue-500 animate-spin flex-shrink-0" />
            <flux:text class="text-blue-700 dark:text-blue-400">Syncing conversations in the background — this may take a minute. Check your inbox shortly.</flux:text>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <flux:text class="text-red-700 dark:text-red-400">{{ session('error') }}</flux:text>
        </div>
    @endif

    {{-- Available Platforms --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-8">

        {{-- Facebook --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center text-white font-bold">f</div>
                <div>
                    <flux:heading size="sm">Facebook</flux:heading>
                    <flux:text size="xs">Messenger</flux:text>
                </div>
            </div>

            {{-- Connected Facebook accounts --}}
            @php $facebookAccounts = $this->connectedAccounts->where('platform', 'facebook'); @endphp
            @foreach($facebookAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-2 min-w-0">
                        @if($account->avatar)
                            <img src="{{ $account->avatar }}" class="w-5 h-5 rounded-full flex-shrink-0" />
                        @endif
                        <flux:text size="xs" class="truncate">{{ $account->name }}</flux:text>
                        <flux:badge color="green" size="sm">Active</flux:badge>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect '{{ addslashes($account->name) }}'? This will deactivate all pages linked to this account."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-500 hover:text-red-600 flex-shrink-0 ml-2 disabled:opacity-50"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            {{-- Connect button --}}
            <div class="{{ $facebookAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                @if(empty(config('services.meta.app_id')))
                    <flux:text size="xs" class="text-zinc-400">Requires META_APP_ID and META_APP_SECRET in .env</flux:text>
                @else
                    <flux:button as="a" href="{{ route('connections.facebook.redirect') }}" variant="primary" size="sm" class="w-full">
                        {{ $facebookAccounts->isNotEmpty() ? 'Add Another Facebook Account' : 'Connect with Facebook' }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Instagram --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">IG</div>
                <div>
                    <flux:heading size="sm">Instagram</flux:heading>
                    <flux:text size="xs">Direct Messages</flux:text>
                </div>
            </div>

            @php $instagramAccounts = $this->connectedAccounts->where('platform', 'instagram'); @endphp
            @foreach($instagramAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-2 min-w-0">
                        @if($account->avatar)
                            <img src="{{ $account->avatar }}" class="w-5 h-5 rounded-full flex-shrink-0" />
                        @endif
                        <flux:text size="xs" class="truncate">{{ $account->name }}</flux:text>
                        <flux:badge color="green" size="sm">Active</flux:badge>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect '{{ addslashes($account->name) }}'? Instagram DMs will stop coming in."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-500 hover:text-red-600 flex-shrink-0 ml-2 disabled:opacity-50"
                    >
                        Disconnect
                    </flux:button>
                </div>
                @foreach($this->pages->where('platform', 'instagram')->where('connected_account_id', $account->id) as $igPage)
                    <div class="pl-2 py-1 border-t border-zinc-50 dark:border-zinc-800">
                        <flux:text size="xs" class="text-zinc-500">
                            {{ $igPage->name }}{{ isset($igPage->metadata['username']) ? ' (@' . $igPage->metadata['username'] . ')' : '' }}
                        </flux:text>
                    </div>
                @endforeach
            @endforeach

            <div class="{{ $instagramAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                @if(empty(config('services.meta.app_id')))
                    <flux:text size="xs" class="text-zinc-400">Requires META_APP_ID and META_APP_SECRET in .env</flux:text>
                @else
                    <flux:button as="a" href="{{ route('connections.instagram.redirect') }}" variant="primary" size="sm" class="w-full" style="background: linear-gradient(135deg, #833AB4, #E1306C); border: none;">
                        {{ $instagramAccounts->isNotEmpty() ? 'Add Another Instagram Account' : 'Connect Instagram' }}
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- WhatsApp --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center text-white font-bold">WA</div>
                <div>
                    <flux:heading size="sm">WhatsApp</flux:heading>
                    <flux:text size="xs">Business API &amp; QR Connect</flux:text>
                </div>
            </div>

            {{-- Connected WhatsApp accounts --}}
            @php $whatsappAccounts = $this->connectedAccounts->where('platform', 'whatsapp'); @endphp
            @foreach($whatsappAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-2 min-w-0">
                        <flux:text size="xs" class="truncate">{{ $account->name }}</flux:text>
                        <flux:badge color="green" size="sm">Active</flux:badge>
                        @if(! empty($account->metadata['gateway_mode']))
                            <flux:badge color="blue" size="sm">QR</flux:badge>
                        @endif
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect '{{ addslashes($account->name) }}'? Messages from this number will stop coming in."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-500 hover:text-red-600 flex-shrink-0 ml-2 disabled:opacity-50"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            {{-- Two connect buttons: Meta API and QR --}}
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
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-cyan-500 flex items-center justify-center text-white font-bold">TG</div>
                <div>
                    <flux:heading size="sm">Telegram</flux:heading>
                    <flux:text size="xs">Bot API</flux:text>
                </div>
            </div>

            {{-- Connected Telegram bots --}}
            @php $telegramAccounts = $this->connectedAccounts->where('platform', 'telegram'); @endphp
            @foreach($telegramAccounts as $account)
                <div class="flex items-center justify-between py-2 border-t border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-2 min-w-0">
                        <flux:text size="xs" class="truncate">{{ $account->name }}</flux:text>
                        <flux:badge color="green" size="sm">Active</flux:badge>
                    </div>
                    <flux:button
                        wire:click="disconnect({{ $account->id }})"
                        wire:confirm="Disconnect bot '{{ addslashes($account->name) }}'? The webhook will be removed and the bot will stop receiving messages."
                        wire:loading.attr="disabled"
                        size="xs"
                        variant="ghost"
                        class="text-red-500 hover:text-red-600 flex-shrink-0 ml-2 disabled:opacity-50"
                    >
                        Disconnect
                    </flux:button>
                </div>
            @endforeach

            <div class="{{ $telegramAccounts->isNotEmpty() ? 'mt-3' : '' }}">
                <flux:modal.trigger name="telegram-connect">
                    <flux:button variant="primary" size="sm" class="w-full">
                        {{ $telegramAccounts->isNotEmpty() ? 'Add Another Telegram Bot' : 'Connect Telegram' }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        {{-- TikTok (Coming Soon) --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 opacity-75">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-black flex items-center justify-center" style="background: linear-gradient(135deg, #010101 60%, #69C9D0 100%);">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.17 8.17 0 0 0 4.78 1.52V6.76a4.85 4.85 0 0 1-1.01-.07z"/>
                    </svg>
                </div>
                <div>
                    <flux:heading size="sm">TikTok</flux:heading>
                    <flux:text size="xs">Business Messaging API</flux:text>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge color="yellow" size="sm" class="w-full justify-center">Coming Soon</flux:badge>
            </div>
            <flux:text size="xs" class="text-zinc-400 mt-2">TikTok DM integration requires partner API access. We're working on it.</flux:text>
        </div>

        {{-- Snapchat (Coming Soon) --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 opacity-75">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-400 flex items-center justify-center">
                    <svg class="w-6 h-6 text-black" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.166 2C9.34 2 7.5 3.607 7.5 6.5v.574c-.34.06-.69.14-1.04.24-.272.078-.553.005-.74-.196a.75.75 0 0 0-1.092 1.026c.38.406.924.597 1.47.538-.02.222-.03.448-.03.676 0 2.21.896 4.21 2.344 5.664-.17.13-.354.23-.55.296-.896.3-1.864.094-2.582-.55a.75.75 0 0 0-1.02 1.1c1.06.988 2.534 1.334 3.9.906.248-.083.49-.19.72-.318.46.436.97.822 1.52 1.148-.274.17-.572.3-.886.386-1.08.3-2.22.08-3.08-.58a.75.75 0 1 0-.9 1.2c1.21.908 2.78 1.22 4.27.828.418-.116.81-.293 1.17-.522.296.1.6.176.91.226v.358c0 .414.336.75.75.75s.75-.336.75-.75v-.358c.31-.05.614-.126.91-.226.36.23.752.406 1.17.522 1.49.392 3.06.08 4.27-.828a.75.75 0 1 0-.9-1.2c-.86.66-2 .88-3.08.58a3.26 3.26 0 0 1-.886-.386c.55-.326 1.06-.712 1.52-1.148.23.128.472.235.72.318 1.366.428 2.84.082 3.9-.906a.75.75 0 0 0-1.02-1.1c-.718.644-1.686.85-2.582.55a3.2 3.2 0 0 1-.55-.296A7.494 7.494 0 0 0 16.5 9.334c0-.228-.01-.454-.03-.676.546.059 1.09-.132 1.47-.538a.75.75 0 0 0-1.092-1.026c-.187.201-.468.274-.74.196-.35-.1-.7-.18-1.04-.24V6.5C15.068 3.607 13.228 2 12.166 2z"/>
                    </svg>
                </div>
                <div>
                    <flux:heading size="sm">Snapchat</flux:heading>
                    <flux:text size="xs">Business Messaging API</flux:text>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge color="yellow" size="sm" class="w-full justify-center">Coming Soon</flux:badge>
            </div>
            <flux:text size="xs" class="text-zinc-400 mt-2">Snapchat messaging requires allowlist approval from Snap. We're applying for access.</flux:text>
        </div>
    </div>

    {{-- Connected Pages --}}
    <flux:heading size="lg" class="mb-4">Connected Pages</flux:heading>

    @if($this->pages->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="link" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No pages connected yet. Connect a platform above to get started.</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->pages as $page)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full {{ match($page->platform) {
                            'facebook' => 'bg-blue-500',
                            'instagram' => 'bg-pink-500',
                            'whatsapp' => 'bg-green-500',
                            'telegram' => 'bg-cyan-500',
                            default => 'bg-gray-400',
                        } }} flex items-center justify-center text-white text-xs font-bold">
                            {{ strtoupper(substr($page->platform, 0, 2)) }}
                        </div>
                        <div>
                            <flux:heading size="sm">{{ $page->name }}</flux:heading>
                            <flux:text size="xs">
                                {{ ucfirst($page->platform) }}
                                {{ $page->category ? "({$page->category})" : '' }}
                                @if($page->metadata && isset($page->metadata['username']))
                                    &middot; {{ '@' . $page->metadata['username'] }}
                                @endif
                            </flux:text>
                        </div>
                    </div>
                    <flux:badge color="green" size="sm">Active</flux:badge>
                </div>
            @endforeach
        </div>
    @endif

    {{-- WhatsApp QR Modal (Evolution API / gateway mode) --}}
    <livewire:connections.whatsapp-qr-modal />

    {{-- WhatsApp Connect Modal --}}
    <flux:modal name="whatsapp-connect" class="md:w-[32rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Connect WhatsApp Business</flux:heading>
                <flux:text class="mt-1">Follow these steps to get your WhatsApp credentials.</flux:text>
            </div>

            {{-- Step-by-step guide --}}
            <div class="space-y-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 text-sm">
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-bold">1</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">Open <a href="https://business.facebook.com/settings/system-users" target="_blank" class="text-green-500 underline font-medium">Meta Business Settings &rarr; System Users</a></p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-bold">2</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">Click <strong>"Add"</strong> to create a new System User (name it anything, set role to <strong>Admin</strong>)</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-bold">3</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">Click <strong>"Generate New Token"</strong> on the user, select your app, and check these permissions:</p>
                        <p class="text-zinc-500 dark:text-zinc-400 text-xs mt-1"><code>whatsapp_business_management</code>, <code>whatsapp_business_messaging</code></p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 text-white text-xs flex items-center justify-center font-bold">4</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">Copy the token. Then go to <a href="https://business.facebook.com/settings/whatsapp-business-accounts" target="_blank" class="text-green-500 underline font-medium">WhatsApp Accounts</a> and copy your <strong>Account ID</strong> (the number under your account name)</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('connections.whatsapp.connect') }}" method="POST" class="space-y-4">
                @csrf
                <flux:input name="waba_id" label="WhatsApp Account ID" placeholder="Paste the Account ID number here" required />
                <flux:input name="access_token" label="System User Token" type="password" placeholder="Paste the generated token here" required />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" class="flex-1">Connect</flux:button>
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Telegram Connect Modal --}}
    <flux:modal name="telegram-connect" class="md:w-[32rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Connect Telegram Bot</flux:heading>
                <flux:text class="mt-1">Create a Telegram bot in 2 minutes and paste the token here.</flux:text>
            </div>

            {{-- Step-by-step guide --}}
            <div class="space-y-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4 text-sm">
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-500 text-white text-xs flex items-center justify-center font-bold">1</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">Open Telegram and search for <a href="https://t.me/BotFather" target="_blank" class="text-cyan-500 underline font-medium">@BotFather</a> (or click this link)</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-500 text-white text-xs flex items-center justify-center font-bold">2</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">Send the message: <code class="bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded">/newbot</code></p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-500 text-white text-xs flex items-center justify-center font-bold">3</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">BotFather will ask for a <strong>name</strong> (e.g. "My Sales Bot") and a <strong>username</strong> (must end in "bot", e.g. "mysales_bot")</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-cyan-500 text-white text-xs flex items-center justify-center font-bold">4</span>
                    <div>
                        <p class="text-zinc-700 dark:text-zinc-300">BotFather will reply with your <strong>bot token</strong> - it looks like: <code class="bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded text-xs">123456:ABCdefGHI...</code></p>
                        <p class="text-zinc-500 dark:text-zinc-400 text-xs mt-1">Copy that entire token and paste it below.</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('connections.telegram.connect') }}" method="POST" class="space-y-4">
                @csrf
                <flux:input name="bot_token" label="Bot Token" type="password" placeholder="Paste the token from BotFather here" required />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" class="flex-1">Connect</flux:button>
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
