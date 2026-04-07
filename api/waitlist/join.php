<?php
/**
 * MediQueue — Join Waitlist
 * POST api/waitlist/join.php
 *
 * Body: { doctor_id, preferred_date }
 * Adds patient to waitlist for a fully-booked doctor on a specific date.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['patient']);

$doctorId = getPostInt('doctor_id');
$prefDate = getPostString('preferred_date');
$userId   = (int) $_SESSION['user_id'];

if (!$doctorId) {
    jsonError('Doctor is required.', 400);
}
if (!$prefDate || !isValidDate($prefDate) || !isFutureDate($prefDate)) {
    jsonError('Please provide a valid future date.', 400);
}

$db = getDB();

// Check doctor exists
$doc = $db->prepare('SELECT id FROM users WHERE id = ? AND role = "doctor" AND is_active = 1 LIMIT 1');
$doc->execute([$doctorId]);
if (!$doc->fetch()) {
    jsonError('Doctor not found.', 404);
}

// Check if already on waitlist
$existing = $db->prepare(
    "SELECT id FROM waitlist WHERE patient_id = ? AND doctor_id = ? AND preferred_date = ? AND status = 'waiting' LIMIT 1"
);
$existing->execute([$userId, $doctorId, $prefDate]);
if ($existing->fetch()) {
    jsonError('You are already on the waitlist for this doctor on this date.', 409);
}

$db->prepare(
    'INSERT INTO waitlist (patient_id, doctor_id, preferred_date) VALUES (?, ?, ?)'
)->execute([$userId, $doctorId, $prefDate]);

$waitlistId = (int) $db->lastInsertId();

jsonSuccess([
    'waitlist_id'    => $waitlistId,
    'preferred_date' => formatDate($prefDate),
], 'You have been added to the waitlist. We will notify you when a slot opens up.', 201);
