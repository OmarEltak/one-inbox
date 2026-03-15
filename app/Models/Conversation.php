<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'page_id',
        'team_id',
        'platform',
        'platform_conversation_id',
        'contact_id',
        'status',
        'ai_paused',
        'last_message_at',
        'last_message_preview',
        'unread_count',
        'assigned_to',
        'labels',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'unread_count' => 'integer',
            'ai_paused' => 'boolean',
            'labels' => 'array',
            'metadata' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
    }

    public function incrementUnread(): void
    {
        $this->increment('unread_count');
    }

    public function pauseAi(): void
    {
        $this->update(['ai_paused' => true]);
    }

    public function resumeAi(): void
    {
        $this->update(['ai_paused' => false]);
    }
}
