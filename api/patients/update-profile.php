<?php
/**
 * MediQueue — Update Patient Profile
 * POST api/patients/update-profile.php
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['patient']);

$userId = (int) $_SESSION['user_id'];
$db = getDB();

$dob     = getPostString('date_of_birth');
$gender  = getPostString('gender');
$address = getPostString('address');
$bloodType = getPostString('blood_type');
$allergies = getPostString('allergies');
$medicalHistory = getPostString('medical_history');
$emergName  = getPostString('emergency_contact_name');
$emergPhone = getPostString('emergency_contact_phone');

// Validate gender
$validGenders = ['male', 'female', 'prefer_not_to_say', ''];
if (!in_array($gender, $validGenders, true)) {
    jsonError('Invalid gender value.');
}

// Upsert patient profile
$existing = $db->prepare('SELECT id FROM patient_profiles WHERE user_id = ? LIMIT 1');
$existing->execute([$userId]);

if ($existing->fetch()) {
    $db->prepare(
        'UPDATE patient_profiles SET
            date_of_birth = ?, gender = ?, address = ?, blood_type = ?,
            allergies = ?, medical_history = ?,
            emergency_contact_name = ?, emergency_contact_phone = ?
         WHERE user_id = ?'
    )->execute([
        ($dob && isValidDate($dob)) ? $dob : null,
        $gender ?: null,
        $address ?: null,
        $bloodType ?: null,
        $allergies ?: null,
        $medicalHistory ?: null,
        $emergName ?: null,
        $emergPhone ?: null,
        $userId
    ]);
} else {
    $db->prepare(
        'INSERT INTO patient_profiles (user_id, date_of_birth, gender, address, blood_type, allergies, medical_history, emergency_contact_name, emergency_contact_phone)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId,
        ($dob && isValidDate($dob)) ? $dob : null,
        $gender ?: null,
        $address ?: null,
        $bloodType ?: null,
        $allergies ?: null,
        $medicalHistory ?: null,
        $emergName ?: null,
        $emergPhone ?: null
    ]);
}

logAudit('profile_updated', 'patient_profile', $userId, 'Patient profile updated');

jsonSuccess([], 'Profile updated successfully.');
