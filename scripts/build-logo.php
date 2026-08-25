<?php
declare(strict_types=1);

// One-off logo build: takes the source JPEG, pads it onto a white square
// canvas, then writes logo.png (1024), apple-touch-icon.png (180),
// favicon-32.png (32), favicon-16.png (16). Run: php scripts/build-logo.php <src>
// Default src: C:\Users\NanoChip\Downloads\Ot1 Pro logo without text.jpeg

$src = $argv[1] ?? 'C:\\Users\\NanoChip\\Downloads\\Ot1 Pro logo without text.jpeg';
if (!is_file($src)) {
    fwrite(STDERR, "Source not found: {$src}\n");
    exit(1);
}

$publicDir = __DIR__ . '/../public';
if (!is_dir($publicDir)) {
    fwrite(STDERR, "public/ not found next to scripts/\n");
    exit(1);
}

$source = imagecreatefromjpeg($src);
if ($source === false) {
    fwrite(STDERR, "imagecreatefromjpeg failed\n");
    exit(1);
}

$w = imagesx($source);
$h = imagesy($source);
$side = max($w, $h);

// Build a square 1024x1024 canvas: white background, source centered.
$master = imagecreatetruecolor(1024, 1024);
$white  = imagecolorallocate($master, 255, 255, 255);
imagefilledrectangle($master, 0, 0, 1024, 1024, $white);

$squared = imagecreatetruecolor($side, $side);
imagefilledrectangle($squared, 0, 0, $side, $side, imagecolorallocate($squared, 255, 255, 255));
imagecopy($squared, $source, (int)(($side - $w) / 2), (int)(($side - $h) / 2), 0, 0, $w, $h);

imagecopyresampled($master, $squared, 0, 0, 0, 0, 1024, 1024, $side, $side);
imagedestroy($source);
imagedestroy($squared);

$outputs = [
    'logo.png'              => 1024,
    'apple-touch-icon.png'  => 180,
    'favicon-32.png'        => 32,
    'favicon-16.png'        => 16,
];

foreach ($outputs as $file => $size) {
    $canvas = imagecreatetruecolor($size, $size);
    imagefilledrectangle($canvas, 0, 0, $size, $size, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $master, 0, 0, 0, 0, $size, $size, 1024, 1024);
    $path = $publicDir . '/' . $file;
    if (!imagepng($canvas, $path, 6)) {
        fwrite(STDERR, "Failed to write {$path}\n");
        exit(1);
    }
    imagedestroy($canvas);
    echo "wrote {$file} ({$size}x{$size})\n";
}

imagedestroy($master);
echo "done\n";
