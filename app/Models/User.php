<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'current_team_id',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role', 'permissions')
            ->withTimestamps();
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function switchTeam(Team $team): void
    {
        $this->update(['current_team_id' => $team->id]);
    }

    public function isOwnerOf(Team $team): bool
    {
        return $this->id === $team->owner_id;
    }

    public function roleIn(Team $team): ?string
    {
        return $this->teams()->where('team_id', $team->id)->first()?->pivot->role;
    }

    public function isHeadAdmin(): bool
    {
        $team = $this->currentTeam;

        return $team && $this->isOwnerOf($team);
    }

    /**
     * Request-scoped cache of the user's permission SET on the current team.
     * The sidebar calls hasPermission() 8+ times per render; without this,
     * every non-owner team member paid 8 DB queries just to decide which nav
     * items to show. Reset implicitly on each new request (new model instance).
     *
     * @var array<string, mixed>|null  Null = not yet loaded for this request.
     */
    private ?array $permissionCache = null;

    /**
     * Returns:
     *   - ['*']            if the user is the team owner (short-circuits every check)
     *   - string[]         the member's explicit permissions list (possibly empty)
     *   - null             if there's no current team or the user isn't a member
     *
     * @return list<string>|null
     */
    private function loadCurrentTeamPermissions(): ?array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache['perms'];
        }

        $team = $this->currentTeam;
        if (! $team) {
            $this->permissionCache = ['perms' => null];
            return null;
        }

        if ($this->isOwnerOf($team)) {
            $this->permissionCache = ['perms' => ['*']];
            return ['*'];
        }

        $member = $this->teams()->where('team_id', $team->id)->first();
        if (! $member) {
            $this->permissionCache = ['perms' => null];
            return null;
        }

        $raw = $member->pivot->permissions;
        $perms = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);
        $perms = array_values(array_filter($perms, 'is_string'));

        $this->permissionCache = ['perms' => $perms];
        return $perms;
    }

    public function hasPermission(string $permission): bool
    {
        $perms = $this->loadCurrentTeamPermissions();
        if ($perms === null) {
            return false;
        }
        if ($perms === ['*']) {
            return true;
        }
        return in_array($permission, $perms, true);
    }

    public function canManageAdmins(): bool
    {
        return $this->isHeadAdmin() || $this->hasPermission('manage-admins');
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
