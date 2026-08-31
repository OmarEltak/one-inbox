<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ══ ARCHITECTURE REFERENCE §5, §6 ══
 * READ docs/ARCHITECTURE.md §5 (Sales-Flow) + §6 (Contact Cap) before
 * modifying the goal presets, CONTACT_CAP_MIN/MAX, or
 * defaultCaptureFieldsFor/defaultEscalationKeywordsFor.
 *
 * Load-bearing: CONTACT_CAP_MAX = 50 is a platform-hard ceiling that
 * customers cannot exceed. Raising it silently is an anti-abuse regression.
 * The bilingual (AR + EN) defaults for escalation keywords are the "zero-
 * config sane behavior" that makes the goal presets work out of the box —
 * do not strip either language.
 */
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

    // Comments — Phase A (config only; ingestion + sending unlock in Phase B
    // once Meta App Review approves pages_manage_engagement + instagram_manage_comments).
    public const COMMENT_REPLY_OFF                      = 'off';
    public const COMMENT_REPLY_ALL                      = 'all';
    public const COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS = 'questions_and_complaints';
    public const COMMENT_REPLY_CUSTOM_KEYWORDS          = 'custom_keywords';

    public const COMMENT_DM_OFF                = 'off';
    public const COMMENT_DM_ALWAYS             = 'always';
    public const COMMENT_DM_ON_PURCHASE_INTENT = 'on_purchase_intent';

    public const COMMENT_SCOPE_FUTURE_ONLY = 'future_posts_only';
    public const COMMENT_SCOPE_ALL_POSTS   = 'all_posts';

    public const COMMENT_MAX_REPLIES_PER_POST_MIN =   1;
    public const COMMENT_MAX_REPLIES_PER_POST_MAX = 100;

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
        'escalate_on_media',
        'escalation_topics',
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
        'comment_settings',
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
            'escalate_on_media' => 'boolean',
            'escalation_topics' => 'array',
            'contact_ai_reply_cap' => 'integer',
            'working_hours' => 'array',
            'is_24_7' => 'boolean',
            'escalation_rules' => 'array',
            'sales_methodology' => 'array',
            'is_active' => 'boolean',
            'response_delay_min_seconds' => 'integer',
            'response_delay_max_seconds' => 'integer',
            'comment_settings' => 'array',
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

    /**
     * Safe defaults for the Comments tab. Every documented key is always present
     * so downstream Phase B code can rely on shape without null-checking.
     *
     * @return array{
     *     enabled: bool,
     *     enabled_at: string|null,
     *     reply_mode: string,
     *     reply_keywords: array<int, string>,
     *     dm_mode: string,
     *     dm_keywords: array<int, string>,
     *     reply_instructions: string,
     *     scope: string,
     *     max_ai_replies_per_post_per_day: int,
     * }
     */
    public static function defaultCommentSettings(): array
    {
        return [
            'enabled'                          => false,
            'enabled_at'                       => null,
            'reply_mode'                       => self::COMMENT_REPLY_OFF,
            'reply_keywords'                   => [],
            'dm_mode'                          => self::COMMENT_DM_OFF,
            'dm_keywords'                      => [],
            'reply_instructions'               => '',
            'scope'                            => self::COMMENT_SCOPE_FUTURE_ONLY,
            'max_ai_replies_per_post_per_day'  => 20,
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
