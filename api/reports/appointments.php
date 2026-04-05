<?php
/**
 * MediQueue — Appointments Report
 * GET api/reports/appointments.php?date_from=&date_to=
 * Access: staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$dateFrom = getGetString('date_from');
$dateTo   = getGetString('date_to');

$db     = getDB();
$where  = '1=1';
$params = [];

if ($dateFrom && isValidDate($dateFrom)) {
    $where   .= ' AND a.appointment_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo && isValidDate($dateTo)) {
    $where   .= ' AND a.appointment_date <= ?';
    $params[] = $dateTo;
}

// Totals
$stmt = $db->prepare("SELECT COUNT(*) AS total,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) AS confirmed
    FROM appointments a WHERE $where");
$stmt->execute($params);
$totals = $stmt->fetch();

$total     = (int) $totals['total'];
$completed = (int) $totals['completed'];
$completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

// By status
$stmt2 = $db->prepare("SELECT status, COUNT(*) AS count FROM appointments a WHERE $where GROUP BY status ORDER BY count DESC");
$stmt2->execute($params);
$byStatus = $stmt2->fetchAll();

jsonSuccess([
    'total'           => $total,
    'completed'       => $completed,
    'cancelled'       => (int) $totals['cancelled'],
    'pending'         => (int) $totals['pending'],
    'confirmed'       => (int) $totals['confirmed'],
    'completion_rate' => $completionRate,
    'by_status'       => $byStatus,
]);
