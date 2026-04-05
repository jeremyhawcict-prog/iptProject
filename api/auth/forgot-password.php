<?php
/**
 * MediQueue — Forgot Password (request reset link)
 * POST api/auth/forgot-password.php
 *
 * Body: { email }
 */

require_once __DIR__ . '/../../includes/api_guard.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/email_templates.php';

guardApi('POST', false, false);

checkRateLimit('forgot_password', 5, 900);

$email = getPostString('email');

if (!$email || !isValidEmail($email)) {
    jsonError('Please provide a valid email address.');
}

// Always respond success to prevent email enumeration
$stmt = getDB()->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Invalidate previous tokens
    getDB()->prepare('UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0')->execute([$email]);

    // Insert new token
    getDB()->prepare(
        'INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)'
    )->execute([$email, $token, $expiresAt]);

    try {
        $html = emailPasswordReset($email, $token);
        sendMail($email, 'Password Reset — MediQueue', $html);
    } catch (\Throwable $e) {
        error_log('[MediQueue] Reset email error: ' . $e->getMessage());
    }
}

jsonSuccess([], 'If an account with that email exists, a reset link has been sent.');