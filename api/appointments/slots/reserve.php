<?php
/**
 * MediQueue — Reserve a Time Slot (5-minute temporary hold)
 * POST api/appointments/slots/reserve.php
 *
 * Body: { slot_id }
 * Prevents other patients from booking the same slot while this patient is filling the form.
 */

require_once __DIR__ . '/../../../includes/api_guard.php';

guardApi('POST', true, true, ['patient']);

$slotId = getPostInt('slot_id');
if (!$slotId) {
    jsonError('Please select a time slot.', 400);
}

$userId = (int) $_SESSION['user_id'];
$db = getDB();

try {
    $db->beginTransaction();

    // Clean up expired reservations first
    $db->exec("DELETE FROM slot_reservations WHERE expires_at < NOW()");

    // Check if slot is still available
    $stmt = $db->prepare(
        'SELECT id, is_available FROM time_slots WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$slotId]);
    $slot = $stmt->fetch();

    if (!$slot || !$slot['is_available']) {
        $db->rollBack();
        jsonError('This time slot is no longer available.', 409);
    }

    // Check if slot is already reserved by someone else
    $resStmt = $db->prepare(
        'SELECT id, user_id FROM slot_reservations WHERE slot_id = ? AND expires_at > NOW() LIMIT 1'
    );
    $resStmt->execute([$slotId]);
    $existing = $resStmt->fetch();

    if ($existing && (int) $existing['user_id'] !== $userId) {
        $db->rollBack();
        jsonError('This slot is temporarily reserved by another patient. Please try again shortly.', 409);
    }

    // If already reserved by this user, extend
    if ($existing) {
        $db->prepare(
            'UPDATE slot_reservations SET expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?'
        )->execute([$existing['id']]);
    } else {
        $db->prepare(
            'INSERT INTO slot_reservations (slot_id, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))'
        )->execute([$slotId, $userId]);
    }

    $db->commit();

    jsonSuccess([
        'slot_id'    => $slotId,
        'expires_in' => 300, // 5 minutes in seconds
    ], 'Slot reserved for 5 minutes.');

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('[MediQueue] Slot reservation error: ' . $e->getMessage());
    jsonError('An error occurred while reserving the slot.', 500);
}
