<?php
/**
 * MediQueue — Update User (admin)
 * POST api/users/update.php
 *
 * Body: { id, full_name?, email?, role?, phone? }
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

$fullName = getPostString('full_name') ?: $user['full_name'];
$email    = getPostString('email')     ?: $user['email'];
$role     = getPostString('role')      ?: $user['role'];
$phone    = getPostString('phone')     ?: $user['phone'];

if ($email !== $user['email']) {
    if (!isValidEmail($email)) {
        jsonError('Invalid email address.');
    }
    $dup = getDB()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $dup->execute([$email, $id]);
    if ($dup->fetch()) {
        jsonError('A user with this email already exists.');
    }
}

$allowedRoles = ['admin', 'doctor', 'patient', 'staff'];
if (!in_array($role, $allowedRoles, true)) {
    jsonError('Role must be one of: ' . implode(', ', $allowedRoles));
}

$stmt = getDB()->prepare(
    'UPDATE users SET full_name = ?, email = ?, role = ?, phone = ? WHERE id = ?'
);
$stmt->execute([$fullName, $email, $role, $phone, $id]);

jsonSuccess([], 'User updated successfully.');