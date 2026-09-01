<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'team_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'kind',
        'duration_seconds',
        'checksum_sha256',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes'       => 'integer',
            'duration_seconds' => 'integer',
            'metadata'         => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
