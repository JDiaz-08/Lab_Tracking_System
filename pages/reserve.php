<?php
session_start();
$base = '../';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireUser();
$db  = getDB();
$uid = $_SESSION['user_id'];

// Refresh user
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

$labRooms = ['524', '526', '528', '530', '542', 'Mac Laboratory'];

// Remaining sessions: 30 max minus how many sit-ins they've done
$used = (int) $db->prepare(
    "SELECT COUNT(*) FROM sit_in_logs WHERE user_id = ?"
)->execute([$uid]) ? $db->query(
    "SELECT COUNT(*) FROM sit_in_logs WHERE user_id = $uid"
)->fetchColumn() : 0;
$remainingSessions = max(0, 30 - $used);

/* ---- HANDLE SIT-IN (Submit button) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'sitin') {
    $purpose = trim($_POST['purpose'] ?? '');
    $labRoom = trim($_POST['lab_room'] ?? '');

    if (!$purpose || !$labRoom) {
        $errors[] = 'Purpose and Lab are required.';
    } else {
        $db->prepare(
            "INSERT INTO sit_in_logs (user_id, lab_room, purpose) VALUES (?, ?, ?)"
        )->execute([$uid, $labRoom, $purpose]);

        $db->prepare(
            "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
        )->execute([$uid, "✅ Sit-in session started in Lab {$labRoom} for {$purpose}."]);

        $sitSuccess = "Sit-in session logged successfully for Lab {$labRoom}!";
    }
}

/* ---- HANDLE RESERVATION (Reserve button) ---- */
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
            "INSERT INTO reservations (user_id, lab_room, date, time_slot, purpose)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$uid, $labRoom, $date, $timeIn, $purpose]);

        $db->prepare(
            "INSERT INTO notifications (user_id, message) VALUES (?, ?)"
        )->execute([$uid,
            "📅 Reservation for Lab {$labRoom} on " . date('F j, Y', strtotime($date))
            . " at {$timeIn} submitted and pending approval."
        ]);

        $resSuccess = "Reservation submitted! You'll be notified once it's approved.";
    }
}

$pageTitle = 'Reservation';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/reserve.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<div class="user-page">
  <div class="user-page-inner">
    <div class="res-page-wrap">

      <h1 class="res-title">Reservation</h1>

      <?php if ($errors): ?>
        <div class="flash-msg flash-error res-flash">
          ❌ <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>
      <?php if ($sitSuccess): ?>
        <div class="flash-msg flash-success res-flash">✅ <?= htmlspecialchars($sitSuccess) ?></div>
      <?php endif; ?>
      <?php if ($resSuccess): ?>
        <div class="flash-msg flash-success res-flash">✅ <?= htmlspecialchars($resSuccess) ?></div>
      <?php endif; ?>

      <div class="res-form-card">

        <!-- ===== SIT-IN FORM ===== -->
        <form method="POST" action="">
          <input type="hidden" name="action" value="sitin">

          <div class="res-row">
            <label class="res-label">ID Number:</label>
            <div class="res-field">
              <input type="text" class="res-input res-disabled"
                     value="<?= htmlspecialchars($user['student_id']) ?>" disabled />
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Student Name:</label>
            <div class="res-field">
              <input type="text" class="res-input res-disabled"
                     value="<?= htmlspecialchars(
                         $user['first_name'] . ' ' .
                         ($user['middle_name'] ? $user['middle_name'] . ' ' : '') .
                         $user['last_name']
                     ) ?>" disabled />
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Purpose:</label>
            <div class="res-field">
              <select name="purpose" class="res-input res-select">
                <option value="" disabled selected>— Select Purpose —</option>
                <?php foreach ($purposes as $p): ?>
                  <option value="<?= $p ?>"><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Lab:</label>
            <div class="res-field">
              <select name="lab_room" class="res-input res-select">
                <option value="" disabled selected>— Select Lab —</option>
                <?php foreach ($labRooms as $lab): ?>
                  <option value="<?= $lab ?>"><?= $lab ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="res-row">
            <label class="res-label"></label>
            <div class="res-field">
              <button type="submit" class="res-btn res-btn-submit">Submit</button>
            </div>
          </div>

        </form>

        <!-- ===== RESERVATION FORM ===== -->
        <form method="POST" action="" style="margin-top: 0.5rem;">
          <input type="hidden" name="action" value="reserve">

          <div class="res-row">
            <label class="res-label">Time In:</label>
            <div class="res-field">
              <input type="time" name="time_in" class="res-input"
                     value="<?= htmlspecialchars($_POST['time_in'] ?? '') ?>" />
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Date:</label>
            <div class="res-field">
              <input type="date" name="date" class="res-input"
                     min="<?= date('Y-m-d') ?>"
                     value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" />
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Purpose:</label>
            <div class="res-field">
              <select name="purpose_res" class="res-input res-select">
                <option value="" disabled selected>— Select Purpose —</option>
                <?php foreach ($purposes as $p): ?>
                  <option value="<?= $p ?>"><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Lab:</label>
            <div class="res-field">
              <select name="lab_room_res" class="res-input res-select">
                <option value="" disabled selected>— Select Lab —</option>
                <?php foreach ($labRooms as $lab): ?>
                  <option value="<?= $lab ?>"><?= $lab ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="res-row">
            <label class="res-label">Remaining Session:</label>
            <div class="res-field">
              <input type="text" class="res-input res-disabled"
                     value="<?= $remainingSessions ?>" disabled />
            </div>
          </div>

          <div class="res-row">
            <label class="res-label"></label>
            <div class="res-field">
              <button type="submit" class="res-btn res-btn-reserve">Reserve</button>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>