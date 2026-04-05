<?php
/**
 * MediQueue — List Specializations
 * GET api/doctors/specializations.php
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', false, false);

$stmt = getDB()->query(
    "SELECT DISTINCT specialization FROM doctor_profiles WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization ASC"
);
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

jsonSuccess(['specializations' => $rows]);