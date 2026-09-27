<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One shareable referral code per team.
 *
 * When another team redeems it, `referred_team_id`, `redeemed_at`,
 * `discount_percent` and `reciprocal_discount_percent` are populated with
 * a SNAPSHOT of the current `config('referrals.*')` values — later config
 * changes must NOT retroactively rewrite past redemptions.
 */
class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'referrer_team_id',
        'referred_team_id',
        'redeemed_at',
        'discount_percent',
        'reciprocal_discount_percent',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
            'discount_percent' => 'decimal:2',
            'reciprocal_discount_percent' => 'decimal:2',
        ];
    }

    public function referrerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'referrer_team_id');
    }

    public function referredTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'referred_team_id');
    }

    /**
     * Active = not yet redeemed and not expired.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('redeemed_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Generate a unique code using config prefix + random alphanumerics.
     * Retries until unique (bounded to a sane cap; collisions are rare at
     * 8-char default length).
     */
    public static function generateCode(): string
    {
        $prefix = (string) config('referrals.code_prefix', '');
        $length = max(4, (int) config('referrals.code_length', 8));

        for ($i = 0; $i < 20; $i++) {
            $random = strtoupper(Str::random($length));
            $code = $prefix === '' ? $random : "{$prefix}-{$random}";
            $code = substr($code, 0, 20); // enforce column limit

            if (! static::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate a unique referral code after 20 attempts.');
    }
}
