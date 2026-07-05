<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-zinc-900">Wire Transfer Requests</h1>
            <p class="text-sm text-zinc-500">Review and approve manual payment submissions.</p>
        </div>
        <div class="flex gap-2">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $val => $label)
                <button
                    wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 text-sm rounded-lg font-medium transition-colors {{ $statusFilter === $val ? 'bg-violet-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200' }}"
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if($this->requests->isEmpty())
        <div class="text-center py-16 text-zinc-400">No {{ $statusFilter === 'all' ? '' : $statusFilter }} requests.</div>
    @else
        <div class="space-y-4">
            @foreach($this->requests as $req)
                <div class="bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 flex flex-wrap items-start gap-4">

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-zinc-900">{{ $req->full_name }}</span>
                                <span class="text-zinc-400">·</span>
                                <span class="text-sm text-zinc-500">{{ $req->email }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' :
                                      ($req->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-zinc-600">
                                <span><strong>Plan:</strong> {{ $req->planLabel() }}</span>
                                <span><strong>Bank:</strong> {{ $req->bank_name }}, {{ $req->bank_country }}</span>
                                @if($req->txid)
                                    <span><strong>TXID:</strong> <code class="font-mono text-xs">{{ $req->txid }}</code></span>
                                @endif
                                <span class="text-zinc-400 text-xs">{{ $req->created_at->diffForHumans() }}</span>
                            </div>
                            @if($req->team)
                                <div class="mt-1 text-xs text-zinc-400">Team: {{ $req->team->name }}</div>
                            @endif
                            @if($req->notes)
                                <div class="mt-2 text-xs text-zinc-500 bg-zinc-50 rounded px-3 py-2">{{ $req->notes }}</div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            {{-- View receipt --}}
                            <a
                                href="{{ route('super-admin.payment-requests.receipt', $req->id) }}"
                                target="_blank"
                                class="px-3 py-1.5 text-xs font-medium text-zinc-600 bg-zinc-100 hover:bg-zinc-200 rounded-lg transition-colors"
                            >View receipt</a>

                            <button
                                wire:click="openNotes({{ $req->id }})"
                                class="px-3 py-1.5 text-xs font-medium text-zinc-600 bg-zinc-100 hover:bg-zinc-200 rounded-lg transition-colors"
                            >Notes</button>

                            @if($req->status !== 'approved')
                                <button
                                    wire:click="approve({{ $req->id }})"
                                    wire:confirm="Approve and set {{ $req->plan }} plan for this customer?"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors"
                                >Approve</button>
                            @endif

                            @if($req->status !== 'rejected')
                                <button
                                    wire:click="reject({{ $req->id }})"
                                    wire:confirm="Reject this payment request?"
                                    class="px-3 py-1.5 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors"
                                >Reject</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Notes Modal --}}
    @if($notesId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h3 class="font-semibold text-zinc-900">Internal notes</h3>
                <textarea
                    wire:model="notesText"
                    rows="4"
                    class="w-full border border-zinc-300 rounded-lg px-3 py-2 text-sm text-zinc-800 focus:outline-none focus:border-violet-400"
                    placeholder="Add any notes about this payment..."
                ></textarea>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('notesId', null)" class="px-4 py-2 text-sm text-zinc-600 bg-zinc-100 hover:bg-zinc-200 rounded-lg">Cancel</button>
                    <button wire:click="saveNotes" class="px-4 py-2 text-sm text-white bg-violet-600 hover:bg-violet-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
