<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Onboarding Requests</flux:heading>
        <flux:text class="mt-1">Customers waiting on managed Facebook / Instagram setup. Connect their page through your own Meta account first, then complete the request below to hand it off.</flux:text>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <flux:text class="text-green-700 dark:text-green-400">{{ session('success') }}</flux:text>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <flux:text class="text-red-700 dark:text-red-400">{{ session('error') }}</flux:text>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-48">
            <flux:select wire:model.live="statusFilter">
                <option value="open">Open (pending + in progress)</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In progress</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
                <option value="all">All</option>
            </flux:select>
        </div>
        <div class="ml-auto text-sm text-zinc-500">
            {{ $this->requests->count() }} request(s)
        </div>
    </div>

    @if($this->requests->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="inbox" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No requests in this view.</flux:text>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->requests as $req)
                @php
                    $statusColor = match($req->status) {
                        'pending'     => 'amber',
                        'in_progress' => 'blue',
                        'completed'   => 'green',
                        'rejected'    => 'red',
                        default       => 'zinc',
                    };
                @endphp
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 bg-white dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center rounded-md bg-{{ $statusColor }}-100 dark:bg-{{ $statusColor }}-900/30 px-2 py-0.5 text-xs font-medium text-{{ $statusColor }}-800 dark:text-{{ $statusColor }}-300 capitalize">
                                    {{ str_replace('_', ' ', $req->status) }}
                                </span>
                                <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 capitalize">
                                    {{ $req->platform }}
                                </span>
                                <span class="text-xs text-zinc-500">#{{ $req->id }} · {{ $req->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-base font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $req->business_name ?: '(no business name)' }}
                            </div>
                            <div class="text-sm text-zinc-500 mt-0.5">
                                Team: <span class="text-zinc-700 dark:text-zinc-300">{{ $req->team?->name ?? '?' }}</span>
                                · Requested by {{ $req->requestedBy?->name ?? '?' }} ({{ $req->requestedBy?->email ?? '?' }})
                                @if($req->contact_phone)
                                    · {{ $req->contact_phone }}
                                @endif
                            </div>
                            @if($req->page_url)
                                <a href="{{ $req->page_url }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block break-all">
                                    {{ $req->page_url }}
                                </a>
                            @endif
                        </div>

                        @if($req->status === 'pending')
                            <flux:button wire:click="startReview({{ $req->id }})" size="sm" variant="outline">
                                Start review
                            </flux:button>
                        @endif
                    </div>

                    @if($req->notes)
                        <div class="mb-3 rounded-md bg-zinc-50 dark:bg-zinc-800/50 p-3 text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="text-xs uppercase tracking-wide text-zinc-500 block mb-1">Customer notes</span>
                            {{ $req->notes }}
                        </div>
                    @endif

                    @if($req->status === 'completed' && $req->resultingPage)
                        <div class="text-sm text-green-700 dark:text-green-400">
                            ✓ Assigned page: <span class="font-medium">{{ $req->resultingPage->name }}</span>
                            · completed {{ $req->completed_at?->diffForHumans() }}
                        </div>
                    @elseif($req->status === 'rejected')
                        <div class="text-sm text-red-700 dark:text-red-400">
                            ✗ Rejected: {{ $req->admin_notes }}
                            · {{ $req->completed_at?->diffForHumans() }}
                        </div>
                    @elseif($req->isOpen())
                        <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 space-y-3">
                            <div>
                                <flux:text size="sm" class="mb-2 text-zinc-700 dark:text-zinc-300">Assign a page from your holding workspace and complete this request:</flux:text>
                                <div class="flex gap-2">
                                    <flux:select wire:model="selectedPageByRequest.{{ $req->id }}" class="flex-1">
                                        <option value="">— pick a page —</option>
                                        @foreach($this->assignablePages->where('platform', $req->platform) as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->platform }})</option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button wire:click="complete({{ $req->id }})" variant="primary" size="sm">
                                        Complete
                                    </flux:button>
                                </div>
                                @if($this->assignablePages->where('platform', $req->platform)->isEmpty())
                                    <flux:text size="sm" class="mt-2 text-amber-700 dark:text-amber-400">
                                        No {{ $req->platform }} pages currently sit in your holding workspace. Connect the customer's page via your Meta account in Connections first.
                                    </flux:text>
                                @endif
                            </div>
                            <details class="text-sm">
                                <summary class="cursor-pointer text-zinc-500 hover:text-zinc-700">Reject instead</summary>
                                <div class="mt-2 flex gap-2">
                                    <flux:input wire:model="rejectionReasonByRequest.{{ $req->id }}" placeholder="Reason shown to customer..." class="flex-1" />
                                    <flux:button wire:click="reject({{ $req->id }})" variant="danger" size="sm">
                                        Reject
                                    </flux:button>
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
