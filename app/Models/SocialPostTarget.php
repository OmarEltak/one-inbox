<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostTarget extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_SUCCEEDED  = 'succeeded';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'social_post_id',
        'page_id',
        'platform',
        'channel_id',
        'status',
        'platform_post_id',
        'error_message',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function markPublishing(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHING]);
    }

    public function markSucceeded(?string $platformPostId): void
    {
        $this->update([
            'status'           => self::STATUS_SUCCEEDED,
            'platform_post_id' => $platformPostId,
            'posted_at'        => now(),
            'error_message'    => null,
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'error_message' => mb_substr($reason, 0, 8000),
        ]);
    }
}
