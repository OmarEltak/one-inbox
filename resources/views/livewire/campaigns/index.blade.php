<div class="max-w-5xl mx-auto p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">WhatsApp Campaigns</flux:heading>
            <flux:text class="mt-1">Send template broadcasts to your WhatsApp contacts.</flux:text>
        </div>
        <flux:button icon="plus" wire:click="openCreateModal">New Campaign</flux:button>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    @if($this->whatsappPages->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            No active WhatsApp connections found. <a href="{{ route('connections.index') }}" class="underline" wire:navigate>Connect a WhatsApp account</a> first.
        </flux:callout>
    @endif

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
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $campaign->name }}</span>
                            <flux:badge size="sm" color="{{ $statusColor }}">{{ ucfirst($campaign->status) }}</flux:badge>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                            <span>
                                <flux:icon.phone class="inline size-3 mr-1" />
                                {{ $page?->name ?? 'Unknown page' }}
                            </span>
                            <span class="font-mono">Template: {{ $campaign->message_template }}</span>
                            @if(! empty($criteria['lead_status']))
                                <span>Filter: {{ $criteria['lead_status'] }}</span>
                            @endif
                            <span>Created by {{ $campaign->creator?->name ?? '—' }}</span>
                            <span>{{ $campaign->created_at->diffForHumans() }}</span>
                        </div>

                        @if($campaign->status === 'completed' || $campaign->sent_count > 0)
                            <div class="flex gap-6 pt-1 text-sm">
                                <span class="text-zinc-600 dark:text-zinc-300">
                                    <strong>{{ number_format($campaign->total_contacts) }}</strong> contacts
                                </span>
                                <span class="text-zinc-600 dark:text-zinc-300">
                                    <strong>{{ number_format($campaign->sent_count) }}</strong> sent
                                </span>
                                <span class="text-zinc-600 dark:text-zinc-300">
                                    <strong>{{ number_format($campaign->reply_count) }}</strong> replies
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if(in_array($campaign->status, ['draft', 'paused']))
                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="paper-airplane"
                                wire:click="launch({{ $campaign->id }})"
                                wire:confirm="Launch campaign '{{ $campaign->name }}'? This will send to all matching contacts."
                            >
                                Launch
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
            <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                <flux:icon.paper-airplane class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                <flux:heading class="mt-3">No campaigns yet</flux:heading>
                <flux:text class="mt-1">Create a broadcast campaign to reach your WhatsApp contacts at scale.</flux:text>
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

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Create Campaign</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
