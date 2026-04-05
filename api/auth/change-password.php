<?php
/**
 * MediQueue — Change Password (authenticated)
 * POST api/auth/change-password.php
 *
 * Body: { current_password, new_password }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true);

$currentPassword = getPostString('current_password');
$newPassword     = getPostString('new_password');

if (!$currentPassword || !$newPassword) {
    jsonError('Current and new passwords are required.');
}

if (!validatePasswordStrength($newPassword)) {
    jsonError('New password must be at least 8 characters.');
}

$user = getUserById($_SESSION['user_id']);
if (!$user) {
    jsonError('User not found.', 404);
}

if (!password_verify($currentPassword, $user['password_hash'])) {
    jsonError('Current password is incorrect.', 403);
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);

jsonSuccess([], 'Password changed successfully.');