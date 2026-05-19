<?php
// FILE: pages/admin/index.php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

/* ── Handle POST actions ── */
$annSuccess = '';
$annError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['post_action'] ?? '';

    /* Post announcement */
    if ($postAction === 'post_announcement') {
        $content = trim($_POST['announcement'] ?? '');
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

    /* Edit announcement */
    if ($postAction === 'edit_announcement') {
        $aid     = (int)($_POST['ann_id'] ?? 0);
        $content = trim($_POST['ann_content'] ?? '');
        if ($aid > 0 && $content) {
            $db->prepare("UPDATE announcements SET content = ? WHERE id = ?")
               ->execute([$content, $aid]);
            $annSuccess = 'Announcement updated.';
        } else {
            $annError = 'Content cannot be empty.';
        }
    }

    /* Delete announcement */
    if ($postAction === 'delete_announcement') {
        $aid = (int)($_POST['ann_id'] ?? 0);
        if ($aid > 0) {
            $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$aid]);
            $annSuccess = 'Announcement deleted.';
        }
    }
}

$totalStudents = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$currentSitIn  = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs WHERE logout_time IS NULL")->fetchColumn();
$totalSitIn    = (int)$db->query("SELECT COUNT(*) FROM sit_in_logs")->fetchColumn();
$totalRes      = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status='pending' AND disabled_by_student = 0")->fetchColumn();

$purposes = $db->query("
    SELECT purpose, COUNT(*) as cnt
    FROM sit_in_logs
    WHERE purpose IS NOT NULL AND purpose != ''
    GROUP BY purpose ORDER BY cnt DESC
")->fetchAll();

$announcements = $db->query(
    "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10"
)->fetchAll();

$recentSitIns = $db->query("
    SELECT s.login_time, u.first_name||' '||u.last_name AS name,
           u.student_id, s.lab_room, s.purpose
    FROM sit_in_logs s
    JOIN users u ON u.id = s.user_id
    WHERE s.logout_time IS NULL
    ORDER BY s.login_time DESC
    LIMIT 6
")->fetchAll();

/* ── Leaderboard: top students by completed sessions ── */
$topStudentsAdmin = $db->query("
    SELECT u.first_name, u.last_name, u.course, u.profile_picture,
           u.points, u.remaining_sessions,
           COUNT(s.id) AS total_sessions
    FROM users u
    LEFT JOIN sit_in_logs s ON s.user_id = u.id AND s.logout_time IS NOT NULL
    GROUP BY u.id
    ORDER BY total_sessions DESC, u.points DESC
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
    .db-stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.75rem; }
    .db-stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.1rem 1.25rem; display: flex; align-items: center; gap: 0.875rem; box-shadow: 0 1px 3px rgba(15,40,84,0.06); transition: box-shadow 0.18s, transform 0.18s; }
    .db-stat-card:hover { box-shadow: 0 4px 16px rgba(15,40,84,0.10); transform: translateY(-2px); }
    .db-stat-ico { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .ico-blue  { background: rgba(37,99,235,0.08);  color: #2563EB; }
    .ico-green { background: rgba(22,163,74,0.08);  color: #16a34a; }
    .ico-amber { background: rgba(217,119,6,0.08);  color: #d97706; }
    .ico-slate { background: rgba(71,85,105,0.08);  color: #475569; }
    .db-stat-val { font-size: 1.75rem; font-weight: 800; color: #1e293b; line-height: 1; margin-bottom: 2px; }
    .db-stat-lbl { font-size: 0.70rem; color: #94a3b8; font-weight: 500; }

    .db-main-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; align-items: start; }
    .db-col-left  { display: flex; flex-direction: column; gap: 1.25rem; }
    .db-col-right { display: flex; flex-direction: column; gap: 1.25rem; }

    .db-chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding: 1.25rem; }
    .db-chart-box  { position: relative; height: 200px; display: flex; align-items: center; justify-content: center; }
    .db-chart-box canvas { max-height: 200px !important; }

    /* Activity */
    .db-activity-list { padding: 0 1.25rem 0.5rem; }
    .db-act-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
    .db-act-item:last-child { border-bottom: none; }
    .db-act-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #1a3a6b, #2563EB); color: #fff; font-size: 0.68rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .db-act-name { font-size: 0.83rem; font-weight: 700; color: #1e293b; margin-bottom: 1px; }
    .db-act-meta { font-size: 0.72rem; color: #94a3b8; }
    .db-act-time { font-size: 0.70rem; color: #94a3b8; flex-shrink: 0; margin-left: auto; }
    .db-empty    { padding: 2rem; text-align: center; color: #94a3b8; font-size: 0.83rem; }
    .db-empty i  { font-size: 1.75rem; display: block; margin-bottom: 0.4rem; opacity: 0.35; }

    /* Quick links */
    .db-quicklinks { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; padding: 1.1rem; }
    .db-ql-item { display: flex; align-items: center; gap: 8px; padding: 0.65rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.80rem; font-weight: 600; color: #1e293b; background: #fff; text-decoration: none; transition: all 0.15s; }
    .db-ql-item:hover { background: #f0f4f8; border-color: #2563EB; color: #2563EB; }
    .db-ql-item i { font-size: 0.9rem; color: #2563EB; }

    /* Announcement form */
    .db-ann-form { padding: 1.1rem 1.25rem; }
    .db-ann-ta { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 0.65rem 0.875rem; font-family: 'Outfit', sans-serif; font-size: 0.875rem; resize: vertical; min-height: 85px; outline: none; transition: border-color 0.18s; color: #1e293b; }
    .db-ann-ta:focus { border-color: #2563EB; }
    .db-ann-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; }
    .db-ann-count { font-size: 0.70rem; color: #94a3b8; }

    /* Announcement list items */
    .db-ann-divider { margin: 0 1.25rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
    .db-ann-heading { font-size: 0.70rem; font-weight: 700; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 0.75rem; }
    .db-ann-list { padding: 0 1.25rem 1.25rem; }
    .db-ann-item { padding: 0.7rem 0; border-bottom: 1px solid #f1f5f9; }
    .db-ann-item:last-child { border-bottom: none; }
    .db-ann-item-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
    .db-ann-date { font-size: 0.68rem; color: #94a3b8; margin-bottom: 0.25rem; }
    .db-ann-text { font-size: 0.82rem; color: #1e293b; line-height: 1.55; flex: 1; }

    /* Announcement action buttons */
    .db-ann-actions { display: flex; gap: 4px; flex-shrink: 0; }
    .db-ann-act-btn {
      background: none; border: 1px solid transparent; cursor: pointer;
      padding: 3px 5px; border-radius: 5px;
      font-size: 0.78rem; transition: all 0.15s;
      display: flex; align-items: center; justify-content: center;
      width: 26px; height: 26px; line-height: 1;
    }
    .db-ann-edit-btn { color: #94a3b8; }
    .db-ann-edit-btn:hover { background: rgba(37,99,235,0.08); color: #2563EB; border-color: rgba(37,99,235,0.20); }
    .db-ann-del-btn  { color: #cbd5e1; }
    .db-ann-del-btn:hover  { background: rgba(220,38,38,0.08); color: #dc2626; border-color: rgba(220,38,38,0.20); }

    /* Welcome */
    .db-welcome { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem; }
    .db-welcome h2 { font-size: 1.3rem; font-weight: 800; color: #1e293b; margin-bottom: 0.15rem; }
    .db-welcome p  { font-size: 0.82rem; color: #94a3b8; }
    .db-date-chip { font-size: 0.78rem; color: #64748b; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.38rem 0.875rem; display: flex; align-items: center; gap: 5px; }
    .db-date-chip i { color: #2563EB; font-size: 0.82rem; }

    /* Edit modal textarea */
    #editAnnContent {
      width: 100%; border: 1.5px solid #e2e8f0; border-radius: 7px;
      padding: 0.65rem 0.875rem; font-family: 'Outfit', sans-serif;
      font-size: 0.875rem; resize: vertical; min-height: 110px;
      outline: none; transition: border-color 0.18s; color: #1e293b;
    }
    #editAnnContent:focus { border-color: #2563EB; }
    .edit-char-hint { font-size: 0.70rem; color: #94a3b8; text-align: right; margin-top: 4px; }

    /* Leaderboard widget */
    .db-lb-list { padding: 0.5rem 1.25rem 1rem; }
    .db-lb-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
    .db-lb-item:last-child { border-bottom: none; }
    .db-lb-rank {
      width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.75rem; font-weight: 800;
    }
    .db-lb-rank.gold   { background: linear-gradient(135deg,#f59e0b,#fbbf24); color: #fff; }
    .db-lb-rank.silver { background: linear-gradient(135deg,#94a3b8,#cbd5e1); color: #fff; }
    .db-lb-rank.bronze { background: linear-gradient(135deg,#d97706,#fbbf24); color: #fff; }
    .db-lb-rank.other  { background: #f1f5f9; color: #64748b; }
    .db-lb-avatar { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg,#1a3a6b,#2563EB); color: #fff; font-size: 0.65rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
    .db-lb-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .db-lb-name { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
    .db-lb-course { font-size: 0.68rem; color: #94a3b8; }
    .db-lb-right { margin-left: auto; text-align: right; flex-shrink: 0; }
    .db-lb-sessions { font-size: 1rem; font-weight: 800; color: #1e293b; line-height: 1; }
    .db-lb-pts { font-size: 0.62rem; color: #4988c4; font-weight: 600; }
    .db-lb-prog { margin-top: 3px; background: #f1f5f9; border-radius: 100px; height: 4px; width: 60px; }
    .db-lb-prog-fill { height: 4px; border-radius: 100px; background: linear-gradient(90deg,#2563EB,#4988c4); }

    @media (max-width: 1100px) { .db-stat-grid { grid-template-columns: repeat(2,1fr); } .db-main-grid { grid-template-columns: 1fr; } .db-col-right { display: grid; grid-template-columns: 1fr 1fr; } }
    @media (max-width: 640px)  { .db-chart-grid { grid-template-columns: 1fr; } .db-col-right { grid-template-columns: 1fr; } .db-stat-grid { grid-template-columns: 1fr 1fr; } }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <div class="db-welcome">
      <div>
        <h2>Admin Dashboard</h2>
        <p>Welcome back. Here's what's happening in the labs.</p>
      </div>
      <div class="db-date-chip">
        <i class="bi bi-calendar3"></i>
        <?= date('l, F j, Y') ?>
      </div>
    </div>

    <!-- Stats -->
    <div class="db-stat-grid">
      <div class="db-stat-card">
        <div class="db-stat-ico ico-blue"><i class="bi bi-people-fill"></i></div>
        <div><div class="db-stat-val"><?= $totalStudents ?></div><div class="db-stat-lbl">Registered Students</div></div>
      </div>
      <div class="db-stat-card">
        <div class="db-stat-ico ico-green"><i class="bi bi-pc-display-horizontal"></i></div>
        <div><div class="db-stat-val"><?= $currentSitIn ?></div><div class="db-stat-lbl">Currently Sit-in</div></div>
      </div>
      <div class="db-stat-card">
        <div class="db-stat-ico ico-amber"><i class="bi bi-clock-history"></i></div>
        <div><div class="db-stat-val"><?= $totalSitIn ?></div><div class="db-stat-lbl">Total Sit-in Sessions</div></div>
      </div>
      <div class="db-stat-card">
        <div class="db-stat-ico ico-slate"><i class="bi bi-calendar-check"></i></div>
        <div><div class="db-stat-val"><?= $totalRes ?></div><div class="db-stat-lbl">Pending Reservations</div></div>
      </div>
    </div>

    <!-- Main grid -->
    <div class="db-main-grid">

      <!-- LEFT -->
      <div class="db-col-left">

        <!-- Charts -->
        <div class="a-card">
          <div class="a-card-header"><i class="bi bi-pie-chart"></i> Session Breakdown by Purpose</div>
          <div class="db-chart-grid">
            <div class="db-chart-box"><canvas id="purposeChart"></canvas></div>
            <div class="db-chart-box"><canvas id="labBarChart"></canvas></div>
          </div>
        </div>

        <!-- Active sit-ins -->
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-activity"></i> Active Sit-in Sessions
            <?php if ($currentSitIn > 0): ?>
              <span class="a-badge badge-active" style="margin-left:auto;"><?= $currentSitIn ?> active</span>
            <?php endif; ?>
          </div>
          <?php if (empty($recentSitIns)): ?>
            <div class="db-empty"><i class="bi bi-inbox"></i>No active sit-in sessions right now.</div>
          <?php else: ?>
            <div class="db-activity-list">
              <?php foreach ($recentSitIns as $si):
                $parts    = explode(' ', $si['name']);
                $initials = strtoupper(substr($parts[0],0,1) . substr($parts[1] ?? '',0,1));
                $elapsed  = round((time() - strtotime($si['login_time'])) / 60);
              ?>
                <div class="db-act-item">
                  <div class="db-act-avatar"><?= $initials ?></div>
                  <div style="flex:1;min-width:0;">
                    <div class="db-act-name"><?= htmlspecialchars($si['name']) ?></div>
                    <div class="db-act-meta"><?= htmlspecialchars($si['student_id']) ?> &middot; Lab <?= htmlspecialchars($si['lab_room']) ?> &middot; <?= htmlspecialchars($si['purpose'] ?? '—') ?></div>
                  </div>
                  <div class="db-act-time"><?= $elapsed < 60 ? $elapsed.'m' : floor($elapsed/60).'h '.($elapsed%60).'m' ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if ($currentSitIn > 6): ?>
              <div style="padding:0.75rem 1.25rem;border-top:1px solid #f1f5f9;text-align:right;">
                <a href="<?= $base ?>pages/admin/sitin.php" style="font-size:0.78rem;color:#2563EB;font-weight:600;text-decoration:none;">View all <?= $currentSitIn ?> sessions <i class="bi bi-arrow-right"></i></a>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <!-- Leaderboard -->
        <div class="a-card">
          <div class="a-card-header"><i class="bi bi-trophy"></i> Top Students 🏆
            <span style="margin-left:auto;font-size:0.70rem;color:#94a3b8;font-weight:400;">By completed sessions</span>
          </div>
          <?php if (empty($topStudentsAdmin)): ?>
            <div class="db-empty"><i class="bi bi-trophy"></i>No session data yet.</div>
          <?php else: ?>
          <div class="db-lb-list">
            <?php foreach ($topStudentsAdmin as $i => $ts):
              $rank = $i + 1;
              $rankClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'other'));
              $rankLabel = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#'.$rank));
              $tsInit = strtoupper(substr($ts['first_name'],0,1).substr($ts['last_name'],0,1));
              $pts = (int)$ts['points'];
              $pctToReward = min(100, round(($pts % 8) / 8 * 100));
            ?>
            <div class="db-lb-item">
              <div class="db-lb-rank <?= $rankClass ?>"><?= $rankLabel ?></div>
              <div class="db-lb-avatar">
                <?php if (!empty($ts['profile_picture'])): ?>
                  <img src="<?= htmlspecialchars($ts['profile_picture']) ?>" alt="">
                <?php else: ?><?= $tsInit ?><?php endif; ?>
              </div>
              <div style="flex:1;min-width:0;">
                <div class="db-lb-name"><?= htmlspecialchars($ts['first_name'].' '.$ts['last_name']) ?></div>
                <div class="db-lb-course"><?= htmlspecialchars($ts['course'] ?? 'CCS') ?></div>
              </div>
              <div class="db-lb-right">
                <div class="db-lb-sessions"><?= (int)$ts['total_sessions'] ?></div>
                <div class="db-lb-pts"><?= $pts ?> pts · <?= 8 - ($pts % 8) ?> to reward</div>
                <div class="db-lb-prog"><div class="db-lb-prog-fill" style="width:<?= $pctToReward ?>%"></div></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- RIGHT -->
      <div class="db-col-right">

        <!-- Quick links -->
        <div class="a-card">
          <div class="a-card-header"><i class="bi bi-grid"></i> Quick Access</div>
          <div class="db-quicklinks">
            <a href="<?= $base ?>pages/admin/sitin.php" class="db-ql-item"><i class="bi bi-pc-display"></i> Sit-in</a>
            <a href="<?= $base ?>pages/admin/students.php" class="db-ql-item"><i class="bi bi-people"></i> Students</a>
            <a href="<?= $base ?>pages/admin/reservation.php" class="db-ql-item"><i class="bi bi-calendar-check"></i> Reservations</a>
            <a href="<?= $base ?>pages/admin/sitin-reports.php" class="db-ql-item"><i class="bi bi-bar-chart-line"></i> Reports</a>
            <a href="<?= $base ?>pages/admin/view-sitin.php" class="db-ql-item"><i class="bi bi-table"></i> Records</a>
            <a href="<?= $base ?>pages/admin/feedback.php" class="db-ql-item"><i class="bi bi-chat-square-text"></i> Feedback</a>
            <a href="<?= $base ?>pages/admin/pc-control.php" class="db-ql-item"><i class="bi bi-display"></i> PC Control</a>
            <a href="<?= $base ?>pages/admin/testimonials.php" class="db-ql-item"><i class="bi bi-chat-heart"></i> Testimonials</a>
            <a href="<?= $base ?>pages/admin/software.php" class="db-ql-item"><i class="bi bi-cpu"></i> Software</a>
          </div>
        </div>

        <!-- Announcement form -->
        <div class="a-card">
          <div class="a-card-header"><i class="bi bi-megaphone"></i> Post Announcement</div>
          <div class="db-ann-form">
            <form method="POST" action="" id="annPostForm">
              <input type="hidden" name="post_action" value="post_announcement">
              <textarea
                name="announcement"
                class="db-ann-ta"
                placeholder="Write an announcement for all students..."
                maxlength="500"
                id="annTextarea"
                oninput="document.getElementById('annCount').textContent = this.value.length"
              ></textarea>
              <div class="db-ann-meta">
                <span class="db-ann-count"><span id="annCount">0</span> / 500</span>
                <button type="submit" class="a-btn a-btn-primary">
                  <i class="bi bi-send"></i> Post
                </button>
              </div>
            </form>
          </div>

          <!-- Posted announcements -->
          <div class="db-ann-divider">
            <div class="db-ann-heading">Recent Announcements</div>
          </div>

          <?php if (empty($announcements)): ?>
            <div class="db-empty" style="padding:1.25rem;">
              <i class="bi bi-megaphone"></i>No announcements posted yet.
            </div>
          <?php else: ?>
            <div class="db-ann-list">
              <?php foreach ($announcements as $ann): ?>
                <div class="db-ann-item">
                  <div class="db-ann-item-top">
                    <div style="flex:1;">
                      <div class="db-ann-date">
                        <i class="bi bi-clock" style="font-size:0.65rem;"></i>
                        <?= date('M j, Y · g:i A', strtotime($ann['created_at'])) ?>
                      </div>
                      <div class="db-ann-text"><?= nl2br(htmlspecialchars($ann['content'])) ?></div>
                    </div>
                    <!-- Action buttons -->
                    <div class="db-ann-actions">
                      <!-- Edit -->
                      <button type="button" class="db-ann-act-btn db-ann-edit-btn"
                              title="Edit announcement"
                              onclick="openEditAnn(<?= (int)$ann['id'] ?>, <?= htmlspecialchars(json_encode($ann['content']), ENT_QUOTES) ?>)">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <!-- Delete -->
                      <form method="POST" action="" style="margin:0;"
                            onsubmit="return confirm('Delete this announcement?')">
                        <input type="hidden" name="post_action" value="delete_announcement">
                        <input type="hidden" name="ann_id" value="<?= (int)$ann['id'] ?>">
                        <button type="submit" class="db-ann-act-btn db-ann-del-btn" title="Delete announcement">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </form>
                    </div>
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

<!-- ══════════════════════════════════════
     EDIT ANNOUNCEMENT MODAL
══════════════════════════════════════ -->
<div class="a-modal-overlay" id="editAnnModal">
  <div class="a-modal">
    <div class="a-modal-header">
      <span><i class="bi bi-pencil-square" style="margin-right:5px;color:#2563EB;font-size:0.85rem;"></i>Edit Announcement</span>
      <button class="a-modal-close" onclick="closeEditAnn()">
        <i class="bi bi-x-lg" style="font-size:0.7rem;"></i>
      </button>
    </div>
    <form method="POST" action="" id="editAnnForm">
      <input type="hidden" name="post_action" value="edit_announcement">
      <input type="hidden" name="ann_id" id="editAnnId">
      <div class="a-modal-body">
        <label style="font-size:0.80rem;font-weight:600;color:#1e293b;display:block;margin-bottom:6px;">
          Announcement Content
        </label>
        <textarea
          id="editAnnContent"
          name="ann_content"
          maxlength="500"
          placeholder="Edit announcement..."
          required
          oninput="document.getElementById('editCharCount').textContent = this.value.length"
        ></textarea>
        <div class="edit-char-hint"><span id="editCharCount">0</span> / 500</div>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" onclick="closeEditAnn()">Cancel</button>
        <button type="submit" class="a-btn a-btn-primary">
          <i class="bi bi-floppy"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= $base ?>assets/js/toast.js"></script>
<script>
Chart.defaults.font.family = 'Outfit';
const purposes = <?= json_encode(array_column($purposes,'purpose')) ?>;
const counts   = <?= json_encode(array_column($purposes,'cnt')) ?>;
const palette  = ['#1a3a6b','#2563EB','#4988C4','#93c5fd','#1C4D8D','#0891b2','#475569'];
const demoLabels = ['C#','C','Java','ASP.Net','PHP'];
const demoData   = [1,1,1,1,1];

new Chart(document.getElementById('purposeChart'), {
  type: 'doughnut',
  data: {
    labels: purposes.length ? purposes : demoLabels,
    datasets: [{ data: purposes.length ? counts : demoData, backgroundColor: palette, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
  },
  options: { responsive: true, maintainAspectRatio: true, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 10, boxWidth: 10 } } } }
});

new Chart(document.getElementById('labBarChart'), {
  type: 'bar',
  data: {
    labels: purposes.length ? purposes : demoLabels,
    datasets: [{ label: 'Sessions', data: purposes.length ? counts : demoData, backgroundColor: '#2563EB', borderRadius: 5, barThickness: 18 }]
  },
  options: {
    responsive: true, maintainAspectRatio: true, indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } }, grid: { color: '#f1f5f9' } },
      y: { ticks: { font: { size: 10 } }, grid: { display: false } }
    }
  }
});

/* ── Edit Announcement Modal ── */
function openEditAnn(id, content) {
  document.getElementById('editAnnId').value       = id;
  document.getElementById('editAnnContent').value  = content;
  document.getElementById('editCharCount').textContent = content.length;
  document.getElementById('editAnnModal').classList.add('open');
}
function closeEditAnn() {
  document.getElementById('editAnnModal').classList.remove('open');
}
document.getElementById('editAnnModal')?.addEventListener('click', e => {
  if (e.target.id === 'editAnnModal') closeEditAnn();
});

/* ── PHP flash → Toast ── */
<?php if ($annSuccess): ?>
  document.addEventListener('DOMContentLoaded', () => {
    Toast.success(<?= json_encode($annSuccess) ?>);
  });
<?php elseif ($annError): ?>
  document.addEventListener('DOMContentLoaded', () => {
    Toast.error(<?= json_encode($annError) ?>);
  });
<?php endif; ?>
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
</body>
</html>