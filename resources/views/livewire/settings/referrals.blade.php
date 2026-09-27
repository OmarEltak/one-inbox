<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Referrals') }}</flux:heading>

    <x-settings.layout :heading="__('Referrals')" :subheading="__('Share your code and both sides save.')">
        @php
            $referral = $this->referral;
            $shareUrl = url('/register') . '?ref=' . $referral->code;
            $discountPercent = (float) config('referrals.discount_percent');
            $reciprocalPercent = (float) config('referrals.reciprocal_discount_percent');
        @endphp

        @if (session('referral_success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emer-700">
                {{ session('referral_success') }}
            </div>
        @endif

        <div class="my-6 rounded-xl border border-line bg-cream px-5 py-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-emer-700">{{ __('Your referral code') }}</p>

            <div x-data="{ copied: false, copiedUrl: false }" class="mt-3 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex-1 rounded-lg border border-line bg-white px-4 py-3 font-mono text-lg font-bold tracking-wider text-ink text-center select-all">
                        {{ $referral->code }}
                    </div>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $referral->code }}'); copied=true; setTimeout(()=>copied=false, 2000)"
                        class="rounded-lg bg-emer-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emer-900 transition-colors"
                    >
                        <span x-show="!copied">{{ __('Copy code') }}</span>
                        <span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex-1 rounded-lg border border-line bg-white px-4 py-2 font-mono text-xs text-zinc-700 truncate">
                        {{ $shareUrl }}
                    </div>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $shareUrl }}'); copiedUrl=true; setTimeout(()=>copiedUrl=false, 2000)"
                        class="rounded-lg border border-emer-700 px-4 py-2 text-xs font-semibold text-emer-700 hover:bg-emerald-50 transition-colors"
                    >
                        <span x-show="!copiedUrl">{{ __('Copy link') }}</span>
                        <span x-show="copiedUrl" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>
            </div>

            <p class="mt-4 text-sm text-zinc-700 leading-relaxed">
                {{ __('Share your code — friends get') }} <strong class="text-emer-700">{{ $discountPercent }}%</strong>
                {{ __('off their first paid month, and you get') }} <strong class="text-emer-700">{{ $reciprocalPercent }}%</strong>
                {{ __('off yours. Values pull from config(\'referrals.*\').') }}
            </p>
        </div>

        <div class="my-6 rounded-xl border border-line bg-cream px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-emer-700">{{ __('Redeem a code') }}</p>
            <p class="mt-1 text-xs text-zinc-600">{{ __('Got a code from a friend? Enter it here to claim your discount.') }}</p>

            <form wire:submit="redeem" class="mt-3 flex items-start gap-3">
                <div class="flex-1">
                    <flux:input wire:model="redeemCode" :placeholder="__('e.g. OT1-XY7Q')" />
                </div>
                <flux:button type="submit" variant="primary">{{ __('Redeem') }}</flux:button>
            </form>
        </div>

        <div class="my-6">
            <p class="text-sm font-semibold text-ink mb-3">{{ __('Successful redemptions') }}</p>

            @if ($this->redemptions->isEmpty())
                <div class="rounded-xl border border-line bg-cream px-5 py-4 text-sm text-zinc-600">
                    {{ __('No one has redeemed your code yet. Share it above to get started.') }}
                </div>
            @else
                <div class="rounded-xl border border-line bg-cream overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-white border-b border-line">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 uppercase tracking-wide">{{ __('Team') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 uppercase tracking-wide">{{ __('Redeemed') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-zinc-600 uppercase tracking-wide">{{ __('Your discount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->redemptions as $r)
                                <tr class="border-t border-line">
                                    <td class="px-4 py-2 text-ink">{{ $r->referredTeam?->name ?? __('Unknown') }}</td>
                                    <td class="px-4 py-2 text-zinc-600">{{ $r->redeemed_at?->diffForHumans() }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-emer-700">{{ $r->reciprocal_discount_percent }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
