<?php
$files = [
    __DIR__ . '/../public/assets/images/internal-logo.png',
    __DIR__ . '/../public/assets/images/external-logo.png',
];

foreach ($files as $path) {
    $label = str_replace(['\\', __DIR__ . '/../'], ['/', ''], $path);
    if (!is_file($path)) {
        echo $label . ": missing\n";
        continue;
    }

    $data = file_get_contents($path);
    if ($data === false || strlen($data) < 64) {
        echo $label . ": unreadable\n";
        continue;
    }

    $sig = substr($data, 0, 8);
    if ($sig !== "\x89PNG\r\n\x1a\n") {
        echo $label . ": not png\n";
        continue;
    }

    $ihdrPos = strpos($data, 'IHDR');
    if ($ihdrPos === false || $ihdrPos + 17 >= strlen($data)) {
        echo $label . ": missing IHDR\n";
        continue;
    }

    $w = unpack('N', substr($data, $ihdrPos + 4, 4))[1];
    $h = unpack('N', substr($data, $ihdrPos + 8, 4))[1];
    $bitDepth = ord($data[$ihdrPos + 12]);
    $colorType = ord($data[$ihdrPos + 13]);

    // Color types: 0=grayscale, 2=RGB, 3=indexed, 4=grayscale+alpha, 6=RGBA
    echo $label . ": {$w}x{$h} bit={$bitDepth} colorType={$colorType}\n";
}
