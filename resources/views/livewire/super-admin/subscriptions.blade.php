<div class="p-6">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Subscriptions</flux:heading>
            <flux:text class="mt-1">Grant, extend, or revoke customer plans. Each row is a team — its owner is the paying customer.</flux:text>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <flux:text class="text-green-700 dark:text-green-400">{{ session('success') }}</flux:text>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <flux:text class="text-red-700 dark:text-red-400">{{ session('error') }}</flux:text>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-64">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search by team name, owner name, or email..."
            />
        </div>
        <div class="w-44">
            <flux:select wire:model.live="statusFilter">
                <option value="all">All statuses</option>
                <option value="free">Free tier</option>
                <option value="active">Active paid</option>
                <option value="expiring">Expiring in 7 days</option>
                <option value="expired">Expired</option>
            </flux:select>
        </div>
        <div class="w-44">
            <flux:select wire:model.live="planFilter">
                <option value="all">All plans</option>
                <option value="free">Free</option>
                <option value="basic">Basic</option>
                <option value="starter">Starter</option>
                <option value="pro">Pro</option>
                <option value="business">Business</option>
                <option value="agency">Agency</option>
                <option value="enterprise">Enterprise</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500">
            {{ $this->teams->count() }} team(s)
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if(count($selected) > 0)
        <div class="mb-4 flex items-center justify-between gap-3 rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 px-4 py-2.5">
            <div class="text-sm text-violet-900 dark:text-violet-200">
                <strong>{{ count($selected) }}</strong> team(s) selected
            </div>
            <div class="flex gap-2">
                <flux:button
                    wire:click="bulkResetAiQuota"
                    wire:confirm="Reset AI quota (ai_credits_used → 0) for {{ count($selected) }} team(s)?"
                    size="sm"
                    variant="primary"
                    icon="arrow-path"
                >
                    Reset AI quota
                </flux:button>
                <flux:button wire:click="clearSelection" size="sm" variant="ghost">Clear</flux:button>
            </div>
        </div>
    @endif

    @if($this->teams->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="user-group" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No teams match these filters.</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            @php $allSelected = count($selected) > 0 && count($selected) === $this->teams->count(); @endphp
                            <input
                                type="checkbox"
                                @checked($allSelected)
                                wire:click="{{ $allSelected ? 'clearSelection' : 'selectAllVisible' }}"
                                class="rounded border-zinc-300 dark:border-zinc-600 text-violet-600 focus:ring-violet-500"
                                title="Select all visible"
                            />
                        </th>
                        <th class="text-left px-4 py-3">Team / Owner</th>
                        <th class="text-left px-4 py-3">Plan</th>
                        <th class="text-left px-4 py-3">Expires</th>
                        <th class="text-left px-4 py-3">Usage</th>
                        <th class="text-right px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->teams as $team)
                        @php
                            $plan = $team->subscription_plan ?: 'free';
                            $expired = $team->subscription_ends_at && $team->subscription_ends_at->isPast();
                            $expiringSoon = $team->subscription_ends_at
                                && $team->subscription_ends_at->isFuture()
                                && $team->subscription_ends_at->diffInDays(now()) <= 7;
                            $planColor = match($plan) {
                                'free' => 'zinc',
                                'basic', 'starter' => 'blue',
                                'pro' => 'violet',
                                'business' => 'indigo',
                                'agency' => 'pink',
                                'enterprise' => 'amber',
                                default => 'zinc',
                            };
                            $expiryColor = $expired ? 'red' : ($expiringSoon ? 'amber' : 'green');
                            $pendingPaymentId = $this->pendingPaymentByTeam[$team->id] ?? null;
                        @endphp
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    value="{{ $team->id }}"
                                    wire:model.live="selected"
                                    class="rounded border-zinc-300 dark:border-zinc-600 text-violet-600 focus:ring-violet-500"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $team->name }}</div>
                                <div class="text-xs text-zinc-500 mt-0.5">
                                    {{ $team->owner?->name ?? '—' }}
                                    @if($team->owner?->email)
                                        · <span class="text-zinc-400">{{ $team->owner->email }}</span>
                                    @endif
                                </div>
                                @if($pendingPaymentId)
                                    <a href="{{ route('super-admin.payment-requests') }}" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-amber-700 dark:text-amber-400 hover:underline">
                                        <flux:icon name="banknotes" class="w-3 h-3" />
                                        Pending payment request
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-{{ $planColor }}-100 dark:bg-{{ $planColor }}-900/30 px-2 py-0.5 text-xs font-medium text-{{ $planColor }}-800 dark:text-{{ $planColor }}-300 capitalize">
                                    {{ $plan }}
                                </span>
                                @if($team->billing_cycle)
                                    <div class="text-xs text-zinc-500 mt-1">{{ $team->billing_cycle }}</div>
                                @endif
                                @if($team->subscription_status === 'past_due')
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1">past due</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($team->subscription_ends_at)
                                    <span class="inline-flex items-center rounded-md bg-{{ $expiryColor }}-100 dark:bg-{{ $expiryColor }}-900/30 px-2 py-0.5 text-xs font-medium text-{{ $expiryColor }}-800 dark:text-{{ $expiryColor }}-300">
                                        {{ $expired ? 'expired' : 'active' }}
                                    </span>
                                    <div class="text-xs text-zinc-500 mt-1">
                                        {{ $team->subscription_ends_at->toFormattedDateString() }}
                                        <span class="text-zinc-400">·</span>
                                        {{ $team->subscription_ends_at->diffForHumans() }}
                                    </div>
                                @elseif($plan === 'free')
                                    <span class="text-xs text-zinc-400">—</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                        no expiry
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-0.5">
                                    <div>{{ $team->pages_count }} page(s)</div>
                                    <div>{{ $team->members_count }} member(s)</div>
                                    @if($team->ai_credits_used)
                                        <div>{{ number_format($team->ai_credits_used) }} AI credits used</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <flux:button wire:click="openGrant({{ $team->id }})" size="sm" variant="primary" icon="key">
                                        Grant access
                                    </flux:button>
                                    <flux:button
                                        wire:click="resetAiQuota({{ $team->id }})"
                                        wire:confirm="Reset AI quota (ai_credits_used → 0) for {{ $team->name }}?"
                                        size="sm"
                                        variant="ghost"
                                        icon="arrow-path"
                                    >
                                        Reset AI
                                    </flux:button>
                                    @if($plan !== 'free' || $team->subscription_ends_at)
                                        <flux:button
                                            wire:click="revoke({{ $team->id }})"
                                            wire:confirm="Revoke {{ $team->name }}'s plan and reset to free tier?"
                                            size="sm"
                                            variant="ghost"
                                            icon="x-mark"
                                        >
                                            Revoke
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Grant modal --}}
    @if($grantTeamId)
        @php $grantTeam = \App\Models\Team::find($grantTeamId); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" wire:key="grant-modal-{{ $grantTeamId }}">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5 border border-zinc-200 dark:border-zinc-700">
                <div>
                    <flux:heading size="lg">Grant plan access</flux:heading>
                    <flux:text class="mt-1">
                        {{ $grantTeam?->name }}
                        @if($grantTeam?->owner)
                            · <span class="text-zinc-500">{{ $grantTeam->owner->email }}</span>
                        @endif
                    </flux:text>
                    @if($grantTeam?->subscription_ends_at)
                        <div class="mt-2 text-xs {{ $grantTeam->subscription_ends_at->isPast() ? 'text-red-600' : 'text-zinc-500' }}">
                            Currently: <strong class="capitalize">{{ $grantTeam->subscription_plan }}</strong>
                            · expires {{ $grantTeam->subscription_ends_at->toFormattedDateString() }}
                            ({{ $grantTeam->subscription_ends_at->diffForHumans() }})
                        </div>
                    @endif
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Plan</label>
                        <flux:select wire:model="grantPlan">
                            <option value="basic">Basic — $8/mo</option>
                            <option value="starter">Starter — $29/mo</option>
                            <option value="pro">Pro — $79/mo</option>
                            <option value="business">Business — $199/mo</option>
                            <option value="agency">Agency — $499/mo</option>
                            <option value="enterprise">Enterprise</option>
                        </flux:select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Billing cycle</label>
                        <flux:select wire:model="grantBillingCycle">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </flux:select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Duration</label>
                        <flux:select wire:model.live="grantDuration">
                            <option value="1_month">1 month</option>
                            <option value="3_months">3 months</option>
                            <option value="6_months">6 months</option>
                            <option value="12_months">1 year</option>
                            <option value="custom">Custom end date...</option>
                        </flux:select>
                    </div>

                    @if($grantDuration === 'custom')
                        <div>
                            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Custom end date</label>
                            <flux:input type="date" wire:model="grantCustomDate" min="{{ now()->addDay()->toDateString() }}" />
                        </div>
                    @endif

                    @if($grantTeam?->subscription_ends_at && $grantTeam->subscription_ends_at->isFuture())
                        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-2">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="radio" wire:model="grantMode" value="extend" class="mt-0.5" />
                                <div>
                                    <div class="text-sm font-medium">Extend from current end date</div>
                                    <div class="text-xs text-zinc-500">Adds duration to {{ $grantTeam->subscription_ends_at->toFormattedDateString() }}</div>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="radio" wire:model="grantMode" value="reset" class="mt-0.5" />
                                <div>
                                    <div class="text-sm font-medium">Reset — start duration from today</div>
                                    <div class="text-xs text-zinc-500">Overwrites current end date</div>
                                </div>
                            </label>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button wire:click="closeGrant" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="grant" variant="primary" icon="check">Grant access</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
