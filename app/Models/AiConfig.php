<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConfig extends Model
{
    protected $fillable = [
        'page_id',
        'team_id',
        'system_prompt',
        'business_description',
        'product_catalog',
        'pricing_info',
        'faq',
        'tone',
        'language',
        'response_delay_min_seconds',
        'response_delay_max_seconds',
        'working_hours',
        'timezone',
        'escalation_rules',
        'sales_methodology',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'product_catalog' => 'array',
            'pricing_info' => 'array',
            'faq' => 'array',
            'working_hours' => 'array',
            'escalation_rules' => 'array',
            'sales_methodology' => 'array',
            'is_active' => 'boolean',
            'response_delay_min_seconds' => 'integer',
            'response_delay_max_seconds' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getRandomDelay(): int
    {
        return rand($this->response_delay_min_seconds, $this->response_delay_max_seconds);
    }

    public function isWithinWorkingHours(): bool
    {
        if (empty($this->working_hours)) {
            return true;
        }

        $tz = $this->timezone ?? 'UTC';
        $now = Carbon::now($tz);
        $dayKey = strtolower($now->format('l')); // e.g. "monday"

        $dayConfig = $this->working_hours[$dayKey] ?? null;

        if (! $dayConfig || empty($dayConfig['enabled'])) {
            return false;
        }

        $start = Carbon::parse($dayConfig['start'], $tz);
        $end   = Carbon::parse($dayConfig['end'], $tz);

        // Cross-midnight range (e.g. 09:00 → 08:59): end is before start on the same day,
        // meaning the window wraps past midnight. Active when now >= start OR now <= end.
        if ($end->lt($start)) {
            return $now->gte($start) || $now->lte($end);
        }

        return $now->between($start, $end);
    }
}
