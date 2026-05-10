<?php

declare(strict_types=1);

/**
 * Generate the social sharing image (og-image.png).
 *
 * Run: php scripts/generate-og-image.php
 *
 * Produces public/og-image.png at 1200x630 — the size Facebook, LinkedIn,
 * X/Twitter, and Slack scrape for share previews. Uses GD (bundled with
 * Laravel Herd's PHP) so no extra dependency is required.
 */

const OUT = __DIR__ . '/../public/og-image.png';
const WIDTH = 1200;
const HEIGHT = 630;

$im = imagecreatetruecolor(WIDTH, HEIGHT);

// Vertical gradient — deep indigo into purple, matching the SVG palette.
for ($y = 0; $y < HEIGHT; $y++) {
    $t = $y / HEIGHT;
    $r = (int) (15 + (30 - 15) * $t);
    $g = (int) (15 + (27 - 15) * $t);
    $b = (int) (35 + (75 - 35) * $t);
    $color = imagecolorallocate($im, $r, $g, $b);
    imageline($im, 0, $y, WIDTH, $y, $color);
}

// Soft purple glow (top-left).
addRadialGlow($im, 240, 130, 360, [168, 85, 247], 0.4);
// Soft blue glow (bottom-right).
addRadialGlow($im, 1020, 500, 320, [59, 130, 246], 0.35);

$white = imagecolorallocate($im, 255, 255, 255);
$lightPurple = imagecolorallocate($im, 196, 181, 253);
$slate = imagecolorallocate($im, 203, 213, 225);
$accentPurple = imagecolorallocate($im, 168, 85, 247);

// Logo block — purple rounded square with "O".
$accentTop = imagecolorallocate($im, 168, 85, 247);
roundedRect($im, 80, 80, 80 + 56, 80 + 56, 14, $accentTop);

// Use bundled font 5 (largest GD bitmap font) for the "O" mark.
imagestring($im, 5, 80 + 22, 80 + 19, 'O', $white);

// "OT1 Pro" wordmark.
$titleFont = findFont(['arialbd.ttf', 'arial.ttf', 'segoeuib.ttf', 'segoeui.ttf']);

if ($titleFont !== null) {
    imagettftext($im, 26, 0, 80 + 80, 80 + 36, $white, $titleFont, 'OT1 Pro');
} else {
    imagestring($im, 5, 80 + 80, 80 + 18, 'OT1 Pro', $white);
}

// Headline — three lines, the middle one in accent purple.
$lineHeight = 84;
$startY = 280;
if ($titleFont !== null) {
    imagettftext($im, 56, 0, 80, $startY, $white, $titleFont, 'Every message.');
    imagettftext($im, 56, 0, 80, $startY + $lineHeight, $accentPurple, $titleFont, 'One inbox.');
    imagettftext($im, 56, 0, 80, $startY + 2 * $lineHeight, $white, $titleFont, 'Zero missed sales.');
} else {
    // Fallback when no TTF font is available — bigger bitmap font tile.
    imagestring($im, 5, 80, $startY - 14, 'Every message.', $white);
    imagestring($im, 5, 80, $startY - 14 + $lineHeight, 'One inbox.', $accentPurple);
    imagestring($im, 5, 80, $startY - 14 + 2 * $lineHeight, 'Zero missed sales.', $white);
}

// Subline.
$subFont = findFont(['arial.ttf', 'segoeui.ttf']);
$sublineY = 560;
$subline = 'WhatsApp · Instagram · Facebook · Telegram + AI Sales Responder';
if ($subFont !== null) {
    imagettftext($im, 18, 0, 80, $sublineY, $slate, $subFont, $subline);
} else {
    imagestring($im, 4, 80, $sublineY - 12, $subline, $slate);
}

// Platform dots cluster (top-right).
drawPlatformDot($im, 960, 110, 36, [37, 211, 102], 'W');   // WhatsApp green
drawPlatformDot($im, 890, 150, 32, [225, 48, 108], 'I');   // Instagram pink
drawPlatformDot($im, 1030, 150, 32, [24, 119, 242], 'F');  // Facebook blue
drawPlatformDot($im, 960, 195, 32, [0, 136, 204], 'T');    // Telegram blue

if (! is_dir(dirname(OUT))) {
    mkdir(dirname(OUT), 0775, true);
}

imagepng($im, OUT, 9);
imagedestroy($im);

echo 'Wrote ' . OUT . ' (' . filesize(OUT) . ' bytes)' . PHP_EOL;

function addRadialGlow($im, int $cx, int $cy, int $radius, array $rgb, float $alpha): void
{
    $steps = 40;
    for ($i = $steps; $i > 0; $i--) {
        $t = $i / $steps;
        $r = (int) ($radius * $t);
        $a = (int) (127 - 127 * $alpha * (1 - $t));
        $c = imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], min(127, $a));
        imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $c);
    }
}

function roundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
{
    imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

function drawPlatformDot($im, int $cx, int $cy, int $r, array $rgb, string $letter): void
{
    $fill = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $fill);

    $font = findFont(['arialbd.ttf', 'arial.ttf', 'segoeuib.ttf', 'segoeui.ttf']);
    if ($font !== null) {
        $box = imagettfbbox(28, 0, $font, $letter);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        imagettftext(
            $im,
            28,
            0,
            $cx - (int) ($textWidth / 2),
            $cy + (int) ($textHeight / 2) - 2,
            $white,
            $font,
            $letter
        );
    } else {
        imagestring($im, 5, $cx - 4, $cy - 7, $letter, $white);
    }
}

function findFont(array $candidates): ?string
{
    $dirs = [
        'C:\\Windows\\Fonts',
        '/usr/share/fonts',
        '/usr/share/fonts/truetype/dejavu',
        '/Library/Fonts',
    ];

    foreach ($dirs as $dir) {
        if (! is_dir($dir)) {
            continue;
        }
        foreach ($candidates as $name) {
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (is_file($path)) {
                return $path;
            }
        }
    }

    // Fallback: scan recursively only under common Linux truetype paths.
    foreach (['/usr/share/fonts/truetype'] as $root) {
        if (! is_dir($root)) {
            continue;
        }
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iter as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['ttf'], true)) {
                return $file->getPathname();
            }
        }
    }

    return null;
}
