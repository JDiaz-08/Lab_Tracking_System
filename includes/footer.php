<?php
// Do NOT override $base if the calling page already set it.
if (!isset($base)) $base = '';
?>
<footer class="footer">
  <div class="footer-container">
    <div class="footer-top">

      <div class="footer-logo">
        <img src="<?= $base ?>assets/images/uc-logo.png" alt="University of Cebu" class="nav-uc-logo footer-uc-logo" />
        <div class="logo-text">
          <span class="org-name">University of Cebu</span>
          <span class="org-sub">Computer Laboratory System</span>
        </div>
      </div>

      <div class="footer-links">
        <a href="<?= $base ?>index.php">Home</a>
        <a href="<?= $base ?>pages/about.php">About</a>
        <a href="<?= $base ?>pages/login.php">Login</a>
      </div>

    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> University of Cebu — College of Computer Studies. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- Fix: filename is Main.js (capital M) — must match exactly on Linux servers -->
<script src="<?= $base ?>assets/js/Main.js"></script>
</body>
</html>