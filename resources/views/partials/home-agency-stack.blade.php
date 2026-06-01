{{--
    Agency operator's view (light theme): stacked client workspaces.
    Same per-team color logic as the in-product workspace chip (crc32 hue).
--}}
@php
    $clients = [
        ['name' => 'Acme Apparel',          'channels' => 'IG + WA',       'today' => 47, 'hot' => 3, 'spark' => '0,32 14,28 28,22 42,26 56,12 70,16 84,8'],
        ['name' => 'Cairo Real Estate',     'channels' => 'FB + WA + TG',  'today' => 31, 'hot' => 1, 'spark' => '0,30 14,24 28,28 42,18 56,20 70,12 84,14'],
        ['name' => 'Belmonte Restaurants',  'channels' => 'IG + FB',       'today' => 22, 'hot' => 0, 'spark' => '0,28 14,30 28,24 42,22 56,18 70,20 84,16'],
        ['name' => 'Nile Yoga Studio',      'channels' => 'WA + Email',    'today' => 14, 'hot' => 2, 'spark' => '0,34 14,32 28,28 42,30 56,24 70,20 84,18'],
    ];
@endphp

<div class="relative">
    {{-- Stacked workspace chips --}}
    <div class="space-y-3">
        @foreach($clients as $client)
            @php
                $hue = crc32(strtolower($client['name'])) % 360;
                $initial = strtoupper(mb_substr($client['name'], 0, 1));
            @endphp
            <div
                class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 transition-colors hover:border-zinc-300"
                style="background: linear-gradient(135deg, hsla({{ $hue }}, 75%, 96%, 1), #ffffff 60%);"
            >
                {{-- Avatar --}}
                <div class="flex size-10 flex-shrink-0 items-center justify-center rounded-lg text-base font-bold text-white shadow-sm"
                     style="background: hsl({{ $hue }}, 65%, 50%);">
                    {{ $initial }}
                </div>

                {{-- Name + channels --}}
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-zinc-900">{{ $client['name'] }}</div>
                    <div class="mt-0.5 text-[11px] text-zinc-500">{{ $client['channels'] }}</div>
                </div>

                {{-- Today count --}}
                <div class="hidden text-right sm:block">
                    <div class="text-sm font-semibold text-zinc-900">{{ $client['today'] }}</div>
                    <div class="text-[10px] uppercase tracking-widest text-zinc-400">today</div>
                </div>

                {{-- Sparkline --}}
                <svg class="hidden h-8 w-20 flex-shrink-0 sm:block" viewBox="0 0 84 40" fill="none" preserveAspectRatio="none">
                    <polyline points="{{ $client['spark'] }}"
                              stroke="hsl({{ $hue }}, 65%, 50%)" stroke-width="1.5" fill="none"
                              stroke-linecap="round" stroke-linejoin="round" />
                </svg>

                {{-- Hot lead badge --}}
                @if($client['hot'] > 0)
                    <span class="inline-flex flex-shrink-0 items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                        <span class="size-1 rounded-full bg-rose-500"></span>
                        {{ $client['hot'] }} hot
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Sum bar --}}
    <div class="mt-5 flex items-center justify-between rounded-xl border border-indigo-200 bg-indigo-50 p-4">
        <div class="flex items-center gap-3">
            <svg class="size-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
            </svg>
            <div>
                <div class="text-sm font-semibold text-zinc-900">114 conversations today</div>
                <div class="text-xs text-zinc-600">82 closed by AI · 6 hot leads waiting for you</div>
            </div>
        </div>
        <span class="hidden rounded-md bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-indigo-700 sm:inline">All clients</span>
    </div>
</div>
