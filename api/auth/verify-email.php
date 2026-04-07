<?php
/**
 * MediQueue — Verify Email
 * GET api/auth/verify-email.php?token=...&email=...
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$token = getGetString('token');
$email = getGetString('email');

if (!$token || !$email) {
    header('Location: ' . BASE_URL . '/pages/login.php?verify=invalid');
    exit;
}

$db = getDB();
$stmt = $db->prepare(
    'SELECT id, is_verified FROM users WHERE email = ? AND email_verification_token = ? LIMIT 1'
);
$stmt->execute([$email, $token]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL . '/pages/login.php?verify=invalid');
    exit;
}

if ($user['is_verified']) {
    header('Location: ' . BASE_URL . '/pages/login.php?verify=already');
    exit;
}

$db->prepare(
    'UPDATE users SET is_verified = 1, email_verification_token = NULL WHERE id = ?'
)->execute([$user['id']]);

logAudit('email_verified', 'user', (int) $user['id'], 'Email verified: ' . $email);

header('Location: ' . BASE_URL . '/pages/login.php?verify=success');
exit;
