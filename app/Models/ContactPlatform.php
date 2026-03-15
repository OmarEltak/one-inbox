<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactPlatform extends Model
{
    protected $table = 'contact_platform';

    protected $fillable = [
        'contact_id',
        'platform',
        'platform_contact_id',
        'platform_name',
        'platform_avatar',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
