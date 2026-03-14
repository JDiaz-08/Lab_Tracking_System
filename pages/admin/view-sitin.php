<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$records = $db->query("
    SELECT s.id, u.student_id,
           u.first_name||' '||COALESCE(u.middle_name||' ','')||u.last_name AS full_name,
           s.purpose, s.lab_room,
           s.login_time, s.logout_time,
           DATE(s.login_time) AS log_date, s.status
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    ORDER BY s.login_time DESC
")->fetchAll();

function dur($a, $b) {
    if (!$b) return '—';
    $d = strtotime($b) - strtotime($a);
    return floor($d/3600).'h '.floor(($d%3600)/60).'m';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sit-in Records — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">
    <h1 class="a-page-title">View Sit-in Records</h1>

    <div class="a-card">
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
              </tr>
            </thead>
            <tbody id="recBody">
              <?php if (empty($records)): ?>
                <tr class="a-table-empty"><td colspan="8">No data available</td></tr>
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

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({ tableId:'recTable', bodyId:'recBody', infoId:'recInfo', pagId:'recPag', searchId:'recSearch', selectId:'recSelect' });
</script>
</body></html>