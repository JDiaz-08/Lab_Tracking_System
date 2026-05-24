<?php
// FILE: pages/admin/feedback.php
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
$total     = (int)$db->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$fiveStar  = (int)$db->query("SELECT COUNT(*) FROM feedback WHERE rating = 5")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Feedback — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    .fb-stat-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .fb-stat-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 1.25rem 1.5rem;
      box-shadow: 0 1px 3px rgba(15,40,84,0.06);
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .fb-stat-ico {
      width: 44px; height: 44px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }
    .fb-val { font-size: 1.75rem; font-weight: 800; color: #1e293b; line-height: 1; margin-bottom: 2px; }
    .fb-lbl { font-size: 0.70rem; color: #94a3b8; font-weight: 500; }

    .rating-stars-full  { color: #d97706; font-size: 0.88rem; letter-spacing: 1.5px; }
    .rating-stars-empty { color: #e2e8f0; font-size: 0.88rem; letter-spacing: 1.5px; }
    .rating-cell { display: flex; align-items: center; gap: 6px; }
    .rating-num  { font-size: 0.75rem; font-weight: 700; color: #64748b; }

    @media (max-width: 720px) {
      .fb-stat-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Feedback Reports</h1>

    <!-- Stats -->
    <div class="fb-stat-grid">
      <div class="fb-stat-card">
        <div class="fb-stat-ico" style="background:rgba(37,99,235,0.08); color:#2563EB;">
          <i class="bi bi-chat-square-text"></i>
        </div>
        <div>
          <div class="fb-val"><?= $total ?></div>
          <div class="fb-lbl">Total Feedback</div>
        </div>
      </div>
      <div class="fb-stat-card">
        <div class="fb-stat-ico" style="background:rgba(217,119,6,0.08); color:#d97706;">
          <i class="bi bi-star-fill"></i>
        </div>
        <div>
          <div class="fb-val"><?= $avgRating ? number_format($avgRating,1) : '—' ?></div>
          <div class="fb-lbl">Average Rating</div>
        </div>
      </div>
      <div class="fb-stat-card">
        <div class="fb-stat-ico" style="background:rgba(22,163,74,0.08); color:#16a34a;">
          <i class="bi bi-hand-thumbs-up"></i>
        </div>
        <div>
          <div class="fb-val"><?= $fiveStar ?></div>
          <div class="fb-lbl">5-Star Ratings</div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-chat-square-text"></i> All Feedback
        <?php if ($total > 0): ?>
          <span class="a-badge badge-active" style="margin-left:auto;"><?= $total ?> total</span>
        <?php endif; ?>
      </div>
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
                    <td style="max-width:280px; white-space:normal; line-height:1.5;">
                      <?= htmlspecialchars($f['message']) ?>
                    </td>
                    <td>
                      <div class="rating-cell">
                        <span class="rating-stars-full">
                          <?= str_repeat('★', (int)$f['rating']) ?>
                        </span>
                        <?php if ((int)$f['rating'] < 5): ?>
                          <span class="rating-stars-empty">
                            <?= str_repeat('★', 5 - (int)$f['rating']) ?>
                          </span>
                        <?php endif; ?>
                        <span class="rating-num"><?= (int)$f['rating'] ?>/5</span>
                      </div>
                    </td>
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
initAdminTable({
  tableId:'fbTable', bodyId:'fbBody', infoId:'fbInfo',
  pagId:'fbPag', searchId:'fbSearch', selectId:'fbSelect'
});
</script>
</body>
</html>