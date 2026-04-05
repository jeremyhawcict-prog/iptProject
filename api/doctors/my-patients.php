<?php
/**
 * MediQueue — Doctor's Patients
 * GET api/doctors/my-patients.php?search=&page=&per_page=
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['doctor']);

$doctorId = (int) $_SESSION['user_id'];
$search   = getGetString('search');
$page     = max(1, getGetInt('page') ?: 1);
$perPage  = min(50, max(1, getGetInt('per_page') ?: 20));

$where  = 'WHERE a.doctor_id = ?';
$params = [$doctorId];

if ($search) {
    $where   .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

$countSql = "SELECT COUNT(DISTINCT u.id) FROM appointments a JOIN users u ON u.id = a.patient_id $where";
$countStmt = getDB()->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;

$sql = "SELECT u.id, u.full_name, u.email, u.phone, u.profile_photo,
               COUNT(a.id) AS visit_count,
               MAX(a.appointment_date) AS last_visit
        FROM appointments a
        JOIN users u ON u.id = a.patient_id
        $where
        GROUP BY u.id
        ORDER BY last_visit DESC
        LIMIT $perPage OFFSET $offset";

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

foreach ($patients as &$p) {
    $p['visit_count'] = (int) $p['visit_count'];
}

jsonSuccess([
    'patients'   => $patients,
    'pagination' => [
        'current_page' => $page,
        'per_page'     => $perPage,
        'total'        => $total,
        'total_pages'  => (int) ceil($total / $perPage),
        'has_prev'     => $page > 1,
        'has_next'     => $page < (int) ceil($total / $perPage),
    ],
]);