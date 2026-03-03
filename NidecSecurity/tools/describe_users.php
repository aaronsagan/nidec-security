<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $rows = db_fetch_all('DESCRIBE users');
    foreach ($rows as $r) {
        echo ($r['Field'] ?? '') . "\t" . ($r['Type'] ?? '') . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
