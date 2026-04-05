<?php
/**
 * MediQueue — Get System Settings
 * GET api/settings/get.php
 * Access: staff, admin
 *
 * Creates settings table if not exists, returns defaults for missing keys.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false, ['staff', 'admin']);

$db = getDB();

// Ensure settings table exists
$db->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Defaults
$defaults = [
    'clinic_name'              => 'MediQueue Clinic',
    'clinic_email'             => 'info@mediqueue.com',
    'clinic_phone'             => '(02) 8123-4567',
    'clinic_address'           => 'MediQueue Clinic, Quezon City, Philippines',
    'operating_hours_start'    => '09:00',
    'operating_hours_end'      => '17:00',
    'lunch_break_start'        => '12:00',
    'lunch_break_end'          => '13:00',
    'default_slot_duration'    => '30',
    'max_advance_booking_days' => '30',
    'cancellation_window_hours'=> '24',
];

$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
$rows = $stmt->fetchAll();
$stored = [];
foreach ($rows as $r) {
    $stored[$r['setting_key']] = $r['setting_value'];
}

$settings = array_merge($defaults, $stored);

jsonSuccess(['settings' => $settings]);
