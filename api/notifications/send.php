<?php
/**
 * MediQueue — Send / Create Notification
 * POST api/notifications/send.php
 *
 * Body: { user_id, type, subject, message, appointment_id? }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['admin', 'staff']);

$userId        = getPostInt('user_id');
$type          = getPostString('type');
$subject       = getPostString('subject');
$message       = getPostString('message');
$appointmentId = getPostInt('appointment_id');

if (!$userId || !$type || !$subject || !$message) {
    jsonError('user_id, type, subject and message are required.');
}

$allowedTypes = ['email', 'sms', 'system'];
if (!in_array($type, $allowedTypes, true)) {
    jsonError('type must be one of: ' . implode(', ', $allowedTypes));
}

// Verify user exists
$targetUser = getUserById($userId);
if (!$targetUser) {
    jsonError('User not found.', 404);
}

$stmt = getDB()->prepare(
    'INSERT INTO notifications (user_id, appointment_id, type, subject, message, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW())'
);
$stmt->execute([
    $userId,
    $appointmentId ?: null,
    $type,
    $subject,
    $message,
    $type === 'system' ? 'sent' : 'pending',
]);

$id = (int) getDB()->lastInsertId();

jsonSuccess(['notification_id' => $id], 'Notification created successfully.', 201);