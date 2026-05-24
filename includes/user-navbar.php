<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/* Ensure $db is available for notifications even on pages that don't init it */
if (!isset($db) && !empty($_SESSION['user_id'])) {
    if (!function_exists('getDB')) {
        require_once __DIR__ . '/../config/database.php';
    }
    if (!function_exists('getUnreadCount')) {
        require_once __DIR__ . '/../includes/auth.php';
    }
    $db = getDB();
}

$_unread      = isset($db) ? getUnreadCount($db) : 0;
$_notifs      = isset($db) ? getRecentNotifications($db) : [];
$_currentUser = $_SESSION['user'] ?? [];
$_initials    = strtoupper(
    substr($_currentUser['first_name'] ?? 'U', 0, 1) .
    substr($_currentUser['last_name']  ?? '',  0, 1)
);

/*
 * Profile picture: prefer the page-scoped $user variable (freshest DB read),
 * fall back to the session copy. This fixes the issue where an uploaded photo
 * was not reflected because the session copy was stale.
 */
$_profilePic = null;
if (isset($user) && !empty($user['profile_picture'])) {
    $_profilePic = $user['profile_picture'];
} elseif (!empty($_currentUser['profile_picture'])) {
    $_profilePic = $_currentUser['profile_picture'];
}

/*
 * mark_read is NOT processed here to avoid "headers already sent".
 * The "Mark all read" link points to pages/mark-read.php instead.
 */
$_currentFile = basename($_SERVER['PHP_SELF']);
function _navActive(string $file): string {
    global $_currentFile;
    return $_currentFile === $file ? 'active' : '';
}

$_markReadUrl = (isset($base) ? $base : '../')
    . 'pages/mark-read.php?redirect='
    . urlencode(basename($_SERVER['PHP_SELF']));
?>
<style>
.user-nav-logo-img {
  height: 30px; width: auto; object-fit: contain;
  filter: brightness(0) invert(1); opacity: 0.88; flex-shrink: 0;
}
.user-brand-text { display: flex; flex-direction: column; line-height: 1.25; }
.user-brand-name { font-size: 0.80rem; font-weight: 700; color: #fff; letter-spacing: 0.15px; white-space: nowrap; }
.user-brand-sub  { font-size: 0.60rem; color: rgba(189,232,245,0.58); letter-spacing: 0.3px; }

/* Photo avatar in navbar */
.user-avatar-photo {
  width: 34px; height: 34px; border-radius: 50%;
  object-fit: cover; display: block; flex-shrink: 0;
  border: 2px solid rgba(189,232,245,0.35);
}
</style>

<nav class="user-navbar">
  <div class="user-nav-container">

    <a href="<?= $base ?>pages/dashboard.php" class="user-nav-brand">
      <img src="<?= $base ?>assets/images/uc-logo-white.png" alt="UC" class="user-nav-logo-img" />
      <div class="user-brand-text">
        <span class="user-brand-name">UC CompLab</span>
        <span class="user-brand-sub">SitIn Management System</span>
      </div>
    </a>

    <ul class="user-nav-links">

      <!-- Notification Bell -->
      <li class="notif-wrapper">
        <button class="notif-btn" id="notifToggle" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <?php if ($_unread > 0): ?>
            <span class="notif-badge"><?= $_unread > 9 ? '9+' : $_unread ?></span>
          <?php endif; ?>
        </button>

        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-header">
            <span>Notifications</span>
            <?php if ($_unread > 0): ?>
              <a href="<?= htmlspecialchars($_markReadUrl) ?>" class="notif-mark-read">Mark all read</a>
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

      <li><a href="<?= $base ?>pages/dashboard.php" class="<?= _navActive('dashboard.php') ?>"><i class="bi bi-house"></i> Home</a></li>
      <li><a href="<?= $base ?>pages/edit-profile.php" class="<?= _navActive('edit-profile.php') ?>"><i class="bi bi-person"></i> Profile</a></li>
      <li><a href="<?= $base ?>pages/history.php" class="<?= _navActive('history.php') ?>"><i class="bi bi-clock-history"></i> History</a></li>
      <li><a href="<?= $base ?>pages/reserve.php" class="<?= _navActive('reserve.php') ?>"><i class="bi bi-calendar-check"></i> Reserve</a></li>
      <li><a href="<?= $base ?>pages/testimonials.php" class="<?= _navActive('testimonials.php') ?>"><i class="bi bi-chat-heart"></i> Testimonials</a></li>
      <li><a href="<?= $base ?>pages/software.php" class="<?= _navActive('software.php') ?>"><i class="bi bi-cpu"></i> Software</a></li>
      <li><a href="<?= $base ?>pages/logout.php" class="user-nav-logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
    </ul>

    <!-- Avatar: photo or initials -->
    <div class="user-avatar-wrap">
      <?php if ($_profilePic): ?>
        <img src="<?= htmlspecialchars($_profilePic) ?>"
             alt="<?= htmlspecialchars($_currentUser['first_name'] ?? '') ?>"
             class="user-avatar-photo" />
      <?php else: ?>
        <div class="user-avatar"><?= $_initials ?></div>
      <?php endif; ?>
      <span class="user-name-short"><?= htmlspecialchars($_currentUser['first_name'] ?? '') ?></span>
    </div>

    <div class="nav-right" style="display: flex; align-items: center; gap: 15px; margin-left: auto;">
      <button class="dm-toggle" id="userDmToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
        <i class="bi bi-moon-fill" id="userDmIcon"></i>
      </button>
      <button class="hamburger user-hamburger" id="userHamburger" aria-label="Toggle menu" style="margin-left: 0;">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>

  <div class="user-mobile-menu" id="userMobileMenu">
    <div class="mobile-user-info">
      <?php if ($_profilePic): ?>
        <img src="<?= htmlspecialchars($_profilePic) ?>"
             alt="<?= htmlspecialchars($_currentUser['first_name'] ?? '') ?>"
             class="user-avatar-photo" />
      <?php else: ?>
        <div class="user-avatar"><?= $_initials ?></div>
      <?php endif; ?>
      <div>
        <strong><?= htmlspecialchars(($_currentUser['first_name'] ?? '') . ' ' . ($_currentUser['last_name'] ?? '')) ?></strong>
        <small><?= htmlspecialchars($_currentUser['student_id'] ?? '') ?></small>
      </div>
    </div>
    <a href="<?= $base ?>pages/dashboard.php"><i class="bi bi-house"></i> Home</a>
    <a href="<?= $base ?>pages/edit-profile.php"><i class="bi bi-person"></i> Edit Profile</a>
    <a href="<?= $base ?>pages/history.php"><i class="bi bi-clock-history"></i> History</a>
    <a href="<?= $base ?>pages/reserve.php"><i class="bi bi-calendar-check"></i> Reserve</a>
    <a href="<?= $base ?>pages/testimonials.php"><i class="bi bi-chat-heart"></i> Testimonials</a>
    <a href="<?= $base ?>pages/software.php"><i class="bi bi-cpu"></i> Software</a>
    <a href="<?= $base ?>pages/logout.php" class="mobile-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</nav>
<script>
(function () {
  /* ── Dark Mode ── */
  const dmBtn  = document.getElementById('userDmToggle');
  const dmIcon = document.getElementById('userDmIcon');
  function applyDark(on) {
    document.documentElement.classList.toggle('dark', on);
    if (dmIcon) dmIcon.className = on ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    try { localStorage.setItem('ucDarkMode', on ? '1' : '0'); } catch(e) {}
  }
  applyDark(document.documentElement.classList.contains('dark'));
  dmBtn?.addEventListener('click', function(e) {
    e.stopPropagation();
    applyDark(!document.documentElement.classList.contains('dark'));
  });

  /* ── Hamburger ── */
  const ham = document.getElementById('userHamburger');
  const mob = document.getElementById('userMobileMenu');
  ham?.addEventListener('click', function(e) {
    e.stopPropagation();
    ham.classList.toggle('open');
    if (mob.style.display === 'flex') {
      mob.style.display = 'none';
    } else {
      mob.style.display = 'flex';
    }
  });

  /* ── Notification Dropdown ── */
  const notifBtn = document.getElementById('notifToggle');
  const notifDd  = document.getElementById('notifDropdown');
  let notifOpen  = false;

  notifBtn?.addEventListener('click', function(e) {
    e.stopPropagation();
    notifOpen = !notifOpen;
    notifDd?.classList.toggle('open', notifOpen);
  });

  /* Close notification when clicking outside */
  document.addEventListener('click', function(e) {
    if (notifOpen && notifDd && !notifDd.contains(e.target) && e.target !== notifBtn) {
      notifOpen = false;
      notifDd.classList.remove('open');
    }
    /* Close mobile menu when clicking outside */
    if (mob?.style.display === 'flex' && !mob.contains(e.target) && e.target !== ham) {
      mob.style.display = 'none';
      ham?.classList.remove('open');
    }
  });

  /* Stop clicks inside dropdown from closing it */
  notifDd?.addEventListener('click', function(e) {
    e.stopPropagation();
  });
})();
</script>