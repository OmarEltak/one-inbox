# Blog Admin & SEO Enhancement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a personal blog writing UI at `/super-admin/blog`, add language/RTL support to posts, and upgrade the public blog's SEO + LLM citation signals.

**Architecture:** Livewire components under the existing `super-admin` middleware group. TipTap WYSIWYG editor wired via Alpine.js with content synced back to Livewire through a hidden input. Language filter on the public blog index via query param. Schema.org and OG tag upgrades in blade views.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI, Alpine.js, TipTap (npm), Tailwind CSS 4

---

## File Map

| File | Action |
|------|--------|
| `database/migrations/XXXX_add_language_to_posts_table.php` | Create |
| `app/Models/Post.php` | Modify |
| `routes/web.php` | Modify (2 places: super-admin group + sitemap lastmod) |
| `app/Livewire/SuperAdmin/Blog/Index.php` | Create |
| `app/Livewire/SuperAdmin/Blog/Editor.php` | Create |
| `resources/views/livewire/super-admin/blog/index.blade.php` | Create |
| `resources/views/livewire/super-admin/blog/editor.blade.php` | Create |
| `app/Http/Controllers/BlogController.php` | Modify |
| `resources/views/blog/index.blade.php` | Modify |
| `resources/views/blog/show.blade.php` | Modify |
| `resources/js/app.js` | Modify (register TipTap Alpine component) |
| `package.json` | Modify (add TipTap packages) |

---

## Task 1: Install TipTap

**Files:**
- Modify: `package.json`
- Modify: `resources/js/app.js`

- [ ] **Step 1: Install TipTap packages**

```bash
cd /c/Users/NanoChip/Herd/one-inbox
npm install @tiptap/core @tiptap/starter-kit @tiptap/extension-color @tiptap/extension-text-style @tiptap/extension-link @tiptap/extension-image
```

Expected output: packages added to `node_modules/`, `package.json` updated.

- [ ] **Step 2: Build to confirm no errors**

```bash
npm run build
```

Expected: build succeeds with no errors.

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "chore: install tiptap editor packages"
```

---

## Task 2: Migration — add language columns to posts

**Files:**
- Create: `database/migrations/2026_07_08_000001_add_language_to_posts_table.php`

- [ ] **Step 1: Create migration file**

Create `database/migrations/2026_07_08_000001_add_language_to_posts_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('language', 10)->default('en')->after('author');
            $table->boolean('is_rtl')->default(false)->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['language', 'is_rtl']);
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `Migrated: 2026_07_08_000001_add_language_to_posts_table`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_07_08_000001_add_language_to_posts_table.php
git commit -m "feat: add language and is_rtl columns to posts table"
```

---

## Task 3: Update Post model

**Files:**
- Modify: `app/Models/Post.php`

- [ ] **Step 1: Update Post model**

Replace the entire `app/Models/Post.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'meta_title', 'meta_description', 'category',
        'reading_time', 'author', 'published_at',
        'language', 'is_rtl',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_rtl'       => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeForLanguage($query, string $lang)
    {
        return $query->where('language', $lang);
    }

    public static function publishedLanguages(): array
    {
        return self::published()
            ->distinct()
            ->orderBy('language')
            ->pluck('language')
            ->all();
    }

    public function wordCount(): int
    {
        preg_match_all('/\S+/u', strip_tags($this->content ?? ''), $m);
        return count($m[0]);
    }

    public function getMetaTitleAttribute($value): string
    {
        return $value ?: $this->title . ' | OT1-Pro Blog';
    }

    public function getMetaDescriptionAttribute($value): string
    {
        return $value ?: $this->excerpt;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/Post.php
git commit -m "feat: add language scope and wordCount to Post model"
```

---

## Task 4: Blog admin routes

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Add routes inside the existing super-admin group**

Open `routes/web.php`. Find this block:

```php
Route::middleware('super-admin')->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('customers', \App\Livewire\SuperAdmin\Customers::class)->name('customers');
    Route::get('subscriptions', \App\Livewire\SuperAdmin\Subscriptions::class)->name('subscriptions');
    Route::get('page-assignments', \App\Livewire\SuperAdmin\PageAssignments::class)->name('page-assignments');
    Route::get('onboarding-requests', \App\Livewire\SuperAdmin\OnboardingRequests::class)->name('onboarding-requests');
});
```

Replace it with:

```php
Route::middleware('super-admin')->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('customers', \App\Livewire\SuperAdmin\Customers::class)->name('customers');
    Route::get('subscriptions', \App\Livewire\SuperAdmin\Subscriptions::class)->name('subscriptions');
    Route::get('page-assignments', \App\Livewire\SuperAdmin\PageAssignments::class)->name('page-assignments');
    Route::get('onboarding-requests', \App\Livewire\SuperAdmin\OnboardingRequests::class)->name('onboarding-requests');

    // Blog admin
    Route::get('blog', \App\Livewire\SuperAdmin\Blog\Index::class)->name('blog.index');
    Route::get('blog/create', \App\Livewire\SuperAdmin\Blog\Editor::class)->name('blog.create');
    Route::get('blog/{post}/edit', \App\Livewire\SuperAdmin\Blog\Editor::class)->name('blog.edit');
});
```

- [ ] **Step 2: Fix sitemap lastmod to use updated_at**

In the same `routes/web.php`, find:

```php
'lastmod'    => $post->published_at?->toDateString() ?? $today,
```

Replace with:

```php
'lastmod'    => $post->updated_at?->toDateString() ?? $today,
```

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "feat: add blog admin routes and fix sitemap lastmod to use updated_at"
```

---

## Task 5: Blog\Index Livewire component

**Files:**
- Create: `app/Livewire/SuperAdmin/Blog/Index.php`
- Create: `resources/views/livewire/super-admin/blog/index.blade.php`

- [ ] **Step 1: Create the Livewire component**

Create `app/Livewire/SuperAdmin/Blog/Index.php`:

```php
<?php

namespace App\Livewire\SuperAdmin\Blog;

use App\Models\Post;
use Livewire\Component;

class Index extends Component
{
    public ?int $confirmDeleteId = null;

    public function deletePost(int $id): void
    {
        Post::findOrFail($id)->delete();
        $this->confirmDeleteId = null;
        session()->flash('success', 'Post deleted.');
    }

    public function render()
    {
        return view('livewire.super-admin.blog.index', [
            'posts' => Post::orderByDesc('updated_at')->get(),
        ])->layout('layouts.app', ['title' => 'Blog Admin']);
    }
}
```

- [ ] **Step 2: Create the view**

Create `resources/views/livewire/super-admin/blog/index.blade.php`:

```blade
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl" class="text-zinc-900">Blog Posts</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Write, schedule, and manage all blog articles.</flux:text>
        </div>
        <flux:button href="{{ route('super-admin.blog.create') }}" variant="primary" icon="plus">
            New Post
        </flux:button>
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
                        <th class="text-left px-4 py-3 font-medium text-zinc-600">Date</th>
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
                                <flux:button href="{{ route('super-admin.blog.edit', $post) }}" size="sm" variant="ghost" icon="pencil">
                                    Edit
                                </flux:button>
                                <flux:button wire:click="$set('confirmDeleteId', {{ $post->id }})" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600">
                                    Delete
                                </flux:button>
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
```

- [ ] **Step 3: Commit**

```bash
git add app/Livewire/SuperAdmin/Blog/Index.php resources/views/livewire/super-admin/blog/index.blade.php
git commit -m "feat: blog admin index — list all posts with status badges"
```

---

## Task 6: Blog\Editor Livewire component

**Files:**
- Create: `app/Livewire/SuperAdmin/Blog/Editor.php`

- [ ] **Step 1: Create the component**

Create `app/Livewire/SuperAdmin/Blog/Editor.php`:

```php
<?php

namespace App\Livewire\SuperAdmin\Blog;

use App\Models\Post;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Editor extends Component
{
    use WithFileUploads;

    public ?Post $post = null;

    public string $title        = '';
    public string $slug         = '';
    public string $excerpt      = '';
    public string $content      = '';
    public string $language     = 'en';
    public string $category     = 'general';
    public string $reading_time = '5 min read';
    public string $meta_title   = '';
    public string $meta_description = '';
    public ?string $scheduledAt = null;

    public $imageUpload = null;
    public string $uploadedImageUrl = '';

    public function mount(?Post $post = null): void
    {
        if ($post?->exists) {
            $this->post             = $post;
            $this->title            = $post->title;
            $this->slug             = $post->slug;
            $this->excerpt          = $post->excerpt;
            $this->content          = $post->content;
            $this->language         = $post->language;
            $this->category         = $post->category;
            $this->reading_time     = $post->reading_time;
            $this->meta_title       = $post->getRawOriginal('meta_title') ?? '';
            $this->meta_description = $post->getRawOriginal('meta_description') ?? '';
            $this->scheduledAt      = $post->published_at?->isFuture()
                ? $post->published_at->format('Y-m-d\TH:i')
                : null;
        }
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->post?->exists) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedLanguage(string $value): void
    {
        $this->dispatch('language-changed', language: $value);
    }

    public function uploadImage(): void
    {
        $this->validate(['imageUpload' => 'required|image|max:4096']);
        $path = $this->imageUpload->store('blog/images', 'public');
        $this->uploadedImageUrl = '/storage/' . $path;
        $this->dispatch('image-uploaded', url: $this->uploadedImageUrl);
        $this->imageUpload = null;
    }

    public function saveDraft(): void
    {
        $this->save(publishedAt: null);
        session()->flash('success', 'Draft saved.');
    }

    public function publishNow(): void
    {
        $this->save(publishedAt: now());
        session()->flash('success', 'Post published.');
    }

    public function schedule(): void
    {
        $this->validate(['scheduledAt' => 'required|date|after:now']);
        $this->save(publishedAt: $this->scheduledAt);
        session()->flash('success', 'Post scheduled.');
    }

    private function save(mixed $publishedAt): void
    {
        $data = $this->validate([
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|max:255|unique:posts,slug' . ($this->post?->exists ? ',' . $this->post->id : ''),
            'excerpt'          => 'required|string|max:300',
            'content'          => 'required|string',
            'language'         => 'required|in:en,ar,fr,es,de',
            'category'         => 'required|string|max:100',
            'reading_time'     => 'required|string|max:50',
            'meta_title'       => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ]);

        $isRtl = in_array($data['language'], ['ar', 'he', 'fa', 'ur']);

        $attributes = array_merge($data, [
            'is_rtl'       => $isRtl,
            'published_at' => $publishedAt,
            'author'       => 'Omar Eltak',
        ]);

        if ($this->post?->exists) {
            $this->post->update($attributes);
        } else {
            $this->post = Post::create($attributes);
        }

        $this->redirect(route('super-admin.blog.edit', $this->post), navigate: true);
    }

    public function render()
    {
        return view('livewire.super-admin.blog.editor')
            ->layout('layouts.app', ['title' => $this->post?->exists ? 'Edit Post' : 'New Post']);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Livewire/SuperAdmin/Blog/Editor.php
git commit -m "feat: blog editor Livewire component — draft/publish/schedule actions"
```

---

## Task 7: Blog editor view with TipTap

**Files:**
- Create: `resources/views/livewire/super-admin/blog/editor.blade.php`
- Modify: `resources/js/app.js`

- [ ] **Step 1: Register TipTap Alpine component in app.js**

Open `resources/js/app.js` and add the TipTap Alpine component. Add these imports at the top and the component registration before `Alpine.start()` (or wherever Alpine is initialized):

```js
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import { Color } from '@tiptap/extension-color'
import TextStyle from '@tiptap/extension-text-style'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'

document.addEventListener('alpine:init', () => {
    Alpine.data('tiptap', (initialContent = '', language = 'en') => ({
        editor: null,
        content: initialContent,
        isRtl: ['ar', 'he', 'fa', 'ur'].includes(language),

        init() {
            const self = this

            this.editor = new Editor({
                element: this.$refs.editorEl,
                extensions: [
                    StarterKit,
                    TextStyle,
                    Color,
                    Link.configure({ openOnClick: false }),
                    Image,
                ],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: 'prose prose-zinc max-w-none min-h-[400px] p-4 focus:outline-none',
                        dir: this.isRtl ? 'rtl' : 'ltr',
                    },
                },
                onUpdate({ editor }) {
                    self.content = editor.getHTML()
                    self.$dispatch('tiptap-updated', { content: editor.getHTML() })
                },
            })

            this.$watch('isRtl', (val) => {
                this.editor?.view.dom.setAttribute('dir', val ? 'rtl' : 'ltr')
            })
        },

        destroy() {
            this.editor?.destroy()
        },

        setLink() {
            const url = prompt('URL:')
            if (!url) return
            this.editor.chain().focus().extendMarkToLink({ href: url, target: '_blank', rel: 'noopener noreferrer' }).setLink({ href: url, target: '_blank', rel: 'noopener noreferrer' }).run()
        },

        insertImageUrl() {
            const url = prompt('Image URL:')
            if (url) this.editor.chain().focus().setImage({ src: url }).run()
        },

        setColor(color) {
            this.editor.chain().focus().setColor(color).run()
        },
    }))
})
```

- [ ] **Step 2: Create the editor blade view**

Create `resources/views/livewire/super-admin/blog/editor.blade.php`:

```blade
<div class="p-6 max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('super-admin.blog.index') }}" variant="ghost" icon="arrow-left" size="sm">
            All Posts
        </flux:button>
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
                <flux:label>Title</flux:label>
                <flux:input wire:model.live="title" placeholder="Post title..." class="mt-1 text-lg font-semibold" />
            </div>

            {{-- Slug --}}
            <div>
                <flux:label>Slug</flux:label>
                <flux:input wire:model="slug" placeholder="post-slug" class="mt-1 font-mono text-sm" />
            </div>

            {{-- Excerpt --}}
            <div>
                <div class="flex justify-between">
                    <flux:label>Excerpt</flux:label>
                    <span class="text-xs text-zinc-400">{{ strlen($excerpt) }}/300</span>
                </div>
                <flux:textarea wire:model="excerpt" rows="2" placeholder="Short summary..." class="mt-1" />
            </div>

            {{-- TipTap Editor --}}
            <div
                x-data="tiptap(@js($content), @js($language))"
                x-on:language-changed.window="isRtl = ['ar','he','fa','ur'].includes($event.detail.language)"
                x-on:image-uploaded.window="editor.chain().focus().setImage({ src: $event.detail.url }).run()"
                x-destroy="destroy()"
            >
                {{-- Toolbar --}}
                <div class="flex flex-wrap items-center gap-1 rounded-t-xl border border-b-0 border-zinc-200 bg-zinc-50 px-2 py-1.5">
                    <button type="button" @click="editor.chain().focus().toggleBold().run()" class="px-2 py-1 text-sm font-bold rounded hover:bg-zinc-200" :class="{ 'bg-zinc-200': editor?.isActive('bold') }">B</button>
                    <button type="button" @click="editor.chain().focus().toggleItalic().run()" class="px-2 py-1 text-sm italic rounded hover:bg-zinc-200" :class="{ 'bg-zinc-200': editor?.isActive('italic') }">I</button>
                    <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" class="px-2 py-1 text-sm font-semibold rounded hover:bg-zinc-200" :class="{ 'bg-zinc-200': editor?.isActive('heading', { level: 2 }) }">H2</button>
                    <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" class="px-2 py-1 text-sm font-semibold rounded hover:bg-zinc-200" :class="{ 'bg-zinc-200': editor?.isActive('heading', { level: 3 }) }">H3</button>
                    <button type="button" @click="editor.chain().focus().toggleBulletList().run()" class="px-2 py-1 text-sm rounded hover:bg-zinc-200" :class="{ 'bg-zinc-200': editor?.isActive('bulletList') }">• List</button>
                    <button type="button" @click="setLink()" class="px-2 py-1 text-sm rounded hover:bg-zinc-200" :class="{ 'bg-zinc-200': editor?.isActive('link') }">Link</button>
                    <div class="flex items-center gap-1 ml-1">
                        <span class="text-xs text-zinc-400">Color:</span>
                        <input type="color" @change="setColor($event.target.value)" class="w-6 h-6 rounded cursor-pointer border border-zinc-200" title="Text color" />
                        <button type="button" @click="editor.chain().focus().unsetColor().run()" class="px-1.5 py-1 text-xs rounded hover:bg-zinc-200 text-zinc-500">✕</button>
                    </div>
                    <div class="h-4 w-px bg-zinc-300 mx-1"></div>
                    <button type="button" @click="insertImageUrl()" class="px-2 py-1 text-sm rounded hover:bg-zinc-200">🖼 URL</button>
                    <div>
                        <label class="px-2 py-1 text-sm rounded hover:bg-zinc-200 cursor-pointer">
                            📁 Upload
                            <input type="file" wire:model="imageUpload" class="hidden" accept="image/*" @change="$wire.uploadImage()" />
                        </label>
                    </div>
                </div>

                {{-- Editor area --}}
                <div
                    x-ref="editorEl"
                    class="rounded-b-xl border border-zinc-200 bg-white"
                ></div>

                {{-- Hidden input synced to Livewire via $wire.set on every editor update --}}
                <input type="hidden" x-on:tiptap-updated.window="$wire.set('content', $event.detail.content)" />

                @error('content') <flux:text class="text-red-500 text-xs mt-1">{{ $message }}</flux:text> @enderror
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">

            {{-- Publish actions --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-3">
                <flux:heading size="sm">Publish</flux:heading>

                <flux:button wire:click="saveDraft" variant="ghost" class="w-full">Save Draft</flux:button>
                <flux:button wire:click="publishNow" variant="primary" class="w-full">Publish Now</flux:button>

                <div class="border-t border-zinc-100 pt-3 space-y-2">
                    <flux:label class="text-xs">Schedule for later</flux:label>
                    <flux:input type="datetime-local" wire:model="scheduledAt" class="text-sm" />
                    <flux:button wire:click="schedule" variant="ghost" class="w-full text-sm">Schedule</flux:button>
                </div>

                @error('scheduledAt') <flux:text class="text-red-500 text-xs">{{ $message }}</flux:text> @enderror
            </div>

            {{-- Language --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-2">
                <flux:heading size="sm">Language</flux:heading>
                <flux:select wire:model.live="language">
                    <option value="en">🇬🇧 English</option>
                    <option value="ar">🇸🇦 Arabic</option>
                    <option value="fr">🇫🇷 French</option>
                    <option value="es">🇪🇸 Spanish</option>
                    <option value="de">🇩🇪 German</option>
                </flux:select>
            </div>

            {{-- Category & reading time --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-3">
                <flux:heading size="sm">Details</flux:heading>
                <div>
                    <flux:label>Category</flux:label>
                    <flux:input wire:model="category" class="mt-1" />
                </div>
                <div>
                    <flux:label>Reading time</flux:label>
                    <flux:input wire:model="reading_time" class="mt-1" placeholder="5 min read" />
                </div>
            </div>

            {{-- SEO --}}
            <div class="rounded-xl border border-zinc-200 p-4 space-y-3">
                <flux:heading size="sm">SEO</flux:heading>
                <div>
                    <div class="flex justify-between">
                        <flux:label>Meta title</flux:label>
                        <span class="text-xs text-zinc-400">{{ strlen($meta_title) }}/60</span>
                    </div>
                    <flux:input wire:model="meta_title" class="mt-1 text-sm" placeholder="Leave blank to use title" />
                </div>
                <div>
                    <div class="flex justify-between">
                        <flux:label>Meta description</flux:label>
                        <span class="text-xs text-zinc-400">{{ strlen($meta_description) }}/160</span>
                    </div>
                    <flux:textarea wire:model="meta_description" rows="3" class="mt-1 text-sm" placeholder="Leave blank to use excerpt" />
                </div>
            </div>

        </div>
    </div>
</div>
```

- [ ] **Step 3: Build assets**

```bash
npm run build
```

Expected: build succeeds, TipTap bundled into the JS output.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/super-admin/blog/editor.blade.php resources/js/app.js
git commit -m "feat: blog editor view with TipTap WYSIWYG — bold, italic, headings, color, link, image"
```

---

## Task 8: Language filter on public blog index

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `resources/views/blog/index.blade.php`

- [ ] **Step 1: Update BlogController**

Replace `app/Http/Controllers/BlogController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->query('lang');

        $query = Post::published()->orderByDesc('published_at');

        if ($lang) {
            $query->forLanguage($lang);
        }

        $posts     = $query->paginate(12)->withQueryString();
        $languages = Post::publishedLanguages();
        $activeLang = $lang;

        return view('blog.index', compact('posts', 'languages', 'activeLang'));
    }

    public function show(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $related = Post::published()
            ->where('category', $post->category)
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
```

- [ ] **Step 2: Add language filter bar to blog index view**

Open `resources/views/blog/index.blade.php`. Find the hero section closing tag and the `{{-- Posts Grid --}}` comment:

```blade
    </section>

    {{-- Posts Grid --}}
```

Replace with:

```blade
    </section>

    {{-- Language filter --}}
    @if(count($languages) > 1)
    <div class="border-b border-zinc-200 bg-white">
        <div class="mx-auto max-w-6xl px-6 py-3 flex items-center gap-2">
            <a href="{{ route('blog.index') }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ is_null($activeLang) ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                All
            </a>
            @foreach($languages as $lang)
            <a href="{{ route('blog.index', ['lang' => $lang]) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium transition-colors {{ $activeLang === $lang ? 'bg-indigo-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                {{ match($lang) {
                    'ar' => 'Arabic',
                    'en' => 'English',
                    'fr' => 'French',
                    'es' => 'Spanish',
                    'de' => 'German',
                    default => strtoupper($lang),
                } }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Posts Grid --}}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/BlogController.php resources/views/blog/index.blade.php
git commit -m "feat: language filter on public blog index"
```

---

## Task 9: Upgrade blog show view — schema, OG tags, lang/dir

**Files:**
- Modify: `resources/views/blog/show.blade.php`

- [ ] **Step 1: Replace the schema block and add OG/lang meta**

The existing `show.blade.php` already has `Article` schema and `BreadcrumbList`. We need to:
1. Upgrade `Article` → `BlogPosting` with `Person` author, `inLanguage`, `wordCount`, `dateModified`
2. Add Open Graph article tags
3. Add `lang` + `dir` to the content wrapper

Replace the entire `@push('schema')` block (lines 7–65 of the current file) with:

```blade
@push('schema')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BlogPosting",
    "headline": {!! json_encode($post->title) !!},
    "description": {!! json_encode($post->meta_description) !!},
    "image": [{!! json_encode(config('app.url') . '/og-image.png') !!}],
    "datePublished": "{{ $post->published_at->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    "inLanguage": {{ json_encode($post->language) }},
    "wordCount": {{ $post->wordCount() }},
    "author": {
        "@@type": "Person",
        "name": "Omar Eltak",
        "url": "{{ url('/about') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "OT1-Pro",
        "url": "https://ot1-pro.com",
        "logo": {
            "@@type": "ImageObject",
            "url": "https://ot1-pro.com/logo.png"
        }
    },
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ route('blog.show', $post->slug) }}"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {"@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ route('home') }}"},
        {"@@type": "ListItem", "position": 2, "name": "Blog", "item": "{{ route('blog.index') }}"},
        {"@@type": "ListItem", "position": 3, "name": {!! json_encode($post->title) !!}, "item": "{{ route('blog.show', $post->slug) }}"}
    ]
}
</script>
@endpush

@push('meta')
<meta property="og:type" content="article">
<meta property="og:title" content="{{ $post->meta_title }}">
<meta property="og:description" content="{{ $post->meta_description }}">
<meta property="og:image" content="{{ config('app.url') }}/og-image.png">
<meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
<meta property="article:author" content="Omar Eltak">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $post->meta_title }}">
<meta name="twitter:description" content="{{ $post->meta_description }}">
<meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">
@endpush
```

- [ ] **Step 2: Add dir="rtl" to the content wrapper**

In `show.blade.php`, find:

```blade
            <div class="prose prose-zinc max-w-none dark:prose-invert
```

Replace with:

```blade
            <div class="prose prose-zinc max-w-none dark:prose-invert" @if($post->is_rtl) dir="rtl" @endif
```

- [ ] **Step 3: Check if the marketing layout supports @stack('meta')**

Check `resources/views/layouts/marketing.blade.php` (or similar) for a `@stack('meta')` or `@yield('meta')` inside `<head>`. If it's not there, add `@stack('meta')` inside `<head>` just before `</head>`.

- [ ] **Step 4: Commit**

```bash
git add resources/views/blog/show.blade.php
git commit -m "feat: upgrade blog post schema to BlogPosting with Person author, inLanguage, wordCount; add OG + Twitter meta; RTL support"
```

---

## Task 10: Smoke test the full feature

- [ ] **Step 1: Visit the blog admin**

Navigate to `https://one-inbox.test/super-admin/blog`. Verify:
- Table renders, "New Post" button visible
- No 404 or auth errors

- [ ] **Step 2: Create a test post**

Click "New Post". Fill in title, excerpt, write some content in the editor (try bold + a link). Set language to Arabic. Click "Save Draft". Verify:
- Redirects to edit page with success flash
- `language = ar`, `is_rtl = true` in the DB (`php artisan tinker` → `App\Models\Post::latest()->first()->only(['language','is_rtl'])`)

- [ ] **Step 3: Publish and verify public view**

Click "Publish Now" on the draft. Visit `/blog/{slug}`. Verify:
- Page loads
- View source: `inLanguage` present in JSON-LD, `wordCount` > 0, author is `Person` not `Organization`
- View source: OG tags present

- [ ] **Step 4: Verify language filter**

Visit `/blog`. If only one language exists, the filter bar is hidden (correct). Publish an English post. Visit `/blog` — filter should now show "All | Arabic | English". Click Arabic — only Arabic post shown.

- [ ] **Step 5: Final commit**

```bash
git add .
git commit -m "chore: blog admin + SEO enhancement complete"
```
