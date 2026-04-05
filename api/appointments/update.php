<?php
/**
 * MediQueue — Update Appointment Status
 * POST api/appointments/update.php
 *
 * Required POST params: appointment_id, action
 * Actions: confirm, cancel, complete, reschedule
 *
 * Access matrix (from Section 4.3):
 *   confirm     → staff, admin
 *   cancel      → patient (own only), staff, admin
 *   reschedule  → patient (own only), staff, admin
 *   complete    → doctor (own only), staff, admin
 */

require_once __DIR__ . '/../../includes/api_guard.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/email_templates.php';

guardApi('POST', true, true);

$appointmentId = getPostInt('appointment_id');
$action        = getPostString('action');
$newSlotId     = getPostInt('new_slot_id');   // only for reschedule

if (!$appointmentId || !$action) {
    jsonError('Missing appointment_id or action.', 400);
}

$validActions = ['confirm', 'cancel', 'complete', 'reschedule'];
if (!in_array($action, $validActions, true)) {
    jsonError('Invalid action. Must be one of: ' . implode(', ', $validActions), 400);
}

$appointment = getAppointmentById($appointmentId);
if (!$appointment) {
    jsonError('Appointment not found.', 404);
}

$role      = $_SESSION['user_role'];
$userId    = (int) $_SESSION['user_id'];
$patientId = (int) $appointment['patient_id'];
$doctorId  = (int) $appointment['doctor_id'];

// ── Permission checks ────────────────────────────────────
switch ($action) {
    case 'confirm':
        if ($role === 'doctor') {
            if ($doctorId !== $userId) {
                jsonError('You can only confirm your own appointments.', 403);
            }
        } elseif (!in_array($role, ['staff', 'admin'], true)) {
            jsonError('Only the assigned doctor, staff, or admin can confirm appointments.', 403);
        }
        if ($appointment['status'] !== 'pending') {
            jsonError('Only pending appointments can be confirmed.', 400);
        }
        break;

    case 'cancel':
        if ($role === 'patient') {
            if ($patientId !== $userId) {
                jsonError('You can only cancel your own appointments.', 403);
            }
        } elseif ($role === 'doctor') {
            if ($doctorId !== $userId) {
                jsonError('You can only cancel your own appointments.', 403);
            }
        } elseif (!in_array($role, ['staff', 'admin'], true)) {
            jsonError('You do not have permission to cancel this appointment.', 403);
        }
        if (in_array($appointment['status'], ['completed', 'cancelled'], true)) {
            jsonError('This appointment cannot be cancelled.', 400);
        }
        break;

    case 'complete':
        if ($role === 'doctor') {
            if ($doctorId !== $userId) {
                jsonError('You can only complete your own appointments.', 403);
            }
        } elseif (!in_array($role, ['staff', 'admin'], true)) {
            jsonError('You do not have permission to complete this appointment.', 403);
        }
        if ($appointment['status'] !== 'confirmed') {
            jsonError('Only confirmed appointments can be completed.', 400);
        }
        break;

    case 'reschedule':
        if ($role === 'patient') {
            if ($patientId !== $userId) {
                jsonError('You can only reschedule your own appointments.', 403);
            }
        } elseif (!in_array($role, ['staff', 'admin'], true)) {
            jsonError('You do not have permission to reschedule this appointment.', 403);
        }
        if (in_array($appointment['status'], ['completed', 'cancelled'], true)) {
            jsonError('This appointment cannot be rescheduled.', 400);
        }
        if (!$newSlotId) {
            jsonError('Please select a new time slot for rescheduling.', 400);
        }
        break;
}

$db = getDB();

try {
    $db->beginTransaction();

    $patient = getUserById($patientId);
    $doctor  = getUserById($doctorId);
    $oldDate = $appointment['appointment_date'];
    $oldTime = $appointment['start_time'];

    switch ($action) {

        /* ── CONFIRM ─────────────────────────────────────── */
        case 'confirm':
            $db->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ?")->execute([$appointmentId]);
            $appointment['status'] = 'confirmed';
            $db->commit();

            try {
                $html = emailAppointmentConfirmation($appointment, $patient, $doctor, 'confirmed');
                sendMail($patient['email'], 'Appointment Confirmed — MediQueue', $html);
                sendMail($doctor['email'],  'Appointment Confirmed — MediQueue', $html);
            } catch (\Throwable $e) {
                error_log('[MediQueue] Email error (confirm #' . $appointmentId . '): ' . $e->getMessage());
            }

            jsonSuccess(['appointment_id' => $appointmentId, 'status' => 'confirmed'], 'Appointment confirmed.');
            break;

        /* ── CANCEL ──────────────────────────────────────── */
        case 'cancel':
            $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([$appointmentId]);
            // Free the slot
            $db->prepare('UPDATE time_slots SET is_available = 1 WHERE id = ?')->execute([$appointment['slot_id']]);
            $db->commit();

            try {
                $html = emailAppointmentCancellation($appointment, $patient, $doctor);
                sendMail($patient['email'], 'Appointment Cancelled — MediQueue', $html);
                sendMail($doctor['email'],  'Appointment Cancelled — MediQueue', $html);
            } catch (\Throwable $e) {
                error_log('[MediQueue] Email error (cancel #' . $appointmentId . '): ' . $e->getMessage());
            }

            jsonSuccess(['appointment_id' => $appointmentId, 'status' => 'cancelled'], 'Appointment cancelled. Time slot has been freed.');
            break;

        /* ── COMPLETE ────────────────────────────────────── */
        case 'complete':
            $db->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?")->execute([$appointmentId]);
            $appointment['status'] = 'completed';
            $db->commit();

            try {
                $html = emailAppointmentConfirmation($appointment, $patient, $doctor, 'completed');
                sendMail($patient['email'], 'Appointment Completed — MediQueue', $html);
                sendMail($doctor['email'],  'Appointment Completed — MediQueue', $html);
            } catch (\Throwable $e) {
                error_log('[MediQueue] Email error (complete #' . $appointmentId . '): ' . $e->getMessage());
            }

            jsonSuccess(['appointment_id' => $appointmentId, 'status' => 'completed'], 'Appointment marked as completed.');
            break;

        /* ── RESCHEDULE ──────────────────────────────────── */
        case 'reschedule':
            // Lock and verify new slot
            $slotStmt = $db->prepare(
                'SELECT * FROM time_slots WHERE id = ? AND is_available = 1 FOR UPDATE'
            );
            $slotStmt->execute([$newSlotId]);
            $newSlot = $slotStmt->fetch();

            if (!$newSlot) {
                $db->rollBack();
                jsonError('The selected time slot is no longer available.', 409);
            }

            // Verify new slot belongs to the same doctor
            if ((int) $newSlot['doctor_id'] !== $doctorId) {
                $db->rollBack();
                jsonError('The selected slot does not belong to the same doctor.', 400);
            }

            // Update appointment with new slot info
            $db->prepare(
                "UPDATE appointments
                 SET status = 'rescheduled', slot_id = ?, appointment_date = ?, start_time = ?
                 WHERE id = ?"
            )->execute([$newSlotId, $newSlot['slot_date'], $newSlot['start_time'], $appointmentId]);

            // Free old slot, block new slot
            $db->prepare('UPDATE time_slots SET is_available = 1 WHERE id = ?')->execute([$appointment['slot_id']]);
            $db->prepare('UPDATE time_slots SET is_available = 0 WHERE id = ?')->execute([$newSlotId]);

            $db->commit();

            // Update appointment array for email
            $appointment['appointment_date'] = $newSlot['slot_date'];
            $appointment['start_time']       = $newSlot['start_time'];
            $appointment['status']           = 'rescheduled';

            try {
                $html = emailAppointmentRescheduled($appointment, $patient, $doctor, $oldDate, $oldTime);
                sendMail($patient['email'], 'Appointment Rescheduled — MediQueue', $html);
                sendMail($doctor['email'],  'Appointment Rescheduled — MediQueue', $html);
            } catch (\Throwable $e) {
                error_log('[MediQueue] Email error (reschedule #' . $appointmentId . '): ' . $e->getMessage());
            }

            jsonSuccess([
                'appointment_id' => $appointmentId,
                'status'         => 'rescheduled',
                'new_date'       => formatDate($newSlot['slot_date']),
                'new_time'       => formatTime($newSlot['start_time']),
            ], 'Appointment rescheduled successfully.');
            break;
    }

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[MediQueue] Update appointment error: ' . $e->getMessage());
    jsonError('An unexpected error occurred. Please try again.', 500);
}
