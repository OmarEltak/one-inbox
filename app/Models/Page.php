<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Page extends Model
{
    protected $fillable = [
        'connected_account_id',
        'team_id',
        'platform',
        'platform_page_id',
        'name',
        'avatar',
        'page_access_token',
        'category',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'page_access_token' => 'encrypted',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }
}
