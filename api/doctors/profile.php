<?php
/**
 * MediQueue — Doctor Profile
 * GET  api/doctors/profile.php?id=          — read (public)
 * POST api/doctors/profile.php              — update own profile (doctor auth)
 *
 * Body (POST): { specialization, bio, consultation_fee, years_experience?, clinic_address? }
 */

require_once __DIR__ . '/../../includes/api_guard.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    guardApi('GET', false, false);

    $id = getGetInt('id');
    if (!$id) {
        jsonError('Doctor id is required.');
    }

    $stmt = getDB()->prepare(
        "SELECT u.id, u.full_name, u.email, u.phone, u.profile_photo,
                dp.specialization, dp.bio, dp.years_experience, dp.clinic_address, dp.consultation_fee,
                dp.available_days,
                COALESCE(AVG(f.rating), 0) AS avg_rating,
                COUNT(DISTINCT f.id) AS review_count
         FROM users u
         LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
         LEFT JOIN appointments a  ON a.doctor_id = u.id AND a.status = 'completed'
         LEFT JOIN feedback f      ON f.appointment_id = a.id
         WHERE u.id = ? AND u.role = 'doctor'
         GROUP BY u.id"
    );
    $stmt->execute([$id]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        jsonError('Doctor not found.', 404);
    }

    $doctor['avg_rating']   = round((float) $doctor['avg_rating'], 1);
    $doctor['review_count'] = (int) $doctor['review_count'];

    // Parse available_days JSON into an array for easier frontend consumption
    if (!empty($doctor['available_days'])) {
        $parsed = json_decode($doctor['available_days'], true);
        $doctor['available_days'] = is_array($parsed) ? $parsed : [];
    } else {
        $doctor['available_days'] = [];
    }

    jsonSuccess(['doctor' => $doctor]);

} else {
    guardApi('POST', true, true, ['doctor']);

    $doctorId       = (int) $_SESSION['user_id'];
    $input          = getJsonInput();
    $specialization = getPostString('specialization');
    $bio            = getPostString('bio');
    $fee            = getPostString('consultation_fee');
    $experience     = getPostInt('years_experience');
    $clinicAddr     = getPostString('clinic_address');
    $fullName       = getPostString('full_name');
    $phone          = getPostString('phone');

    // Handle available_days - accept comma-separated or array, store as JSON
    $availDaysRaw = $input['available_days'] ?? '';
    if (is_array($availDaysRaw)) {
        $availDays = json_encode($availDaysRaw);
    } elseif (is_string($availDaysRaw) && $availDaysRaw !== '') {
        $days = array_map('trim', explode(',', $availDaysRaw));
        $availDays = json_encode($days);
    } else {
        $availDays = '[]';
    }

    // Update user table fields if provided
    if ($fullName || $phone !== '') {
        $db = getDB();
        $updates = [];
        $vals = [];
        if ($fullName) { $updates[] = 'full_name = ?'; $vals[] = $fullName; }
        if ($phone !== '') { $updates[] = 'phone = ?'; $vals[] = $phone; }
        if ($updates) {
            $vals[] = $doctorId;
            $db->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($vals);
        }
    }

    // Upsert doctor profile
    $existing = getDoctorProfile($doctorId);
    if ($existing) {
        $stmt = getDB()->prepare(
            'UPDATE doctor_profiles SET specialization = ?, bio = ?, consultation_fee = ?, years_experience = ?, clinic_address = ?, available_days = ? WHERE user_id = ?'
        );
        $stmt->execute([$specialization, $bio, $fee, $experience, $clinicAddr, $availDays, $doctorId]);
    } else {
        $stmt = getDB()->prepare(
            'INSERT INTO doctor_profiles (user_id, specialization, bio, consultation_fee, years_experience, clinic_address, available_days) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$doctorId, $specialization, $bio, $fee, $experience, $clinicAddr, $availDays]);
    }

    jsonSuccess([], 'Profile updated successfully.');
}
