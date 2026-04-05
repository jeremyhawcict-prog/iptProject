<?php
/**
 * MediQueue — Reports Overview
 * GET api/reports/overview.php
 * Access: staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

// Monthly appointment counts (last 12 months)
$monthly = $db->query(
    "SELECT DATE_FORMAT(appointment_date, '%Y-%m') AS month, COUNT(*) AS count
     FROM appointments
     WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month ASC"
)->fetchAll();

// By specialization
$specializations = $db->query(
    "SELECT COALESCE(dp.specialization, 'General') AS specialization, COUNT(*) AS count
     FROM appointments a
     LEFT JOIN doctor_profiles dp ON dp.user_id = a.doctor_id
     GROUP BY specialization
     ORDER BY count DESC"
)->fetchAll();

// Daily counts (last 30 days)
$daily = $db->query(
    "SELECT DATE(appointment_date) AS date, COUNT(*) AS count
     FROM appointments
     WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY date
     ORDER BY date ASC"
)->fetchAll();

jsonSuccess([
    'monthly'         => $monthly,
    'specializations' => $specializations,
    'daily'           => $daily,
]);
