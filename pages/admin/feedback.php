<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$feedbacks = $db->query("
    SELECT f.*, u.student_id,
           u.first_name||' '||u.last_name AS full_name
    FROM feedback f
    JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
")->fetchAll();

$avgRating = $db->query("SELECT AVG(rating) FROM feedback")->fetchColumn();
$total     = $db->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Feedback Reports — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">
    <h1 class="a-page-title">Feedback Reports</h1>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem;">
      <div class="a-card"><div class="a-card-body" style="text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:#2563EB;"><?= (int)$total ?></div>
        <div style="font-size:0.82rem; color:#475569;">Total Feedback</div>
      </div></div>
      <div class="a-card"><div class="a-card-body" style="text-align:center;">
        <div style="font-size:2rem; font-weight:800; color:#d97706;"><?= $avgRating ? number_format($avgRating,1) : '—' ?></div>
        <div style="font-size:0.82rem; color:#475569;">Average Rating</div>
      </div></div>
    </div>

    <div class="a-card">
      <div class="a-card-body">
        <div class="a-table-controls">
          <div class="a-entries-wrap">
            <select id="fbSelect" class="a-entries-select">
              <option>10</option><option>25</option><option>50</option>
            </select>
            <span>entries per page</span>
          </div>
          <div class="a-search-wrap">
            <label>Search:</label>
            <input type="text" id="fbSearch" class="a-search-box" placeholder="Search..."/>
          </div>
        </div>

        <div class="a-table-wrap">
          <table class="a-table" id="fbTable">
            <thead>
              <tr>
                <th class="a-sortable" data-col="0">Student ID <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">Name <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Feedback <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Rating <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Date <span class="a-sort-icon">⇅</span></th>
              </tr>
            </thead>
            <tbody id="fbBody">
              <?php if (empty($feedbacks)): ?>
                <tr class="a-table-empty"><td colspan="5">No feedback submitted yet.</td></tr>
              <?php else: ?>
                <?php foreach ($feedbacks as $f): ?>
                  <tr class="a-data-row">
                    <td><?= htmlspecialchars($f['student_id']) ?></td>
                    <td><?= htmlspecialchars($f['full_name']) ?></td>
                    <td><?= htmlspecialchars($f['message']) ?></td>
                    <td><?= str_repeat('★', (int)$f['rating']) . str_repeat('☆', 5-(int)$f['rating']) ?></td>
                    <td><?= date('M j, Y', strtotime($f['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="a-table-footer">
          <div class="a-table-info" id="fbInfo"></div>
          <div class="a-pagination"  id="fbPag"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({ tableId:'fbTable', bodyId:'fbBody', infoId:'fbInfo', pagId:'fbPag', searchId:'fbSearch', selectId:'fbSelect' });
</script>
</body></html>