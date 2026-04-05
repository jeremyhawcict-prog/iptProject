<?php
/**
 * MediQueue — Reset Password (via token)
 * POST api/auth/reset-password.php
 *
 * Body: { email, token, password }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', false, false);

$email    = getPostString('email');
$token    = getPostString('token');
$password = getPostString('password');

if (!$email || !$token || !$password) {
    jsonError('Email, token and new password are required.');
}

if (!validatePasswordStrength($password)) {
    jsonError('Password must be at least 8 characters.');
}

$stmt = getDB()->prepare(
    'SELECT id FROM password_resets WHERE email = ? AND token = ? AND used = 0 AND expires_at > NOW() LIMIT 1'
);
$stmt->execute([$email, $token]);
$reset = $stmt->fetch();

if (!$reset) {
    jsonError('Invalid or expired reset link. Please request a new one.', 400);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
getDB()->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([$hash, $email]);
getDB()->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);

jsonSuccess([], 'Password has been reset successfully. You may now log in.');