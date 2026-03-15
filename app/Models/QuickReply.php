<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickReply extends Model
{
    protected $fillable = ['team_id', 'title', 'content'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
