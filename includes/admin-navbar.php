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
      College of Computer Studies Admin
    </a>

    <ul class="admin-nav-links">
      <li><a href="<?= $base ?>pages/admin/index.php"        class="<?= _aNav('index.php') ?>">Home</a></li>
      <li><a href="#" id="adminSearchBtn"                                                       >Search</a></li>
      <li><a href="<?= $base ?>pages/admin/students.php"     class="<?= _aNav('students.php') ?>">Students</a></li>
      <li><a href="<?= $base ?>pages/admin/sitin.php"        class="<?= _aNav('sitin.php') ?>">Sit-in</a></li>
      <li><a href="<?= $base ?>pages/admin/view-sitin.php"   class="<?= _aNav('view-sitin.php') ?>">View Sit-in Records</a></li>
      <li><a href="<?= $base ?>pages/admin/sitin-reports.php" class="<?= _aNav('sitin-reports.php') ?>">Sit-in Reports</a></li>
      <li><a href="<?= $base ?>pages/admin/feedback.php"     class="<?= _aNav('feedback.php') ?>">Feedback Reports</a></li>
      <li><a href="<?= $base ?>pages/admin/reservation.php"  class="<?= _aNav('reservation.php') ?>">Reservation</a></li>
      <li><a href="<?= $base ?>pages/logout.php" class="admin-logout-btn">Log out</a></li>
    </ul>

    <button class="admin-hamburger" id="adminHamburger">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="admin-mobile-menu" id="adminMobileMenu">
    <a href="<?= $base ?>pages/admin/index.php">Home</a>
    <a href="#" id="adminSearchBtnMobile">Search</a>
    <a href="<?= $base ?>pages/admin/students.php">Students</a>
    <a href="<?= $base ?>pages/admin/sitin.php">Sit-in</a>
    <a href="<?= $base ?>pages/admin/view-sitin.php">View Sit-in Records</a>
    <a href="<?= $base ?>pages/admin/sitin-reports.php">Sit-in Reports</a>
    <a href="<?= $base ?>pages/admin/feedback.php">Feedback Reports</a>
    <a href="<?= $base ?>pages/admin/reservation.php">Reservation</a>
    <a href="<?= $base ?>pages/logout.php" class="admin-logout-btn">Log out</a>
  </div>
</nav>

<!-- Search Modal -->
<div class="a-modal-overlay" id="searchModalOverlay">
  <div class="a-modal">
    <div class="a-modal-header">
      <span>Search Student</span>
      <button class="a-modal-close" id="searchModalClose">×</button>
    </div>
    <div class="a-modal-body">
      <form method="GET" action="<?= $base ?>pages/admin/students.php">
        <input type="text" name="q" class="a-search-input" placeholder="Search..."
               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autofocus />
        <div style="text-align:right; margin-top:0.75rem;">
          <button type="submit" class="a-btn a-btn-primary">Search</button>
        </div>
      </form>
    </div>
  </div>
</div>