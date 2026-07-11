<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        {{-- Force a permanent violet border + ring around Name and Email
             inputs, in every state (default, hover, focus). --}}
        <style>
            .profile-form input,
            .profile-form input[data-flux-control],
            .profile-form input:hover,
            .profile-form input:focus,
            .profile-form input:focus-visible {
                border-color: rgb(139 92 246) !important;   /* violet-500 */
                box-shadow: 0 0 0 2px rgb(139 92 246) !important;
                outline: none !important;
                --tw-ring-shadow: 0 0 0 2px rgb(139 92 246) !important;
                --tw-ring-color: rgb(139 92 246) !important;
                --tw-ring-offset-shadow: 0 0 #0000 !important;
            }
        </style>
        <form wire:submit="updateProfileInformation" class="profile-form my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @php
            $team = auth()->user()?->currentTeam;
            $plan = $team?->subscription_plan ?? 'free';
            $planLabels = ['free' => __('Free'), 'starter' => __('Starter — $29/mo'), 'pro' => __('Pro — $79/mo'), 'enterprise' => __('Enterprise')];
            $planColors = [
                'enterprise' => 'bg-purple-100 text-purple-700 border-purple-200',
                'pro'        => 'bg-blue-100 text-blue-700 border-blue-200',
                'starter'    => 'bg-green-100 text-green-700 border-green-200',
                'free'       => 'bg-zinc-100 text-zinc-600 border-zinc-200',
            ];
        @endphp
        <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-zinc-900">{{ __('Subscription') }}</p>
                <p class="text-xs text-zinc-500 mt-0.5">{{ $planLabels[$plan] ?? ucfirst($plan) }}</p>
            </div>
            <a href="{{ route('settings.billing') }}" wire:navigate
               class="text-xs font-medium text-zinc-600 hover:text-zinc-900 underline underline-offset-2 transition-colors">
                {{ __('Manage') }}
            </a>
        </div>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-settings.layout>
</section>
