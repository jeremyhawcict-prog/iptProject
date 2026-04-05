<?php
/**
 * MediQueue — Get Single Appointment
 * GET api/appointments/get.php?id=
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$id = getGetInt('id');
if (!$id) {
    jsonError('Missing appointment id.', 400);
}

$db   = getDB();
$stmt = $db->prepare(
    "SELECT a.*,
            p.full_name AS patient_name, p.email AS patient_email, p.phone AS patient_phone, p.profile_photo AS patient_photo,
            d.full_name AS doctor_name, d.email AS doctor_email, d.phone AS doctor_phone, d.profile_photo AS doctor_photo,
            dp.specialization, dp.clinic_address, dp.consultation_fee
     FROM appointments a
     JOIN users p  ON p.id = a.patient_id
     JOIN users d  ON d.id = a.doctor_id
     LEFT JOIN doctor_profiles dp ON dp.user_id = d.id
     WHERE a.id = ?
     LIMIT 1"
);
$stmt->execute([$id]);
$appt = $stmt->fetch();

if (!$appt) {
    jsonError('Appointment not found.', 404);
}

// Enforce ownership: patients see own, doctors see own, staff/admin see all
$role   = $_SESSION['user_role'];
$userId = (int) $_SESSION['user_id'];
if ($role === 'patient' && (int) $appt['patient_id'] !== $userId) {
    jsonError('Access denied.', 403);
}
if ($role === 'doctor' && (int) $appt['doctor_id'] !== $userId) {
    jsonError('Access denied.', 403);
}

jsonSuccess(['appointment' => $appt]);
