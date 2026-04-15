<?php
/**
 * MediQueue — Patients Report
 * GET api/reports/patients.php
 * Access: staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

$totalPatients  = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$activePatients = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient' AND is_active = 1")->fetchColumn();

// Monthly registrations (last 12 months)
$registrations = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
     FROM users
     WHERE role = 'patient' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month ASC"
)->fetchAll();

// Top patients by appointment frequency
$topPatients = $db->query(
    "SELECT u.id, u.full_name, u.email, u.created_at AS joined,
            COUNT(a.id) AS total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed
     FROM users u
     LEFT JOIN appointments a ON a.patient_id = u.id
     WHERE u.role = 'patient' AND u.is_active = 1
     GROUP BY u.id
     ORDER BY total_appointments DESC
     LIMIT 15"
)->fetchAll();

jsonSuccess([
    'total_patients'  => $totalPatients,
    'active_patients' => $activePatients,
    'registrations'   => $registrations,
    'top_patients'    => $topPatients,
]);
