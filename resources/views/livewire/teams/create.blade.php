<div class="flex flex-col gap-6">
    <div class="text-center">
        <flux:heading size="xl">Create Your Team</flux:heading>
        <flux:text class="mt-2">Set up your team to start managing your social inbox.</flux:text>
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

        <flux:button variant="primary" type="submit" class="w-full">
            Create Team
        </flux:button>
    </form>
</div>
