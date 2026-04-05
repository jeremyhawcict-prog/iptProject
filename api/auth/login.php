<?php
/**
 * MediQueue — Login
 * POST api/auth/login.php
 *
 * Body: { email, password }
 * Returns: user object + redirect URL by role.
 */

require_once __DIR__ . '/../../includes/api_guard.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';

guardApi('POST', false, false);

checkRateLimit('login', 10, 900);

$email    = getPostString('email');
$password = getJsonInput()['password'] ?? '';

if (!$email || !$password) {
    jsonError('Email and password are required.');
}

if (!isValidEmail($email)) {
    jsonError('Invalid email address.');
}

$stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Invalid email or password.', 401);
}

if (!$user['is_active']) {
    jsonError('Your account has been deactivated. Please contact support.', 403);
}

loginUser($user);
resetRateLimit('login');

logAudit('login', 'user', (int)$user['id'], 'User logged in: ' . $user['email']);

jsonSuccess([
    'user' => [
        'id'        => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ],
    'redirect' => getRedirectByRole(),
], 'Login successful.');