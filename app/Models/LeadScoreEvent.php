<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadScoreEvent extends Model
{
    protected $fillable = [
        'contact_id',
        'conversation_id',
        'event_type',
        'score_change',
        'reason',
        'ai_analysis',
    ];

    protected function casts(): array
    {
        return [
            'score_change' => 'integer',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
