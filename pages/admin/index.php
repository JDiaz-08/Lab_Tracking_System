<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$annSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
    $content = trim($_POST['announcement']);
    if ($content) {
        $db->prepare("INSERT INTO announcements (title,content) VALUES (?,?)")
           ->execute(['CCS Admin', $content]);
        $users = $db->query("SELECT id FROM users")->fetchAll();
        $ins   = $db->prepare("INSERT INTO notifications (user_id,message) VALUES (?,?)");
        foreach ($users as $u)
            $ins->execute([$u['id'], "New announcement: " . mb_substr($content,0,80) . (strlen($content)>80?'…':'')]);
        $annSuccess = 'Announcement posted successfully.';
    }
}

$totalStudents = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$currentSitIn  = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs WHERE logout_time IS NULL")->fetchColumn();
$totalSitIn    = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs")->fetchColumn();
$totalRes      = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetchColumn();

$purposes = $db->query("
    SELECT purpose, COUNT(*) as cnt
    FROM sit_in_logs
    WHERE purpose IS NOT NULL AND purpose != ''
    GROUP BY purpose ORDER BY cnt DESC
")->fetchAll();

$announcements = $db->query(
    "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

// Recent sit-ins for activity feed
$recentSitIns = $db->query("
    SELECT s.login_time, u.first_name||' '||u.last_name AS name,
           u.student_id, s.lab_room, s.purpose
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    WHERE s.logout_time IS NULL
    ORDER BY s.login_time DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin Dashboard — UC CompLab</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <style>
    /* ---- Page-level overrides ---- */
    .dash-welcome {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.75rem;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    .dash-welcome-text h2 {
      font-size: 1.4rem;
      font-weight: 800;
      color: #1e293b;
      margin-bottom: 0.2rem;
      letter-spacing: -0.2px;
    }
    .dash-welcome-text p {
      font-size: 0.84rem;
      color: var(--a-gray400);
    }
    .dash-date {
      font-size: 0.80rem;
      color: var(--a-gray400);
      background: var(--a-white);
      border: 1px solid var(--a-gray200);
      border-radius: 6px;
      padding: 0.38rem 0.8rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .dash-date i { color: var(--a-mid); font-size: 0.85rem; }

    /* Summary cards */
    .dash-summary {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.75rem;
    }
    .dash-stat {
      background: var(--a-white);
      border: 1px solid var(--a-gray200);
      border-radius: var(--radius);
      padding: 1.1rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.875rem;
      box-shadow: var(--shadow-xs);
      transition: box-shadow 0.18s, transform 0.18s;
    }
    .dash-stat:hover {
      box-shadow: var(--shadow);
      transform: translateY(-2px);
    }
    .dash-stat-icon {
      width: 42px; height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    .dsi-blue   { background: rgba(37,99,235,0.10);  color: #2563EB; }
    .dsi-green  { background: rgba(22,163,74,0.10);  color: #16a34a; }
    .dsi-yellow { background: rgba(217,119,6,0.10);  color: #d97706; }
    .dsi-slate  { background: rgba(71,85,105,0.10);  color: #475569; }
    .dash-stat-body {}
    .dash-stat-val {
      font-size: 1.75rem;
      font-weight: 800;
      color: #1e293b;
      line-height: 1;
      margin-bottom: 3px;
    }
    .dash-stat-label {
      font-size: 0.72rem;
      color: var(--a-gray400);
      font-weight: 500;
      letter-spacing: 0.15px;
    }

    /* Main grid */
    .dash-main {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 1.5rem;
      align-items: start;
    }
    .dash-left  { display: flex; flex-direction: column; gap: 1.5rem; }
    .dash-right { display: flex; flex-direction: column; gap: 1.5rem; }

    /* Chart card */
    .chart-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    /* Activity feed */
    .activity-feed { display: flex; flex-direction: column; }
    .activity-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--a-gray100);
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-avatar {
      width: 34px; height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1a3a6b, #2563EB);
      color: #fff;
      font-size: 0.70rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .activity-info { flex: 1; min-width: 0; }
    .activity-name {
      font-size: 0.83rem;
      font-weight: 700;
      color: #1e293b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 1px;
    }
    .activity-meta {
      font-size: 0.72rem;
      color: var(--a-gray400);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .activity-time {
      font-size: 0.70rem;
      color: var(--a-gray400);
      flex-shrink: 0;
      text-align: right;
    }
    .activity-empty {
      padding: 1.5rem 0;
      text-align: center;
      color: var(--a-gray400);
      font-size: 0.83rem;
    }
    .activity-empty i { font-size: 1.5rem; display: block; margin-bottom: 0.4rem; opacity: 0.4; }

    /* Announcement form */
    .ann-form-wrap { display: flex; flex-direction: column; gap: 0.65rem; }
    .ann-char-hint {
      font-size: 0.72rem;
      color: var(--a-gray400);
      text-align: right;
    }

    /* Posted announcements */
    .ann-posted-list { display: flex; flex-direction: column; }
    .ann-posted-item {
      padding: 0.75rem 0;
      border-bottom: 1px solid var(--a-gray100);
    }
    .ann-posted-item:last-child { border-bottom: none; }
    .ann-posted-meta {
      font-size: 0.70rem;
      color: var(--a-gray400);
      margin-bottom: 0.25rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .ann-posted-meta i { font-size: 0.72rem; }
    .ann-posted-content {
      font-size: 0.83rem;
      color: #1e293b;
      line-height: 1.55;
    }
    .ann-empty {
      padding: 1.25rem 0;
      text-align: center;
      color: var(--a-gray400);
      font-size: 0.83rem;
    }
    .ann-empty i { font-size: 1.5rem; display: block; margin-bottom: 0.35rem; opacity: 0.4; }

    /* Quick links */
    .quick-links {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.6rem;
    }
    .quick-link-item {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0.65rem 0.875rem;
      border: 1px solid var(--a-gray200);
      border-radius: 7px;
      font-size: 0.80rem;
      font-weight: 600;
      color: #1e293b;
      background: var(--a-white);
      text-decoration: none;
      transition: all 0.15s;
    }
    .quick-link-item:hover {
      background: #f0f4f8;
      border-color: #2563EB;
      color: #2563EB;
    }
    .quick-link-item i {
      font-size: 0.9rem;
      color: #2563EB;
      flex-shrink: 0;
    }

    @media (max-width: 1100px) {
      .dash-summary { grid-template-columns: repeat(2,1fr); }
      .dash-main    { grid-template-columns: 1fr; }
      .dash-right   { display: grid; grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
      .dash-summary { grid-template-columns: 1fr 1fr; }
      .chart-grid   { grid-template-columns: 1fr; }
      .dash-right   { grid-template-columns: 1fr; }
      .quick-links  { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <!-- Welcome bar -->
    <div class="dash-welcome">
      <div class="dash-welcome-text">
        <h2>Admin Dashboard</h2>
        <p>Welcome back. Here's what's happening in the labs today.</p>
      </div>
      <div class="dash-date">
        <i class="bi bi-calendar3"></i>
        <?= date('l, F j, Y') ?>
      </div>
    </div>

    <?php if ($annSuccess): ?>
      <div class="a-flash a-flash-success" style="margin-bottom:1.25rem;">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($annSuccess) ?>
      </div>
    <?php endif; ?>

    <!-- Summary stats -->
    <div class="dash-summary">
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-blue"><i class="bi bi-people-fill"></i></div>
        <div class="dash-stat-body">
          <div class="dash-stat-val"><?= $totalStudents ?></div>
          <div class="dash-stat-label">Registered Students</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-green"><i class="bi bi-pc-display-horizontal"></i></div>
        <div class="dash-stat-body">
          <div class="dash-stat-val"><?= $currentSitIn ?></div>
          <div class="dash-stat-label">Currently Sit-in</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-yellow"><i class="bi bi-clock-history"></i></div>
        <div class="dash-stat-body">
          <div class="dash-stat-val"><?= $totalSitIn ?></div>
          <div class="dash-stat-label">Total Sit-in Sessions</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-slate"><i class="bi bi-calendar-check"></i></div>
        <div class="dash-stat-body">
          <div class="dash-stat-val"><?= $totalRes ?></div>
          <div class="dash-stat-label">Pending Reservations</div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div class="dash-main">

      <!-- LEFT -->
      <div class="dash-left">

        <!-- Charts -->
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-pie-chart"></i> Session Breakdown by Purpose
          </div>
          <div class="a-card-body">
            <div class="chart-grid">
              <div style="position:relative; max-height:220px;">
                <canvas id="purposeChart"></canvas>
              </div>
              <div style="position:relative; max-height:220px;">
                <canvas id="labBarChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Active sit-ins feed -->
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-activity"></i> Active Sit-in Sessions
            <?php if ($currentSitIn > 0): ?>
              <span style="margin-left:auto; background:rgba(22,163,74,0.10); color:#15803d; border:1px solid rgba(22,163,74,0.22); font-size:0.70rem; font-weight:700; padding:2px 8px; border-radius:100px;">
                <?= $currentSitIn ?> active
              </span>
            <?php endif; ?>
          </div>
          <div class="a-card-body" style="padding-top:0.25rem; padding-bottom:0.25rem;">
            <?php if (empty($recentSitIns)): ?>
              <div class="activity-empty">
                <i class="bi bi-inbox"></i>
                No active sit-in sessions right now.
              </div>
            <?php else: ?>
              <div class="activity-feed">
                <?php foreach ($recentSitIns as $si):
                  $initials = strtoupper(substr(explode(' ',$si['name'])[0],0,1) . substr(explode(' ',$si['name'])[1] ?? '',0,1));
                  $elapsed  = round((time() - strtotime($si['login_time'])) / 60);
                ?>
                  <div class="activity-item">
                    <div class="activity-avatar"><?= $initials ?></div>
                    <div class="activity-info">
                      <div class="activity-name"><?= htmlspecialchars($si['name']) ?></div>
                      <div class="activity-meta">
                        <?= htmlspecialchars($si['student_id']) ?> &middot;
                        Lab <?= htmlspecialchars($si['lab_room']) ?> &middot;
                        <?= htmlspecialchars($si['purpose'] ?? '—') ?>
                      </div>
                    </div>
                    <div class="activity-time">
                      <?= $elapsed < 60 ? $elapsed.'m' : floor($elapsed/60).'h '.($elapsed%60).'m' ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($currentSitIn > 5): ?>
                <div style="padding:0.6rem 0 0.1rem; text-align:right;">
                  <a href="<?= $base ?>pages/admin/sitin.php" style="font-size:0.78rem; color:#2563EB; font-weight:600; text-decoration:none;">
                    View all <?= $currentSitIn ?> sessions <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- RIGHT -->
      <div class="dash-right">

        <!-- Quick links -->
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-grid"></i> Quick Access
          </div>
          <div class="a-card-body">
            <div class="quick-links">
              <a href="<?= $base ?>pages/admin/sitin.php" class="quick-link-item">
                <i class="bi bi-pc-display"></i> Sit-in
              </a>
              <a href="<?= $base ?>pages/admin/students.php" class="quick-link-item">
                <i class="bi bi-people"></i> Students
              </a>
              <a href="<?= $base ?>pages/admin/reservation.php" class="quick-link-item">
                <i class="bi bi-calendar-check"></i> Reservations
              </a>
              <a href="<?= $base ?>pages/admin/sitin-reports.php" class="quick-link-item">
                <i class="bi bi-bar-chart-line"></i> Reports
              </a>
              <a href="<?= $base ?>pages/admin/view-sitin.php" class="quick-link-item">
                <i class="bi bi-table"></i> Records
              </a>
              <a href="<?= $base ?>pages/admin/feedback.php" class="quick-link-item">
                <i class="bi bi-chat-square-text"></i> Feedback
              </a>
            </div>
          </div>
        </div>

        <!-- Announcement form -->
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-megaphone"></i> Post Announcement
          </div>
          <div class="a-card-body">
            <form method="POST" action="" id="annForm">
              <div class="ann-form-wrap">
                <textarea
                  name="announcement"
                  id="annText"
                  class="a-ann-textarea"
                  placeholder="Write an announcement for all students..."
                  maxlength="500"
                  style="min-height:90px; margin-bottom:0;"
                  oninput="document.getElementById('annCount').textContent = this.value.length"
                ></textarea>
                <div style="display:flex; align-items:center; justify-content:space-between;">
                  <span class="ann-char-hint"><span id="annCount">0</span> / 500</span>
                  <button type="submit" class="a-btn a-btn-primary">
                    <i class="bi bi-send"></i> Post
                  </button>
                </div>
              </div>
            </form>

            <!-- Posted list -->
            <div style="margin-top:1.1rem; padding-top:1.1rem; border-top:1px solid var(--a-gray200);">
              <div style="font-size:0.78rem; font-weight:700; color:#1e293b; margin-bottom:0.6rem; display:flex; align-items:center; gap:6px;">
                <i class="bi bi-clock-history" style="color:var(--a-gray400); font-size:0.80rem;"></i>
                Recent Announcements
              </div>
              <?php if (empty($announcements)): ?>
                <div class="ann-empty">
                  <i class="bi bi-megaphone"></i>
                  No announcements posted yet.
                </div>
              <?php else: ?>
                <div class="ann-posted-list">
                  <?php foreach ($announcements as $ann): ?>
                    <div class="ann-posted-item">
                      <div class="ann-posted-meta">
                        <i class="bi bi-person-circle"></i>
                        CCS Admin &nbsp;&middot;&nbsp;
                        <?= date('M j, Y · g:i A', strtotime($ann['created_at'])) ?>
                      </div>
                      <div class="ann-posted-content">
                        <?= nl2br(htmlspecialchars($ann['content'])) ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
/* ---- Charts ---- */
const purposes = <?= json_encode(array_column($purposes,'purpose')) ?>;
const counts   = <?= json_encode(array_column($purposes,'cnt')) ?>;
const palette  = ['#1a3a6b','#2563EB','#4988C4','#93c5fd','#1C4D8D','#0891b2','#475569'];

const demoLabels = ['C#','C','Java','ASP.Net','PHP'];
const demoData   = [1,1,1,1,1];

new Chart(document.getElementById('purposeChart'), {
  type: 'doughnut',
  data: {
    labels: purposes.length ? purposes : demoLabels,
    datasets: [{
      data:            purposes.length ? counts : demoData,
      backgroundColor: palette,
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 6
    }]
  },
  options: {
    responsive: true,
    cutout: '60%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: { font: { family:'Outfit', size:10 }, padding: 10 }
      }
    }
  }
});

/* Lab bar chart — use real data if any sit-ins exist, else demo */
const labData = <?= json_encode($purposes) ?>;
const labLabels = labData.length
  ? <?= json_encode(array_column($purposes,'purpose')) ?>
  : demoLabels;
const labCounts = labData.length
  ? <?= json_encode(array_column($purposes,'cnt')) ?>
  : demoData;

new Chart(document.getElementById('labBarChart'), {
  type: 'bar',
  data: {
    labels: labLabels,
    datasets: [{
      label: 'Sessions',
      data: labCounts,
      backgroundColor: '#2563EB',
      borderRadius: 5,
      barThickness: 22
    }]
  },
  options: {
    responsive: true,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { stepSize: 1, font: { family:'Outfit', size: 10 } } },
      y: { ticks: { font: { family:'Outfit', size: 10 } } }
    }
  }
});
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body>
</html>