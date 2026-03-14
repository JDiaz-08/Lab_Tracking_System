<?php
session_start();
$base = '../';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireUser();
$db  = getDB();
$uid = $_SESSION['user_id'];

// Fetch sit-in logs joined with user info (already scoped to current user)
$sitIns = $db->prepare("
    SELECT
        u.student_id,
        u.first_name || ' ' || COALESCE(u.middle_name || ' ', '') || u.last_name AS full_name,
        s.purpose,
        s.lab_room,
        s.login_time,
        s.logout_time,
        DATE(s.login_time) AS log_date,
        s.id
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    WHERE s.user_id = ?
    ORDER BY s.login_time DESC
");
$sitIns->execute([$uid]);
$logs = $sitIns->fetchAll();

$pageTitle = 'History';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/history.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<div class="user-page">
  <div class="user-page-inner">

    <h1 class="hist-title">History Information</h1>

    <div class="hist-card">

      <!-- Table Controls -->
      <div class="hist-controls">
        <div class="hist-entries">
          <label>
            <select id="entriesSelect" class="hist-entries-select">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </label>
          <span>entries per page</span>
        </div>
        <div class="hist-search">
          <label>Search:</label>
          <input type="text" id="histSearch" class="hist-search-input"
                 placeholder="Search records..." />
        </div>
      </div>

      <!-- Table -->
      <div class="hist-table-wrap">
        <table class="hist-table" id="histTable">
          <thead>
            <tr>
              <th class="sortable" data-col="0">ID Number <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="1">Name <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="2">Sit Purpose <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="3">Laboratory <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="4">Login <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="5">Logout <span class="sort-icon">⇅</span></th>
              <th class="sortable" data-col="6">Date <span class="sort-icon">⇅</span></th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="histBody">
            <?php if (empty($logs)): ?>
              <tr class="hist-empty-row">
                <td colspan="8">No data available</td>
              </tr>
            <?php else: ?>
              <?php foreach ($logs as $row): ?>
                <tr class="hist-row">
                  <td><?= htmlspecialchars($row['student_id']) ?></td>
                  <td><?= htmlspecialchars($row['full_name']) ?></td>
                  <td><?= htmlspecialchars($row['purpose'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($row['lab_room']) ?></td>
                  <td><?= $row['login_time']
                          ? date('g:i A', strtotime($row['login_time']))
                          : '—' ?></td>
                  <td><?= $row['logout_time']
                          ? date('g:i A', strtotime($row['logout_time']))
                          : '<span class="hist-active">Active</span>' ?></td>
                  <td><?= date('m/d/Y', strtotime($row['log_date'])) ?></td>
                  <td>
                    <a href="?logout_id=<?= (int)$row['id'] ?>"
                       class="hist-action-btn"
                       onclick="return confirm('Log out of this session?')">
                      <?= $row['logout_time'] ? '🗑️' : '🚪 Logout' ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Table Footer -->
      <div class="hist-footer">
        <div class="hist-info" id="histInfo">Showing 0 to 0 of 0 entries</div>
        <div class="hist-pagination" id="histPagination"></div>
      </div>

    </div>
  </div>
</div>

<?php
// Handle session logout action
if (isset($_GET['logout_id'])) {
    $lid = (int) $_GET['logout_id'];
    $db->prepare(
        "UPDATE sit_in_logs SET logout_time = CURRENT_TIMESTAMP
         WHERE id = ? AND user_id = ? AND logout_time IS NULL"
    )->execute([$lid, $uid]);
    header('Location: history.php');
    exit;
}
?>

<script>
// ===========================
//  DATATABLE-STYLE CONTROLLER
// ===========================
const allRows     = Array.from(document.querySelectorAll('#histBody .hist-row'));
const histInfo    = document.getElementById('histInfo');
const histPag     = document.getElementById('histPagination');
const searchInput = document.getElementById('histSearch');
const entriesSel  = document.getElementById('entriesSelect');

let currentPage = 1;
let perPage     = 10;
let filtered    = [...allRows];

function getCellText(row, col) {
  return (row.cells[col]?.textContent || '').trim().toLowerCase();
}

function applySearch(term) {
  const q = term.toLowerCase().trim();
  filtered = allRows.filter(row => {
    if (!q) return true;
    for (let i = 0; i < row.cells.length - 1; i++) {
      if (getCellText(row, i).includes(q)) return true;
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

  // Hide all rows first
  allRows.forEach(r => r.style.display = 'none');

  // Show only current page rows
  filtered.slice(start, end).forEach(r => r.style.display = '');

  // Update info
  if (total === 0) {
    histInfo.textContent = 'Showing 0 to 0 of 0 entries';
  } else {
    histInfo.textContent = `Showing ${start + 1} to ${end} of ${total} entr${total === 1 ? 'y' : 'ies'}`;
  }

  // Show "No data" row if empty
  const emptyRow = document.querySelector('.hist-empty-row');
  if (emptyRow) emptyRow.style.display = total === 0 ? '' : 'none';

  renderPagination(total);
}

function renderPagination(total) {
  const pages = Math.max(1, Math.ceil(total / perPage));
  histPag.innerHTML = '';

  const make = (label, page, disabled, active) => {
    const btn = document.createElement('button');
    btn.textContent = label;
    btn.className   = 'hist-pg-btn' + (active ? ' active' : '') + (disabled ? ' disabled' : '');
    btn.disabled    = disabled;
    btn.onclick     = () => { currentPage = page; render(); };
    return btn;
  };

  histPag.appendChild(make('«', 1,           currentPage === 1,     false));
  histPag.appendChild(make('‹', currentPage - 1, currentPage === 1, false));

  // Page numbers (window of 5)
  let s = Math.max(1, currentPage - 2);
  let e = Math.min(pages, s + 4);
  if (e - s < 4) s = Math.max(1, e - 4);
  for (let p = s; p <= e; p++) {
    histPag.appendChild(make(p, p, false, p === currentPage));
  }

  histPag.appendChild(make('›', currentPage + 1, currentPage === pages, false));
  histPag.appendChild(make('»', pages,           currentPage === pages, false));
}

// Sorting
let sortCol = -1, sortDir = 1;
document.querySelectorAll('.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const col = parseInt(th.dataset.col);
    sortDir = (sortCol === col) ? -sortDir : 1;
    sortCol = col;

    document.querySelectorAll('.sortable').forEach(h => h.classList.remove('asc','desc'));
    th.classList.add(sortDir === 1 ? 'asc' : 'desc');

    filtered.sort((a, b) => {
      const av = getCellText(a, col);
      const bv = getCellText(b, col);
      return av < bv ? -sortDir : av > bv ? sortDir : 0;
    });
    currentPage = 1;
    render();
  });
});

// Search
searchInput.addEventListener('input', () => applySearch(searchInput.value));

// Entries per page
entriesSel.addEventListener('change', () => {
  perPage = parseInt(entriesSel.value);
  currentPage = 1;
  render();
});

// Initial render
render();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>