<div>
    @if(! $data)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <svg class="mb-4 size-16 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h2 class="text-lg font-semibold text-white/80">{{ __('No team selected') }}</h2>
        </div>
    @else
        <div class="p-6 space-y-6">
            {{-- Header with period selector --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ __('Analytics') }}</h1>
                    <p class="mt-1 text-sm text-white/40">{{ __('AI performance and sales insights') }}</p>
                </div>
                <div class="flex gap-0.5 rounded-xl p-1" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);">
                    @foreach(['7' => '7d', '14' => '14d', '30' => '30d', '90' => '90d'] as $value => $label)
                        <button
                            wire:click="$set('period', '{{ $value }}')"
                            class="cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold transition-all {{ $period === $value ? 'text-white shadow-sm' : 'text-white/35 hover:text-white/60' }}"
                            @if($period === $value) style="background: linear-gradient(135deg, #7C3AED, #6D28D9); box-shadow: 0 2px 8px rgba(124,58,237,0.3);" @endif
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Top Row: Key Metrics --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- AI Automation Rate --}}
                <div class="aio-card aio-stat-purple rounded-2xl p-5">
                    <p class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">{{ __('AI Automation') }}</p>
                    <span class="text-3xl font-bold text-[#C27AFF]">{{ $data['aiVsHuman']['ai_percent'] }}%</span>
                    <p class="mt-2 text-xs text-white/35">{{ number_format($data['aiVsHuman']['ai']) }} AI / {{ number_format($data['aiVsHuman']['human']) }} human</p>
                </div>

                {{-- AI Avg Response Time --}}
                <div class="aio-card aio-stat-green rounded-2xl p-5">
                    <p class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">{{ __('AI Avg Response') }}</p>
                    @if($data['responseTime']['ai_avg'] !== null)
                        <span class="text-3xl font-bold text-[#00D492]">{{ $data['responseTime']['ai_avg'] }}s</span>
                    @else
                        <span class="text-3xl font-bold text-white/20">—</span>
                    @endif
                    <p class="mt-2 text-xs text-white/35">{{ __('Based on') }} {{ $data['responseTime']['ai_count'] }} {{ __('responses') }}</p>
                </div>

                {{-- Human Avg Response Time --}}
                <div class="aio-card aio-stat-blue rounded-2xl p-5">
                    <p class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">{{ __('Human Avg Response') }}</p>
                    @if($data['responseTime']['human_avg'] !== null)
                        @php
                            $humanTime = $data['responseTime']['human_avg'];
                            $humanDisplay = $humanTime >= 3600 ? round($humanTime / 3600, 1) . 'h' : ($humanTime >= 60 ? round($humanTime / 60) . 'm' : $humanTime . 's');
                        @endphp
                        <span class="text-3xl font-bold text-[#3b82f6]">{{ $humanDisplay }}</span>
                    @else
                        <span class="text-3xl font-bold text-white/20">—</span>
                    @endif
                    <p class="mt-2 text-xs text-white/35">{{ __('Based on') }} {{ $data['responseTime']['human_count'] }} {{ __('responses') }}</p>
                </div>

                {{-- Conversion Rate --}}
                <div class="aio-card aio-stat-orange rounded-2xl p-5">
                    <p class="text-xs font-semibold text-white/40 uppercase tracking-widest mb-3">{{ __('Conversion Rate') }}</p>
                    <span class="text-3xl font-bold text-[#FBBF24]">{{ $data['conversionFunnel']['conversion_rate'] }}%</span>
                    <p class="mt-2 text-xs text-white/35">{{ $data['conversionFunnel']['stages']['converted'] ?? 0 }} converted of {{ $data['conversionFunnel']['total'] }}</p>
                </div>
            </div>

            {{-- Reach Across Platforms — Chart.js line chart --}}
            <div class="aio-card rounded-2xl p-5"
                 x-data="{
                     chart: null,
                     init() {
                         const labels = @js(array_column($data['dailyMessages'] ?? [], 'date'));
                         const inbound = @js(array_column($data['dailyMessages'] ?? [], 'inbound'));
                         const ai = @js(array_column($data['dailyMessages'] ?? [], 'ai'));
                         const human = @js(array_column($data['dailyMessages'] ?? [], 'human'));

                         const ctx = this.$refs.reachChart.getContext('2d');
                         this.chart = new Chart(ctx, {
                             type: 'line',
                             data: {
                                 labels: labels,
                                 datasets: [
                                     {
                                         label: 'Inbound',
                                         data: inbound,
                                         borderColor: '#64748b',
                                         backgroundColor: 'rgba(100,116,139,0.1)',
                                         fill: true,
                                         tension: 0.4,
                                         pointRadius: 3,
                                     },
                                     {
                                         label: 'AI Responses',
                                         data: ai,
                                         borderColor: '#8b5cf6',
                                         backgroundColor: 'rgba(139,92,246,0.1)',
                                         fill: true,
                                         tension: 0.4,
                                         pointRadius: 3,
                                     },
                                     {
                                         label: 'Human Responses',
                                         data: human,
                                         borderColor: '#3b82f6',
                                         backgroundColor: 'rgba(59,130,246,0.1)',
                                         fill: true,
                                         tension: 0.4,
                                         pointRadius: 3,
                                     },
                                 ]
                             },
                             options: {
                                 responsive: true,
                                 maintainAspectRatio: false,
                                 plugins: {
                                     legend: {
                                         labels: { color: '#64748b', font: { size: 12 } }
                                     },
                                     tooltip: {
                                         backgroundColor: '#161b27',
                                         borderColor: '#1e2536',
                                         borderWidth: 1,
                                         titleColor: '#f1f5f9',
                                         bodyColor: '#64748b',
                                     }
                                 },
                                 scales: {
                                     x: {
                                         ticks: { color: '#64748b', maxTicksLimit: 8 },
                                         grid: { color: '#1e2536' }
                                     },
                                     y: {
                                         ticks: { color: '#64748b' },
                                         grid: { color: '#1e2536' }
                                     }
                                 }
                             }
                         });
                     }
                 }"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-white/80">{{ __('Reach Across Platforms') }}</h3>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-[#64748b]"></div><span class="text-xs text-white/40">Inbound</span></div>
                        <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-[#8b5cf6]"></div><span class="text-xs text-white/40">AI</span></div>
                        <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-[#3b82f6]"></div><span class="text-xs text-white/40">Human</span></div>
                    </div>
                </div>
                <div class="h-64">
                    <canvas x-ref="reachChart"></canvas>
                </div>
            </div>

            {{-- AI vs Human + Lead Funnel --}}
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- AI vs Human --}}
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('AI vs Human Responses') }}</h3>
                    @if($data['aiVsHuman']['total'] > 0)
                        <div class="space-y-4">
                            <div class="flex h-8 overflow-hidden rounded-full">
                                @if($data['aiVsHuman']['ai'] > 0)
                                    <div class="flex items-center justify-center bg-[#8b5cf6] text-xs font-bold text-white transition-all" style="width: {{ $data['aiVsHuman']['ai_percent'] }}%">
                                        @if($data['aiVsHuman']['ai_percent'] > 15) {{ $data['aiVsHuman']['ai_percent'] }}% @endif
                                    </div>
                                @endif
                                @if($data['aiVsHuman']['human'] > 0)
                                    <div class="flex items-center justify-center bg-[#3b82f6] text-xs font-bold text-white transition-all" style="width: {{ 100 - $data['aiVsHuman']['ai_percent'] }}%">
                                        @if((100 - $data['aiVsHuman']['ai_percent']) > 15) {{ round(100 - $data['aiVsHuman']['ai_percent'], 1) }}% @endif
                                    </div>
                                @endif
                            </div>
                            <div class="flex justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full bg-[#8b5cf6]"></div>
                                    <span class="text-sm text-white/80">AI: {{ number_format($data['aiVsHuman']['ai']) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full bg-[#3b82f6]"></div>
                                    <span class="text-sm text-white/80">Human: {{ number_format($data['aiVsHuman']['human']) }}</span>
                                </div>
                            </div>
                            @if($data['responseTime']['ai_avg'] !== null && $data['responseTime']['human_avg'] !== null)
                                @php
                                    $speedup = $data['responseTime']['human_avg'] > 0 ? round($data['responseTime']['human_avg'] / max($data['responseTime']['ai_avg'], 1)) : 0;
                                @endphp
                                <div class="rounded-lg bg-white/5 p-3">
                                    <p class="text-sm text-white/40">
                                        AI responds <span class="font-bold text-green-400">{{ $speedup }}x faster</span> than human agents
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-white/40">{{ __('No response data in this period') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Lead Funnel --}}
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Lead Funnel') }}</h3>
                    @php
                        $funnelColors = [
                            'new' => 'bg-gray-500',
                            'cold' => 'bg-blue-500',
                            'warm' => 'bg-yellow-500',
                            'hot' => 'bg-orange-500',
                            'converted' => 'bg-green-500',
                            'lost' => 'bg-red-500',
                        ];
                        $maxFunnel = max(1, max($data['conversionFunnel']['stages']));
                    @endphp
                    @if($data['conversionFunnel']['total'] > 0)
                        <div class="space-y-3">
                            @foreach($data['conversionFunnel']['stages'] as $stage => $count)
                                @if($count > 0 || in_array($stage, ['new', 'warm', 'hot', 'converted']))
                                    <div>
                                        <div class="mb-1 flex items-center justify-between">
                                            <span class="text-sm font-medium text-white/80">{{ ucfirst($stage) }}</span>
                                            <span class="text-sm text-white/40">{{ $count }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-white/5">
                                            <div class="{{ $funnelColors[$stage] ?? 'bg-gray-500' }} h-full rounded-full transition-all" style="width: {{ round(($count / $maxFunnel) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-white/40">{{ __('No contacts yet') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Platform Performance --}}
            @if(! empty($data['platformPerformance']))
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Platform Performance') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-white/[0.07]">
                                    <th class="pb-3 text-left font-medium text-white/40">{{ __('Platform') }}</th>
                                    <th class="pb-3 text-right font-medium text-white/40">{{ __('Conversations') }}</th>
                                    <th class="pb-3 text-right font-medium text-white/40">{{ __('Messages') }}</th>
                                    <th class="pb-3 text-right font-medium text-white/40">{{ __('Qualified Leads') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1e2536]">
                                @php
                                    $platformColors = [
                                        'facebook' => 'bg-blue-500',
                                        'instagram' => 'bg-pink-500',
                                        'whatsapp' => 'bg-green-500',
                                        'telegram' => 'bg-cyan-500',
                                        'tiktok' => 'bg-red-500',
                                        'email' => 'bg-orange-500',
                                    ];
                                @endphp
                                @foreach($data['platformPerformance'] as $platform => $stats)
                                    <tr>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="size-2.5 rounded-full {{ $platformColors[$platform] ?? 'bg-gray-400' }}"></div>
                                                <span class="font-medium text-white/80">{{ ucfirst($platform) }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right text-white/40">{{ $stats['conversations'] }}</td>
                                        <td class="py-3 text-right text-white/40">{{ $stats['messages'] }}</td>
                                        <td class="py-3 text-right">
                                            <span class="rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-medium text-green-400">{{ $stats['qualified_leads'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Daily Volume + Top Objections --}}
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- Daily Messages (bar chart visual) --}}
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Daily Message Volume') }}</h3>
                    @if(! empty($data['dailyMessages']))
                        @php
                            $maxDaily = max(array_map(fn ($d) => $d['ai'] + $d['human'] + $d['inbound'], $data['dailyMessages']));
                            $recentDays = array_slice($data['dailyMessages'], -14);
                        @endphp
                        <div class="space-y-1.5">
                            @foreach($recentDays as $day)
                                @php
                                    $dayTotal = $day['ai'] + $day['human'] + $day['inbound'];
                                    $pct = $maxDaily > 0 ? round(($dayTotal / $maxDaily) * 100) : 0;
                                    $dateLabel = \Carbon\Carbon::parse($day['date'])->format('M j');
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="w-12 flex-shrink-0 text-xs text-white/40">{{ $dateLabel }}</span>
                                    <div class="flex h-4 flex-1 overflow-hidden rounded">
                                        @if($day['inbound'] > 0)
                                            <div class="bg-[#64748b]" style="width: {{ $maxDaily > 0 ? round(($day['inbound'] / $maxDaily) * 100) : 0 }}%"></div>
                                        @endif
                                        @if($day['ai'] > 0)
                                            <div class="bg-[#8b5cf6]" style="width: {{ $maxDaily > 0 ? round(($day['ai'] / $maxDaily) * 100) : 0 }}%"></div>
                                        @endif
                                        @if($day['human'] > 0)
                                            <div class="bg-[#3b82f6]" style="width: {{ $maxDaily > 0 ? round(($day['human'] / $maxDaily) * 100) : 0 }}%"></div>
                                        @endif
                                    </div>
                                    <span class="w-8 flex-shrink-0 text-right text-xs font-medium text-white/40">{{ $dayTotal }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 flex gap-4">
                            <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-[#64748b]"></div><span class="text-xs text-white/40">Inbound</span></div>
                            <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-[#8b5cf6]"></div><span class="text-xs text-white/40">AI</span></div>
                            <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-[#3b82f6]"></div><span class="text-xs text-white/40">Human</span></div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-white/40">{{ __('No messages in this period') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Top Objections --}}
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Common Objections') }}</h3>
                    @if(! empty($data['topObjections']))
                        <div class="space-y-3">
                            @foreach($data['topObjections'] as $objection)
                                <div class="flex items-start gap-3 rounded-lg bg-white/5 p-3">
                                    <div class="flex-shrink-0 rounded bg-red-500/20 px-1.5 py-0.5">
                                        <span class="text-xs font-bold text-red-400">{{ $objection['avg_impact'] }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-white/80">{{ $objection['reason'] }}</p>
                                        <p class="text-xs text-white/40">{{ $objection['occurrences'] }}x in this period</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <p class="text-sm text-white/40">{{ __('No objections recorded') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Lead Score Distribution --}}
            @if(! empty($data['leadDistribution']))
                <div class="aio-card rounded-2xl p-5">
                    <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Lead Score Distribution') }}</h3>
                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @php
                            $statusColors = [
                                'new' => 'border-gray-500/50',
                                'cold' => 'border-blue-500/50',
                                'warm' => 'border-yellow-500/50',
                                'hot' => 'border-orange-500/50',
                                'converted' => 'border-green-500/50',
                                'lost' => 'border-red-500/50',
                            ];
                        @endphp
                        @foreach($data['leadDistribution'] as $status => $info)
                            <div class="rounded-xl border-2 p-3 text-center {{ $statusColors[$status] ?? 'border-white/[0.07]' }} bg-transparent">
                                <p class="text-2xl font-bold text-white/80">{{ $info['count'] }}</p>
                                <p class="mt-0.5 text-sm font-medium text-white/40">{{ ucfirst($status) }}</p>
                                <p class="text-xs text-white/40">avg {{ $info['avg_score'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Conversation Status --}}
            <div class="aio-card rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white/80 mb-4">{{ __('Conversation Status') }}</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-white/5 p-4 text-center">
                        <p class="text-2xl font-bold text-white/80">{{ $data['conversationVolume']['total'] }}</p>
                        <p class="text-sm text-white/40 mt-1">{{ __('New conversations') }}</p>
                        <p class="text-xs text-white/40">{{ __('in selected period') }}</p>
                    </div>
                    <div class="rounded-xl bg-white/5 p-4 text-center">
                        <p class="text-2xl font-bold text-white/80">{{ $data['conversationVolume']['by_status']['open'] ?? 0 }}</p>
                        <p class="text-sm text-white/40 mt-1">{{ __('Open') }}</p>
                        <p class="text-xs text-white/40">{{ __('active conversations') }}</p>
                    </div>
                    <div class="rounded-xl bg-orange-500/10 border border-orange-500/30 p-4 text-center">
                        <p class="text-2xl font-bold text-orange-400">{{ $data['conversationVolume']['ai_paused'] }}</p>
                        <p class="text-sm text-white/40 mt-1">{{ __('AI Paused') }}</p>
                        <p class="text-xs text-white/40">{{ __('human-handled') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
