<?php
/**
 * MediQueue — Update Own Profile
 * POST api/users/update-profile.php
 *
 * Body: { full_name?, phone? }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true);

$userId = (int) $_SESSION['user_id'];
$user   = getUserById($userId);
if (!$user) {
    jsonError('User not found.', 404);
}

$fullName = getPostString('full_name') ?: $user['full_name'];
$phone    = getPostString('phone')     ?: $user['phone'];

$stmt = getDB()->prepare(
    'UPDATE users SET full_name = ?, phone = ? WHERE id = ?'
);
$stmt->execute([$fullName, $phone, $userId]);

jsonSuccess([], 'Profile updated successfully.');