<?php
// FILE: pages/admin/testimonials.php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash     = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tid    = (int)($_POST['test_id'] ?? 0);

    if ($action === 'approve' && $tid > 0) {
        $db->prepare("UPDATE testimonials SET status = 'approved' WHERE id = ?")->execute([$tid]);
        $flash = 'Testimonial approved.';
    }
    elseif ($action === 'reject' && $tid > 0) {
        $db->prepare("UPDATE testimonials SET status = 'rejected' WHERE id = ?")->execute([$tid]);
        $flash = 'Testimonial rejected.';
    }
    elseif ($action === 'feature' && $tid > 0) {
        $db->prepare("UPDATE testimonials SET is_featured = 1 WHERE id = ?")->execute([$tid]);
        $flash = 'Testimonial marked as featured.';
    }
    elseif ($action === 'unfeature' && $tid > 0) {
        $db->prepare("UPDATE testimonials SET is_featured = 0 WHERE id = ?")->execute([$tid]);
        $flash = 'Testimonial removed from featured.';
    }
    elseif ($action === 'delete' && $tid > 0) {
        $db->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$tid]);
        $flash = 'Testimonial deleted.';
    }

    header('Location: testimonials.php?flash=' . urlencode($flash) . '&ft=' . urlencode($flashType));
    exit;
}

if (isset($_GET['flash'])) {
    $flash     = $_GET['flash'];
    $flashType = $_GET['ft'] ?? 'success';
}

$testimonials = $db->query("
    SELECT t.*, u.student_id,
           u.first_name || ' ' || u.last_name AS full_name,
           u.course
    FROM testimonials t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
")->fetchAll();

$total    = count($testimonials);
$pending  = count(array_filter($testimonials, fn($t) => $t['status'] === 'pending'));
$approved = count(array_filter($testimonials, fn($t) => $t['status'] === 'approved'));
$featured = count(array_filter($testimonials, fn($t) => $t['is_featured']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Testimonials — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    .tst-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .tst-stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.1rem 1.25rem; display: flex; align-items: center; gap: 0.875rem; box-shadow: 0 1px 3px rgba(15,40,84,0.06); }
    .tst-stat-ico { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .tst-stat-val { font-size: 1.75rem; font-weight: 800; color: #1e293b; line-height: 1; margin-bottom: 2px; }
    .tst-stat-lbl { font-size: 0.70rem; color: #94a3b8; font-weight: 500; }

    .rating-stars { color: #d97706; font-size: 0.82rem; letter-spacing: 1px; }
    .rating-stars .off { color: #e2e8f0; }

    .tst-actions { display: flex; gap: 4px; flex-wrap: wrap; }
    .tst-act-btn {
      padding: 0.25rem 0.55rem; border: none; border-radius: 5px;
      font-size: 0.68rem; font-weight: 700; cursor: pointer;
      transition: all 0.12s; display: inline-flex; align-items: center; gap: 3px;
    }
    .tst-act-approve { background: rgba(22,163,74,0.08); color: #15803d; }
    .tst-act-approve:hover { background: #16a34a; color: #fff; }
    .tst-act-reject  { background: rgba(217,119,6,0.08); color: #92400e; }
    .tst-act-reject:hover  { background: #d97706; color: #fff; }
    .tst-act-feature { background: rgba(37,99,235,0.08); color: #2563EB; }
    .tst-act-feature:hover { background: #2563EB; color: #fff; }
    .tst-act-delete  { background: rgba(220,38,38,0.08); color: #dc2626; }
    .tst-act-delete:hover  { background: #dc2626; color: #fff; }

    @media (max-width: 800px) { .tst-stat-grid { grid-template-columns: repeat(2, 1fr); } }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Testimonial Management</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <i class="bi bi-<?= $flashType === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="tst-stat-grid">
      <div class="tst-stat-card">
        <div class="tst-stat-ico" style="background:rgba(37,99,235,0.08);color:#2563EB;"><i class="bi bi-chat-heart"></i></div>
        <div><div class="tst-stat-val"><?= $total ?></div><div class="tst-stat-lbl">Total Testimonials</div></div>
      </div>
      <div class="tst-stat-card">
        <div class="tst-stat-ico" style="background:rgba(217,119,6,0.08);color:#d97706;"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="tst-stat-val"><?= $pending ?></div><div class="tst-stat-lbl">Pending Review</div></div>
      </div>
      <div class="tst-stat-card">
        <div class="tst-stat-ico" style="background:rgba(22,163,74,0.08);color:#16a34a;"><i class="bi bi-check-circle"></i></div>
        <div><div class="tst-stat-val"><?= $approved ?></div><div class="tst-stat-lbl">Approved</div></div>
      </div>
      <div class="tst-stat-card">
        <div class="tst-stat-ico" style="background:rgba(139,92,246,0.08);color:#7c3aed;"><i class="bi bi-star"></i></div>
        <div><div class="tst-stat-val"><?= $featured ?></div><div class="tst-stat-lbl">Featured</div></div>
      </div>
    </div>

    <!-- Table -->
    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-chat-heart"></i> All Testimonials
      </div>
      <div class="a-card-body">

        <div class="a-table-controls">
          <div class="a-entries-wrap">
            <select id="tstSelect" class="a-entries-select">
              <option>10</option><option>25</option><option>50</option>
            </select>
            <span>entries per page</span>
          </div>
          <div class="a-search-wrap">
            <label>Search:</label>
            <input type="text" id="tstSearch" class="a-search-box" placeholder="Search..."/>
          </div>
        </div>

        <div class="a-table-wrap">
          <table class="a-table" id="tstTable">
            <thead>
              <tr>
                <th class="a-sortable" data-col="0">Student <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">Course <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Rating <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Message <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Status <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="5">Date <span class="a-sort-icon">⇅</span></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="tstBody">
              <?php if (empty($testimonials)): ?>
                <tr class="a-table-empty"><td colspan="7">No testimonials submitted yet.</td></tr>
              <?php else: ?>
                <?php foreach ($testimonials as $t): ?>
                  <tr class="a-data-row">
                    <td>
                      <div style="font-weight:700;font-size:0.82rem;"><?= htmlspecialchars($t['full_name']) ?></div>
                      <div style="font-size:0.68rem;color:#94a3b8;"><?= htmlspecialchars($t['student_id']) ?></div>
                    </td>
                    <td style="font-size:0.80rem;"><?= htmlspecialchars($t['course']) ?></td>
                    <td>
                      <span class="rating-stars">
                        <?= str_repeat('★', (int)$t['rating']) ?><?php if ((int)$t['rating'] < 5): ?><span class="off"><?= str_repeat('★', 5 - (int)$t['rating']) ?></span><?php endif; ?>
                      </span>
                    </td>
                    <td style="max-width:250px;white-space:normal;line-height:1.5;font-size:0.80rem;">
                      <?= htmlspecialchars(mb_substr($t['message'], 0, 120)) ?><?= strlen($t['message']) > 120 ? '…' : '' ?>
                    </td>
                    <td>
                      <span class="a-badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
                      <?php if ($t['is_featured']): ?>
                        <span class="a-badge" style="background:rgba(139,92,246,0.08);color:#7c3aed;margin-left:3px;">★ Featured</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:0.78rem;"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                    <td>
                      <div class="tst-actions">
                        <?php if ($t['status'] === 'pending'): ?>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="approve"><input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="tst-act-btn tst-act-approve"><i class="bi bi-check-lg"></i> Approve</button>
                          </form>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="reject"><input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="tst-act-btn tst-act-reject"><i class="bi bi-x-lg"></i> Reject</button>
                          </form>
                        <?php endif; ?>
                        <?php if ($t['status'] === 'approved' && !$t['is_featured']): ?>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="feature"><input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="tst-act-btn tst-act-feature"><i class="bi bi-star"></i> Feature</button>
                          </form>
                        <?php elseif ($t['is_featured']): ?>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="unfeature"><input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="tst-act-btn tst-act-feature"><i class="bi bi-star-fill"></i> Unfeature</button>
                          </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this testimonial?')">
                          <input type="hidden" name="action" value="delete"><input type="hidden" name="test_id" value="<?= $t['id'] ?>">
                          <button type="submit" class="tst-act-btn tst-act-delete"><i class="bi bi-trash3"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="a-table-footer">
          <div class="a-table-info" id="tstInfo"></div>
          <div class="a-pagination" id="tstPag"></div>
        </div>

      </div>
    </div>

  </div>
</div>

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({
  tableId:'tstTable', bodyId:'tstBody', infoId:'tstInfo',
  pagId:'tstPag', searchId:'tstSearch', selectId:'tstSelect'
});
</script>
</body>
</html>
