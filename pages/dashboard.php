<?php
session_start();
$base = '../';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireUser();          // Redirects if not logged in
$db   = getDB();

// Refresh user from DB to get latest data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;      // Keep session in sync

// Fetch announcements (latest 5)
$announcements = $db->query(
    "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// Year level display map
$yearMap = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year',
            '4' => '4th Year', '5' => '5th Year'];

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<div class="user-page">
  <div class="user-page-inner">

    <div class="page-heading">
      <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?>! 👋</h1>
      <p>Here's your overview for today.</p>
    </div>

    <div class="dashboard-grid">

      <!-- ======== LEFT: Student Info Card ======== -->
      <aside>
        <div class="student-card">
          <div class="student-card-avatar">
            <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
          </div>

          <div class="student-card-name">
            <?= htmlspecialchars($user['first_name'] . ' ' .
                ($user['middle_name'] ? $user['middle_name'][0] . '. ' : '') .
                $user['last_name']) ?>
          </div>
          <div class="student-card-id"><?= htmlspecialchars($user['student_id']) ?></div>

          <div class="student-card-divider"></div>

          <div class="student-info-list">
            <div class="student-info-item">
              <span class="info-label">Course</span>
              <span class="info-value"><?= htmlspecialchars($user['course']) ?></span>
            </div>
            <div class="student-info-item">
              <span class="info-label">Year Level</span>
              <span class="info-value"><?= $yearMap[$user['course_level']] ?? $user['course_level'] ?></span>
            </div>
            <div class="student-info-item">
              <span class="info-label">Email</span>
              <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="student-info-item">
              <span class="info-label">Address</span>
              <span class="info-value"><?= htmlspecialchars($user['address']) ?></span>
            </div>
          </div>

          <div class="student-card-footer">
            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
          </div>
        </div>

        <!-- Sessions Available -->
        <div style="
          margin-top: 1rem;
          background: var(--white);
          border-radius: var(--radius-lg);
          border: 1px solid rgba(15,40,84,0.08);
          box-shadow: var(--shadow-sm);
          padding: 1.25rem 1.75rem;
          text-align: center;
        ">
          <div style="font-size:0.72rem; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--mid); margin-bottom:0.5rem;">
            Sessions Available
          </div>
          <div style="font-family:var(--font-display); font-size:2.8rem; font-weight:800; color:var(--navy); line-height:1;">
            <?= (int)($user['remaining_sessions'] ?? 30) ?>
          </div>
          <div style="font-size:0.78rem; color:var(--gray-400); margin-top:0.35rem;">
            out of 30 total
          </div>
          <!-- Progress bar -->
          <?php $pct = min(100, round((($user['remaining_sessions'] ?? 30) / 30) * 100)); ?>
          <div style="margin-top:0.85rem; background:#e2e8f0; border-radius:100px; height:8px; overflow:hidden;">
            <div style="
              width:<?= $pct ?>%;
              height:100%;
              background: <?= $pct > 50 ? 'var(--mid)' : ($pct > 20 ? '#d97706' : '#dc2626') ?>;
              border-radius:100px;
              transition: width 0.5s ease;
            "></div>
          </div>
        </div>

      </aside>

      <!-- ======== RIGHT: Announcements + Rules ======== -->
      <div class="dashboard-right">

        <!-- Announcements -->
        <div class="content-card reveal">
          <div class="content-card-header">
            <span class="card-icon">📢</span>
            <h2>Announcements</h2>
          </div>
          <div class="content-card-body">
            <?php if (empty($announcements)): ?>
              <p style="color:var(--gray-400); font-size:0.88rem;">No announcements at this time.</p>
            <?php else: ?>
              <div class="announcement-list">
                <?php foreach ($announcements as $ann): ?>
                  <div class="announcement-item">
                    <h4><?= htmlspecialchars($ann['title']) ?></h4>
                    <p><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                    <div class="announcement-meta">
                      📅 <?= date('F j, Y', strtotime($ann['created_at'])) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Rules & Regulations -->
        <div class="content-card reveal">
          <div class="content-card-header">
            <span class="card-icon">📜</span>
            <h2>Laboratory Rules &amp; Regulations</h2>
          </div>
          <div class="content-card-body">

            <div style="text-align:center; margin-bottom:1.25rem;">
              <div style="font-size:0.9rem; font-weight:700; color:var(--navy);">University of Cebu</div>
              <div style="font-size:0.85rem; font-weight:700; color:var(--navy);">COLLEGE OF INFORMATION &amp; COMPUTER STUDIES</div>
              <div style="font-size:0.82rem; font-weight:700; color:var(--mid); margin-top:2px; letter-spacing:0.3px;">LABORATORY RULES AND REGULATIONS</div>
              <div style="font-size:0.8rem; color:var(--gray-600); margin-top:0.5rem; font-weight:300;">
                To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:
              </div>
            </div>

            <div class="rules-list">

              <?php $rules = [
                '1' => 'Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.',
                '2' => 'Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.',
                '3' => 'Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.',
                '4' => 'Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.',
                '5' => 'Deleting computer files and changing the set-up of the computer is a major offense.',
                '6' => 'Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".',
                '8' => 'Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.',
                '9' => 'Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.',
                '10'=> 'Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.',
                '11'=> 'For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.',
                '12'=> 'Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.',
              ]; ?>

              <?php foreach ($rules as $num => $text): ?>
                <div class="rule-item">
                  <div class="rule-number"><?= $num ?></div>
                  <p class="rule-text"><?= htmlspecialchars($text) ?></p>
                </div>
              <?php endforeach; ?>

              <!-- Rule 7 with sub-bullets -->
              <div class="rule-item" style="flex-direction:column; align-items:flex-start; gap:0.5rem;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                  <div class="rule-number">7</div>
                  <p class="rule-text">Observe proper decorum while inside the laboratory.</p>
                </div>
                <ul style="margin-left:2.75rem; display:flex; flex-direction:column; gap:0.3rem;">
                  <?php foreach ([
                    'Do not get inside the lab unless the instructor is present.',
                    'All bags, knapsacks, and the likes must be deposited at the counter.',
                    'Follow the seating arrangement of your instructor.',
                    'At the end of class, all software programs must be closed.',
                    'Return all chairs to their proper places after using.',
                  ] as $sub): ?>
                    <li style="font-size:0.875rem; color:var(--gray-600); font-weight:300; list-style:disc;">
                      <?= htmlspecialchars($sub) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

            </div>

            <!-- Disciplinary Action -->
            <div style="
              margin-top:1.5rem;
              background:rgba(239,68,68,0.05);
              border:1px solid rgba(239,68,68,0.15);
              border-radius:10px;
              padding:1.1rem 1.25rem;
            ">
              <div style="font-size:0.78rem; font-weight:800; letter-spacing:1.2px; text-transform:uppercase; color:#991b1b; margin-bottom:0.75rem;">
                ⚠️ Disciplinary Action
              </div>
              <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <div style="display:flex; gap:10px; align-items:flex-start;">
                  <div style="min-width:14px; min-height:14px; width:14px; height:14px; border-radius:50%; background:#dc2626; margin-top:3px; flex-shrink:0;"></div>
                  <p style="font-size:0.875rem; color:var(--gray-600); font-weight:300;">
                    <strong style="color:var(--navy);">First Offense</strong> — The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.
                  </p>
                </div>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                  <div style="min-width:14px; min-height:14px; width:14px; height:14px; border-radius:50%; background:#991b1b; margin-top:3px; flex-shrink:0;"></div>
                  <p style="font-size:0.875rem; color:var(--gray-600); font-weight:300;">
                    <strong style="color:var(--navy);">Second and Subsequent Offenses</strong> — A recommendation for a heavier sanction will be endorsed to the Guidance Center.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div><!-- /.dashboard-right -->
    </div><!-- /.dashboard-grid -->
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>