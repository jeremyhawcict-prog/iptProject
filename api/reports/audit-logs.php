<?php
/**
 * MediQueue — Audit Logs
 * GET api/reports/audit-logs.php?page=&limit=&action=&entity_type=
 * Access: staff, admin
 *
 * Creates audit_logs table if not exists. Returns empty list gracefully
 * until audit events are recorded elsewhere in the system.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

// Ensure audit_logs table exists
$db->exec("CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`           INT          NOT NULL AUTO_INCREMENT,
    `user_id`      INT          DEFAULT NULL,
    `user_name`    VARCHAR(150) DEFAULT NULL,
    `action`       VARCHAR(100) NOT NULL,
    `entity_type`  VARCHAR(100) DEFAULT NULL,
    `entity_id`    INT          DEFAULT NULL,
    `details`      TEXT         DEFAULT NULL,
    `ip_address`   VARCHAR(45)  DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_al_user`   (`user_id`),
    KEY `idx_al_action` (`action`),
    KEY `idx_al_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$action     = getGetString('action');
$entityType = getGetString('entity_type');
$page       = max(1, getGetInt('page') ?: 1);
$limit      = min(100, max(1, getGetInt('limit') ?: 20));

$where  = '1=1';
$params = [];

if ($action) {
    $where   .= ' AND action = ?';
    $params[] = $action;
}
if ($entityType) {
    $where   .= ' AND entity_type = ?';
    $params[] = $entityType;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$offset     = ($page - 1) * $limit;
$totalPages = (int) ceil($total / $limit);

$stmt = $db->prepare(
    "SELECT id, user_id, user_name, action, entity_type, entity_id, details, ip_address,
            created_at,
            DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '+08:00'), '%b %d, %Y %h:%i %p') AS created_at_formatted
     FROM audit_logs
     WHERE $where
     ORDER BY created_at DESC
     LIMIT $limit OFFSET $offset"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

jsonSuccess([
    'logs'       => $logs,
    'pagination' => [
        'current_page' => $page,
        'total_pages'  => $totalPages,
        'total'        => $total,
        'has_prev'     => $page > 1,
        'has_next'     => $page < $totalPages,
    ],
]);
