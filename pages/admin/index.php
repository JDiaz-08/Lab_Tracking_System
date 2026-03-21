<?php
// FILE: pages/admin/index.php
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
      <div class="a-flash a-flash-success">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($annSuccess) ?>
      </div>
    <?php endif; ?>

    <!-- Summary stats -->
    <div class="dash-summary">
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-blue"><i class="bi bi-people-fill"></i></div>
        <div>
          <div class="dash-stat-val"><?= $totalStudents ?></div>
          <div class="dash-stat-label">Registered Students</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-green"><i class="bi bi-pc-display-horizontal"></i></div>
        <div>
          <div class="dash-stat-val"><?= $currentSitIn ?></div>
          <div class="dash-stat-label">Currently Sit-in</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-yellow"><i class="bi bi-clock-history"></i></div>
        <div>
          <div class="dash-stat-val"><?= $totalSitIn ?></div>
          <div class="dash-stat-label">Total Sit-in Sessions</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon dsi-slate"><i class="bi bi-calendar-check"></i></div>
        <div>
          <div class="dash-stat-val"><?= $totalRes ?></div>
          <div class="dash-stat-label">Pending Reservations</div>
        </div>
      </div>
    </div>

    <!-- Main grid -->
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
              <div><canvas id="purposeChart"></canvas></div>
              <div><canvas id="labBarChart"></canvas></div>
            </div>
          </div>
        </div>

        <!-- Active sit-ins feed -->
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-activity"></i> Active Sit-in Sessions
            <?php if ($currentSitIn > 0): ?>
              <span class="a-badge badge-active" style="margin-left:auto;">
                <?= $currentSitIn ?> active
              </span>
            <?php endif; ?>
          </div>
          <div class="a-card-body">
            <?php if (empty($recentSitIns)): ?>
              <div class="activity-empty">
                <i class="bi bi-inbox"></i>
                No active sit-in sessions right now.
              </div>
            <?php else: ?>
              <div class="activity-feed">
                <?php foreach ($recentSitIns as $si):
                  $parts    = explode(' ', $si['name']);
                  $initials = strtoupper(substr($parts[0],0,1) . substr($parts[1] ?? '',0,1));
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
                <div style="padding-top:0.65rem; text-align:right;">
                  <a href="<?= $base ?>pages/admin/sitin.php" class="activity-more-link">
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
            <form method="POST" action="">
              <textarea
                name="announcement"
                class="a-ann-textarea"
                placeholder="Write an announcement for all students..."
                maxlength="500"
                oninput="document.getElementById('annCount').textContent = this.value.length"
              ></textarea>
              <div class="ann-form-row">
                <span class="ann-char-hint"><span id="annCount">0</span> / 500</span>
                <button type="submit" class="a-btn a-btn-primary">
                  <i class="bi bi-send"></i> Post
                </button>
              </div>
            </form>

            <div class="ann-posted-divider">
              <div class="ann-posted-heading">
                <i class="bi bi-clock-history"></i> Recent Announcements
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
      data: purposes.length ? counts : demoData,
      backgroundColor: palette, borderWidth: 2, borderColor: '#fff', hoverOffset: 6
    }]
  },
  options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { family:'Outfit',size:10 }, padding:10 } } } }
});

new Chart(document.getElementById('labBarChart'), {
  type: 'bar',
  data: {
    labels: purposes.length ? purposes : demoLabels,
    datasets: [{ label:'Sessions', data: purposes.length ? counts : demoData, backgroundColor:'#2563EB', borderRadius:5, barThickness:22 }]
  },
  options: {
    responsive: true, indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero:true, ticks:{ stepSize:1, font:{family:'Outfit',size:10} } },
      y: { ticks:{ font:{family:'Outfit',size:10} } }
    }
  }
});
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body>
</html>