<?php
/**
 * MediQueue — Mark Notifications as Read
 * POST api/notifications/mark-read.php
 * Body: { all: true } or { notification_id: <int> }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true);

$userId = (int) $_SESSION['user_id'];
$input  = getJsonInput();
$all    = !empty($input['all']);
$id     = getPostInt('notification_id');

$db = getDB();

if ($all) {
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    $count = $stmt->rowCount();
    jsonSuccess(['updated' => $count], "$count notification(s) marked as read.");
} elseif ($id) {
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    if ($stmt->rowCount() === 0) {
        jsonError('Notification not found or already read.', 404);
    }
    jsonSuccess([], 'Notification marked as read.');
} else {
    jsonError('Provide notification_id or set all=true.', 400);
}
