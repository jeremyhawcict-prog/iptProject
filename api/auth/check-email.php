<?php
/**
 * MediQueue — Check Email Availability
 * GET api/auth/check-email.php?email=...
 *
 * Returns { available: true/false }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', false, false);

$email = getGetString('email');

if (!$email || !isValidEmail($email)) {
    jsonError('Invalid email address.');
}

$stmt = getDB()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$exists = (bool) $stmt->fetch();

jsonSuccess(['available' => !$exists]);