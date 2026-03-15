<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'platform_message_id',
        'direction',
        'sender_type',
        'sender_id',
        'content_type',
        'content',
        'media_url',
        'media_type',
        'reply_to_message_id',
        'ai_confidence',
        'metadata',
        'platform_sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_confidence' => 'float',
            'metadata' => 'array',
            'platform_sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function isFromAi(): bool
    {
        return $this->sender_type === 'ai';
    }

    public function isFromContact(): bool
    {
        return $this->sender_type === 'contact';
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    public function isActivityNote(): bool
    {
        if (! $this->content) return false;
        return str_starts_with($this->content, 'Lead stage set to ')
            || str_starts_with($this->content, 'Assigned to ')
            || str_starts_with($this->content, 'Unassigned from ')
            || str_starts_with($this->content, 'Label added:')
            || str_starts_with($this->content, 'Label removed:');
    }


}
