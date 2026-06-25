<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTransfer extends Model
{
    protected $fillable = [
        'superseded_page_id',
        'superseded_by_page_id',
        'from_team_id',
        'to_team_id',
        'actor_user_id',
        'reason',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function supersededPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'superseded_page_id');
    }

    public function supersededByPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'superseded_by_page_id');
    }
}
