<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'current_team_id',
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

    public function hasPermission(string $permission): bool
    {
        $team = $this->currentTeam;

        if (! $team) {
            return false;
        }

        if ($this->isOwnerOf($team)) {
            return true;
        }

        $member = $this->teams()->where('team_id', $team->id)->first();

        if (! $member) {
            return false;
        }

        $raw = $member->pivot->permissions;
        $permissions = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);

        return in_array($permission, $permissions);
    }

    public function canManageAdmins(): bool
    {
        return $this->isHeadAdmin() || $this->hasPermission('manage-admins');
    }
}
