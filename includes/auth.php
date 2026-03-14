<?php
/**
 * auth.php — Session guards & helpers
 * Include this before any protected page.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Require a logged-in student. Redirect to login if not authenticated.
 */
function requireUser(): array {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . (isset($base) ? $base : '../') . 'pages/login.php');
        exit;
    }
    return $_SESSION['user'];
}

/**
 * Require a logged-in admin. Redirect to login if not authenticated.
 */
function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . (isset($base) ? $base : '../../') . 'pages/login.php');
        exit;
    }
}

/**
 * Returns unread notification count for the current user.
 */
function getUnreadCount(PDO $db): int {
    if (empty($_SESSION['user_id'])) return 0;
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returns latest notifications (read + unread) for dropdown.
 */
function getRecentNotifications(PDO $db, int $limit = 8): array {
    if (empty($_SESSION['user_id'])) return [];
    $stmt = $db->prepare(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([$_SESSION['user_id'], $limit]);
    return $stmt->fetchAll();
}

/**
 * Mark all notifications as read for current user.
 */
function markAllRead(PDO $db): void {
    if (empty($_SESSION['user_id'])) return;
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
       ->execute([$_SESSION['user_id']]);
}