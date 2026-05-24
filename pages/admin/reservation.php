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
    $rid    = (int)($_POST['res_id'] ?? 0);

    if ($action === 'approve') {
        $res = $db->prepare("SELECT * FROM reservations WHERE id=?");
        $res->execute([$rid]);
        $res = $res->fetch();
        $db->prepare("UPDATE reservations SET status='approved' WHERE id=?")->execute([$rid]);
        if ($res) {
            $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
               ->execute([$res['user_id'],
                   "✅ Your reservation for Lab {$res['lab_room']} on "
                   . date('F j, Y', strtotime($res['date']))
                   . " at {$res['time_slot']} has been approved. Please arrive on time."]);
        }
        $flash = 'Reservation approved.';
    }
    elseif ($action === 'reject') {
        $res = $db->prepare("SELECT * FROM reservations WHERE id=?");
        $res->execute([$rid]);
        $res = $res->fetch();
        $rejectNote = trim($_POST['reject_note'] ?? '');
        $db->prepare("UPDATE reservations SET status='rejected', reject_note=? WHERE id=?")->execute([$rejectNote ?: null, $rid]);
        if ($res) {
            $noteMsg = $rejectNote ? " Reason: {$rejectNote}" : '';
            $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
               ->execute([$res['user_id'],
                   "❌ Your reservation for Lab {$res['lab_room']} on "
                   . date('F j, Y', strtotime($res['date']))
                   . " has been rejected.{$noteMsg}"]);
        }
        $flash = 'Reservation rejected.';
    }
    elseif ($action === 'delete') {
        $db->prepare("DELETE FROM reservations WHERE id=?")->execute([$rid]);
        $flash = 'Reservation deleted.';
    }
    elseif ($action === 'start_session') {
        $res = $db->prepare("SELECT r.*, u.remaining_sessions, u.first_name, u.last_name FROM reservations r JOIN users u ON u.id = r.user_id WHERE r.id=?");
        $res->execute([$rid]);
        $res = $res->fetch();
        if (!$res) {
            $flash = 'Reservation not found.'; $flashType = 'error';
        } elseif ($res['status'] !== 'approved') {
            $flash = 'Only approved reservations can be started.'; $flashType = 'error';
        } elseif ((int)$res['remaining_sessions'] <= 0) {
            $flash = 'Student has no remaining sessions.'; $flashType = 'error';
        } else {
            // Check if student already has active sit-in
            $activeCheck = $db->prepare("SELECT id FROM sit_in_logs WHERE user_id = ? AND logout_time IS NULL");
            $activeCheck->execute([$res['user_id']]);
            if ($activeCheck->fetch()) {
                $flash = 'This student already has an active sit-in session.'; $flashType = 'error';
            } else {
                // Insert sit_in_log
                $db->prepare("INSERT INTO sit_in_logs (user_id, lab_room, purpose, status, pc_number, login_time) VALUES (?, ?, ?, 'active', ?, ?)")
                   ->execute([$res['user_id'], $res['lab_room'], $res['purpose'], $res['pc_number'] ?: null, date('Y-m-d H:i:s')]);

                // Mark PC as occupied
                if ($res['pc_number']) {
                    $db->prepare("UPDATE pcs SET status = 'occupied', occupied_by = ? WHERE lab_room = ? AND pc_number = ?")
                       ->execute([$res['user_id'], $res['lab_room'], $res['pc_number']]);
                }

                // Mark reservation as done
                $db->prepare("UPDATE reservations SET status='done' WHERE id=?")->execute([$rid]);

                // Notify student
                $pcLabel = $res['pc_number'] ? ' PC-'.str_pad($res['pc_number'],2,'0',STR_PAD_LEFT) : '';
                $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
                   ->execute([$res['user_id'],
                       "🟢 Your sit-in session has started! Lab {$res['lab_room']}{$pcLabel} — {$res['purpose']}. Welcome!"]);

                $flash = "Session started for {$res['first_name']} {$res['last_name']} in Lab {$res['lab_room']}.";
            }
        }
    }

    header('Location: reservation.php?flash=' . urlencode($flash) . '&ft=' . urlencode($flashType));
    exit;
}

if (isset($_GET['flash'])) {
    $flash     = $_GET['flash'];
    $flashType = $_GET['ft'] ?? 'success';
}

$reservations = $db->query("
    SELECT r.*, u.student_id,
           u.first_name||' '||u.last_name AS full_name,
           u.remaining_sessions
    FROM reservations r
    JOIN users u ON u.id = r.user_id
    WHERE r.disabled_by_student = 0
    ORDER BY r.created_at DESC
")->fetchAll();

// Compute display status (expired if past date and still pending/approved)
$today = date('Y-m-d');
foreach ($reservations as &$r) {
    if (in_array($r['status'], ['pending','approved']) && $r['date'] < $today) {
        $r['display_status'] = 'expired';
    } else {
        $r['display_status'] = $r['status'];
    }
}
unset($r);

$pendingCount  = count(array_filter($reservations, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($reservations, fn($r) => $r['status'] === 'approved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Reservations — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    /* ── Stat strip ── */
    .res-stat-strip { display:flex; gap:0.75rem; margin-bottom:1.25rem; flex-wrap:wrap; }
    .res-stat-chip  { display:flex; align-items:center; gap:8px; padding:0.55rem 1rem; border-radius:9px; font-size:0.82rem; font-weight:700; border:1px solid; }
    .rsc-pending  { background:rgba(217,119,6,0.07);  border-color:rgba(217,119,6,0.22);  color:#92400e; }
    .rsc-approved { background:rgba(22,163,74,0.07);  border-color:rgba(22,163,74,0.22);  color:#15803d; }
    .rsc-total    { background:rgba(37,99,235,0.06);  border-color:rgba(37,99,235,0.18);  color:#1e40af; }

    /* ── Badge for display_status ── */
    .badge-expired { background:rgba(100,116,139,0.08); color:#475569; border:1px solid rgba(100,116,139,0.22); }
    .badge-done    { background:rgba(37,99,235,0.06);   color:#1e40af; border:1px solid rgba(37,99,235,0.18); }

    /* ── Actions cell ── */
    .res-actions { display:flex; gap:4px; flex-wrap:nowrap; align-items:center; }

    /* ── Reject modal note ── */
    .rej-modal-note { width:100%; border:1.5px solid var(--a-gray200); border-radius:6px; padding:0.6rem 0.75rem; font-family:var(--font); font-size:0.875rem; resize:vertical; min-height:80px; outline:none; transition:border-color .18s; color:#1e293b; background:#fff; }
    .rej-modal-note:focus { border-color:var(--a-mid); }

    /* ── Start Session modal ── */
    .ss-detail-row { display:flex; align-items:center; gap:8px; margin-bottom:0.6rem; font-size:0.84rem; color:#1e293b; }
    .ss-detail-row i { color:#2563EB; font-size:0.9rem; flex-shrink:0; }
    .ss-detail-row strong { font-weight:700; }
    .ss-highlight { background:rgba(22,163,74,0.07); border:1px solid rgba(22,163,74,0.22); border-radius:8px; padding:0.75rem 1rem; margin-bottom:0.875rem; }
    .ss-highlight-label { font-size:0.68rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#15803d; margin-bottom:0.35rem; }
    .ss-student-name { font-size:1rem; font-weight:800; color:#0f2854; }
    .ss-student-id   { font-size:0.75rem; color:#64748b; }

    /* dark mode */
    html.dark .rej-modal-note { background:#1e293b; color:#e2e8f0; border-color:#334155; }
    html.dark .rej-modal-note:focus { border-color:#4988c4; }
    html.dark .ss-student-name { color:#e2e8f0; }
    html.dark .ss-detail-row { color:#cbd5e1; }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Reservation Management</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <i class="bi bi-<?= $flashType === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <!-- Stat strip -->
    <div class="res-stat-strip">
      <div class="res-stat-chip rsc-pending">
        <i class="bi bi-clock"></i> <?= $pendingCount ?> Pending
      </div>
      <div class="res-stat-chip rsc-approved">
        <i class="bi bi-check-circle"></i> <?= $approvedCount ?> Approved
      </div>
      <div class="res-stat-chip rsc-total">
        <i class="bi bi-list-ul"></i> <?= count($reservations) ?> Total
      </div>
    </div>

    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-calendar-check"></i> All Reservations
      </div>
      <div class="a-card-body">

        <div class="a-table-controls">
          <div class="a-entries-wrap">
            <select id="resSelect" class="a-entries-select">
              <option>10</option><option>25</option><option>50</option>
            </select>
            <span>entries per page</span>
          </div>
          <div class="a-search-wrap">
            <label>Search:</label>
            <input type="text" id="resSearch" class="a-search-box" placeholder="Search..."/>
          </div>
        </div>

        <div class="a-table-wrap">
          <table class="a-table" id="resTable">
            <thead>
              <tr>
                <th class="a-sortable" data-col="0">Student ID <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">Name <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Lab <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Date <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Time <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="5">Purpose <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="6">PC <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="7">Status <span class="a-sort-icon">⇅</span></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="resBody">
              <?php if (empty($reservations)): ?>
                <tr class="a-table-empty"><td colspan="9">No reservations yet.</td></tr>
              <?php else: ?>
                <?php foreach ($reservations as $r): ?>
                  <tr class="a-data-row">
                    <td><?= htmlspecialchars($r['student_id']) ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['lab_room']) ?></td>
                    <td><?= date('M j, Y', strtotime($r['date'])) ?></td>
                    <td><?= htmlspecialchars($r['time_slot']) ?></td>
                    <td><?= htmlspecialchars($r['purpose'] ?? '—') ?></td>
                    <td><?= $r['pc_number'] ? 'PC-'.str_pad($r['pc_number'],2,'0',STR_PAD_LEFT) : '—' ?></td>
                    <td>
                      <span class="a-badge badge-<?= $r['display_status'] ?>">
                        <?= ucfirst($r['display_status']) ?>
                      </span>
                      <?php if ($r['reject_note'] && $r['status'] === 'rejected'): ?>
                        <span title="Reason: <?= htmlspecialchars($r['reject_note']) ?>" style="cursor:help;margin-left:3px;color:#94a3b8;font-size:0.78rem;">
                          <i class="bi bi-info-circle"></i>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="res-actions">
                        <?php if ($r['status'] === 'pending'): ?>
                          <!-- Approve -->
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="a-btn a-btn-green a-btn-sm" title="Approve">
                              <i class="bi bi-check-lg"></i>
                            </button>
                          </form>
                          <!-- Reject -->
                          <button type="button" class="a-btn a-btn-yellow a-btn-sm" title="Reject"
                                  onclick="openRejectModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['full_name'], ENT_QUOTES) ?>')">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        <?php endif; ?>

                        <?php if ($r['status'] === 'approved' && $r['display_status'] !== 'expired'): ?>
                          <!-- Start Session -->
                          <button type="button" class="a-btn a-btn-primary a-btn-sm" title="Start Session"
                                  onclick="openStartModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['full_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($r['student_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($r['lab_room'], ENT_QUOTES) ?>', '<?= $r['pc_number'] ? 'PC-'.str_pad($r['pc_number'],2,'0',STR_PAD_LEFT) : '—' ?>', '<?= htmlspecialchars($r['purpose'] ?? '', ENT_QUOTES) ?>', '<?= date('M j, Y', strtotime($r['date'])) ?>', '<?= htmlspecialchars($r['time_slot'], ENT_QUOTES) ?>', <?= (int)$r['remaining_sessions'] ?>)">
                            <i class="bi bi-play-fill"></i> Start
                          </button>
                        <?php endif; ?>

                        <!-- Delete -->
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Delete this reservation?')">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                          <button type="submit" class="a-btn a-btn-red a-btn-sm" title="Delete">
                            <i class="bi bi-trash3"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="a-table-footer">
          <div class="a-table-info" id="resInfo"></div>
          <div class="a-pagination"  id="resPag"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ══ REJECT MODAL ══ -->
<div class="a-modal-overlay" id="rejectModal">
  <div class="a-modal a-modal-wide">
    <div class="a-modal-header">
      <span><i class="bi bi-x-circle-fill" style="color:#d97706;margin-right:6px;"></i>Reject Reservation</span>
      <button class="a-modal-close" onclick="document.getElementById('rejectModal').classList.remove('open')">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <form method="POST" id="rejectForm">
      <input type="hidden" name="action" value="reject">
      <input type="hidden" name="res_id" id="rejectResId">
      <input type="hidden" name="reject_note" id="rejectNoteInput">
      <div class="a-modal-body">
        <p style="font-size:0.84rem;color:#64748b;margin-bottom:1rem;">
          Rejecting reservation for <strong id="rejectStudentName"></strong>. Please select a reason:
        </p>
        <style>
        .rej-options { display: flex; flex-direction: column; gap: 8px; }
        .rej-option {
          display: flex; align-items: center; gap: 10px;
          padding: 0.75rem 1rem; border-radius: 9px;
          border: 1.5px solid #e2e8f0; background: #f8fafc;
          cursor: pointer; font-size: 0.855rem; font-weight: 500;
          color: #334155; transition: all 0.16s; text-align: left; width: 100%;
        }
        .rej-option i { font-size: 1rem; color: #94a3b8; flex-shrink: 0; transition: color 0.16s; }
        .rej-option:hover { border-color: #d97706; background: rgba(217,119,6,0.04); color: #92400e; }
        .rej-option:hover i { color: #d97706; }
        .rej-option.selected { border-color: #d97706; background: rgba(217,119,6,0.07); color: #92400e; font-weight: 700; }
        .rej-option.selected i { color: #d97706; }
        .rej-option.selected::after { content: '✓'; margin-left: auto; font-weight: 800; color: #d97706; }
        </style>
        <div class="rej-options">
          <button type="button" class="rej-option" onclick="selectRejectReason(this,'Lab Unavailable')">
            <i class="bi bi-door-closed"></i> Lab Unavailable
          </button>
          <button type="button" class="rej-option" onclick="selectRejectReason(this,'Class Ongoing')">
            <i class="bi bi-person-video3"></i> Class Ongoing
          </button>
          <button type="button" class="rej-option" onclick="selectRejectReason(this,'Lab is Occupied')">
            <i class="bi bi-people-fill"></i> Lab is Occupied
          </button>
          <button type="button" class="rej-option" onclick="selectRejectReason(this,'PC Under Maintenance')">
            <i class="bi bi-tools"></i> PC Under Maintenance
          </button>
          <button type="button" class="rej-option" onclick="selectRejectReason(this,'Outside Operating Hours')">
            <i class="bi bi-clock-history"></i> Outside Operating Hours
          </button>
        </div>
        <p id="rej-error" style="display:none;color:#dc2626;font-size:0.75rem;margin-top:8px;">
          Please select a reason before confirming.
        </p>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" onclick="document.getElementById('rejectModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="a-btn a-btn-yellow" onclick="return validateReject()">
          <i class="bi bi-x-circle"></i> Confirm Reject
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ START SESSION MODAL ══ -->
<div class="a-modal-overlay" id="startSessionModal">
  <div class="a-modal a-modal-wide">
    <div class="a-modal-header">
      <span><i class="bi bi-play-circle-fill" style="color:#16a34a;margin-right:6px;"></i>Start Sit-in Session</span>
      <button class="a-modal-close" onclick="document.getElementById('startSessionModal').classList.remove('open')">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <form method="POST" id="startSessionForm">
      <input type="hidden" name="action" value="start_session">
      <input type="hidden" name="res_id" id="startResId">
      <div class="a-modal-body">
        <div class="ss-highlight">
          <div class="ss-highlight-label">Starting session for</div>
          <div class="ss-student-name" id="ssStudentName">—</div>
          <div class="ss-student-id"   id="ssStudentId">—</div>
        </div>
        <div class="ss-detail-row"><i class="bi bi-building"></i> Lab: <strong id="ssLab">—</strong></div>
        <div class="ss-detail-row"><i class="bi bi-display"></i> PC: <strong id="ssPc">—</strong></div>
        <div class="ss-detail-row"><i class="bi bi-calendar-event"></i> Date: <strong id="ssDate">—</strong></div>
        <div class="ss-detail-row"><i class="bi bi-clock"></i> Time: <strong id="ssTime">—</strong></div>
        <div class="ss-detail-row"><i class="bi bi-journal-code"></i> Purpose: <strong id="ssPurpose">—</strong></div>
        <div class="ss-detail-row"><i class="bi bi-hourglass-split"></i> Sessions Remaining: <strong id="ssSessions" style="color:#16a34a;">—</strong></div>
        <div style="background:rgba(37,99,235,0.05);border:1px solid rgba(37,99,235,0.15);border-radius:7px;padding:0.65rem 0.875rem;margin-top:0.875rem;font-size:0.80rem;color:#1e40af;">
          <i class="bi bi-info-circle" style="margin-right:5px;"></i>
          This will create an active sit-in session and mark the PC as <strong>Occupied</strong>. The reservation will be marked as <strong>Done</strong>.
        </div>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" onclick="document.getElementById('startSessionModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="a-btn a-btn-green">
          <i class="bi bi-play-fill"></i> Start Session
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({
  tableId:'resTable', bodyId:'resBody', infoId:'resInfo',
  pagId:'resPag', searchId:'resSearch', selectId:'resSelect'
});

function openRejectModal(id, name) {
  document.getElementById('rejectResId').value = id;
  document.getElementById('rejectStudentName').textContent = name;
  document.getElementById('rejectNoteInput').value = '';
  /* Reset all option selections */
  document.querySelectorAll('.rej-option').forEach(b => b.classList.remove('selected'));
  document.getElementById('rej-error').style.display = 'none';
  document.getElementById('rejectModal').classList.add('open');
}
document.getElementById('rejectModal')?.addEventListener('click', e => {
  if (e.target.id === 'rejectModal') document.getElementById('rejectModal').classList.remove('open');
});

function selectRejectReason(btn, reason) {
  document.querySelectorAll('.rej-option').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('rejectNoteInput').value = reason;
  document.getElementById('rej-error').style.display = 'none';
}

function validateReject() {
  if (!document.getElementById('rejectNoteInput').value) {
    document.getElementById('rej-error').style.display = 'block';
    return false;
  }
  return true;
}

function openStartModal(id, name, stuId, lab, pc, purpose, date, time, sessions) {
  document.getElementById('startResId').value = id;
  document.getElementById('ssStudentName').textContent = name;
  document.getElementById('ssStudentId').textContent   = stuId;
  document.getElementById('ssLab').textContent         = lab;
  document.getElementById('ssPc').textContent          = pc;
  document.getElementById('ssDate').textContent        = date;
  document.getElementById('ssTime').textContent        = time;
  document.getElementById('ssPurpose').textContent     = purpose;
  document.getElementById('ssSessions').textContent    = sessions + ' / 30';
  document.getElementById('startSessionModal').classList.add('open');
}
document.getElementById('startSessionModal')?.addEventListener('click', e => {
  if (e.target.id === 'startSessionModal') document.getElementById('startSessionModal').classList.remove('open');
});
</script>
</body>
</html>