<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'avatar',
        'email',
        'phone',
        'lead_score',
        'lead_status',
        'score_history',
        'tags',
        'first_seen_at',
        'last_interaction_at',
        'ai_replies_today',
        'ai_replies_reset_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'lead_score' => 'integer',
            'score_history' => 'array',
            'tags' => 'array',
            'first_seen_at' => 'datetime',
            'last_interaction_at' => 'datetime',
            'ai_replies_today' => 'integer',
            'ai_replies_reset_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Lazy-reset the daily counter if the 24h window has expired.
     * Called before every increment so we don't need a cron job for windowing.
     * Returns true if the window was rolled (counter reset to 0), false otherwise.
     */
    public function ensureCapWindowIsCurrent(): bool
    {
        if ($this->ai_replies_reset_at !== null && $this->ai_replies_reset_at->isFuture()) {
            return false;
        }

        $this->forceFill([
            'ai_replies_today'    => 0,
            'ai_replies_reset_at' => now()->addHours(24),
        ])->save();

        return true;
    }

    /**
     * Check whether this contact can still receive an AI reply within their
     * daily cap. Rolls the window first if it has expired.
     */
    public function canReceiveAiReply(int $cap): bool
    {
        $this->ensureCapWindowIsCurrent();

        return $this->ai_replies_today < $cap;
    }

    /**
     * Bump the counter after a successful AI reply. Assumes canReceiveAiReply()
     * was checked immediately before — so the window is already fresh.
     */
    public function recordAiReply(): void
    {
        $this->increment('ai_replies_today');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function platforms(): HasMany
    {
        return $this->hasMany(ContactPlatform::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function scoreEvents(): HasMany
    {
        return $this->hasMany(LeadScoreEvent::class);
    }

    public function adjustScore(int $change, string $eventType, string $reason, ?int $conversationId = null, ?string $aiAnalysis = null): void
    {
        $newScore = max(0, min(100, $this->lead_score + $change));

        $this->update(['lead_score' => $newScore]);

        $this->scoreEvents()->create([
            'conversation_id' => $conversationId,
            'event_type' => $eventType,
            'score_change' => $change,
            'reason' => $reason,
            'ai_analysis' => $aiAnalysis,
        ]);

        $this->updateLeadStatus();
    }

    protected function updateLeadStatus(): void
    {
        $status = match (true) {
            $this->lead_score >= 71 => 'hot',
            $this->lead_score >= 31 => 'warm',
            $this->lead_score > 0 => 'cold',
            default => 'new',
        };

        if ($this->lead_status !== 'converted' && $this->lead_status !== 'lost') {
            $this->update(['lead_status' => $status]);
        }
    }

    public function getScoreColorAttribute(): string
    {
        return match (true) {
            $this->lead_score >= 86 => 'red',
            $this->lead_score >= 71 => 'orange',
            $this->lead_score >= 51 => 'yellow',
            $this->lead_score >= 26 => 'blue',
            default => 'gray',
        };
    }
}
