<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Billing & AI credits')" :subheading="__('Your AI credit balance, usage history, and plan.')">
        <div class="space-y-8 w-full max-w-3xl">

            {{-- ══ BALANCE CARD ══ --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:icon name="sparkles" class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                {{ __('Monthly allowance') }}
                            </flux:text>
                        </div>

                        <div class="mt-2 flex items-baseline gap-2">
                            @if($this->isUnlimited)
                                <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100" aria-hidden="true">∞</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('unlimited on this plan') }}</span>
                            @else
                                <span class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 font-mono tabular-nums">
                                    {{ number_format($this->monthlyUsed) }}
                                </span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400 font-mono tabular-nums">
                                    / {{ number_format($this->monthlyQuota) }}
                                </span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('used') }}
                                </span>
                            @endif
                        </div>

                        @unless($this->isUnlimited)
                            <div class="mt-3 h-2 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                                @php
                                    $barFill = $this->pctUsed > 85
                                        ? 'bg-red-500 dark:bg-red-400'
                                        : ($this->pctUsed >= 60 ? 'bg-amber-500 dark:bg-amber-400' : 'bg-emerald-500 dark:bg-emerald-400');
                                @endphp
                                <div class="h-2 {{ $barFill }}" style="width: {{ $this->pctUsed }}%;"></div>
                            </div>
                        @endunless

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                            @unless($this->isUnlimited)
                                <span>{{ __('Resets in :days days', ['days' => $this->daysToReset]) }}</span>
                            @endunless
                            @if($this->balance->wallet > 0)
                                <span class="inline-flex items-center gap-1">
                                    <flux:icon name="wallet" class="size-3.5" />
                                    {{ __(':amount wallet credits', ['amount' => number_format($this->balance->wallet)]) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col items-stretch sm:items-end gap-2 shrink-0">
                        <flux:badge color="emerald" size="sm">
                            {{ $this->plans[$this->currentPlan]['name'] ?? __('Free') }}
                        </flux:badge>
                        <a
                            href="{{ route('settings.billing.top-up') }}"
                            wire:navigate.hover
                            data-test="billing-top-up-link"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 text-xs font-semibold transition-colors"
                        >
                            <flux:icon name="arrow-up-right" class="size-3.5" />
                            {{ __('Top up credits') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- ══ LEDGER ══ --}}
            <div>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <flux:heading size="sm">{{ __('Usage history') }}</flux:heading>

                    <div class="flex items-center gap-2">
                        <label for="ledger-range" class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Range') }}
                        </label>
                        <select
                            id="ledger-range"
                            wire:model.live="rangeDays"
                            data-test="ledger-range-filter"
                            class="rounded-lg border border-zinc-200 bg-white px-2 py-1 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                        >
                            <option value="7">{{ __('Last 7 days') }}</option>
                            <option value="30">{{ __('Last 30 days') }}</option>
                            <option value="90">{{ __('Last 90 days') }}</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden">
                    @if($this->ledger->isEmpty())
                        <div class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No credit activity in this period.') }}
                        </div>
                    @else
                        @php $running = $this->balance->total(); @endphp
                        <table class="w-full text-sm" data-test="ledger-table">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                                <tr class="border-b border-zinc-100 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    <th class="px-4 py-2.5 text-start font-semibold">{{ __('Date') }}</th>
                                    <th class="px-4 py-2.5 text-start font-semibold">{{ __('Action') }}</th>
                                    <th class="px-4 py-2.5 text-end font-semibold">{{ __('Change') }}</th>
                                    <th class="px-4 py-2.5 text-start font-semibold hidden sm:table-cell">{{ __('Note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->ledger as $entry)
                                    @php
                                        $sign = $entry->delta >= 0 ? '+' : '−';
                                        $amount = abs((int) $entry->delta);
                                        $deltaClass = $entry->delta >= 0
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-zinc-700 dark:text-zinc-300';
                                        $note = $entry->metadata['payment_reference']
                                            ?? ($entry->metadata['note']
                                                ?? ($entry->metadata['plan'] ?? ''));
                                    @endphp
                                    <tr class="border-b border-zinc-50 dark:border-zinc-700/40 last:border-0">
                                        <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                            {{ $entry->created_at->translatedFormat('M j, Y H:i') }}
                                        </td>
                                        <td class="px-4 py-2.5 text-zinc-800 dark:text-zinc-100">
                                            {{ $this->reasonLabel($entry->reason) }}
                                            <span class="ms-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wider
                                                {{ $entry->balance_type === 'wallet'
                                                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200'
                                                    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700/40 dark:text-zinc-300' }}">
                                                {{ __($entry->balance_type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-end font-mono tabular-nums font-semibold {{ $deltaClass }}">
                                            {{ $sign }}{{ number_format($amount) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400 hidden sm:table-cell max-w-[220px] truncate" dir="auto" title="{{ $note }}">
                                            {{ $note }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if($this->ledger->hasPages())
                            <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700">
                                {{ $this->ledger->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- ══ PLAN SUMMARY ══ --}}
            <div>
                <flux:heading size="sm">{{ __('Current Plan') }}</flux:heading>
                <div class="mt-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $this->plans[$this->currentPlan]['name'] ?? __('Free') }}
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            @if($this->isUnlimited)
                                {{ __('Unlimited AI credits / month') }}
                            @else
                                {{ __(':n AI credits / month', ['n' => number_format($this->monthlyQuota)]) }}
                            @endif
                        </div>
                    </div>
                    <a
                        href="{{ route('settings.billing.top-up') }}"
                        wire:navigate.hover
                        class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 px-3 py-1.5 text-xs font-semibold transition-colors dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50"
                    >
                        {{ __('Upgrade') }}
                    </a>
                </div>
            </div>

        </div>
    </x-settings.layout>
</section>
