<?php
/**
 * MediQueue — Doctor Schedule (time slots)
 * GET api/doctors/schedule.php?doctor_id=&from=&to=
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$doctorId = getGetInt('doctor_id');
$from     = getGetString('from');
$to       = getGetString('to');

if (!$doctorId) {
    jsonError('doctor_id is required.');
}

$where  = 'WHERE ts.doctor_id = ?';
$params = [$doctorId];

if ($from && isValidDate($from)) {
    $where   .= ' AND ts.slot_date >= ?';
    $params[] = $from;
}
if ($to && isValidDate($to)) {
    $where   .= ' AND ts.slot_date <= ?';
    $params[] = $to;
}

$stmt = getDB()->prepare(
    "SELECT ts.id, ts.slot_date, ts.start_time, ts.end_time, ts.is_available,
            CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END AS is_booked
     FROM time_slots ts
     LEFT JOIN appointments a ON a.slot_id = ts.id AND a.status IN ('pending','confirmed')
     $where
     ORDER BY ts.slot_date ASC, ts.start_time ASC"
);
$stmt->execute($params);
$slots = $stmt->fetchAll();

foreach ($slots as &$s) {
    $s['is_available'] = (bool) $s['is_available'];
    $s['is_booked']    = (bool) $s['is_booked'];
}

jsonSuccess(['slots' => $slots]);