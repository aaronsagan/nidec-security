<?php
// Cron automation: move overdue Department fixes to Security final check.
// Intended to run every minute.
// Example cron (Linux): * * * * * /usr/bin/php /path/to/cron_check_timelines.php

require_once __DIR__ . '/../includes/config.php';

$startedAt = microtime(true);
$isCli = (PHP_SAPI === 'cli');

$argv = $isCli ? ($_SERVER['argv'] ?? []) : [];
$dryRun = (!$isCli && (($_GET['dry_run'] ?? '') === '1')) || ($isCli && in_array('--dry-run', $argv, true));

$limit = 250;
if ($isCli) {
    foreach ($argv as $a) {
        if (substr($a, 0, strlen('--limit=')) === '--limit=') {
            $limit = (int)substr($a, strlen('--limit='));
        }
    }
} else {
    if (isset($_GET['limit'])) {
        $limit = (int)$_GET['limit'];
    }
}
$limit = max(1, min(1000, $limit));

$results = [
    'dry_run' => $dryRun,
    'limit' => $limit,
    'processed' => 0,
    'updated' => 0,
    'report_nos' => [],
    'errors' => [],
    'duration_ms' => 0,
];

try {
    $conn = db();
    $conn->beginTransaction();

    // 1) Due soon reminders (within next 24 hours): notify responsible Department (deduped for 24h)
    $dueSoon = db_fetch_all(
        "SELECT id, report_no, fix_due_date, responsible_department_id
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date > NOW()
           AND fix_due_date <= DATE_ADD(NOW(), INTERVAL 1 DAY)
         ORDER BY fix_due_date ASC
         LIMIT {$limit}"
    );

    foreach ($dueSoon as $r) {
        $results['processed']++;
        $rid = (int)$r['id'];
        $deptId = (int)($r['responsible_department_id'] ?? 0);
        $reportNo = (string)$r['report_no'];

        if ($dryRun) {
            $results['report_nos'][] = $reportNo;
            continue;
        }

        if ($deptId <= 0) continue;

        $message = 'Fix Timeline Due Soon (within 24 hours)';

        // Dedupe per-user for the same report/message in the last 24 hours
        $alreadyRows = db_fetch_all(
            'SELECT DISTINCT user_id FROM notifications WHERE report_id = ? AND message = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            'is',
            [$rid, $message]
        );
        $already = [];
        foreach ($alreadyRows as $ar) {
            $already[(int)($ar['user_id'] ?? 0)] = true;
        }

        $deptUsers = db_fetch_all(
            "SELECT id FROM users WHERE role = 'department' AND account_status = 'active' AND department_id = ?",
            'i',
            [$deptId]
        );

        foreach ($deptUsers as $u) {
            $uid = (int)($u['id'] ?? 0);
            if ($uid <= 0) continue;
            if (isset($already[$uid])) continue;
            notify_user($uid, $rid, $message);
            $results['updated']++;
        }
    }

    $rows = db_fetch_all(
        "SELECT id, report_no, fix_due_date
         FROM reports
         WHERE status = 'under_department_fix'
           AND fix_due_date IS NOT NULL
           AND fix_due_date <= NOW()
         ORDER BY fix_due_date ASC
         LIMIT {$limit}"
    );

    foreach ($rows as $r) {
        $results['processed']++;
        $rid = (int)$r['id'];
        $reportNo = (string)$r['report_no'];

        if ($dryRun) {
            $results['report_nos'][] = $reportNo;
            continue;
        }

        $updated = db_execute(
            "UPDATE reports
             SET status = 'for_security_final_check',
                 current_reviewer = 'security',
                 fix_due_date = NULL
             WHERE id = ?
               AND status = 'under_department_fix'",
            'i',
            [$rid]
        );

        if ($updated > 0) {
            $results['updated']++;
            $results['report_nos'][] = $reportNo;

            db_execute(
                "INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
                 VALUES (?, 'for_security_final_check', NULL, 'Fix timeline reached (auto-escalated to Security final check)', NOW())",
                'i',
                [$rid]
            );

            notify_role('security', $rid, 'Fix Timeline Reached. Please Perform Final Check');

            // Notifications: Timeline reached -> notify GA roles (audit/visibility)
            notify_role('ga_staff', $rid, 'Fix Timeline Reached (Auto-escalated to Security Final Check)');
            notify_role('ga_president', $rid, 'Fix Timeline Reached (Auto-escalated to Security Final Check)');
        }
    }

    $conn->commit();
} catch (Throwable $e) {
    $results['errors'][] = $e->getMessage();
    try {
        $c = db();
        if ($c->inTransaction()) {
            $c->rollBack();
        }
    } catch (Throwable $ignored) {
    }
}

$results['duration_ms'] = (int)round((microtime(true) - $startedAt) * 1000);

if ($isCli) {
    echo json_encode($results, JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_UNESCAPED_SLASHES);
}
