<?php
/**
 * MediQueue — Create User (admin)
 * POST api/users/create.php
 *
 * Body: { full_name, email, password, role, phone? }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['admin']);

$required = validateRequired(['full_name', 'email', 'password', 'role'], getJsonInput());
if (!empty($required)) {
    jsonError("Missing required fields: " . implode(', ', $required));
}

$fullName = getPostString('full_name');
$email    = getPostString('email');
$password = getPostString('password');
$role     = getPostString('role');
$phone    = getPostString('phone');

if (!isValidEmail($email)) {
    jsonError('Invalid email address.');
}

$allowedRoles = ['admin', 'doctor', 'patient', 'staff'];
if (!in_array($role, $allowedRoles, true)) {
    jsonError('Role must be one of: ' . implode(', ', $allowedRoles));
}

if (!validatePasswordStrength($password)) {
    jsonError('Password must be at least 8 characters.');
}

// Check duplicate email
$stmt = getDB()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonError('A user with this email already exists.');
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

$stmt = getDB()->prepare(
    'INSERT INTO users (full_name, email, password_hash, role, phone, is_active)
     VALUES (?, ?, ?, ?, ?, 1)'
);
$stmt->execute([$fullName, $email, $hash, $role, $phone]);

$id = (int) getDB()->lastInsertId();

logAudit('user_created', 'user', $id, "Created $role: $email");

// If role is doctor, create an empty doctor_profiles row
if ($role === 'doctor') {
    getDB()->prepare(
        'INSERT INTO doctor_profiles (user_id, specialization) VALUES (?, ?)'
    )->execute([$id, 'General Practice']);
}

jsonSuccess(['user_id' => $id], 'User created successfully.', 201);