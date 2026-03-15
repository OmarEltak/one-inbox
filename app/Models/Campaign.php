<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'team_id',
        'created_by',
        'name',
        'type',
        'target_criteria',
        'message_template',
        'ai_personalize',
        'total_contacts',
        'sent_count',
        'reply_count',
        'status',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'target_criteria' => 'array',
            'ai_personalize' => 'boolean',
            'total_contacts' => 'integer',
            'sent_count' => 'integer',
            'reply_count' => 'integer',
            'scheduled_at' => 'datetime',
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
}
