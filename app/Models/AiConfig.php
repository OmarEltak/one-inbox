<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConfig extends Model
{
    // Sales goal presets — expand SALES_GOAL_PRESETS with new options in one place.
    public const GOAL_INFO_ONLY    = 'info_only';
    public const GOAL_CAPTURE_DATA = 'capture_data';
    public const GOAL_BOOKING      = 'booking';
    public const GOAL_ECOMMERCE    = 'ecommerce';
    public const GOAL_CUSTOM       = 'custom';

    // Platform-hard ceiling on the per-contact daily AI reply cap.
    // Customer can set the cap anywhere from CONTACT_CAP_MIN to CONTACT_CAP_MAX;
    // requests above this are silently clamped at save time (defence in depth).
    public const CONTACT_CAP_MIN =  5;
    public const CONTACT_CAP_MAX = 50;

    protected $fillable = [
        'page_id',
        'team_id',
        'system_prompt',
        'business_description',
        'product_catalog',
        'pricing_info',
        'faq',
        'sales_goal_preset',
        'required_capture_fields',
        'escalation_keywords',
        'contact_ai_reply_cap',
        'tone',
        'language',
        'response_delay_min_seconds',
        'response_delay_max_seconds',
        'working_hours',
        'is_24_7',
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
            'required_capture_fields' => 'array',
            'escalation_keywords' => 'array',
            'contact_ai_reply_cap' => 'integer',
            'working_hours' => 'array',
            'is_24_7' => 'boolean',
            'escalation_rules' => 'array',
            'sales_methodology' => 'array',
            'is_active' => 'boolean',
            'response_delay_min_seconds' => 'integer',
            'response_delay_max_seconds' => 'integer',
        ];
    }

    /**
     * Default field-capture requirements per preset. Used both to populate the
     * UI when the customer picks a preset and to seed reasonable defaults for
     * customers who don't customize.
     *
     * @return array<int, array{key:string,label:string,type:string}>
     */
    public static function defaultCaptureFieldsFor(string $preset): array
    {
        return match ($preset) {
            self::GOAL_CAPTURE_DATA => [
                ['key' => 'email', 'label' => 'Email address', 'type' => 'email'],
            ],
            self::GOAL_BOOKING => [
                ['key' => 'name',           'label' => 'Full name',         'type' => 'text'],
                ['key' => 'phone',          'label' => 'Phone number',      'type' => 'phone'],
                ['key' => 'preferred_slot', 'label' => 'Preferred date/time','type' => 'text'],
            ],
            self::GOAL_ECOMMERCE => [
                ['key' => 'product',  'label' => 'Product',          'type' => 'text'],
                ['key' => 'quantity', 'label' => 'Quantity',         'type' => 'text'],
                ['key' => 'address',  'label' => 'Shipping address', 'type' => 'address'],
                ['key' => 'phone',    'label' => 'Phone number',     'type' => 'phone'],
            ],
            default => [],
        };
    }

    /**
     * Default escalation keywords per preset. Multilingual (AR + EN) so most
     * businesses in your target market get sensible behavior with zero setup.
     *
     * @return array<int, string>
     */
    public static function defaultEscalationKeywordsFor(string $preset): array
    {
        $shared = [
            'human', 'agent', 'representative', 'manager', 'complaint', 'refund', 'cancel',
            'مندوب', 'موظف', 'شكوى', 'إلغاء', 'استرجاع', 'مسؤول',
        ];

        return match ($preset) {
            self::GOAL_ECOMMERCE => array_merge($shared, [
                'order', 'buy', 'checkout', 'payment',
                'طلب', 'شراء', 'دفع', 'كاش',
            ]),
            self::GOAL_BOOKING => array_merge($shared, [
                'book', 'reservation', 'schedule', 'appointment',
                'حجز', 'موعد', 'تحديد',
            ]),
            default => $shared,
        };
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
        if ($this->is_24_7) {
            return true;
        }

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
