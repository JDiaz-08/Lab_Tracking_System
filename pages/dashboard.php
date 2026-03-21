<?php
// FILE: pages/dashboard.php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireUser();
$db   = getDB();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

$announcements = $db->query(
    "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$activeSitInStmt = $db->prepare(
    "SELECT * FROM sit_in_logs WHERE user_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1"
);
$activeSitInStmt->execute([$user['id']]);
$activeSitIn = $activeSitInStmt->fetch();

$yearMap   = ['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year','5'=>'5th Year'];
$remaining = (int)($user['remaining_sessions'] ?? 30);
$pct       = min(100, round(($remaining / 30) * 100));
$barColor  = $pct > 50 ? 'var(--mid)' : ($pct > 20 ? '#d97706' : '#dc2626');

$rules = [
    '1'  => 'Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.',
    '2'  => 'Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.',
    '3'  => 'Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.',
    '4'  => 'Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.',
    '5'  => 'Deleting computer files and changing the set-up of the computer is a major offense.',
    '6'  => 'Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".',
    '8'  => 'Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.',
    '9'  => 'Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.',
    '10' => 'Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.',
    '11' => 'For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.',
    '12' => 'Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.',
];
$ruleSubs = [
    'Do not get inside the lab unless the instructor is present.',
    'All bags, knapsacks, and the likes must be deposited at the counter.',
    'Follow the seating arrangement of your instructor.',
    'At the end of class, all software programs must be closed.',
    'Return all chairs to their proper places after using.',
];

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<div class="user-page">
  <div class="user-page-inner">

    <div class="page-heading">
      <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?></h1>
      <p><?= date('l, F j, Y') ?></p>
    </div>

    <?php if ($activeSitIn): ?>
      <?php $elapsed = round((time() - strtotime($activeSitIn['login_time'])) / 60); ?>
      <div class="db-active-banner">
        <div class="db-active-dot"></div>
        <div class="db-active-info">
          <div class="db-active-title">Active Sit-in Session</div>
          <div class="db-active-meta">
            Lab <?= htmlspecialchars($activeSitIn['lab_room']) ?> &middot;
            <?= htmlspecialchars($activeSitIn['purpose'] ?? '—') ?> &middot;
            <?= $elapsed < 60 ? $elapsed.'m elapsed' : floor($elapsed/60).'h '.($elapsed%60).'m elapsed' ?>
          </div>
        </div>
        <a href="history.php" class="db-active-link">
          View <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    <?php endif; ?>

    <div class="db-wrap">

      <!-- SIDEBAR -->
      <div class="db-sidebar">

        <div class="db-profile-card">
          <div class="db-profile-top">
            <div class="db-avatar">
              <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile" />
              <?php else: ?>
                <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
              <?php endif; ?>
            </div>
            <div class="db-profile-name">
              <?= htmlspecialchars(
                $user['first_name'] . ' ' .
                ($user['middle_name'] ? $user['middle_name'][0].'. ' : '') .
                $user['last_name']
              ) ?>
            </div>
            <div class="db-profile-id"><?= htmlspecialchars($user['student_id']) ?></div>
          </div>

          <div class="db-profile-info">
            <div class="db-info-row">
              <div class="db-info-icon"><i class="bi bi-mortarboard"></i></div>
              <div>
                <div class="db-info-label">Course</div>
                <div class="db-info-val"><?= htmlspecialchars($user['course']) ?></div>
              </div>
            </div>
            <div class="db-info-row">
              <div class="db-info-icon"><i class="bi bi-layers"></i></div>
              <div>
                <div class="db-info-label">Year Level</div>
                <div class="db-info-val"><?= $yearMap[$user['course_level']] ?? $user['course_level'] ?></div>
              </div>
            </div>
            <div class="db-info-row">
              <div class="db-info-icon"><i class="bi bi-envelope"></i></div>
              <div>
                <div class="db-info-label">Email</div>
                <div class="db-info-val db-info-val-sm"><?= htmlspecialchars($user['email']) ?></div>
              </div>
            </div>
            <div class="db-info-row">
              <div class="db-info-icon"><i class="bi bi-geo-alt"></i></div>
              <div>
                <div class="db-info-label">Address</div>
                <div class="db-info-val"><?= htmlspecialchars($user['address']) ?></div>
              </div>
            </div>
          </div>

          <div class="db-profile-footer">
            <i class="bi bi-calendar3"></i>
            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
          </div>
        </div>

        <div class="db-sessions-card">
          <div class="db-sessions-header">
            <span class="db-sessions-label">Sessions Available</span>
            <a href="reserve.php" class="db-sessions-link">
              Reserve <i class="bi bi-arrow-right"></i>
            </a>
          </div>
          <div class="db-sessions-num"><?= $remaining ?></div>
          <div class="db-sessions-sub">out of 30 total sessions</div>
          <div class="db-sessions-bar">
            <div class="db-sessions-fill" style="width:<?= $pct ?>%; background:<?= $barColor ?>;"></div>
          </div>
        </div>

      </div>

      <!-- MAIN CONTENT -->
      <div class="db-main">

        <div class="db-card reveal">
          <div class="db-card-header">
            <div class="db-card-icon"><i class="bi bi-megaphone"></i></div>
            <span class="db-card-title">Announcements</span>
          </div>
          <div class="db-card-body">
            <?php if (empty($announcements)): ?>
              <div class="ann-empty-state">
                <i class="bi bi-inbox"></i>
                No announcements at this time.
              </div>
            <?php else: ?>
              <div class="ann-list">
                <?php foreach ($announcements as $ann): ?>
                  <div class="ann-item">
                    <div class="ann-item-title"><?= htmlspecialchars($ann['title']) ?></div>
                    <div class="ann-item-body"><?= nl2br(htmlspecialchars($ann['content'])) ?></div>
                    <div class="ann-item-meta">
                      <i class="bi bi-calendar3"></i>
                      <?= date('F j, Y', strtotime($ann['created_at'])) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="db-card reveal">
          <div class="db-card-header">
            <div class="db-card-icon"><i class="bi bi-journal-text"></i></div>
            <span class="db-card-title">Laboratory Rules &amp; Regulations</span>
          </div>
          <div class="db-card-body">

            <div class="rules-header">
              <div class="rules-org">University of Cebu</div>
              <div class="rules-dept">College of Information &amp; Computer Studies</div>
              <div class="rules-sub">Laboratory Rules and Regulations</div>
              <div class="rules-intro">
                To avoid embarrassment and maintain camaraderie with your friends and
                superiors at our laboratories, please observe the following:
              </div>
            </div>

            <div class="rules-list">
              <?php foreach ($rules as $num => $text): ?>
                <div class="rule-row">
                  <div class="rule-num"><?= $num ?></div>
                  <p class="rule-txt"><?= htmlspecialchars($text) ?></p>
                </div>
              <?php endforeach; ?>
              <div>
                <div class="rule-row">
                  <div class="rule-num">7</div>
                  <p class="rule-txt">Observe proper decorum while inside the laboratory.</p>
                </div>
                <ul class="rule-sub-list">
                  <?php foreach ($ruleSubs as $sub): ?>
                    <li><?= htmlspecialchars($sub) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>

            <div class="disc-box">
              <div class="disc-box-title">
                <i class="bi bi-exclamation-triangle"></i> Disciplinary Action
              </div>
              <div class="disc-item">
                <div class="disc-dot"></div>
                <p><strong>First Offense</strong> — The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</p>
              </div>
              <div class="disc-item">
                <div class="disc-dot disc-dot-2"></div>
                <p><strong>Second and Subsequent Offenses</strong> — A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>