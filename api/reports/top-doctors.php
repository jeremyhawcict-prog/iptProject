<?php
/**
 * MediQueue — Top Doctors
 * GET api/reports/top-doctors.php?from=&to=
 *
 * Returns top 5 doctors by appointment count + avg rating.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['admin', 'staff']);

global $pdo;

$from = getGetString('from');
$to   = getGetString('to');

$dateFilter = '';
$params     = [];

if ($from && isValidDate($from)) {
    $dateFilter .= ' AND a.appointment_date >= ?';
    $params[]    = $from;
}
if ($to && isValidDate($to)) {
    $dateFilter .= ' AND a.appointment_date <= ?';
    $params[]    = $to;
}

$sql = "
    SELECT
        u.id,
        u.full_name,
        dp.specialization,
        COUNT(a.id)                          AS appointment_count,
        ROUND(AVG(f.rating), 1)              AS avg_rating,
        SUM(a.status = 'completed')          AS completed_count
    FROM users u
    LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
    LEFT JOIN appointments a     ON a.doctor_id = u.id {$dateFilter}
    LEFT JOIN feedback f         ON f.appointment_id = a.id
    WHERE u.role = 'doctor'
    GROUP BY u.id, u.full_name, dp.specialization
    ORDER BY appointment_count DESC, avg_rating DESC
    LIMIT 5
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess($rows);