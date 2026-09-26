<div class="p-6 max-w-6xl mx-auto space-y-6" wire:poll.10s>
    @php
        $isWhatsapp = ($campaign->platform ?? 'email') === 'whatsapp';
        $identifierColumn = $isWhatsapp ? __('Phone') : __('Email');
        $statusPill = match($campaign->status) {
            'active'    => 'text-yellow-700 bg-yellow-50 border-yellow-200',
            'completed' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
            'paused'    => 'text-orange-700 bg-orange-50 border-orange-200',
            'failed'    => 'text-red-700 bg-red-50 border-red-200',
            'scheduled' => 'text-blue-700 bg-blue-50 border-blue-200',
            default     => 'text-zinc-700 bg-zinc-100 border-zinc-200',
        };
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="min-w-0">
            <a href="{{ route('campaigns.index') }}" wire:navigate
               class="inline-flex items-center gap-1 text-xs font-medium text-zinc-700 hover:text-zinc-900">
                ← {{ __('Campaigns') }}
            </a>
            <h1 class="text-2xl font-bold text-zinc-900 mt-1 truncate">{{ $campaign->name }}</h1>
            <p class="mt-1 text-sm text-zinc-700 flex flex-wrap items-center gap-x-3 gap-y-1">
                <span>{{ ucfirst($campaign->platform ?? 'email') }}</span>
                <span class="text-zinc-400">·</span>
                <span>{{ __('Sender') }}: <strong class="text-zinc-900">{{ optional($campaign->senderPage)->name ?? '—' }}</strong></span>
                <span class="text-zinc-400">·</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border {{ $statusPill }}">
                    {{ ucfirst($campaign->status) }}
                </span>
            </p>
        </div>
        <div class="flex gap-2 shrink-0">
            @if($campaign->status === 'active')
                <button wire:click="pause"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-orange-800 bg-orange-50 border border-orange-200 hover:bg-orange-100">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ __('Pause') }}
                </button>
            @elseif($campaign->status === 'paused')
                <button wire:click="resume"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-emerald-800 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ __('Resume') }}
                </button>
            @endif
            @if($this->counts['failed'] > 0)
                <button wire:click="retryFailed"
                        wire:confirm="{{ __('Requeue :n failed recipients?', ['n' => $this->counts['failed']]) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-zinc-800 bg-zinc-100 border border-zinc-200 hover:bg-zinc-200">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    {{ __('Retry :n failed', ['n' => $this->counts['failed']]) }}
                </button>
            @endif
        </div>
    </div>

    {{-- Stat tiles --}}
    @php $c = $this->counts; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
        @foreach([
            ['Total',    $c['total'],        'text-zinc-900',    'bg-white  border-zinc-200'],
            ['Pending',  $c['pending'],      'text-zinc-700',    'bg-zinc-50 border-zinc-200'],
            ['Sent',     $c['sent'],         'text-emerald-700', 'bg-emerald-50 border-emerald-200'],
            ['Opened',   $c['opened'],       'text-emerald-700',  'bg-emerald-50 border-emerald-200'],
            ['Failed',   $c['failed'],       'text-red-700',     'bg-red-50 border-red-200'],
            ['Unsub.',   $c['unsubscribed'], 'text-orange-700',  'bg-orange-50 border-orange-200'],
        ] as [$label, $value, $color, $bgBorder])
            <div class="rounded-xl p-4 text-center border {{ $bgBorder }}">
                <p class="text-2xl font-bold {{ $color }}">{{ number_format($value) }}</p>
                <p class="text-xs font-medium text-zinc-700 mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- Progress bar --}}
    @if($c['total'] > 0)
        @php
            $completed = $c['sent'] + $c['failed'] + $c['unsubscribed'];
            $pct = (int) round(($completed / $c['total']) * 100);
            $successRate = $c['sent'] > 0 && ($c['sent'] + $c['failed']) > 0
                ? round(($c['sent'] / ($c['sent'] + $c['failed'])) * 100, 1)
                : null;
        @endphp
        <div>
            <div class="flex justify-between items-baseline text-xs mb-1">
                <div class="flex items-center gap-3">
                    <span class="font-semibold text-zinc-900">{{ __('Progress') }}</span>
                    <span class="text-zinc-700">
                        {{ number_format($completed) }} / {{ number_format($c['total']) }}
                    </span>
                    @if($successRate !== null)
                        <span class="text-zinc-700">·</span>
                        <span class="text-zinc-700">
                            <strong class="{{ $successRate >= 95 ? 'text-emerald-700' : ($successRate >= 80 ? 'text-yellow-700' : 'text-red-700') }}">{{ $successRate }}%</strong> {{ __('success rate') }}
                        </span>
                    @endif
                </div>
                <span class="font-semibold text-zinc-900">{{ $pct }}%</span>
            </div>
            <div class="h-2 rounded-full bg-zinc-200 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all duration-500"
                     style="width: {{ $pct }}%"></div>
            </div>
        </div>
    @endif

    {{-- Filter tabs --}}
    @php
        $tabs = $isWhatsapp
            ? ['all' => __('All'), 'pending' => __('Pending'), 'sent' => __('Sent'), 'failed' => __('Failed')]
            : ['all' => __('All'), 'pending' => __('Pending'), 'sent' => __('Sent'), 'opened' => __('Opened'), 'failed' => __('Failed'), 'unsubscribed' => __('Unsubscribed')];
    @endphp
    <div class="flex gap-0 border-b border-zinc-200 overflow-x-auto">
        @foreach($tabs as $key => $label)
            @php
                $count = $c[$key] ?? null;
            @endphp
            <button wire:click="$set('filter', '{{ $key }}')"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold transition-colors cursor-pointer -mb-px whitespace-nowrap
                           {{ $filter === $key ? 'border-b-2 border-emerald-600 text-emerald-700' : 'text-zinc-700 hover:text-zinc-900' }}">
                {{ $label }}
                @if($count !== null && $key !== 'all')
                    <span class="inline-flex items-center justify-center min-w-[20px] px-1.5 h-5 rounded-full text-[10px] font-bold
                                 {{ $filter === $key ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-700' }}">
                        {{ number_format($count) }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Recipients table --}}
    <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr class="text-left text-xs font-semibold text-zinc-700 uppercase tracking-wide">
                        <th class="px-4 py-3">{{ $identifierColumn }}</th>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Attempts') }}</th>
                        <th class="px-4 py-3">{{ __('Scheduled') }}</th>
                        <th class="px-4 py-3">{{ __('Sent') }}</th>
                        <th class="px-4 py-3">{{ __('Error') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($recipients as $r)
                        @php
                            $statusClass = match($r->status) {
                                'sent'         => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'opened'       => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'pending'      => 'bg-zinc-50 text-zinc-700 border-zinc-200',
                                'queued'       => 'bg-blue-50 text-blue-800 border-blue-200',
                                'sending'      => 'bg-yellow-50 text-yellow-800 border-yellow-200',
                                'failed'       => 'bg-red-50 text-red-800 border-red-200',
                                'unsubscribed' => 'bg-orange-50 text-orange-800 border-orange-200',
                                default        => 'bg-zinc-50 text-zinc-700 border-zinc-200',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50">
                            <td class="px-4 py-2.5 font-mono text-zinc-900">
                                {{ $isWhatsapp ? $r->phone : $r->email }}
                            </td>
                            <td class="px-4 py-2.5 text-zinc-800">{{ $r->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border {{ $statusClass }}">
                                    {{ $r->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-zinc-700">{{ $r->attempts }}</td>
                            <td class="px-4 py-2.5 text-zinc-700">{{ optional($r->scheduled_at)->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-zinc-700">{{ optional($r->sent_at)->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-red-700 text-xs">
                                @if($r->last_error)
                                    <span title="{{ $r->last_error }}">{{ \Illuminate\Support\Str::limit($r->last_error, 60) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-700">
                                {{ __('No recipients in this view.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $recipients->links() }}</div>
</div>
