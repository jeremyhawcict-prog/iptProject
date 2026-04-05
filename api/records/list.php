<?php
/**
 * MediQueue — List Patient Records
 * GET api/records/list.php?patient_id=&page=&per_page=
 *
 * - Patients see only their own records
 * - Doctors see records of their patients
 * - Admin/staff see all
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$role      = $_SESSION['user_role'];
$userId    = (int) $_SESSION['user_id'];
$patientId = getGetInt('patient_id');
$page      = max(1, getGetInt('page') ?: 1);
$perPage   = min(50, max(1, getGetInt('per_page') ?: 20));

$where  = '1=1';
$params = [];

if ($role === 'patient') {
    $where  .= ' AND pr.patient_id = ?';
    $params[] = $userId;
} elseif ($role === 'doctor') {
    $where .= ' AND pr.doctor_id = ?';
    $params[] = $userId;
    if ($patientId) {
        $where   .= ' AND pr.patient_id = ?';
        $params[] = $patientId;
    }
} else {
    // admin / staff
    if ($patientId) {
        $where   .= ' AND pr.patient_id = ?';
        $params[] = $patientId;
    }
}

$countStmt = getDB()->prepare("SELECT COUNT(*) FROM patient_records pr WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;

$sql = "SELECT pr.*,
               doc.full_name AS doctor_name,
               pat.full_name AS patient_name
        FROM patient_records pr
        JOIN users doc ON doc.id = pr.doctor_id
        JOIN users pat ON pat.id = pr.patient_id
        WHERE $where
        ORDER BY pr.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

jsonSuccess([
    'records'    => $records,
    'pagination' => [
        'current_page'  => $page,
        'per_page'      => $perPage,
        'total'         => $total,
        'total_pages'   => (int) ceil($total / $perPage),
        'has_prev'      => $page > 1,
        'has_next'      => $page < (int) ceil($total / $perPage),
    ],
]);