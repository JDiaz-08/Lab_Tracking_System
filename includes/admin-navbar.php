<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($base)) $base = '../../';

$_adminUser   = $_SESSION['admin_user'] ?? 'Admin';
$_currentFile = basename($_SERVER['PHP_SELF']);
function _aNav(string $file): string {
    global $_currentFile;
    return $_currentFile === $file ? 'a-active' : '';
}
?>
<nav class="admin-navbar">
  <div class="admin-nav-inner">
    <a href="<?= $base ?>pages/admin/index.php" class="admin-brand">
      UC CompLab &mdash; Admin
    </a>

    <ul class="admin-nav-links">
      <li><a href="<?= $base ?>pages/admin/index.php" class="<?= _aNav('index.php') ?>">
        <i class="bi bi-speedometer2"></i> Home
      </a></li>
      <li><a href="#" id="adminSearchBtn">
        <i class="bi bi-search"></i> Search
      </a></li>
      <li><a href="<?= $base ?>pages/admin/students.php" class="<?= _aNav('students.php') ?>">
        <i class="bi bi-people"></i> Students
      </a></li>
      <li><a href="<?= $base ?>pages/admin/sitin.php" class="<?= _aNav('sitin.php') ?>">
        <i class="bi bi-pc-display"></i> Sit-in
      </a></li>
      <li><a href="<?= $base ?>pages/admin/view-sitin.php" class="<?= _aNav('view-sitin.php') ?>">
        <i class="bi bi-table"></i> Records
      </a></li>
      <li><a href="<?= $base ?>pages/admin/sitin-reports.php" class="<?= _aNav('sitin-reports.php') ?>">
        <i class="bi bi-bar-chart-line"></i> Reports
      </a></li>
      <li><a href="<?= $base ?>pages/admin/feedback.php" class="<?= _aNav('feedback.php') ?>">
        <i class="bi bi-chat-square-text"></i> Feedback
      </a></li>
      <li><a href="<?= $base ?>pages/admin/reservation.php" class="<?= _aNav('reservation.php') ?>">
        <i class="bi bi-calendar-check"></i> Reservations
      </a></li>
      <li><a href="<?= $base ?>pages/logout.php" class="admin-logout-btn">
        <i class="bi bi-box-arrow-right"></i> Log out
      </a></li>
    </ul>

    <button class="admin-hamburger" id="adminHamburger">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="admin-mobile-menu" id="adminMobileMenu">
    <a href="<?= $base ?>pages/admin/index.php"><i class="bi bi-speedometer2"></i> Home</a>
    <a href="#" id="adminSearchBtnMobile"><i class="bi bi-search"></i> Search</a>
    <a href="<?= $base ?>pages/admin/students.php"><i class="bi bi-people"></i> Students</a>
    <a href="<?= $base ?>pages/admin/sitin.php"><i class="bi bi-pc-display"></i> Sit-in</a>
    <a href="<?= $base ?>pages/admin/view-sitin.php"><i class="bi bi-table"></i> Records</a>
    <a href="<?= $base ?>pages/admin/sitin-reports.php"><i class="bi bi-bar-chart-line"></i> Reports</a>
    <a href="<?= $base ?>pages/admin/feedback.php"><i class="bi bi-chat-square-text"></i> Feedback</a>
    <a href="<?= $base ?>pages/admin/reservation.php"><i class="bi bi-calendar-check"></i> Reservations</a>
    <a href="<?= $base ?>pages/logout.php" class="admin-logout-btn"><i class="bi bi-box-arrow-right"></i> Log out</a>
  </div>
</nav>

<!-- Search Modal -->
<div class="a-modal-overlay" id="searchModalOverlay">
  <div class="a-modal">
    <div class="a-modal-header">
      <span>Search Student</span>
      <button class="a-modal-close" id="searchModalClose">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <div class="a-modal-body">
      <form method="GET" action="<?= $base ?>pages/admin/students.php">
        <div style="position:relative;">
          <i class="bi bi-search" style="position:absolute;left:0.7rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.85rem;"></i>
          <input type="text" name="q" class="a-search-input" placeholder="Name or ID number..."
                 value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                 autofocus style="padding-left:2rem;" />
        </div>
        <div style="text-align:right; margin-top:0.75rem;">
          <button type="submit" class="a-btn a-btn-primary">
            <i class="bi bi-search"></i> Search
          </button>
        </div>
      </form>
    </div>
  </div>
</div>