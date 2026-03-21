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
$sitSuccess = '';
$resSuccess = '';

$purposes = ['C# Programming','Java Programming','PHP Programming','C Programming','ASP.net Programming'];
$labRooms = ['524','526','528','530','542','Mac Laboratory'];

$remainingSessions = (int)($user['remaining_sessions'] ?? 30);

$activeCheck = $db->prepare("SELECT id FROM sit_in_logs WHERE user_id = ? AND logout_time IS NULL");
$activeCheck->execute([$uid]);
$hasActive = (bool)$activeCheck->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sitin') {
    $purpose = trim($_POST['purpose'] ?? '');
    $labRoom = trim($_POST['lab_room'] ?? '');
    if (!$purpose || !$labRoom) {
        $errors[] = 'Please select both a purpose and a lab room.';
    } elseif ($remainingSessions <= 0) {
        $errors[] = 'You have no remaining sessions.';
    } elseif ($hasActive) {
        $errors[] = 'You already have an active sit-in session.';
    } else {
        $db->prepare("INSERT INTO sit_in_logs (user_id, lab_room, purpose, status) VALUES (?, ?, ?, 'active')")->execute([$uid, $labRoom, $purpose]);
        $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$uid, "Sit-in session started in Lab {$labRoom} for {$purpose}."]);
        $sitSuccess = "Sit-in logged for Lab {$labRoom}. Please check in with the lab administrator.";
        $hasActive  = true;
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        $_SESSION['user'] = $user;
        $remainingSessions = (int)($user['remaining_sessions'] ?? 30);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reserve') {
    $purpose = trim($_POST['purpose_res'] ?? '');
    $labRoom = trim($_POST['lab_room_res'] ?? '');
    $timeIn  = trim($_POST['time_in'] ?? '');
    $date    = trim($_POST['date'] ?? '');
    if (!$purpose || !$labRoom || !$timeIn || !$date) {
        $errors[] = 'All reservation fields are required.';
    } elseif (strtotime($date) < strtotime('today')) {
        $errors[] = 'Reservation date cannot be in the past.';
    } else {
        $db->prepare("INSERT INTO reservations (user_id, lab_room, date, time_slot, purpose) VALUES (?, ?, ?, ?, ?)")->execute([$uid, $labRoom, $date, $timeIn, $purpose]);
        $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$uid, "Reservation for Lab {$labRoom} on " . date('F j, Y', strtotime($date)) . " at {$timeIn} submitted."]);
        $resSuccess = "Reservation submitted for Lab {$labRoom} on " . date('F j, Y', strtotime($date)) . ". You will be notified once approved.";
    }
}

$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));
$sessBadgeClass = $remainingSessions > 10 ? 'sess-ok' : ($remainingSessions > 0 ? 'sess-warn' : 'sess-empty');

$pageTitle = 'Reservation';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
?>
<style>
/* ── Reserve page fixes ── */
.res-page-wrap { max-width: 860px; margin: 0 auto; }
.res-page-title {
  font-family: var(--font-display);
  font-size: 1.55rem; font-weight: 800; color: var(--navy);
  text-align: center; margin-bottom: 1.5rem;
}

.res-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  align-items: start;
}

/* Cards */
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

/* Student strip */
.res-stu-strip {
  display: flex; align-items: center; gap: 0.75rem;
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 9px; padding: 0.65rem 0.875rem;
  margin-bottom: 1rem;
}
.res-stu-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  background: linear-gradient(135deg, var(--navy), var(--blue));
  color: var(--light); font-size: 0.75rem; font-weight: 800;
  font-family: var(--font-display);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;
}
.res-stu-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.res-stu-name { font-size: 0.875rem; font-weight: 700; color: var(--navy); margin-bottom: 1px; }
.res-stu-id   { font-size: 0.70rem; color: #94a3b8; }

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

/* Alert boxes */
.res-alert {
  display: flex; align-items: flex-start; gap: 8px;
  padding: 0.65rem 0.875rem; border-radius: 8px;
  font-size: 0.80rem; font-weight: 500; margin-bottom: 0.875rem;
}
.res-alert i { font-size: 0.88rem; flex-shrink: 0; margin-top: 1px; }
.res-alert-warn  { background: rgba(217,119,6,0.07);  color: #92400e; border: 1px solid rgba(217,119,6,0.20); }
.res-alert-error { background: rgba(239,68,68,0.07);  color: #991b1b; border: 1px solid rgba(239,68,68,0.18); }
.res-alert-info  { background: rgba(73,136,196,0.06); color: #1C4D8D; border: 1px solid rgba(73,136,196,0.18); }

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
.res-input:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
.res-select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23475569' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.875rem center; padding-right: 2.5rem; cursor: pointer;
}

/* Submit buttons */
.res-btn {
  width: 100%; padding: 0.72rem;
  border: none; border-radius: 9px;
  font-family: var(--font-body); font-size: 0.875rem; font-weight: 700;
  cursor: pointer; transition: all 0.18s;
  display: flex; align-items: center; justify-content: center; gap: 7px;
  letter-spacing: 0.15px;
}
.res-btn-sitin   { background: #2563EB; color: #fff; box-shadow: 0 2px 8px rgba(37,99,235,0.18); }
.res-btn-sitin:hover:not(:disabled)   { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(37,99,235,0.26); }
.res-btn-reserve { background: var(--navy); color: #fff; box-shadow: 0 2px 8px rgba(15,40,84,0.16); }
.res-btn-reserve:hover:not(:disabled) { background: var(--blue); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(15,40,84,0.22); }
.res-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

@media (max-width: 680px) {
  .res-grid { grid-template-columns: 1fr; }
}
</style>
<?php require_once __DIR__ . '/../includes/user-navbar.php'; ?>

<div class="user-page">
  <div class="user-page-inner">
    <div class="res-page-wrap">

      <h1 class="res-page-title">Reservation</h1>

      <?php if ($errors): ?>
        <div class="flash-msg flash-error" style="margin-bottom:1.1rem;">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>
      <?php if ($sitSuccess): ?>
        <div class="flash-msg flash-success" style="margin-bottom:1.1rem;">
          <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($sitSuccess) ?>
        </div>
      <?php endif; ?>
      <?php if ($resSuccess): ?>
        <div class="flash-msg flash-success" style="margin-bottom:1.1rem;">
          <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($resSuccess) ?>
        </div>
      <?php endif; ?>

      <div class="res-grid">

        <!-- SIT-IN -->
        <div class="res-card">
          <div class="res-card-head">
            <div class="res-card-ico"><i class="bi bi-pc-display"></i></div>
            <div>
              <div class="res-card-name">Sit-in Request</div>
              <div class="res-card-sub">Log a walk-in laboratory session</div>
            </div>
          </div>
          <div class="res-card-body">

            <div class="res-stu-strip">
              <div class="res-stu-avatar">
                <?php if (!empty($user['profile_picture'])): ?>
                  <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="" />
                <?php else: ?>
                  <?= $initials ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="res-stu-name">
                  <?= htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'].' ' : '') . $user['last_name']) ?>
                </div>
                <div class="res-stu-id"><?= htmlspecialchars($user['student_id']) ?></div>
              </div>
            </div>

            <div class="res-sess-row">
              <span class="res-sess-lbl"><i class="bi bi-hourglass-split"></i> Sessions Remaining</span>
              <span class="res-sess-badge <?= $sessBadgeClass ?>"><?= $remainingSessions ?> / 30</span>
            </div>

            <?php if ($hasActive): ?>
              <div class="res-alert res-alert-warn">
                <i class="bi bi-exclamation-triangle-fill"></i>
                You already have an active session. End it from History before starting a new one.
              </div>
            <?php elseif ($remainingSessions <= 0): ?>
              <div class="res-alert res-alert-error">
                <i class="bi bi-x-circle-fill"></i>
                No remaining sessions. Contact the administrator.
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <input type="hidden" name="action" value="sitin">
              <div class="res-field">
                <label>Purpose</label>
                <select name="purpose" class="res-input res-select"
                  <?= ($hasActive || $remainingSessions <= 0) ? 'disabled' : '' ?> required>
                  <option value="" disabled selected>— Select Purpose —</option>
                  <?php foreach ($purposes as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="res-field">
                <label>Laboratory Room</label>
                <select name="lab_room" class="res-input res-select"
                  <?= ($hasActive || $remainingSessions <= 0) ? 'disabled' : '' ?> required>
                  <option value="" disabled selected>— Select Lab —</option>
                  <?php foreach ($labRooms as $lab): ?>
                    <option value="<?= htmlspecialchars($lab) ?>"><?= htmlspecialchars($lab) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="res-btn res-btn-sitin"
                <?= ($hasActive || $remainingSessions <= 0) ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-right"></i> Submit Sit-in
              </button>
            </form>

          </div>
        </div>

        <!-- RESERVATION -->
        <div class="res-card">
          <div class="res-card-head">
            <div class="res-card-ico"><i class="bi bi-calendar-plus"></i></div>
            <div>
              <div class="res-card-name">Advance Reservation</div>
              <div class="res-card-sub">Book a lab slot for a future date</div>
            </div>
          </div>
          <div class="res-card-body">

            <div class="res-alert res-alert-info">
              <i class="bi bi-info-circle-fill"></i>
              Reservations are subject to admin approval. You'll be notified once reviewed.
            </div>

            <form method="POST" action="">
              <input type="hidden" name="action" value="reserve">
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
                <select name="lab_room_res" class="res-input res-select" required>
                  <option value="" disabled selected>— Select Lab —</option>
                  <?php foreach ($labRooms as $lab): ?>
                    <option value="<?= htmlspecialchars($lab) ?>" <?= (($_POST['lab_room_res'] ?? '') === $lab) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($lab) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="res-sess-row" style="margin-bottom:1rem;">
                <span class="res-sess-lbl"><i class="bi bi-hourglass-split"></i> Sessions Remaining</span>
                <span class="res-sess-badge <?= $sessBadgeClass ?>"><?= $remainingSessions ?> / 30</span>
              </div>
              <button type="submit" class="res-btn res-btn-reserve">
                <i class="bi bi-calendar-check"></i> Submit Reservation
              </button>
            </form>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>