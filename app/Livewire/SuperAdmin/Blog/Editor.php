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

    public string $title            = '';
    public string $slug             = '';
    public string $excerpt          = '';
    public string $content          = '';
    public string $language         = 'en';
    public string $category         = 'general';
    public string $reading_time     = '5 min read';
    public string $meta_title       = '';
    public string $meta_description = '';
    public ?string $scheduledAt     = null;

    public $imageUpload = null;

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
        $url  = '/storage/' . $path;
        $this->dispatch('image-uploaded', url: $url);
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
