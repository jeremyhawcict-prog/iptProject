<?php
/**
 * MediQueue — Doctor Performance Report
 * GET api/reports/doctors.php
 * Access: staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

$stmt = $db->query(
    "SELECT u.id, u.full_name,
            COALESCE(dp.specialization, 'General') AS specialization,
            COUNT(a.id) AS total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            COALESCE(AVG(f.rating), 0) AS avg_rating
     FROM users u
     LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
     LEFT JOIN appointments a ON a.doctor_id = u.id
     LEFT JOIN feedback f ON f.appointment_id = a.id AND a.status = 'completed'
     WHERE u.role = 'doctor' AND u.is_active = 1
     GROUP BY u.id
     ORDER BY total_appointments DESC"
);
$doctors = $stmt->fetchAll();

foreach ($doctors as &$d) {
    $d['avg_rating'] = round((float) $d['avg_rating'], 1);
}

jsonSuccess(['doctors' => $doctors]);
