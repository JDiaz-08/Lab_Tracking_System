<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

/* ── Fetch all records with feedback (LEFT JOIN) ── */
$records = $db->query("
    SELECT s.id, u.student_id,
           u.first_name || ' ' || COALESCE(u.middle_name || ' ', '') || u.last_name AS full_name,
           s.purpose, s.lab_room,
           s.login_time, s.logout_time,
           DATE(s.login_time) AS log_date, s.status,
           f.id      AS feedback_id,
           f.message AS feedback_msg,
           f.rating  AS feedback_rating
    FROM sit_in_logs s
    JOIN  users    u ON u.id = s.user_id
    LEFT JOIN feedback f ON f.sit_in_id = s.id AND f.user_id = u.id
    ORDER BY s.login_time DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sit-in Records — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    /* ── Feedback table-cell buttons ── */
    .fb-view-btn {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 0.26rem 0.68rem;
      background: rgba(22,163,74,0.08); color: #15803d;
      border: 1px solid rgba(22,163,74,0.22); border-radius: 5px;
      font-size: 0.74rem; font-weight: 600; cursor: pointer;
      transition: all 0.15s; white-space: nowrap;
    }
    .fb-view-btn:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
    .fb-none { font-size: 0.74rem; color: #94a3b8; }

    /* ── Feedback modal extras ── */
    .fb-stars-lbl {
      font-size: 0.75rem; font-weight: 700; color: #475569;
      margin-bottom: 0.4rem; display: block;
      letter-spacing: 0.5px; text-transform: uppercase;
    }
    .fb-view-stars { font-size: 1.5rem; letter-spacing: 3px; margin-bottom: 1rem; }
    .fb-view-stars .son  { color: #d97706; }
    .fb-view-stars .soff { color: #e2e8f0; }

    .fb-msg-box {
      font-size: 0.875rem; color: #1e293b; line-height: 1.75;
      padding: 0.9rem 1rem; background: #f8fafc;
      border-radius: 8px; border: 1px solid #e2e8f0;
      white-space: pre-wrap; word-break: break-word;
    }
    .fb-meta-row {
      display: flex; align-items: center; gap: 6px;
      font-size: 0.72rem; color: #94a3b8; margin-top: 0.6rem;
    }
    .fb-meta-row i { font-size: 0.72rem; }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">View Sit-in Records</h1>

    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-table"></i> All Sit-in Records
      </div>
      <div class="a-card-body">

        <div class="a-table-controls">
          <div class="a-entries-wrap">
            <select id="recSelect" class="a-entries-select">
              <option>10</option><option>25</option><option>50</option>
            </select>
            <span>entries per page</span>
          </div>
          <div class="a-search-wrap">
            <label>Search:</label>
            <input type="text" id="recSearch" class="a-search-box" placeholder="Search..."/>
          </div>
        </div>

        <div class="a-table-wrap">
          <table class="a-table" id="recTable">
            <thead>
              <tr>
                <th class="a-sortable" data-col="0">ID Number <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">Name <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Purpose <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Laboratory <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Login <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="5">Logout <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="6">Date <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="7">Status <span class="a-sort-icon">⇅</span></th>
                <th>Feedback</th>
              </tr>
            </thead>
            <tbody id="recBody">
              <?php if (empty($records)): ?>
                <tr class="a-table-empty"><td colspan="9">No records found.</td></tr>
              <?php else: ?>
                <?php foreach ($records as $r): ?>
                  <tr class="a-data-row">
                    <td><?= htmlspecialchars($r['student_id']) ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['purpose'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['lab_room']) ?></td>
                    <td><?= $r['login_time']  ? date('g:i A', strtotime($r['login_time']))  : '—' ?></td>
                    <td><?= $r['logout_time'] ? date('g:i A', strtotime($r['logout_time'])) : '—' ?></td>
                    <td><?= date('m/d/Y', strtotime($r['log_date'])) ?></td>
                    <td>
                      <?php $st = $r['logout_time'] ? 'done' : 'active'; ?>
                      <span class="a-badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
                    </td>
                    <!-- ── Feedback cell ── -->
                    <td>
                      <?php if ($r['feedback_id']): ?>
                        <button class="fb-view-btn"
                                onclick="openAdminFb(this)"
                                data-msg="<?= htmlspecialchars($r['feedback_msg'], ENT_QUOTES) ?>"
                                data-rating="<?= (int)$r['feedback_rating'] ?>"
                                data-name="<?= htmlspecialchars($r['full_name'], ENT_QUOTES) ?>">
                          <i class="bi bi-eye"></i> View
                        </button>
                      <?php else: ?>
                        <span class="fb-none">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="a-table-footer">
          <div class="a-table-info" id="recInfo"></div>
          <div class="a-pagination"  id="recPag"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     VIEW FEEDBACK MODAL (admin — read-only)
══════════════════════════════════════ -->
<div class="a-modal-overlay" id="fbViewModal">
  <div class="a-modal">
    <div class="a-modal-header">
      <span>
        <i class="bi bi-chat-square-text"
           style="margin-right:6px;color:#16a34a;font-size:0.88rem;"></i>
        Student Feedback
      </span>
      <button class="a-modal-close"
              onclick="document.getElementById('fbViewModal').classList.remove('open')">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>

    <div class="a-modal-body">
      <!-- Student name chip -->
      <div id="fbStudentChip"
           style="font-size:0.80rem;font-weight:700;color:#1e293b;
                  margin-bottom:1rem;display:flex;align-items:center;gap:7px;">
        <i class="bi bi-person-circle" style="color:#4988C4;font-size:1rem;"></i>
        <span id="fbStudentName"></span>
      </div>

      <!-- Stars -->
      <span class="fb-stars-lbl">Rating</span>
      <div class="fb-view-stars" id="adminFbStars"></div>

      <!-- Message -->
      <span class="fb-stars-lbl">Feedback</span>
      <div class="fb-msg-box" id="adminFbMsg"></div>
    </div>

    <div class="a-modal-footer">
      <button type="button" class="a-btn a-btn-gray"
              onclick="document.getElementById('fbViewModal').classList.remove('open')">
        Close
      </button>
    </div>
  </div>
</div>

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({
  tableId: 'recTable', bodyId: 'recBody', infoId: 'recInfo',
  pagId:   'recPag',   searchId: 'recSearch', selectId: 'recSelect'
});

/* ── View feedback modal ── */
function openAdminFb(btn) {
  const msg    = btn.dataset.msg;
  const rating = parseInt(btn.dataset.rating) || 0;
  const name   = btn.dataset.name;

  let starsHtml = '';
  for (let i = 1; i <= 5; i++) {
    starsHtml += `<span class="${i <= rating ? 'son' : 'soff'}">★</span>`;
  }
  document.getElementById('adminFbStars').innerHTML  = starsHtml;
  document.getElementById('adminFbMsg').textContent  = msg;
  document.getElementById('fbStudentName').textContent = name;
  document.getElementById('fbViewModal').classList.add('open');
}

/* Close on backdrop click */
document.getElementById('fbViewModal')?.addEventListener('click', e => {
  if (e.target.id === 'fbViewModal') e.target.classList.remove('open');
});
</script>
</body>
</html>