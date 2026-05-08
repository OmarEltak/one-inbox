<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_QUEUED     = 'queued';
    public const STATUS_PUBLISHING = 'publishing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_PARTIAL    = 'partial';
    public const STATUS_FAILED     = 'failed';

    protected $fillable = [
        'team_id',
        'user_id',
        'content',
        'media_path',
        'media_type',
        'status',
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SocialPostTarget::class);
    }

    /**
     * Roll up per-target statuses into a single post-level status.
     * Called by PublishPostJob after fanning out.
     */
    public function recomputeStatus(): void
    {
        $statuses = $this->targets()->pluck('status');
        if ($statuses->isEmpty()) {
            return;
        }
        $allDone = $statuses->every(fn ($s) => in_array($s, ['succeeded', 'failed'], true));
        if (! $allDone) {
            return; // still publishing
        }
        $succeeded = $statuses->filter(fn ($s) => $s === 'succeeded')->count();
        $failed    = $statuses->filter(fn ($s) => $s === 'failed')->count();

        $this->status = match (true) {
            $failed === 0    => self::STATUS_COMPLETED,
            $succeeded === 0 => self::STATUS_FAILED,
            default          => self::STATUS_PARTIAL,
        };
        $this->published_at = $this->published_at ?? now();
        $this->save();
    }
}
