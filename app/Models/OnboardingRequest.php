<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingRequest extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_REJECTED    = 'rejected';

    protected $fillable = [
        'team_id',
        'requested_by_user_id',
        'platform',
        'business_name',
        'page_url',
        'contact_phone',
        'notes',
        'status',
        'assigned_admin_user_id',
        'resulting_page_id',
        'admin_notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_user_id');
    }

    public function resultingPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'resulting_page_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true);
    }
}
