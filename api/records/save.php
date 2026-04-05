<?php
/**
 * MediQueue — Save Patient Record
 * POST api/records/save.php
 *
 * Body: { appointment_id, diagnosis, notes?, prescription? }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['doctor', 'staff', 'admin']);

$appointmentId = getPostInt('appointment_id');
$diagnosis     = getPostString('diagnosis');
$notes         = getPostString('notes');
$prescription  = getPostString('prescription');

if (!$appointmentId || !$diagnosis) {
    jsonError('appointment_id and diagnosis are required.');
}

$appt = getAppointmentById($appointmentId);
if (!$appt) {
    jsonError('Appointment not found.', 404);
}

// Doctors can only create records for their own appointments
if ($_SESSION['user_role'] === 'doctor' && (int) $appt['doctor_id'] !== (int) $_SESSION['user_id']) {
    jsonError('You can only add records for your own appointments.', 403);
}

$visitDate = $appt['appointment_date'] ?? date('Y-m-d');

$stmt = getDB()->prepare(
    'INSERT INTO patient_records (appointment_id, patient_id, doctor_id, diagnosis, prescription, notes, visit_date, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
);
$stmt->execute([
    $appointmentId,
    $appt['patient_id'],
    $appt['doctor_id'],
    $diagnosis,
    $prescription,
    $notes,
    $visitDate,
]);

$id = (int) getDB()->lastInsertId();

jsonSuccess(['record_id' => $id], 'Record saved successfully.', 201);