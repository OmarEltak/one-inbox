<?php
/**
 * Extract every __() key from a blade file (handles escaped quotes)
 * and diff against lang/ar.json to list what's missing.
 *
 * Usage: php scripts/audit-i18n.php resources/views/pages/ai-campaign-manager.blade.php
 */
$file = $argv[1] ?? 'resources/views/pages/ai-campaign-manager.blade.php';
$blade = file_get_contents($file);

// Single-quoted string with optional escaped '
$re1 = "/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/";
// Double-quoted string with optional escaped "
$re2 = '/__\(\s*"((?:[^"\\\\]|\\\\.)*)"/';

preg_match_all($re1, $blade, $m1);
preg_match_all($re2, $blade, $m2);

$rawKeys = array_merge($m1[1] ?? [], $m2[1] ?? []);
$keys = [];
foreach ($rawKeys as $k) {
    // Unescape
    $keys[] = str_replace(["\\'", '\\"', '\\\\'], ["'", '"', '\\'], $k);
}
$keys = array_values(array_unique($keys));
sort($keys);

$ar = json_decode(file_get_contents(__DIR__ . '/../lang/ar.json'), true);
$missing = [];
foreach ($keys as $k) {
    if (!isset($ar[$k]) || $ar[$k] === $k) $missing[] = $k;
}

echo count($keys) . " total keys in $file\n";
echo count($missing) . " missing / untranslated:\n\n";
foreach ($missing as $k) echo '>>> ' . $k . "\n";
