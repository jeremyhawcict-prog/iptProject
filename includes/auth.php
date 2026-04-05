<?php
/**
 * MediQueue — Authentication & Role Helpers
 *
 * Requires: includes/config.php (session + DB)
 *
 * Session keys set on login:
 *   $_SESSION['user_id']    — int
 *   $_SESSION['user_role']  — string (patient|doctor|staff|admin)
 *   $_SESSION['user_name']  — string
 *   $_SESSION['user_email'] — string
 *   $_SESSION['user_data']  — full user row (cached, refreshed each request)
 */

require_once __DIR__ . '/config.php';

// ── Dashboard paths by role ───────────────────────────────
define('ROLE_DASHBOARDS', [
    'patient' => BASE_URL . '/pages/patient/dashboard.php',
    'doctor'  => BASE_URL . '/pages/doctor/dashboard.php',
    'staff'   => BASE_URL . '/pages/admin/dashboard.php',
    'admin'   => BASE_URL . '/pages/admin/dashboard.php',
]);

// ── Page access matrix ────────────────────────────────────
// Maps path prefixes to the roles allowed to view them.
define('PAGE_ACCESS', [
    '/pages/patient/' => ['patient'],
    '/pages/doctor/'  => ['doctor'],
    '/pages/admin/'   => ['staff', 'admin'],
]);

/* ──────────────────────────────────────────────────────────
   Core auth functions
   ────────────────────────────────────────────────────────── */

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Redirect to login page if user is not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

/**
 * Ensure the logged-in user has one of the specified roles.
 * Redirects to their own dashboard if not authorized.
 *
 * @param array $roles Allowed roles, e.g. ['admin', 'staff']
 */
function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        header('Location: ' . getRedirectByRole());
        exit;
    }
}

/**
 * Return true if the current user has a specific role.
 */
function hasRole(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Get the dashboard URL for the current user's role.
 */
function getRedirectByRole(): string {
    $role = $_SESSION['user_role'] ?? 'patient';
    return ROLE_DASHBOARDS[$role] ?? ROLE_DASHBOARDS['patient'];
}

/**
 * Fetch the full user row from the DB (cached in session for the request).
 * Returns null if not logged in or user not found.
 *
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    // Refresh from DB once per request (keyed by request-level static)
    static $fetched = false;
    if (!$fetched || empty($_SESSION['user_data'])) {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT id, full_name, email, phone, role, profile_photo, is_active, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            // Account deleted or deactivated — force logout
            logoutUser();
            return null;
        }

        $_SESSION['user_data']  = $user;
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $fetched = true;
    }

    return $_SESSION['user_data'];
}

/**
 * Log in a user — set all session variables.
 *
 * @param array $user Row from the users table.
 */
function loginUser(array $user): void {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_data']  = $user;
}

/**
 * Log out — destroy session and redirect to login.
 */
function logoutUser(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

/* ──────────────────────────────────────────────────────────
   Page-level guard (call from every page)
   ────────────────────────────────────────────────────────── */

/**
 * Enforce the PAGE_ACCESS matrix based on the current script path.
 * Call this at the top of any page that needs protection.
 */
function enforcePageAccess(): void {
    requireLogin();
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

    foreach (PAGE_ACCESS as $prefix => $allowedRoles) {
        if (strpos($script, $prefix) !== false) {
            if (!in_array($_SESSION['user_role'], $allowedRoles, true)) {
                header('Location: ' . getRedirectByRole());
                exit;
            }
            return; // access granted
        }
    }
    // Pages not in the matrix (index.php, login.php, etc.) are open to any logged-in user.
}
