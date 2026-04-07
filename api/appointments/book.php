<?php
/**
 * MediQueue — Book Appointment (Patient only)
 * POST api/appointments/book.php
 *
 * Required POST params: slot_id, reason_for_visit (optional)
 * Uses DB TRANSACTION to prevent double-booking.
 */

require_once __DIR__ . '/../../includes/api_guard.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/email_templates.php';

guardApi('POST', true, true, ['patient']);

$slotId = getPostInt('slot_id');
$reason = getPostString('reason_for_visit', '') ?: getPostString('notes', '');
$visitType = getPostString('visit_type', 'General Checkup');
$reminderPref = getPostString('reminder_preference', 'email');

if (!$slotId) {
    jsonError('Please select a time slot.', 400);
}

// Validate visit_type
$validVisitTypes = ['General Checkup', 'Follow-up', 'Vaccination', 'Specialist Consult', 'Emergency'];
if (!in_array($visitType, $validVisitTypes, true)) {
    $visitType = 'General Checkup';
}

// Validate reminder_preference
$validReminders = ['email', 'sms', 'both'];
if (!in_array($reminderPref, $validReminders, true)) {
    $reminderPref = 'email';
}

$db = getDB();

try {
    $db->beginTransaction();

    // 1. Lock and verify the slot is still available
    $stmt = $db->prepare(
        'SELECT ts.*, u.full_name AS doctor_name
         FROM time_slots ts
         JOIN users u ON u.id = ts.doctor_id
         WHERE ts.id = ? AND ts.is_available = 1
         FOR UPDATE'
    );
    $stmt->execute([$slotId]);
    $slot = $stmt->fetch();

    if (!$slot) {
        $db->rollBack();
        jsonError('This time slot is no longer available. Please choose another.', 409);
    }

    $patientId = (int) $_SESSION['user_id'];
    $doctorId  = (int) $slot['doctor_id'];
    $slotDate  = $slot['slot_date'];
    $startTime = $slot['start_time'];

    // 2. Double-booking guard: patient must not have an active appointment with same doctor on same date
    $checkStmt = $db->prepare(
        "SELECT id FROM appointments
         WHERE patient_id = ? AND doctor_id = ? AND appointment_date = ?
           AND status IN ('pending','confirmed')
         LIMIT 1"
    );
    $checkStmt->execute([$patientId, $doctorId, $slotDate]);
    if ($checkStmt->fetch()) {
        $db->rollBack();
        jsonError('You already have an active appointment with this doctor on ' . formatDate($slotDate) . '.', 409);
    }

    // 3. Insert appointment
    $insStmt = $db->prepare(
        "INSERT INTO appointments (patient_id, doctor_id, slot_id, appointment_date, start_time, status, visit_type, reason_for_visit, reminder_preference)
         VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)"
    );
    $insStmt->execute([$patientId, $doctorId, $slotId, $slotDate, $startTime, $visitType, $reason, $reminderPref]);
    $appointmentId = (int) $db->lastInsertId();

    // 4. Mark slot unavailable
    $db->prepare('UPDATE time_slots SET is_available = 0 WHERE id = ?')->execute([$slotId]);

    $db->commit();

    // 5. Load full data for email
    $appointment = getAppointmentById($appointmentId);
    $patient     = getUserById($patientId);
    $doctor      = getUserById($doctorId);

    // 6. Send email notifications (failure must NOT break the booking)
    try {
        $html = emailAppointmentConfirmation($appointment, $patient, $doctor, 'pending');
        sendMail($patient['email'], 'Appointment Booked — MediQueue', $html);
        sendMail($doctor['email'],  'New Appointment Booked — MediQueue', $html);
    } catch (\Throwable $e) {
        error_log('[MediQueue] Email failed after booking #' . $appointmentId . ': ' . $e->getMessage());
    }

    jsonSuccess([
        'appointment_id' => $appointmentId,
        'doctor'         => $slot['doctor_name'],
        'date'           => formatDate($slotDate),
        'time'           => formatTime($startTime),
        'status'         => 'pending',
    ], 'Appointment booked successfully.', 201);

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[MediQueue] Booking error: ' . $e->getMessage());
    jsonError('An unexpected error occurred. Please try again.', 500);
}
