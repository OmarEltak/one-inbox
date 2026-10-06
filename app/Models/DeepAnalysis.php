<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deep Analysis run (spec §5).
 *
 * Represents one operator-triggered bulk analysis of up to ~1000 contacts.
 * Credit charge is persisted at dispatch time (so a user can't game by
 * closing the tab mid-analysis). Terminal status transitions:
 *
 *   queued → running → completed
 *                   → failed  (triggers AiCredits::refund)
 */
class DeepAnalysis extends Model
{
    public const STATUS_QUEUED    = 'queued';
    public const STATUS_RUNNING   = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';

    public const MODE_CUSTOMER_THEMES = 'customer_themes';
    public const MODE_AGENT_AUDIT     = 'agent_audit';

    protected $fillable = [
        'team_id',
        'triggered_by_user_id',
        'mode',
        'cohort_filter',
        'cohort_size',
        'credits_charged',
        'status',
        'result_json',
        'token_usage',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'cohort_filter'   => 'array',
            'result_json'     => 'array',
            'token_usage'     => 'array',
            'cohort_size'     => 'integer',
            'credits_charged' => 'integer',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
