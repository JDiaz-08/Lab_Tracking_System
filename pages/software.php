<?php
// FILE: pages/software.php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireUser();
$db = getDB();

$labRooms = ['524','526','528','530','542','Mac Laboratory'];

/* Fetch all software grouped by lab */
$allSoftware = [];
foreach ($labRooms as $lab) {
    $stmt = $db->prepare("SELECT * FROM lab_software WHERE lab_room = ? ORDER BY software_name ASC");
    $stmt->execute([$lab]);
    $allSoftware[$lab] = $stmt->fetchAll();
}

$pageTitle = 'Software Availability';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css?v=' . filemtime(__DIR__ . '/../assets/css/user.css') . '">';
?>
<style>
.sw-page-wrap { max-width: 920px; margin: 0 auto; }
.sw-page-title {
  font-family: var(--font-display);
  font-size: 1.55rem; font-weight: 800; color: var(--navy);
  text-align: center; margin-bottom: 1.5rem;
}

/* Lab tabs */
.sw-tabs {
  display: flex; gap: 6px; flex-wrap: wrap;
  margin-bottom: 1.25rem; justify-content: center;
}
.sw-tab {
  padding: 0.45rem 1rem; border-radius: 8px;
  background: #f1f5f9; color: #475569;
  font-size: 0.80rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s;
  border: 1.5px solid transparent;
}
.sw-tab:hover { background: #e2e8f0; }
.sw-tab.active {
  background: var(--navy); color: #fff;
  border-color: var(--navy);
}

/* Software card */
.sw-card {
  background: #fff; border: 1px solid #e2e8f0;
  border-radius: 14px; box-shadow: 0 1px 4px rgba(15,40,84,0.06);
  overflow: hidden;
}
.sw-card-head {
  display: flex; align-items: center; gap: 9px;
  padding: 0.95rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.sw-card-ico {
  width: 28px; height: 28px; border-radius: 7px;
  background: rgba(15,40,84,0.05);
  display: flex; align-items: center; justify-content: center;
  color: var(--navy); font-size: 0.82rem; flex-shrink: 0;
}
.sw-card-title { font-family: var(--font-display); font-size: 0.92rem; font-weight: 800; color: var(--navy); }
.sw-card-body { padding: 1.1rem 1.25rem; }

/* Software grid */
.sw-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem;
}
.sw-item {
  display: flex; align-items: center; gap: 0.65rem;
  padding: 0.7rem 0.875rem; background: #f8fafc;
  border: 1px solid #e2e8f0; border-radius: 9px;
  transition: all 0.15s;
}
.sw-item:hover { background: #eef2ff; border-color: rgba(37,99,235,0.20); }
.sw-item-icon {
  width: 32px; height: 32px; border-radius: 7px;
  background: rgba(37,99,235,0.08); color: #2563EB;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.88rem; flex-shrink: 0;
}
.sw-item-name { font-size: 0.82rem; font-weight: 700; color: var(--navy); margin-bottom: 1px; }
.sw-item-ver  { font-size: 0.68rem; color: #94a3b8; }

/* Empty */
.sw-empty {
  text-align: center; padding: 2rem; color: #94a3b8; font-size: 0.875rem;
}
.sw-empty i { font-size: 1.75rem; display: block; margin-bottom: 0.5rem; opacity: 0.4; }

/* Panel for each lab (hidden by default) */
.sw-panel { display: none; }
.sw-panel.active { display: block; }
</style>
<?php require_once __DIR__ . '/../includes/user-navbar.php'; ?>

<div class="user-page">
  <div class="user-page-inner">
    <div class="sw-page-wrap">

      <h1 class="sw-page-title">Software Availability</h1>

      <div class="sw-tabs" id="swTabs">
        <?php foreach ($labRooms as $i => $lab): ?>
          <div class="sw-tab <?= $i === 0 ? 'active' : '' ?>"
               data-lab="<?= htmlspecialchars($lab) ?>"
               onclick="switchSwTab(this)">
            Lab <?= htmlspecialchars($lab) ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php foreach ($labRooms as $i => $lab): ?>
        <div class="sw-panel <?= $i === 0 ? 'active' : '' ?>" id="swPanel-<?= htmlspecialchars($lab) ?>">
          <div class="sw-card">
            <div class="sw-card-head">
              <div class="sw-card-ico"><i class="bi bi-cpu"></i></div>
              <span class="sw-card-title">Lab <?= htmlspecialchars($lab) ?> — Installed Software</span>
            </div>
            <div class="sw-card-body">
              <?php if (empty($allSoftware[$lab])): ?>
                <div class="sw-empty">
                  <i class="bi bi-inbox"></i>
                  No software has been listed for this lab yet.
                </div>
              <?php else: ?>
                <div class="sw-grid">
                  <?php foreach ($allSoftware[$lab] as $sw): ?>
                    <div class="sw-item">
                      <div class="sw-item-icon">
                        <i class="bi <?= htmlspecialchars($sw['icon'] ?: 'bi-window') ?>"></i>
                      </div>
                      <div>
                        <div class="sw-item-name"><?= htmlspecialchars($sw['software_name']) ?></div>
                        <?php if ($sw['version']): ?>
                          <div class="sw-item-ver">v<?= htmlspecialchars($sw['version']) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>
</div>

<script>
function switchSwTab(el) {
  document.querySelectorAll('.sw-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.sw-panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  const lab = el.dataset.lab;
  document.getElementById('swPanel-' + lab)?.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
