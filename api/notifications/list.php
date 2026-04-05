<?php
/**
 * MediQueue — List Notifications
 * GET api/notifications/list.php?page=&per_page=&unread=
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$userId  = (int) $_SESSION['user_id'];
$unread  = getGetString('unread');
$page    = max(1, getGetInt('page') ?: 1);
$perPage = min(50, max(1, getGetInt('per_page') ?: 20));

$where  = 'WHERE n.user_id = ?';
$params = [$userId];

if ($unread === '1') {
    $where  .= ' AND n.is_read = 0';
}

// Count
$countStmt = getDB()->prepare("SELECT COUNT(*) FROM notifications n $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;

$stmt = getDB()->prepare(
    "SELECT n.id, n.appointment_id, n.type, n.subject, n.message, n.status, n.is_read, n.sent_at, n.created_at
     FROM notifications n
     $where
     ORDER BY n.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$items = $stmt->fetchAll();

jsonSuccess([
    'notifications' => $items,
    'pagination'    => [
        'current_page'  => $page,
        'per_page'      => $perPage,
        'total'         => $total,
        'total_pages'   => (int) ceil($total / $perPage),
        'has_prev'      => $page > 1,
        'has_next'      => $page < (int) ceil($total / $perPage),
    ],
]);