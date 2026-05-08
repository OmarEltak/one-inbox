<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white/90">Publish a post</h1>
        <p class="text-sm text-white/50 mt-1">Compose once, send to every connected channel.</p>
    </div>

    @if($statusMessage)
        <div class="mb-4 rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-3">
            <p class="text-sm text-emerald-200">{{ $statusMessage }}</p>
        </div>
    @endif

    <form wire:submit="publish" class="space-y-5">
        {{-- Content --}}
        <div class="aio-card rounded-2xl p-5">
            <label class="block text-xs font-medium text-white/40 mb-1.5">Message</label>
            <textarea
                wire:model.defer="content"
                rows="5"
                placeholder="What's on your mind?"
                class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none"
            ></textarea>
            <div class="flex justify-between items-center mt-1">
                @error('content')<span class="text-xs text-red-400">{{ $message }}</span>@enderror
                <span class="text-[10px] text-white/30 ml-auto">{{ strlen($content) }}/5000</span>
            </div>
        </div>

        {{-- Image upload --}}
        <div class="aio-card rounded-2xl p-5">
            <label class="block text-xs font-medium text-white/40 mb-1.5">Image (optional, required for Instagram)</label>
            <input type="file" wire:model="image" accept="image/*" class="text-xs text-white/70" />
            <div wire:loading wire:target="image" class="text-xs text-white/40 mt-1">Uploading…</div>
            @error('image')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
            @if($image)
                <div class="mt-3">
                    <img src="{{ $image->temporaryUrl() }}" class="rounded-lg max-h-48" />
                </div>
            @endif
        </div>

        {{-- Platform pickers --}}
        <div class="aio-card rounded-2xl p-5">
            <label class="block text-xs font-medium text-white/40 mb-3">Publish to</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach(['facebook' => ['Facebook Page', 'bg-blue-600', 'FB'], 'instagram' => ['Instagram (image required)', 'bg-pink-500', 'IG'], 'telegram' => ['Telegram', 'bg-cyan-500', 'TG'], 'slack' => ['Slack', 'bg-[#4A154B]', 'SL'], 'discord' => ['Discord', 'bg-[#5865F2]', 'DC']] as $platform => $meta)
                    @php
                        [$label, $color, $abbr] = $meta;
                        $isConnected = $this->pages->has($platform);
                    @endphp
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-white/[0.07] {{ $isConnected ? 'bg-white/[0.03] hover:bg-white/[0.06] cursor-pointer' : 'opacity-40 cursor-not-allowed' }} transition">
                        <input
                            type="checkbox"
                            wire:model.live="selectedPlatforms.{{ $platform }}"
                            @disabled(!$isConnected)
                            class="mt-0.5"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg {{ $color }} flex items-center justify-center text-white font-bold text-[10px]">{{ $abbr }}</span>
                                <span class="text-sm text-white/80">{{ $label }}</span>
                            </div>
                            @unless($isConnected)
                                <p class="text-[10px] text-white/40 mt-1">Connect this platform on the Connections page to enable.</p>
                            @endunless
                        </div>
                    </label>
                @endforeach
            </div>
            @error('selectedPlatforms')<p class="text-xs text-red-400 mt-2">{{ $message }}</p>@enderror
        </div>

        {{-- Per-platform channel inputs (shown only when selected) --}}
        @if($selectedPlatforms['telegram'] ?? false)
            <div class="aio-card rounded-2xl p-5">
                <label class="block text-xs font-medium text-white/40 mb-1.5">Telegram chat / channel id</label>
                <input
                    type="text"
                    wire:model.defer="telegramChatId"
                    placeholder="-1001234567890 (channel) or 123456789 (chat)"
                    class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono"
                />
                <p class="mt-1 text-[10px] text-white/40">Add your bot as an admin to the channel, then copy the chat id.</p>
            </div>
        @endif
        @if($selectedPlatforms['slack'] ?? false)
            <div class="aio-card rounded-2xl p-5">
                <label class="block text-xs font-medium text-white/40 mb-1.5">Slack channel id</label>
                <input
                    type="text"
                    wire:model.defer="slackChannelId"
                    placeholder="C0123ABCDEF"
                    class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono"
                />
                <p class="mt-1 text-[10px] text-white/40">In Slack: right-click the channel → View channel details → bottom of the page.</p>
            </div>
        @endif
        @if($selectedPlatforms['discord'] ?? false)
            <div class="aio-card rounded-2xl p-5">
                <label class="block text-xs font-medium text-white/40 mb-1.5">Discord channel id</label>
                <input
                    type="text"
                    wire:model.defer="discordChannelId"
                    placeholder="123456789012345678"
                    class="w-full rounded-lg border border-white/[0.07] bg-[#0d1117] px-3 py-2 text-sm text-white/80 placeholder-[#64748b] focus:border-[#3b82f6] focus:outline-none font-mono"
                />
                <p class="mt-1 text-[10px] text-white/40">Enable Developer Mode in Discord → right-click channel → Copy Channel ID.</p>
            </div>
        @endif

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="publish,image">
                <span wire:loading.remove wire:target="publish">Publish now</span>
                <span wire:loading wire:target="publish">Publishing…</span>
            </flux:button>
        </div>
    </form>

    {{-- Recent posts --}}
    @if($this->recentPosts->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-lg font-semibold text-white/80 mb-3">Recent posts</h2>
            <div class="space-y-3">
                @foreach($this->recentPosts as $post)
                    <div class="aio-card rounded-2xl p-4">
                        <div class="flex justify-between items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-white/80 line-clamp-2">{{ $post->content ?: '[Image only]' }}</p>
                                <p class="text-[10px] text-white/40 mt-1">{{ $post->created_at?->diffForHumans() }}</p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                                @class([
                                    'bg-emerald-500/20 text-emerald-300' => $post->status === 'completed',
                                    'bg-yellow-500/20 text-yellow-300' => in_array($post->status, ['queued','publishing','partial']),
                                    'bg-red-500/20 text-red-300' => $post->status === 'failed',
                                ])">
                                {{ $post->status }}
                            </span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach($post->targets as $t)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px]
                                    @class([
                                        'bg-emerald-500/15 text-emerald-300 border border-emerald-400/20' => $t->status === 'succeeded',
                                        'bg-red-500/15 text-red-300 border border-red-400/20' => $t->status === 'failed',
                                        'bg-yellow-500/15 text-yellow-300 border border-yellow-400/20' => in_array($t->status, ['pending','publishing']),
                                    ])"
                                    @if($t->error_message) title="{{ $t->error_message }}" @endif
                                >
                                    {{ strtoupper(substr($t->platform, 0, 2)) }}
                                    @if($t->status === 'succeeded')✓@elseif($t->status === 'failed')✗@else…@endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
