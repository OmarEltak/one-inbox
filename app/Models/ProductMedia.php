<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    public const KIND_IMAGE = 'image';
    public const KIND_VIDEO = 'video';

    protected $fillable = [
        'team_id',
        'ai_config_id',
        'product_key',
        'kind',
        'disk',
        'path',
        'mime',
        'size_bytes',
        'variant_label',
        'is_primary',
        'allow_ai_autosend',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'        => 'boolean',
            'allow_ai_autosend' => 'boolean',
            'size_bytes'        => 'integer',
            'display_order'     => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function aiConfig(): BelongsTo
    {
        return $this->belongsTo(AiConfig::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }
}
