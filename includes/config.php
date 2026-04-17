<?php
/**
 * MediQueue — Central Configuration
 * Loaded by every page and API endpoint.
 */

// ── Start session (once) ──────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Timezone ──────────────────────────────────────────────
// Philippine Standard Time (UTC+8)
date_default_timezone_set('Asia/Manila');

// ── Environment flag ──────────────────────────────────────
// Set to false in production on ByetHost
define('MQ_DEBUG', true);

if (MQ_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ── Database credentials ──────────────────────────────────
// ByetHost typically uses "sql_server" — verify in your control panel.
define('DB_HOST', 'sql200.byethost31.com');   // ← change to your ByetHost DB host
define('DB_NAME', 'b31_41481208_MQDB');    // ← change to your ByetHost DB name
define('DB_USER', 'b31_41481208');         // ← change to your ByetHost DB user
define('DB_PASS', 'project123');             // ← change to your ByetHost DB password
define('DB_CHARSET', 'utf8mb4');

// ── Base URL (no trailing slash) ──────────────────────────
// Adjust to match your ByetHost domain/path.
define('BASE_URL', '/Mediqueue');

// ── File paths ────────────────────────────────────────────
define('ROOT_PATH',    __DIR__ . '/..');
define('INCLUDES_PATH', __DIR__);
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads');
define('PHOTOS_PATH',  UPLOADS_PATH . '/photos');

// ── App constants ─────────────────────────────────────────
define('APP_NAME',    'MediQueue');
define('APP_VERSION', '2.0');
define('SLOT_DURATION_MIN', 30);

// ── Mail credentials (Gmail SMTP) ─────────────────────────
// Use a real Gmail address and a Gmail App Password (NOT your normal Gmail password).
// To generate an App Password: Google Account → Security → 2-Step Verification → App Passwords.
define('MAIL_USER', 'charlesandreiv033@gmail.com');   // ← replace with actual Gmail address
define('MAIL_PASS', 'nmso qloj pkvh ijlp');          // ← replace with 16-char Gmail App Password

// ── PDO connection (singleton) ────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname='    . DB_NAME
             . ';charset='   . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->exec("SET time_zone = '+08:00'"); // Philippine Standard Time (UTC+8)
    }
    return $pdo;
}

// Global $pdo alias — Section 6.6 convention
$pdo = getDB();

// ── CSRF Token helpers (Section 7.1) ──────────────────────
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Legacy aliases
function generateCsrfToken(): string { return getCsrfToken(); }
function verifyCsrfToken(string $token): bool { return validateCsrfToken($token); }
