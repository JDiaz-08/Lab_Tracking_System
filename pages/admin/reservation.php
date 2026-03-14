<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rid    = (int)($_POST['res_id'] ?? 0);

    if ($action === 'approve') {
        $res = $db->query("SELECT * FROM reservations WHERE id=$rid")->fetch();
        $db->prepare("UPDATE reservations SET status='approved' WHERE id=?")->execute([$rid]);
        if ($res) {
            $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
               ->execute([$res['user_id'],
                   "✅ Your reservation for Lab {$res['lab_room']} on "
                   . date('F j, Y', strtotime($res['date']))
                   . " at {$res['time_slot']} has been approved!"]);
        }
        $flash = 'Reservation approved.';
    }
    elseif ($action === 'reject') {
        $res = $db->query("SELECT * FROM reservations WHERE id=$rid")->fetch();
        $db->prepare("UPDATE reservations SET status='rejected' WHERE id=?")->execute([$rid]);
        if ($res) {
            $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)")
               ->execute([$res['user_id'],
                   "❌ Your reservation for Lab {$res['lab_room']} on "
                   . date('F j, Y', strtotime($res['date']))
                   . " has been rejected."]);
        }
        $flash = 'Reservation rejected.';
    }
    elseif ($action === 'delete') {
        $db->prepare("DELETE FROM reservations WHERE id=?")->execute([$rid]);
        $flash = 'Reservation deleted.';
    }

    header('Location: reservation.php?flash=' . urlencode($flash)); exit;
}

if (isset($_GET['flash'])) $flash = $_GET['flash'];

$reservations = $db->query("
    SELECT r.*, u.student_id,
           u.first_name||' '||u.last_name AS full_name
    FROM reservations r
    JOIN users u ON u.id = r.user_id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Reservations — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">
    <h1 class="a-page-title">Reservation Management</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-success">✅ <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="a-card">
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
                <th class="a-sortable" data-col="6">Status <span class="a-sort-icon">⇅</span></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="resBody">
              <?php if (empty($reservations)): ?>
                <tr class="a-table-empty"><td colspan="8">No reservations yet.</td></tr>
              <?php else: ?>
                <?php foreach ($reservations as $r): ?>
                  <tr class="a-data-row">
                    <td><?= htmlspecialchars($r['student_id']) ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['lab_room']) ?></td>
                    <td><?= date('M j, Y', strtotime($r['date'])) ?></td>
                    <td><?= htmlspecialchars($r['time_slot']) ?></td>
                    <td><?= htmlspecialchars($r['purpose'] ?? '—') ?></td>
                    <td><span class="a-badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td style="white-space:nowrap;">
                      <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="approve">
                          <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                          <button type="submit" class="a-btn a-btn-green a-btn-sm">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                          <button type="submit" class="a-btn a-btn-yellow a-btn-sm">Reject</button>
                        </form>
                      <?php endif; ?>
                      <form method="POST" style="display:inline;"
                            onsubmit="return confirm('Delete this reservation?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="res_id" value="<?= $r['id'] ?>">
                        <button type="submit" class="a-btn a-btn-red a-btn-sm">Delete</button>
                      </form>
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

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({ tableId:'resTable', bodyId:'resBody', infoId:'resInfo', pagId:'resPag', searchId:'resSearch', selectId:'resSelect' });
</script>
</body></html>