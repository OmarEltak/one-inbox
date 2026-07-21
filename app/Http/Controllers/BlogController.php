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

    /**
     * SEO priority pages — surfaced in the "Related articles" section of every
     * blog post to consolidate internal link equity toward the pages we most
     * want ranking on page 1. Reorder or edit as ranking priorities shift.
     */
    private const PRIORITY_SLUGS = [
        'respond-io-pricing-explained-2026',
        'what-is-unified-inbox-complete-guide-2026',
        'unified-inbox-vs-shared-inbox-vs-team-inbox-difference',
    ];

    public function show(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        $priorityRelated = Post::published()
            ->whereIn('slug', self::PRIORITY_SLUGS)
            ->where('id', '!=', $post->id)
            ->get()
            ->sortBy(fn ($p) => array_search($p->slug, self::PRIORITY_SLUGS, true))
            ->take(2)
            ->values();

        $categoryRelated = Post::published()
            ->where('category', $post->category)
            ->where('id', '!=', $post->id)
            ->whereNotIn('slug', $priorityRelated->pluck('slug'))
            ->orderByDesc('published_at')
            ->limit(3 - $priorityRelated->count())
            ->get();

        $related = $priorityRelated->concat($categoryRelated);

        return view('blog.show', compact('post', 'related'));
    }
}
