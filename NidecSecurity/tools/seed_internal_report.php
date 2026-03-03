<?php
/**
 * Seed a sample INTERNAL security report so you can preview the internal PDF template.
 *
 * Usage (PowerShell):
 *   php tools/seed_internal_report.php
 */

require_once __DIR__ . '/../includes/config.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    echo "Not found";
    exit;
}

function norm_security_type(?string $raw): string {
    $v = strtolower(trim((string)$raw));
    return in_array($v, ['internal', 'external'], true) ? $v : 'external';
}

function generate_security_report_no(): string {
    $year = date('Y');
    $prefix = 'SR-' . $year . '-';

    $row = db_fetch_one('SELECT report_no FROM reports WHERE report_no LIKE ? ORDER BY report_no DESC LIMIT 1', 's', [$prefix . '%']);
    $last = $row['report_no'] ?? null;
    $seq = 0;
    if ($last && preg_match('/^SR-' . preg_quote($year, '/') . '-(\d{4})$/', (string)$last, $m)) {
        $seq = (int)$m[1];
    }
    $seq++;
    return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
}

function random_suffix(int $len = 6): string {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}

try {
    // 1) Ensure an INTERNAL security user exists
    $internalUser = db_fetch_one(
        "SELECT id, name, username, security_type
         FROM users
         WHERE role = 'security'
           AND (account_status IS NULL OR account_status = 'active')
           AND security_type = 'internal'
         ORDER BY id ASC
         LIMIT 1"
    );

    $createdUser = false;
    $tempPassword = null;

    if (!$internalUser) {
        $username = 'internal_sec_' . random_suffix(5);
        $tempPassword = 'Temp' . random_suffix(7) . '!';
        $hash = password_hash($tempPassword, PASSWORD_DEFAULT);

        db_execute(
            "INSERT INTO users (name, username, password_hash, role, security_type, department_id, account_status)
             VALUES (?, ?, ?, 'security', 'internal', NULL, 'active')",
            'sss',
            ['Internal Security (Sample)', $username, $hash]
        );

        $uid = (int)db_last_insert_id();
        $internalUser = [
            'id' => $uid,
            'name' => 'Internal Security (Sample)',
            'username' => $username,
            'security_type' => 'internal'
        ];
        $createdUser = true;
    }

    // 2) Pick a valid department
    $dept = db_fetch_one("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    if (!$dept) {
        throw new RuntimeException('No active departments found. Create at least one department first.');
    }

    // 3) Insert a sample report
    $reportNo = generate_security_report_no();

    $subject = 'INTERNAL TEMPLATE PREVIEW — Sample Observation';
    $category = 'Safety';
    $location = 'Main Reception';
    $severity = 'medium';
    $details = "This is a sample INTERNAL security report created to preview the Internal PDF template.\n\nReplace this content with a real incident once available.";

    db_execute(
        "INSERT INTO reports (report_no, subject, category, location, severity, responsible_department_id, details, actions_taken, remarks, submitted_by, status, current_reviewer, submitted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, 'submitted_to_ga_staff', 'ga_staff', NOW())",
        'sssssisi',
        [$reportNo, $subject, $category, $location, $severity, (int)$dept['id'], $details, (int)$internalUser['id']]
    );

    $reportId = (int)db_last_insert_id();

    db_execute(
        'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
        'isis',
        [$reportId, 'submitted_to_ga_staff', (int)$internalUser['id'], 'Seeded sample internal report for PDF template preview']
    );

    $projectDir = basename(dirname(__DIR__));
    $url = 'http://localhost/' . $projectDir . '/public/print_report.php?report_id=' . $reportId;

    echo "OK: Created sample INTERNAL report\n";
    echo "- report_id: " . $reportId . "\n";
    echo "- report_no: " . $reportNo . "\n";
    echo "- department: " . (string)$dept['name'] . "\n";
    echo "- submitted_by (internal): " . (string)$internalUser['name'] . " (" . (string)$internalUser['username'] . ")\n";

    if ($createdUser) {
        echo "\nCreated an internal security user because none existed:\n";
        echo "- username: " . (string)$internalUser['username'] . "\n";
        echo "- temp password: " . (string)$tempPassword . "\n";
        echo "(You can change this password in GA President → User Management.)\n";
    }

    echo "\nTo preview the INTERNAL PDF template:\n";
    echo "- Log into the app in your browser\n";
    echo "- Open: " . $url . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
