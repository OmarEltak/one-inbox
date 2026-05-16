<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    public const STATUS_PENDING      = 'pending';
    public const STATUS_SENDING      = 'sending';
    public const STATUS_SENT         = 'sent';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_BOUNCED      = 'bounced';
    public const STATUS_OPENED       = 'opened';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'email',
        'name',
        'custom_fields',
        'status',
        'attempts',
        'last_error',
        'scheduled_at',
        'sent_at',
        'opened_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'attempts'      => 'integer',
            'scheduled_at'  => 'datetime',
            'sent_at'       => 'datetime',
            'opened_at'     => 'datetime',
            'failed_at'     => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
