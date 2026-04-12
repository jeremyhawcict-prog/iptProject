<?php
/**
 * MediQueue — Generate Time Slots for a Doctor
 * POST api/appointments/slots/generate.php
 *
 * Required: doctor_id, from_date, to_date
 * Optional: start_hour (default 9), end_hour (default 17),
 *           slot_duration (15/30/60, default 30),
 *           break_start_hour (default 12), break_end_hour (default 13)
 *
 * Access: staff, admin only
 */

require_once __DIR__ . '/../../../includes/api_guard.php';

guardApi('POST', true, true, ['doctor', 'staff', 'admin']);

$input          = getJsonInput();
$doctorId       = getPostInt('doctor_id');
$fromDate       = getPostString('from_date') ?: getPostString('start_date');
$toDate         = getPostString('to_date') ?: getPostString('end_date');
$startHour      = getPostInt('start_hour', -1);
$endHour        = getPostInt('end_hour', -1);
$startTime      = getPostString('start_time');
$endTime        = getPostString('end_time');
$slotDuration   = getPostInt('slot_duration', getPostInt('duration', 30));
$breakStartHour = getPostInt('break_start_hour', 12);
$breakEndHour   = getPostInt('break_end_hour', 13);

/**
 * Normalize weekday input to lowercase full names.
 * Accepts full names and common 3-letter abbreviations.
 */
$normalizeWeekdays = static function ($value): array {
    $map = [
        'monday' => 'monday', 'mon' => 'monday',
        'tuesday' => 'tuesday', 'tue' => 'tuesday', 'tues' => 'tuesday',
        'wednesday' => 'wednesday', 'wed' => 'wednesday',
        'thursday' => 'thursday', 'thu' => 'thursday', 'thurs' => 'thursday',
        'friday' => 'friday', 'fri' => 'friday',
        'saturday' => 'saturday', 'sat' => 'saturday',
        'sunday' => 'sunday', 'sun' => 'sunday',
    ];

    $items = [];
    if (is_array($value)) {
        $items = $value;
    } elseif (is_string($value) && trim($value) !== '') {
        $items = explode(',', $value);
    }

    $result = [];
    foreach ($items as $item) {
        $key = strtolower(trim((string) $item));
        if ($key !== '' && isset($map[$key])) {
            $result[] = $map[$key];
        }
    }

    return array_values(array_unique($result));
};

$parseTimeToMinutes = static function (string $time): int {
    $parts = explode(':', $time);
    $h = isset($parts[0]) ? (int) $parts[0] : 0;
    $m = isset($parts[1]) ? (int) $parts[1] : 0;
    return ($h * 60) + $m;
};

if ($startTime && isValidTime($startTime)) {
    $startMinutes = $parseTimeToMinutes($startTime);
} else {
    $startMinutes = (($startHour >= 0 ? $startHour : 9) * 60);
}

if ($endTime && isValidTime($endTime)) {
    $endMinutes = $parseTimeToMinutes($endTime);
} else {
    $endMinutes = (($endHour >= 0 ? $endHour : 17) * 60);
}

// ── Validation ───────────────────────────────────────────
if (!$doctorId) {
    jsonError('doctor_id is required.', 400);
}
if (!isValidDate($fromDate) || !isValidDate($toDate)) {
    jsonError('Valid from_date and to_date (YYYY-MM-DD) are required.', 400);
}
if ($fromDate > $toDate) {
    jsonError('from_date must be on or before to_date.', 400);
}
if (!in_array($slotDuration, [15, 30, 60], true)) {
    jsonError('slot_duration must be 15, 30, or 60 minutes.', 400);
}
if ($startMinutes >= $endMinutes || $startMinutes < 0 || $endMinutes > (24 * 60)) {
    jsonError('Invalid start/end time range.', 400);
}

// Verify doctor exists
$doctor = getUserById($doctorId);
if (!$doctor || $doctor['role'] !== 'doctor') {
    jsonError('Doctor not found.', 404);
}

$db = getDB();

$selectedDays = $normalizeWeekdays($input['days'] ?? '');
if (empty($selectedDays)) {
    $dp = $db->prepare('SELECT available_days FROM doctor_profiles WHERE user_id = ? LIMIT 1');
    $dp->execute([$doctorId]);
    $dpRow = $dp->fetch();
    if ($dpRow && !empty($dpRow['available_days'])) {
        $raw = json_decode((string) $dpRow['available_days'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $raw = (string) $dpRow['available_days'];
        }
        $selectedDays = $normalizeWeekdays($raw);
    }
}

if (!empty($selectedDays)) {
    $activeStatuses = "'pending','confirmed','in_progress'";
    $dayPlaceholders = implode(',', array_fill(0, count($selectedDays), '?'));

    // Disable future unbooked slots that do not match selected weekdays.
    $disableSql =
        "UPDATE time_slots ts
         LEFT JOIN appointments a ON a.slot_id = ts.id AND a.status IN ($activeStatuses)
         SET ts.is_available = 0
         WHERE ts.doctor_id = ?
           AND ts.slot_date >= ?
           AND ts.slot_date <= ?
           AND a.id IS NULL
           AND LOWER(DAYNAME(ts.slot_date)) NOT IN ($dayPlaceholders)";
    $disableParams = array_merge([$doctorId, $fromDate, $toDate], $selectedDays);
    $db->prepare($disableSql)->execute($disableParams);

    // Enable future unbooked slots that match selected weekdays.
    $enableSql =
        "UPDATE time_slots ts
         LEFT JOIN appointments a ON a.slot_id = ts.id AND a.status IN ($activeStatuses)
         SET ts.is_available = 1
         WHERE ts.doctor_id = ?
           AND ts.slot_date >= ?
           AND ts.slot_date <= ?
           AND a.id IS NULL
           AND LOWER(DAYNAME(ts.slot_date)) IN ($dayPlaceholders)";
    $enableParams = array_merge([$doctorId, $fromDate, $toDate], $selectedDays);
    $db->prepare($enableSql)->execute($enableParams);
}

$insertStmt = $db->prepare(
    'INSERT IGNORE INTO time_slots (doctor_id, slot_date, start_time, end_time)
     VALUES (?, ?, ?, ?)'
);

$created = 0;
$skipped = 0;
$current = new DateTime($fromDate);
$end     = new DateTime($toDate);
$end->modify('+1 day'); // inclusive end

while ($current < $end) {
    $dateStr = $current->format('Y-m-d');

    if (!empty($selectedDays)) {
        $weekday = strtolower($current->format('l'));
        if (!in_array($weekday, $selectedDays, true)) {
            $current->modify('+1 day');
            continue;
        }
    }

    // Generate slots for this day
    $slotStartMinutes = $startMinutes;

    while (true) {
        $slotEndMinutes = $slotStartMinutes + $slotDuration;

        if ($slotEndMinutes > $endMinutes) {
            break; // past end_hour
        }

        $slotStart = sprintf('%02d:%02d:00', intdiv($slotStartMinutes, 60), $slotStartMinutes % 60);
        $slotEnd   = sprintf('%02d:%02d:00', intdiv($slotEndMinutes, 60), $slotEndMinutes % 60);

        // Skip if slot falls within break window
        $breakStartMinutes = $breakStartHour * 60;
        $breakEndMinutes   = $breakEndHour * 60;
        if ($slotStartMinutes >= $breakStartMinutes && $slotStartMinutes < $breakEndMinutes) {
            $slotStartMinutes = $slotEndMinutes;
            continue;
        }

        $insertStmt->execute([$doctorId, $dateStr, $slotStart, $slotEnd]);
        if ($insertStmt->rowCount() > 0) {
            $created++;
        } else {
            $skipped++;
        }

        $slotStartMinutes = $slotEndMinutes;
    }

    $current->modify('+1 day');
}

jsonSuccess([
    'doctor_id' => $doctorId,
    'from_date' => $fromDate,
    'to_date'   => $toDate,
    'days'      => $selectedDays,
    'created'   => $created,
    'skipped'   => $skipped,
], "Generated $created new slots ($skipped duplicates skipped).");
