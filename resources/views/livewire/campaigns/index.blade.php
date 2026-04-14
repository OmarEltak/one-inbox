<div class="p-6 space-y-6" x-data="{ tab: 'all' }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Campaigns') }}</h1>
            <p class="mt-1 text-sm text-white/40">{{ __('Send broadcast messages to your contacts across all platforms.') }}</p>
        </div>
        <button wire:click="openCreateModal"
                class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all aio-btn-primary">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('New Campaign') }}
        </button>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    {{-- Stat Chips --}}
    @php
        $allCampaigns = $this->campaigns;
        $statChips = [
            ['label' => 'Total', 'value' => $allCampaigns->count(), 'color' => 'text-white/80'],
            ['label' => 'Active', 'value' => $allCampaigns->where('status', 'active')->count(), 'color' => 'text-yellow-400'],
            ['label' => 'Scheduled', 'value' => $allCampaigns->where('status', 'scheduled')->count(), 'color' => 'text-blue-400'],
            ['label' => 'Total Sent', 'value' => number_format($allCampaigns->sum('sent_count')), 'color' => 'text-green-400'],
            ['label' => 'Completed', 'value' => $allCampaigns->where('status', 'completed')->count(), 'color' => 'text-[#8b5cf6]'],
            ['label' => 'Draft', 'value' => $allCampaigns->where('status', 'draft')->count(), 'color' => 'text-white/40'],
        ];
    @endphp
    <div class="grid grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($statChips as $chip)
            <div class="aio-card rounded-2xl p-4 text-center">
                <p class="text-xl font-bold {{ $chip['color'] }}">{{ $chip['value'] }}</p>
                <p class="text-xs text-white/35 mt-1">{{ $chip['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tab Filter Bar --}}
    <div class="flex gap-0 border-b pb-0" style="border-color: rgba(255,255,255,0.07);">
        @foreach(['all' => 'All', 'active' => 'Active', 'scheduled' => 'Scheduled', 'draft' => 'Draft', 'completed' => 'Completed', 'paused' => 'Paused'] as $key => $label)
            <button
                @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'border-b-2 text-[#C27AFF]'
                    : 'text-white/35 hover:text-white/60'"
                :style="tab === '{{ $key }}' ? 'border-color: #7C3AED;' : ''"
                class="px-4 py-2.5 text-sm font-medium transition-colors cursor-pointer -mb-px"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- Campaign List --}}
    <div class="space-y-3">
        @forelse($this->campaigns as $campaign)
            @php
                $criteria = $campaign->target_criteria ?? [];
                $campaignPlatform = $campaign->platform ?? 'whatsapp';
                $platformLabel    = ucfirst($campaignPlatform);
                $platformColors   = [
                    'facebook'  => 'text-blue-400',
                    'instagram' => 'text-pink-400',
                    'telegram'  => 'text-sky-400',
                    'whatsapp'  => 'text-green-400',
                ];
                $platformColor = $platformColors[$campaignPlatform] ?? 'text-white/50';
                $statusColor = match($campaign->status) {
                    'active'    => 'yellow',
                    'completed' => 'green',
                    'paused'    => 'orange',
                    default     => 'zinc',
                };
            @endphp
            <div
                x-show="tab === 'all' || tab === '{{ $campaign->status }}'"
                class="aio-card rounded-2xl p-4"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-white/80">{{ $campaign->name }}</span>
                            <flux:badge size="sm" color="{{ $statusColor }}">{{ ucfirst($campaign->status) }}</flux:badge>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/5 border border-white/10 {{ $platformColor }}">
                                {{ $platformLabel }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-white/40">
                            @if($campaignPlatform === 'whatsapp')
                                <span class="font-mono">Template: {{ $campaign->message_template }}</span>
                            @else
                                <span class="truncate max-w-xs">{{ Str::limit($campaign->message_template, 60) }}</span>
                            @endif
                            @if(! empty($criteria['lead_status']))
                                <span>Filter: {{ $criteria['lead_status'] }}</span>
                            @endif
                            <span>By {{ $campaign->creator?->name ?? '—' }}</span>
                            <span>{{ $campaign->created_at->diffForHumans() }}</span>
                        </div>

                        @if($campaign->total_contacts > 0)
                            @php
                                $progress = $campaign->total_contacts > 0
                                    ? min(100, round(($campaign->sent_count / $campaign->total_contacts) * 100))
                                    : 0;
                                $replyRate = $campaign->sent_count > 0
                                    ? round(($campaign->reply_count / $campaign->sent_count) * 100, 1)
                                    : 0;
                            @endphp
                            <div class="pt-2 space-y-2">
                                <div class="flex items-center justify-between text-xs text-white/40">
                                    <span>{{ number_format($campaign->sent_count) }} / {{ number_format($campaign->total_contacts) }} sent</span>
                                    <span>{{ $progress }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.07);">
                                    <div class="h-full rounded-full transition-all duration-300"
                                         style="width: {{ $progress }}%; background: {{ $campaign->status === 'active' ? 'linear-gradient(90deg, #7C3AED, #06B6D4)' : ($campaign->status === 'completed' ? '#00D492' : '#6B7280') }};">
                                    </div>
                                </div>
                                <div class="flex gap-4 text-xs text-white/40">
                                    <span><span class="text-white/60 font-medium">{{ number_format($campaign->reply_count) }}</span> replies</span>
                                    @if($replyRate > 0)
                                        <span><span class="text-green-400 font-medium">{{ $replyRate }}%</span> reply rate</span>
                                    @endif
                                    @if(!empty($criteria['delay_seconds']))
                                        <span>{{ $criteria['delay_seconds'] }}s delay</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($campaign->status === 'active')
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="pause"
                                wire:click="pause({{ $campaign->id }})"
                            >Pause</flux:button>
                        @endif
                        @if(in_array($campaign->status, ['draft', 'paused']))
                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="paper-airplane"
                                wire:click="launch({{ $campaign->id }})"
                                wire:confirm="Launch campaign '{{ $campaign->name }}'? This will send to all matching contacts."
                            >
                                {{ $campaign->status === 'paused' ? 'Resume' : 'Launch' }}
                            </flux:button>
                        @endif
                        @if($campaign->status !== 'active')
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="text-red-500"
                                wire:click="delete({{ $campaign->id }})"
                                wire:confirm="Delete '{{ $campaign->name }}'?"
                            >Delete</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl p-10 text-center" style="border: 1px dashed rgba(124,58,237,0.2); background: rgba(124,58,237,0.03);">
                <flux:icon.paper-airplane class="mx-auto size-10 text-white/40" />
                <p class="mt-3 font-semibold text-white/80">No campaigns yet</p>
                <p class="mt-1 text-sm text-white/40">Create a broadcast campaign to reach your contacts across Facebook, Instagram, or Telegram.</p>
                <div class="mt-4">
                    <flux:button icon="plus" wire:click="openCreateModal">New Campaign</flux:button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Create Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <div class="space-y-5">
            <flux:heading size="lg">New Campaign</flux:heading>

            {{-- Platform Selector --}}
            <div>
                <flux:label class="mb-2 block">Platform</flux:label>
                <div class="grid grid-cols-2 gap-2">
                    {{-- Facebook --}}
                    <button
                        type="button"
                        wire:click="$set('platform', 'facebook')"
                        @class([
                            'flex items-center gap-3 p-3 rounded-xl border text-left transition-all',
                            'border-[#7C3AED] bg-[#7C3AED]/10 text-white' => $platform === 'facebook',
                            'border-white/10 bg-white/3 text-white/50 hover:border-white/20 hover:text-white/70' => $platform !== 'facebook',
                        ])
                    >
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span class="text-sm font-medium">Facebook</span>
                    </button>

                    {{-- Instagram --}}
                    <button
                        type="button"
                        wire:click="$set('platform', 'instagram')"
                        @class([
                            'flex items-center gap-3 p-3 rounded-xl border text-left transition-all',
                            'border-[#7C3AED] bg-[#7C3AED]/10 text-white' => $platform === 'instagram',
                            'border-white/10 bg-white/3 text-white/50 hover:border-white/20 hover:text-white/70' => $platform !== 'instagram',
                        ])
                    >
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                        <span class="text-sm font-medium">Instagram</span>
                    </button>

                    {{-- Telegram --}}
                    <button
                        type="button"
                        wire:click="$set('platform', 'telegram')"
                        @class([
                            'flex items-center gap-3 p-3 rounded-xl border text-left transition-all',
                            'border-[#7C3AED] bg-[#7C3AED]/10 text-white' => $platform === 'telegram',
                            'border-white/10 bg-white/3 text-white/50 hover:border-white/20 hover:text-white/70' => $platform !== 'telegram',
                        ])
                    >
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.96 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                        <span class="text-sm font-medium">Telegram</span>
                    </button>

                    {{-- WhatsApp — Coming Soon --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-white/5 bg-white/2 opacity-50 cursor-not-allowed">
                        <svg class="size-5 shrink-0 text-white/30" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <div>
                            <span class="text-sm font-medium text-white/30">WhatsApp</span>
                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-400/10 text-yellow-400/70 border border-yellow-400/20">Coming Soon</span>
                        </div>
                    </div>
                </div>
            </div>

            <flux:field>
                <flux:label>Campaign Name</flux:label>
                <flux:input wire:model="name" placeholder="e.g. January Promotion" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Page</flux:label>
                <flux:select wire:model="pageId">
                    <option value="">Select a page…</option>
                    @foreach($this->pagesForPlatform as $page)
                        <option value="{{ $page->id }}">{{ $page->name }}</option>
                    @endforeach
                </flux:select>
                @if($this->pagesForPlatform->isEmpty())
                    <flux:description class="text-yellow-400/70">
                        No active {{ ucfirst($platform) }} pages found.
                        <a href="{{ route('connections.index') }}" class="underline" wire:navigate>Connect one first.</a>
                    </flux:description>
                @endif
                <flux:error name="pageId" />
            </flux:field>

            <flux:field>
                <flux:label>Message</flux:label>
                <flux:textarea wire:model="messageTemplate" rows="3" placeholder="Write your broadcast message here…" />
                <flux:error name="messageTemplate" />
            </flux:field>

            <flux:field>
                <flux:label>Target — Lead Status <flux:badge size="sm" variant="outline">optional</flux:badge></flux:label>
                <flux:select wire:model="leadStatus">
                    <option value="">All contacts on this page</option>
                    <option value="new">New</option>
                    <option value="warm">Warm</option>
                    <option value="hot">Hot</option>
                    <option value="cold">Cold</option>
                    <option value="converted">Converted</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Delay Between Messages <flux:badge size="sm" color="yellow">Rate-limit safe</flux:badge></flux:label>
                <flux:select wire:model="delaySeconds">
                    <option value="1">1 second (fast)</option>
                    <option value="3" selected>3 seconds (recommended)</option>
                    <option value="5">5 seconds</option>
                    <option value="10">10 seconds (safe)</option>
                    <option value="30">30 seconds (very safe)</option>
                </flux:select>
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Create Campaign</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
