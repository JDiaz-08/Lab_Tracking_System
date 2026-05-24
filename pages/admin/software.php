<?php
// FILE: pages/admin/software.php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash     = '';
$flashType = 'success';
$labRooms  = ['524','526','528','530','542','Mac Laboratory'];

$iconOptions = [
    'bi-window' => 'Window',
    'bi-code-slash' => 'Code',
    'bi-terminal' => 'Terminal',
    'bi-filetype-java' => 'Java',
    'bi-filetype-py' => 'Python',
    'bi-filetype-html' => 'HTML',
    'bi-filetype-css' => 'CSS',
    'bi-filetype-js' => 'JavaScript',
    'bi-database' => 'Database',
    'bi-browser-chrome' => 'Browser',
    'bi-brush' => 'Design',
    'bi-gear' => 'Tools',
    'bi-cpu' => 'System',
    'bi-globe' => 'Web',
    'bi-file-earmark-text' => 'Document',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_software') {
        $lab     = trim($_POST['lab_room'] ?? '');
        $name    = trim($_POST['software_name'] ?? '');
        $version = trim($_POST['version'] ?? '');
        $icon    = trim($_POST['icon'] ?? 'bi-window');
        if ($lab && $name) {
            $db->prepare("INSERT INTO lab_software (lab_room, software_name, version, icon) VALUES (?, ?, ?, ?)")
               ->execute([$lab, $name, $version ?: null, $icon]);
            $flash = "Software \"$name\" added to Lab $lab.";
        } else {
            $flash = 'Lab and software name are required.';
            $flashType = 'error';
        }
    }
    elseif ($action === 'delete_software') {
        $sid = (int)($_POST['sw_id'] ?? 0);
        if ($sid > 0) {
            $db->prepare("DELETE FROM lab_software WHERE id = ?")->execute([$sid]);
            $flash = 'Software removed.';
        }
    }
    elseif ($action === 'edit_software') {
        $sid     = (int)($_POST['sw_id'] ?? 0);
        $name    = trim($_POST['software_name'] ?? '');
        $version = trim($_POST['version'] ?? '');
        $icon    = trim($_POST['icon'] ?? 'bi-window');
        if ($sid > 0 && $name) {
            $db->prepare("UPDATE lab_software SET software_name = ?, version = ?, icon = ? WHERE id = ?")
               ->execute([$name, $version ?: null, $icon, $sid]);
            $flash = 'Software updated.';
        }
    }

    header('Location: software.php?flash=' . urlencode($flash) . '&ft=' . urlencode($flashType));
    exit;
}

if (isset($_GET['flash'])) {
    $flash     = $_GET['flash'];
    $flashType = $_GET['ft'] ?? 'success';
}

/* Fetch software per lab */
$allSoftware = [];
foreach ($labRooms as $lab) {
    $stmt = $db->prepare("SELECT * FROM lab_software WHERE lab_room = ? ORDER BY software_name ASC");
    $stmt->execute([$lab]);
    $allSoftware[$lab] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Software — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    .sw-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 1.25rem; }
    .sw-tab {
      padding: 0.5rem 1.1rem; border-radius: 8px;
      background: #fff; color: #475569; border: 1.5px solid #e2e8f0;
      font-size: 0.80rem; font-weight: 600;
      cursor: pointer; transition: all 0.15s;
    }
    .sw-tab:hover { background: #f0f4f8; border-color: #cbd5e1; }
    .sw-tab.active { background: #1a3a6b; color: #fff; border-color: #1a3a6b; }

    .sw-panel { display: none; }
    .sw-panel.active { display: block; }

    .sw-add-form {
      display: flex; gap: 0.5rem; align-items: flex-end;
      flex-wrap: wrap; margin-bottom: 1rem;
      padding: 0.875rem; background: #f8fafc; border-radius: 8px;
      border: 1px solid #e2e8f0;
    }
    .sw-add-field { display: flex; flex-direction: column; gap: 3px; }
    .sw-add-field label { font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .sw-add-field input, .sw-add-field select {
      padding: 0.45rem 0.65rem; border: 1.5px solid #e2e8f0; border-radius: 6px;
      font-family: inherit; font-size: 0.82rem; outline: none;
      transition: border-color 0.15s;
    }
    .sw-add-field input:focus, .sw-add-field select:focus { border-color: #2563EB; }

    .sw-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.65rem;
    }
    .sw-item {
      display: flex; align-items: center; gap: 0.65rem;
      padding: 0.65rem 0.875rem; background: #fff;
      border: 1px solid #e2e8f0; border-radius: 8px;
      transition: all 0.12s;
    }
    .sw-item:hover { background: #f8fafc; }
    .sw-item-icon {
      width: 32px; height: 32px; border-radius: 7px;
      background: rgba(37,99,235,0.08); color: #2563EB;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.88rem; flex-shrink: 0;
    }
    .sw-item-name { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
    .sw-item-ver  { font-size: 0.68rem; color: #94a3b8; }
    .sw-item-actions { margin-left: auto; display: flex; gap: 3px; }
    .sw-del-btn {
      background: none; border: 1px solid transparent; cursor: pointer;
      padding: 3px 5px; border-radius: 5px;
      font-size: 0.72rem; color: #cbd5e1; transition: all 0.15s;
    }
    .sw-del-btn:hover { background: rgba(220,38,38,0.08); color: #dc2626; border-color: rgba(220,38,38,0.20); }

    .sw-empty { text-align: center; padding: 1.5rem; color: #94a3b8; font-size: 0.82rem; }
    .sw-empty i { font-size: 1.5rem; display: block; margin-bottom: 0.4rem; opacity: 0.35; }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Software Availability</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <i class="bi bi-<?= $flashType === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <!-- Lab Tabs -->
    <div class="sw-tabs" id="swTabs">
      <?php foreach ($labRooms as $i => $lab): ?>
        <div class="sw-tab <?= $i === 0 ? 'active' : '' ?>"
             data-lab="<?= htmlspecialchars($lab) ?>"
             onclick="switchSwTab(this)">
          Lab <?= htmlspecialchars($lab) ?>
          <span style="font-size:0.62rem;opacity:0.6;margin-left:3px;">(<?= count($allSoftware[$lab]) ?>)</span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php foreach ($labRooms as $i => $lab): ?>
      <div class="sw-panel <?= $i === 0 ? 'active' : '' ?>" id="swPanel-<?= htmlspecialchars($lab) ?>">
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-cpu"></i> Lab <?= htmlspecialchars($lab) ?> — Installed Software
          </div>
          <div class="a-card-body">

            <!-- Add form -->
            <form method="POST" action="" class="sw-add-form">
              <input type="hidden" name="action" value="add_software">
              <input type="hidden" name="lab_room" value="<?= htmlspecialchars($lab) ?>">
              <div class="sw-add-field">
                <label>Software Name</label>
                <input type="text" name="software_name" placeholder="e.g. Visual Studio Code" required style="min-width:180px;" />
              </div>
              <div class="sw-add-field">
                <label>Version</label>
                <input type="text" name="version" placeholder="e.g. 1.85" style="width:90px;" />
              </div>
              <div class="sw-add-field">
                <label>Icon</label>
                <select name="icon">
                  <?php foreach ($iconOptions as $cls => $label): ?>
                    <option value="<?= $cls ?>"><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="a-btn a-btn-primary a-btn-sm">
                <i class="bi bi-plus-lg"></i> Add
              </button>
            </form>

            <!-- Software list -->
            <?php if (empty($allSoftware[$lab])): ?>
              <div class="sw-empty">
                <i class="bi bi-inbox"></i>
                No software listed for this lab yet. Add one above.
              </div>
            <?php else: ?>
              <div class="sw-grid">
                <?php foreach ($allSoftware[$lab] as $sw): ?>
                  <div class="sw-item">
                    <div class="sw-item-icon">
                      <i class="bi <?= htmlspecialchars($sw['icon'] ?: 'bi-window') ?>"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                      <div class="sw-item-name"><?= htmlspecialchars($sw['software_name']) ?></div>
                      <?php if ($sw['version']): ?>
                        <div class="sw-item-ver">v<?= htmlspecialchars($sw['version']) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="sw-item-actions">
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this software?')">
                        <input type="hidden" name="action" value="delete_software">
                        <input type="hidden" name="sw_id" value="<?= $sw['id'] ?>">
                        <button type="submit" class="sw-del-btn" title="Remove">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </form>
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

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
function switchSwTab(el) {
  document.querySelectorAll('.sw-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.sw-panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('swPanel-' + el.dataset.lab)?.classList.add('active');
}
</script>
</body>
</html>
