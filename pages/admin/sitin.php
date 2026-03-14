<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash = '';

// Handle sit-in logout (end session)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'logout_sitin') {
        $sid = (int)$_POST['sit_id'];
        // Get user_id to restore session
        $log = $db->prepare("SELECT user_id FROM sit_in_logs WHERE id=?")->execute([$sid])
               ? $db->query("SELECT user_id FROM sit_in_logs WHERE id=$sid")->fetch()
               : null;
        $db->prepare("UPDATE sit_in_logs SET logout_time=CURRENT_TIMESTAMP, status='done' WHERE id=?")
           ->execute([$sid]);
        // Restore remaining session for user
        if ($log) {
            $db->prepare("UPDATE users SET remaining_sessions = MIN(30, remaining_sessions+1) WHERE id=?")
               ->execute([$log['user_id']]);
        }
        $flash = 'Session ended.';
    }

    if ($action === 'sitin') {
        $uid    = (int)$_POST['user_id'];
        $purpose = $_POST['purpose'] ?? '';
        $lab    = $_POST['lab_room'] ?? '';

        // Check remaining sessions
        $u = $db->prepare("SELECT remaining_sessions FROM users WHERE id=?")->execute([$uid])
            ? $db->query("SELECT remaining_sessions FROM users WHERE id=$uid")->fetch()
            : null;

        if ($u && $u['remaining_sessions'] > 0) {
            $db->prepare("INSERT INTO sit_in_logs (user_id, lab_room, purpose, status) VALUES (?,?,?,'active')")
               ->execute([$uid, $lab, $purpose]);
            $db->prepare("UPDATE users SET remaining_sessions = remaining_sessions - 1 WHERE id=?")
               ->execute([$uid]);
            $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
               ->execute([$uid, "✅ Admin logged you in for a sit-in session in Lab {$lab} ({$purpose})."]);
            $flash = 'Sit-in session started.';
        } else {
            $flash = 'Student has no remaining sessions.';
        }
    }

    header('Location: sitin.php?flash=' . urlencode($flash)); exit;
}

if (isset($_GET['flash'])) $flash = $_GET['flash'];

// Active sit-in records
$sitins = $db->query("
    SELECT s.id, u.student_id, u.first_name||' '||u.last_name AS full_name,
           s.purpose, s.lab_room, u.remaining_sessions, s.status, s.login_time
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    WHERE s.logout_time IS NULL
    ORDER BY s.login_time DESC
")->fetchAll();

// All students for sit-in form dropdown
$allStudents = $db->query("SELECT id, student_id, first_name, last_name, remaining_sessions FROM users ORDER BY student_id")->fetchAll();

$purposes = ['C# Programming','Java Programming','PHP Programming','C Programming','ASP.net Programming'];
$labRooms = ['524','526','528','530','542','Mac Laboratory'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sit-in — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Current Sit in</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-success">✅ <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="a-card">
      <div class="a-card-body">

        <div style="margin-bottom:1rem;">
          <button class="a-btn a-btn-green" data-open-modal="sitInFormModal">+ New Sit-in</button>
        </div>

        <div class="a-table-controls">
          <div class="a-entries-wrap">
            <select id="sitSelect" class="a-entries-select">
              <option>10</option><option>25</option><option>50</option>
            </select>
            <span>entries per page</span>
          </div>
          <div class="a-search-wrap">
            <label>Search:</label>
            <input type="text" id="sitSearch" class="a-search-box" placeholder="Search..."/>
          </div>
        </div>

        <div class="a-table-wrap">
          <table class="a-table" id="sitTable">
            <thead>
              <tr>
                <th class="a-sortable" data-col="0">Sit ID Number <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">ID Number <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Name <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Purpose <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Sit Lab <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="5">Session <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="6">Status <span class="a-sort-icon">⇅</span></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="sitBody">
              <?php if (empty($sitins)): ?>
                <tr class="a-table-empty"><td colspan="8">No data available</td></tr>
              <?php else: ?>
                <?php foreach ($sitins as $row): ?>
                  <tr class="a-data-row">
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['purpose'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['lab_room']) ?></td>
                    <td><?= (int)$row['remaining_sessions'] ?></td>
                    <td><span class="a-badge badge-active">Active</span></td>
                    <td>
                      <form method="POST" action="" style="display:inline;"
                            onsubmit="return confirm('End this session?')">
                        <input type="hidden" name="action"  value="logout_sitin">
                        <input type="hidden" name="sit_id"  value="<?= $row['id'] ?>">
                        <button type="submit" class="a-btn a-btn-red a-btn-sm">End Session</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="a-table-footer">
          <div class="a-table-info" id="sitInfo"></div>
          <div class="a-pagination"  id="sitPag"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Sit-in Form Modal -->
<div class="a-modal-overlay" id="sitInFormModal">
  <div class="a-modal">
    <div class="a-modal-header">
      Sit In Form
      <button class="a-modal-close" data-close-modal="sitInFormModal">×</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="sitin">
      <div class="a-modal-body">
        <div class="a-mrow">
          <label class="a-mlabel">Student:</label>
          <select name="user_id" id="sitStudentSel" class="a-minput" required onchange="fillStudent(this)">
            <option value="" disabled selected>— Select Student —</option>
            <?php foreach ($allStudents as $u): ?>
              <option value="<?= $u['id'] ?>"
                data-sid="<?= htmlspecialchars($u['student_id']) ?>"
                data-name="<?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?>"
                data-rem="<?= (int)$u['remaining_sessions'] ?>">
                <?= htmlspecialchars($u['student_id'].' — '.$u['first_name'].' '.$u['last_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="a-mrow"><label class="a-mlabel">ID Number:</label>
          <input type="text" id="sitSID" class="a-minput" disabled /></div>
        <div class="a-mrow"><label class="a-mlabel">Student Name:</label>
          <input type="text" id="sitName" class="a-minput" disabled /></div>
        <div class="a-mrow"><label class="a-mlabel">Purpose:</label>
          <select name="purpose" class="a-minput" required>
            <option value="" disabled selected>— Select —</option>
            <?php foreach ($purposes as $p): ?>
              <option value="<?= $p ?>"><?= $p ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="a-mrow"><label class="a-mlabel">Lab:</label>
          <select name="lab_room" class="a-minput" required>
            <option value="" disabled selected>— Select —</option>
            <?php foreach ($labRooms as $lab): ?>
              <option value="<?= $lab ?>"><?= $lab ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="a-mrow"><label class="a-mlabel">Remaining Session:</label>
          <input type="text" id="sitRem" class="a-minput" disabled /></div>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" data-close-modal="sitInFormModal">Close</button>
        <button type="submit" class="a-btn a-btn-primary">Sit In</button>
      </div>
    </form>
  </div>
</div>

<script>
function fillStudent(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('sitSID').value  = opt.dataset.sid  || '';
  document.getElementById('sitName').value = opt.dataset.name || '';
  document.getElementById('sitRem').value  = opt.dataset.rem  || '0';
}
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({ tableId:'sitTable', bodyId:'sitBody', infoId:'sitInfo', pagId:'sitPag', searchId:'sitSearch', selectId:'sitSelect' });
</script>
</body></html>