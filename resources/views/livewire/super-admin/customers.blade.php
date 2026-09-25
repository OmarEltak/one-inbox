<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="text-zinc-900">{{ __('Customers') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-900">{{ __('Provision a company workspace and the login the customer will use.') }}</flux:text>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
            {{ __('New Customer') }}
        </flux:button>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <p class="text-sm font-medium text-green-900 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if($this->customers->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 p-12 text-center">
            <flux:icon name="building-office-2" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" />
            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('No customers yet. Click "New Customer" to provision the first one.') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->customers as $team)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm" class="text-zinc-900">{{ $team->name }}</flux:heading>
                                <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-900 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700">{{ $team->pages_count }} {{ __('pages') }}</span>
                                <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-900 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700">{{ $team->members_count }} {{ __('users') }}</span>
                            </div>
                            @if($team->owner)
                                <flux:text size="xs" class="mt-1 text-zinc-900">
                                    {{ __('Owner') }}: {{ $team->owner->name }} &middot; {{ $team->owner->email }}
                                </flux:text>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <flux:button
                                wire:click="openPagesModal({{ $team->id }})"
                                size="sm"
                                icon="eye"
                                class="bg-blue-600 hover:bg-blue-700 text-white border-blue-600"
                            >
                                {{ __('View Pages') }}
                            </flux:button>
                            @if($team->owner)
                                <flux:button wire:click="openPasswordModal({{ $team->owner->id }})" size="sm" variant="outline" icon="key">
                                    {{ __('Reset Password') }}
                                </flux:button>
                            @endif
                            <flux:button
                                wire:click="deleteCustomer({{ $team->id }})"
                                wire:confirm="Delete customer '{{ addslashes($team->name) }}' and its owner? This cannot be undone."
                                size="sm"
                                variant="outline"
                                icon="trash"
                                class="text-red-700 hover:text-red-800 border-red-300 hover:bg-red-50"
                            />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showCreateModal" class="md:w-[32rem]">
        <div class="space-y-5">
            <flux:heading size="lg">{{ __('New Customer') }}</flux:heading>
            <flux:text>{{ __('Creates a workspace and a login for the customer. They will sign in with the email and password you set below.') }}</flux:text>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-900 mb-1">{{ __('Company Name') }}</label>
                    <input type="text" wire:model="companyName" placeholder="{{ __('Acme Corp') }}" required
                        class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-900 mb-1">{{ __('Owner Full Name') }}</label>
                    <input type="text" wire:model="ownerName" placeholder="{{ __('John Doe') }}" required
                        class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-900 mb-1">{{ __('Owner Email') }}</label>
                    <input type="email" wire:model="ownerEmail" placeholder="{{ __('john@acme.com') }}" required
                        class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-900 mb-1">{{ __('Password') }}</label>
                    <input type="password" wire:model="ownerPassword" placeholder="{{ __('Minimum 8 characters') }}" required
                        class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition" />
                </div>
            </div>

            @error('companyName') <p class="text-red-700 text-xs">{{ $message }}</p> @enderror
            @error('ownerName') <p class="text-red-700 text-xs">{{ $message }}</p> @enderror
            @error('ownerEmail') <p class="text-red-700 text-xs">{{ $message }}</p> @enderror
            @error('ownerPassword') <p class="text-red-700 text-xs">{{ $message }}</p> @enderror

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showCreateModal', false)" variant="outline">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="createCustomer" variant="primary">{{ __('Create Customer') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showPagesModal" class="md:w-[32rem]">
        <div class="space-y-5">
            <flux:heading size="lg">{{ __('Pages for') }} {{ $pagesModalTeamName }}</flux:heading>

            @if(empty($pagesModalPages))
                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('No pages connected yet.') }}</p>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach($pagesModalPages as $page)
                        <div class="flex items-center justify-between gap-3 p-3">
                            <div class="min-w-0">
                                <flux:text class="font-medium text-zinc-900 truncate">{{ $page['name'] ?: __('(Unnamed page)') }}</flux:text>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-900 ring-1 ring-blue-200">{{ ucfirst($page['platform']) }}</span>
                                @if(! $page['is_active'])
                                    <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-900 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700">{{ __('Inactive') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button wire:click="$set('showPagesModal', false)" variant="outline">{{ __('Close') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showPasswordModal" class="md:w-[24rem]">
        <div class="space-y-5">
            <flux:heading size="lg">{{ __('Reset Password') }}</flux:heading>
            <flux:text>{{ __('Set a new password for') }} {{ $passwordUserName }}.</flux:text>

            <div>
                <label class="block text-sm font-medium text-zinc-900 mb-1">{{ __('New Password') }}</label>
                <input type="password" wire:model="newPassword" placeholder="{{ __('Minimum 8 characters') }}" required
                    class="block w-full rounded-lg border border-zinc-300 bg-white text-zinc-900 placeholder:text-zinc-400 px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100 transition" />
            </div>
            @error('newPassword') <p class="text-red-700 text-xs">{{ $message }}</p> @enderror

            <div class="flex gap-2 justify-end">
                <flux:button wire:click="$set('showPasswordModal', false)" variant="outline">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="resetPassword" variant="primary">{{ __('Reset Password') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
