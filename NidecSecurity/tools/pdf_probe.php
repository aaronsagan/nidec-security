<?php
// Simple PDF probe (no external libs): prints MediaBox and tries to locate page content streams.
// Usage: php tools/pdf_probe.php "file.pdf"

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/pdf_probe.php <pdf-file>\n");
    exit(2);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "File not found: $path\n");
    exit(2);
}

$data = file_get_contents($path);

if (preg_match('#/MediaBox\s*\[\s*([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s*\]#', $data, $m)) {
    $x0 = (float)$m[1];
    $y0 = (float)$m[2];
    $x1 = (float)$m[3];
    $y1 = (float)$m[4];
    $w = $x1 - $x0;
    $h = $y1 - $y0;
    $wi = $w / 72.0;
    $hi = $h / 72.0;
    echo "MediaBox: $x0 $y0 $x1 $y1 => {$w}x{$h} pt ({$wi}x{$hi} in)\n";
} else {
    echo "MediaBox: NOT FOUND\n";
}

// Print basic catalog/page count if present
if (preg_match('#/Count\s+(\d+)#', $data, $m)) {
    echo "PageCount (first /Count): {$m[1]}\n";
}

// Find object references for /Contents
if (preg_match_all('#/Contents\s+(\d+)\s+(\d+)\s+R#', $data, $all, PREG_SET_ORDER)) {
    $seen = [];
    foreach ($all as $mm) {
        $ref = $mm[1] . ' ' . $mm[2];
        if (isset($seen[$ref])) continue;
        $seen[$ref] = true;
        echo "ContentsRef: {$mm[1]} {$mm[2]} R\n";
    }
} else {
    echo "ContentsRef: NOT FOUND\n";
}
