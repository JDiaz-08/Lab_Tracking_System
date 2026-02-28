<?php
// Do NOT override $base if the calling page already set it.
// (navbar.php lives in includes/, so __DIR__ never contains '/pages',
//  making the old strpos check always return '' regardless of caller.)
if (!isset($base)) $base = '';
?>
<nav class="navbar">
  <div class="nav-container">

    <!-- Logo -->
    <a href="<?= $base ?>index.php" class="nav-logo">
      <img src="<?= $base ?>assets/images/uc-logo.png" alt="University of Cebu" class="nav-uc-logo" />
      <div class="logo-text">
        <span class="org-name">University of Cebu</span>
        <span class="org-sub">Computer Laboratory System</span>
      </div>
    </a>

    <!-- Desktop Links -->
    <ul class="nav-links">
      <li><a href="<?= $base ?>index.php">Home</a></li>
      <li><a href="<?= $base ?>pages/about.php">About</a></li>
      <li><a href="<?= $base ?>pages/login.php" class="btn-login">Login</a></li>
    </ul>

    <!-- Hamburger (mobile) -->
    <button class="hamburger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>

  </div>

  <!-- Mobile Menu -->
  <div class="mobile-menu">
    <a href="<?= $base ?>index.php">Home</a>
    <a href="<?= $base ?>pages/about.php">About</a>
    <a href="<?= $base ?>pages/login.php" class="btn-login">Login</a>
  </div>
</nav>