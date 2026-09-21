<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

/**
 * Default-country dropdown seed for the WhatsApp campaign wizard.
 *
 * Kept as a class constant (not a config file or DB seed) so the array is
 * loaded once by the opcache and costs zero I/O per request. The list is
 * intentionally short — the top markets we actually sell into plus the
 * anglophone tier — because a 250-country dropdown is worse UX than 30.
 */
final class SupportedCountries
{
    /** @var list<array{iso2: string, name: string, dial: string}> */
    public const LIST = [
        ['iso2' => 'EG', 'name' => 'Egypt',              'dial' => '+20'],
        ['iso2' => 'SA', 'name' => 'Saudi Arabia',       'dial' => '+966'],
        ['iso2' => 'AE', 'name' => 'United Arab Emirates', 'dial' => '+971'],
        ['iso2' => 'KW', 'name' => 'Kuwait',             'dial' => '+965'],
        ['iso2' => 'QA', 'name' => 'Qatar',              'dial' => '+974'],
        ['iso2' => 'BH', 'name' => 'Bahrain',            'dial' => '+973'],
        ['iso2' => 'OM', 'name' => 'Oman',               'dial' => '+968'],
        ['iso2' => 'JO', 'name' => 'Jordan',             'dial' => '+962'],
        ['iso2' => 'LB', 'name' => 'Lebanon',            'dial' => '+961'],
        ['iso2' => 'IQ', 'name' => 'Iraq',               'dial' => '+964'],
        ['iso2' => 'MA', 'name' => 'Morocco',            'dial' => '+212'],
        ['iso2' => 'DZ', 'name' => 'Algeria',            'dial' => '+213'],
        ['iso2' => 'TN', 'name' => 'Tunisia',            'dial' => '+216'],
        ['iso2' => 'LY', 'name' => 'Libya',              'dial' => '+218'],
        ['iso2' => 'SD', 'name' => 'Sudan',              'dial' => '+249'],
        ['iso2' => 'PS', 'name' => 'Palestine',          'dial' => '+970'],
        ['iso2' => 'YE', 'name' => 'Yemen',              'dial' => '+967'],
        ['iso2' => 'TR', 'name' => 'Turkey',             'dial' => '+90'],
        ['iso2' => 'GB', 'name' => 'United Kingdom',     'dial' => '+44'],
        ['iso2' => 'US', 'name' => 'United States',      'dial' => '+1'],
        ['iso2' => 'CA', 'name' => 'Canada',             'dial' => '+1'],
        ['iso2' => 'DE', 'name' => 'Germany',            'dial' => '+49'],
        ['iso2' => 'FR', 'name' => 'France',             'dial' => '+33'],
        ['iso2' => 'ES', 'name' => 'Spain',              'dial' => '+34'],
        ['iso2' => 'IT', 'name' => 'Italy',              'dial' => '+39'],
        ['iso2' => 'NL', 'name' => 'Netherlands',        'dial' => '+31'],
        ['iso2' => 'IN', 'name' => 'India',              'dial' => '+91'],
        ['iso2' => 'PK', 'name' => 'Pakistan',           'dial' => '+92'],
        ['iso2' => 'NG', 'name' => 'Nigeria',            'dial' => '+234'],
        ['iso2' => 'KE', 'name' => 'Kenya',              'dial' => '+254'],
        ['iso2' => 'ZA', 'name' => 'South Africa',       'dial' => '+27'],
    ];

    public static function has(string $iso2): bool
    {
        $iso2 = strtoupper($iso2);
        foreach (self::LIST as $c) {
            if ($c['iso2'] === $iso2) {
                return true;
            }
        }
        return false;
    }
}
