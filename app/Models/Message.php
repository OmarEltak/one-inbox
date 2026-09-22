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
        'media_asset_id',
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

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
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

    /**
     * Phase E — Onboarding funnel: stamp `first_real_message_at` on the team
     * the FIRST time a real inbound message from a contact lands. Guarded so
     * only genuine customer traffic counts:
     *
     *   - direction === 'inbound' (skip outbound echoes/replies)
     *   - sender_type === 'contact' (skip system/AI/user)
     *   - team.settings.first_real_message_at empty (once-per-team)
     *
     * Inserted here in Message::booted() rather than in ProcessIncomingMessage
     * because that job has 12+ Message::create() sites (one per platform);
     * hooking the model gives one narrow choke point that all platforms hit.
     */
    protected static function booted(): void
    {
        static::created(function (Message $message): void {
            if ($message->direction !== 'inbound' || $message->sender_type !== 'contact') {
                return;
            }
            if ($message->isActivityNote()) {
                return;
            }

            $conversation = $message->conversation;
            if (! $conversation || ! $conversation->team_id) {
                return;
            }

            $team = $conversation->team;
            if (! $team) {
                return;
            }

            $settings = $team->settings ?? [];
            if (! empty($settings['first_real_message_at'])) {
                return;
            }

            $settings['first_real_message_at'] = now()->toIso8601String();
            $team->forceFill(['settings' => $settings])->save();
            \Illuminate\Support\Facades\Log::info('onboarding.first_real_message_received', [
                'team_id'         => $team->id,
                'conversation_id' => $conversation->id,
                'message_id'      => $message->id,
            ]);
        });
    }
}
