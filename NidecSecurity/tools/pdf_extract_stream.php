<?php
// Extract a PDF object stream and (optionally) decompress FlateDecode.
// Usage: php tools/pdf_extract_stream.php <pdf-file> <objNum>

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/pdf_extract_stream.php <pdf-file> <objNum>\n");
    exit(2);
}

$pdfPath = $argv[1];
$objNum = (int)$argv[2];

if (!is_file($pdfPath)) {
    fwrite(STDERR, "File not found: $pdfPath\n");
    exit(2);
}
if ($objNum <= 0) {
    fwrite(STDERR, "Invalid objNum\n");
    exit(2);
}

$data = file_get_contents($pdfPath);
$needle = $objNum . " 0 obj";
$pos = strpos($data, $needle);
if ($pos === false) {
    fwrite(STDERR, "Object not found: $needle\n");
    exit(3);
}

$endObj = strpos($data, "endobj", $pos);
if ($endObj === false) {
    fwrite(STDERR, "endobj not found for object\n");
    exit(3);
}

$objChunk = substr($data, $pos, $endObj - $pos);

$streamPos = strpos($objChunk, "stream");
$endStreamPos = strpos($objChunk, "endstream");
if ($streamPos === false || $endStreamPos === false) {
    fwrite(STDERR, "stream/endstream not found in object\n");
    exit(4);
}

$header = substr($objChunk, 0, $streamPos);

// Stream bytes start after the EOL following 'stream'
$streamStart = $streamPos + strlen('stream');
// Consume \r\n or \n
if (substr($objChunk, $streamStart, 2) === "\r\n") {
    $streamStart += 2;
} elseif (substr($objChunk, $streamStart, 1) === "\n") {
    $streamStart += 1;
}

$rawStream = substr($objChunk, $streamStart, $endStreamPos - $streamStart);

$isFlate = (strpos($header, '/FlateDecode') !== false);
$out = $rawStream;
$decompressed = false;

if ($isFlate) {
    $try = @gzuncompress($rawStream);
    if ($try !== false) {
        $out = $try;
        $decompressed = true;
    } else {
        $try2 = @gzinflate($rawStream);
        if ($try2 !== false) {
            $out = $try2;
            $decompressed = true;
        }
    }
}

$baseName = basename($pdfPath);
$baseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName);
$outDir = __DIR__ . '/out';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$outPath = $outDir . '/' . $baseName . '_obj' . $objNum . ($decompressed ? '_decompressed' : '_raw') . '.txt';
file_put_contents($outPath, $out);

echo "Wrote: $outPath\n";
echo "FilterFlate: " . ($isFlate ? 'yes' : 'no') . "\n";
echo "Decompressed: " . ($decompressed ? 'yes' : 'no') . "\n";
echo "Bytes: " . strlen($out) . "\n";
