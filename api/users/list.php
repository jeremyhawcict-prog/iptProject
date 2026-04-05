<?php
/**
 * MediQueue — List Users
 * GET api/users/list.php?page=&per_page=&role=&search=
 * Access: staff, admin only
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$search  = getGetString('search');
$role    = getGetString('role');
$page    = max(1, getGetInt('page') ?: 1);
$perPage = min(50, max(1, getGetInt('per_page') ?: 20));

$where  = '1=1';
$params = [];

if ($role) {
    $where   .= ' AND u.role = ?';
    $params[] = $role;
}
if ($search) {
    $where   .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

$db = getDB();

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$offset    = ($page - 1) * $perPage;
$totalPages = (int) ceil($total / $perPage);

$stmt = $db->prepare(
    "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.is_active, u.profile_photo, u.created_at
     FROM users u
     WHERE $where
     ORDER BY u.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

jsonSuccess([
    'users'      => $users,
    'pagination' => [
        'current_page' => $page,
        'per_page'     => $perPage,
        'total'        => $total,
        'total_pages'  => $totalPages,
        'has_prev'     => $page > 1,
        'has_next'     => $page < $totalPages,
    ],
]);
