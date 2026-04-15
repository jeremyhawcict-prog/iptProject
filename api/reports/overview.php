<?php
/**
 * MediQueue — Reports Overview
 * GET api/reports/overview.php
 * Access: staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

// ── Aggregate summary counts ───────────────────────────────
$summary = $db->query(
    "SELECT
        COUNT(*) AS total_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
        SUM(CASE WHEN status IN ('pending','confirmed') THEN 1 ELSE 0 END) AS upcoming
     FROM appointments"
)->fetch();

$totalDoctors  = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor' AND is_active = 1")->fetchColumn();
$totalPatients = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'patient' AND is_active = 1")->fetchColumn();

// ── Monthly appointment counts (last 12 months) ────────────
$monthly = $db->query(
    "SELECT DATE_FORMAT(appointment_date, '%Y-%m') AS month, COUNT(*) AS count
     FROM appointments
     WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month ASC"
)->fetchAll();

// ── Appointment status breakdown (all time) ────────────────
$byStatus = $db->query(
    "SELECT status, COUNT(*) AS count FROM appointments GROUP BY status ORDER BY count DESC"
)->fetchAll();

// ── By specialization ──────────────────────────────────────
$specializations = $db->query(
    "SELECT COALESCE(dp.specialization, 'General') AS specialization, COUNT(*) AS count
     FROM appointments a
     LEFT JOIN doctor_profiles dp ON dp.user_id = a.doctor_id
     GROUP BY specialization
     ORDER BY count DESC"
)->fetchAll();

// ── Daily counts (last 30 days) ────────────────────────────
$daily = $db->query(
    "SELECT DATE(appointment_date) AS date, COUNT(*) AS count
     FROM appointments
     WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY date
     ORDER BY date ASC"
)->fetchAll();

jsonSuccess([
    'summary' => [
        'total_appointments' => (int) $summary['total_appointments'],
        'completed'          => (int) $summary['completed'],
        'cancelled'          => (int) $summary['cancelled'],
        'upcoming'           => (int) $summary['upcoming'],
        'total_doctors'      => $totalDoctors,
        'total_patients'     => $totalPatients,
    ],
    'monthly'         => $monthly,
    'by_status'       => $byStatus,
    'specializations' => $specializations,
    'daily'           => $daily,
]);
