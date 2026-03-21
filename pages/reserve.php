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

$purposes = [
    'C# Programming',
    'Java Programming',
    'PHP Programming',
    'C Programming',
    'ASP.net Programming',
];
$labRooms = ['524','526','528','530','542','Mac Laboratory'];

$remainingSessions = (int)($user['remaining_sessions'] ?? 30);

$activeCheck = $db->prepare(
    "SELECT id FROM sit_in_logs WHERE user_id = ? AND logout_time IS NULL"
);
$activeCheck->execute([$uid]);
$hasActive = (bool)$activeCheck->fetch();

/* ---- HANDLE SIT-IN ---- */
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
        $db->prepare(
            "INSERT INTO sit_in_logs (user_id, lab_room, purpose, status) VALUES (?, ?, ?, 'active')"
        )->execute([$uid, $labRoom, $purpose]);
        $db->prepare(
            "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
        )->execute([$uid, "Sit-in session started in Lab {$labRoom} for {$purpose}."]);
        $sitSuccess = "Sit-in logged for Lab {$labRoom}. Please check in with the lab administrator.";
        $hasActive  = true;
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        $_SESSION['user'] = $user;
        $remainingSessions = (int)($user['remaining_sessions'] ?? 30);
    }
}

/* ---- HANDLE RESERVATION ---- */
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
        $db->prepare(
            "INSERT INTO reservations (user_id, lab_room, date, time_slot, purpose) VALUES (?, ?, ?, ?, ?)"
        )->execute([$uid, $labRoom, $date, $timeIn, $purpose]);
        $db->prepare(
            "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
        )->execute([$uid,
            "Reservation for Lab {$labRoom} on " . date('F j, Y', strtotime($date))
            . " at {$timeIn} submitted and pending approval."
        ]);
        $resSuccess = "Reservation submitted for Lab {$labRoom} on " . date('F j, Y', strtotime($date)) . ". You will be notified once approved.";
    }
}

if ($remainingSessions > 10)    $sessBadgeClass = 'res-badge-ok';
elseif ($remainingSessions > 0) $sessBadgeClass = 'res-badge-warn';
else                             $sessBadgeClass = 'res-badge-empty';

$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));

$pageTitle = 'Reservation';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<div class="user-page">
  <div class="user-page-inner">
    <div class="res-wrap">

      <h1 class="res-page-title">Reservation</h1>

      <?php if ($errors): ?>
        <div class="flash-msg flash-error">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>
      <?php if ($sitSuccess): ?>
        <div class="flash-msg flash-success">
          <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($sitSuccess) ?>
        </div>
      <?php endif; ?>
      <?php if ($resSuccess): ?>
        <div class="flash-msg flash-success">
          <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($resSuccess) ?>
        </div>
      <?php endif; ?>

      <div class="res-grid">

        <!-- SIT-IN -->
        <div class="res-card">
          <div class="res-card-header">
            <div class="res-card-icon"><i class="bi bi-pc-display"></i></div>
            <div>
              <div class="res-card-title">Sit-in Request</div>
              <div class="res-card-subtitle">Log a walk-in laboratory session</div>
            </div>
          </div>
          <div class="res-card-body">

            <div class="res-student-strip">
              <div class="res-student-avatar">
                <?php if (!empty($user['profile_picture'])): ?>
                  <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile" />
                <?php else: ?>
                  <?= $initials ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="res-student-name">
                  <?= htmlspecialchars(
                    $user['first_name'] . ' ' .
                    ($user['middle_name'] ? $user['middle_name'].' ' : '') .
                    $user['last_name']
                  ) ?>
                </div>
                <div class="res-student-id"><?= htmlspecialchars($user['student_id']) ?></div>
              </div>
            </div>

            <div class="res-sessions-row">
              <span class="res-sessions-row-label">
                <i class="bi bi-hourglass-split"></i> Sessions Remaining
              </span>
              <span class="res-sessions-badge <?= $sessBadgeClass ?>">
                <?= $remainingSessions ?> / 30
              </span>
            </div>

            <?php if ($hasActive): ?>
              <div class="res-info-box res-info-warn">
                <i class="bi bi-exclamation-triangle-fill"></i>
                You already have an active sit-in session. End it from your History page before starting a new one.
              </div>
            <?php elseif ($remainingSessions <= 0): ?>
              <div class="res-info-box res-info-error">
                <i class="bi bi-x-circle-fill"></i>
                No remaining sessions. Contact the administrator to reset your sessions.
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <input type="hidden" name="action" value="sitin">
              <div class="res-field-group">
                <label class="res-field-label">Purpose</label>
                <select name="purpose" class="res-field-input res-select"
                  <?= ($hasActive || $remainingSessions <= 0) ? 'disabled' : '' ?> required>
                  <option value="" disabled selected>— Select Purpose —</option>
                  <?php foreach ($purposes as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="res-field-group">
                <label class="res-field-label">Laboratory Room</label>
                <select name="lab_room" class="res-field-input res-select"
                  <?= ($hasActive || $remainingSessions <= 0) ? 'disabled' : '' ?> required>
                  <option value="" disabled selected>— Select Lab —</option>
                  <?php foreach ($labRooms as $lab): ?>
                    <option value="<?= htmlspecialchars($lab) ?>"><?= htmlspecialchars($lab) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="res-submit-btn res-btn-sitin"
                <?= ($hasActive || $remainingSessions <= 0) ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-right"></i> Submit Sit-in
              </button>
            </form>

          </div>
        </div>

        <!-- RESERVATION -->
        <div class="res-card">
          <div class="res-card-header">
            <div class="res-card-icon"><i class="bi bi-calendar-plus"></i></div>
            <div>
              <div class="res-card-title">Advance Reservation</div>
              <div class="res-card-subtitle">Book a lab slot for a future date</div>
            </div>
          </div>
          <div class="res-card-body">

            <div class="res-info-box res-info-note">
              <i class="bi bi-info-circle-fill"></i>
              Reservations are subject to admin approval. You will receive a notification once reviewed.
            </div>

            <form method="POST" action="">
              <input type="hidden" name="action" value="reserve">
              <div class="res-field-group">
                <label class="res-field-label">Preferred Date</label>
                <input type="date" name="date" class="res-field-input"
                       min="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($_POST['date'] ?? '') ?>"
                       required />
              </div>
              <div class="res-field-group">
                <label class="res-field-label">Preferred Time</label>
                <input type="time" name="time_in" class="res-field-input"
                       value="<?= htmlspecialchars($_POST['time_in'] ?? '') ?>"
                       required />
              </div>
              <div class="res-field-group">
                <label class="res-field-label">Purpose</label>
                <select name="purpose_res" class="res-field-input res-select" required>
                  <option value="" disabled selected>— Select Purpose —</option>
                  <?php foreach ($purposes as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>"
                      <?= (($_POST['purpose_res'] ?? '') === $p) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="res-field-group">
                <label class="res-field-label">Laboratory Room</label>
                <select name="lab_room_res" class="res-field-input res-select" required>
                  <option value="" disabled selected>— Select Lab —</option>
                  <?php foreach ($labRooms as $lab): ?>
                    <option value="<?= htmlspecialchars($lab) ?>"
                      <?= (($_POST['lab_room_res'] ?? '') === $lab) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($lab) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="res-sessions-row">
                <span class="res-sessions-row-label">
                  <i class="bi bi-hourglass-split"></i> Sessions Remaining
                </span>
                <span class="res-sessions-badge <?= $sessBadgeClass ?>">
                  <?= $remainingSessions ?> / 30
                </span>
              </div>
              <button type="submit" class="res-submit-btn res-btn-reserve">
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