<?php
/**
 * MediQueue — Update System Settings
 * POST api/settings/update.php
 * Body: { settings: { key: value, ... } }
 * Access: admin only
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('POST', true, true, ['admin']);

$db    = getDB();
$input = getJsonInput();
$settings = $input['settings'] ?? [];

if (!is_array($settings) || empty($settings)) {
    jsonError('No settings provided.', 400);
}

// Ensure settings table exists
$db->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$allowedKeys = [
    'clinic_name', 'clinic_email', 'clinic_phone', 'clinic_address',
    'operating_hours_start', 'operating_hours_end',
    'lunch_break_start', 'lunch_break_end',
    'default_slot_duration', 'max_advance_booking_days', 'cancellation_window_hours',
];

$stmt = $db->prepare(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
);

$updated = 0;
foreach ($settings as $key => $value) {
    if (in_array($key, $allowedKeys, true)) {
        $stmt->execute([$key, (string) $value]);
        $updated++;
    }
}

logAudit('settings_updated', 'settings', null, "Updated $updated setting(s)");

jsonSuccess(['updated' => $updated], 'Settings saved successfully.');
