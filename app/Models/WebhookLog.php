<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'team_id',
        'platform',
        'event_type',
        'payload',
        'processed',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function markProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'processed' => false,
            'error' => $error,
        ]);
    }
}
