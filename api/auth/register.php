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
$verifyToken = bin2hex(random_bytes(32));

$db = getDB();
$ins = $db->prepare(
    'INSERT INTO users (full_name, email, password_hash, phone, role, is_verified, email_verification_token) VALUES (?, ?, ?, ?, ?, 0, ?)'
);
$ins->execute([$name, $email, $hash, $phone ?: null, 'patient', $verifyToken]);
$userId = (int) $db->lastInsertId();

// Create patient profile row
$dob = getPostString('date_of_birth');
$gender = getPostString('gender');
$address = getPostString('address');
$db->prepare(
    'INSERT INTO patient_profiles (user_id, date_of_birth, gender, address) VALUES (?, ?, ?, ?)'
)->execute([
    $userId,
    ($dob && isValidDate($dob)) ? $dob : null,
    in_array($gender, ['male','female','prefer_not_to_say']) ? $gender : null,
    $address ?: null
]);

$user = getUserById($userId);

// Send verification email (non-blocking)
try {
    $html = emailVerification($user, $verifyToken);
    sendMail($email, 'Verify Your Email — MediQueue', $html);
} catch (\Throwable $e) {
    error_log('[MediQueue] Verification email error: ' . $e->getMessage());
}

// Auto-login the new patient and redirect to dashboard
loginUser($user);

logAudit('register', 'user', $userId, 'New patient registered: ' . $email);

jsonSuccess([
    'user_id'  => $userId,
    'redirect' => getRedirectByRole(),
], 'Registration successful. Please check your email to verify your account.', 201);