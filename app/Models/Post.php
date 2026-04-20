<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'meta_title', 'meta_description', 'category',
        'reading_time', 'author', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function getMetaTitleAttribute($value): string
    {
        return $value ?: $this->title . ' | One Inbox Blog';
    }

    public function getMetaDescriptionAttribute($value): string
    {
        return $value ?: $this->excerpt;
    }
}
