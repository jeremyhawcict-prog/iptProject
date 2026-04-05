<?php
/**
 * MediQueue — File-Based IP Rate Limiter (Section 7.2)
 *
 * Usage:
 *   require_once __DIR__ . '/rate_limiter.php';
 *   checkRateLimit('login', 10, 900);   // 10 requests per 15 min
 *
 * Stores attempt counts in /tmp/mediqueue_rates/ as flat files.
 * Each file: "<ip>_<key>.json" → { "attempts": N, "window_start": timestamp }
 */

require_once __DIR__ . '/config.php';

define('RATE_LIMIT_DIR', defined('ROOT_PATH') ? ROOT_PATH . '/storage/rates' : __DIR__ . '/../storage/rates');

/**
 * Check rate limit for the current IP on a named action.
 *
 * @param string $key      Unique action key (e.g. 'login', 'register')
 * @param int    $maxHits  Maximum allowed requests in the window
 * @param int    $windowSec Window size in seconds (default 900 = 15 min)
 */
function checkRateLimit(string $key, int $maxHits, int $windowSec = 900): void {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $hash = md5($ip . '_' . $key);
    $file = RATE_LIMIT_DIR . '/' . $hash . '.json';

    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0755, true);
    }

    $now  = time();
    $data = ['attempts' => 0, 'window_start' => $now];

    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $parsed = $raw ? json_decode($raw, true) : null;
        if ($parsed && isset($parsed['attempts'], $parsed['window_start'])) {
            $data = $parsed;
        }
    }

    // Reset window if expired
    if (($now - $data['window_start']) >= $windowSec) {
        $data = ['attempts' => 0, 'window_start' => $now];
    }

    // Check limit BEFORE incrementing
    if ($data['attempts'] >= $maxHits) {
        $retryAfter = $data['window_start'] + $windowSec - $now;
        header('Retry-After: ' . max(1, $retryAfter));
        jsonError('Too many requests. Please try again in ' . ceil($retryAfter / 60) . ' minute(s).', 429);
    }

    // Increment and save
    $data['attempts']++;
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Reset rate limit for the current IP on a named action.
 * Call after a successful login to clear the counter.
 */
function resetRateLimit(string $key): void {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $hash = md5($ip . '_' . $key);
    $file = RATE_LIMIT_DIR . '/' . $hash . '.json';
    if (file_exists($file)) {
        @unlink($file);
    }
}
