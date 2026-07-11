<div class="settings-layout flex items-start max-md:flex-col">
    {{-- Scoped: force readable dark text across all Flux controls, labels,
         nav items, headings, and user input inside the settings pages. The
         primary "Save" button is deliberately left alone so its white-on-
         violet contrast stays intact. --}}
    <style>
        .settings-layout [data-flux-navlist-item],
        .settings-layout [data-flux-navlist-item] * { color: rgb(9,9,11) !important; }
        .settings-layout [data-flux-heading],
        .settings-layout [data-flux-subheading] { color: rgb(9,9,11) !important; }
        .settings-layout [data-flux-label] { color: rgb(9,9,11) !important; }
        .settings-layout [data-flux-control]:not([type="submit"]):not(button) { color: rgb(9,9,11) !important; }
        .settings-layout input,
        .settings-layout textarea,
        .settings-layout select { color: rgb(9,9,11) !important; }
        .settings-layout input::placeholder,
        .settings-layout textarea::placeholder { color: rgb(113,113,122) !important; }
    </style>
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('user-password.edit')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <flux:navlist.item :href="route('two-factor.show')" wire:navigate>{{ __('Two-Factor Auth') }}</flux:navlist.item>
            @endif
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.billing')" wire:navigate>{{ __('Billing') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading class="!text-zinc-900">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading class="!text-zinc-900">{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
