<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Billable;

class Team extends Model
{
    use Billable;

    protected $attributes = [
        'ai_enabled' => false,
    ];

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'subscription_plan',
        'subscription_status',
        'ai_enabled',
        'ai_disabled_at',
        'ai_credits_used',
        'ai_credits_limit',
        'settings',
        'ai_memory',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'ai_disabled_at' => 'datetime',
            'ai_credits_used' => 'integer',
            'ai_credits_limit' => 'integer',
            'settings' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role', 'permissions')
            ->withTimestamps();
    }

    public function connectedAccounts(): HasMany
    {
        return $this->hasMany(ConnectedAccount::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function aiCommands(): HasMany
    {
        return $this->hasMany(AiCommand::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function isAiEnabled(): bool
    {
        return $this->ai_enabled;
    }

    public function toggleAi(bool $enabled): void
    {
        $this->update([
            'ai_enabled' => $enabled,
            'ai_disabled_at' => $enabled ? null : now(),
        ]);
    }

    public function getActivePages()
    {
        return Cache::remember("team.{$this->id}.active_pages", 300, function () {
            return $this->pages()->where('is_active', true)->get();
        });
    }

    public function clearActivePagesCache(): void
    {
        Cache::forget("team.{$this->id}.active_pages");
    }
}
