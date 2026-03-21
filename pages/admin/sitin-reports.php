<?php
// FILE: pages/admin/sitin-reports.php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$byPurpose = $db->query("
    SELECT purpose, COUNT(*) as cnt
    FROM sit_in_logs
    WHERE purpose IS NOT NULL AND purpose != ''
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Sit-in Reports</h1>

    <!-- Summary Cards -->
    <div class="dash-summary">
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-blue"><i class="bi bi-clock-history"></i></div>
        <div>
          <div class="dash-stat-val"><?= $total ?></div>
          <div class="dash-stat-label">Total Sit-ins</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-green"><i class="bi bi-pc-display-horizontal"></i></div>
        <div>
          <div class="dash-stat-val"><?= $active ?></div>
          <div class="dash-stat-label">Active Now</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-slate"><i class="bi bi-check2-circle"></i></div>
        <div>
          <div class="dash-stat-val"><?= $completed ?></div>
          <div class="dash-stat-label">Completed</div>
        </div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="reports-grid">

      <div class="a-card">
        <div class="a-card-header">
          <i class="bi bi-pie-chart"></i> Sessions by Purpose
        </div>
        <div class="a-card-body">
          <div class="report-chart-wrap">
            <canvas id="purposeChart" height="220"></canvas>
          </div>
        </div>
      </div>

      <div class="a-card">
        <div class="a-card-header">
          <i class="bi bi-bar-chart"></i> Sessions by Laboratory
        </div>
        <div class="a-card-body">
          <div class="report-chart-wrap">
            <canvas id="labChart" height="220"></canvas>
          </div>
        </div>
      </div>

    </div>

    <!-- Trend chart -->
    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-graph-up"></i> Daily Sit-in Trend (Last 30 Days)
      </div>
      <div class="a-card-body">
        <canvas id="dateChart" height="90"></canvas>
      </div>
    </div>

  </div>
</div>

<script>
const palette = ['#1a3a6b','#2563EB','#4988C4','#93c5fd','#1C4D8D','#0891b2','#475569'];

new Chart(document.getElementById('purposeChart'), {
  type: 'pie',
  data: {
    labels: <?= json_encode(array_column($byPurpose,'purpose')) ?>,
    datasets: [{ data: <?= json_encode(array_column($byPurpose,'cnt')) ?>, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }]
  },
  options: { responsive: true, plugins: { legend: { position:'bottom', labels:{ font:{family:'Outfit',size:11}, padding:12 } } } }
});

new Chart(document.getElementById('labChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byLab,'lab_room')) ?>,
    datasets: [{ label:'Sessions', data: <?= json_encode(array_column($byLab,'cnt')) ?>, backgroundColor:'#2563EB', borderRadius:5 }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero:true, ticks:{ stepSize:1 } } }
  }
});

new Chart(document.getElementById('dateChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($byDate,'log_date')) ?>,
    datasets: [{
      label: 'Sit-ins',
      data: <?= json_encode(array_column($byDate,'cnt')) ?>,
      borderColor: '#2563EB',
      backgroundColor: 'rgba(37,99,235,0.07)',
      tension: 0.35, fill: true, pointRadius: 3,
      pointBackgroundColor: '#2563EB'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero:true, ticks:{ stepSize:1 } } }
  }
});
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body>
</html>