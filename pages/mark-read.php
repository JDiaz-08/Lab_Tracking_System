<?php
/**
 * FILE: pages/mark-read.php
 * Dedicated handler for marking notifications as read.
 * Avoids "headers already sent" by processing before any output.
 */
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . $base . 'pages/login.php');
    exit;
}

$db = getDB();
markAllRead($db);

/* Redirect back to the referring page, or dashboard as fallback */
$redirect = $_GET['redirect'] ?? 'dashboard.php';

/* Safety: only allow relative paths within the app */
$redirect = ltrim(basename($redirect), '/');
$allowed  = ['dashboard.php', 'history.php', 'reserve.php', 'edit-profile.php'];
if (!in_array($redirect, $allowed, true)) {
    $redirect = 'dashboard.php';
}

header('Location: ' . $base . 'pages/' . $redirect);
exit;