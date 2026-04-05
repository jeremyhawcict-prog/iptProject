<?php
/**
 * MediQueue — Unread Notification Count
 * GET api/notifications/count.php
 * Returns count of unread notifications for the logged-in user.
 */

require_once __DIR__ . '/../../includes/api_guard.php';

guardApi('GET', true, false);

$userId = (int) $_SESSION['user_id'];

$stmt = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$userId]);
$count = (int) $stmt->fetchColumn();

jsonSuccess(['count' => $count]);
