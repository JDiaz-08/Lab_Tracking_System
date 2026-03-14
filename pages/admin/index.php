<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

// Handle announcement submission
$annSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
    $content = trim($_POST['announcement']);
    if ($content) {
        $db->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)")
           ->execute(['CCS Admin', $content]);

        // Notify all users
        $users = $db->query("SELECT id FROM users")->fetchAll();
        $ins   = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)");
        foreach ($users as $u)
            $ins->execute([$u['id'], "📢 New announcement from Admin: " . mb_substr($content, 0, 80) . (strlen($content)>80?'…':'')]);

        $annSuccess = 'Announcement posted!';
    }
}

// Stats
$totalStudents  = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$currentSitIn   = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs WHERE logout_time IS NULL")->fetchColumn();
$totalSitIn     = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs")->fetchColumn();

// Purpose breakdown for pie chart
$purposes = $db->query("
    SELECT purpose, COUNT(*) as cnt
    FROM sit_in_logs
    WHERE purpose IS NOT NULL AND purpose != ''
    GROUP BY purpose
")->fetchAll();

$announcements = $db->query(
    "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Home — UC CompLab</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <?php if ($annSuccess): ?>
      <div class="a-flash a-flash-success">✅ <?= $annSuccess ?></div>
    <?php endif; ?>

    <div class="admin-dash-grid">

      <!-- LEFT: Statistics + Chart -->
      <div class="a-card">
        <div class="a-card-header">📊 Statistics</div>
        <div class="a-card-body">
          <div class="a-stat-list">
            <div class="a-stat-item"><strong>Students Registered:</strong> <?= $totalStudents ?></div>
            <div class="a-stat-item"><strong>Currently Sit-in:</strong> <?= $currentSitIn ?></div>
            <div class="a-stat-item"><strong>Total Sit-in:</strong> <?= $totalSitIn ?></div>
          </div>
          <div class="a-chart-wrap">
            <canvas id="purposeChart" height="240"></canvas>
          </div>
        </div>
      </div>

      <!-- RIGHT: Announcement -->
      <div class="a-card">
        <div class="a-card-header">📣 Announcement</div>
        <div class="a-card-body">
          <form method="POST" action="">
            <textarea name="announcement" class="a-ann-textarea"
              placeholder="New Announcement"></textarea>
            <button type="submit" class="a-btn a-btn-green">Submit</button>
          </form>

          <div class="a-ann-list">
            <h3>Posted Announcement</h3>
            <?php if (empty($announcements)): ?>
              <p style="font-size:0.85rem; color:var(--a-gray400);">No announcements yet.</p>
            <?php else: ?>
              <?php foreach ($announcements as $ann): ?>
                <div class="a-ann-item">
                  <div class="a-ann-meta">
                    CCS Admin | <?= date('Y-M-d', strtotime($ann['created_at'])) ?>
                  </div>
                  <?php if ($ann['content'] !== $ann['title']): ?>
                    <div class="a-ann-content"><?= nl2br(htmlspecialchars($ann['content'])) ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /.admin-dash-grid -->
  </div>
</div>

<script>
// Pie chart
const labels  = <?= json_encode(array_column($purposes, 'purpose')) ?>;
const data    = <?= json_encode(array_column($purposes, 'cnt')) ?>;
const colors  = ['#2563EB','#dc2626','#d97706','#16a34a','#7c3aed','#0891b2'];

if (labels.length > 0) {
  new Chart(document.getElementById('purposeChart'), {
    type: 'pie',
    data: {
      labels,
      datasets: [{ data, backgroundColor: colors.slice(0, labels.length), borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top', labels: { font: { family: 'Outfit', size: 11 }, padding: 10 } }
      }
    }
  });
} else {
  // Default demo chart
  new Chart(document.getElementById('purposeChart'), {
    type: 'pie',
    data: {
      labels: ['C#','C','Java','ASP.Net','PHP'],
      datasets: [{ data:[1,1,1,1,1], backgroundColor:['#2563EB','#dc2626','#d97706','#16a34a','#7c3aed'], borderWidth:2, borderColor:'#fff' }]
    },
    options: { responsive:true, plugins:{ legend:{ position:'top', labels:{ font:{family:'Outfit',size:11}, padding:10 } } } }
  });
}
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body></html>