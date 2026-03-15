<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectedAccount extends Model
{
    protected $fillable = [
        'team_id',
        'platform',
        'platform_user_id',
        'name',
        'email',
        'avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'metadata',
        'is_active',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'scopes' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'connected_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
