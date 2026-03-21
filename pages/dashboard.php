<?php
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

// Active sit-in check
$activeSitIn = $db->prepare(
    "SELECT * FROM sit_in_logs WHERE user_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1"
);
$activeSitIn->execute([$user['id']]);
$activeSitIn = $activeSitIn->fetch();

$yearMap = ['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year','5'=>'5th Year'];
$remaining = (int)($user['remaining_sessions'] ?? 30);
$pct       = min(100, round(($remaining / 30) * 100));

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
?>
<style>
  /* ---- Dashboard overrides ---- */
  .db-wrap {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
    align-items: start;
  }

  /* ---- Profile sidebar ---- */
  .db-sidebar { display: flex; flex-direction: column; gap: 1rem; }

  .db-profile-card {
    background: linear-gradient(155deg, #0F2854 0%, #1C4D8D 100%);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(15,40,84,0.18);
  }
  .db-profile-top {
    padding: 1.75rem 1.5rem 1.25rem;
    text-align: center;
    position: relative;
  }
  .db-profile-top::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 120px; height: 120px;
    background: rgba(189,232,245,0.07);
    border-radius: 50%;
    pointer-events: none;
  }
  .db-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    border: 2.5px solid rgba(189,232,245,0.35);
    margin: 0 auto 0.875rem;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(189,232,245,0.15);
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #BDE8F5;
    position: relative;
    z-index: 1;
  }
  .db-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
  .db-profile-name {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.2rem;
    position: relative;
    z-index: 1;
  }
  .db-profile-id {
    font-size: 0.72rem;
    color: rgba(189,232,245,0.70);
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
  }

  .db-profile-info {
    border-top: 1px solid rgba(189,232,245,0.12);
    padding: 1rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  .db-info-row {
    display: flex;
    align-items: flex-start;
    gap: 9px;
  }
  .db-info-icon {
    width: 22px; height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(189,232,245,0.55);
    font-size: 0.78rem;
    flex-shrink: 0;
    margin-top: 1px;
  }
  .db-info-label {
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(189,232,245,0.55);
    margin-bottom: 1px;
  }
  .db-info-val {
    font-size: 0.83rem;
    color: #fff;
    font-weight: 500;
    word-break: break-word;
  }

  .db-profile-footer {
    border-top: 1px solid rgba(189,232,245,0.10);
    padding: 0.65rem 1.5rem;
    font-size: 0.68rem;
    color: rgba(189,232,245,0.40);
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
  }

  /* Sessions card */
  .db-sessions-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    box-shadow: 0 1px 4px rgba(15,40,84,0.06);
  }
  .db-sessions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.875rem;
  }
  .db-sessions-label {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #4988C4;
  }
  .db-sessions-link {
    font-size: 0.74rem;
    color: #4988C4;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 3px;
    transition: color 0.18s;
  }
  .db-sessions-link:hover { color: #0F2854; }
  .db-sessions-num {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.6rem;
    font-weight: 800;
    color: #0F2854;
    line-height: 1;
    margin-bottom: 2px;
  }
  .db-sessions-sub {
    font-size: 0.74rem;
    color: #94A3B8;
    margin-bottom: 0.75rem;
  }
  .db-sessions-bar {
    background: #f0f4f8;
    border-radius: 100px;
    height: 7px;
    overflow: hidden;
  }
  .db-sessions-fill {
    height: 100%;
    border-radius: 100px;
    transition: width 0.6s ease;
  }

  /* Active session banner */
  .db-active-banner {
    background: rgba(22,163,74,0.07);
    border: 1px solid rgba(22,163,74,0.20);
    border-radius: 10px;
    padding: 0.8rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .db-active-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #16a34a;
    flex-shrink: 0;
    animation: pulse-dot 2s infinite;
  }
  @keyframes pulse-dot {
    0%,100% { box-shadow: 0 0 0 0 rgba(22,163,74,0.4); }
    50%      { box-shadow: 0 0 0 5px rgba(22,163,74,0); }
  }
  .db-active-info { flex: 1; }
  .db-active-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #065F46;
    margin-bottom: 1px;
  }
  .db-active-meta {
    font-size: 0.72rem;
    color: #16a34a;
  }

  /* Main content */
  .db-main { display: flex; flex-direction: column; gap: 1.25rem; }

  /* Content card */
  .db-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 4px rgba(15,40,84,0.06);
    overflow: hidden;
  }
  .db-card-header {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 1rem 1.35rem;
    border-bottom: 1px solid #f0f4f8;
  }
  .db-card-icon {
    width: 30px; height: 30px;
    border-radius: 7px;
    background: rgba(15,40,84,0.06);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0F2854;
    font-size: 0.85rem;
    flex-shrink: 0;
  }
  .db-card-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 0.95rem;
    font-weight: 800;
    color: #0F2854;
  }
  .db-card-body { padding: 1.25rem 1.35rem; }

  /* Announcements */
  .ann-list { display: flex; flex-direction: column; gap: 0.875rem; }
  .ann-item {
    padding: 0.9rem 1rem;
    background: #f8fafc;
    border-radius: 9px;
    border-left: 3px solid #4988C4;
  }
  .ann-item-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0F2854;
    margin-bottom: 0.3rem;
  }
  .ann-item-body {
    font-size: 0.83rem;
    color: #475569;
    line-height: 1.65;
    font-weight: 300;
  }
  .ann-item-meta {
    font-size: 0.70rem;
    color: #94A3B8;
    margin-top: 0.4rem;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .ann-item-meta i { font-size: 0.70rem; }
  .ann-empty-state {
    text-align: center;
    padding: 1.5rem 1rem;
    color: #94A3B8;
    font-size: 0.875rem;
  }
  .ann-empty-state i { font-size: 1.75rem; display:block; margin-bottom:0.5rem; opacity:0.4; }

  /* Rules */
  .rules-header {
    text-align: center;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f4f8;
  }
  .rules-header .rules-org   { font-size: 0.875rem; font-weight:700; color:#0F2854; }
  .rules-header .rules-dept  { font-size: 0.83rem;  font-weight:700; color:#0F2854; margin-top:1px; }
  .rules-header .rules-sub   { font-size: 0.75rem;  font-weight:700; color:#4988C4; text-transform:uppercase; letter-spacing:0.5px; margin-top:4px; }
  .rules-header .rules-intro { font-size: 0.80rem;  color:#475569; font-weight:300; margin-top:0.5rem; line-height:1.65; max-width:520px; margin-left:auto; margin-right:auto; }

  .rules-list { display:flex; flex-direction:column; gap:0.65rem; }
  .rule-row   { display:flex; gap:10px; align-items:flex-start; }
  .rule-num   {
    width:22px; height:22px;
    border-radius:50%;
    background:#0F2854;
    color:#fff;
    font-size:0.65rem;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
    margin-top:2px;
  }
  .rule-txt   { font-size:0.84rem; color:#475569; line-height:1.65; font-weight:300; }

  .rule-sub-list {
    margin-left:2.2rem;
    margin-top:0.35rem;
    display:flex;
    flex-direction:column;
    gap:0.25rem;
  }
  .rule-sub-list li {
    font-size:0.83rem;
    color:#475569;
    font-weight:300;
    line-height:1.6;
    list-style:disc;
    padding-left:2px;
  }

  .disc-box {
    margin-top:1.25rem;
    background:rgba(239,68,68,0.04);
    border:1px solid rgba(239,68,68,0.12);
    border-radius:9px;
    padding:0.9rem 1rem;
  }
  .disc-box-title {
    font-size:0.70rem;
    font-weight:800;
    letter-spacing:1.2px;
    text-transform:uppercase;
    color:#991b1b;
    margin-bottom:0.6rem;
    display:flex;
    align-items:center;
    gap:6px;
  }
  .disc-item {
    display:flex;
    gap:9px;
    align-items:flex-start;
    margin-bottom:0.45rem;
  }
  .disc-item:last-child { margin-bottom:0; }
  .disc-dot {
    width:10px; height:10px;
    border-radius:50%;
    background:#dc2626;
    flex-shrink:0;
    margin-top:4px;
  }
  .disc-dot-2 { background:#991b1b; }
  .disc-item p { font-size:0.84rem; color:#475569; font-weight:300; line-height:1.6; }

  /* Responsive */
  @media (max-width:960px) {
    .db-wrap { grid-template-columns:1fr; }
    .db-sidebar { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
  }
  @media (max-width:600px) {
    .db-sidebar { grid-template-columns:1fr; }
  }
</style>
<?php require_once __DIR__ . '/../includes/user-navbar.php'; ?>

<div class="user-page">
  <div class="user-page-inner">

    <!-- Page heading -->
    <div style="margin-bottom:1.5rem;">
      <h1 style="font-family:'Playfair Display',Georgia,serif; font-size:1.6rem; font-weight:800; color:#0F2854; margin-bottom:0.2rem;">
        Welcome back, <?= htmlspecialchars($user['first_name']) ?>
      </h1>
      <p style="color:#94A3B8; font-size:0.875rem; font-weight:300;">
        <?= date('l, F j, Y') ?>
      </p>
    </div>

    <!-- Active session banner (if any) -->
    <?php if ($activeSitIn): ?>
      <?php $elapsed = round((time() - strtotime($activeSitIn['login_time'])) / 60); ?>
      <div class="db-active-banner" style="margin-bottom:1.25rem;">
        <div class="db-active-dot"></div>
        <div class="db-active-info">
          <div class="db-active-title">Active Sit-in Session</div>
          <div class="db-active-meta">
            Lab <?= htmlspecialchars($activeSitIn['lab_room']) ?> &middot;
            <?= htmlspecialchars($activeSitIn['purpose'] ?? '—') ?> &middot;
            <?= $elapsed < 60 ? $elapsed.'m elapsed' : floor($elapsed/60).'h '.($elapsed%60).'m elapsed' ?>
          </div>
        </div>
        <a href="history.php" style="font-size:0.76rem; font-weight:700; color:#065F46; text-decoration:none; white-space:nowrap; display:flex; align-items:center; gap:4px;">
          View <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    <?php endif; ?>

    <div class="db-wrap">

      <!-- ======== SIDEBAR ======== -->
      <div class="db-sidebar">

        <!-- Profile card -->
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
                <div class="db-info-val" style="font-size:0.78rem; word-break:break-all;"><?= htmlspecialchars($user['email']) ?></div>
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

        <!-- Sessions widget -->
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
            <div class="db-sessions-fill" style="
              width: <?= $pct ?>%;
              background: <?= $pct > 50 ? '#4988C4' : ($pct > 20 ? '#d97706' : '#dc2626') ?>;
            "></div>
          </div>
        </div>

      </div><!-- /.db-sidebar -->

      <!-- ======== MAIN ======== -->
      <div class="db-main">

        <!-- Announcements -->
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

        <!-- Rules & Regulations -->
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
              <?php
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
              foreach ($rules as $num => $text): ?>
                <div class="rule-row">
                  <div class="rule-num"><?= $num ?></div>
                  <p class="rule-txt"><?= htmlspecialchars($text) ?></p>
                </div>
              <?php endforeach; ?>

              <!-- Rule 7 -->
              <div>
                <div class="rule-row">
                  <div class="rule-num">7</div>
                  <p class="rule-txt">Observe proper decorum while inside the laboratory.</p>
                </div>
                <ul class="rule-sub-list">
                  <?php foreach ([
                    'Do not get inside the lab unless the instructor is present.',
                    'All bags, knapsacks, and the likes must be deposited at the counter.',
                    'Follow the seating arrangement of your instructor.',
                    'At the end of class, all software programs must be closed.',
                    'Return all chairs to their proper places after using.',
                  ] as $sub): ?>
                    <li><?= htmlspecialchars($sub) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>

            <!-- Disciplinary action -->
            <div class="disc-box">
              <div class="disc-box-title">
                <i class="bi bi-exclamation-triangle"></i> Disciplinary Action
              </div>
              <div class="disc-item">
                <div class="disc-dot"></div>
                <p><strong style="color:#1e293b;">First Offense</strong> — The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</p>
              </div>
              <div class="disc-item">
                <div class="disc-dot disc-dot-2"></div>
                <p><strong style="color:#1e293b;">Second and Subsequent Offenses</strong> — A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
              </div>
            </div>

          </div>
        </div>

      </div><!-- /.db-main -->
    </div><!-- /.db-wrap -->
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>