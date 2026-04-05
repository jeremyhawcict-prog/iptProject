<?php
/**
 * MediQueue — Logout
 * GET api/auth/logout.php
 *
 * Destroys session and redirects to login.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

logoutUser();

// Return JSON for API calls
if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
}