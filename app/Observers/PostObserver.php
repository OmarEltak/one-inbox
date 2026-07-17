<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\PingSearchEnginesJob;
use App\Models\Post;

class PostObserver
{
    /**
     * Fires whenever a post transitions from unpublished → published,
     * or when an already-published post's content is updated.
     */
    public function saved(Post $post): void
    {
        if ($post->published_at === null || $post->published_at->isFuture()) {
            return;
        }

        $wasJustCreated   = $post->wasRecentlyCreated;
        $wasJustPublished = $post->wasChanged('published_at') && $post->getOriginal('published_at') === null;
        $contentChanged   = $post->wasChanged(['title', 'content', 'excerpt', 'meta_title', 'meta_description']);

        if (! $wasJustCreated && ! $wasJustPublished && ! $contentChanged) {
            return;
        }

        $url = route('blog.show', $post->slug);
        PingSearchEnginesJob::dispatch([$url])->afterCommit();
    }
}
