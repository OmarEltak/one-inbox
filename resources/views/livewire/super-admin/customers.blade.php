<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">Customers</flux:heading>
            <flux:text class="mt-1">Provision a company workspace and the login the customer will use.</flux:text>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
            New Customer
        </flux:button>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <flux:text class="text-green-700 dark:text-green-400">{{ session('success') }}</flux:text>
        </div>
    @endif

    @if($this->customers->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="building-office-2" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No customers yet. Click "New Customer" to provision the first one.</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->customers as $team)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm">{{ $team->name }}</flux:heading>
                                <flux:badge color="zinc" size="sm">{{ $team->pages_count }} pages</flux:badge>
                                <flux:badge color="zinc" size="sm">{{ $team->members_count }} users</flux:badge>
                            </div>
                            @if($team->owner)
                                <flux:text size="xs" class="mt-1">
                                    Owner: {{ $team->owner->name }} &middot; {{ $team->owner->email }}
                                </flux:text>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($team->owner)
                                <flux:button wire:click="openPasswordModal({{ $team->owner->id }})" size="sm" variant="ghost" icon="key">
                                    Reset Password
                                </flux:button>
                            @endif
                            <flux:button
                                wire:click="deleteCustomer({{ $team->id }})"
                                wire:confirm="Delete customer '{{ addslashes($team->name) }}' and its owner? This cannot be undone."
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                class="text-red-500 hover:text-red-600"
                            />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showCreateModal" class="md:w-[32rem]">
        <div class="space-y-5">
            <flux:heading size="lg">New Customer</flux:heading>
            <flux:text>Creates a workspace and a login for the customer. They will sign in with the email and password you set below.</flux:text>

            <div class="space-y-4">
                <flux:input wire:model="companyName" label="Company Name" placeholder="Acme Corp" required />
                <flux:input wire:model="ownerName" label="Owner Full Name" placeholder="John Doe" required />
                <flux:input wire:model="ownerEmail" label="Owner Email" type="email" placeholder="john@acme.com" required />
                <flux:input wire:model="ownerPassword" label="Password" type="password" placeholder="Minimum 8 characters" required />
            </div>

            @error('companyName') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror
            @error('ownerName') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror
            @error('ownerEmail') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror
            @error('ownerPassword') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="createCustomer" variant="primary">Create Customer</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showPasswordModal" class="md:w-[24rem]">
        <div class="space-y-5">
            <flux:heading size="lg">Reset Password</flux:heading>
            <flux:text>Set a new password for {{ $passwordUserName }}.</flux:text>

            <flux:input wire:model="newPassword" label="New Password" type="password" placeholder="Minimum 8 characters" required />
            @error('newPassword') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showPasswordModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="resetPassword" variant="primary">Reset Password</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
