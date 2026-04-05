<?php
/**
 * MediQueue — Toggle User Active Status (admin)
 * POST api/users/toggle-status.php
 *
 * Body: { id }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['admin']);

$id = getPostInt('user_id') ?: getPostInt('id');
if (!$id) {
    jsonError('User id is required.');
}

$user = getUserById($id);
if (!$user) {
    jsonError('User not found.', 404);
}

// Prevent admin from deactivating themselves
if ((int) $user['id'] === (int) $_SESSION['user_id']) {
    jsonError('You cannot deactivate your own account.');
}

$input = getJsonInput();
$requestedStatus = isset($input['is_active']) ? (int) $input['is_active'] : null;
$newStatus = ($requestedStatus !== null) ? ($requestedStatus ? 1 : 0) : ($user['is_active'] ? 0 : 1);
getDB()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$newStatus, $id]);

$label = $newStatus ? 'activated' : 'deactivated';

logAudit('user_' . $label, 'user', $id, 'User ' . $user['email'] . ' ' . $label);

jsonSuccess(['is_active' => $newStatus], "User $label successfully.");