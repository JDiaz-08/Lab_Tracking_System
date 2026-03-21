<?php
// FILE: index.php
$pageTitle = 'Sit In Management';
$base = '';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* ═══════════════════════════════════════════
   SIT IN MANAGEMENT — Landing Page
═══════════════════════════════════════════ */
.sim-hero {
  min-height: 100vh;
  background: var(--navy);
  display: flex; align-items: center;
  position: relative; overflow: hidden;
  padding: 100px 0 80px;
}
.sim-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image: radial-gradient(rgba(189,232,245,0.07) 1px, transparent 1px);
  background-size: 48px 48px;
  pointer-events: none;
}
.sim-hero::after {
  content: '';
  position: absolute; top: 0; right: -120px; bottom: 0; width: 600px;
  background: radial-gradient(ellipse 70% 80% at 80% 50%, rgba(73,136,196,0.14) 0%, transparent 70%);
  pointer-events: none;
}
.sim-hero-inner {
  max-width: 1140px; margin: 0 auto; padding: 0 2rem;
  display: grid; grid-template-columns: 1fr 420px;
  gap: 4.5rem; align-items: center;
  position: relative; z-index: 1;
}
.sim-eyebrow-tag {
  display: flex; align-items: center; gap: 10px;
  font-size: 0.68rem; font-weight: 700; letter-spacing: 2.5px;
  text-transform: uppercase; color: rgba(189,232,245,0.50);
  margin-bottom: 1.25rem;
}
.sim-eyebrow-tag span { width: 24px; height: 1px; background: rgba(189,232,245,0.30); }
.sim-hero-h1 {
  font-family: var(--font-display);
  font-size: clamp(2.6rem, 5vw, 4rem);
  font-weight: 800; color: #fff; line-height: 1.08;
  letter-spacing: -0.5px; margin-bottom: 1.5rem;
}
.sim-hero-h1 .accent { color: var(--light); display: block; }
.sim-hero-p {
  font-size: 0.975rem; color: rgba(255,255,255,0.50);
  line-height: 1.85; font-weight: 300;
  max-width: 420px; margin-bottom: 2.5rem;
}
.sim-hero-actions { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.sim-btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 0.875rem 2rem; background: var(--light); color: var(--navy);
  border-radius: 8px; font-family: var(--font-body);
  font-size: 0.875rem; font-weight: 700;
  text-decoration: none; transition: all 0.2s; letter-spacing: 0.1px;
}
.sim-btn-primary:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(189,232,245,0.22); }
.sim-btn-ghost {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 0.875rem 1.75rem; background: transparent;
  color: rgba(255,255,255,0.58); border: 1px solid rgba(255,255,255,0.18);
  border-radius: 8px; font-family: var(--font-body);
  font-size: 0.875rem; font-weight: 500;
  text-decoration: none; transition: all 0.2s;
}
.sim-btn-ghost:hover { color: #fff; border-color: rgba(255,255,255,0.38); background: rgba(255,255,255,0.04); }

/* Panel */
.sim-panel { background: rgba(255,255,255,0.04); border: 1px solid rgba(189,232,245,0.10); border-radius: 14px; overflow: hidden; }
.sim-panel-hd { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.35rem; border-bottom: 1px solid rgba(189,232,245,0.07); }
.sim-panel-title { font-size: 0.68rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(189,232,245,0.40); }
.sim-live { display: flex; align-items: center; gap: 6px; font-size: 0.70rem; font-weight: 600; color: #6ee7b7; }
.sim-live::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #6ee7b7; animation: pulse 2s infinite; }
.sim-stats-3 { display: grid; grid-template-columns: repeat(3,1fr); border-bottom: 1px solid rgba(189,232,245,0.07); }
.sim-sc { padding: 1.1rem 0.75rem; text-align: center; border-right: 1px solid rgba(189,232,245,0.07); }
.sim-sc:last-child { border-right: none; }
.sim-sc-n { font-family: var(--font-display); font-size: 1.8rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 3px; }
.sim-sc-l { font-size: 0.65rem; color: rgba(189,232,245,0.38); letter-spacing: 0.3px; }
.sim-rows { padding: 0.5rem 0; }
.sim-row  { display: flex; align-items: center; gap: 9px; padding: 0.6rem 1.25rem; transition: background 0.15s; }
.sim-row:hover { background: rgba(255,255,255,0.02); }
.sim-dot  { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.sim-dot.on   { background: #6ee7b7; }
.sim-dot.idle { background: #fcd34d; }
.sim-dot.off  { background: rgba(189,232,245,0.20); }
.sim-row-name { flex: 1; font-size: 0.82rem; color: rgba(255,255,255,0.62); }
.sim-row-time { font-size: 0.70rem; color: rgba(189,232,245,0.32); }
.sim-panel-ft { padding: 0.75rem 1.25rem; border-top: 1px solid rgba(189,232,245,0.07); display: flex; align-items: center; justify-content: space-between; }
.sim-uptime { font-size: 0.72rem; color: rgba(189,232,245,0.35); }
.sim-uptime strong { color: #6ee7b7; }

/* Features */
.sim-features { padding: 7rem 0 6rem; background: #fff; }
.sim-container { max-width: 1140px; margin: 0 auto; padding: 0 2rem; }
.sim-section-hd { margin-bottom: 3.5rem; }
.sim-eyebrow { font-size: 0.68rem; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: var(--mid); display: block; margin-bottom: 0.7rem; }
.sim-section-h2 { font-family: var(--font-display); font-size: clamp(1.75rem,3vw,2.5rem); font-weight: 800; color: var(--navy); line-height: 1.2; margin-bottom: 0.65rem; }
.sim-section-desc { font-size: 0.9rem; color: var(--gray-600); line-height: 1.75; font-weight: 300; max-width: 460px; }
.sim-feat-grid { display: grid; grid-template-columns: repeat(3,1fr); border: 1.5px solid var(--gray-200); border-radius: 12px; overflow: hidden; gap: 1.5px; background: var(--gray-200); }
.sim-feat-cell { background: #fff; padding: 2rem 1.75rem; transition: background 0.18s; }
.sim-feat-cell:hover { background: #f8fafc; }
.sim-feat-ico { width: 40px; height: 40px; border-radius: 8px; background: rgba(15,40,84,0.05); display: flex; align-items: center; justify-content: center; color: var(--navy); font-size: 1rem; margin-bottom: 1rem; transition: all 0.18s; }
.sim-feat-cell:hover .sim-feat-ico { background: var(--navy); color: #fff; }
.sim-feat-title { font-size: 0.9rem; font-weight: 700; color: var(--navy); margin-bottom: 0.4rem; }
.sim-feat-desc  { font-size: 0.82rem; color: var(--gray-600); line-height: 1.7; font-weight: 300; }

/* Steps */
.sim-steps { padding: 6rem 0; background: var(--gray-50); }
.sim-steps-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 2rem; margin-top: 3rem; position: relative; }
.sim-steps-line { position: absolute; top: 22px; left: 12%; right: 12%; height: 1px; background: linear-gradient(90deg, var(--navy), var(--mid), rgba(73,136,196,0.25)); }
.sim-step { text-align: center; position: relative; z-index: 1; }
.sim-step-n { width: 44px; height: 44px; border-radius: 50%; background: var(--navy); color: #fff; font-family: var(--font-display); font-size: 0.95rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; box-shadow: 0 0 0 5px var(--gray-50), 0 0 0 6.5px rgba(15,40,84,0.12); transition: all 0.2s; }
.sim-step:hover .sim-step-n { background: var(--mid); transform: scale(1.08); }
.sim-step-label { font-size: 0.875rem; font-weight: 700; color: var(--navy); margin-bottom: 0.4rem; }
.sim-step-desc  { font-size: 0.80rem; color: var(--gray-600); line-height: 1.65; font-weight: 300; }

/* Stats */
.sim-stats-banner { background: var(--navy); padding: 4.5rem 0; position: relative; overflow: hidden; }
.sim-stats-banner::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(rgba(189,232,245,0.05) 1px, transparent 1px); background-size: 40px 40px; }
.sim-stats-grid { display: grid; grid-template-columns: repeat(4,1fr); position: relative; z-index: 1; }
.sim-stat-item { text-align: center; padding: 1rem; border-right: 1px solid rgba(189,232,245,0.08); }
.sim-stat-item:last-child { border-right: none; }
.sim-stat-n { font-family: var(--font-display); font-size: 2.6rem; font-weight: 800; color: var(--light); line-height: 1; margin-bottom: 0.45rem; }
.sim-stat-l { font-size: 0.80rem; color: rgba(255,255,255,0.38); }

/* CTA */
.sim-cta { padding: 6.5rem 0; background: #fff; }
.sim-cta-box { max-width: 680px; margin: 0 auto; text-align: center; }
.sim-cta-h2 { font-family: var(--font-display); font-size: clamp(1.75rem,3vw,2.4rem); font-weight: 800; color: var(--navy); line-height: 1.2; margin-bottom: 0.875rem; }
.sim-cta-p  { font-size: 0.93rem; color: var(--gray-600); line-height: 1.75; font-weight: 300; margin-bottom: 2.25rem; }
.sim-cta-actions { display: flex; gap: 0.875rem; justify-content: center; flex-wrap: wrap; }
.sim-btn-dark { display: inline-flex; align-items: center; gap: 8px; padding: 0.875rem 2rem; background: var(--navy); color: #fff; border-radius: 8px; font-family: var(--font-body); font-size: 0.875rem; font-weight: 700; text-decoration: none; transition: all 0.2s; }
.sim-btn-dark:hover { background: var(--blue); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,40,84,0.18); }
.sim-btn-outline { display: inline-flex; align-items: center; gap: 8px; padding: 0.875rem 1.75rem; background: transparent; color: var(--navy); border: 1.5px solid var(--gray-200); border-radius: 8px; font-family: var(--font-body); font-size: 0.875rem; font-weight: 600; text-decoration: none; transition: all 0.2s; }
.sim-btn-outline:hover { border-color: var(--mid); color: var(--mid); background: rgba(73,136,196,0.04); }

@media (max-width: 960px) {
  .sim-hero-inner  { grid-template-columns: 1fr; gap: 2.5rem; }
  .sim-panel       { display: none; }
  .sim-feat-grid   { grid-template-columns: repeat(2,1fr); }
  .sim-steps-grid  { grid-template-columns: repeat(2,1fr); }
  .sim-steps-line  { display: none; }
  .sim-stats-grid  { grid-template-columns: repeat(2,1fr); }
  .sim-stat-item   { border-bottom: 1px solid rgba(189,232,245,0.08); }
  .sim-stat-item:nth-child(2) { border-right: none; }
}
@media (max-width: 600px) {
  .sim-feat-grid  { grid-template-columns: 1fr; }
  .sim-steps-grid { grid-template-columns: 1fr; }
}
</style>

<!-- HERO -->
<section class="sim-hero">
  <div class="sim-hero-inner">
    <div>
      <div class="sim-eyebrow-tag"><span></span>University of Cebu · CCS</div>
      <h1 class="sim-hero-h1">
        Sit In
        <span class="accent">Management</span>
        System
      </h1>
      <p class="sim-hero-p">
        A streamlined platform to monitor student sit-in sessions,
        manage laboratory schedules, and keep accurate records —
        all in one place, built for UC CCS.
      </p>
      <div class="sim-hero-actions">
        <a href="pages/login.php" class="sim-btn-primary">Get Started <i class="bi bi-arrow-right"></i></a>
        <a href="pages/about.php" class="sim-btn-ghost">About the System</a>
      </div>
    </div>
    <div class="sim-panel">
      <div class="sim-panel-hd">
        <span class="sim-panel-title">Lab Dashboard</span>
        <span class="sim-live">Live</span>
      </div>
      <div class="sim-stats-3">
        <div class="sim-sc"><div class="sim-sc-n">42</div><div class="sim-sc-l">Active Now</div></div>
        <div class="sim-sc"><div class="sim-sc-n">08</div><div class="sim-sc-l">Available</div></div>
        <div class="sim-sc"><div class="sim-sc-n">03</div><div class="sim-sc-l">Pending</div></div>
      </div>
      <div class="sim-rows">
        <div class="sim-row"><span class="sim-dot on"></span><span class="sim-row-name">Lab Room 524 — Sit-in</span><span class="sim-row-time">2h 15m</span></div>
        <div class="sim-row"><span class="sim-dot on"></span><span class="sim-row-name">Lab Room 526 — Class</span><span class="sim-row-time">1h 40m</span></div>
        <div class="sim-row"><span class="sim-dot idle"></span><span class="sim-row-name">Lab Room 528 — Idle</span><span class="sim-row-time">0h 05m</span></div>
        <div class="sim-row"><span class="sim-dot off"></span><span class="sim-row-name">Lab Room 530 — Vacant</span><span class="sim-row-time">—</span></div>
      </div>
      <div class="sim-panel-ft">
        <span class="sim-uptime">Uptime <strong>98.4%</strong></span>
        <span style="font-size:0.68rem;color:rgba(189,232,245,0.28);"><?= date('g:i A') ?></span>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="sim-features">
  <div class="sim-container">
    <div class="sim-section-hd reveal">
      <span class="sim-eyebrow">Platform Features</span>
      <h2 class="sim-section-h2">Everything you need<br>to manage your lab.</h2>
      <p class="sim-section-desc">Designed for university computer laboratories to simplify daily management tasks.</p>
    </div>
    <div class="sim-feat-grid reveal">
      <div class="sim-feat-cell"><div class="sim-feat-ico"><i class="bi bi-pc-display"></i></div><div class="sim-feat-title">Sit-In Monitoring</div><p class="sim-feat-desc">Track sit-in sessions in real time. Know who's using which workstation and for how long, with automatic time logging.</p></div>
      <div class="sim-feat-cell"><div class="sim-feat-ico"><i class="bi bi-journal-text"></i></div><div class="sim-feat-title">Session Logging</div><p class="sim-feat-desc">Detailed logs of every lab session. Generate reports by date, student, course, or lab room.</p></div>
      <div class="sim-feat-cell"><div class="sim-feat-ico"><i class="bi bi-people"></i></div><div class="sim-feat-title">Student Records</div><p class="sim-feat-desc">Complete student directory with academic info, visit history, and usage stats linked to their university ID.</p></div>
      <div class="sim-feat-cell"><div class="sim-feat-ico"><i class="bi bi-calendar-check"></i></div><div class="sim-feat-title">Reservation System</div><p class="sim-feat-desc">Let students book lab slots in advance to reduce wait times and optimize seat utilization.</p></div>
      <div class="sim-feat-cell"><div class="sim-feat-ico"><i class="bi bi-bar-chart-line"></i></div><div class="sim-feat-title">Analytics &amp; Reports</div><p class="sim-feat-desc">Visualize peak hours, popular workstations, and attendance trends to support lab policy decisions.</p></div>
      <div class="sim-feat-cell"><div class="sim-feat-ico"><i class="bi bi-shield-lock"></i></div><div class="sim-feat-title">Secure Access Control</div><p class="sim-feat-desc">Role-based access for administrators, instructors, and students. Data stays protected at every level.</p></div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="sim-steps">
  <div class="sim-container">
    <div class="sim-section-hd reveal">
      <span class="sim-eyebrow">Simple Process</span>
      <h2 class="sim-section-h2">How It Works</h2>
      <p class="sim-section-desc">Getting started is straightforward — students and faculty can be up and running in minutes.</p>
    </div>
    <div class="sim-steps-grid">
      <div class="sim-steps-line"></div>
      <div class="sim-step reveal"><div class="sim-step-n">01</div><div class="sim-step-label">Register Account</div><p class="sim-step-desc">Sign up with your university ID and course details to create a verified account.</p></div>
      <div class="sim-step reveal"><div class="sim-step-n">02</div><div class="sim-step-label">Log In to the Lab</div><p class="sim-step-desc">On arrival, log your sit-in session by selecting the lab room and your purpose.</p></div>
      <div class="sim-step reveal"><div class="sim-step-n">03</div><div class="sim-step-label">Work &amp; Track</div><p class="sim-step-desc">The system records your session automatically throughout your stay.</p></div>
      <div class="sim-step reveal"><div class="sim-step-n">04</div><div class="sim-step-label">Log Out &amp; Review</div><p class="sim-step-desc">Log out to close the session and view your full history from the dashboard anytime.</p></div>
    </div>
  </div>
</section>

<!-- STATS -->
<section class="sim-stats-banner">
  <div class="sim-container">
    <div class="sim-stats-grid">
      <div class="sim-stat-item reveal"><div class="sim-stat-n counter" data-target="1200" data-suffix="+">0</div><div class="sim-stat-l">Registered Students</div></div>
      <div class="sim-stat-item reveal"><div class="sim-stat-n counter" data-target="6">0</div><div class="sim-stat-l">Laboratory Rooms</div></div>
      <div class="sim-stat-item reveal"><div class="sim-stat-n counter" data-target="180" data-suffix="+">0</div><div class="sim-stat-l">Workstations</div></div>
      <div class="sim-stat-item reveal"><div class="sim-stat-n counter" data-target="98" data-suffix="%">0</div><div class="sim-stat-l">System Uptime</div></div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="sim-cta">
  <div class="sim-container">
    <div class="sim-cta-box reveal">
      <h2 class="sim-cta-h2">Ready to get started?</h2>
      <p class="sim-cta-p">Join students and faculty already using the UC Sit In Management System to make lab sessions more organized and productive.</p>
      <div class="sim-cta-actions">
        <a href="pages/login.php" class="sim-btn-dark">Login Now <i class="bi bi-arrow-right"></i></a>
        <a href="pages/about.php" class="sim-btn-outline">Learn More</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>