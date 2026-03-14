<?php
/**
 * user-navbar.php — Post-login navigation bar
 * Requires: $base, $db (PDO), $_SESSION['user']
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$_unread      = isset($db) ? getUnreadCount($db) : 0;
$_notifs      = isset($db) ? getRecentNotifications($db) : [];
$_currentUser = $_SESSION['user'] ?? [];
$_initials    = strtoupper(
    substr($_currentUser['first_name'] ?? 'U', 0, 1) .
    substr($_currentUser['last_name']  ?? '',  0, 1)
);

// Mark as read when notification panel is opened (via AJAX or page load)
if (isset($_GET['mark_read']) && isset($db)) {
    markAllRead($db);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Determine active page for nav highlight
$_currentFile = basename($_SERVER['PHP_SELF']);
function _navActive(string $file): string {
    global $_currentFile;
    return $_currentFile === $file ? 'active' : '';
}
?>
<nav class="user-navbar">
  <div class="user-nav-container">

    <!-- Brand -->
    <a href="<?= $base ?>pages/dashboard.php" class="user-nav-brand">
      <div class="user-nav-badge">UC</div>
      <div class="logo-text">
        <span class="org-name">UC CompLab</span>
        <span class="org-sub">Management System</span>
      </div>
    </a>

    <!-- Desktop Links -->
    <ul class="user-nav-links">

      <!-- Notification Bell -->
      <li class="notif-wrapper">
        <button class="notif-btn" id="notifToggle" aria-label="Notifications">
          🔔
          <?php if ($_unread > 0): ?>
            <span class="notif-badge"><?= $_unread > 9 ? '9+' : $_unread ?></span>
          <?php endif; ?>
        </button>

        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-header">
            <span>Notifications</span>
            <?php if ($_unread > 0): ?>
              <a href="?mark_read=1" class="notif-mark-read">Mark all read</a>
            <?php endif; ?>
          </div>

          <div class="notif-list">
            <?php if (empty($_notifs)): ?>
              <div class="notif-empty">No notifications yet.</div>
            <?php else: ?>
              <?php foreach ($_notifs as $n): ?>
                <div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>">
                  <span class="notif-dot"></span>
                  <div class="notif-body">
                    <p><?= htmlspecialchars($n['message']) ?></p>
                    <small><?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></small>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </li>

      <li><a href="<?= $base ?>pages/dashboard.php"    class="<?= _navActive('dashboard.php') ?>">Home</a></li>
      <li><a href="<?= $base ?>pages/edit-profile.php" class="<?= _navActive('edit-profile.php') ?>">Edit Profile</a></li>
      <li><a href="<?= $base ?>pages/history.php"      class="<?= _navActive('history.php') ?>">History</a></li>
      <li><a href="<?= $base ?>pages/reserve.php"      class="<?= _navActive('reserve.php') ?>">Reserve</a></li>
      <li>
        <a href="<?= $base ?>pages/logout.php" class="user-nav-logout">
          Logout
        </a>
      </li>
    </ul>

    <!-- Avatar (desktop) -->
    <div class="user-avatar-wrap">
      <div class="user-avatar"><?= $_initials ?></div>
      <span class="user-name-short">
        <?= htmlspecialchars($_currentUser['first_name'] ?? '') ?>
      </span>
    </div>

    <!-- Hamburger -->
    <button class="hamburger user-hamburger" id="userHamburger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

  </div>

  <!-- Mobile Menu -->
  <div class="user-mobile-menu" id="userMobileMenu">
    <div class="mobile-user-info">
      <div class="user-avatar"><?= $_initials ?></div>
      <div>
        <strong><?= htmlspecialchars(($_currentUser['first_name'] ?? '') . ' ' . ($_currentUser['last_name'] ?? '')) ?></strong>
        <small><?= htmlspecialchars($_currentUser['student_id'] ?? '') ?></small>
      </div>
    </div>
    <a href="<?= $base ?>pages/dashboard.php">🏠 Home</a>
    <a href="<?= $base ?>pages/edit-profile.php">✏️ Edit Profile</a>
    <a href="<?= $base ?>pages/history.php">📋 History</a>
    <a href="<?= $base ?>pages/reserve.php">📅 Reserve</a>
    <a href="<?= $base ?>pages/logout.php" class="mobile-logout">🚪 Logout</a>
  </div>
</nav>