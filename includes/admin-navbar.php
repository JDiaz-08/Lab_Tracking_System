<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($base)) $base = '../../';

$_adminUser   = $_SESSION['admin_user'] ?? 'Admin';
$_currentFile = basename($_SERVER['PHP_SELF']);

/* Fetch students for search + sit-in (db is defined by each admin page before this include) */
$_navStudents = [];
if (isset($db)) {
    $_navStudents = $db->query(
        "SELECT id, student_id, first_name, last_name, middle_name, course, remaining_sessions
         FROM users ORDER BY student_id ASC"
    )->fetchAll();
}

$_sitPurposes = ['C# Programming','Java Programming','PHP Programming','C Programming','ASP.net Programming'];
$_sitLabs     = ['524','526','528','530','542','Mac Laboratory'];

function _aNav(string $file): string {
    global $_currentFile;
    return $_currentFile === $file ? 'a-active' : '';
}
?>
<style>
/* ── Navbar logo ── */
.admin-brand-wrap { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; margin-right: 1.25rem; }
.admin-brand-logo { height: 32px; width: auto; object-fit: contain; filter: brightness(0) invert(1); opacity: 0.90; }
.admin-brand-text { display: flex; flex-direction: column; line-height: 1.25; }
.admin-brand-name { font-size: 0.80rem; font-weight: 700; color: #fff; letter-spacing: 0.15px; white-space: nowrap; }
.admin-brand-sub  { font-size: 0.60rem; color: rgba(189,232,245,0.60); letter-spacing: 0.3px; }

/* ── Search sit-in modal ── */
.sns-results { max-height: 280px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 7px; margin-top: 0.55rem; display: none; background: #fff; }
.sns-results.show { display: block; }
.sns-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.9rem; border-bottom: 1px solid #f0f4f8; cursor: pointer; transition: background 0.13s; font-size: 0.83rem; }
.sns-item:last-child { border-bottom: none; }
.sns-item:hover { background: #f0f4f8; }
.sns-item-id   { font-weight: 700; color: #1e293b; min-width: 90px; flex-shrink: 0; font-size: 0.82rem; }
.sns-item-name { flex: 1; color: #475569; font-size: 0.82rem; }
.sns-item-sess { font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 100px; flex-shrink: 0; }
.sess-ok    { background: rgba(22,163,74,0.10);  color: #15803d; }
.sess-low   { background: rgba(217,119,6,0.10);  color: #92400e; }
.sess-empty { background: rgba(220,38,38,0.08);  color: #991b1b; }
.sns-empty  { padding: 1.1rem; text-align: center; color: #94a3b8; font-size: 0.83rem; }

/* Sit-in form modal */
.sitin-warn {
  display: none; align-items: center; gap: 7px;
  background: rgba(220,38,38,0.06); border: 1px solid rgba(220,38,38,0.20);
  color: #991b1b; border-radius: 6px; padding: 0.5rem 0.75rem;
  font-size: 0.79rem; font-weight: 600; margin-bottom: 0.75rem;
}
.sitin-warn i { font-size: 0.83rem; }
.sitin-stu-strip {
  display: flex; align-items: center; gap: 0.65rem;
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 0.6rem 0.875rem; margin-bottom: 0.875rem;
}
.sitin-stu-av {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, #1a3a6b, #2563EB);
  color: #fff; font-size: 0.68rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.sitin-stu-name { font-size: 0.84rem; font-weight: 700; color: #1e293b; margin-bottom: 1px; }
.sitin-stu-id   { font-size: 0.70rem; color: #94a3b8; }
.sitin-sess-row {
  display: flex; align-items: center; justify-content: space-between;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 7px;
  padding: 0.45rem 0.75rem; margin-bottom: 0.875rem; font-size: 0.80rem;
}
.sitin-sess-lbl { color: #64748b; display: flex; align-items: center; gap: 5px; }
.sitin-sess-lbl i { color: #2563EB; }
.sitin-sess-val { font-weight: 800; }
</style>

<nav class="admin-navbar">
  <div class="admin-nav-inner">
    <a href="<?= $base ?>pages/admin/index.php" class="admin-brand-wrap">
      <img src="<?= $base ?>assets/images/uc-logo-white.png" alt="UC" class="admin-brand-logo" />
      <div class="admin-brand-text">
        <span class="admin-brand-name">UC CompLab</span>
        <span class="admin-brand-sub">SitIn Admin Panel</span>
      </div>
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

<!-- ── MODAL 1: Search Student ── -->
<div class="a-modal-overlay" id="searchModalOverlay">
  <div class="a-modal a-modal-wide">
    <div class="a-modal-header">
      <span><i class="bi bi-search" style="margin-right:5px;color:#2563EB;font-size:0.85rem;"></i>Search Student</span>
      <button class="a-modal-close" id="searchModalClose">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <div class="a-modal-body">
      <div style="position:relative;">
        <i class="bi bi-search" style="position:absolute;left:0.7rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.84rem;pointer-events:none;"></i>
        <input type="text" id="navStuSearch" class="a-search-input"
               placeholder="Type name or ID number..." autofocus
               autocomplete="off" style="padding-left:2.1rem;" />
      </div>
      <div id="navStuResults" class="sns-results">
        <div class="sns-empty">Start typing to search students…</div>
      </div>
      <p style="font-size:0.72rem; color:#94a3b8; margin-top:0.55rem; text-align:center;">
        Click a student to start a sit-in session
      </p>
    </div>
  </div>
</div>

<!-- ── MODAL 2: Sit-in Form ── -->
<div class="a-modal-overlay" id="navSitInModal">
  <div class="a-modal">
    <div class="a-modal-header">
      <span><i class="bi bi-pc-display" style="margin-right:5px;color:#2563EB;font-size:0.85rem;"></i>Start Sit-in Session</span>
      <button class="a-modal-close" id="navSitInClose">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <form method="POST" action="<?= $base ?>pages/admin/sitin.php" id="navSitInForm">
      <input type="hidden" name="action"  value="sitin">
      <input type="hidden" name="user_id" id="nsUserId">
      <div class="a-modal-body">

        <!-- Student strip -->
        <div class="sitin-stu-strip">
          <div class="sitin-stu-av" id="nsAvatar">—</div>
          <div>
            <div class="sitin-stu-name" id="nsName">—</div>
            <div class="sitin-stu-id"   id="nsStudentId">—</div>
          </div>
        </div>

        <!-- Session counter -->
        <div class="sitin-sess-row">
          <span class="sitin-sess-lbl"><i class="bi bi-hourglass-split"></i> Sessions Remaining</span>
          <span class="sitin-sess-val" id="nsSessions">—</span>
        </div>

        <!-- Warning if 0 sessions -->
        <div class="sitin-warn" id="nsWarn">
          <i class="bi bi-exclamation-triangle-fill"></i>
          This student has no remaining sessions. Sit-in cannot be started.
        </div>

        <div class="a-mrow">
          <label class="a-mlabel">Purpose</label>
          <select name="purpose" id="nsPurpose" class="a-minput" required>
            <option value="" disabled selected>— Select Purpose —</option>
            <?php foreach ($_sitPurposes as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="a-mrow">
          <label class="a-mlabel">Laboratory</label>
          <select name="lab_room" id="nsLab" class="a-minput" required>
            <option value="" disabled selected>— Select Lab —</option>
            <?php foreach ($_sitLabs as $lab): ?>
              <option value="<?= htmlspecialchars($lab) ?>"><?= htmlspecialchars($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" id="nsBack">
          <i class="bi bi-arrow-left"></i> Back
        </button>
        <button type="submit" id="nsSubmit" class="a-btn a-btn-green">
          <i class="bi bi-box-arrow-in-right"></i> Start Session
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const ALL_STU = <?= json_encode(array_values($_navStudents), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;

  /* helpers */
  function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
  function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
  function esc(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Search modal open ── */
  ['adminSearchBtn','adminSearchBtnMobile'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', e => {
      e.preventDefault();
      openModal('searchModalOverlay');
      setTimeout(() => document.getElementById('navStuSearch')?.focus(), 80);
    });
  });

  document.getElementById('searchModalClose')
    ?.addEventListener('click', () => closeModal('searchModalOverlay'));
  document.getElementById('searchModalOverlay')
    ?.addEventListener('click', e => { if (e.target.id === 'searchModalOverlay') closeModal('searchModalOverlay'); });

  /* ── Live search ── */
  const searchInput = document.getElementById('navStuSearch');
  const resultsBox  = document.getElementById('navStuResults');

  searchInput?.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    resultsBox.innerHTML = '';

    if (!q) {
      resultsBox.innerHTML = '<div class="sns-empty">Start typing to search students…</div>';
      resultsBox.classList.add('show');
      return;
    }

    const hits = ALL_STU.filter(s => {
      const full = (s.first_name + ' ' + (s.middle_name || '') + ' ' + s.last_name).toLowerCase();
      return s.student_id.toLowerCase().includes(q) || full.includes(q);
    });

    if (!hits.length) {
      resultsBox.innerHTML = '<div class="sns-empty">No students found.</div>';
    } else {
      hits.slice(0, 30).forEach(s => {
        const rem   = parseInt(s.remaining_sessions) || 0;
        const cls   = rem > 10 ? 'sess-ok' : rem > 0 ? 'sess-low' : 'sess-empty';
        const mid   = s.middle_name ? ' ' + s.middle_name + ' ' : ' ';
        const full  = s.first_name + mid + s.last_name;
        const div   = document.createElement('div');
        div.className = 'sns-item';
        div.innerHTML =
          '<span class="sns-item-id">'   + esc(s.student_id) + '</span>' +
          '<span class="sns-item-name">' + esc(full)         + '</span>' +
          '<span class="sns-item-sess '  + cls + '">' + rem + ' sessions</span>';
        div.addEventListener('click', () => openSitInForm(s));
        resultsBox.appendChild(div);
      });
    }
    resultsBox.classList.add('show');
  });

  /* ── Open sit-in form ── */
  function openSitInForm(s) {
    closeModal('searchModalOverlay');
    searchInput.value = '';
    resultsBox.innerHTML = '<div class="sns-empty">Start typing to search students…</div>';
    resultsBox.classList.remove('show');

    const rem  = parseInt(s.remaining_sessions) || 0;
    const mid  = s.middle_name ? ' ' + s.middle_name + ' ' : ' ';
    const full = s.first_name + mid + s.last_name;
    const init = (s.first_name[0] || '').toUpperCase() + (s.last_name[0] || '').toUpperCase();

    document.getElementById('nsUserId').value    = s.id;
    document.getElementById('nsAvatar').textContent  = init;
    document.getElementById('nsName').textContent    = full;
    document.getElementById('nsStudentId').textContent = s.student_id + ' · ' + (s.course || '');
    document.getElementById('nsSessions').textContent = rem + ' / 30';
    document.getElementById('nsPurpose').selectedIndex = 0;
    document.getElementById('nsLab').selectedIndex     = 0;

    const warn   = document.getElementById('nsWarn');
    const submit = document.getElementById('nsSubmit');
    if (rem <= 0) {
      warn.style.display   = 'flex';
      submit.disabled      = true;
      submit.style.opacity = '0.45';
    } else {
      warn.style.display   = 'none';
      submit.disabled      = false;
      submit.style.opacity = '';
    }

    /* colour the session count */
    const sessEl = document.getElementById('nsSessions');
    sessEl.style.color = rem > 10 ? '#1e293b' : rem > 0 ? '#d97706' : '#dc2626';

    openModal('navSitInModal');
  }

  /* ── Back button ── */
  document.getElementById('nsBack')?.addEventListener('click', () => {
    closeModal('navSitInModal');
    openModal('searchModalOverlay');
    setTimeout(() => searchInput?.focus(), 80);
  });
  document.getElementById('navSitInClose')?.addEventListener('click', () => closeModal('navSitInModal'));
  document.getElementById('navSitInModal')?.addEventListener('click', e => {
    if (e.target.id === 'navSitInModal') closeModal('navSitInModal');
  });
})();
</script>