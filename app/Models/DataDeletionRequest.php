<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit record for every data deletion request received via Meta's signed
 * callback or processed manually via the artisan command.
 *
 * Required for Section 3(d)(i) compliance — proves to Meta on audit that
 * deletions actually executed.
 */
final class DataDeletionRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const SOURCE_META = 'meta';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_API = 'api';

    protected $fillable = [
        'platform_user_id',
        'source',
        'confirmation_code',
        'status',
        'matched_records',
        'error',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_records' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
