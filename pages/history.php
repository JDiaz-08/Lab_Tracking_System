<?php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();
$db  = getDB();
$uid = $_SESSION['user_id'];

if (isset($_GET['logout_id'])) {
    $lid = (int)$_GET['logout_id'];
    $db->prepare("UPDATE sit_in_logs SET logout_time=CURRENT_TIMESTAMP, status='done' WHERE id=? AND user_id=? AND logout_time IS NULL")
       ->execute([$lid, $uid]);
    header('Location: history.php'); exit;
}

$sitStmt = $db->prepare("
    SELECT u.student_id,
           u.first_name||' '||COALESCE(u.middle_name||' ','')||u.last_name AS full_name,
           s.purpose, s.lab_room, s.login_time, s.logout_time,
           DATE(s.login_time) AS log_date, s.id
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    WHERE s.user_id = ?
    ORDER BY s.login_time DESC
");
$sitStmt->execute([$uid]);
$logs = $sitStmt->fetchAll();

$pageTitle = 'History';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/history.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

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
              <th class="sortable" data-col="6">Date <span class="sort-icon">⇅</span></th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="histBody">
            <?php if (empty($logs)): ?>
              <tr class="hist-empty-row"><td colspan="8">No sit-in records found.</td></tr>
            <?php else: ?>
              <?php foreach ($logs as $row): ?>
                <tr class="hist-row">
                  <td><?= htmlspecialchars($row['student_id']) ?></td>
                  <td><?= htmlspecialchars($row['full_name']) ?></td>
                  <td><?= htmlspecialchars($row['purpose'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($row['lab_room']) ?></td>
                  <td><?= $row['login_time']  ? date('g:i A', strtotime($row['login_time']))  : '—' ?></td>
                  <td><?= $row['logout_time'] ? date('g:i A', strtotime($row['logout_time'])) : '<span class="hist-active">Active</span>' ?></td>
                  <td><?= date('m/d/Y', strtotime($row['log_date'])) ?></td>
                  <td>
                    <?php if (!$row['logout_time']): ?>
                      <a href="?logout_id=<?= (int)$row['id'] ?>" class="hist-action-btn"
                         onclick="return confirm('Log out of this session?')">
                        <i class="bi bi-door-open"></i> Logout
                      </a>
                    <?php else: ?>
                      <span style="font-size:0.75rem; color:#94a3b8;">Completed</span>
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

<script>
const allRows     = Array.from(document.querySelectorAll('#histBody .hist-row'));
const histInfo    = document.getElementById('histInfo');
const histPag     = document.getElementById('histPagination');
const searchInput = document.getElementById('histSearch');
const entriesSel  = document.getElementById('entriesSelect');
let currentPage=1, perPage=10, filtered=[...allRows], sortCol=-1, sortDir=1;

function cellText(row,col){ return (row.cells[col]?.textContent||'').trim().toLowerCase(); }

function applySearch(term){
  const q=term.toLowerCase().trim();
  filtered=allRows.filter(row=>{ if(!q) return true; for(let i=0;i<row.cells.length-1;i++) if(cellText(row,i).includes(q)) return true; return false; });
  currentPage=1; render();
}

function render(){
  const total=filtered.length, start=(currentPage-1)*perPage, end=Math.min(start+perPage,total);
  allRows.forEach(r=>r.style.display='none');
  filtered.slice(start,end).forEach(r=>r.style.display='');
  const emptyRow=document.querySelector('.hist-empty-row');
  if(emptyRow) emptyRow.style.display=total===0?'':'none';
  histInfo.textContent=total===0?'Showing 0 to 0 of 0 entries':`Showing ${start+1} to ${end} of ${total} entr${total===1?'y':'ies'}`;
  renderPag(total);
}

function renderPag(total){
  const pages=Math.max(1,Math.ceil(total/perPage));
  histPag.innerHTML='';
  const mk=(label,p,disabled,active)=>{
    const b=document.createElement('button');
    b.textContent=label;
    b.className='hist-pg-btn'+(active?' active':'')+(disabled?' disabled':'');
    b.disabled=disabled;
    b.onclick=()=>{currentPage=p;render();};
    return b;
  };
  histPag.appendChild(mk('«',1,currentPage===1,false));
  histPag.appendChild(mk('‹',currentPage-1,currentPage===1,false));
  let s=Math.max(1,currentPage-2),e=Math.min(pages,s+4);
  if(e-s<4) s=Math.max(1,e-4);
  for(let p=s;p<=e;p++) histPag.appendChild(mk(p,p,false,p===currentPage));
  histPag.appendChild(mk('›',currentPage+1,currentPage===pages,false));
  histPag.appendChild(mk('»',pages,currentPage===pages,false));
}

document.querySelectorAll('.sortable').forEach(th=>{
  th.addEventListener('click',()=>{
    const col=parseInt(th.dataset.col);
    sortDir=sortCol===col?-sortDir:1; sortCol=col;
    document.querySelectorAll('.sortable').forEach(h=>h.classList.remove('asc','desc'));
    th.classList.add(sortDir===1?'asc':'desc');
    filtered.sort((a,b)=>{ const av=cellText(a,col),bv=cellText(b,col); return av<bv?-sortDir:av>bv?sortDir:0; });
    currentPage=1; render();
  });
});

searchInput.addEventListener('input',()=>applySearch(searchInput.value));
entriesSel.addEventListener('change',()=>{perPage=parseInt(entriesSel.value);currentPage=1;render();});
render();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>