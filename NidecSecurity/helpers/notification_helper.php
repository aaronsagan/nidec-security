<?php

require_once __DIR__ . '/../includes/db.php';

function notifications_unread_count(int $userId): int {
    $row = db_fetch_one('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', 'i', [$userId]);
    return (int)($row['c'] ?? 0);
}

function notifications_fetch(int $userId, int $limit = 20): array {
    $limit = max(1, min(50, $limit));

    // LIMIT cannot be bound in MySQL prepared statements in all modes; inline safely.
    $sql =
        'SELECT n.id, n.report_id, n.message, n.is_read, n.created_at, r.report_no\n'
        . 'FROM notifications n\n'
        . 'LEFT JOIN reports r ON r.id = n.report_id\n'
        . 'WHERE n.user_id = ?\n'
        . 'ORDER BY n.created_at DESC, n.id DESC\n'
        . 'LIMIT ' . (int)$limit;

    return db_fetch_all($sql, 'i', [$userId]);
}

function notifications_mark_read(int $userId, int $notificationId): bool {
    $affected = db_execute(
        'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
        'ii',
        [$notificationId, $userId]
    );
    return $affected > 0;
}

function notifications_mark_all_read(int $userId): int {
    return db_execute('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', 'i', [$userId]);
}

function notify_user(int $userId, ?int $reportId, string $message): void {
    db_execute(
        'INSERT INTO notifications (user_id, report_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())',
        'iis',
        [$userId, $reportId, $message]
    );
}

function notify_users(array $userIds, ?int $reportId, string $message): void {
    $unique = [];
    foreach ($userIds as $id) {
        $id = (int)$id;
        if ($id > 0) $unique[$id] = true;
    }

    foreach (array_keys($unique) as $uid) {
        notify_user($uid, $reportId, $message);
    }
}

function notify_role(string $role, ?int $reportId, string $message, ?int $departmentId = null): void {
    $params = [$role];
    $types = 's';
    $where = 'role = ? AND account_status = \'active\'';

    // Building scoping for Security notifications:
    // only Security users assigned to the same building as the report should be notified.
    if ($role === 'security' && $reportId !== null) {
        $bRow = db_fetch_one('SELECT building FROM reports WHERE id = ? LIMIT 1', 'i', [(int)$reportId]);
        $building = isset($bRow['building']) ? strtoupper(trim((string)$bRow['building'])) : '';
        if (in_array($building, ['NCFL', 'NPFL'], true)) {
            $where .= ' AND building = ?';
            $params[] = $building;
            $types .= 's';
        }
    }

    if ($departmentId !== null) {
        $where .= ' AND department_id = ?';
        $params[] = $departmentId;
        $types .= 'i';
    }

    $rows = db_fetch_all('SELECT id FROM users WHERE ' . $where, $types, $params);
    $ids = array_map(static fn($r) => (int)$r['id'], $rows);
    notify_users($ids, $reportId, $message);
}
