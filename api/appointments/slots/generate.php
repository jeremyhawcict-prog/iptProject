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

$doctorId       = getPostInt('doctor_id');
$fromDate       = getPostString('from_date');
$toDate         = getPostString('to_date');
$startHour      = getPostInt('start_hour', 9);
$endHour        = getPostInt('end_hour', 17);
$slotDuration   = getPostInt('slot_duration', 30);
$breakStartHour = getPostInt('break_start_hour', 12);
$breakEndHour   = getPostInt('break_end_hour', 13);

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
if ($startHour >= $endHour || $startHour < 0 || $endHour > 24) {
    jsonError('Invalid start_hour / end_hour range.', 400);
}

// Verify doctor exists
$doctor = getUserById($doctorId);
if (!$doctor || $doctor['role'] !== 'doctor') {
    jsonError('Doctor not found.', 404);
}

$db = getDB();

// ── Load doctor's configured available days ───────────────
$dpStmt = $db->prepare('SELECT available_days FROM doctor_profiles WHERE user_id = ?');
$dpStmt->execute([$doctorId]);
$dpRow = $dpStmt->fetch();

$doctorAvailDays = [];
if ($dpRow && !empty($dpRow['available_days'])) {
    $parsed = json_decode($dpRow['available_days'], true);
    if (is_array($parsed) && count($parsed) > 0) {
        // Normalise to ucfirst (e.g. "monday" → "Monday")
        $doctorAvailDays = array_map(fn($d) => ucfirst(strtolower(trim($d))), $parsed);
    }
}

// ── Accept optional caller-supplied days ──────────────────
$input      = getJsonInput();
$daysRaw    = $input['days'] ?? [];
if (is_string($daysRaw) && $daysRaw !== '') {
    $daysRaw = array_map('trim', explode(',', $daysRaw));
}
$requestedDays = is_array($daysRaw)
    ? array_map(fn($d) => ucfirst(strtolower(trim($d))), $daysRaw)
    : [];

// Effective allowed days:
//  - If caller supplied days AND doctor has a profile: intersect (can only narrow, never expand)
//  - If caller supplied days but no profile: use caller's list
//  - If caller supplied nothing: use doctor's profile days
//  - If neither is set: allow all days (legacy / no profile yet)
if (!empty($requestedDays) && !empty($doctorAvailDays)) {
    $allowedDays = array_values(array_intersect($requestedDays, $doctorAvailDays));
} elseif (!empty($requestedDays)) {
    $allowedDays = $requestedDays;
} elseif (!empty($doctorAvailDays)) {
    $allowedDays = $doctorAvailDays;
} else {
    $allowedDays = []; // empty = no filter applied
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
    $dayName = $current->format('l'); // e.g. "Monday", "Tuesday"

    // Skip if this weekday is not in the doctor's allowed days
    if (!empty($allowedDays) && !in_array($dayName, $allowedDays, true)) {
        $current->modify('+1 day');
        continue;
    }

    // Generate slots for this day
    $hour   = $startHour;
    $minute = 0;

    while (true) {
        $slotStart = sprintf('%02d:%02d:00', $hour, $minute);

        // Advance by slot_duration
        $totalMin = $hour * 60 + $minute + $slotDuration;
        $eHour    = intdiv($totalMin, 60);
        $eMin     = $totalMin % 60;

        if ($eHour > $endHour || ($eHour === $endHour && $eMin > 0)) {
            break; // past end_hour
        }

        $slotEnd = sprintf('%02d:%02d:00', $eHour, $eMin);

        // Skip if slot falls within break window
        if ($hour >= $breakStartHour && $hour < $breakEndHour) {
            $hour   = $eHour;
            $minute = $eMin;
            continue;
        }

        $insertStmt->execute([$doctorId, $dateStr, $slotStart, $slotEnd]);
        if ($insertStmt->rowCount() > 0) {
            $created++;
        } else {
            $skipped++;
        }

        $hour   = $eHour;
        $minute = $eMin;
    }

    $current->modify('+1 day');
}

jsonSuccess([
    'doctor_id' => $doctorId,
    'from_date' => $fromDate,
    'to_date'   => $toDate,
    'created'   => $created,
    'skipped'   => $skipped,
], "Generated $created new slots ($skipped duplicates skipped).");
