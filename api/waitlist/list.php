<?php
/**
 * MediQueue — My Waitlist Entries
 * GET api/waitlist/list.php
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['patient']);

$userId = (int) $_SESSION['user_id'];

$db = getDB();
$stmt = $db->prepare(
    "SELECT w.id, w.preferred_date, w.status, w.created_at, w.notified_at,
            u.full_name AS doctor_name, dp.specialization
     FROM waitlist w
     JOIN users u ON u.id = w.doctor_id
     LEFT JOIN doctor_profiles dp ON dp.user_id = w.doctor_id
     WHERE w.patient_id = ?
     ORDER BY w.created_at DESC"
);
$stmt->execute([$userId]);
$entries = $stmt->fetchAll();

jsonSuccess(['waitlist' => $entries]);
