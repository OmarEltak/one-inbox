<div class="p-6 space-y-6" x-data="{ tab: 'all' }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Campaigns') }}</h1>
            <p class="mt-1 text-sm text-white/40">{{ __('Send template broadcasts to your WhatsApp contacts.') }}</p>
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

    @if($this->whatsappPages->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            No active WhatsApp connections found. <a href="{{ route('connections.index') }}" class="underline" wire:navigate>Connect a WhatsApp account</a> first.
        </flux:callout>
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
                $page = $this->whatsappPages->firstWhere('id', $criteria['page_id'] ?? null);
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
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-white/80">{{ $campaign->name }}</span>
                            <flux:badge size="sm" color="{{ $statusColor }}">{{ ucfirst($campaign->status) }}</flux:badge>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-white/40">
                            <span>
                                <flux:icon.phone class="inline size-3 mr-1" />
                                {{ $page?->name ?? 'Unknown page' }}
                            </span>
                            <span class="font-mono">Template: {{ $campaign->message_template }}</span>
                            @if(! empty($criteria['lead_status']))
                                <span>Filter: {{ $criteria['lead_status'] }}</span>
                            @endif
                            <span>By {{ $campaign->creator?->name ?? '—' }}</span>
                            <span>{{ $campaign->created_at->diffForHumans() }}</span>
                        </div>

                        @if($campaign->total_contacts > 0)
                            {{-- Progress bar --}}
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
                <p class="mt-1 text-sm text-white/40">Create a broadcast campaign to reach your WhatsApp contacts at scale.</p>
                <div class="mt-4">
                    <flux:button icon="plus" wire:click="openCreateModal">New Campaign</flux:button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Create Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">New WhatsApp Campaign</flux:heading>

            <flux:field>
                <flux:label>Campaign Name</flux:label>
                <flux:input wire:model="name" placeholder="e.g. January Promotion" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>WhatsApp Page</flux:label>
                <flux:select wire:model="pageId">
                    <option value="">Select a page…</option>
                    @foreach($this->whatsappPages as $page)
                        <option value="{{ $page->id }}">{{ $page->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="pageId" />
            </flux:field>

            <flux:field>
                <flux:label>Template Name <flux:badge size="sm" variant="outline">approved template slug</flux:badge></flux:label>
                <flux:input wire:model="messageTemplate" placeholder="e.g. hello_world" />
                <flux:description>Enter the exact template name as approved in WhatsApp Business Manager.</flux:description>
                <flux:error name="messageTemplate" />
            </flux:field>

            <flux:field>
                <flux:label>Language Code</flux:label>
                <flux:select wire:model="languageCode">
                    <option value="en">English (en)</option>
                    <option value="en_US">English US (en_US)</option>
                    <option value="ar">Arabic (ar)</option>
                    <option value="fr">French (fr)</option>
                    <option value="es">Spanish (es)</option>
                    <option value="pt_BR">Portuguese BR (pt_BR)</option>
                    <option value="de">German (de)</option>
                    <option value="it">Italian (it)</option>
                    <option value="tr">Turkish (tr)</option>
                    <option value="id">Indonesian (id)</option>
                </flux:select>
                <flux:error name="languageCode" />
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
                <flux:label>Delay Between Messages <flux:badge size="sm" color="yellow">Anti-ban</flux:badge></flux:label>
                <flux:select wire:model="delaySeconds">
                    <option value="5">5 seconds (fast, higher risk)</option>
                    <option value="10" selected>10 seconds (recommended)</option>
                    <option value="20">20 seconds (safe)</option>
                    <option value="30">30 seconds (very safe)</option>
                    <option value="60">60 seconds (ultra safe)</option>
                </flux:select>
                <flux:description>Adding a delay between messages reduces the risk of WhatsApp banning your number.</flux:description>
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Create Campaign</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
