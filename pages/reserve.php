<?php
// FILE: pages/reserve.php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireUser();
$db  = getDB();
$uid = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

$errors     = [];
$resSuccess = '';

$purposes = ['C# Programming','Java Programming','PHP Programming','C Programming','ASP.net Programming'];
$labRooms = ['524','526','528','530','542','Mac Laboratory'];

$remainingSessions = (int)($user['remaining_sessions'] ?? 30);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reserve') {
    $purpose  = trim($_POST['purpose_res'] ?? '');
    $labRoom  = trim($_POST['lab_room_res'] ?? '');
    $timeIn   = trim($_POST['time_in'] ?? '');
    $date     = trim($_POST['date'] ?? '');
    $pcNumber = (int)($_POST['pc_number'] ?? 0);
    if (!$purpose || !$labRoom || !$timeIn || !$date) {
        $errors[] = 'All reservation fields are required.';
    } elseif (strtotime($date) < strtotime('today')) {
        $errors[] = 'Reservation date cannot be in the past.';
    } elseif ($pcNumber < 1 || $pcNumber > 49) {
        $errors[] = 'Please select a PC from the grid.';
    } else {
        $db->prepare("INSERT INTO reservations (user_id, lab_room, date, time_slot, purpose, pc_number) VALUES (?, ?, ?, ?, ?, ?)")->execute([$uid, $labRoom, $date, $timeIn, $purpose, $pcNumber]);
        $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$uid, "Reservation for Lab {$labRoom} (PC-".str_pad($pcNumber,2,'0',STR_PAD_LEFT).") on " . date('F j, Y', strtotime($date)) . " at {$timeIn} submitted."]);
        $resSuccess = "Reservation submitted for Lab {$labRoom}, PC-".str_pad($pcNumber,2,'0',STR_PAD_LEFT)." on " . date('F j, Y', strtotime($date)) . ". You will be notified once approved.";
    }
}

$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));
$sessBadgeClass = $remainingSessions > 10 ? 'sess-ok' : ($remainingSessions > 0 ? 'sess-warn' : 'sess-empty');

$pageTitle = 'Reservation';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
?>
<style>
/* ── Reserve page ── */
.res-page-wrap { max-width: 920px; margin: 0 auto; }
.res-page-title {
  font-family: var(--font-display);
  font-size: 1.55rem; font-weight: 800; color: var(--navy);
  text-align: center; margin-bottom: 1.5rem;
}

/* Card */
.res-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(15,40,84,0.06);
  overflow: hidden;
}
.res-card-head {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 1.1rem 1.35rem;
  border-bottom: 1px solid #f1f5f9;
}
.res-card-ico {
  width: 32px; height: 32px; border-radius: 7px;
  background: rgba(15,40,84,0.05);
  display: flex; align-items: center; justify-content: center;
  color: var(--navy); font-size: 0.88rem; flex-shrink: 0; margin-top: 1px;
}
.res-card-name    { font-family: var(--font-display); font-size: 0.95rem; font-weight: 800; color: var(--navy); }
.res-card-sub     { font-size: 0.72rem; color: #94a3b8; font-weight: 300; margin-top: 1px; }
.res-card-body { padding: 1.25rem 1.35rem; }

/* Form layout */
.res-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

/* Alert boxes */
.res-alert {
  display: flex; align-items: flex-start; gap: 8px;
  padding: 0.65rem 0.875rem; border-radius: 8px;
  font-size: 0.80rem; font-weight: 500; margin-bottom: 0.875rem;
}
.res-alert i { font-size: 0.88rem; flex-shrink: 0; margin-top: 1px; }
.res-alert-info  { background: rgba(73,136,196,0.06); color: #1C4D8D; border: 1px solid rgba(73,136,196,0.18); }

/* Sessions row */
.res-sess-row {
  display: flex; align-items: center; justify-content: space-between;
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 0.5rem 0.875rem; margin-bottom: 1rem;
}
.res-sess-lbl { font-size: 0.78rem; color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 6px; }
.res-sess-lbl i { color: var(--mid); }
.res-sess-badge { font-size: 0.875rem; font-weight: 800; }
.sess-ok    { color: var(--navy); }
.sess-warn  { color: #d97706; }
.sess-empty { color: #dc2626; }

/* Form fields */
.res-field { margin-bottom: 0.875rem; }
.res-field label {
  display: block; font-size: 0.80rem; font-weight: 600;
  color: var(--navy); margin-bottom: 5px; letter-spacing: 0.1px;
}
.res-input {
  width: 100%; padding: 0.6rem 0.875rem;
  border: 1.5px solid #e2e8f0; border-radius: 8px;
  font-family: var(--font-body); font-size: 0.875rem;
  color: var(--navy); background: #fff; outline: none;
  transition: border-color 0.18s, box-shadow 0.18s; appearance: none;
}
.res-input:focus { border-color: var(--mid); box-shadow: 0 0 0 3px rgba(73,136,196,0.09); }
.res-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23475569' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.875rem center; padding-right: 2.5rem; cursor: pointer;
}

/* Submit button */
.res-btn {
  width: 100%; padding: 0.72rem;
  border: none; border-radius: 9px;
  font-family: var(--font-body); font-size: 0.875rem; font-weight: 700;
  cursor: pointer; transition: all 0.18s;
  display: flex; align-items: center; justify-content: center; gap: 7px;
  letter-spacing: 0.15px;
}
.res-btn-reserve { background: var(--navy); color: #fff; box-shadow: 0 2px 8px rgba(15,40,84,0.16); }
.res-btn-reserve:hover:not(:disabled) { background: var(--blue); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(15,40,84,0.22); }
.res-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

/* ═══ PC GRID ═══ */
.pc-grid-section {
  margin-top: 1rem; margin-bottom: 1rem;
}
.pc-grid-title {
  font-size: 0.78rem; font-weight: 700; color: var(--navy);
  margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px;
}
.pc-grid-title i { color: var(--mid); }
.pc-grid-hint {
  font-size: 0.68rem; color: #94a3b8; margin-bottom: 0.75rem;
}
.pc-grid-empty {
  text-align: center; color: #94a3b8; font-size: 0.82rem;
  padding: 2rem; background: #f8fafc; border-radius: 10px;
  border: 1px dashed #e2e8f0;
}
.pc-grid-empty i { font-size: 1.5rem; display: block; margin-bottom: 0.4rem; opacity: 0.4; }

.pc-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}
.pc-cell {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 0.5rem 0.25rem;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
  min-height: 54px;
  position: relative;
}
.pc-cell:hover:not(.pc-occupied):not(.pc-disabled) {
  border-color: var(--mid);
  background: rgba(73,136,196,0.04);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(15,40,84,0.08);
}
.pc-cell.pc-selected {
  border-color: #2563EB;
  background: rgba(37,99,235,0.08);
  box-shadow: 0 0 0 2px rgba(37,99,235,0.18);
}
.pc-cell.pc-occupied {
  background: rgba(220,38,38,0.04);
  border-color: rgba(220,38,38,0.18);
  cursor: not-allowed;
  opacity: 0.65;
}
.pc-cell.pc-disabled {
  background: #f1f5f9;
  border-color: #e2e8f0;
  cursor: not-allowed;
  opacity: 0.45;
}
.pc-cell-icon {
  font-size: 1rem;
  margin-bottom: 2px;
  color: #64748b;
}
.pc-cell.pc-selected .pc-cell-icon { color: #2563EB; }
.pc-cell.pc-occupied .pc-cell-icon { color: #dc2626; }
.pc-cell.pc-disabled .pc-cell-icon { color: #94a3b8; }
.pc-cell-num {
  font-size: 0.60rem; font-weight: 700; color: #94a3b8;
  letter-spacing: 0.3px;
}
.pc-cell.pc-selected .pc-cell-num { color: #2563EB; }

/* Legend */
.pc-legend {
  display: flex; gap: 1rem; margin-top: 0.65rem;
  flex-wrap: wrap;
}
.pc-legend-item {
  display: flex; align-items: center; gap: 5px;
  font-size: 0.65rem; color: #64748b;
}
.pc-legend-dot {
  width: 8px; height: 8px; border-radius: 50%;
}
.pc-legend-dot.avail { background: #16a34a; }
.pc-legend-dot.occupied { background: #dc2626; }
.pc-legend-dot.disabled { background: #94a3b8; }
.pc-legend-dot.selected { background: #2563EB; }

.pc-selected-label {
  display: none; align-items: center; gap: 6px;
  margin-top: 0.75rem; padding: 0.55rem 0.875rem;
  background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.18);
  border-radius: 8px; font-size: 0.80rem; font-weight: 600; color: #1e40af;
}
.pc-selected-label.show { display: flex; }
.pc-selected-label i { color: #2563EB; }

@media (max-width: 680px) {
  .res-form-grid { grid-template-columns: 1fr; }
  .pc-grid { grid-template-columns: repeat(7, 1fr); gap: 4px; }
  .pc-cell { min-height: 44px; padding: 0.35rem 0.15rem; }
  .pc-cell-icon { font-size: 0.85rem; }
  .pc-cell-num { font-size: 0.52rem; }
}
</style>
<?php require_once __DIR__ . '/../includes/user-navbar.php'; ?>

<div class="user-page">
  <div class="user-page-inner">
    <div class="res-page-wrap">

      <h1 class="res-page-title">Advance Reservation</h1>

      <?php if ($errors): ?>
        <div class="flash-msg flash-error" style="margin-bottom:1.1rem;">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>
      <?php if ($resSuccess): ?>
        <div class="flash-msg flash-success" style="margin-bottom:1.1rem;">
          <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($resSuccess) ?>
        </div>
      <?php endif; ?>

      <!-- RESERVATION CARD -->
      <div class="res-card">
        <div class="res-card-head">
          <div class="res-card-ico"><i class="bi bi-calendar-plus"></i></div>
          <div>
            <div class="res-card-name">Book a Lab Slot</div>
            <div class="res-card-sub">Reserve a specific PC in advance for your next session</div>
          </div>
        </div>
        <div class="res-card-body">

          <div class="res-alert res-alert-info">
            <i class="bi bi-info-circle-fill"></i>
            Reservations are subject to admin approval. Select a lab first, then pick an available PC from the grid.
          </div>

          <form method="POST" action="" id="reserveForm">
            <input type="hidden" name="action" value="reserve">
            <input type="hidden" name="pc_number" id="selectedPcNumber" value="">

            <div class="res-form-grid">
              <div>
                <div class="res-field">
                  <label>Preferred Date</label>
                  <input type="date" name="date" class="res-input"
                         min="<?= date('Y-m-d') ?>"
                         value="<?= htmlspecialchars($_POST['date'] ?? '') ?>"
                         required />
                </div>
                <div class="res-field">
                  <label>Preferred Time</label>
                  <input type="time" name="time_in" class="res-input"
                         value="<?= htmlspecialchars($_POST['time_in'] ?? '') ?>"
                         required />
                </div>
              </div>
              <div>
                <div class="res-field">
                  <label>Purpose</label>
                  <select name="purpose_res" class="res-input res-select" required>
                    <option value="" disabled selected>— Select Purpose —</option>
                    <?php foreach ($purposes as $p): ?>
                      <option value="<?= htmlspecialchars($p) ?>" <?= (($_POST['purpose_res'] ?? '') === $p) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="res-field">
                  <label>Laboratory Room</label>
                  <select name="lab_room_res" id="labSelect" class="res-input res-select" required>
                    <option value="" disabled selected>— Select Lab —</option>
                    <?php foreach ($labRooms as $lab): ?>
                      <option value="<?= htmlspecialchars($lab) ?>" <?= (($_POST['lab_room_res'] ?? '') === $lab) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lab) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <!-- PC Grid Section -->
            <div class="pc-grid-section" id="pcGridSection">
              <div class="pc-grid-title"><i class="bi bi-pc-display-horizontal"></i> Select a PC</div>
              <div class="pc-grid-hint">Choose a lab room first, then click an available PC below.</div>
              <div id="pcGridContainer">
                <div class="pc-grid-empty">
                  <i class="bi bi-display"></i>
                  Select a laboratory room to view available PCs
                </div>
              </div>
              <div class="pc-legend">
                <div class="pc-legend-item"><div class="pc-legend-dot avail"></div> Available</div>
                <div class="pc-legend-item"><div class="pc-legend-dot occupied"></div> Occupied</div>
                <div class="pc-legend-item"><div class="pc-legend-dot disabled"></div> Disabled</div>
                <div class="pc-legend-item"><div class="pc-legend-dot selected"></div> Selected</div>
              </div>
              <div class="pc-selected-label" id="pcSelectedLabel">
                <i class="bi bi-check-circle-fill"></i>
                <span id="pcSelectedText">—</span>
              </div>
            </div>

            <div class="res-sess-row" style="margin-bottom:1rem;">
              <span class="res-sess-lbl"><i class="bi bi-hourglass-split"></i> Sessions Remaining</span>
              <span class="res-sess-badge <?= $sessBadgeClass ?>"><?= $remainingSessions ?> / 30</span>
            </div>

            <button type="submit" class="res-btn res-btn-reserve" id="reserveBtn">
              <i class="bi bi-calendar-check"></i> Submit Reservation
            </button>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>

<script>
const labSelect     = document.getElementById('labSelect');
const pcContainer   = document.getElementById('pcGridContainer');
const pcInput       = document.getElementById('selectedPcNumber');
const pcLabel       = document.getElementById('pcSelectedLabel');
const pcLabelText   = document.getElementById('pcSelectedText');
let selectedPc      = 0;

labSelect.addEventListener('change', function() {
  const lab = this.value;
  if (!lab) return;
  pcContainer.innerHTML = '<div class="pc-grid-empty"><i class="bi bi-arrow-repeat"></i> Loading PCs...</div>';
  selectedPc = 0;
  pcInput.value = '';
  pcLabel.classList.remove('show');

  fetch('<?= $base ?>pages/api/pc-status.php?lab=' + encodeURIComponent(lab))
    .then(r => r.json())
    .then(pcs => {
      if (!pcs.length) {
        pcContainer.innerHTML = '<div class="pc-grid-empty"><i class="bi bi-display"></i> No PCs found for this lab.</div>';
        return;
      }
      let grid = '<div class="pc-grid">';
      pcs.forEach(pc => {
        const num = String(pc.pc_number).padStart(2, '0');
        let cls = '';
        let disabled = false;
        if (pc.status === 'occupied') { cls = 'pc-occupied'; disabled = true; }
        else if (pc.status === 'disabled') { cls = 'pc-disabled'; disabled = true; }
        grid += `<div class="pc-cell ${cls}" data-pc="${pc.pc_number}" ${disabled ? '' : 'onclick="selectPc('+pc.pc_number+')"'}>
                   <i class="bi bi-display pc-cell-icon"></i>
                   <span class="pc-cell-num">PC-${num}</span>
                 </div>`;
      });
      grid += '</div>';
      pcContainer.innerHTML = grid;
    })
    .catch(() => {
      pcContainer.innerHTML = '<div class="pc-grid-empty"><i class="bi bi-exclamation-triangle"></i> Failed to load PCs.</div>';
    });
});

function selectPc(num) {
  selectedPc = num;
  pcInput.value = num;
  document.querySelectorAll('.pc-cell').forEach(c => c.classList.remove('pc-selected'));
  const cell = document.querySelector(`.pc-cell[data-pc="${num}"]`);
  if (cell) cell.classList.add('pc-selected');
  pcLabelText.textContent = 'PC-' + String(num).padStart(2, '0') + ' selected';
  pcLabel.classList.add('show');
}

document.getElementById('reserveForm').addEventListener('submit', function(e) {
  if (!pcInput.value) {
    e.preventDefault();
    alert('Please select a PC from the grid before submitting.');
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>