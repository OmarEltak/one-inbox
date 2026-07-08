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

        $posts      = $query->paginate(12)->withQueryString();
        $languages  = Post::publishedLanguages();
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
