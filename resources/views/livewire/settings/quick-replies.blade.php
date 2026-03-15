<div class="max-w-4xl mx-auto p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Quick Replies</flux:heading>
            <flux:text class="mt-1">Canned responses your team can use in conversations.</flux:text>
        </div>
        <flux:button icon="plus" wire:click="openCreateModal">New Quick Reply</flux:button>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-2">
        @forelse($this->quickReplies as $reply)
            <div class="flex items-start justify-between gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <flux:icon.bolt class="size-4 text-yellow-500 shrink-0" />
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reply->title }}</span>
                    </div>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $reply->content }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openEditModal({{ $reply->id }})">Edit</flux:button>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        class="text-red-500 hover:text-red-600"
                        wire:click="delete({{ $reply->id }})"
                        wire:confirm="Delete '{{ $reply->title }}'?"
                    >Delete</flux:button>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-600">
                <flux:icon.bolt class="mx-auto size-10 text-zinc-300 dark:text-zinc-600" />
                <flux:heading class="mt-3">No quick replies yet</flux:heading>
                <flux:text class="mt-1">Create canned responses to speed up your team's replies.</flux:text>
                <div class="mt-4">
                    <flux:button icon="plus" wire:click="openCreateModal">New Quick Reply</flux:button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit Quick Reply' : 'New Quick Reply' }}</flux:heading>

            <flux:field>
                <flux:label>Title <flux:badge size="sm" variant="outline">Short name</flux:badge></flux:label>
                <flux:input wire:model="title" placeholder="e.g. Greeting, Pricing Info, Thank You" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Content</flux:label>
                <flux:textarea wire:model="content" rows="5" placeholder="The message text that will be inserted..." />
                <flux:error name="content" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" wire:click="$set('showModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">{{ $editingId ? 'Save Changes' : 'Create' }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
