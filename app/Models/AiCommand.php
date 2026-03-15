<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCommand extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'command',
        'response',
        'action_taken',
        'contacts_affected',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'action_taken' => 'array',
            'contacts_affected' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
