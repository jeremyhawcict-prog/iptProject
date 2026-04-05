<?php
/**
 * MediQueue — List Feedback (Public)
 * GET api/feedback/list.php
 *
 * Query params (all optional):
 *   doctor_id — filter by doctor
 *   page, limit — pagination
 *
 * No authentication required.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', false, false);  // public — no auth, no CSRF

$doctorId = getGetInt('doctor_id');
$page     = getGetInt('page', 1);
$limit    = getGetInt('limit', 20);
$pag      = getPagination($page, $limit);

$where  = [];
$params = [];

if ($doctorId) {
    $where[]  = 'f.doctor_id = ?';
    $params[] = $doctorId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$db = getDB();

// Total
$countStmt = $db->prepare("SELECT COUNT(*) FROM feedback f $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

// Fetch
$sql = "SELECT f.id, f.rating, f.comments, f.created_at,
               p.full_name AS patient_name, p.profile_photo AS patient_photo,
               d.full_name AS doctor_name, dp.specialization
        FROM feedback f
        JOIN users p  ON p.id = f.patient_id
        JOIN users d  ON d.id = f.doctor_id
        LEFT JOIN doctor_profiles dp ON dp.user_id = d.id
        $whereSql
        ORDER BY f.created_at DESC
        LIMIT {$pag['limit']} OFFSET {$pag['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// If doctor_id given, also return average rating
$avg = null;
if ($doctorId) {
    $avgStmt = $db->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews FROM feedback WHERE doctor_id = ?');
    $avgStmt->execute([$doctorId]);
    $avg = $avgStmt->fetch();
    $avg['avg_rating'] = $avg['avg_rating'] ? round((float) $avg['avg_rating'], 1) : null;
}

jsonSuccess([
    'feedback'   => $rows,
    'summary'    => $avg,
    'pagination' => paginationResponse($pag['page'], $pag['limit'], $total),
]);
