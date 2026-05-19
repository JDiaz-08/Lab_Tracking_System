<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash     = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'logout_sitin') {
        $sid = (int)($_POST['sit_id'] ?? 0);
        if ($sid > 0) {
            $logStmt = $db->prepare(
                "SELECT user_id, lab_room, pc_number FROM sit_in_logs WHERE id = ? AND logout_time IS NULL"
            );
            $logStmt->execute([$sid]);
            $log = $logStmt->fetch();

            if ($log) {
                $db->prepare(
                    "UPDATE sit_in_logs
                     SET logout_time = ?, status = 'done'
                     WHERE id = ? AND logout_time IS NULL"
                )->execute([date('Y-m-d H:i:s'), $sid]);

                $db->prepare(
                    "UPDATE users SET remaining_sessions = MAX(0, remaining_sessions - 1) WHERE id = ?"
                )->execute([$log['user_id']]);

                /* Release PC */
                if ($log['pc_number']) {
                    $db->prepare("UPDATE pcs SET status = 'available', occupied_by = NULL WHERE lab_room = ? AND pc_number = ?")
                       ->execute([$log['lab_room'], $log['pc_number']]);
                }

                $db->prepare(
                    "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
                )->execute([
                    $log['user_id'],
                    "Your sit-in session has been ended by the administrator. One session has been deducted."
                ]);

                /* ── Reward System: +2 points per session ── */
                $pointsStmt = $db->prepare("SELECT remaining_sessions, points FROM users WHERE id = ?");
                $pointsStmt->execute([$log['user_id']]);
                $userRow = $pointsStmt->fetch();
                $newPoints = (int)($userRow['points'] ?? 0) + 2;

                if ($newPoints >= 8) {
                    /* Award extra session, reset points */
                    $db->prepare("UPDATE users SET points = 0, remaining_sessions = remaining_sessions + 1 WHERE id = ?")
                       ->execute([$log['user_id']]);
                    $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                       ->execute([$log['user_id'],
                           "🏆 Congratulations! You earned a FREE extra session as a reward for your lab consistency! Keep it up!"]);
                } else {
                    $db->prepare("UPDATE users SET points = ? WHERE id = ?")
                       ->execute([$newPoints, $log['user_id']]);
                }

                $flash = 'Session ended. One session deducted.';
            } else {
                $flash     = 'Session not found or already ended.';
                $flashType = 'error';
            }
        } else {
            $flash     = 'Invalid session ID.';
            $flashType = 'error';
        }
    }
    elseif ($action === 'sitin') {
        $uid     = (int)($_POST['user_id'] ?? 0);
        $purpose = trim($_POST['purpose']  ?? '');
        $lab     = trim($_POST['lab_room'] ?? '');
        $pcNum   = (int)($_POST['pc_number'] ?? 0);

        if (!$uid || !$purpose || !$lab) {
            $flash     = 'All fields are required.';
            $flashType = 'error';
        } else {
            $uStmt = $db->prepare(
                "SELECT remaining_sessions, first_name, last_name FROM users WHERE id = ?"
            );
            $uStmt->execute([$uid]);
            $u = $uStmt->fetch();

            if (!$u) {
                $flash     = 'Student not found.';
                $flashType = 'error';
            } elseif ((int)$u['remaining_sessions'] <= 0) {
                $flash     = 'Student has no remaining sessions.';
                $flashType = 'error';
            } else {
                $activeStmt = $db->prepare(
                    "SELECT id FROM sit_in_logs WHERE user_id = ? AND logout_time IS NULL"
                );
                $activeStmt->execute([$uid]);
                if ($activeStmt->fetch()) {
                    $flash     = 'This student already has an active sit-in session.';
                    $flashType = 'error';
                } else {
                    $db->prepare(
                        "INSERT INTO sit_in_logs (user_id, lab_room, purpose, status, pc_number, login_time) VALUES (?, ?, ?, 'active', ?, ?)"
                    )->execute([$uid, $lab, $purpose, $pcNum ?: null, date('Y-m-d H:i:s')]);

                    /* Mark PC as occupied */
                    if ($pcNum > 0) {
                        $db->prepare("UPDATE pcs SET status = 'occupied', occupied_by = ? WHERE lab_room = ? AND pc_number = ?")
                           ->execute([$uid, $lab, $pcNum]);
                    }

                    $pcLabel = $pcNum ? ' (PC-'.str_pad($pcNum,2,'0',STR_PAD_LEFT).')' : '';
                    $db->prepare(
                        "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
                    )->execute([
                        $uid,
                        "You have been logged in for a sit-in session in Lab {$lab}{$pcLabel} ({$purpose})."
                    ]);

                    $flash = "Sit-in started for {$u['first_name']} {$u['last_name']} in Lab {$lab}{$pcLabel}.";
                }
            }
        }
    }

    header('Location: sitin.php?flash=' . urlencode($flash) . '&ft=' . urlencode($flashType));
    exit;
}

if (isset($_GET['flash'])) {
    $flash     = $_GET['flash'];
    $flashType = $_GET['ft'] ?? 'success';
}

$sitins = $db->query("
    SELECT s.id, u.id AS user_id, u.student_id,
           u.first_name || ' ' || u.last_name AS full_name,
           s.purpose, s.lab_room, u.remaining_sessions, s.status, s.login_time, s.pc_number
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    WHERE s.logout_time IS NULL
    ORDER BY s.login_time DESC
")->fetchAll();

$allStudents = $db->query("
    SELECT id, student_id, first_name, last_name, middle_name,
           course, remaining_sessions
    FROM users ORDER BY student_id ASC
")->fetchAll();

$purposes = [
    'C# Programming','Java Programming','PHP Programming',
    'C Programming','ASP.net Programming',
];
$labRooms = ['524','526','528','530','542','Mac Laboratory'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sit-in — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    .stu-results {
      max-height: 270px;
      overflow-y: auto;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      margin-top: 0.55rem;
      display: none;
      background: #fff;
    }
    .stu-results.show { display: block; }
    .stu-result-item {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0.6rem 0.875rem;
      border-bottom: 1px solid #f0f4f8;
      cursor: pointer;
      transition: background 0.13s;
      font-size: 0.83rem;
    }
    .stu-result-item:last-child { border-bottom: none; }
    .stu-result-item:hover { background: #f0f4f8; }
    .stu-result-id   { font-weight: 700; color: #1e293b; min-width: 88px; flex-shrink: 0; }
    .stu-result-name { flex: 1; color: #475569; }
    .stu-result-sess {
      font-size: 0.70rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 100px;
      flex-shrink: 0;
    }
    .sess-ok    { background: rgba(22,163,74,0.10);  color: #15803d; }
    .sess-low   { background: rgba(217,119,6,0.10);  color: #92400e; }
    .sess-empty { background: rgba(220,38,38,0.08);  color: #991b1b; }
    .stu-results-empty {
      padding: 1rem;
      text-align: center;
      color: #94a3b8;
      font-size: 0.83rem;
    }
    .a-modal-wide { max-width: 500px; }
    .sess-warning {
      display: none;
      background: rgba(220,38,38,0.07);
      border: 1px solid rgba(220,38,38,0.22);
      color: #991b1b;
      border-radius: 6px;
      padding: 0.5rem 0.8rem;
      font-size: 0.80rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: none;
      align-items: center;
      gap: 6px;
    }
    .sess-warning i { font-size: 0.85rem; }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Current Sit-in</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <i class="bi bi-<?= $flashType === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-pc-display"></i> Active Sessions
      </div>
      <div class="a-card-body">

        <div style="margin-bottom:1rem;">
          <button class="a-btn a-btn-green" onclick="openModal('searchStudentModal')">
            <i class="bi bi-person-plus"></i> New Sit-in
          </button>
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
                <th class="a-sortable" data-col="0">Sit ID <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">ID Number <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Name <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Purpose <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Lab <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="5">PC <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="6">Sessions Left <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="7">Status <span class="a-sort-icon">⇅</span></th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="sitBody">
              <?php if (empty($sitins)): ?>
                <tr class="a-table-empty">
                  <td colspan="9">No active sit-in sessions.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($sitins as $row): ?>
                  <tr class="a-data-row">
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['purpose'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['lab_room']) ?></td>
                    <td><?= $row['pc_number'] ? 'PC-'.str_pad($row['pc_number'],2,'0',STR_PAD_LEFT) : '—' ?></td>
                    <td><?= (int)$row['remaining_sessions'] ?></td>
                    <td><span class="a-badge badge-active">Active</span></td>
                    <td>
                      <form method="POST" action="sitin.php" style="display:inline;"
                        onsubmit="return confirm('End this session? One session will be deducted.')">
                        <input type="hidden" name="action" value="logout_sitin">
                        <input type="hidden" name="sit_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" class="a-btn a-btn-red a-btn-sm">
                          <i class="bi bi-door-open"></i> End Session
                        </button>
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

<!-- MODAL 1: Search Student -->
<div class="a-modal-overlay" id="searchStudentModal">
  <div class="a-modal a-modal-wide">
    <div class="a-modal-header">
      <span>Search Student</span>
      <button class="a-modal-close" onclick="closeSearchModal()">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <div class="a-modal-body">
      <div style="position:relative;">
        <i class="bi bi-search" style="position:absolute;left:0.7rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.84rem;"></i>
        <input type="text" id="stuSearchInput" class="a-search-input"
               placeholder="Type ID number or student name..."
               autocomplete="off" style="padding-left:2rem;" />
      </div>
      <div id="stuResults" class="stu-results">
        <div class="stu-results-empty">Start typing to search students...</div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL 2: Sit-in Form -->
<div class="a-modal-overlay" id="sitInFormModal">
  <div class="a-modal">
    <div class="a-modal-header">
      <span>Sit-in Form</span>
      <button class="a-modal-close" onclick="closeSitInModal()">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <form method="POST" action="" id="sitInForm">
      <input type="hidden" name="action"  value="sitin">
      <input type="hidden" name="user_id" id="fUserId">
      <div class="a-modal-body">

        <div id="sessWarning" class="sess-warning">
          <i class="bi bi-exclamation-triangle-fill"></i>
          This student has no remaining sessions. Sit-in cannot be started.
        </div>

        <div class="a-mrow">
          <label class="a-mlabel">ID Number</label>
          <input type="text" id="fStudentId" class="a-minput" disabled />
        </div>
        <div class="a-mrow">
          <label class="a-mlabel">Student Name</label>
          <input type="text" id="fStudentName" class="a-minput" disabled />
        </div>
        <div class="a-mrow">
          <label class="a-mlabel">Purpose</label>
          <select name="purpose" id="fPurpose" class="a-minput" required>
            <option value="" disabled selected>— Select Purpose —</option>
            <?php foreach ($purposes as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="a-mrow">
          <label class="a-mlabel">Laboratory</label>
          <select name="lab_room" id="fLab" class="a-minput" required onchange="loadPcOptions('fLab','fPcNumber')">
            <option value="" disabled selected>— Select Lab —</option>
            <?php foreach ($labRooms as $lab): ?>
              <option value="<?= htmlspecialchars($lab) ?>"><?= htmlspecialchars($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="a-mrow">
          <label class="a-mlabel">PC Number</label>
          <select name="pc_number" id="fPcNumber" class="a-minput">
            <option value="" selected>— Select a lab first —</option>
          </select>
        </div>
        <div class="a-mrow">
          <label class="a-mlabel">Sessions Remaining</label>
          <input type="text" id="fRemaining" class="a-minput" disabled />
        </div>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" onclick="closeSitInModal()">Cancel</button>
        <button type="submit" id="fSubmitBtn" class="a-btn a-btn-primary">
          <i class="bi bi-box-arrow-in-right"></i> Sit In
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
const ALL_STUDENTS = <?= json_encode(array_values($allStudents), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const stuSearchInput = document.getElementById('stuSearchInput');
const stuResults     = document.getElementById('stuResults');

function closeSearchModal() {
    closeModal('searchStudentModal');
    stuSearchInput.value = '';
    stuResults.innerHTML = '<div class="stu-results-empty">Start typing to search students...</div>';
    stuResults.classList.remove('show');
}

function closeSitInModal() {
    closeModal('sitInFormModal');
}

document.getElementById('searchStudentModal').addEventListener('click', function(e) {
    if (e.target === this) closeSearchModal();
});
document.getElementById('sitInFormModal').addEventListener('click', function(e) {
    if (e.target === this) closeSitInModal();
});

stuSearchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    stuResults.innerHTML = '';

    if (!q) {
        stuResults.innerHTML = '<div class="stu-results-empty">Start typing to search students...</div>';
        stuResults.classList.add('show');
        return;
    }

    const matches = ALL_STUDENTS.filter(s => {
        const full = (s.first_name + ' ' + (s.middle_name || '') + ' ' + s.last_name).toLowerCase();
        return s.student_id.toLowerCase().includes(q) || full.includes(q);
    });

    if (matches.length === 0) {
        stuResults.innerHTML = '<div class="stu-results-empty">No students found.</div>';
    } else {
        matches.slice(0, 25).forEach(s => {
            const rem       = parseInt(s.remaining_sessions) || 0;
            const sessClass = rem > 10 ? 'sess-ok' : rem > 0 ? 'sess-low' : 'sess-empty';
            const mid       = s.middle_name ? ' ' + s.middle_name + ' ' : ' ';
            const fullName  = s.first_name + mid + s.last_name;

            const item = document.createElement('div');
            item.className = 'stu-result-item';
            item.innerHTML =
                '<span class="stu-result-id">'  + esc(s.student_id) + '</span>' +
                '<span class="stu-result-name">' + esc(fullName) + '</span>' +
                '<span class="stu-result-sess ' + sessClass + '">' + rem + ' sessions</span>';
            item.addEventListener('click', () => selectStudent(s));
            stuResults.appendChild(item);
        });
    }

    stuResults.classList.add('show');
});

function selectStudent(s) {
    closeSearchModal();

    const rem      = parseInt(s.remaining_sessions) || 0;
    const mid      = s.middle_name ? ' ' + s.middle_name + ' ' : ' ';
    const fullName = s.first_name + mid + s.last_name;

    document.getElementById('fUserId').value      = s.id;
    document.getElementById('fStudentId').value   = s.student_id;
    document.getElementById('fStudentName').value = fullName;
    document.getElementById('fRemaining').value   = rem + ' / 30';
    document.getElementById('fPurpose').selectedIndex = 0;
    document.getElementById('fLab').selectedIndex     = 0;

    const warning   = document.getElementById('sessWarning');
    const submitBtn = document.getElementById('fSubmitBtn');
    if (rem <= 0) {
        warning.style.display   = 'flex';
        submitBtn.disabled      = true;
        submitBtn.style.opacity = '0.45';
    } else {
        warning.style.display   = 'none';
        submitBtn.disabled      = false;
        submitBtn.style.opacity = '';
    }

    openModal('sitInFormModal');
}

function esc(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function loadPcOptions(labSelectId, pcSelectId) {
    const lab = document.getElementById(labSelectId).value;
    const sel = document.getElementById(pcSelectId);
    sel.innerHTML = '<option value="">— Loading... —</option>';
    if (!lab) { sel.innerHTML = '<option value="">— Select a lab first —</option>'; return; }
    fetch('<?= $base ?>pages/api/pc-status.php?lab=' + encodeURIComponent(lab))
      .then(r => r.json())
      .then(pcs => {
        sel.innerHTML = '<option value="">— Select PC (optional) —</option>';
        pcs.forEach(pc => {
          const num = String(pc.pc_number).padStart(2,'0');
          const disabled = pc.status !== 'available';
          const label = 'PC-' + num + (disabled ? ' (' + pc.status + ')' : '');
          sel.innerHTML += '<option value="' + pc.pc_number + '"' + (disabled ? ' disabled' : '') + '>' + label + '</option>';
        });
      })
      .catch(() => { sel.innerHTML = '<option value="">— Error loading PCs —</option>'; });
}

initAdminTable({
    tableId:'sitTable', bodyId:'sitBody', infoId:'sitInfo',
    pagId:'sitPag', searchId:'sitSearch', selectId:'sitSelect'
});
</script>
</body>
</html>