<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl" class="text-zinc-900">{{ __('Billing') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600">{{ __('Every team by plan lifecycle. Trials, invoices sent, overdue accounts, and receipts pending verification.') }}</flux:text>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4">
            <flux:text class="text-green-700">{{ session('success') }}</flux:text>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-64">
            <flux:input wire:model.live.debounce.400ms="search" placeholder="Search team, owner, email…" />
        </div>
        <div class="w-56">
            <flux:select wire:model.live="statusFilter">
                <option value="attention">{{ __('Needs attention') }}</option>
                <option value="trial">{{ __('Trial') }}</option>
                <option value="pending_payment">{{ __('Pending payment') }}</option>
                <option value="overdue">{{ __('Overdue') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
                <option value="all">{{ __('All') }}</option>
            </flux:select>
        </div>
        <div class="ml-auto text-sm text-zinc-500">{{ count($this->teams) }} {{ __('teams') }}</div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-3 py-2">{{ __('Team') }}</th>
                    <th class="px-3 py-2">{{ __('Owner') }}</th>
                    <th class="px-3 py-2">{{ __('Plan') }}</th>
                    <th class="px-3 py-2">{{ __('Status') }}</th>
                    <th class="px-3 py-2">{{ __('Trial day') }}</th>
                    <th class="px-3 py-2">{{ __('Payment due') }}</th>
                    <th class="px-3 py-2">{{ __('Receipt?') }}</th>
                    <th class="px-3 py-2">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white text-sm text-zinc-800">
                @forelse($this->teams as $team)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $team->name }}</td>
                        <td class="px-3 py-2 text-zinc-600">
                            {{ $team->owner?->name }}<br>
                            <span class="text-xs text-zinc-400">{{ $team->owner?->email }}</span>
                        </td>
                        <td class="px-3 py-2">{{ $team->subscription_plan ?? '—' }}</td>
                        <td class="px-3 py-2">
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-blue-100 text-blue-700'     => $team->plan_status === 'trial',
                                'bg-amber-100 text-amber-700'   => $team->plan_status === 'pending_payment',
                                'bg-green-100 text-green-700'   => $team->plan_status === 'paid',
                                'bg-red-100 text-red-700'       => $team->plan_status === 'overdue',
                                'bg-zinc-200 text-zinc-700'     => $team->plan_status === 'cancelled',
                            ])>
                                {{ str_replace('_', ' ', $team->plan_status ?? '—') }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            @php $day = $this->daysInTrialFor($team); @endphp
                            {{ $day !== null ? $day . '/' . \App\Services\Billing\PlanLifecycle::TRIAL_DAYS : '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ $team->plan_payment_due_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            @if(isset($this->pendingReceiptByTeam[$team->id]))
                                <a href="{{ route('super-admin.subscriptions') }}" class="text-emerald-600 underline">
                                    {{ __('Review') }}
                                </a>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    wire:click="markPaid({{ $team->id }})"
                                    class="rounded bg-green-600 px-2 py-1 text-xs font-semibold text-white hover:bg-green-700">
                                    {{ __('Mark paid') }}
                                </button>
                                <button
                                    wire:click="resetToTrial({{ $team->id }})"
                                    class="rounded border border-zinc-300 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50">
                                    {{ __('Reset trial') }}
                                </button>
                                <button
                                    wire:click="cancel({{ $team->id }})"
                                    wire:confirm="{{ __('Cancel this team? They will lose AI access.') }}"
                                    class="rounded border border-red-300 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50">
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-6 text-center text-zinc-500">
                            {{ __('No teams match this filter.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-xs text-zinc-500">
        {{ __('Overdue accounts are soft-throttled to') }}
        {{ \App\Services\Billing\PlanLifecycle::OVERDUE_DAILY_MESSAGE_CAP }}
        {{ __('AI messages per UTC day — never fully blocked, per plan.') }}
    </p>
</div>
