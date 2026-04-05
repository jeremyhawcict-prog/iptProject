<?php
/**
 * MediQueue — Appointments by Status
 * GET api/reports/by-status.php?from=&to=
 *
 * Returns [{status, count}] grouped by appointment status.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['admin', 'staff']);

global $pdo;

$from = getGetString('from');
$to   = getGetString('to');

$where  = [];
$params = [];

if ($from && isValidDate($from)) {
    $where[]  = 'appointment_date >= ?';
    $params[] = $from;
}
if ($to && isValidDate($to)) {
    $where[]  = 'appointment_date <= ?';
    $params[] = $to;
}

$sql = "SELECT status, COUNT(*) AS count FROM appointments";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " GROUP BY status ORDER BY FIELD(status, 'pending','confirmed','completed','cancelled','rescheduled')";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess($rows);