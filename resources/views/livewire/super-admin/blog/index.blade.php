<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="text-zinc-900">Blog Posts</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Write, schedule, and manage all blog articles.</flux:text>
        </div>
        <a href="{{ route('super-admin.blog.create') }}" wire:navigate>
            <flux:button variant="primary" icon="plus">New Post</flux:button>
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-3">
            <flux:text class="text-green-700 text-sm">{{ session('success') }}</flux:text>
        </div>
    @endif

    @if($posts->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 p-12 text-center">
            <flux:icon name="document-text" class="w-12 h-12 text-zinc-300 mx-auto mb-3" />
            <flux:text class="text-zinc-500">No posts yet. Click "New Post" to write the first one.</flux:text>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 border-b border-zinc-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-zinc-600">Title</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-600">Language</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-600">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-zinc-600">Updated</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach($posts as $post)
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-zinc-900 max-w-xs truncate">
                            {{ $post->title }}
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge size="sm" color="zinc">{{ strtoupper($post->language) }}</flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            @if(is_null($post->published_at))
                                <flux:badge size="sm" color="zinc">Draft</flux:badge>
                            @elseif($post->published_at->isFuture())
                                <flux:badge size="sm" color="amber">Scheduled</flux:badge>
                            @else
                                <flux:badge size="sm" color="green">Published</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">
                            {{ $post->updated_at->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('super-admin.blog.edit', $post) }}" wire:navigate>
                                    <flux:button size="sm" variant="ghost" icon="pencil">Edit</flux:button>
                                </a>
                                <flux:button
                                    wire:click="$set('confirmDeleteId', {{ $post->id }})"
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    class="text-red-500 hover:text-red-600"
                                >Delete</flux:button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Delete confirm modal --}}
    @if($confirmDeleteId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
            <flux:heading size="lg">Delete post?</flux:heading>
            <flux:text class="mt-2 text-zinc-500">This cannot be undone.</flux:text>
            <div class="flex gap-3 mt-5">
                <flux:button wire:click="deletePost({{ $confirmDeleteId }})" variant="danger" class="flex-1">Delete</flux:button>
                <flux:button wire:click="$set('confirmDeleteId', null)" variant="ghost" class="flex-1">Cancel</flux:button>
            </div>
        </div>
    </div>
    @endif
</div>
