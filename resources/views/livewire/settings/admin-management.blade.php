<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="text-zinc-900">{{ __('Admin Management') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-900">{{ __('Create and manage admin accounts and their page permissions.') }}</flux:text>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
            {{ __('Add New Admin') }}
        </flux:button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <flux:text class="text-green-700 dark:text-green-400">{{ session('success') }}</flux:text>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <flux:text class="text-red-700 dark:text-red-400">{{ session('error') }}</flux:text>
        </div>
    @endif

    {{-- Head Admin Info --}}
    <div class="mb-6 rounded-xl border border-zinc-200 p-4 bg-zinc-50">
        <div class="flex items-center gap-3">
            <flux:avatar :name="auth()->user()->currentTeam->owner->name" />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="sm" class="!text-zinc-900">{{ auth()->user()->currentTeam->owner->name }}</flux:heading>
                    <flux:badge color="yellow" size="sm">{{ __('Head Admin') }}</flux:badge>
                </div>
                <flux:text size="xs" class="text-zinc-900">{{ auth()->user()->currentTeam->owner->email }} &middot; {{ __('Full access to everything') }}</flux:text>
            </div>
        </div>
    </div>

    {{-- Admin List --}}
    @if($this->teamAdmins->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="users" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <flux:text class="text-zinc-500">{{ __('No admins added yet. Click "Add New Admin" to create one.') }}</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->teamAdmins as $admin)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <flux:avatar :name="$admin->name" />
                            <div class="min-w-0">
                                <flux:heading size="sm" class="!text-zinc-900">{{ $admin->name }}</flux:heading>
                                <flux:text size="xs" class="text-zinc-900">{{ $admin->email }}</flux:text>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @forelse($admin->pivot->permissions as $perm)
                                        <flux:badge color="blue" size="sm">{{ \App\Livewire\Settings\AdminManagement::PERMISSIONS[$perm] ?? $perm }}</flux:badge>
                                    @empty
                                        <flux:badge color="red" size="sm">{{ __('No Permissions') }}</flux:badge>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <flux:button wire:click="openEditModal({{ $admin->id }})" size="sm" variant="ghost" icon="pencil">
                                {{ __('Permissions') }}
                            </flux:button>
                            <flux:button wire:click="openPasswordModal({{ $admin->id }})" size="sm" variant="ghost" icon="key">
                                {{ __('Password') }}
                            </flux:button>
                            <flux:button
                                wire:click="deleteAdmin({{ $admin->id }})"
                                wire:confirm="Delete admin '{{ addslashes($admin->name) }}'? This cannot be undone."
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

    {{-- Create Admin Modal --}}
    <flux:modal wire:model="showCreateModal" class="md:w-[32rem]">
        <div class="space-y-5">
            <flux:heading size="lg">{{ __('Add New Admin') }}</flux:heading>

            <div class="space-y-4">
                <flux:input wire:model="createName" :label="__('Full Name')" placeholder="John Doe" required />
                <flux:input wire:model="createEmail" :label="__('Email Address')" type="email" placeholder="john@example.com" required />
                <flux:input wire:model="createPassword" :label="__('Password')" type="password" :placeholder="__('Minimum 8 characters')" required />

                <div>
                    <flux:label>{{ __('Page Permissions') }}</flux:label>
                    <flux:text size="xs" class="text-zinc-500 mb-3">{{ __('Select which pages this admin can access.') }}</flux:text>
                    <div class="space-y-2">
                        @foreach(\App\Livewire\Settings\AdminManagement::PERMISSIONS as $slug => $label)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="createPermissions"
                                    value="{{ $slug }}"
                                    class="rounded border-zinc-300 dark:border-zinc-600 text-blue-500"
                                />
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @error('createEmail') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror
            @error('createPassword') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="createAdmin" variant="primary">{{ __('Create Admin') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Edit Permissions Modal --}}
    <flux:modal wire:model="showEditModal" class="md:w-[28rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Edit Permissions') }}</flux:heading>
                <flux:text class="mt-1">{{ $editingAdminName }}</flux:text>
            </div>

            <div class="space-y-2">
                @foreach(\App\Livewire\Settings\AdminManagement::PERMISSIONS as $slug => $label)
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="editPermissions"
                            value="{{ $slug }}"
                            class="rounded border-zinc-300 dark:border-zinc-600 text-blue-500"
                        />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showEditModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="savePermissions" variant="primary">{{ __('Save Permissions') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Reset Password Modal --}}
    <flux:modal wire:model="showPasswordModal" class="md:w-[24rem]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Reset Password') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Set a new password for :name.', ['name' => $passwordAdminName]) }}</flux:text>
            </div>

            <flux:input wire:model="newPassword" :label="__('New Password')" type="password" :placeholder="__('Minimum 8 characters')" required />
            @error('newPassword') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showPasswordModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="resetPassword" variant="primary">{{ __('Reset Password') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
