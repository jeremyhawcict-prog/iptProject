<?php
/**
 * MediQueue — List Appointments
 * GET api/appointments/list.php
 *
 * Query params (all optional):
 *   patient_id, doctor_id, status, date_from, date_to, page, limit
 *
 * Patients see only their own; Doctors see their own; Staff/Admin see all.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$role      = $_SESSION['user_role'];
$userId    = (int) $_SESSION['user_id'];
$status    = getGetString('status');
$dateFrom  = getGetString('date_from');
$dateTo    = getGetString('date_to');
$page      = getGetInt('page', 1);
$limit     = getGetInt('per_page', 0) ?: getGetInt('limit', 20);
$pag       = getPagination($page, $limit);

$where  = [];
$params = [];

// Role-based filtering
if ($role === 'patient') {
    $where[]  = 'a.patient_id = ?';
    $params[] = $userId;
} elseif ($role === 'doctor') {
    $where[]  = 'a.doctor_id = ?';
    $params[] = $userId;
    $patientFilter = getGetInt('patient_id');
    if ($patientFilter) { $where[] = 'a.patient_id = ?'; $params[] = $patientFilter; }
} else {
    // Staff/Admin can filter by patient or doctor
    $patientFilter = getGetInt('patient_id');
    $doctorFilter  = getGetInt('doctor_id');
    if ($patientFilter) { $where[] = 'a.patient_id = ?'; $params[] = $patientFilter; }
    if ($doctorFilter)  { $where[] = 'a.doctor_id = ?';  $params[] = $doctorFilter; }
}

if ($status) {
    $where[]  = 'a.status = ?';
    $params[] = $status;
}

// Search by patient or doctor name
$search = getGetString('search');
if ($search) {
    $where[]  = '(p.full_name LIKE ? OR d.full_name LIKE ?)';
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

if ($dateFrom && isValidDate($dateFrom)) {
    $where[]  = 'a.appointment_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo && isValidDate($dateTo)) {
    $where[]  = 'a.appointment_date <= ?';
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$db = getDB();

// Total count
$countSql = "SELECT COUNT(*) FROM appointments a
             JOIN users p ON p.id = a.patient_id
             JOIN users d ON d.id = a.doctor_id
             $whereSql";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Fetch rows
$sql = "SELECT a.*,
               ts.end_time AS end_time,
               p.full_name AS patient_name, p.email AS patient_email, p.profile_photo AS patient_photo,
               d.full_name AS doctor_name, d.email AS doctor_email, d.profile_photo AS doctor_photo,
               dp.specialization,
               CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS has_feedback
        FROM appointments a
        JOIN users p  ON p.id = a.patient_id
        JOIN users d  ON d.id = a.doctor_id
        LEFT JOIN time_slots ts ON ts.id = a.slot_id
        LEFT JOIN doctor_profiles dp ON dp.user_id = d.id
        LEFT JOIN feedback f ON f.appointment_id = a.id
        $whereSql
        GROUP BY a.id
        ORDER BY a.appointment_date DESC, a.start_time DESC
        LIMIT {$pag['limit']} OFFSET {$pag['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Cast has_feedback to boolean for JS
foreach ($rows as &$r) {
    $r['has_feedback'] = (bool) $r['has_feedback'];
}

jsonSuccess([
    'appointments' => $rows,
    'pagination'   => paginationResponse($pag['page'], $pag['limit'], $total),
]);
