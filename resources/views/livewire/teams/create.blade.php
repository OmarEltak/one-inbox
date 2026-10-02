<div class="flex flex-col gap-6">
    <div class="text-center">
        <flux:heading size="xl">{{ __('Create Your Team') }}</flux:heading>
        <flux:text class="mt-2">{{ __('Set up your team to start managing your social inbox.') }}</flux:text>
    </div>

    <form wire:submit="createTeam" class="flex flex-col gap-6">
        <flux:input
            wire:model="name"
            :label="__('Team Name')"
            type="text"
            required
            autofocus
            placeholder="e.g. My Company"
        />

        <div class="text-center text-xs text-zinc-500">
            {{ __('By creating a team, you agree to our') }}
            <a href="{{ route('terms') }}" class="text-indigo-600 hover:underline">{{ __('Terms of Service') }}</a>
            {{ __('and') }}
            <a href="{{ route('privacy') }}" class="text-indigo-600 hover:underline">{{ __('Privacy Policy') }}</a>.
        </div>

        <flux:button variant="primary" type="submit" class="w-full">
            {{ __('Create Team') }}
        </flux:button>
    </form>
</div>
