<?php
/**
 * MediQueue — Auto-Cancel Pending Appointments
 * Cron job: Run every hour via cron or manual trigger.
 *
 * Cancels appointments that have been in 'pending' status for over 24 hours.
 * Frees the associated time slots.
 *
 * Usage: php cron/auto-cancel.php
 * Or via web: GET /cron/auto-cancel.php?key=YOUR_CRON_KEY
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/email_templates.php';

// Simple security: require a key if accessed via web
if (php_sapi_name() !== 'cli') {
    $cronKey = $_GET['key'] ?? '';
    if ($cronKey !== 'mq_cron_2026') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

$db = getDB();

// Find pending appointments older than 24 hours
$stmt = $db->prepare(
    "SELECT a.id, a.patient_id, a.doctor_id, a.slot_id, a.appointment_date, a.start_time,
            p.full_name AS patient_name, p.email AS patient_email,
            d.full_name AS doctor_name, d.email AS doctor_email
     FROM appointments a
     JOIN users p ON p.id = a.patient_id
     JOIN users d ON d.id = a.doctor_id
     WHERE a.status = 'pending'
       AND a.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);
$stmt->execute();
$expired = $stmt->fetchAll();

$cancelled = 0;

foreach ($expired as $appt) {
    try {
        $db->beginTransaction();

        // Cancel appointment
        $db->prepare(
            "UPDATE appointments SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = 'Auto-cancelled: pending for over 24 hours' WHERE id = ? AND status = 'pending'"
        )->execute([$appt['id']]);

        if ($db->prepare("SELECT ROW_COUNT()")->fetchColumn() > 0) {
            // Free the slot
            $db->prepare('UPDATE time_slots SET is_available = 1 WHERE id = ?')->execute([$appt['slot_id']]);

            $db->commit();
            $cancelled++;

            // Notify patient
            try {
                $patient = ['full_name' => $appt['patient_name'], 'email' => $appt['patient_email']];
                $doctor  = ['full_name' => $appt['doctor_name'], 'email' => $appt['doctor_email']];
                $html = emailAppointmentCancellation($appt, $patient, $doctor);
                sendMail($appt['patient_email'], 'Appointment Auto-Cancelled — MediQueue', $html);
            } catch (\Throwable $e) {
                error_log('[MediQueue Cron] Email error for auto-cancel #' . $appt['id'] . ': ' . $e->getMessage());
            }
        } else {
            $db->rollBack();
        }
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log('[MediQueue Cron] Auto-cancel error #' . $appt['id'] . ': ' . $e->getMessage());
    }
}

// Also clean up expired slot reservations
$db->exec("DELETE FROM slot_reservations WHERE expires_at < NOW()");

$msg = date('Y-m-d H:i:s') . " — Auto-cancel cron: $cancelled appointment(s) cancelled.";
error_log('[MediQueue Cron] ' . $msg);

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cancelled' => $cancelled, 'message' => $msg]);
} else {
    echo $msg . "\n";
}
