<?php
/**
 * MediQueue — API Guard
 * Include at the top of every API endpoint.
 * Validates HTTP method, CSRF token, and authentication.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

/**
 * Validate an API request.
 *
 * @param string $method   Expected HTTP method ('POST', 'GET', etc.)
 * @param bool   $auth     Require authenticated session (default true)
 * @param bool   $csrf     Require valid CSRF token for POST/PUT/DELETE (default true)
 * @param array  $roles    If non-empty, restrict to these roles
 */
function guardApi(string $method = 'POST', bool $auth = true, bool $csrf = true, array $roles = []): void {

    // Security headers for all API responses
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');

    // 1. Method check
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        jsonError('Method not allowed.', 405);
    }

    // 2. Authentication check
    if ($auth && !isLoggedIn()) {
        jsonError('Authentication required.', 401);
    }

    // 3. Role check
    if ($auth && $roles && !in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        jsonError('You do not have permission to perform this action.', 403);
    }

    // 4. CSRF check (only for state-changing methods)
    if ($csrf && in_array(strtoupper($method), ['POST', 'PUT', 'DELETE'], true)) {
        $input = getJsonInput();
        $token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCsrfToken($token)) {
            jsonError('Invalid or missing CSRF token.', 403);
        }
    }
}
