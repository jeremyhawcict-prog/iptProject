<?php
/**
 * MediQueue — Patients Report
 * GET api/reports/patients.php
 * Access: staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

$totalPatients = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$activePatients = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient' AND is_active = 1")->fetchColumn();

// Monthly registrations (last 12 months)
$registrations = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
     FROM users
     WHERE role = 'patient' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month ASC"
)->fetchAll();

jsonSuccess([
    'total_patients'  => $totalPatients,
    'active_patients' => $activePatients,
    'avg_age'         => 0,
    'registrations'   => $registrations,
]);
