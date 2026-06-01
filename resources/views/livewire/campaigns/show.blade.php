<div class="p-6 max-w-6xl mx-auto space-y-6" wire:poll.10s>
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('campaigns.index') }}" wire:navigate class="text-xs text-white/40 hover:text-white">← Campaigns</a>
            <h1 class="text-2xl font-bold text-white mt-1">{{ $campaign->name }}</h1>
            <p class="text-sm text-white/40">
                {{ ucfirst($campaign->platform ?? 'email') }} ·
                Sender: {{ optional($campaign->senderPage)->name ?? '—' }} ·
                Status:
                <span class="font-semibold
                    @if($campaign->status === 'active') text-yellow-400
                    @elseif($campaign->status === 'completed') text-green-400
                    @elseif($campaign->status === 'paused') text-orange-400
                    @elseif($campaign->status === 'failed') text-red-400
                    @else text-white/60 @endif">{{ ucfirst($campaign->status) }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            @if($campaign->status === 'active')
                <button wire:click="pause" class="px-4 py-2 rounded-xl text-sm text-orange-300 bg-orange-500/15">Pause</button>
            @elseif($campaign->status === 'paused')
                <button wire:click="resume" class="px-4 py-2 rounded-xl text-sm text-green-300 bg-green-500/15">Resume</button>
            @endif
            @if($this->counts['failed'] > 0)
                <button wire:click="retryFailed" class="px-4 py-2 rounded-xl text-sm text-white/80 bg-white/[0.06]">Retry failed</button>
            @endif
        </div>
    </div>

    {{-- Stat tiles --}}
    @php $c = $this->counts; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
        @foreach([
            ['Total', $c['total'], 'text-white'],
            ['Pending', $c['pending'], 'text-white/60'],
            ['Sent', $c['sent'], 'text-green-400'],
            ['Opened', $c['opened'], 'text-[#C27AFF]'],
            ['Failed', $c['failed'], 'text-red-400'],
            ['Unsub.', $c['unsubscribed'], 'text-orange-400'],
        ] as [$label, $value, $color])
            <div class="aio-card rounded-2xl p-4 text-center">
                <p class="text-xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                <p class="text-xs text-white/40 mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- Progress bar --}}
    @if($c['total'] > 0)
        @php $pct = (int) round((($c['sent'] + $c['failed'] + $c['unsubscribed']) / $c['total']) * 100); @endphp
        <div>
            <div class="flex justify-between text-xs text-white/50 mb-1">
                <span>Progress</span>
                <span>{{ $pct }}%</span>
            </div>
            <div class="h-2 rounded-full bg-white/[0.05] overflow-hidden">
                <div class="h-full bg-gradient-to-r from-[#7C3AED] to-[#C27AFF]" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    @endif

    {{-- Filter tabs --}}
    <div class="flex gap-0 border-b" style="border-color: rgba(255,255,255,0.07);">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'sent' => 'Sent', 'opened' => 'Opened', 'failed' => 'Failed', 'unsubscribed' => 'Unsubscribed'] as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')"
                    class="px-4 py-2.5 text-sm font-medium transition-colors cursor-pointer -mb-px
                           {{ $filter === $key ? 'border-b-2 text-[#C27AFF]' : 'text-white/35 hover:text-white/60' }}"
                    @if($filter === $key) style="border-color: #7C3AED;" @endif>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Recipients table --}}
    <div class="aio-card rounded-2xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-white/[0.03]">
                <tr class="text-left text-xs text-white/50">
                    <th class="px-4 py-2.5">Email</th>
                    <th class="px-4 py-2.5">Name</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Attempts</th>
                    <th class="px-4 py-2.5">Scheduled</th>
                    <th class="px-4 py-2.5">Sent</th>
                    <th class="px-4 py-2.5">Error</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recipients as $r)
                    <tr class="border-t border-white/[0.04]">
                        <td class="px-4 py-2 text-white/80">{{ $r->email }}</td>
                        <td class="px-4 py-2 text-white/60">{{ $r->name ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-md text-xs font-semibold
                                @switch($r->status)
                                    @case('sent') bg-green-500/15 text-green-300 @break
                                    @case('opened') bg-purple-500/15 text-purple-300 @break
                                    @case('pending') bg-white/[0.06] text-white/60 @break
                                    @case('sending') bg-yellow-500/15 text-yellow-300 @break
                                    @case('failed') bg-red-500/15 text-red-300 @break
                                    @case('unsubscribed') bg-orange-500/15 text-orange-300 @break
                                    @default bg-white/[0.06] text-white/60
                                @endswitch">
                                {{ $r->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-white/50">{{ $r->attempts }}</td>
                        <td class="px-4 py-2 text-white/50">{{ optional($r->scheduled_at)->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-2 text-white/50">{{ optional($r->sent_at)->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-2 text-red-300/80 text-xs">{{ \Illuminate\Support\Str::limit($r->last_error ?? '', 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-white/40">No recipients in this view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $recipients->links() }}</div>
</div>
