<?php
/**
 * MediQueue — Submit Feedback (Patient only)
 * POST api/feedback/submit.php
 *
 * Required: appointment_id, rating (1-5)
 * Optional: comments
 *
 * Rules:
 *   - Patient role only
 *   - Appointment must be 'completed'
 *   - Only one feedback per appointment (UNIQUE constraint)
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['patient']);

$appointmentId = getPostInt('appointment_id');
$rating        = getPostInt('rating');
$comments      = getPostString('comments', '');

if (!$appointmentId) {
    jsonError('appointment_id is required.', 400);
}
if ($rating < 1 || $rating > 5) {
    jsonError('Rating must be between 1 and 5.', 400);
}

$appointment = getAppointmentById($appointmentId);
if (!$appointment) {
    jsonError('Appointment not found.', 404);
}

$userId = (int) $_SESSION['user_id'];
if ((int) $appointment['patient_id'] !== $userId) {
    jsonError('You can only leave feedback for your own appointments.', 403);
}
if ($appointment['status'] !== 'completed') {
    jsonError('Feedback can only be submitted for completed appointments.', 400);
}

$db = getDB();

// Check if feedback already exists
$checkStmt = $db->prepare('SELECT id FROM feedback WHERE appointment_id = ? LIMIT 1');
$checkStmt->execute([$appointmentId]);
if ($checkStmt->fetch()) {
    jsonError('You have already submitted feedback for this appointment.', 409);
}

$stmt = $db->prepare(
    'INSERT INTO feedback (patient_id, doctor_id, appointment_id, rating, comments)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([
    $userId,
    (int) $appointment['doctor_id'],
    $appointmentId,
    $rating,
    $comments,
]);

jsonSuccess([
    'feedback_id' => (int) $db->lastInsertId(),
], 'Thank you for your feedback!', 201);
