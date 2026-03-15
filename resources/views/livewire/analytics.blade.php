<div>
    @if(! $data)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <flux:icon name="chart-bar" class="mb-4 size-16 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('No team selected') }}</flux:heading>
        </div>
    @else
        <div class="space-y-6">
            {{-- Header with period selector --}}
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">{{ __('Analytics') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('AI performance and sales insights') }}</flux:text>
                </div>
                <div class="flex gap-1">
                    @foreach(['7' => '7d', '14' => '14d', '30' => '30d', '90' => '90d'] as $value => $label)
                        <button
                            wire:click="$set('period', '{{ $value }}')"
                            class="cursor-pointer rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $period === $value ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Top Row: Key Metrics --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- AI Automation Rate --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('AI Automation Rate') }}</flux:text>
                    <div class="mt-2 flex items-end gap-2">
                        <span class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $data['aiVsHuman']['ai_percent'] }}%</span>
                    </div>
                    <flux:text class="mt-1 text-xs">{{ number_format($data['aiVsHuman']['ai']) }} {{ __('AI') }} / {{ number_format($data['aiVsHuman']['human']) }} {{ __('human responses') }}</flux:text>
                </div>

                {{-- AI Avg Response Time --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('AI Avg Response') }}</flux:text>
                    <div class="mt-2">
                        @if($data['responseTime']['ai_avg'] !== null)
                            <span class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $data['responseTime']['ai_avg'] }}s</span>
                        @else
                            <span class="text-3xl font-bold text-zinc-400">—</span>
                        @endif
                    </div>
                    <flux:text class="mt-1 text-xs">{{ __('Based on') }} {{ $data['responseTime']['ai_count'] }} {{ __('responses') }}</flux:text>
                </div>

                {{-- Human Avg Response Time --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Human Avg Response') }}</flux:text>
                    <div class="mt-2">
                        @if($data['responseTime']['human_avg'] !== null)
                            @php
                                $humanTime = $data['responseTime']['human_avg'];
                                $humanDisplay = $humanTime >= 3600 ? round($humanTime / 3600, 1) . 'h' : ($humanTime >= 60 ? round($humanTime / 60) . 'm' : $humanTime . 's');
                            @endphp
                            <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $humanDisplay }}</span>
                        @else
                            <span class="text-3xl font-bold text-zinc-400">—</span>
                        @endif
                    </div>
                    <flux:text class="mt-1 text-xs">{{ __('Based on') }} {{ $data['responseTime']['human_count'] }} {{ __('responses') }}</flux:text>
                </div>

                {{-- Conversion Rate --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Conversion Rate') }}</flux:text>
                    <div class="mt-2">
                        <span class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $data['conversionFunnel']['conversion_rate'] }}%</span>
                    </div>
                    <flux:text class="mt-1 text-xs">{{ $data['conversionFunnel']['stages']['converted'] ?? 0 }} {{ __('converted out of') }} {{ $data['conversionFunnel']['total'] }}</flux:text>
                </div>
            </div>

            {{-- AI vs Human Breakdown + Conversion Funnel --}}
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- AI vs Human detailed --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('AI vs Human Responses') }}</flux:heading>
                    @if($data['aiVsHuman']['total'] > 0)
                        <div class="space-y-4">
                            {{-- Visual bar --}}
                            <div class="flex h-8 overflow-hidden rounded-full">
                                @if($data['aiVsHuman']['ai'] > 0)
                                    <div class="flex items-center justify-center bg-purple-500 text-xs font-bold text-white transition-all" style="width: {{ $data['aiVsHuman']['ai_percent'] }}%">
                                        @if($data['aiVsHuman']['ai_percent'] > 15) {{ $data['aiVsHuman']['ai_percent'] }}% @endif
                                    </div>
                                @endif
                                @if($data['aiVsHuman']['human'] > 0)
                                    <div class="flex items-center justify-center bg-blue-500 text-xs font-bold text-white transition-all" style="width: {{ 100 - $data['aiVsHuman']['ai_percent'] }}%">
                                        @if((100 - $data['aiVsHuman']['ai_percent']) > 15) {{ round(100 - $data['aiVsHuman']['ai_percent'], 1) }}% @endif
                                    </div>
                                @endif
                            </div>
                            <div class="flex justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full bg-purple-500"></div>
                                    <flux:text class="text-sm">{{ __('AI') }}: {{ number_format($data['aiVsHuman']['ai']) }}</flux:text>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="size-3 rounded-full bg-blue-500"></div>
                                    <flux:text class="text-sm">{{ __('Human') }}: {{ number_format($data['aiVsHuman']['human']) }}</flux:text>
                                </div>
                            </div>

                            {{-- Response time comparison --}}
                            @if($data['responseTime']['ai_avg'] !== null && $data['responseTime']['human_avg'] !== null)
                                <div class="mt-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-700/50">
                                    <flux:text class="mb-2 text-xs font-medium text-zinc-500">{{ __('Speed Comparison') }}</flux:text>
                                    @php
                                        $speedup = $data['responseTime']['human_avg'] > 0 ? round($data['responseTime']['human_avg'] / max($data['responseTime']['ai_avg'], 1)) : 0;
                                    @endphp
                                    <flux:text class="text-sm">
                                        {{ __('AI responds') }} <span class="font-bold text-green-600 dark:text-green-400">{{ $speedup }}x {{ __('faster') }}</span> {{ __('than human agents') }}
                                    </flux:text>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="chart-bar" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No response data in this period') }}</flux:text>
                        </div>
                    @endif
                </div>

                {{-- Conversion Funnel --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Lead Funnel') }}</flux:heading>
                    @php
                        $funnelColors = [
                            'new' => 'bg-gray-400',
                            'cold' => 'bg-blue-400',
                            'warm' => 'bg-yellow-400',
                            'hot' => 'bg-orange-500',
                            'converted' => 'bg-green-500',
                            'lost' => 'bg-red-400',
                        ];
                        $maxFunnel = max(1, max($data['conversionFunnel']['stages']));
                    @endphp
                    @if($data['conversionFunnel']['total'] > 0)
                        <div class="space-y-3">
                            @foreach($data['conversionFunnel']['stages'] as $stage => $count)
                                @if($count > 0 || in_array($stage, ['new', 'warm', 'hot', 'converted']))
                                    <div>
                                        <div class="mb-1 flex items-center justify-between">
                                            <flux:text class="text-sm font-medium">{{ ucfirst($stage) }}</flux:text>
                                            <flux:text class="text-sm">{{ $count }}</flux:text>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                            <div class="{{ $funnelColors[$stage] ?? 'bg-gray-400' }} h-full rounded-full transition-all" style="width: {{ round(($count / $maxFunnel) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="funnel" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No contacts yet') }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Platform Performance --}}
            @if(! empty($data['platformPerformance']))
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Platform Performance') }}</flux:heading>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Platform') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500">{{ __('Conversations') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500">{{ __('Messages') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500">{{ __('Qualified Leads') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                                @php
                                    $platformIcons = [
                                        'facebook' => 'bg-blue-500',
                                        'instagram' => 'bg-pink-500',
                                        'whatsapp' => 'bg-green-500',
                                        'telegram' => 'bg-cyan-500',
                                    ];
                                @endphp
                                @foreach($data['platformPerformance'] as $platform => $stats)
                                    <tr>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="size-2.5 rounded-full {{ $platformIcons[$platform] ?? 'bg-gray-400' }}"></div>
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($platform) }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 text-right text-zinc-700 dark:text-zinc-300">{{ $stats['conversations'] }}</td>
                                        <td class="py-3 text-right text-zinc-700 dark:text-zinc-300">{{ $stats['messages'] }}</td>
                                        <td class="py-3 text-right">
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">{{ $stats['qualified_leads'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Daily Message Volume + Top Objections --}}
            <div class="grid gap-4 lg:grid-cols-2">
                {{-- Daily Messages Chart (text-based) --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Daily Message Volume') }}</flux:heading>
                    @if(! empty($data['dailyMessages']))
                        @php
                            $maxDaily = max(array_map(fn ($d) => $d['ai'] + $d['human'] + $d['inbound'], $data['dailyMessages']));
                            $recentDays = array_slice($data['dailyMessages'], -14); // last 14 days
                        @endphp
                        <div class="space-y-1.5">
                            @foreach($recentDays as $day)
                                @php
                                    $dayTotal = $day['ai'] + $day['human'] + $day['inbound'];
                                    $pct = $maxDaily > 0 ? round(($dayTotal / $maxDaily) * 100) : 0;
                                    $dateLabel = \Carbon\Carbon::parse($day['date'])->format('M j');
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="w-12 flex-shrink-0 text-xs text-zinc-500">{{ $dateLabel }}</span>
                                    <div class="flex h-4 flex-1 overflow-hidden rounded">
                                        @if($day['inbound'] > 0)
                                            <div class="bg-zinc-400" style="width: {{ $maxDaily > 0 ? round(($day['inbound'] / $maxDaily) * 100) : 0 }}%"></div>
                                        @endif
                                        @if($day['ai'] > 0)
                                            <div class="bg-purple-500" style="width: {{ $maxDaily > 0 ? round(($day['ai'] / $maxDaily) * 100) : 0 }}%"></div>
                                        @endif
                                        @if($day['human'] > 0)
                                            <div class="bg-blue-500" style="width: {{ $maxDaily > 0 ? round(($day['human'] / $maxDaily) * 100) : 0 }}%"></div>
                                        @endif
                                    </div>
                                    <span class="w-8 flex-shrink-0 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ $dayTotal }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 flex gap-4">
                            <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-zinc-400"></div><flux:text class="text-xs">{{ __('Inbound') }}</flux:text></div>
                            <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-purple-500"></div><flux:text class="text-xs">{{ __('AI') }}</flux:text></div>
                            <div class="flex items-center gap-1.5"><div class="size-2 rounded-full bg-blue-500"></div><flux:text class="text-xs">{{ __('Human') }}</flux:text></div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="chart-bar" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No messages in this period') }}</flux:text>
                        </div>
                    @endif
                </div>

                {{-- Top Objections --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Common Objections') }}</flux:heading>
                    @if(! empty($data['topObjections']))
                        <div class="space-y-3">
                            @foreach($data['topObjections'] as $objection)
                                <div class="flex items-start gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-700/50">
                                    <div class="flex-shrink-0 rounded bg-red-100 px-1.5 py-0.5 dark:bg-red-900/30">
                                        <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $objection['avg_impact'] }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $objection['reason'] }}</p>
                                        <flux:text class="text-xs text-zinc-500">{{ $objection['occurrences'] }}x {{ __('in this period') }}</flux:text>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="shield-check" class="mb-2 size-8 text-zinc-300 dark:text-zinc-600" />
                            <flux:text class="text-sm text-zinc-400">{{ __('No objections recorded') }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Lead Distribution --}}
            @if(! empty($data['leadDistribution']))
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm" class="mb-4">{{ __('Lead Score Distribution') }}</flux:heading>
                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        @php
                            $statusEmoji = [
                                'new' => '🔵', 'cold' => '❄️', 'warm' => '🌤️',
                                'hot' => '🔥', 'converted' => '✅', 'lost' => '❌',
                            ];
                            $statusBg = [
                                'new' => 'border-gray-300 dark:border-gray-600',
                                'cold' => 'border-blue-300 dark:border-blue-700',
                                'warm' => 'border-yellow-300 dark:border-yellow-700',
                                'hot' => 'border-orange-300 dark:border-orange-700',
                                'converted' => 'border-green-300 dark:border-green-700',
                                'lost' => 'border-red-300 dark:border-red-700',
                            ];
                        @endphp
                        @foreach($data['leadDistribution'] as $status => $info)
                            <div class="rounded-lg border-2 p-3 text-center {{ $statusBg[$status] ?? 'border-zinc-200' }}">
                                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $info['count'] }}</p>
                                <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ ucfirst($status) }}</p>
                                <p class="text-xs text-zinc-500">{{ __('avg') }} {{ $info['avg_score'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Conversations handled by AI --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm" class="mb-4">{{ __('Conversation Status') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-zinc-50 p-4 text-center dark:bg-zinc-700/50">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $data['conversationVolume']['total'] }}</p>
                        <flux:text class="text-sm">{{ __('New conversations') }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ __('in selected period') }}</flux:text>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-4 text-center dark:bg-zinc-700/50">
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $data['conversationVolume']['by_status']['open'] ?? 0 }}</p>
                        <flux:text class="text-sm">{{ __('Open') }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ __('active conversations') }}</flux:text>
                    </div>
                    <div class="rounded-lg bg-orange-50 p-4 text-center dark:bg-orange-900/10">
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $data['conversationVolume']['ai_paused'] }}</p>
                        <flux:text class="text-sm">{{ __('AI Paused') }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ __('human-handled') }}</flux:text>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
