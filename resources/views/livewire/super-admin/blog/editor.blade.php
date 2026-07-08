<div class="p-6 max-w-5xl mx-auto text-zinc-900">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('super-admin.blog.index') }}" wire:navigate>
            <flux:button variant="ghost" icon="arrow-left" size="sm" class="text-zinc-900">All Posts</flux:button>
        </a>
        <flux:heading size="xl" class="text-zinc-900">
            {{ $post?->exists ? 'Edit Post' : 'New Post' }}
        </flux:heading>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-3">
            <flux:text class="text-green-700 text-sm">{{ session('success') }}</flux:text>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- Main content --}}
        <div class="col-span-2 space-y-5">

            {{-- Title --}}
            <div>
                <flux:label class="text-zinc-900">Title</flux:label>
                <flux:input wire:model.live="title" placeholder="Post title..." class="mt-1 text-zinc-900" />
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Slug --}}
            <div>
                <flux:label class="text-zinc-900">Slug</flux:label>
                <flux:input wire:model="slug" placeholder="post-slug" class="mt-1 font-mono text-sm text-zinc-900" />
                @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Excerpt --}}
            <div>
                <div class="flex justify-between items-center">
                    <flux:label class="text-zinc-900">Excerpt</flux:label>
                    <span class="text-xs text-zinc-900">{{ strlen($excerpt) }}/300</span>
                </div>
                <flux:textarea wire:model="excerpt" rows="2" placeholder="Short summary shown in post cards..." class="mt-1 text-zinc-900" />
                @error('excerpt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- TipTap Editor --}}
            <div>
                <flux:label class="mb-1 text-zinc-900">Content</flux:label>
                <div
                    x-data="tiptap(@js($content), @js($language))"
                    x-on:language-changed.window="isRtl = ['ar','he','fa','ur'].includes($event.detail.language)"
                    x-on:image-uploaded.window="editor.chain().focus().setImage({ src: $event.detail.url }).run()"
                    x-init="init()"
                    x-destroy="destroy()"
                >
                    {{-- Toolbar --}}
                    <div class="flex flex-wrap items-center gap-1 rounded-t-xl border border-b-0 border-zinc-200 bg-zinc-50 px-2 py-1.5 text-zinc-900">
                        <button type="button" @click="editor.chain().focus().toggleBold().run()"
                            class="px-2 py-1 text-sm font-bold text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('bold') }">B</button>

                        <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                            class="px-2 py-1 text-sm italic text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('italic') }">I</button>

                        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                            class="px-2 py-1 text-sm font-semibold text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('heading', { level: 2 }) }">H2</button>

                        <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                            class="px-2 py-1 text-sm font-semibold text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('heading', { level: 3 }) }">H3</button>

                        <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                            class="px-2 py-1 text-sm text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('bulletList') }">• List</button>

                        <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                            class="px-2 py-1 text-sm text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('orderedList') }">1. List</button>

                        <button type="button" @click="setLink()"
                            class="px-2 py-1 text-sm text-zinc-900 rounded hover:bg-zinc-200 transition-colors"
                            :class="{ 'bg-zinc-200': editor?.isActive('link') }">Link</button>

                        <div class="h-4 w-px bg-zinc-300 mx-1"></div>

                        <div class="flex items-center gap-1">
                            <span class="text-xs text-zinc-900">Color:</span>
                            <input type="color" @change="setColor($event.target.value)"
                                class="w-6 h-6 rounded cursor-pointer border border-zinc-200" title="Text color" />
                            <button type="button" @click="editor.chain().focus().unsetColor().run()"
                                class="px-1.5 py-1 text-xs rounded hover:bg-zinc-200 text-zinc-900" title="Remove color">✕</button>
                        </div>

                        <div class="h-4 w-px bg-zinc-300 mx-1"></div>

                        <button type="button" @click="insertImageUrl()"
                            class="px-2 py-1 text-sm text-zinc-900 rounded hover:bg-zinc-200 transition-colors">🖼 URL</button>

                        <label class="px-2 py-1 text-sm text-zinc-900 rounded hover:bg-zinc-200 transition-colors cursor-pointer">
                            📁 Upload
                            <input type="file" wire:model="imageUpload" class="hidden" accept="image/*"
                                x-on:change="$wire.uploadImage()" />
                        </label>
                    </div>

                    {{-- Editor canvas --}}
                    <div x-ref="editorEl" class="rounded-b-xl border border-zinc-200 bg-white min-h-[400px]"></div>

                    {{-- Sync content back to Livewire on every editor update --}}
                    <input type="hidden" x-on:tiptap-updated.window="$wire.set('content', $event.detail.content)" />

                    @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">

            {{-- Publish actions --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-3">
                <flux:heading size="sm" class="text-zinc-900">Publish</flux:heading>

                <flux:button wire:click="saveDraft" variant="ghost" class="w-full text-zinc-900">Save Draft</flux:button>
                <flux:button wire:click="publishNow" variant="primary" class="w-full">Publish Now</flux:button>

                <div class="border-t border-zinc-100 pt-3 space-y-2">
                    <flux:label class="text-xs text-zinc-900">Schedule for later</flux:label>
                    <input type="datetime-local" wire:model="scheduledAt"
                        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    <flux:button wire:click="schedule" variant="ghost" class="w-full text-sm text-zinc-900">Schedule</flux:button>
                    @error('scheduledAt') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Language --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-2">
                <flux:heading size="sm" class="text-zinc-900">Language</flux:heading>
                <select wire:model.live="language"
                    class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="en">🇬🇧 English</option>
                    <option value="ar">🇸🇦 Arabic</option>
                    <option value="fr">🇫🇷 French</option>
                    <option value="es">🇪🇸 Spanish</option>
                    <option value="de">🇩🇪 German</option>
                </select>
                @error('language') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Details --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-3">
                <flux:heading size="sm" class="text-zinc-900">Details</flux:heading>
                <div>
                    <flux:label class="text-zinc-900">Category</flux:label>
                    <flux:input wire:model="category" class="mt-1 text-zinc-900" />
                </div>
                <div>
                    <flux:label class="text-zinc-900">Reading time</flux:label>
                    <flux:input wire:model="reading_time" class="mt-1 text-zinc-900" placeholder="5 min read" />
                </div>
            </div>

            {{-- SEO --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-3">
                <flux:heading size="sm" class="text-zinc-900">SEO</flux:heading>
                <div>
                    <div class="flex justify-between items-center">
                        <flux:label class="text-zinc-900">Meta title</flux:label>
                        <span class="text-xs text-zinc-900">{{ strlen($meta_title) }}/60</span>
                    </div>
                    <flux:input wire:model="meta_title" class="mt-1 text-sm text-zinc-900" placeholder="Leave blank to use title" />
                    @error('meta_title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex justify-between items-center">
                        <flux:label class="text-zinc-900">Meta description</flux:label>
                        <span class="text-xs text-zinc-900">{{ strlen($meta_description) }}/160</span>
                    </div>
                    <flux:textarea wire:model="meta_description" rows="3" class="mt-1 text-sm text-zinc-900"
                        placeholder="Leave blank to use excerpt" />
                    @error('meta_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

        </div>
    </div>
</div>
