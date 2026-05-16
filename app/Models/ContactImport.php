<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactImport extends Model
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'team_id',
        'user_id',
        'filename',
        'original_name',
        'tag',
        'total_rows',
        'imported_rows',
        'skipped_rows',
        'invalid_rows',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'total_rows'    => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows'  => 'integer',
            'invalid_rows'  => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
