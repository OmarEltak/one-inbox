<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    protected $fillable = [
        'team_id',
        'full_name',
        'email',
        'plan',
        'bank_name',
        'bank_country',
        'txid',
        'receipt_path',
        'status',
        'notes',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'starter' => 'Starter — $29/mo',
            'pro'     => 'Pro — $79/mo',
            default   => ucfirst($this->plan),
        };
    }
}
