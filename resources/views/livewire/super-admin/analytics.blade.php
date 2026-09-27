@php
    $kpis = $this->kpis;
    $funnel = $this->funnel;
    $activationFunnel = $this->activationFunnel;
    $teams = $this->teamsTable;
    $daily = $this->messagesDaily;
    $platforms = $this->platformMix;
    $activationRate = $this->activationRate;
    $ttfar = $this->timeToFirstAiReply;
    $bizFunnel = $this->businessTypeFunnel;
    $retention = $this->retention7d;
    $aiHealth = $this->aiDispatchHealth;

    $fmtSeconds = function (?int $s): string {
        if ($s === null) return '—';
        if ($s < 60) return $s . 's';
        if ($s < 3600) return round($s / 60) . 'm';
        if ($s < 86400) return round($s / 3600, 1) . 'h';
        return round($s / 86400, 1) . 'd';
    };

    $healthStyles = [
        'active'          => ['label' => 'Active',          'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'],
        'at_risk'         => ['label' => 'At risk',         'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
        'dormant'         => ['label' => 'Dormant',         'class' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200'],
        'never_activated' => ['label' => 'Never activated', 'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300'],
    ];

    $platformColors = [
        'facebook'  => '#1877F2',
        'instagram' => '#E1306C',
        'whatsapp'  => '#25D366',
        'telegram'  => '#0088CC',
        'email'     => '#10b981',
        'unknown'   => '#94A3B8',
    ];

    // Chart geometry (inline SVG, no JS lib).
    $chartW = 900;
    $chartH = 220;
    $chartPadL = 40;
    $chartPadR = 12;
    $chartPadT = 12;
    $chartPadB = 28;
    $plotW = $chartW - $chartPadL - $chartPadR;
    $plotH = $chartH - $chartPadT - $chartPadB;
    $dailyMax = max(1, collect($daily)->max(fn ($d) => $d['inbound'] + $d['ai_out'] + $d['human_out']));
    $barW = $plotW / max(1, count($daily));
@endphp

<div class="p-6 space-y-8">
    {{-- Top-of-page indeterminate progress bar during ANY Livewire request --}}
    <div wire:loading class="fixed top-0 left-0 right-0 z-50 h-0.5 bg-emerald-500 animate-pulse"></div>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">Analytics</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Product usage across all customer teams. Cached 5&nbsp;min (windowed) / 30&nbsp;min (all-time) &middot;
                <a href="?refresh=1" class="underline hover:text-zinc-900 dark:hover:text-zinc-200">refresh now</a>
            </flux:text>
        </div>
        <div wire:loading class="flex items-center gap-2 text-xs text-zinc-500">
            <svg class="w-4 h-4 animate-spin text-emerald-500 dark:text-emerald-400" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
            Recomputing…
        </div>
    </div>

    {{-- KPI TILES --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Total teams</div>
            <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($kpis['total_teams']) }}</div>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $kpis['active_7d'] }} active in 7d · {{ $kpis['active_30d'] }} in 30d
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">New signups (7d)</div>
            <div class="mt-1 flex items-baseline gap-2">
                <div class="text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $kpis['signups_this_week'] }}</div>
                @php $delta = $kpis['signup_delta_pct']; @endphp
                <div class="text-sm {{ $delta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $delta >= 0 ? '+' : '' }}{{ $delta }}%
                </div>
            </div>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                vs {{ $kpis['signups_last_week'] }} last week
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">AI replies (30d)</div>
            <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($kpis['ai_replies_30d']) }}</div>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">outbound, sender_type = ai</div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Teams with inbound (7d)</div>
            <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $kpis['teams_inbound_7d'] }}</div>
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">the "really using it" number</div>
        </div>
    </div>

    {{-- HEALTH METRICS ROW --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Activation rate --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Activation rate (24h)</div>
            @if($activationRate['has_data'])
                <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $activationRate['pct'] }}%</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $activationRate['activated'] }} of {{ $activationRate['eligible'] }} teams onboarded within 24h
                </div>
            @else
                <div class="mt-1 text-3xl font-semibold text-zinc-400 dark:text-zinc-600">—</div>
                <div class="mt-2 text-sm text-zinc-500">No data yet</div>
            @endif
        </div>

        {{-- Time to first AI reply --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Median TTFAR</div>
            @if($ttfar['has_data'])
                <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $fmtSeconds($ttfar['median_seconds']) }}</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Signup → first AI reply (n={{ $ttfar['sample_size'] }})
                </div>
            @else
                <div class="mt-1 text-3xl font-semibold text-zinc-400 dark:text-zinc-600">—</div>
                <div class="mt-2 text-sm text-zinc-500">No AI replies yet</div>
            @endif
        </div>

        {{-- 7-day retention --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">7-day retention</div>
            @if($retention['has_data'])
                <div class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $retention['pct'] }}%</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $retention['retained'] }} of {{ $retention['cohort'] }} (signed up 7–30d ago) still messaging
                </div>
            @else
                <div class="mt-1 text-3xl font-semibold text-zinc-400 dark:text-zinc-600">—</div>
                <div class="mt-2 text-sm text-zinc-500">Cohort empty</div>
            @endif
        </div>

        {{-- AI dispatch health --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500">AI dispatch health</div>
            @if($aiHealth['has_data'])
                <div class="mt-1 text-3xl font-semibold {{ $aiHealth['pct'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($aiHealth['pct'] >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">{{ $aiHealth['pct'] }}%</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $aiHealth['dispatchable'] }} of {{ $aiHealth['total'] }} teams can dispatch AI now
                </div>
            @else
                <div class="mt-1 text-3xl font-semibold text-zinc-400 dark:text-zinc-600">—</div>
                <div class="mt-2 text-sm text-zinc-500">No teams</div>
            @endif
        </div>
    </div>

    {{-- BUSINESS TYPE FUNNEL --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
        <div class="mb-4">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">Activation by business type (top 5)</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Onboarding completion rate broken down by industry — reveals which verticals convert.
            </flux:text>
        </div>
        @if(!$bizFunnel['has_data'] || count($bizFunnel['rows']) === 0)
            <div class="text-sm text-zinc-500 dark:text-zinc-400 py-4 text-center">No business type data yet.</div>
        @else
            <div class="space-y-3">
                @foreach($bizFunnel['rows'] as $r)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <div class="capitalize text-zinc-800 dark:text-zinc-200">{{ str_replace('_', ' ', $r['business_type']) }}</div>
                            <div class="text-zinc-600 dark:text-zinc-400 tabular-nums">
                                <span class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $r['completed'] }}</span>
                                / {{ $r['total'] }} · {{ $r['pct'] }}%
                            </div>
                        </div>
                        <div class="h-3 rounded bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded" style="width: {{ $r['pct'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ACTIVATION FUNNEL --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
        <div class="mb-4">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">Activation funnel</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Where teams drop off between signup and being retained.</flux:text>
        </div>
        <div class="space-y-2">
            @foreach($funnel as $stage)
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <div class="text-zinc-700 dark:text-zinc-300">{{ $stage['label'] }}</div>
                        <div class="text-zinc-600 dark:text-zinc-400">
                            <span class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $stage['count'] }}</span>
                            <span class="tabular-nums">· {{ $stage['pct'] }}%</span>
                        </div>
                    </div>
                    <div class="h-6 rounded bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-emerald-500 to-emerald-500 rounded transition-all"
                            style="width: {{ $stage['pct'] }}%"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ONBOARDING ACTIVATION FUNNEL (Phase E, cohort-scoped) --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
        <div class="mb-4">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">Onboarding funnel</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                Of the teams that signed up in each window, how many reached each step. Drop-off is measured stage-to-stage.
            </flux:text>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach(['d7' => 'Last 7 days', 'd30' => 'Last 30 days'] as $key => $label)
                @php $window = $activationFunnel[$key]; @endphp
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-baseline justify-between mb-3">
                        <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $label }}</div>
                        <div class="text-xs text-zinc-500">{{ $window['stages'][0]['count'] ?? 0 }} signups</div>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[10px] uppercase tracking-wide text-zinc-500 border-b border-zinc-200 dark:border-zinc-700">
                                <th class="py-1 pr-2">Stage</th>
                                <th class="py-1 pr-2 text-right">Count</th>
                                <th class="py-1 pr-2 text-right">% of signup</th>
                                <th class="py-1 text-right">Drop-off</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($window['stages'] as $i => $stage)
                                <tr>
                                    <td class="py-1.5 pr-2 text-zinc-800 dark:text-zinc-200">{{ $stage['label'] }}</td>
                                    <td class="py-1.5 pr-2 text-right tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($stage['count']) }}</td>
                                    <td class="py-1.5 pr-2 text-right tabular-nums text-zinc-600 dark:text-zinc-400">{{ $stage['pct'] }}%</td>
                                    <td class="py-1.5 text-right tabular-nums">
                                        @if($i === 0)
                                            <span class="text-zinc-400">—</span>
                                        @else
                                            @php
                                                $drop = $stage['drop_pct'];
                                                $klass = $drop >= 50 ? 'text-rose-600 dark:text-rose-400' : ($drop >= 25 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                                            @endphp
                                            <span class="{{ $klass }}">{{ $drop }}%</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>

    {{-- MESSAGES/DAY CHART --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between flex-wrap gap-2">
            <div>
                <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">Messages / day (30d)</flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Stacked: inbound (blue) · AI outbound (violet) · human outbound (emerald)</flux:text>
            </div>
            <div class="flex gap-4 text-xs">
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-sky-500"></span> Inbound</div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500"></span> AI</div>
                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500"></span> Human</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full h-auto" preserveAspectRatio="xMidYMid meet">
                {{-- Y-axis gridlines --}}
                @for($i = 0; $i <= 4; $i++)
                    @php $y = $chartPadT + ($plotH * $i / 4); @endphp
                    <line x1="{{ $chartPadL }}" y1="{{ $y }}" x2="{{ $chartW - $chartPadR }}" y2="{{ $y }}"
                          stroke="currentColor" class="text-zinc-200 dark:text-zinc-800" stroke-width="1" />
                    <text x="{{ $chartPadL - 6 }}" y="{{ $y + 4 }}" text-anchor="end"
                          class="fill-zinc-500 text-[10px]">{{ (int) round($dailyMax * (1 - $i / 4)) }}</text>
                @endfor

                {{-- Bars --}}
                @foreach($daily as $i => $day)
                    @php
                        $x = $chartPadL + ($i * $barW);
                        $w = max(1, $barW - 2);
                        $inH = ($day['inbound'] / $dailyMax) * $plotH;
                        $aiH = ($day['ai_out'] / $dailyMax) * $plotH;
                        $huH = ($day['human_out'] / $dailyMax) * $plotH;
                        $yInBase = $chartPadT + $plotH;
                        $yIn = $yInBase - $inH;
                        $yAi = $yIn - $aiH;
                        $yHu = $yAi - $huH;
                        $showLabel = $i % 5 === 0 || $i === count($daily) - 1;
                        $label = \Illuminate\Support\Carbon::parse($day['day'])->format('M j');
                    @endphp
                    @if($inH > 0)
                        <rect x="{{ $x }}" y="{{ $yIn }}" width="{{ $w }}" height="{{ $inH }}" class="fill-sky-500" />
                    @endif
                    @if($aiH > 0)
                        <rect x="{{ $x }}" y="{{ $yAi }}" width="{{ $w }}" height="{{ $aiH }}" class="fill-emerald-500" />
                    @endif
                    @if($huH > 0)
                        <rect x="{{ $x }}" y="{{ $yHu }}" width="{{ $w }}" height="{{ $huH }}" class="fill-emerald-500" />
                    @endif
                    @if($showLabel)
                        <text x="{{ $x + $w / 2 }}" y="{{ $chartH - 8 }}" text-anchor="middle"
                              class="fill-zinc-500 text-[10px]">{{ $label }}</text>
                    @endif
                @endforeach
            </svg>
        </div>
    </div>

    {{-- PLATFORM MIX + TEAMS TABLE --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
            <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">Platform mix (30d)</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">Which platforms actually generate traffic.</flux:text>
            @if(count($platforms) === 0)
                <div class="text-sm text-zinc-500 py-8 text-center">No message traffic in 30 days.</div>
            @else
                <div class="space-y-3">
                    @foreach($platforms as $p)
                        @php $color = $platformColors[$p['platform']] ?? '#94A3B8'; @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <div class="capitalize text-zinc-800 dark:text-zinc-200">{{ $p['platform'] }}</div>
                                <div class="text-zinc-600 dark:text-zinc-400 tabular-nums">
                                    {{ number_format($p['count']) }} · {{ $p['pct'] }}%
                                </div>
                            </div>
                            <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                <div class="h-full rounded-full" style="width: {{ $p['pct'] }}%; background: {{ $color }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lg:col-span-2 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 bg-white dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">Teams</flux:heading>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ count($teams) }} total · click a header to sort</flux:text>
                </div>
                <flux:select wire:model.live="sort" size="sm" class="w-52">
                    <option value="activity_desc">Most recent activity</option>
                    <option value="signup_desc">Newest signup</option>
                    <option value="signup_asc">Oldest signup</option>
                    <option value="inbound_desc">Most inbound (30d)</option>
                    <option value="ai_desc">Most AI replies (30d)</option>
                    <option value="name_asc">Name (A→Z)</option>
                </flux:select>
            </div>

            <div class="overflow-x-auto" wire:loading.class="opacity-50 pointer-events-none" wire:target="sort">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-zinc-500 border-b border-zinc-200 dark:border-zinc-700">
                            <th class="py-2 pr-3">Team</th>
                            <th class="py-2 pr-3">Health</th>
                            <th class="py-2 pr-3">Platforms</th>
                            <th class="py-2 pr-3">AI cfg</th>
                            <th class="py-2 pr-3 text-right">In 30d</th>
                            <th class="py-2 pr-3 text-right">AI out</th>
                            <th class="py-2 pr-3 text-right">Human</th>
                            <th class="py-2 pr-3 text-right">AI %</th>
                            <th class="py-2 pr-3 text-right">Last seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($teams as $t)
                            @php $badge = $healthStyles[$t['health']]; @endphp
                            <tr>
                                <td class="py-2 pr-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $t['name'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $t['owner_email'] }} · {{ $t['days_since_signup'] }}d old</div>
                                </td>
                                <td class="py-2 pr-3">
                                    <span class="inline-flex px-2 py-0.5 text-xs rounded-full {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="py-2 pr-3">
                                    @if(count($t['platforms']) === 0)
                                        <span class="text-xs text-zinc-400">—</span>
                                    @else
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($t['platforms'] as $plat)
                                                @php $c = $platformColors[$plat] ?? '#94A3B8'; @endphp
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 capitalize">
                                                    <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $c }}"></span>
                                                    {{ $plat }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="py-2 pr-3">
                                    @if($t['ai_configured'])
                                        <span class="text-emerald-600 dark:text-emerald-400">✓</span>
                                    @else
                                        <span class="text-zinc-400">✗</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-right tabular-nums text-zinc-800 dark:text-zinc-200">{{ number_format($t['inbound_30']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ number_format($t['ai_out_30']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ number_format($t['human_out_30']) }}</td>
                                <td class="py-2 pr-3 text-right tabular-nums text-zinc-600 dark:text-zinc-400">{{ $t['ai_share_pct'] }}%</td>
                                <td class="py-2 pr-3 text-right text-zinc-600 dark:text-zinc-400">
                                    {{ $t['days_since_activity'] === null ? 'never' : $t['days_since_activity'] . 'd ago' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-8 text-center text-zinc-500">No teams yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
