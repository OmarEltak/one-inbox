<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Top up credits')" :subheading="__('Upgrade your plan or buy a one-off credit pack. Payments are manual — we credit your account within an hour of confirming.')">
        <div class="space-y-8 w-full max-w-3xl">

            {{-- ══ 1. BALANCE CARD (mirrors Phase B) ══ --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <flux:icon name="sparkles" class="size-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <flux:text class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Your current balance') }}
                    </flux:text>
                </div>

                <div class="mt-2 text-sm text-zinc-700 dark:text-zinc-200" data-test="topup-balance">
                    @if($this->isUnlimited)
                        <span class="font-semibold">{{ __('Unlimited on this plan') }}</span>
                        @if($this->balance->wallet > 0)
                            · {{ __(':amount wallet credits', ['amount' => number_format($this->balance->wallet)]) }}
                        @endif
                    @else
                        <span class="font-semibold font-mono tabular-nums">{{ number_format($this->balance->monthly) }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400 font-mono tabular-nums">/ {{ number_format($this->monthlyQuota) }}</span>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('monthly left') }}</span>
                        @if($this->balance->wallet > 0)
                            · <span class="font-semibold font-mono tabular-nums">{{ number_format($this->balance->wallet) }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('wallet credits') }}</span>
                        @endif
                        <span class="block mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Resets in :days days', ['days' => $this->daysToReset]) }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- ══ 2. PLANS TABLE ══ --}}
            <div>
                <flux:heading size="sm">{{ __('Plans') }}</flux:heading>
                <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="w-full text-sm" data-test="topup-plans-table">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                            <tr class="border-b border-zinc-100 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <th class="px-4 py-2.5 text-start font-semibold">{{ __('Plan') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold">{{ __('AI credits / month') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold hidden md:table-cell">{{ __('Facebook / Instagram pages') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold hidden md:table-cell">{{ __('WhatsApp Business API numbers') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold hidden md:table-cell">{{ __('Bulk campaigns / month') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold hidden lg:table-cell">{{ __('Contacts stored') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold">{{ __('Price') }}</th>
                                <th class="px-4 py-2.5 text-end font-semibold">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->plans as $slug => $plan)
                                @php
                                    $isCurrent = $slug === $this->currentPlan;
                                    $planLabel = ($plan['name'] ?? ucfirst($slug)) . ' plan';
                                    $credits = (int) ($plan['ai_credits'] ?? 0);
                                    $limits = (array) ($plan['limits'] ?? []);
                                    $pages = (int) ($limits['pages'] ?? ($plan['pages'] ?? 0));
                                    $waNumbers = (int) ($limits['whatsapp_numbers'] ?? 0);
                                    $campaignsPerMonth = (int) ($limits['bulk_campaigns_monthly'] ?? 0);
                                    $contactsStored = (int) ($limits['contacts_stored'] ?? 0);
                                @endphp
                                <tr class="border-b border-zinc-50 dark:border-zinc-700/40 last:border-0" data-test="plan-row-{{ $slug }}">
                                    <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100 font-semibold">
                                        {{ $plan['name'] ?? ucfirst($slug) }}
                                        @if($isCurrent)
                                            <flux:badge color="emerald" size="sm" class="ms-2">{{ __('Current plan') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end font-mono tabular-nums text-zinc-700 dark:text-zinc-200">
                                        {{ number_format($credits) }}
                                    </td>
                                    <td class="px-4 py-3 text-end font-mono tabular-nums text-zinc-700 dark:text-zinc-200 hidden md:table-cell">
                                        {{ number_format($pages) }}
                                    </td>
                                    <td class="px-4 py-3 text-end font-mono tabular-nums text-zinc-700 dark:text-zinc-200 hidden md:table-cell">
                                        {{ number_format($waNumbers) }}
                                    </td>
                                    <td class="px-4 py-3 text-end font-mono tabular-nums text-zinc-700 dark:text-zinc-200 hidden md:table-cell">
                                        {{ number_format($campaignsPerMonth) }}
                                    </td>
                                    <td class="px-4 py-3 text-end font-mono tabular-nums text-zinc-700 dark:text-zinc-200 hidden lg:table-cell">
                                        {{ number_format($contactsStored) }}
                                    </td>
                                    <td class="px-4 py-3 text-end font-mono tabular-nums text-zinc-900 dark:text-zinc-100 font-semibold">
                                        ${{ number_format((int) ($plan['price'] ?? 0)) }}
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 font-sans font-normal">{{ __('/ mo') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        @if($isCurrent)
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 bg-zinc-100 text-zinc-500 px-3 py-1.5 text-xs font-semibold cursor-not-allowed dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500" aria-disabled="true">
                                                {{ __('On this plan') }}
                                            </span>
                                        @else
                                            <a
                                                href="{{ $this->waLink('plan:' . $slug, $planLabel) }}"
                                                target="_blank"
                                                rel="noopener"
                                                data-test="plan-wa-{{ $slug }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 text-xs font-semibold transition-colors"
                                            >
                                                <flux:icon name="chat-bubble-left-right" class="size-3.5" />
                                                {{ __('Chat with us about this plan') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ══ 3. CREDIT PACKS ══ --}}
            <div>
                <flux:heading size="sm">{{ __('Credit packs') }}</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('One-off top-ups. Credits land in your wallet and never expire.') }}
                </flux:text>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3" data-test="topup-packs">
                    @foreach($this->packs as $slug => $pack)
                        @php
                            $packLabel = ($pack['name'] ?? ucfirst($slug)) . ' pack';
                            $credits = (int) ($pack['credits'] ?? 0);
                            $price = (int) ($pack['price'] ?? 0);
                            $perCredit = $credits > 0 ? number_format($price / $credits, 3) : '0.000';
                        @endphp
                        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $pack['name'] ?? ucfirst($slug) }}
                                </span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono tabular-nums">
                                    ${{ $perCredit }} {{ __('per credit') }}
                                </span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 font-mono tabular-nums">
                                    {{ number_format($credits) }}
                                </span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('credits') }}</span>
                            </div>
                            <div class="flex items-baseline gap-1 text-sm text-zinc-700 dark:text-zinc-200">
                                <span class="font-semibold">${{ number_format($price) }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('USD') }}</span>
                            </div>
                            <a
                                href="{{ $this->waLink('pack:' . $slug, $packLabel) }}"
                                target="_blank"
                                rel="noopener"
                                data-test="pack-wa-{{ $slug }}"
                                class="mt-2 inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 text-xs font-semibold transition-colors"
                            >
                                <flux:icon name="chat-bubble-left-right" class="size-3.5" />
                                {{ __('Chat with us about this pack') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ══ 4. PAYMENT METHODS (contrast-safe emerald) ══ --}}
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100">
                <div class="flex items-center gap-2 mb-3">
                    <flux:icon name="credit-card" class="size-4 text-emerald-700 dark:text-emerald-300" />
                    <h3 class="text-sm font-semibold uppercase tracking-wider">
                        {{ __('How to pay') }}
                    </h3>
                </div>

                <ul class="space-y-4 text-sm">
                    {{-- PayPal --}}
                    <li>
                        <div class="font-semibold mb-1">{{ __('PayPal') }}</div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-emerald-900/80 dark:text-emerald-100/80">
                                {{ __('Send to') }}
                            </span>
                            <a
                                href="{{ $this->payment['paypal_link'] ?? '#' }}"
                                target="_blank"
                                rel="noopener"
                                class="font-mono text-xs break-all underline hover:no-underline"
                                data-test="paypal-link"
                            >{{ $this->payment['paypal_link'] ?? '' }}</a>
                            <button
                                type="button"
                                x-data
                                x-on:click="navigator.clipboard.writeText(@js($this->payment['paypal_link'] ?? ''))"
                                class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-white/60 px-2 py-1 text-[11px] font-semibold text-emerald-900 hover:bg-white dark:border-emerald-900/60 dark:bg-emerald-900/20 dark:text-emerald-100 dark:hover:bg-emerald-900/40 transition-colors"
                            >
                                <flux:icon name="clipboard" class="size-3" />
                                {{ __('Copy') }}
                            </button>
                        </div>
                    </li>

                    {{-- Bank transfer --}}
                    <li>
                        <div class="font-semibold mb-1">{{ __('Bank transfer') }}</div>
                        @if(! empty($this->payment['bank_iban']))
                            <div class="text-xs">
                                <span class="text-emerald-900/70 dark:text-emerald-100/70">{{ __('IBAN:') }}</span>
                                <span class="font-mono break-all">{{ $this->payment['bank_iban'] }}</span>
                            </div>
                            @if(! empty($this->payment['bank_beneficiary']))
                                <div class="text-xs mt-1">
                                    <span class="text-emerald-900/70 dark:text-emerald-100/70">{{ __('Beneficiary:') }}</span>
                                    {{ $this->payment['bank_beneficiary'] }}
                                </div>
                            @endif
                            <div class="mt-2 text-xs">
                                <strong>{{ __('Include your team ID (:id) in the transfer reference.', ['id' => $this->team?->id ?? '—']) }}</strong>
                            </div>
                        @else
                            <div class="text-xs text-emerald-900/80 dark:text-emerald-100/80">
                                {{ __('Message the founder on WhatsApp to request bank details.') }}
                            </div>
                        @endif
                    </li>

                    {{-- WhatsApp --}}
                    <li>
                        <div class="font-semibold mb-1">{{ __('Chat with the founder') }}</div>
                        @php
                            $whatsapp = (string) ($this->payment['whatsapp'] ?? '');
                            $teamId = $this->team?->id ?? '';
                            $greeting = __('Hi Omar, this is team #:id and I would like to top up AI credits.', ['id' => $teamId]);
                            $greetingHref = 'https://wa.me/' . rawurlencode($whatsapp) . '?text=' . rawurlencode($greeting);
                        @endphp
                        <a
                            href="{{ $greetingHref }}"
                            target="_blank"
                            rel="noopener"
                            data-test="founder-whatsapp"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 text-xs font-semibold transition-colors"
                        >
                            <flux:icon name="chat-bubble-left-right" class="size-3.5" />
                            {{ __('Message founder on WhatsApp') }}
                            <span class="font-mono text-[11px] opacity-90">+{{ $whatsapp }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- ══ 5. NOTIFY FOUNDER PANEL ══ --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm">{{ __("I've sent payment — notify the founder") }}</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Pick what you paid for and we will credit your account within an hour of confirming.') }}
                </flux:text>

                @if(session('topup_success'))
                    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 px-3 py-2 text-sm dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-100 flex items-start gap-2" data-test="topup-success-flash">
                        <flux:icon name="check-circle" class="size-4 mt-0.5 shrink-0" />
                        <span>{{ session('topup_success') }}</span>
                    </div>
                @endif

                @if(session('topup_warning'))
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 px-3 py-2 text-sm dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100 flex items-start gap-2" data-test="topup-warning-flash">
                        <flux:icon name="exclamation-triangle" class="size-4 mt-0.5 shrink-0" />
                        <span>{{ session('topup_warning') }}</span>
                    </div>
                @endif

                <form wire:submit="notify" class="mt-4 space-y-3">
                    <div>
                        <label for="topup-product" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-200 mb-1">
                            {{ __('Which plan or pack did you buy?') }}
                        </label>
                        <select
                            id="topup-product"
                            wire:model="selectedProduct"
                            data-test="topup-product-select"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">{{ __('— Select a plan or pack —') }}</option>
                            @foreach($this->productOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('selectedProduct')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" data-test="topup-product-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        data-test="topup-notify-button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-semibold transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled"
                        wire:target="notify"
                    >
                        <flux:icon name="paper-airplane" class="size-4" />
                        <span wire:loading.remove wire:target="notify">{{ __('Notify founder') }}</span>
                        <span wire:loading wire:target="notify">{{ __('Sending…') }}</span>
                    </button>
                </form>
            </div>

            <div>
                <a href="{{ route('settings.billing') }}" wire:navigate class="text-xs text-zinc-500 dark:text-zinc-400 hover:underline">
                    {{ __('Back to billing') }}
                </a>
            </div>

        </div>
    </x-settings.layout>
</section>
