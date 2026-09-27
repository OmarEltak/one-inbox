<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl" class="text-zinc-900">{{ __('Onboarding Requests') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-900">{{ __('Customers waiting on managed Facebook / Instagram setup. Connect their page through your own Meta account first, then complete the request below to hand it off.') }}</flux:text>
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
            <flux:select wire:model.live="statusFilter" class="text-zinc-900">
                <option value="open">{{ __('Open (pending + in progress)') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="in_progress">{{ __('In progress') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
                <option value="all">{{ __('All') }}</option>
            </flux:select>
        </div>
        <div class="ml-auto text-sm text-zinc-500">
            {{ $this->requests->count() }} request(s)
        </div>
    </div>

    @if($this->requests->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="inbox" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">{{ __('No requests in this view.') }}</flux:text>
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
                            <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $req->business_name ?: __('(no business name)') }}
                            </div>
                            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                                {{ __('Team') }}: <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $req->team?->name ?? '?' }}</span>
                                · {{ __('Requested by') }} {{ $req->requestedBy?->name ?? '?' }}
                            </div>
                        </div>

                        @if($req->status === 'pending')
                            <flux:button wire:click="startReview({{ $req->id }})" size="sm" variant="outline">
                                {{ __('Start review') }}
                            </flux:button>
                        @endif
                    </div>

                    {{-- Submitted details grid — everything the customer filled in the request form,
                         laid out for one-glance scanning. Phone links to wa.me, email to mailto:,
                         page URL opens in new tab. Renders every field even when empty so the
                         reviewer sees at a glance what was and wasn't provided. --}}
                    <div class="mb-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
                        <div class="text-[11px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400 font-semibold mb-3">{{ __('Submitted details') }}</div>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Contact email') }}</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100 font-medium break-all">
                                    @if($req->contact_email)
                                        <a href="mailto:{{ $req->contact_email }}" class="text-emerald-700 dark:text-emerald-400 hover:underline">{{ $req->contact_email }}</a>
                                    @elseif($req->requestedBy?->email)
                                        <a href="mailto:{{ $req->requestedBy->email }}" class="text-emerald-700 dark:text-emerald-400 hover:underline">{{ $req->requestedBy->email }}</a>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">({{ __('account email') }})</span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500 italic">{{ __('not provided') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('WhatsApp') }}</dt>
                                <dd class="mt-0.5 font-medium">
                                    @if($req->contact_phone)
                                        @php $waNumber = preg_replace('/\D+/', '', $req->contact_phone); @endphp
                                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="text-emerald-700 dark:text-emerald-400 hover:underline">{{ $req->contact_phone }}</a>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500 italic">{{ __('not provided') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Page URL') }}</dt>
                                <dd class="mt-0.5 font-medium break-all">
                                    @if($req->page_url)
                                        <a href="{{ $req->page_url }}" target="_blank" class="text-emerald-700 dark:text-emerald-400 hover:underline">{{ $req->page_url }}</a>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500 italic">{{ __('not provided') }}</span>
                                    @endif
                                </dd>
                            </div>
                            @if($req->notes)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</dt>
                                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100 whitespace-pre-wrap">{{ $req->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    @if($req->status === 'completed' && $req->resultingPage)
                        <div class="text-sm text-green-700 dark:text-green-400">
                            ✓ Assigned page: <span class="font-medium">{{ $req->resultingPage->name }}</span>
                            · completed {{ $req->completed_at?->diffForHumans() }}
                        </div>
                    @elseif($req->status === 'rejected')
                        <div class="text-sm text-red-700 dark:text-red-400 flex items-center gap-2 flex-wrap">
                            <span>✗ Rejected: {{ $req->admin_notes }}
                            · {{ $req->completed_at?->diffForHumans() }}</span>
                            @if($req->requestedBy?->email)
                                <a href="mailto:{{ $req->requestedBy->email }}?subject=About%20your%20OT1-Pro%20connection%20request" class="text-xs text-blue-600 hover:underline">Reply</a>
                            @endif
                        </div>
                    @elseif($req->isOpen())
                        <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 space-y-3">
                            <div>
                                <flux:text size="sm" class="mb-2 text-zinc-700 dark:text-zinc-300">{{ __('Assign a page from your holding workspace and complete this request:') }}</flux:text>
                                <div class="flex gap-2">
                                    <flux:select wire:model="selectedPageByRequest.{{ $req->id }}" class="flex-1">
                                        <option value="">{{ __('— pick a page —') }}</option>
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
