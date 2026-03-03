<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getUser();
$role = (string)($user['role'] ?? '');
$allowedRoles = ['ga_president', 'ga_staff', 'security', 'department'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

function parse_date_ymd(string $s): ?string {
    $s = trim($s);
    if ($s === '') return null;
    $dt = DateTime::createFromFormat('Y-m-d', $s);
    if (!$dt) return null;
    return $dt->format('Y-m-d');
}

function pdf_escape_text(string $s): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
}

function output_simple_pdf(string $title, array $lines, string $filename): void {
    $lines = array_slice($lines, 0, 55);

    $y = 770;
    $leading = 14;

    $content = "BT\n/F1 14 Tf\n50 $y Td\n(" . pdf_escape_text($title) . ") Tj\nET\n";
    $y -= 26;

    foreach ($lines as $line) {
        $content .= "BT\n/F1 10 Tf\n50 $y Td\n(" . pdf_escape_text((string)$line) . ") Tj\nET\n";
        $y -= $leading;
        if ($y < 60) break;
    }

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefPos = strlen($pdf);
    $count = count($objects) + 1;
    $pdf .= "xref\n0 $count\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $count; $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefPos\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
}

function build_filters(array $get, array $user, string $role): array {
    $allowedSeverities = ['low', 'medium', 'high', 'critical'];
    $allowedStatuses = [
        'submitted_to_ga_staff',
        'ga_staff_reviewed',
        'submitted_to_ga_president',
        'approved_by_ga_president',
        'sent_to_department',
        'under_department_fix',
        'for_security_final_check',
        'returned_to_department',
        'resolved',
    ];

    $start = parse_date_ymd((string)($get['start_date'] ?? ''));
    $end = parse_date_ymd((string)($get['end_date'] ?? ''));

    // Default date range: last 30 days
    $today = new DateTime('now');
    if (!$end) $end = $today->format('Y-m-d');
    if (!$start) {
        $d = (clone $today)->modify('-29 days');
        $start = $d->format('Y-m-d');
    }

    $severity = strtolower(trim((string)($get['severity'] ?? '')));
    if ($severity !== '' && !in_array($severity, $allowedSeverities, true)) {
        $severity = '';
    }

    $status = trim((string)($get['status'] ?? ''));
    if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
        $status = '';
    }

    $deptParam = (int)($get['department_id'] ?? 0);
    $userDeptId = (int)($user['department_id'] ?? 0);

    $effectiveBuilding = get_effective_building_filter();

    $isDeptRole = ($role === 'department');
    $effectiveDeptId = 0;

    if ($isDeptRole) {
        $effectiveDeptId = $userDeptId;
    } else {
        $effectiveDeptId = $deptParam;
    }

    $where = [];
    $params = [];
    $types = '';

    $where[] = 'r.submitted_at >= ?';
    $params[] = $start . ' 00:00:00';
    $types .= 's';

    $where[] = 'r.submitted_at <= ?';
    $params[] = $end . ' 23:59:59';
    $types .= 's';

    // Security Type restriction (role-based)
    // If the logged-in user is Security (internal/external), only show analytics for that group.
    $securityType = strtolower(trim((string)($user['security_type'] ?? '')));
    if ($role === 'security' && in_array($securityType, ['internal', 'external'], true)) {
        $where[] = 'EXISTS (SELECT 1 FROM users su WHERE su.id = r.submitted_by AND su.security_type = ?)';
        $params[] = $securityType;
        $types .= 's';
    }

    if ($effectiveBuilding) {
        $where[] = 'r.building = ?';
        $params[] = $effectiveBuilding;
        $types .= 's';
    }

    if ($effectiveDeptId > 0) {
        $where[] = 'r.responsible_department_id = ?';
        $params[] = $effectiveDeptId;
        $types .= 'i';
    }

    if ($severity !== '') {
        $where[] = 'r.severity = ?';
        $params[] = $severity;
        $types .= 's';
    }

    if ($status !== '') {
        $where[] = 'r.status = ?';
        $params[] = $status;
        $types .= 's';
    }

    return [
        'start' => $start,
        'end' => $end,
        'building' => $effectiveBuilding,
        'severity' => $severity,
        'status' => $status,
        'department_id' => $effectiveDeptId,
        'is_department_restricted' => $isDeptRole,
        'where_sql' => 'WHERE ' . implode(' AND ', $where),
        'params' => $params,
        'types' => $types,
    ];
}

function get_kpis(array $f): array {
    $whereSql = $f['where_sql'];
    $params = $f['params'];
    $types = $f['types'];

    $row = db_fetch_one(
        "SELECT
            COUNT(*) AS total_reports,
            SUM(CASE WHEN r.status IN ('submitted_to_ga_staff','ga_staff_reviewed','submitted_to_ga_president','approved_by_ga_president') THEN 1 ELSE 0 END) AS pending_ga_review,
            SUM(CASE WHEN r.status IN ('sent_to_department','under_department_fix','returned_to_department') THEN 1 ELSE 0 END) AS under_department_fix,
            SUM(CASE WHEN r.status = 'for_security_final_check' THEN 1 ELSE 0 END) AS waiting_security_check,
            SUM(CASE WHEN r.status = 'resolved' THEN 1 ELSE 0 END) AS resolved,
            SUM(CASE WHEN r.status = 'returned_to_department' THEN 1 ELSE 0 END) AS returned_reports,
            SUM(CASE WHEN r.status = 'under_department_fix' AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date THEN 1 ELSE 0 END) AS overdue_reports,
            AVG(CASE WHEN r.status = 'resolved' THEN TIMESTAMPDIFF(SECOND, r.submitted_at, COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at)) ELSE NULL END) AS avg_resolution_seconds,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS on_time_fixed,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 1 ELSE 0 END) AS late_fixed
         FROM reports r
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         $whereSql",
        $types,
        $params
    ) ?: [];

    $avgDays = null;
    if (isset($row['avg_resolution_seconds']) && $row['avg_resolution_seconds'] !== null) {
        $avgDays = round(((float)$row['avg_resolution_seconds']) / 86400, 1);
    }

    $onTime = (int)($row['on_time_fixed'] ?? 0);
    $late = (int)($row['late_fixed'] ?? 0);
    $rate = ($onTime + $late) > 0 ? round(($onTime / ($onTime + $late)) * 100, 1) : null;

    return [
        'total_reports' => (int)($row['total_reports'] ?? 0),
        'pending_ga_review' => (int)($row['pending_ga_review'] ?? 0),
        'under_department_fix' => (int)($row['under_department_fix'] ?? 0),
        'waiting_security_check' => (int)($row['waiting_security_check'] ?? 0),
        'resolved' => (int)($row['resolved'] ?? 0),
        'returned_reports' => (int)($row['returned_reports'] ?? 0),
        'avg_resolution_days' => $avgDays,
        'overdue_reports' => (int)($row['overdue_reports'] ?? 0),
        'on_time_fix_rate' => $rate,
        'on_time_fixed' => $onTime,
        'late_fixed' => $late,
    ];
}

function get_severity_distribution(array $f): array {
    $rows = db_fetch_all(
        "SELECT r.severity, COUNT(*) AS c
         FROM reports r
         {$f['where_sql']}
         GROUP BY r.severity
         ORDER BY FIELD(r.severity, 'low','medium','high','critical')",
        $f['types'],
        $f['params']
    );

    $map = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
    foreach ($rows as $r) {
        $sev = (string)($r['severity'] ?? '');
        if (isset($map[$sev])) $map[$sev] = (int)($r['c'] ?? 0);
    }

    return [
        'labels' => ['Low','Medium','High','Critical'],
        'values' => [$map['low'], $map['medium'], $map['high'], $map['critical']],
    ];
}

function get_by_department(array $f, string $role, array $user): array {
    $whereSql = $f['where_sql'];
    $params = $f['params'];
    $types = $f['types'];

    if ($role === 'department') {
        $deptId = (int)($user['department_id'] ?? 0);
        $nameRow = db_fetch_one('SELECT name FROM departments WHERE id = ? LIMIT 1', 'i', [$deptId]);
        $deptName = $nameRow['name'] ?? 'Department';
        $countRow = db_fetch_one(
            "SELECT COUNT(*) AS c
             FROM reports r
             $whereSql",
            $types,
            $params
        );
        return [
            'labels' => [$deptName],
            'values' => [(int)($countRow['c'] ?? 0)],
        ];
    }

    $rows = db_fetch_all(
        "SELECT d.name AS department, COUNT(*) AS c
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         $whereSql
         GROUP BY d.id, d.name
         ORDER BY c DESC, d.name ASC",
        $types,
        $params
    );

    $labels = [];
    $values = [];
    foreach ($rows as $r) {
        $labels[] = (string)($r['department'] ?? 'Unassigned');
        $values[] = (int)($r['c'] ?? 0);
    }

    return ['labels' => $labels, 'values' => $values];
}

function get_timeline_performance(array $f): array {
    $row = db_fetch_one(
        "SELECT
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) <= r.fix_due_date THEN 1 ELSE 0 END) AS fixed_on_time,
            SUM(CASE WHEN r.status = 'resolved' AND r.fix_due_date IS NOT NULL AND COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) > r.fix_due_date THEN 1 ELSE 0 END) AS fixed_late,
            SUM(CASE WHEN r.status IN ('sent_to_department','under_department_fix','returned_to_department','for_security_final_check') AND r.fix_due_date IS NOT NULL THEN 1 ELSE 0 END) AS still_pending
         FROM reports r
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}",
        $f['types'],
        $f['params']
    ) ?: [];

    $onTime = (int)($row['fixed_on_time'] ?? 0);
    $late = (int)($row['fixed_late'] ?? 0);
    $rate = ($onTime + $late) > 0 ? round(($onTime / ($onTime + $late)) * 100, 1) : null;

    return [
        'fixed_on_time' => $onTime,
        'fixed_late' => $late,
        'still_pending' => (int)($row['still_pending'] ?? 0),
        'compliance_rate' => $rate,
    ];
}

function get_overdue_rows(array $f): array {
    $whereSql = $f['where_sql'];
    $params = $f['params'];
    $types = $f['types'];

    // Add overdue condition (keep existing filters)
    $whereSql .= " AND r.status = 'under_department_fix' AND r.fix_due_date IS NOT NULL AND NOW() > r.fix_due_date";

    return db_fetch_all(
        "SELECT r.report_no, d.name AS department, r.fix_due_date,
                DATEDIFF(NOW(), r.fix_due_date) AS days_overdue
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         $whereSql
         ORDER BY r.fix_due_date ASC
         LIMIT 100",
        $types,
        $params
    );
}

function get_trend(string $mode, array $fBase): array {
    $mode = in_array($mode, ['daily', 'weekly', 'monthly'], true) ? $mode : 'daily';

    // Rebuild date window relative to today, keep other filters (dept/severity/status)
    $today = new DateTime('now');

    $where = [];
    $params = [];
    $types = '';

    // Extract non-date filters from base by reusing its effective dept/sev/status
    $deptId = (int)($fBase['department_id'] ?? 0);
    $severity = (string)($fBase['severity'] ?? '');
    $status = (string)($fBase['status'] ?? '');

    if ($mode === 'daily') {
        $start = (clone $today)->modify('-6 days')->format('Y-m-d');
        $end = $today->format('Y-m-d');
        $where[] = 'r.submitted_at >= ?';
        $params[] = $start . ' 00:00:00';
        $types .= 's';
        $where[] = 'r.submitted_at <= ?';
        $params[] = $end . ' 23:59:59';
        $types .= 's';

        if ($deptId > 0) { $where[] = 'r.responsible_department_id = ?'; $params[] = $deptId; $types .= 'i'; }
        if ($severity !== '') { $where[] = 'r.severity = ?'; $params[] = $severity; $types .= 's'; }
        if ($status !== '') { $where[] = 'r.status = ?'; $params[] = $status; $types .= 's'; }

        $rows = db_fetch_all(
            "SELECT DATE(r.submitted_at) AS d, COUNT(*) AS c
             FROM reports r
             WHERE " . implode(' AND ', $where) . "
             GROUP BY DATE(r.submitted_at)",
            $types,
            $params
        );

        $map = [];
        foreach ($rows as $r) { $map[$r['d']] = (int)$r['c']; }

        $labels = [];
        $values = [];
        $cur = new DateTime($start);
        for ($i = 0; $i < 7; $i++) {
            $key = $cur->format('Y-m-d');
            $labels[] = $cur->format('M d');
            $values[] = (int)($map[$key] ?? 0);
            $cur->modify('+1 day');
        }

        return ['mode' => 'daily', 'labels' => $labels, 'values' => $values];
    }

    if ($mode === 'weekly') {
        $start = (clone $today)->modify('-27 days')->format('Y-m-d');
        $end = $today->format('Y-m-d');

        $where[] = 'r.submitted_at >= ?';
        $params[] = $start . ' 00:00:00';
        $types .= 's';
        $where[] = 'r.submitted_at <= ?';
        $params[] = $end . ' 23:59:59';
        $types .= 's';

        if ($deptId > 0) { $where[] = 'r.responsible_department_id = ?'; $params[] = $deptId; $types .= 'i'; }
        if ($severity !== '') { $where[] = 'r.severity = ?'; $params[] = $severity; $types .= 's'; }
        if ($status !== '') { $where[] = 'r.status = ?'; $params[] = $status; $types .= 's'; }

        $rows = db_fetch_all(
            "SELECT
                DATE_SUB(DATE(r.submitted_at), INTERVAL WEEKDAY(r.submitted_at) DAY) AS week_start,
                COUNT(*) AS c
             FROM reports r
             WHERE " . implode(' AND ', $where) . "
             GROUP BY week_start
             ORDER BY week_start ASC",
            $types,
            $params
        );

        $map = [];
        foreach ($rows as $r) { $map[$r['week_start']] = (int)$r['c']; }

        // Build last 4 week starts (Mon)
        $wkStart = (clone $today)->modify('-' . ((int)$today->format('N') - 1) . ' days');
        $wkStart->setTime(0, 0, 0);
        $wkStart->modify('-3 weeks');

        $labels = [];
        $values = [];
        for ($i = 0; $i < 4; $i++) {
            $key = $wkStart->format('Y-m-d');
            $labels[] = $wkStart->format('M d');
            $values[] = (int)($map[$key] ?? 0);
            $wkStart->modify('+1 week');
        }

        return ['mode' => 'weekly', 'labels' => $labels, 'values' => $values];
    }

    // monthly
    $start = (clone $today)->modify('first day of this month')->modify('-11 months')->format('Y-m-d');
    $end = $today->format('Y-m-d');

    $where[] = 'r.submitted_at >= ?';
    $params[] = $start . ' 00:00:00';
    $types .= 's';
    $where[] = 'r.submitted_at <= ?';
    $params[] = $end . ' 23:59:59';
    $types .= 's';

    if ($deptId > 0) { $where[] = 'r.responsible_department_id = ?'; $params[] = $deptId; $types .= 'i'; }
    if ($severity !== '') { $where[] = 'r.severity = ?'; $params[] = $severity; $types .= 's'; }
    if ($status !== '') { $where[] = 'r.status = ?'; $params[] = $status; $types .= 's'; }

    $rows = db_fetch_all(
        "SELECT DATE_FORMAT(r.submitted_at, '%Y-%m') AS ym, COUNT(*) AS c
         FROM reports r
         WHERE " . implode(' AND ', $where) . "
         GROUP BY ym
         ORDER BY ym ASC",
        $types,
        $params
    );

    $map = [];
    foreach ($rows as $r) { $map[$r['ym']] = (int)$r['c']; }

    $labels = [];
    $values = [];
    $cur = new DateTime($start);
    for ($i = 0; $i < 12; $i++) {
        $ym = $cur->format('Y-m');
        $labels[] = $cur->format('M');
        $values[] = (int)($map[$ym] ?? 0);
        $cur->modify('+1 month');
    }

    return ['mode' => 'monthly', 'labels' => $labels, 'values' => $values];
}

$f = build_filters($_GET, $user, $role);

$export = trim((string)($_GET['export'] ?? ''));
if ($export === 'csv') {
    $rows = db_fetch_all(
        "SELECT r.report_no, r.subject, r.category, r.location, r.severity, d.name AS department_name, r.status,
                r.submitted_at,
                COALESCE(r.resolved_at, sfc.closed_at, sfc.checked_at) AS resolved_at
         FROM reports r
         JOIN departments d ON d.id = r.responsible_department_id
         LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
         {$f['where_sql']}
         ORDER BY r.submitted_at DESC",
        $f['types'],
        $f['params']
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="analytics_export_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Report ID','Subject','Category','Location','Severity','Department','Status','Date Submitted','Date Resolved']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['report_no'],
            $r['subject'],
            $r['category'],
            $r['location'],
            $r['severity'],
            $r['department_name'],
            $r['status'],
            $r['submitted_at'],
            $r['resolved_at'],
        ]);
    }
    fclose($out);
    exit;
}

if ($export === 'pdf') {
    $k = get_kpis($f);
    $lines = [];
    $lines[] = 'NIDEC Security Reporting System - Analytics Export';
    $lines[] = 'Generated: ' . date('Y-m-d H:i:s');
    $lines[] = 'Role: ' . $role;
    $lines[] = 'Date Range: ' . $f['start'] . ' to ' . $f['end'];
    if (!empty($f['building'])) $lines[] = 'Building: ' . $f['building'];
    if ((int)$f['department_id'] > 0) $lines[] = 'Department ID: ' . (int)$f['department_id'];
    if ($f['severity'] !== '') $lines[] = 'Severity: ' . $f['severity'];
    if ($f['status'] !== '') $lines[] = 'Status: ' . $f['status'];
    $lines[] = '';
    $lines[] = 'Total Reports: ' . (int)$k['total_reports'];
    $lines[] = 'Pending GA Review: ' . (int)$k['pending_ga_review'];
    $lines[] = 'Under Department Fix: ' . (int)$k['under_department_fix'];
    $lines[] = 'Waiting Security Check: ' . (int)$k['waiting_security_check'];
    $lines[] = 'Resolved: ' . (int)$k['resolved'];
    $lines[] = 'Returned: ' . (int)$k['returned_reports'];
    $lines[] = 'Average Resolution (days): ' . ($k['avg_resolution_days'] === null ? 'N/A' : $k['avg_resolution_days']);
    $lines[] = 'Overdue Reports: ' . (int)$k['overdue_reports'];
    $lines[] = 'On-Time Fix Rate (%): ' . ($k['on_time_fix_rate'] === null ? 'N/A' : $k['on_time_fix_rate']);

    output_simple_pdf('Analytics Export', $lines, 'analytics_export_' . date('Ymd_His') . '.pdf');
    exit;
}

$trendMode = trim((string)($_GET['trend'] ?? 'daily'));
$trendMode = in_array($trendMode, ['daily', 'weekly', 'monthly'], true) ? $trendMode : 'daily';

$payload = [
    'filters' => [
        'start_date' => $f['start'],
        'end_date' => $f['end'],
        'building' => $f['building'] ?? null,
        'department_id' => (int)$f['department_id'],
        'severity' => $f['severity'],
        'status' => $f['status'],
        'role' => $role,
        'department_restricted' => (bool)$f['is_department_restricted'],
    ],
    'kpis' => get_kpis($f),
    'trend' => get_trend($trendMode, $f),
    'severity_distribution' => get_severity_distribution($f),
    'by_department' => get_by_department($f, $role, $user),
    'timeline' => get_timeline_performance($f),
    'overdue' => [
        'rows' => get_overdue_rows($f),
    ],
];

header('Content-Type: application/json');
echo json_encode($payload);
