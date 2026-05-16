<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSuppression extends Model
{
    public const REASON_UNSUBSCRIBED = 'unsubscribed';
    public const REASON_BOUNCED      = 'bounced';
    public const REASON_COMPLAINT    = 'complaint';
    public const REASON_MANUAL       = 'manual';

    protected $fillable = [
        'team_id',
        'email',
        'reason',
        'campaign_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public static function isSuppressed(int $teamId, string $email): bool
    {
        return self::query()
            ->where('team_id', $teamId)
            ->where('email', strtolower(trim($email)))
            ->exists();
    }
}
