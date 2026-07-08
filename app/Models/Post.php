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
