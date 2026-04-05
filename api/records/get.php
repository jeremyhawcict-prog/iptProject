<?php
/**
 * MediQueue — Get Patient Record by Appointment
 * GET api/records/get.php?appointment_id=
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$appointmentId = getGetInt('appointment_id');
$recordId      = getGetInt('id');

if (!$appointmentId && !$recordId) {
    jsonError('appointment_id or id is required.', 400);
}

$db     = getDB();
$role   = $_SESSION['user_role'];
$userId = (int) $_SESSION['user_id'];

if ($appointmentId) {
    $stmt = $db->prepare(
        'SELECT pr.*, d.full_name AS doctor_name, p.full_name AS patient_name
         FROM patient_records pr
         JOIN users d ON d.id = pr.doctor_id
         JOIN users p ON p.id = pr.patient_id
         WHERE pr.appointment_id = ?
         LIMIT 1'
    );
    $stmt->execute([$appointmentId]);
} else {
    $stmt = $db->prepare(
        'SELECT pr.*, d.full_name AS doctor_name, p.full_name AS patient_name
         FROM patient_records pr
         JOIN users d ON d.id = pr.doctor_id
         JOIN users p ON p.id = pr.patient_id
         WHERE pr.id = ?
         LIMIT 1'
    );
    $stmt->execute([$recordId]);
}

$record = $stmt->fetch();

if (!$record) {
    jsonError('Record not found.', 404);
}

// Enforce ownership
if ($role === 'patient' && (int) $record['patient_id'] !== $userId) {
    jsonError('Access denied.', 403);
}
if ($role === 'doctor' && (int) $record['doctor_id'] !== $userId) {
    jsonError('Access denied.', 403);
}

jsonSuccess(['record' => $record]);
