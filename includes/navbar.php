<?php
if (!isset($base)) $base = '';
?>
<nav class="navbar">
  <div class="nav-container">
    <a href="<?= $base ?>index.php" class="nav-logo">
      <img src="<?= $base ?>assets/images/uc-logo-white.png" alt="University of Cebu" class="nav-uc-logo" />
      <div class="logo-text">
        <span class="org-name">University of Cebu</span>
        <span class="org-sub">Computer Laboratory System</span>
      </div>
    </a>
    <ul class="nav-links">
      <li><a href="<?= $base ?>index.php">Home</a></li>
      <li><a href="<?= $base ?>pages/about.php">About</a></li>
      <li><a href="<?= $base ?>pages/login.php" class="btn-login">Login</a></li>
    </ul>
    <button class="hamburger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="mobile-menu">
    <a href="<?= $base ?>index.php">Home</a>
    <a href="<?= $base ?>pages/about.php">About</a>
    <a href="<?= $base ?>pages/login.php" class="btn-login">Login</a>
  </div>
</nav>