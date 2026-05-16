<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'team_id',
        'platform',
        'created_by',
        'name',
        'type',
        'target_criteria',
        'message_template',
        'subject',
        'sender_page_id',
        'daily_cap',
        'jitter_min_seconds',
        'jitter_max_seconds',
        'ai_personalize',
        'total_contacts',
        'sent_count',
        'reply_count',
        'failed_count',
        'opened_count',
        'unsubscribed_count',
        'status',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'target_criteria'    => 'array',
            'ai_personalize'     => 'boolean',
            'total_contacts'     => 'integer',
            'sent_count'         => 'integer',
            'reply_count'        => 'integer',
            'failed_count'       => 'integer',
            'opened_count'       => 'integer',
            'unsubscribed_count' => 'integer',
            'daily_cap'          => 'integer',
            'jitter_min_seconds' => 'integer',
            'jitter_max_seconds' => 'integer',
            'scheduled_at'       => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function senderPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'sender_page_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
