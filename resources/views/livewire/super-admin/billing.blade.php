<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl" class="text-zinc-900">Billing — AI Credits</flux:heading>
        <flux:text class="mt-1 text-zinc-600">
            Grant AI credits after confirming a PayPal / bank transfer / WhatsApp payment. Every submit writes to the append-only ledger with you as the actor.
        </flux:text>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4">
            <flux:text class="text-green-800">{{ session('success') }}</flux:text>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
            <flux:text class="text-red-800">{{ session('error') }}</flux:text>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Grant form --}}
        <div class="lg:col-span-2 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <flux:heading size="lg" class="text-zinc-900 mb-4">Grant AI credits</flux:heading>

            <form wire:submit="submit" class="space-y-4">
                {{-- Team selector --}}
                <div>
                    <label for="teamId" class="block text-sm font-medium text-zinc-800 mb-1">Team</label>
                    <select
                        id="teamId"
                        wire:model.live="teamId"
                        class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                    >
                        <option value="">— Pick a team —</option>
                        @foreach($this->teams as $team)
                            <option value="{{ $team->id }}">
                                {{ $team->name }} ({{ $team->subscription_plan ?? 'free' }})
                            </option>
                        @endforeach
                    </select>
                    @error('teamId') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                {{-- Selected team balance panel --}}
                @if($this->selectedBalance)
                    <div class="rounded-lg bg-zinc-50 border border-zinc-200 p-3 text-sm">
                        <div class="font-medium text-zinc-900 mb-1">
                            Current balance for {{ $this->selectedTeam->name }}
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-xs">
                            <div>
                                <div class="text-zinc-500">Monthly</div>
                                <div class="text-base font-semibold text-zinc-900">{{ $this->selectedBalance['monthly'] }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500">Wallet</div>
                                <div class="text-base font-semibold text-zinc-900">{{ $this->selectedBalance['wallet'] }}</div>
                            </div>
                            <div>
                                <div class="text-zinc-500">Total</div>
                                <div class="text-base font-semibold text-emerald-700">{{ $this->selectedBalance['total'] }}</div>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-zinc-500">
                            Plan: <span class="font-medium text-zinc-800">{{ $this->selectedTeam->subscription_plan ?? 'free' }}</span>
                        </div>
                    </div>
                @endif

                {{-- Action radios --}}
                <fieldset>
                    <legend class="block text-sm font-medium text-zinc-800 mb-2">Action</legend>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <label class="flex items-start gap-2 rounded-lg border border-zinc-200 bg-white p-3 cursor-pointer hover:bg-zinc-50">
                            <input type="radio" wire:model.live="action" value="grant_wallet" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                            <span>
                                <span class="block font-medium text-zinc-900">Grant wallet credits</span>
                                <span class="block text-xs text-zinc-500">Never expire. Drained after monthly.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 rounded-lg border border-zinc-200 bg-white p-3 cursor-pointer hover:bg-zinc-50">
                            <input type="radio" wire:model.live="action" value="grant_monthly_bonus" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                            <span>
                                <span class="block font-medium text-zinc-900">Grant monthly bonus</span>
                                <span class="block text-xs text-zinc-500">Resets with billing cycle. Drained first.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 rounded-lg border border-zinc-200 bg-white p-3 cursor-pointer hover:bg-zinc-50">
                            <input type="radio" wire:model.live="action" value="change_plan" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                            <span>
                                <span class="block font-medium text-zinc-900">Change plan</span>
                                <span class="block text-xs text-zinc-500">Audit row only (delta 0).</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 rounded-lg border border-zinc-200 bg-white p-3 cursor-pointer hover:bg-zinc-50">
                            <input type="radio" wire:model.live="action" value="refund" class="mt-0.5 text-emerald-600 focus:ring-emerald-500" />
                            <span>
                                <span class="block font-medium text-zinc-900">Refund credits</span>
                                <span class="block text-xs text-zinc-500">Positive wallet row with reason.</span>
                            </span>
                        </label>
                    </div>
                    @error('action') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </fieldset>

                {{-- Grant-specific fields --}}
                @if(in_array($action, ['grant_wallet', 'grant_monthly_bonus']))
                    <div>
                        <label for="amount" class="block text-sm font-medium text-zinc-800 mb-1">Amount (credits)</label>
                        <input
                            type="number"
                            id="amount"
                            wire:model="amount"
                            min="1"
                            class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                        />
                        @error('amount') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="paymentReference" class="block text-sm font-medium text-zinc-800 mb-1">Payment reference (optional)</label>
                        <input
                            type="text"
                            id="paymentReference"
                            wire:model="paymentReference"
                            placeholder="e.g. PayPal order 1AB-23456789 / Bank ref XYZ"
                            class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                        />
                        @error('paymentReference') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Change-plan field --}}
                @if($action === 'change_plan')
                    <div>
                        <label for="plan" class="block text-sm font-medium text-zinc-800 mb-1">New plan</label>
                        <select
                            id="plan"
                            wire:model="plan"
                            class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                        >
                            @foreach($this->planOptions as $planKey)
                                <option value="{{ $planKey }}">{{ $planKey }}</option>
                            @endforeach
                        </select>
                        @error('plan') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Refund fields --}}
                @if($action === 'refund')
                    <div>
                        <label for="amount" class="block text-sm font-medium text-zinc-800 mb-1">Amount (credits to refund)</label>
                        <input
                            type="number"
                            id="amount"
                            wire:model="amount"
                            min="1"
                            class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                        />
                        @error('amount') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="refundReason" class="block text-sm font-medium text-zinc-800 mb-1">Refund reason</label>
                        <select
                            id="refundReason"
                            wire:model="refundReason"
                            class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                        >
                            <option value="outage">outage</option>
                            <option value="support">support</option>
                            <option value="chargeback">chargeback</option>
                        </select>
                        @error('refundReason') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Note (always available) --}}
                <div>
                    <label for="note" class="block text-sm font-medium text-zinc-800 mb-1">Note (optional)</label>
                    <textarea
                        id="note"
                        wire:model="note"
                        rows="3"
                        placeholder="e.g. PayPal $35 received 2026-10-05, order #..."
                        class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                    ></textarea>
                    @error('note') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        Submit
                    </button>
                </div>
            </form>
        </div>

        {{-- Right column: quick help --}}
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5 text-sm text-zinc-700">
            <div class="font-semibold text-zinc-900 mb-2">What writes to the ledger</div>
            <ul class="list-disc ps-5 space-y-1 text-xs">
                <li><strong>Grant wallet</strong> → reason <code>manual_grant</code>, balance <code>wallet</code>.</li>
                <li><strong>Grant monthly bonus</strong> → reason <code>manual_bonus</code>, balance <code>monthly</code>.</li>
                <li><strong>Change plan</strong> → <code>plan_changed</code>, delta 0 (audit only).</li>
                <li><strong>Refund</strong> → <code>refund_outage</code> / <code>refund_support</code> / <code>refund_chargeback</code>, positive wallet credit.</li>
            </ul>
            <div class="mt-4 text-xs text-zinc-500">
                All entries are append-only. There is no edit / delete — write a compensating entry instead.
            </div>
        </div>
    </div>

    {{-- Ledger table --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="p-4 flex flex-wrap items-end gap-3 border-b border-zinc-200">
            <div class="flex-1 min-w-64">
                <label for="search" class="block text-xs font-medium text-zinc-600 mb-1">Search ledger</label>
                <input
                    type="text"
                    id="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Team name or payment reference…"
                    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 px-3 py-2 text-sm shadow-sm focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 transition"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-600">
                    <tr>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2">Team</th>
                        <th class="px-3 py-2">Actor</th>
                        <th class="px-3 py-2">Reason</th>
                        <th class="px-3 py-2 text-right">Delta</th>
                        <th class="px-3 py-2">Balance</th>
                        <th class="px-3 py-2">Payment ref</th>
                        <th class="px-3 py-2">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white text-sm text-zinc-800">
                    @forelse($this->ledger as $entry)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-zinc-600">
                                {{ $entry->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-3 py-2 font-medium">{{ $entry->team?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-700">{{ $entry->actor?->name ?? 'system' }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $entry->reason }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $entry->delta >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $entry->delta >= 0 ? '+' : '' }}{{ $entry->delta }}
                            </td>
                            <td class="px-3 py-2 text-xs text-zinc-600">{{ $entry->balance_type }}</td>
                            <td class="px-3 py-2 text-xs text-zinc-700">
                                {{ $entry->metadata['payment_reference'] ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-xs text-zinc-700 max-w-xs truncate" title="{{ $entry->metadata['note'] ?? '' }}">
                                {{ $entry->metadata['note'] ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-zinc-500">
                                No ledger entries match this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-zinc-200">
            {{ $this->ledger->links() }}
        </div>
    </div>
</div>
