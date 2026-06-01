<?php

namespace App\Services;

/**
 * WhatsApp Cloud API per-message pricing.
 *
 * SOURCE OF TRUTH (verify quarterly):
 *   https://developers.facebook.com/docs/whatsapp/pricing
 *
 * MODEL (effective July 2025 onward):
 *   Meta charges per *delivered template message*, NOT per conversation.
 *   Service messages (replies sent inside the customer's 24-hour window) are FREE.
 *   The four template categories are: marketing, utility, authentication, service.
 *
 * UPDATING THIS TABLE:
 *   Rates change roughly quarterly. Update self::RATES_USD below and bump
 *   self::RATES_LAST_VERIFIED. Format is country ISO-2 → category → USD per message.
 *   For countries not in the table we fall back to self::DEFAULT_RATES_USD,
 *   which uses the global "Rest of World" tier Meta publishes.
 *
 * NOTE FOR THE DASHBOARD:
 *   These prices are billed by Meta directly to the WhatsApp Business Account
 *   owner — NOT by OT1-Pro. Always surface that disclaimer in any UI that
 *   displays an estimate.
 */
final class WhatsAppCloudPricing
{
    public const RATES_LAST_VERIFIED = '2026-02-01';

    /** Rates Meta uses when the recipient's country isn't on a special list. */
    private const DEFAULT_RATES_USD = [
        'marketing'      => 0.0250,
        'utility'        => 0.0085,
        'authentication' => 0.0140,
        'service'        => 0.0,
    ];

    /**
     * USD price per delivered template message, by ISO-2 country code.
     * Service category is always 0 (free inside 24-hour window).
     * Numbers below are representative tier rates as of RATES_LAST_VERIFIED.
     */
    private const RATES_USD = [
        // ── MENA ──────────────────────────────────────────────
        'EG' => ['marketing' => 0.0273, 'utility' => 0.0064, 'authentication' => 0.0156, 'service' => 0.0],
        'AE' => ['marketing' => 0.0341, 'utility' => 0.0093, 'authentication' => 0.0212, 'service' => 0.0],
        'SA' => ['marketing' => 0.0386, 'utility' => 0.0103, 'authentication' => 0.0235, 'service' => 0.0],
        'KW' => ['marketing' => 0.0381, 'utility' => 0.0105, 'authentication' => 0.0231, 'service' => 0.0],
        'QA' => ['marketing' => 0.0399, 'utility' => 0.0111, 'authentication' => 0.0241, 'service' => 0.0],
        'JO' => ['marketing' => 0.0205, 'utility' => 0.0049, 'authentication' => 0.0125, 'service' => 0.0],
        'LB' => ['marketing' => 0.0188, 'utility' => 0.0046, 'authentication' => 0.0114, 'service' => 0.0],
        'MA' => ['marketing' => 0.0245, 'utility' => 0.0053, 'authentication' => 0.0149, 'service' => 0.0],
        'TN' => ['marketing' => 0.0163, 'utility' => 0.0040, 'authentication' => 0.0099, 'service' => 0.0],
        'IL' => ['marketing' => 0.0353, 'utility' => 0.0091, 'authentication' => 0.0215, 'service' => 0.0],
        'TR' => ['marketing' => 0.0085, 'utility' => 0.0028, 'authentication' => 0.0052, 'service' => 0.0],

        // ── North America ─────────────────────────────────────
        'US' => ['marketing' => 0.0250, 'utility' => 0.0085, 'authentication' => 0.0135, 'service' => 0.0],
        'CA' => ['marketing' => 0.0265, 'utility' => 0.0090, 'authentication' => 0.0140, 'service' => 0.0],
        'MX' => ['marketing' => 0.0436, 'utility' => 0.0114, 'authentication' => 0.0265, 'service' => 0.0],

        // ── South America ─────────────────────────────────────
        'BR' => ['marketing' => 0.0625, 'utility' => 0.0080, 'authentication' => 0.0315, 'service' => 0.0],
        'AR' => ['marketing' => 0.0617, 'utility' => 0.0163, 'authentication' => 0.0376, 'service' => 0.0],
        'CO' => ['marketing' => 0.0125, 'utility' => 0.0041, 'authentication' => 0.0076, 'service' => 0.0],
        'CL' => ['marketing' => 0.0889, 'utility' => 0.0234, 'authentication' => 0.0541, 'service' => 0.0],
        'PE' => ['marketing' => 0.0703, 'utility' => 0.0185, 'authentication' => 0.0428, 'service' => 0.0],

        // ── Europe ────────────────────────────────────────────
        'GB' => ['marketing' => 0.0529, 'utility' => 0.0220, 'authentication' => 0.0358, 'service' => 0.0],
        'DE' => ['marketing' => 0.1365, 'utility' => 0.0550, 'authentication' => 0.0768, 'service' => 0.0],
        'FR' => ['marketing' => 0.1432, 'utility' => 0.0570, 'authentication' => 0.0805, 'service' => 0.0],
        'ES' => ['marketing' => 0.0648, 'utility' => 0.0277, 'authentication' => 0.0379, 'service' => 0.0],
        'IT' => ['marketing' => 0.0691, 'utility' => 0.0301, 'authentication' => 0.0405, 'service' => 0.0],
        'NL' => ['marketing' => 0.1597, 'utility' => 0.0680, 'authentication' => 0.0975, 'service' => 0.0],
        'PT' => ['marketing' => 0.0681, 'utility' => 0.0260, 'authentication' => 0.0395, 'service' => 0.0],
        'RU' => ['marketing' => 0.0721, 'utility' => 0.0238, 'authentication' => 0.0421, 'service' => 0.0],

        // ── Asia ──────────────────────────────────────────────
        'IN' => ['marketing' => 0.0091, 'utility' => 0.0030, 'authentication' => 0.0009, 'service' => 0.0],
        'ID' => ['marketing' => 0.0435, 'utility' => 0.0118, 'authentication' => 0.0265, 'service' => 0.0],
        'PK' => ['marketing' => 0.0507, 'utility' => 0.0143, 'authentication' => 0.0309, 'service' => 0.0],
        'BD' => ['marketing' => 0.0353, 'utility' => 0.0098, 'authentication' => 0.0214, 'service' => 0.0],
        'PH' => ['marketing' => 0.0211, 'utility' => 0.0058, 'authentication' => 0.0128, 'service' => 0.0],
        'MY' => ['marketing' => 0.0860, 'utility' => 0.0229, 'authentication' => 0.0530, 'service' => 0.0],
        'SG' => ['marketing' => 0.0805, 'utility' => 0.0220, 'authentication' => 0.0510, 'service' => 0.0],
        'JP' => ['marketing' => 0.0650, 'utility' => 0.0215, 'authentication' => 0.0395, 'service' => 0.0],

        // ── Africa ────────────────────────────────────────────
        'ZA' => ['marketing' => 0.0298, 'utility' => 0.0079, 'authentication' => 0.0181, 'service' => 0.0],
        'NG' => ['marketing' => 0.0516, 'utility' => 0.0136, 'authentication' => 0.0314, 'service' => 0.0],
        'KE' => ['marketing' => 0.0211, 'utility' => 0.0068, 'authentication' => 0.0128, 'service' => 0.0],
        'GH' => ['marketing' => 0.0312, 'utility' => 0.0083, 'authentication' => 0.0190, 'service' => 0.0],

        // ── Oceania ───────────────────────────────────────────
        'AU' => ['marketing' => 0.0473, 'utility' => 0.0125, 'authentication' => 0.0288, 'service' => 0.0],
        'NZ' => ['marketing' => 0.0454, 'utility' => 0.0120, 'authentication' => 0.0277, 'service' => 0.0],
    ];

    /**
     * E.164-prefix → ISO-2 mapping. Longer prefixes win on conflict (e.g. +1 vs +1242).
     * Trimmed to the markets in RATES_USD plus a few common ones for fallback resolution.
     */
    private const PREFIX_TO_ISO2 = [
        // 4-digit prefixes (special carve-outs first so they match before the 1-digit ones)
        '1242' => 'BS', '1246' => 'BB', '1268' => 'AG', '1284' => 'VG',
        '1340' => 'VI', '1345' => 'KY', '1441' => 'BM', '1473' => 'GD',
        '1664' => 'MS', '1671' => 'GU', '1684' => 'AS', '1721' => 'SX',
        '1758' => 'LC', '1767' => 'DM', '1784' => 'VC', '1809' => 'DO',
        '1829' => 'DO', '1849' => 'DO', '1868' => 'TT', '1869' => 'KN',
        '1876' => 'JM', '1939' => 'PR',
        // 3-digit prefixes
        '212' => 'MA', '213' => 'DZ', '216' => 'TN', '218' => 'LY', '220' => 'GM',
        '221' => 'SN', '222' => 'MR', '223' => 'ML', '224' => 'GN', '225' => 'CI',
        '226' => 'BF', '227' => 'NE', '228' => 'TG', '229' => 'BJ', '230' => 'MU',
        '231' => 'LR', '232' => 'SL', '233' => 'GH', '234' => 'NG', '235' => 'TD',
        '236' => 'CF', '237' => 'CM', '238' => 'CV', '239' => 'ST', '240' => 'GQ',
        '241' => 'GA', '242' => 'CG', '243' => 'CD', '244' => 'AO', '245' => 'GW',
        '246' => 'IO', '247' => 'AC', '248' => 'SC', '249' => 'SD', '250' => 'RW',
        '251' => 'ET', '252' => 'SO', '253' => 'DJ', '254' => 'KE', '255' => 'TZ',
        '256' => 'UG', '257' => 'BI', '258' => 'MZ', '260' => 'ZM', '261' => 'MG',
        '262' => 'RE', '263' => 'ZW', '264' => 'NA', '265' => 'MW', '266' => 'LS',
        '267' => 'BW', '268' => 'SZ', '269' => 'KM', '290' => 'SH', '291' => 'ER',
        '297' => 'AW', '298' => 'FO', '299' => 'GL',
        '350' => 'GI', '351' => 'PT', '352' => 'LU', '353' => 'IE', '354' => 'IS',
        '355' => 'AL', '356' => 'MT', '357' => 'CY', '358' => 'FI', '359' => 'BG',
        '370' => 'LT', '371' => 'LV', '372' => 'EE', '373' => 'MD', '374' => 'AM',
        '375' => 'BY', '376' => 'AD', '377' => 'MC', '378' => 'SM', '380' => 'UA',
        '381' => 'RS', '382' => 'ME', '385' => 'HR', '386' => 'SI', '387' => 'BA',
        '389' => 'MK',
        '420' => 'CZ', '421' => 'SK', '423' => 'LI',
        '500' => 'FK', '501' => 'BZ', '502' => 'GT', '503' => 'SV', '504' => 'HN',
        '505' => 'NI', '506' => 'CR', '507' => 'PA', '508' => 'PM', '509' => 'HT',
        '590' => 'GP', '591' => 'BO', '592' => 'GY', '593' => 'EC', '594' => 'GF',
        '595' => 'PY', '596' => 'MQ', '597' => 'SR', '598' => 'UY', '599' => 'CW',
        '670' => 'TL', '672' => 'NF', '673' => 'BN', '674' => 'NR', '675' => 'PG',
        '676' => 'TO', '677' => 'SB', '678' => 'VU', '679' => 'FJ', '680' => 'PW',
        '681' => 'WF', '682' => 'CK', '683' => 'NU', '685' => 'WS', '686' => 'KI',
        '687' => 'NC', '688' => 'TV', '689' => 'PF', '690' => 'TK', '691' => 'FM',
        '692' => 'MH',
        '850' => 'KP', '852' => 'HK', '853' => 'MO', '855' => 'KH', '856' => 'LA',
        '880' => 'BD', '886' => 'TW',
        '960' => 'MV', '961' => 'LB', '962' => 'JO', '963' => 'SY', '964' => 'IQ',
        '965' => 'KW', '966' => 'SA', '967' => 'YE', '968' => 'OM', '970' => 'PS',
        '971' => 'AE', '972' => 'IL', '973' => 'BH', '974' => 'QA', '975' => 'BT',
        '976' => 'MN', '977' => 'NP', '992' => 'TJ', '993' => 'TM', '994' => 'AZ',
        '995' => 'GE', '996' => 'KG', '998' => 'UZ',
        // 2-digit prefixes
        '20' => 'EG', '27' => 'ZA', '30' => 'GR', '31' => 'NL', '32' => 'BE',
        '33' => 'FR', '34' => 'ES', '36' => 'HU', '39' => 'IT', '40' => 'RO',
        '41' => 'CH', '43' => 'AT', '44' => 'GB', '45' => 'DK', '46' => 'SE',
        '47' => 'NO', '48' => 'PL', '49' => 'DE', '51' => 'PE', '52' => 'MX',
        '53' => 'CU', '54' => 'AR', '55' => 'BR', '56' => 'CL', '57' => 'CO',
        '58' => 'VE', '60' => 'MY', '61' => 'AU', '62' => 'ID', '63' => 'PH',
        '64' => 'NZ', '65' => 'SG', '66' => 'TH', '81' => 'JP', '82' => 'KR',
        '84' => 'VN', '86' => 'CN', '90' => 'TR', '91' => 'IN', '92' => 'PK',
        '93' => 'AF', '94' => 'LK', '95' => 'MM', '98' => 'IR',
        // 1-digit prefixes (last so they don't shadow longer ones)
        '1' => 'US', '7' => 'RU',
    ];

    public const CATEGORIES = ['marketing', 'utility', 'authentication', 'service'];

    /**
     * Resolve an ISO-2 country code from a phone number in any reasonable format.
     * Returns null if we can't confidently match it.
     */
    public static function countryFromPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        // Try 4 → 3 → 2 → 1 digit prefixes (longest match wins)
        for ($len = 4; $len >= 1; $len--) {
            $prefix = substr($digits, 0, $len);
            if (isset(self::PREFIX_TO_ISO2[$prefix])) {
                return self::PREFIX_TO_ISO2[$prefix];
            }
        }
        return null;
    }

    /** USD price per single message for one country + category. */
    public static function rate(string $iso2, string $category): float
    {
        $iso2 = strtoupper($iso2);
        $category = strtolower($category);
        if (! in_array($category, self::CATEGORIES, true)) {
            return 0.0;
        }
        return self::RATES_USD[$iso2][$category]
            ?? self::DEFAULT_RATES_USD[$category]
            ?? 0.0;
    }

    /**
     * Estimate the total USD cost of sending one template-category message
     * to each phone number in the list. Returns:
     *   [
     *     'total_usd'      => 12.34,
     *     'recipient_count'=> 500,
     *     'breakdown'      => [ 'EG' => ['count'=>300,'rate'=>0.0273,'subtotal'=>8.19], ... ],
     *     'unknown_country'=> 12,   // recipients we couldn't classify (charged at default rate)
     *     'category'       => 'marketing',
     *   ]
     */
    public static function estimate(array $phones, string $category): array
    {
        $category = strtolower($category);
        if (! in_array($category, self::CATEGORIES, true)) {
            $category = 'marketing';
        }

        $countries = [];
        $unknown = 0;
        foreach ($phones as $phone) {
            $iso = self::countryFromPhone($phone);
            if ($iso === null) {
                $unknown++;
                $iso = '_UNKNOWN_';
            }
            $countries[$iso] = ($countries[$iso] ?? 0) + 1;
        }

        $breakdown = [];
        $total = 0.0;
        foreach ($countries as $iso => $count) {
            $rate = $iso === '_UNKNOWN_'
                ? (self::DEFAULT_RATES_USD[$category] ?? 0.0)
                : self::rate($iso, $category);
            $subtotal = $rate * $count;
            $total += $subtotal;
            $breakdown[$iso] = [
                'count' => $count,
                'rate' => $rate,
                'subtotal' => $subtotal,
            ];
        }

        // Sort breakdown by subtotal desc so the biggest cost driver shows first.
        uasort($breakdown, fn ($a, $b) => $b['subtotal'] <=> $a['subtotal']);

        return [
            'total_usd' => round($total, 4),
            'recipient_count' => count($phones),
            'breakdown' => $breakdown,
            'unknown_country' => $unknown,
            'category' => $category,
            'rates_last_verified' => self::RATES_LAST_VERIFIED,
        ];
    }
}
