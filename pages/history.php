<?php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();
$db  = getDB();
$uid = $_SESSION['user_id'];

/* ── Handle feedback submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_feedback') {
    $sitId   = (int)($_POST['sit_in_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));

    if ($sitId && $message) {
        /* Verify this sit-in belongs to the user and is completed */
        $chk = $db->prepare(
            "SELECT id FROM sit_in_logs WHERE id = ? AND user_id = ? AND logout_time IS NOT NULL"
        );
        $chk->execute([$sitId, $uid]);
        if ($chk->fetch()) {
            /* No duplicate feedback per session */
            $dup = $db->prepare("SELECT id FROM feedback WHERE sit_in_id = ? AND user_id = ?");
            $dup->execute([$sitId, $uid]);
            if (!$dup->fetch()) {
                $db->prepare(
                    "INSERT INTO feedback (user_id, sit_in_id, message, rating) VALUES (?, ?, ?, ?)"
                )->execute([$uid, $sitId, $message, $rating]);
            }
        }
    }
    header('Location: history.php');
    exit;
}

/* ── Fetch logs with feedback (LEFT JOIN) ── */
$sitStmt = $db->prepare("
    SELECT u.student_id,
           u.first_name || ' ' || COALESCE(u.middle_name || ' ', '') || u.last_name AS full_name,
           s.purpose, s.lab_room, s.login_time, s.logout_time,
           DATE(s.login_time) AS log_date, s.id, s.pc_number,
           f.id      AS feedback_id,
           f.message AS feedback_msg,
           f.rating  AS feedback_rating
    FROM sit_in_logs s
    JOIN  users    u ON u.id = s.user_id
    LEFT JOIN feedback f ON f.sit_in_id = s.id AND f.user_id = s.user_id
    WHERE s.user_id = ?
    ORDER BY s.login_time DESC
");
$sitStmt->execute([$uid]);
$logs = $sitStmt->fetchAll();

$pageTitle = 'History';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css?v=' . filemtime(__DIR__ . '/../assets/css/user.css') . '">';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/history.css?v=' . filemtime(__DIR__ . '/../assets/css/history.css') . '">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<style>
/* ── Feedback modal overlay ── */
.fb-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(0,0,0,0.44); z-index: 2100;
  align-items: center; justify-content: center;
  backdrop-filter: blur(3px);
}
.fb-overlay.open { display: flex; }

.fb-modal {
  background: #fff; border-radius: 14px;
  width: 100%; max-width: 440px; margin: 1rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18);
  animation: fbIn 0.18s ease; overflow: hidden;
  border: 1px solid #e2e8f0;
}
@keyframes fbIn {
  from { opacity:0; transform:scale(0.95) translateY(-10px); }
  to   { opacity:1; transform:scale(1)    translateY(0); }
}

.fb-modal-hd {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.95rem 1.25rem;
  font-size: 0.875rem; font-weight: 700; color: #1e293b;
  border-bottom: 1px solid #e2e8f0; background: #f8fafc;
}
.fb-modal-hd i { font-size: 0.9rem; margin-right: 6px; }
.fb-close-btn {
  background: none; border: 1px solid #e2e8f0; border-radius: 5px;
  width: 26px; height: 26px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #64748b; font-size: 0.8rem; transition: background 0.14s;
}
.fb-close-btn:hover { background: #e2e8f0; }

.fb-modal-body { padding: 1.2rem 1.25rem; }

.fb-modal-ft {
  padding: 0.8rem 1.25rem; border-top: 1px solid #e2e8f0;
  display: flex; justify-content: flex-end; gap: 0.5rem;
  background: #f8fafc;
}

/* ── Star rating (interactive) ── */
.fb-stars-label {
  font-size: 0.78rem; font-weight: 700; color: #1e293b;
  margin-bottom: 0.45rem; display: block;
}
.fb-stars-row { display: flex; gap: 5px; margin-bottom: 1rem; }
.fb-star {
  font-size: 1.9rem; cursor: pointer;
  color: #e2e8f0; transition: color 0.12s, transform 0.1s;
  user-select: none; line-height: 1;
}
.fb-star.lit        { color: #d97706; }
.fb-star:hover      { transform: scale(1.15); }

/* ── Star rating (read-only view) ── */
.fb-view-stars { font-size: 1.5rem; letter-spacing: 3px; margin-bottom: 1rem; }
.fb-view-stars .son  { color: #d97706; }
.fb-view-stars .soff { color: #e2e8f0; }

/* ── Textarea & label ── */
.fb-field-lbl {
  display: block; font-size: 0.80rem; font-weight: 700;
  color: #1e293b; margin-bottom: 5px;
}
.fb-field-lbl .req { color: #dc2626; }
.fb-textarea {
  width: 100%; padding: 0.65rem 0.875rem;
  border: 1.5px solid #e2e8f0; border-radius: 8px;
  font-family: var(--font-body, 'Outfit', sans-serif);
  font-size: 0.875rem; resize: vertical; min-height: 105px;
  outline: none; color: #1e293b; transition: border-color 0.18s;
}
.fb-textarea:focus { border-color: #4988C4; box-shadow: 0 0 0 3px rgba(73,136,196,0.10); }

.fb-char-hint { font-size: 0.68rem; color: #94a3b8; text-align: right; margin-top: 3px; }

/* ── Read-only message box ── */
.fb-view-msg {
  font-size: 0.875rem; color: #1e293b; line-height: 1.75;
  padding: 0.875rem 1rem; background: #f8fafc;
  border-radius: 8px; border: 1px solid #e2e8f0;
}

/* ── Buttons ── */
.fb-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0.45rem 1.05rem; border: none; border-radius: 7px;
  font-family: inherit; font-size: 0.81rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; white-space: nowrap;
}
.fb-btn-primary { background: #2563EB; color: #fff; }
.fb-btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
.fb-btn-gray { background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0; }
.fb-btn-gray:hover { background: #e2e8f0; }

/* ── Table cell buttons ── */
.fb-add-btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 0.26rem 0.65rem;
  background: rgba(37,99,235,0.08); color: #2563EB;
  border: 1px solid rgba(37,99,235,0.22); border-radius: 5px;
  font-size: 0.74rem; font-weight: 600; cursor: pointer;
  transition: all 0.15s; white-space: nowrap;
}
.fb-add-btn:hover { background: #2563EB; color: #fff; border-color: #2563EB; }

.fb-view-btn {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 0.26rem 0.65rem;
  background: rgba(22,163,74,0.08); color: #15803d;
  border: 1px solid rgba(22,163,74,0.22); border-radius: 5px;
  font-size: 0.74rem; font-weight: 600; cursor: pointer;
  transition: all 0.15s; white-space: nowrap;
}
.fb-view-btn:hover { background: #16a34a; color: #fff; border-color: #16a34a; }

.fb-na { font-size: 0.74rem; color: #94a3b8; }
</style>

<div class="user-page">
  <div class="user-page-inner">
    <h1 class="hist-title">History</h1>

    <div class="hist-card">
      <div class="hist-controls">
        <div class="hist-entries">
          <select id="entriesSelect" class="hist-entries-select">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span>entries per page</span>
        </div>
        <div class="hist-search">
          <label>Search:</label>
          <input type="text" id="histSearch" class="hist-search-input" placeholder="Search records..." />
        </div>
      </div>

      <div class="hist-table-wrap">
        <table class="hist-table" id="histTable">
          <thead>
            <tr>
              <th class="sortable" data-col="0">ID Number <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="1">Name <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="2">Purpose <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="3">Laboratory <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="4">Login <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="5">Logout <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="6">Duration <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="7">Date <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="8">PC <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="9">Status <span class="sort-icon">⇅</span></th>
              <th>Feedback</th>
            </tr>
          </thead>
          <tbody id="histBody">
            <?php if (empty($logs)): ?>
              <tr class="hist-empty-row"><td colspan="11">No sit-in records found.</td></tr>
            <?php else: ?>
              <?php foreach ($logs as $row):
                $isActive   = empty($row['logout_time']);
                $statusText = $isActive ? 'Active' : 'Completed';
              ?>
                <tr class="hist-row">
                  <td><?= htmlspecialchars($row['student_id']) ?></td>
                  <td><?= htmlspecialchars($row['full_name']) ?></td>
                  <td><?= htmlspecialchars($row['purpose'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($row['lab_room']) ?></td>
                  <td><?= $row['login_time']  ? date('g:i A', strtotime($row['login_time']))  : '—' ?></td>
                  <td><?= $row['logout_time'] ? date('g:i A', strtotime($row['logout_time'])) : '—' ?></td>
                  <td>
                    <?php if (!$isActive && $row['logout_time']): 
                      $durMin = round((strtotime($row['logout_time']) - strtotime($row['login_time'])) / 60);
                      echo $durMin >= 60 ? floor($durMin/60).'h '.($durMin%60).'m' : $durMin.'m';
                    else: ?>
                      —
                    <?php endif; ?>
                  </td>
                  <td><?= date('m/d/Y', strtotime($row['log_date'])) ?></td>
                  <td><?= $row['pc_number'] ? 'PC-'.str_pad($row['pc_number'],2,'0',STR_PAD_LEFT) : '—' ?></td>

                  <!-- Status column (display only — no logout button) -->
                  <td>
                    <?php if ($isActive): ?>
                      <span class="hist-active">Active</span>
                    <?php else: ?>
                      <span style="font-size:0.75rem; color:#64748b; font-weight:500;">Completed</span>
                    <?php endif; ?>
                  </td>

                  <!-- Feedback column -->
                  <td>
                    <?php if ($isActive): ?>
                      <span class="fb-na" title="Session still active">—</span>
                    <?php elseif ($row['feedback_id']): ?>
                      <button class="fb-view-btn"
                              onclick="openViewFb(this)"
                              data-msg="<?= htmlspecialchars($row['feedback_msg'], ENT_QUOTES) ?>"
                              data-rating="<?= (int)$row['feedback_rating'] ?>">
                        <i class="bi bi-eye"></i> View
                      </button>
                    <?php else: ?>
                      <button class="fb-add-btn"
                              onclick="openAddFb(<?= (int)$row['id'] ?>)">
                        <i class="bi bi-plus-circle"></i> Add
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="hist-footer">
        <div class="hist-info" id="histInfo">Showing 0 to 0 of 0 entries</div>
        <div class="hist-pagination" id="histPagination"></div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     ADD FEEDBACK MODAL
══════════════════════════════════════ -->
<div class="fb-overlay" id="addFbOverlay">
  <div class="fb-modal">
    <div class="fb-modal-hd">
      <span><i class="bi bi-chat-square-text" style="color:#2563EB;"></i>Add Feedback</span>
      <button class="fb-close-btn" onclick="closeFbModal('addFbOverlay')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <form method="POST" action="">
      <input type="hidden" name="action"     value="add_feedback">
      <input type="hidden" name="sit_in_id"  id="fbSitInId">
      <input type="hidden" name="rating"     id="fbRatingInput" value="5">

      <div class="fb-modal-body">

        <!-- Star rating -->
        <span class="fb-stars-label">Your Rating</span>
        <div class="fb-stars-row" id="fbStarRow">
          <span class="fb-star lit" data-val="1">★</span>
          <span class="fb-star lit" data-val="2">★</span>
          <span class="fb-star lit" data-val="3">★</span>
          <span class="fb-star lit" data-val="4">★</span>
          <span class="fb-star lit" data-val="5">★</span>
        </div>

        <!-- Message -->
        <label class="fb-field-lbl" for="fbMessage">
          Your Feedback <span class="req">*</span>
        </label>
        <textarea
          class="fb-textarea"
          name="message"
          id="fbMessage"
          maxlength="500"
          placeholder="Share your experience — lab equipment, environment, staff, or anything relevant..."
          required
        ></textarea>
        <div class="fb-char-hint"><span id="fbCharCount">0</span> / 500</div>

      </div>

      <div class="fb-modal-ft">
        <button type="button" class="fb-btn fb-btn-gray"
                onclick="closeFbModal('addFbOverlay')">Cancel</button>
        <button type="submit" class="fb-btn fb-btn-primary">
          <i class="bi bi-send"></i> Submit Feedback
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════
     VIEW FEEDBACK MODAL
══════════════════════════════════════ -->
<div class="fb-overlay" id="viewFbOverlay">
  <div class="fb-modal">
    <div class="fb-modal-hd">
      <span><i class="bi bi-chat-square-text" style="color:#16a34a;"></i>Your Feedback</span>
      <button class="fb-close-btn" onclick="closeFbModal('viewFbOverlay')">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="fb-modal-body">
      <span class="fb-stars-label">Rating</span>
      <div class="fb-view-stars" id="viewFbStars"></div>
      <label class="fb-field-lbl">Feedback</label>
      <div class="fb-view-msg" id="viewFbMsg"></div>
    </div>

    <div class="fb-modal-ft">
      <button type="button" class="fb-btn fb-btn-gray"
              onclick="closeFbModal('viewFbOverlay')">Close</button>
    </div>
  </div>
</div>

<script>
/* ══════════════════════
   TABLE: pagination / sort / search
══════════════════════ */
const allRows     = Array.from(document.querySelectorAll('#histBody .hist-row'));
const histInfo    = document.getElementById('histInfo');
const histPag     = document.getElementById('histPagination');
const searchInput = document.getElementById('histSearch');
const entriesSel  = document.getElementById('entriesSelect');
let currentPage = 1, perPage = 10, filtered = [...allRows], sortCol = -1, sortDir = 1;

function cellText(row, col) {
  return (row.cells[col]?.textContent || '').trim().toLowerCase();
}

function applySearch(term) {
  const q = term.toLowerCase().trim();
  filtered = allRows.filter(row => {
    if (!q) return true;
    // Search columns 0-9 (skip Feedback cell)
    for (let i = 0; i <= 9; i++) {
      if (cellText(row, i).includes(q)) return true;
    }
    return false;
  });
  currentPage = 1;
  render();
}

function render() {
  const total = filtered.length;
  const start = (currentPage - 1) * perPage;
  const end   = Math.min(start + perPage, total);
  allRows.forEach(r => r.style.display = 'none');
  filtered.slice(start, end).forEach(r => r.style.display = '');
  const emptyRow = document.querySelector('.hist-empty-row');
  if (emptyRow) emptyRow.style.display = total === 0 ? '' : 'none';
  histInfo.textContent = total === 0
    ? 'Showing 0 to 0 of 0 entries'
    : `Showing ${start + 1} to ${end} of ${total} entr${total === 1 ? 'y' : 'ies'}`;
  renderPag(total);
}

function renderPag(total) {
  const pages = Math.max(1, Math.ceil(total / perPage));
  histPag.innerHTML = '';
  const mk = (label, p, disabled, active) => {
    const b = document.createElement('button');
    b.textContent = label;
    b.className   = 'hist-pg-btn' + (active ? ' active' : '') + (disabled ? ' disabled' : '');
    b.disabled    = disabled;
    b.onclick     = () => { currentPage = p; render(); };
    return b;
  };
  histPag.appendChild(mk('«', 1, currentPage === 1, false));
  histPag.appendChild(mk('‹', currentPage - 1, currentPage === 1, false));
  let s = Math.max(1, currentPage - 2), e = Math.min(pages, s + 4);
  if (e - s < 4) s = Math.max(1, e - 4);
  for (let p = s; p <= e; p++) histPag.appendChild(mk(p, p, false, p === currentPage));
  histPag.appendChild(mk('›', currentPage + 1, currentPage === pages, false));
  histPag.appendChild(mk('»', pages, currentPage === pages, false));
}

document.querySelectorAll('.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const col = parseInt(th.dataset.col);
    sortDir  = sortCol === col ? -sortDir : 1;
    sortCol  = col;
    document.querySelectorAll('.sortable').forEach(h => h.classList.remove('asc', 'desc'));
    th.classList.add(sortDir === 1 ? 'asc' : 'desc');
    filtered.sort((a, b) => {
      const av = cellText(a, col), bv = cellText(b, col);
      return av < bv ? -sortDir : av > bv ? sortDir : 0;
    });
    currentPage = 1;
    render();
  });
});

searchInput.addEventListener('input', () => applySearch(searchInput.value));
entriesSel.addEventListener('change', () => { perPage = parseInt(entriesSel.value); currentPage = 1; render(); });
render();

/* ══════════════════════
   MODAL HELPERS
══════════════════════ */
function openModal(id)    { document.getElementById(id)?.classList.add('open'); }
function closeFbModal(id) { document.getElementById(id)?.classList.remove('open'); }

/* Close on backdrop click */
['addFbOverlay', 'viewFbOverlay'].forEach(id => {
  document.getElementById(id)?.addEventListener('click', e => {
    if (e.target.id === id) closeFbModal(id);
  });
});

/* ══════════════════════
   ADD FEEDBACK MODAL
══════════════════════ */
let currentRating = 5;

function openAddFb(sitInId) {
  document.getElementById('fbSitInId').value          = sitInId;
  document.getElementById('fbMessage').value           = '';
  document.getElementById('fbCharCount').textContent   = '0';
  currentRating = 5;
  document.getElementById('fbRatingInput').value       = 5;
  renderStars(5);
  openModal('addFbOverlay');
  setTimeout(() => document.getElementById('fbMessage')?.focus(), 120);
}

const stars = document.querySelectorAll('.fb-star');

stars.forEach(star => {
  star.addEventListener('click', () => {
    currentRating = parseInt(star.dataset.val);
    document.getElementById('fbRatingInput').value = currentRating;
    renderStars(currentRating);
  });
  star.addEventListener('mouseover', () => renderStars(parseInt(star.dataset.val)));
  star.addEventListener('mouseout',  () => renderStars(currentRating));
});

function renderStars(rating) {
  stars.forEach(s => s.classList.toggle('lit', parseInt(s.dataset.val) <= rating));
}

document.getElementById('fbMessage')?.addEventListener('input', function () {
  document.getElementById('fbCharCount').textContent = this.value.length;
});

/* ══════════════════════
   VIEW FEEDBACK MODAL
══════════════════════ */
function openViewFb(btn) {
  const msg    = btn.dataset.msg;
  const rating = parseInt(btn.dataset.rating) || 0;

  let html = '';
  for (let i = 1; i <= 5; i++) {
    html += `<span class="${i <= rating ? 'son' : 'soff'}">★</span>`;
  }
  document.getElementById('viewFbStars').innerHTML = html;
  document.getElementById('viewFbMsg').textContent  = msg;
  openModal('viewFbOverlay');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>