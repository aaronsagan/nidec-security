<?php
/**
 * CLI smoke test for core workflow transitions + notifications.
 *
 * Usage:
 *   php tools/smoke_workflow.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(400);
    echo "This script is CLI-only.\n";
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../app/services/GaStaffReviewService.php';
require_once __DIR__ . '/../app/services/GaPresidentApprovalService.php';
require_once __DIR__ . '/../app/services/AssignedReportsService.php';
require_once __DIR__ . '/../app/services/FinalCheckingService.php';

function out(string $msg): void {
    echo $msg . PHP_EOL;
}

function fail(string $msg, int $code = 2): void {
    fwrite(STDERR, "FAIL: {$msg}\n");
    exit($code);
}

function assert_true(bool $cond, string $msg): void {
    if (!$cond) {
        fail($msg);
    }
}

function fetch_user_by_username(string $username): array {
    $row = db_fetch_one('SELECT * FROM users WHERE username = ? LIMIT 1', 's', [$username]);
    if (!$row) {
        fail("Missing user '{$username}'. Did you import database/seed.sql?");
    }
    return $row;
}

function fetch_report(int $id): array {
    $row = db_fetch_one('SELECT * FROM reports WHERE id = ? LIMIT 1', 'i', [$id]);
    if (!$row) {
        fail("Report id {$id} not found");
    }
    return $row;
}

function count_notifications(int $userId, int $reportId, string $containsMessage = ''): int {
    if ($containsMessage !== '') {
        $row = db_fetch_one(
            'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND report_id = ? AND message LIKE ?',
            'iis',
            [$userId, $reportId, '%' . $containsMessage . '%']
        );
        return (int)($row['c'] ?? 0);
    }

    $row = db_fetch_one(
        'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND report_id = ?',
        'ii',
        [$userId, $reportId]
    );
    return (int)($row['c'] ?? 0);
}

out('=== NidecSecurity smoke workflow ===');

// 1) DB connectivity
try {
    db()->query('SELECT 1');
} catch (Throwable $e) {
    fail('Database not reachable: ' . $e->getMessage());
}
out('DB: OK');

// 2) Users from seed
$gaStaff = fetch_user_by_username('ga_staff1');
$gaPresident = fetch_user_by_username('ga_president');
$security = fetch_user_by_username('sec_ncfl_int');
$dept = fetch_user_by_username('dept_fac');

assert_true(($gaStaff['role'] ?? '') === 'ga_staff', 'ga_staff1 role mismatch');
assert_true(($gaPresident['role'] ?? '') === 'ga_president', 'ga_president role mismatch');
assert_true(($security['role'] ?? '') === 'security', 'sec_ncfl_int role mismatch');
assert_true(($dept['role'] ?? '') === 'department', 'dept_fac role mismatch');

out('Seed users: OK');

// 3) Create a temporary report to avoid destroying real data
$startedAt = date('Y-m-d H:i:s');
$reportNo = 'SMOKE-' . date('YmdHis');

$conn = db();
$conn->beginTransaction();
try {
    db_execute(
        "INSERT INTO reports (report_no, subject, category, location, severity, building, responsible_department_id, details, submitted_by, submitted_at, current_reviewer, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'ga_staff', 'submitted_to_ga_staff')",
        'ssssssisis',
        [
            $reportNo,
            'Smoke Test Report',
            'Access Control',
            'NCFL - Smoke Location',
            'high',
            'NCFL',
            1,
            'Smoke test details used to validate workflow transitions.',
            (int)$security['id'],
        ]
    );
    $rid = (int)db_last_insert_id();
    db_execute(
        'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
        'isis',
        [$rid, 'submitted_to_ga_staff', (int)$security['id'], 'Smoke report submitted']
    );

    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    fail('Failed to create smoke report: ' . $e->getMessage());
}

out("Smoke report created: {$reportNo}");

// Prepare CSRF/session token for service calls
$token = csrf_token();

// 4) GA Staff forwards to GA President
$gaStaffSvc = new GaStaffReviewService();
$res = $gaStaffSvc->handlePost(
    ['csrf_token' => $token, 'action' => 'forward', 'report_no' => $reportNo, 'notes' => 'smoke forward'],
    $gaStaff
);
assert_true(($res['flashType'] ?? '') !== 'error', 'GA Staff forward failed: ' . ($res['flash'] ?? 'unknown'));

$r = fetch_report($rid);
assert_true(($r['status'] ?? '') === 'submitted_to_ga_president', 'Expected status submitted_to_ga_president after GA Staff forward');
assert_true(($r['current_reviewer'] ?? '') === 'ga_president', 'Expected reviewer ga_president after GA Staff forward');

$outCount = count_notifications((int)$gaPresident['id'], $rid, 'Final GA Approval');
assert_true($outCount >= 1, 'Expected GA President notification after GA Staff forward');

out('GA Staff forward: OK');

// 5) GA President approves -> sent to department
$gaPresSvc = new GaPresidentApprovalService();
$res = $gaPresSvc->handlePost(
    ['csrf_token' => $token, 'action' => 'approve', 'report_no' => $reportNo, 'notes' => 'smoke approve'],
    $gaPresident
);
assert_true(($res['flashType'] ?? '') !== 'error', 'GA President approve failed: ' . ($res['flash'] ?? 'unknown'));

$r = fetch_report($rid);
assert_true(($r['status'] ?? '') === 'sent_to_department', 'Expected status sent_to_department after GA President approve');
assert_true(($r['current_reviewer'] ?? '') === 'department', 'Expected reviewer department after GA President approve');

$deptNotif = count_notifications((int)$dept['id'], $rid, 'Assigned');
assert_true($deptNotif >= 1, 'Expected Department notification after GA President approve');

out('GA President approve: OK');

// 6) Department sets timeline -> under_department_fix
$deptId = (int)($dept['department_id'] ?? 0);
assert_true($deptId === 1, 'dept_fac expected department_id=1 (Facilities)');

$deptSvc = new AssignedReportsService();
$res = $deptSvc->handlePost(
    ['csrf_token' => $token, 'action' => 'set_timeline', 'report_no' => $reportNo, 'timeline_days' => 7],
    (int)$dept['id'],
    $deptId
);
assert_true(($res['flashType'] ?? '') !== 'error', 'Department set timeline failed: ' . ($res['flash'] ?? 'unknown'));

$r = fetch_report($rid);
assert_true(($r['status'] ?? '') === 'under_department_fix', 'Expected status under_department_fix after timeline set');
assert_true(($r['current_reviewer'] ?? '') === 'department', 'Expected reviewer department after timeline set');
assert_true(!empty($r['fix_due_date']), 'Expected fix_due_date after timeline set');

$secTimelineNotif = count_notifications((int)$security['id'], $rid, 'Fix Timeline');
assert_true($secTimelineNotif >= 1, 'Expected Security notification after timeline set');

out('Department set timeline: OK');

// 7) Department marks done -> for_security_final_check
$res = $deptSvc->handlePost(
    ['csrf_token' => $token, 'action' => 'mark_done', 'report_no' => $reportNo],
    (int)$dept['id'],
    $deptId
);
assert_true(($res['flashType'] ?? '') !== 'error', 'Department mark done failed: ' . ($res['flash'] ?? 'unknown'));

$r = fetch_report($rid);
assert_true(($r['status'] ?? '') === 'for_security_final_check', 'Expected status for_security_final_check after mark done');
assert_true(($r['current_reviewer'] ?? '') === 'security', 'Expected reviewer security after mark done');

$secDoneNotif = count_notifications((int)$security['id'], $rid, 'Marked Report as Fixed');
assert_true($secDoneNotif >= 1, 'Expected Security notification after mark done');

out('Department mark done: OK');

// 8) Security not resolved -> returned_to_department + GA visibility notifications
$finalSvc = new FinalCheckingService();
$res = $finalSvc->handlePost(
    ['csrf_token' => $token, 'action' => 'not_resolved', 'report_no' => $reportNo, 'final_remarks' => 'smoke not resolved'],
    (int)$security['id']
);
assert_true(($res['flashType'] ?? '') !== 'error', 'Security not resolved failed: ' . ($res['flash'] ?? 'unknown'));

$r = fetch_report($rid);
assert_true(($r['status'] ?? '') === 'returned_to_department', 'Expected status returned_to_department after not resolved');
assert_true(($r['current_reviewer'] ?? '') === 'department', 'Expected reviewer department after not resolved');

$deptReturnNotif = count_notifications((int)$dept['id'], $rid, 'Not Resolved');
assert_true($deptReturnNotif >= 1, 'Expected Department notification after not resolved');

$gaStaffVis = count_notifications((int)$gaStaff['id'], $rid, 'Not Resolved');
$gaPresVis = count_notifications((int)$gaPresident['id'], $rid, 'Not Resolved');
assert_true($gaStaffVis >= 1, 'Expected GA Staff visibility notification after not resolved');
assert_true($gaPresVis >= 1, 'Expected GA President visibility notification after not resolved');

out('Security not resolved: OK');

// 9) Cleanup (delete smoke report + related notifications)
$conn = db();
$conn->beginTransaction();
try {
    db_execute('DELETE FROM notifications WHERE report_id = ?', 'i', [$rid]);
    db_execute('DELETE FROM reports WHERE id = ?', 'i', [$rid]);
    $conn->commit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    fail('Cleanup failed: ' . $e->getMessage());
}

out('Cleanup: OK');
out('=== SMOKE TEST PASS ===');
exit(0);
