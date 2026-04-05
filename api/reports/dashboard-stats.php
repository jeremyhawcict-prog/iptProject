<?php
/**
 * MediQueue — Dashboard Stats
 * GET api/reports/dashboard-stats.php
 *
 * Returns KPI metrics for admin dashboard cards.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['admin', 'staff']);

$pdo = getDB();

$totalUsers    = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalDoctors  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
$totalPatients = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$apptToday     = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$apptThisMonth = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();

$totalAppts     = (int) $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$completedAppts = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed'")->fetchColumn();
$cancelledAppts = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'")->fetchColumn();

$completionRate   = $totalAppts > 0 ? round(($completedAppts / $totalAppts) * 100, 1) : 0;
$cancellationRate = $totalAppts > 0 ? round(($cancelledAppts / $totalAppts) * 100, 1) : 0;

$avgRating = (float) ($pdo->query("SELECT ROUND(AVG(rating), 1) FROM feedback")->fetchColumn() ?: 0);

jsonSuccess([
    'total_users'       => $totalUsers,
    'total_doctors'     => $totalDoctors,
    'total_patients'    => $totalPatients,
    'appointments_today'=> $apptToday,
    'this_month'        => $apptThisMonth,
    'completion_rate'   => $completionRate,
    'cancellation_rate' => $cancellationRate,
    'avg_rating'        => $avgRating,
]);