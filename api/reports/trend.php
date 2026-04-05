<?php
/**
 * MediQueue — Appointment Trend
 * GET api/reports/trend.php?days=30
 *
 * Returns daily appointment counts for the last N days.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['admin', 'staff']);

global $pdo;

$days = getGetInt('days', 30);
if ($days < 1)  $days = 1;
if ($days > 365) $days = 365;

$stmt = $pdo->prepare("
    SELECT d.date, COALESCE(cnt, 0) AS count
    FROM (
        SELECT CURDATE() - INTERVAL seq DAY AS date
        FROM (
            SELECT a.N + b.N * 10 + c.N * 100 AS seq
            FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                 (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                 (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) c
        ) seq_table
        WHERE seq < ?
    ) d
    LEFT JOIN (
        SELECT appointment_date, COUNT(*) AS cnt
        FROM appointments
        WHERE appointment_date >= CURDATE() - INTERVAL ? DAY
        GROUP BY appointment_date
    ) a ON d.date = a.appointment_date
    ORDER BY d.date ASC
");
$stmt->execute([$days, $days]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonSuccess($rows);