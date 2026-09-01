<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Billable;

/**
 * ══ ARCHITECTURE REFERENCE §11, §13, §15 ══
 * READ docs/ARCHITECTURE.md before modifying:
 *   §11 canDispatchAi() — the single AI dispatch gate composed of toggle +
 *        plan quota + upstream pause. Every dispatch site consults this.
 *        Add new conditions HERE, do not scatter checks across the codebase.
 *   §13 markAiUpstreamPaused / aiUpstreamPauseReason — reason-aware pause
 *        that drives the banner's amber (quota) vs red (outage) rendering.
 *   §15 hasAnyConnection — checks active Page rows, NOT ConnectedAccount
 *        (WhatsApp QR, Telegram, Email have Pages but no ConnectedAccount).
 */
class Team extends Model
{
    use Billable, HasFactory;

    protected $attributes = [
        'ai_enabled' => false,
    ];

    protected $fillable = [
        'name',
        'slug',
        'owner_id',
        'subscription_plan',
        'subscription_status',
        'subscription_ends_at',
        'billing_cycle',
        'lemon_squeezy_id',
        'lemon_squeezy_customer_id',
        'ai_enabled',
        'ai_disabled_at',
        'ai_credits_used',
        'ai_credits_limit',
        'settings',
        'ai_memory',
        'audio_transcription_enabled',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'ai_disabled_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'ai_credits_used' => 'integer',
            'ai_credits_limit' => 'integer',
            'settings' => 'array',
            'audio_transcription_enabled' => 'boolean',
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

    private ?bool $hasAnyConnectionCache = null;

    public function hasAnyConnection(): bool
    {
        // Two-layer cache: request-scoped (this instance) then app cache. Both the
        // RequireConnection middleware and the sidebar call this on every request —
        // without cache that's 2 EXISTS queries per pageload. Invalidated by
        // clearActivePagesCache() which fires when a page is created/deactivated.
        if ($this->hasAnyConnectionCache !== null) {
            return $this->hasAnyConnectionCache;
        }

        return $this->hasAnyConnectionCache = Cache::remember(
            "team.{$this->id}.has_any_connection",
            300,
            fn () => Page::query()
                ->where('team_id', $this->id)
                ->where('is_active', true)
                ->exists()
        );
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

    /**
     * Whether AI is allowed to react to new messages right now.
     * Combines the team toggle, the plan quota, and any active upstream pause
     * (set when the AI provider signals quota exhaustion). If any is off,
     * no AI jobs should be queued. Single source of truth for dispatch sites.
     */
    public function canDispatchAi(): bool
    {
        return $this->isAiEnabled()
            && ! $this->isSubscriptionExpired()
            && \App\Http\Middleware\EnforcePlanLimits::hasAiCredits($this)
            && ! $this->isAiUpstreamLimited();
    }

    /**
     * True when a time-limited plan has lapsed. Null subscription_ends_at means
     * "no expiry" (free tier or manually granted forever), so it never blocks.
     * Super-admin grants a plan for N months by setting this timestamp; once
     * it's in the past AI dispatch stops until the admin renews.
     */
    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isPast();
    }

    /**
     * True while a temporary AI provider outage is remembered (typically 24h
     * from when the upstream API returned quota-exhausted). Cache-backed so
     * this survives page refreshes but auto-expires without any cleanup job.
     */
    public function isAiUpstreamLimited(): bool
    {
        return Cache::has($this->aiUpstreamPausedCacheKey());
    }

    /**
     * Pause AI dispatch for this team. Reason is stored alongside the flag
     * so the header banner can show accurate text ("quota reached" vs
     * "provider outage"). Defaults to 24h TTL — pass a shorter DateInterval
     * for transient outages that recover fast.
     */
    public function markAiUpstreamPaused(?\DateInterval $ttl = null, string $reason = 'quota'): void
    {
        Cache::put(
            $this->aiUpstreamPausedCacheKey(),
            ['at' => now()->toIso8601String(), 'reason' => $reason],
            $ttl ?? new \DateInterval('P1D'),
        );
    }

    /**
     * Why is AI paused? Returns 'quota' (default), 'outage', or null when
     * not paused. Used by the banner partial to render the right message.
     */
    public function aiUpstreamPauseReason(): ?string
    {
        $val = Cache::get($this->aiUpstreamPausedCacheKey());
        if (! $val) {
            return null;
        }
        // Back-compat: earlier cache entries stored just the ISO timestamp
        // as a string, not an array. Treat those as 'quota'.
        return is_array($val) ? ($val['reason'] ?? 'quota') : 'quota';
    }

    public function clearAiUpstreamPause(): void
    {
        Cache::forget($this->aiUpstreamPausedCacheKey());
    }

    protected function aiUpstreamPausedCacheKey(): string
    {
        return "ai_upstream_paused:{$this->id}";
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
        Cache::forget("team.{$this->id}.has_any_connection");
        Cache::forget("team.{$this->id}.inbox_sidebar_pages");
        Cache::forget("dashboard.{$this->id}");
        $this->hasAnyConnectionCache = null;
    }
}
