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
$avgPerDay = $byDate ? round(array_sum(array_column($byDate,'cnt')) / count($byDate), 1) : 0;
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
  <style>
    .rep-stat-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .rep-stat-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 1.1rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.875rem;
      box-shadow: 0 1px 3px rgba(15,40,84,0.06);
    }
    .rep-stat-ico {
      width: 44px; height: 44px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }
    .rep-val { font-size: 1.75rem; font-weight: 800; color: #1e293b; line-height: 1; margin-bottom: 2px; }
    .rep-lbl { font-size: 0.70rem; color: #94a3b8; font-weight: 500; }

    .rep-charts-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }
    .rep-chart-wrap {
      position: relative;
      width: 100%;
      height: 280px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .rep-chart-wrap canvas {
      max-height: 280px !important;
    }
    .rep-trend-wrap {
      position: relative;
      width: 100%;
      height: 240px;
    }
    .rep-trend-wrap canvas {
      max-height: 240px !important;
    }

    @media (max-width: 900px) {
      .rep-stat-grid   { grid-template-columns: repeat(2, 1fr); }
      .rep-charts-row  { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Sit-in Reports</h1>

    <!-- Summary -->
    <div class="rep-stat-grid">
      <div class="rep-stat-card">
        <div class="rep-stat-ico" style="background:rgba(37,99,235,0.08); color:#2563EB;">
          <i class="bi bi-clock-history"></i>
        </div>
        <div>
          <div class="rep-val"><?= $total ?></div>
          <div class="rep-lbl">Total Sit-ins</div>
        </div>
      </div>
      <div class="rep-stat-card">
        <div class="rep-stat-ico" style="background:rgba(22,163,74,0.08); color:#16a34a;">
          <i class="bi bi-pc-display-horizontal"></i>
        </div>
        <div>
          <div class="rep-val"><?= $active ?></div>
          <div class="rep-lbl">Active Now</div>
        </div>
      </div>
      <div class="rep-stat-card">
        <div class="rep-stat-ico" style="background:rgba(71,85,105,0.08); color:#475569;">
          <i class="bi bi-check2-circle"></i>
        </div>
        <div>
          <div class="rep-val"><?= $completed ?></div>
          <div class="rep-lbl">Completed</div>
        </div>
      </div>
      <div class="rep-stat-card">
        <div class="rep-stat-ico" style="background:rgba(217,119,6,0.08); color:#d97706;">
          <i class="bi bi-graph-up"></i>
        </div>
        <div>
          <div class="rep-val"><?= $avgPerDay ?></div>
          <div class="rep-lbl">Avg / Day (30d)</div>
        </div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="rep-charts-row">
      <div class="a-card">
        <div class="a-card-header">
          <i class="bi bi-pie-chart"></i> Sessions by Purpose
        </div>
        <div class="a-card-body">
          <div class="rep-chart-wrap">
            <canvas id="purposeChart"></canvas>
          </div>
        </div>
      </div>
      <div class="a-card">
        <div class="a-card-header">
          <i class="bi bi-bar-chart"></i> Sessions by Laboratory
        </div>
        <div class="a-card-body">
          <div class="rep-chart-wrap">
            <canvas id="labChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Trend -->
    <div class="a-card">
      <div class="a-card-header">
        <i class="bi bi-graph-up"></i> Daily Sit-in Trend
        <span style="margin-left:auto; font-size:0.72rem; color:#94a3b8; font-weight:400;">Last 30 days</span>
      </div>
      <div class="a-card-body">
        <div class="rep-trend-wrap">
          <canvas id="dateChart"></canvas>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
Chart.defaults.font.family = 'Outfit';
const palette = ['#1a3a6b','#2563EB','#4988C4','#93c5fd','#1C4D8D','#0891b2','#475569'];

new Chart(document.getElementById('purposeChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($byPurpose,'purpose')) ?>,
    datasets: [{
      data: <?= json_encode(array_column($byPurpose,'cnt')) ?>,
      backgroundColor: palette,
      borderWidth: 3,
      borderColor: '#fff',
      hoverOffset: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    cutout: '55%',
    plugins: {
      legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12, boxWidth: 12 } }
    }
  }
});

new Chart(document.getElementById('labChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byLab,'lab_room')) ?>,
    datasets: [{
      label: 'Sessions',
      data: <?= json_encode(array_column($byLab,'cnt')) ?>,
      backgroundColor: '#2563EB',
      borderRadius: 6,
      barThickness: 32
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
      x: { grid: { display: false } }
    }
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
      backgroundColor: 'rgba(37,99,235,0.06)',
      tension: 0.4,
      fill: true,
      pointRadius: 3,
      pointBackgroundColor: '#2563EB',
      pointBorderColor: '#fff',
      pointBorderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
      x: { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 10 } } }
    }
  }
});
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body>
</html>