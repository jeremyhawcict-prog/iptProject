<?php
/**
 * MediQueue — Update Profile Photo
 * POST api/users/update-photo.php  (multipart/form-data)
 *
 * File field: photo
 */

require_once __DIR__ . '/../../includes/api_guard.php';

// multipart/form-data — guardApi handles method + auth + CSRF
guardApi('POST', true, true);

$userId = (int) $_SESSION['user_id'];

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonError('No photo uploaded or upload error.');
}

$result = uploadPhoto($_FILES['photo']);
if (isset($result['error'])) {
    jsonError($result['error']);
}

// Delete old photo if exists and is not the default
$user = getUserById($userId);
if ($user && $user['profile_photo'] && $user['profile_photo'] !== 'default.svg') {
    $oldPath = PHOTOS_PATH . '/' . $user['profile_photo'];
    if (file_exists($oldPath)) {
        @unlink($oldPath);
    }
}

// Store only the filename in DB (not the full path)
getDB()->prepare('UPDATE users SET profile_photo = ? WHERE id = ?')->execute([$result['filename'], $userId]);

// Refresh session data so header displays new photo immediately
$_SESSION['user_data']['profile_photo'] = $result['filename'];

jsonSuccess(['photo_url' => BASE_URL . '/assets/uploads/photos/' . $result['filename'], 'filename' => $result['filename']], 'Photo updated successfully.');