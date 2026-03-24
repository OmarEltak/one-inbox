<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Content') }}</h1>
            <p class="mt-1 text-sm text-white/40">{{ __('Manage and track your content across all platforms.') }}</p>
        </div>
        <button class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white aio-btn-primary cursor-pointer">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('Create New Content') }}
        </button>
    </div>

    {{-- Stat Chips --}}
    @php
        $allItems = $this->content;
        $published = array_filter($allItems, fn($c) => $c['status'] === 'published');
        $scheduled = array_filter($allItems, fn($c) => $c['status'] === 'scheduled');
        $drafts = array_filter($allItems, fn($c) => $c['status'] === 'draft');
        $totalViews = array_sum(array_column($allItems, 'views'));
        $totalClicks = array_sum(array_column($allItems, 'clicks'));
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="aio-card rounded-2xl p-4 text-center">
            <p class="text-xl font-bold text-white/80">{{ count($allItems) }}</p>
            <p class="text-xs text-white/35 mt-1">{{ __('Total') }}</p>
        </div>
        <div class="aio-card aio-stat-green rounded-2xl p-4 text-center">
            <p class="text-xl font-bold text-[#00D492]">{{ count($published) }}</p>
            <p class="text-xs text-white/35 mt-1">{{ __('Published') }}</p>
        </div>
        <div class="aio-card aio-stat-blue rounded-2xl p-4 text-center">
            <p class="text-xl font-bold text-[#3b82f6]">{{ count($scheduled) }}</p>
            <p class="text-xs text-white/35 mt-1">{{ __('Scheduled') }}</p>
        </div>
        <div class="aio-card aio-stat-purple rounded-2xl p-4 text-center">
            <p class="text-xl font-bold text-[#C27AFF]">{{ number_format($totalViews) }}</p>
            <p class="text-xs text-white/35 mt-1">{{ __('Total Views') }}</p>
        </div>
        <div class="aio-card aio-stat-cyan rounded-2xl p-4 text-center">
            <p class="text-xl font-bold text-[#06B6D4]">{{ number_format($totalClicks) }}</p>
            <p class="text-xs text-white/35 mt-1">{{ __('Total Clicks') }}</p>
        </div>
    </div>

    {{-- Tab Filter --}}
    <div class="flex gap-0" style="border-bottom: 1px solid rgba(255,255,255,0.07);">
        @foreach(['all' => 'All', 'published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Draft'] as $key => $label)
            <button
                wire:click="$set('tab', '{{ $key }}')"
                class="px-4 py-2.5 text-sm font-medium transition-colors cursor-pointer -mb-px
                       {{ $tab === $key ? 'text-[#C27AFF]' : 'text-white/35 hover:text-white/60' }}"
                @if($tab === $key) style="border-bottom: 2px solid #7C3AED;" @endif
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- Content Table --}}
    <div class="aio-card overflow-x-auto rounded-2xl">
        <table class="w-full text-sm aio-table">
            <thead>
                <tr>
                    <th class="text-left px-4 py-3">{{ __('Content') }}</th>
                    <th class="text-left px-4 py-3">{{ __('Platform') }}</th>
                    <th class="text-left px-4 py-3">{{ __('Status') }}</th>
                    <th class="text-left px-4 py-3">{{ __('Date') }}</th>
                    <th class="text-right px-4 py-3">{{ __('Views') }}</th>
                    <th class="text-right px-4 py-3">{{ __('Reach') }}</th>
                    <th class="text-right px-4 py-3">{{ __('Engagement') }}</th>
                    <th class="text-right px-4 py-3">{{ __('Clicks') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="cursor-default hover:bg-white/[0.02] transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-white/80 truncate max-w-[200px]">{{ $item['title'] }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center justify-center size-7 rounded-xl text-white text-[11px] font-bold {{ match($item['platform']) {
                                'facebook' => 'bg-blue-500',
                                'instagram' => 'bg-pink-500',
                                'whatsapp' => 'bg-green-500',
                                'telegram' => 'bg-cyan-500',
                                'tiktok' => 'bg-red-500',
                                'snapchat' => 'bg-yellow-400 text-yellow-900',
                                'email' => 'bg-orange-500',
                                default => 'bg-gray-500',
                            } }}" title="{{ ucfirst($item['platform']) }}">
                                {{ strtoupper(substr($item['platform'], 0, 2)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ match($item['status']) {
                                'published' => 'bg-green-500/15 text-green-400',
                                'scheduled' => 'bg-blue-500/15 text-blue-400',
                                default => 'bg-white/5 text-white/35',
                            } }}">
                                {{ ucfirst($item['status']) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-white/40">
                            {{ \Carbon\Carbon::parse($item['date'])->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 text-right text-white/70 font-medium">
                            {{ $item['views'] > 0 ? number_format($item['views']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-white/40">
                            {{ $item['reach'] > 0 ? number_format($item['reach']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($item['engagement'] > 0)
                                <span class="text-[#C27AFF] font-semibold">{{ $item['engagement'] }}%</span>
                            @else
                                <span class="text-white/25">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-white/40">
                            {{ $item['clicks'] > 0 ? number_format($item['clicks']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button class="text-white/25 hover:text-white/60 transition-colors cursor-pointer p-1">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-14 text-center text-white/30">
                            {{ __('No content found for this filter.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
