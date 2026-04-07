<?php
/**
 * MediQueue — Get Patient Profile
 * GET api/patients/profile.php?user_id=...
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$userId = getGetInt('user_id') ?: (int) $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Patients can only view their own profile; doctors/admin can view any
if ($role === 'patient' && $userId !== (int) $_SESSION['user_id']) {
    jsonError('You can only view your own profile.', 403);
}

$db = getDB();
$stmt = $db->prepare(
    'SELECT u.id, u.full_name, u.email, u.phone, u.profile_photo, u.is_verified, u.created_at,
            pp.date_of_birth, pp.gender, pp.address, pp.blood_type,
            pp.allergies, pp.medical_history,
            pp.emergency_contact_name, pp.emergency_contact_phone
     FROM users u
     LEFT JOIN patient_profiles pp ON pp.user_id = u.id
     WHERE u.id = ? AND u.role = "patient"
     LIMIT 1'
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    jsonError('Patient not found.', 404);
}

jsonSuccess(['profile' => $profile]);
