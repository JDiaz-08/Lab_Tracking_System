<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$byPurpose = $db->query("
    SELECT purpose, COUNT(*) as cnt
    FROM sit_in_logs WHERE purpose IS NOT NULL AND purpose != ''
    GROUP BY purpose ORDER BY cnt DESC
")->fetchAll();

$byLab = $db->query("
    SELECT lab_room, COUNT(*) as cnt
    FROM sit_in_logs
    GROUP BY lab_room ORDER BY cnt DESC
")->fetchAll();

$byDate = $db->query("
    SELECT DATE(login_time) as log_date, COUNT(*) as cnt
    FROM sit_in_logs
    GROUP BY log_date ORDER BY log_date DESC LIMIT 30
")->fetchAll();
$byDate = array_reverse($byDate);

$total     = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs")->fetchColumn();
$active    = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs WHERE logout_time IS NULL")->fetchColumn();
$completed = $total - $active;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sit-in Reports — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">
    <h1 class="a-page-title">Sit-in Reports</h1>

    <!-- Summary cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem;">
      <?php foreach ([
        ['Total Sit-ins',  $total,     '#2563EB'],
        ['Active Now',     $active,    '#16a34a'],
        ['Completed',      $completed, '#475569'],
      ] as [$label, $val, $color]): ?>
        <div class="a-card">
          <div class="a-card-body" style="text-align:center; padding:1.25rem;">
            <div style="font-size:2rem; font-weight:800; color:<?= $color ?>;"><?= $val ?></div>
            <div style="font-size:0.82rem; color:var(--a-gray600); margin-top:4px;"><?= $label ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
      <!-- By Purpose -->
      <div class="a-card">
        <div class="a-card-header">📊 Sessions by Purpose</div>
        <div class="a-card-body">
          <canvas id="purposeChart" height="200"></canvas>
        </div>
      </div>
      <!-- By Lab -->
      <div class="a-card">
        <div class="a-card-header">🏫 Sessions by Laboratory</div>
        <div class="a-card-body">
          <canvas id="labChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <!-- By Date -->
    <div class="a-card">
      <div class="a-card-header">📅 Daily Sit-in Trend (Last 30 Days)</div>
      <div class="a-card-body">
        <canvas id="dateChart" height="90"></canvas>
      </div>
    </div>

  </div>
</div>

<script>
const colors = ['#2563EB','#dc2626','#d97706','#16a34a','#7c3aed','#0891b2','#db2777'];

new Chart(document.getElementById('purposeChart'), {
  type: 'pie',
  data: {
    labels: <?= json_encode(array_column($byPurpose,'purpose')) ?>,
    datasets: [{ data: <?= json_encode(array_column($byPurpose,'cnt')) ?>, backgroundColor: colors }]
  },
  options: { responsive:true, plugins:{ legend:{ position:'right', labels:{ font:{family:'Outfit',size:11} } } } }
});

new Chart(document.getElementById('labChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byLab,'lab_room')) ?>,
    datasets: [{ label:'Sessions', data: <?= json_encode(array_column($byLab,'cnt')) ?>, backgroundColor: '#2563EB', borderRadius:5 }]
  },
  options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } }
});

new Chart(document.getElementById('dateChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($byDate,'log_date')) ?>,
    datasets: [{ label:'Sit-ins', data: <?= json_encode(array_column($byDate,'cnt')) ?>, borderColor:'#2563EB', backgroundColor:'rgba(37,99,235,0.08)', tension:0.35, fill:true, pointRadius:3 }]
  },
  options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } }
});
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body></html>