<?php
/**
 * MediQueue — List Doctors
 * GET api/doctors/list.php?search=&specialization=&page=&per_page=
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', false, false);

$search         = getGetString('search');
$specialization = getGetString('specialization');
$page           = max(1, getGetInt('page') ?: 1);
$perPage        = min(50, max(1, getGetInt('per_page') ?: 20));

$where  = "WHERE u.role = 'doctor' AND u.is_active = 1";
$params = [];

if ($search) {
    $where   .= ' AND (u.full_name LIKE ? OR dp.specialization LIKE ?)';
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

if ($specialization) {
    $where   .= ' AND dp.specialization = ?';
    $params[] = $specialization;
}

$countSql = "SELECT COUNT(*) FROM users u LEFT JOIN doctor_profiles dp ON dp.user_id = u.id $where";
$countStmt = getDB()->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;

$sql = "SELECT u.id, u.full_name, u.email, u.profile_photo,
               dp.specialization, dp.bio, dp.years_experience, dp.clinic_address, dp.consultation_fee,
               dp.available_days,
               COALESCE(AVG(f.rating), 0) AS avg_rating,
               COUNT(DISTINCT f.id) AS review_count,
               COUNT(DISTINCT a2.patient_id) AS patient_count
        FROM users u
        LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
        LEFT JOIN appointments a  ON a.doctor_id = u.id AND a.status = 'completed'
        LEFT JOIN feedback f      ON f.appointment_id = a.id
        LEFT JOIN appointments a2 ON a2.doctor_id = u.id
        $where
        GROUP BY u.id
        ORDER BY avg_rating DESC, u.full_name ASC
        LIMIT $perPage OFFSET $offset";

$stmt = getDB()->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

foreach ($doctors as &$d) {
    $d['avg_rating']   = round((float) $d['avg_rating'], 1);
    $d['review_count'] = (int) $d['review_count'];
    // Parse available_days JSON into an array for easier frontend consumption
    if (!empty($d['available_days'])) {
        $parsed = json_decode($d['available_days'], true);
        $d['available_days'] = is_array($parsed) ? $parsed : [];
    } else {
        $d['available_days'] = [];
    }
}

jsonSuccess([
    'doctors'    => $doctors,
    'pagination' => [
        'current_page'  => $page,
        'per_page'      => $perPage,
        'total'         => $total,
        'total_pages'   => (int) ceil($total / $perPage),
        'has_prev'      => $page > 1,
        'has_next'      => $page < (int) ceil($total / $perPage),
    ],
]);
