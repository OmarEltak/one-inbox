<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Comment extends Model
{
    use HasFactory;

    public const DECISION_REPLIED                = 'replied';
    public const DECISION_DM_ONLY                = 'dm_only';
    public const DECISION_RATE_LIMITED           = 'rate_limited';
    public const DECISION_FILTERED_OFF           = 'filtered_off';
    public const DECISION_FILTERED_MODE          = 'filtered_mode';
    public const DECISION_FILTERED_SCOPE         = 'filtered_scope';
    public const DECISION_FILTERED_KEYWORD       = 'filtered_keyword';
    public const DECISION_FILTERED_WORKING_HOURS = 'filtered_working_hours';
    public const DECISION_FILTERED_SELF          = 'filtered_self';
    public const DECISION_FILTERED_REPLY         = 'filtered_reply';
    public const DECISION_ERROR_GRAPH_API        = 'error_graph_api';
    public const DECISION_ERROR_AI               = 'error_ai';

    protected $fillable = [
        'page_id',
        'pages_post_id',
        'platform_comment_id',
        'parent_comment_id',
        'commenter_platform_id',
        'commenter_name',
        'text',
        'received_at',
        'decision',
        'decision_reason',
        'reply_text',
        'graph_reply_id',
        'dm_sent_at',
        'dm_graph_message_id',
        'graph_error',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'dm_sent_at'  => 'datetime',
            'graph_error' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function pagesPost(): BelongsTo
    {
        return $this->belongsTo(PagesPost::class);
    }

    /**
     * Phase 1 (comment-to-DM link) — every inbound DM Message whose
     * provenance resolves back to this Comment via `source_comment_id`.
     * Multiple rows are theoretically possible (same commenter keeps
     * replying inside the 72h match window), but in practice this is a
     * HasMany so Phase 3's Comments sidebar can show the full thread.
     */
    public function inboundMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'source_comment_id')
            ->orderBy('platform_sent_at');
    }

    /**
     * Phase 1 (comment-to-DM link) — the common "first DM reply" case.
     * Phase 2's inbox banner needs ONE message to deep-link into, so this
     * returns the earliest matching inbound Message. Null when the
     * commenter never replied to the DM.
     */
    public function inboundMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'source_comment_id')
            ->orderBy('platform_sent_at');
    }
}
