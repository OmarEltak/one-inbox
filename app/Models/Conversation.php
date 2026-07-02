<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ══ ARCHITECTURE REFERENCE §5 ══
 * READ docs/ARCHITECTURE.md §5 (Sales-Flow State Machine) before
 * modifying the stage constants, escalate(), complete(), or
 * isSalesStageActive(). Both automatic (SendAiResponse) and manual
 * (Inbox\Index) transitions call these helpers — divergence between
 * them will break the audit trail and downstream gates.
 */
class Conversation extends Model
{
    // Sales-flow lifecycle. Terminal stages (escalated | completed | spam) gate
    // SendAiResponse from firing — the AI stops reacting until a human resets
    // the stage or a new capture cycle begins.
    public const STAGE_ACTIVE    = 'active';
    public const STAGE_ESCALATED = 'escalated';
    public const STAGE_COMPLETED = 'completed';
    public const STAGE_SPAM      = 'spam';

    protected $fillable = [
        'page_id',
        'team_id',
        'platform',
        'platform_conversation_id',
        'contact_id',
        'status',
        'ai_paused',
        'sales_stage',
        'captured_data',
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
            'captured_data' => 'array',
            'labels' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Should AI keep reacting to messages on this conversation?
     * True only while the conversation is in the active stage and not paused.
     * Any terminal stage or explicit human takeover blocks the AI.
     */
    public function isSalesStageActive(): bool
    {
        return $this->sales_stage === self::STAGE_ACTIVE && ! $this->ai_paused;
    }

    /**
     * Mark this conversation as escalated (human needs to take over).
     * Pauses AI so no more replies fire until a human resets the state.
     */
    public function escalate(?string $reason = null): void
    {
        $labels = $this->labels ?? [];
        if (! in_array('escalated', $labels, true)) {
            $labels[] = 'escalated';
        }

        $this->update([
            'sales_stage' => self::STAGE_ESCALATED,
            'ai_paused'   => true,
            'labels'      => $labels,
            'metadata'    => array_merge($this->metadata ?? [], [
                'escalation_reason' => $reason,
                'escalated_at'      => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Mark this conversation as completed (goal reached — data captured).
     * Pauses AI so it doesn't keep pushing after the deal is done.
     */
    public function complete(?string $reason = null): void
    {
        $this->update([
            'sales_stage' => self::STAGE_COMPLETED,
            'ai_paused'   => true,
            'metadata'    => array_merge($this->metadata ?? [], [
                'completion_reason' => $reason,
                'completed_at'      => now()->toIso8601String(),
            ]),
        ]);
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
