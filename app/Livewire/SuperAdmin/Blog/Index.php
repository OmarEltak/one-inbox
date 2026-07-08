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
