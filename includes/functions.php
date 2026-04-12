<?php
/**
 * MediQueue — Utility & Validation Functions
 */

require_once __DIR__ . '/config.php';

/* ──────────────────────────────────────────────────────────
   JSON Response Helpers
   ────────────────────────────────────────────────────────── */

function jsonSuccess(array $data = [], string $message = 'Success', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ──────────────────────────────────────────────────────────
   Input Sanitisation
   ────────────────────────────────────────────────────────── */

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Parse the JSON request body (cached). All POST APIs use JSON bodies.
 */
function getJsonInput(): array {
    static $input = null;
    if ($input === null) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    }
    return $input;
}

function getPostString(string $key, string $default = ''): string {
    $input = getJsonInput();
    return isset($input[$key]) ? sanitize((string) $input[$key]) : $default;
}

function getGetString(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
}

function getPostInt(string $key, int $default = 0): int {
    $input = getJsonInput();
    return isset($input[$key]) ? (int) $input[$key] : $default;
}

function getGetInt(string $key, int $default = 0): int {
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}

/* ──────────────────────────────────────────────────────────
   Validation
   ────────────────────────────────────────────────────────── */

function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Alias per Section 7.4
function validateEmail(string $email): bool {
    return isValidEmail($email);
}

function isValidDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Alias per Section 7.4
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function isValidTime(string $time): bool {
    $t = DateTime::createFromFormat('H:i', $time);
    if (!$t) {
        $t = DateTime::createFromFormat('H:i:s', $time);
    }
    return $t !== false;
}

function isFutureDate(string $date): bool {
    return isValidDate($date) && $date >= date('Y-m-d');
}

/**
 * Check that all required fields are present and non-empty.
 * Returns array of missing field names (empty = all ok).
 */
function validateRequired(array $fields, array $data): array {
    $missing = [];
    foreach ($fields as $f) {
        if (!isset($data[$f]) || (is_string($data[$f]) && trim($data[$f]) === '')) {
            $missing[] = $f;
        }
    }
    return $missing;
}

/**
 * Password must be at least 8 characters.
 */
function validatePasswordStrength(string $password): bool {
    return strlen($password) >= 8;
}

/**
 * Validate that a value is an integer >= $min.
 */
function validateInt($value, int $min = 0): bool {
    if (!is_numeric($value)) return false;
    return (int) $value >= $min;
}

/* ──────────────────────────────────────────────────────────
   Audit Logging
   ────────────────────────────────────────────────────────── */

function logAudit(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?string $details = null
): bool {
    try {
        $db = getDB();
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt = $db->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, CONVERT_TZ(NOW(), \'+00:00\', \'+08:00\'))'
        );
        return $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Create an in-app system notification and mark it as sent.
 * Returns the created notification ID, or null on failure.
 */
function createSystemNotification(
    int $userId,
    string $subject,
    string $message,
    ?int $appointmentId = null
): ?int {
    if ($userId <= 0 || trim($subject) === '' || trim($message) === '') {
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO notifications (user_id, appointment_id, type, subject, message, status, sent_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $userId,
            $appointmentId ?: null,
            'system',
            $subject,
            $message,
            'sent',
        ]);

        return (int) $db->lastInsertId();
    } catch (\Throwable $e) {
        error_log('[MediQueue] Notification insert failed: ' . $e->getMessage());
        return null;
    }
}

/* ──────────────────────────────────────────────────────────
   Database Lookup Helpers
   ────────────────────────────────────────────────────────── */

function getUserById(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getAppointmentById(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM appointments WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getSlotById(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM time_slots WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Fetch doctor profile joined with user info.
 */
function getDoctorProfile(int $userId): ?array {
    $stmt = getDB()->prepare(
        'SELECT u.id, u.full_name, u.email, u.phone, u.profile_photo,
                dp.specialization, dp.bio, dp.years_experience,
                dp.clinic_address, dp.consultation_fee,
                dp.available_days, dp.consultation_duration
         FROM users u
         JOIN doctor_profiles dp ON dp.user_id = u.id
         WHERE u.id = ? AND u.role = "doctor" AND u.is_active = 1
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

/* ──────────────────────────────────────────────────────────
   Formatting
   ────────────────────────────────────────────────────────── */

function formatDate(string $date): string {
    return date('F j, Y', strtotime($date));
}

function formatTime(string $time): string {
    return date('g:i A', strtotime($time));
}

function formatDateTime(string $datetime): string {
    return date('M j, Y g:i A', strtotime($datetime));
}

/* ──────────────────────────────────────────────────────────
   Pagination Helper
   ────────────────────────────────────────────────────────── */

function getPagination(int $page, int $limit): array {
    $page  = max(1, $page);
    $limit = max(1, min(100, $limit));
    $offset = ($page - 1) * $limit;
    return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
}

/**
 * Build the standard pagination response object.
 */
function paginationResponse(int $currentPage, int $limit, int $total): array {
    $totalPages = (int) ceil($total / $limit);
    return [
        'total_pages'  => $totalPages,
        'current_page' => $currentPage,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

/* ──────────────────────────────────────────────────────────
   Profile Photo URL Helper
   ────────────────────────────────────────────────────────── */

/**
 * Get the full URL to a user's profile photo.
 * Handles both legacy full-path values and filename-only values.
 */
function profilePhotoUrl(?string $photo): string {
    if (!$photo || $photo === 'default.svg') {
        return BASE_URL . '/assets/uploads/photos/default.svg';
    }
    // If the value already contains the path prefix, use just the filename
    if (strpos($photo, '/') !== false) {
        $photo = basename($photo);
    }
    return BASE_URL . '/assets/uploads/photos/' . $photo;
}

/* ──────────────────────────────────────────────────────────
   File Upload — Profile Photo (Section 7.5)
   ────────────────────────────────────────────────────────── */

/**
 * Upload a profile photo. Returns ['success'=>bool, 'filename'=>string|null, 'error'=>string|null].
 * Max 2 MB, images only, saved with timestamped filename.
 */
function uploadPhoto(array $file): array {
    $maxSize   = 2 * 1024 * 1024; // 2 MB
    $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $uploadDir = PHOTOS_PATH . '/';

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Upload failed (code ' . $file['error'] . ').'];
    }
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'filename' => null, 'error' => 'File exceeds 2 MB limit.'];
    }

    // Verify actual MIME type (not just the extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Only JPEG, PNG, GIF, and WebP images are allowed.'];
    }

    // Verify it really is an image
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        return ['success' => false, 'filename' => null, 'error' => 'File is not a valid image.'];
    }

    $ext      = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save file.'];
    }

    return ['success' => true, 'filename' => $filename, 'error' => null];
}
