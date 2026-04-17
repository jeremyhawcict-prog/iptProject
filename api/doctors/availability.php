<?php
/**
 * MediQueue — Doctor Availability (available slots for a date)
 * GET api/doctors/availability.php?doctor_id=&date=
 *
 * Used by the Patient booking wizard (Step 2).
 * Returns only available (is_available=1) time slots.
 * Public-ish: requires login but no specific role.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$doctorId = getGetInt('doctor_id');
$date     = getGetString('date');

if (!$doctorId) {
    jsonError('doctor_id is required.', 400);
}
if (!$date || !isValidDate($date)) {
    jsonError('A valid date (YYYY-MM-DD) is required.', 400);
}

$doctor = getDoctorProfile($doctorId);
if (!$doctor) {
    jsonError('Doctor not found.', 404);
}

// Enforce doctor's working days
if (!empty($doctor['available_days'])) {
    $availDays = json_decode($doctor['available_days'], true);
    if (!is_array($availDays)) {
        $availDays = is_string($doctor['available_days']) ? array_map('trim', explode(',', $doctor['available_days'])) : [];
    }
    
    // Normalize to proper-case days (e.g. 'Monday', 'Tuesday')
    $normalizedAvailDays = array_map(function($d) {
        return ucfirst(strtolower(trim($d)));
    }, $availDays);
    
    if (count($normalizedAvailDays) > 0) {
        $dayOfWeek = date('l', strtotime($date));
        if (!in_array($dayOfWeek, $normalizedAvailDays)) {
            jsonSuccess([
                'doctor'  => [
                    'id'             => (int) $doctor['id'],
                    'full_name'      => $doctor['full_name'],
                    'specialization' => $doctor['specialization'],
                ],
                'date'    => $date,
                'slots'   => [],
                'count'   => 0
            ]);
        }
    }
}

$db   = getDB();
$stmt = $db->prepare(
    'SELECT id, slot_date, start_time, end_time
     FROM time_slots
     WHERE doctor_id = ? AND slot_date = ? AND is_available = 1
     ORDER BY start_time ASC'
);
$stmt->execute([$doctorId, $date]);
$slots = $stmt->fetchAll();

// Format for frontend
$formatted = array_map(function ($s) {
    return [
        'slot_id'    => (int) $s['id'],
        'date'       => $s['slot_date'],
        'start_time' => $s['start_time'],
        'end_time'   => $s['end_time'],
        'label'      => formatTime($s['start_time']) . ' – ' . formatTime($s['end_time']),
    ];
}, $slots);

jsonSuccess([
    'doctor'  => [
        'id'             => (int) $doctor['id'],
        'full_name'      => $doctor['full_name'],
        'specialization' => $doctor['specialization'],
    ],
    'date'    => $date,
    'slots'   => $formatted,
    'count'   => count($formatted),
]);
