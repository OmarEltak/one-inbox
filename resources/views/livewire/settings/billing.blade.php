<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Billing')" :subheading="__('Manage your subscription and billing details')">
        <div class="space-y-8 w-full max-w-2xl">

            {{-- Checkout feedback --}}
            @if(request('checkout') === 'success')
                <flux:callout variant="success" icon="check-circle">
                    <flux:callout.heading>{{ __('Subscription activated!') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Your plan has been upgraded successfully.') }}</flux:callout.text>
                </flux:callout>
            @elseif(request('checkout') === 'cancelled')
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>{{ __('Checkout cancelled') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Your subscription was not changed.') }}</flux:callout.text>
                </flux:callout>
            @endif

            {{-- Current Plan --}}
            <div>
                <flux:heading size="sm">{{ __('Current Plan') }}</flux:heading>
                <div class="mt-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                {{ $this->plans[$this->currentPlan]['name'] ?? 'Free' }}
                            </span>
                            @if($this->currentPlan !== 'free')
                                <span class="ml-2 text-sm text-zinc-500">${{ $this->plans[$this->currentPlan]['price'] ?? 0 }}/mo</span>
                            @endif
                        </div>
                        @if($this->currentPlan !== 'free' && $this->team?->hasStripeId())
                            <flux:button wire:click="manageSubscription" variant="outline" size="sm">
                                {{ __('Manage Subscription') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Usage --}}
            <div>
                <flux:heading size="sm">{{ __('Usage') }}</flux:heading>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    {{-- AI Credits --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('AI Credits') }}</flux:text>
                            <flux:icon name="sparkles" class="w-4 h-4 text-purple-500" />
                        </div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ number_format($this->usage['ai_credits_used']) }}
                            <span class="text-sm font-normal text-zinc-400">
                                / {{ $this->usage['ai_credits_limit'] === -1 ? 'Unlimited' : number_format($this->usage['ai_credits_limit']) }}
                            </span>
                        </div>
                        @if($this->usage['ai_credits_limit'] > 0)
                            @php $pct = min(100, ($this->usage['ai_credits_used'] / $this->usage['ai_credits_limit']) * 100); @endphp
                            <div class="mt-2 h-2 rounded-full bg-zinc-100 dark:bg-zinc-700">
                                <div class="h-2 rounded-full {{ $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-yellow-500' : 'bg-purple-500') }}" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </div>

                    {{-- Connected Pages --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-sm font-medium text-zinc-500">{{ __('Connected Pages') }}</flux:text>
                            <flux:icon name="link" class="w-4 h-4 text-blue-500" />
                        </div>
                        <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $this->usage['pages_used'] }}
                            <span class="text-sm font-normal text-zinc-400">
                                / {{ $this->usage['pages_limit'] === -1 ? 'Unlimited' : $this->usage['pages_limit'] }}
                            </span>
                        </div>
                        @if($this->usage['pages_limit'] > 0)
                            @php $pagePct = min(100, ($this->usage['pages_used'] / $this->usage['pages_limit']) * 100); @endphp
                            <div class="mt-2 h-2 rounded-full bg-zinc-100 dark:bg-zinc-700">
                                <div class="h-2 rounded-full {{ $pagePct >= 100 ? 'bg-red-500' : ($pagePct > 70 ? 'bg-yellow-500' : 'bg-blue-500') }}" style="width: {{ $pagePct }}%"></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Available Plans --}}
            <div>
                <flux:heading size="sm">{{ __('Available Plans') }}</flux:heading>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    @foreach($this->plans as $key => $plan)
                        <div class="rounded-xl border p-5 {{ $this->currentPlan === $key
                            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                            : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' }}">
                            <div class="mb-3">
                                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $plan['name'] }}</span>
                                <div class="mt-1">
                                    <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">${{ $plan['price'] }}</span>
                                    @if($plan['price'] > 0)
                                        <span class="text-sm text-zinc-500">/mo</span>
                                    @endif
                                </div>
                            </div>

                            <ul class="space-y-1.5 text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                                <li class="flex items-center gap-2">
                                    <flux:icon name="check" class="w-4 h-4 text-green-500 flex-shrink-0" />
                                    {{ $plan['ai_credits'] === -1 ? 'Unlimited' : number_format($plan['ai_credits']) }} AI credits/mo
                                </li>
                                <li class="flex items-center gap-2">
                                    <flux:icon name="check" class="w-4 h-4 text-green-500 flex-shrink-0" />
                                    {{ $plan['pages'] === -1 ? 'Unlimited' : $plan['pages'] }} connected {{ Str::plural('page', $plan['pages'] === -1 ? 2 : $plan['pages']) }}
                                </li>
                            </ul>

                            @if($this->currentPlan === $key)
                                <flux:badge variant="solid" color="purple" size="sm">{{ __('Current Plan') }}</flux:badge>
                            @elseif($key === 'free')
                                {{-- Can't downgrade to free from here, use Stripe portal --}}
                            @elseif($plan['price_id'])
                                @if($this->currentPlan === 'free')
                                    <flux:button wire:click="subscribe('{{ $key }}')" variant="primary" size="sm" class="w-full">
                                        {{ __('Upgrade') }}
                                    </flux:button>
                                @else
                                    <flux:button wire:click="manageSubscription" variant="outline" size="sm" class="w-full">
                                        {{ __('Change Plan') }}
                                    </flux:button>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Invoice History --}}
            @if($this->invoices->isNotEmpty())
                <div>
                    <flux:heading size="sm">{{ __('Invoice History') }}</flux:heading>
                    <div class="mt-3 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                    <th class="px-4 py-3 text-left font-medium text-zinc-500">{{ __('Date') }}</th>
                                    <th class="px-4 py-3 text-left font-medium text-zinc-500">{{ __('Amount') }}</th>
                                    <th class="px-4 py-3 text-right font-medium text-zinc-500">{{ __('Invoice') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->invoices as $invoice)
                                    <tr class="border-b border-zinc-50 dark:border-zinc-700/50 last:border-0">
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $invoice->date()->toFormattedDateString() }}</td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $invoice->total() }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ $invoice->invoicePdf() }}" target="_blank" class="text-purple-600 hover:text-purple-800 dark:text-purple-400">
                                                {{ __('Download') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </x-settings.layout>
</section>
