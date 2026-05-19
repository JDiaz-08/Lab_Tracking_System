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
    <button class="lp-dm-toggle" id="lpDmToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
      <i class="bi bi-moon-fill" id="lpDmIcon"></i>
    </button>
    <button class="hamburger" id="navHamburger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="<?= $base ?>index.php">Home</a>
    <a href="<?= $base ?>pages/about.php">About</a>
    <a href="<?= $base ?>pages/login.php" class="btn-login">Login</a>
  </div>
</nav>
<script>
(function(){
  var dmBtn  = document.getElementById('lpDmToggle');
  var dmIcon = document.getElementById('lpDmIcon');
  function applyDark(on) {
    document.documentElement.classList.toggle('dark', on);
    if (dmIcon) dmIcon.className = on ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    try { localStorage.setItem('ucDarkMode', on ? '1' : '0'); } catch(e) {}
  }
  applyDark(document.documentElement.classList.contains('dark'));
  dmBtn && dmBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    applyDark(!document.documentElement.classList.contains('dark'));
  });
  /* Hamburger */
  var ham = document.getElementById('navHamburger');
  var mob = document.getElementById('mobileMenu');
  ham && ham.addEventListener('click', function(e) {
    e.stopPropagation();
    mob && mob.classList.toggle('open');
    ham.classList.toggle('open');
  });
  document.addEventListener('click', function(e) {
    if (mob && mob.classList.contains('open') && !mob.contains(e.target) && e.target !== ham) {
      mob.classList.remove('open');
      ham && ham.classList.remove('open');
    }
  });
})();
</script>