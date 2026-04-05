<?php
/**
 * MediQueue — Register
 * POST api/auth/register.php
 *
 * Body: { full_name, email, password, phone?, gender?, date_of_birth? }
 */

require_once __DIR__ . '/../../includes/api_guard.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/email_templates.php';

guardApi('POST', false, false);

checkRateLimit('register', 5, 900);

$input    = getJsonInput();
$name     = getPostString('full_name');
$email    = getPostString('email');
$password = $input['password'] ?? '';
$phone    = getPostString('phone');

$missing = validateRequired(['full_name', 'email', 'password'], $input);
if ($missing) {
    jsonError('Missing required fields: ' . implode(', ', $missing));
}

if (!isValidEmail($email)) {
    jsonError('Invalid email address.');
}

if (!validatePasswordStrength($password)) {
    jsonError('Password must be at least 8 characters.');
}

// Check duplicate email
$stmt = getDB()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('An account with this email already exists.', 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

$db = getDB();
$ins = $db->prepare(
    'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)'
);
$ins->execute([$name, $email, $hash, $phone ?: null, 'patient']);
$userId = (int) $db->lastInsertId();

$user = getUserById($userId);

// Send welcome email (non-blocking)
try {
    $html = emailWelcome($user);
    sendMail($email, 'Welcome to MediQueue!', $html);
} catch (\Throwable $e) {
    error_log('[MediQueue] Welcome email error: ' . $e->getMessage());
}

// Auto-login the new patient and redirect to dashboard
loginUser($user);

logAudit('register', 'user', $userId, 'New patient registered: ' . $email);

jsonSuccess([
    'user_id'  => $userId,
    'redirect' => getRedirectByRole(),
], 'Registration successful.', 201);