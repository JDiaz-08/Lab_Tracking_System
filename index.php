<?php
$pageTitle = 'Home';
$base = '';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- =====================
     HERO SECTION
     ===================== -->
<section class="hero">
  <div class="hero-grid"></div>
  <div class="hero-container">

    <!-- Left: Content -->
    <div class="hero-content">
      <div class="hero-badge">Computer Laboratory Management</div>
      <h1 class="hero-title">
        Smart Lab Tracking
        <span>for Modern Universities</span>
      </h1>
      <p class="hero-desc">
        Streamline your computer laboratory operations. Monitor sit-ins, manage student sessions, and track resource usage — all in one centralized system built for the University of Cebu.
      </p>
      <div class="hero-actions">
        <a href="pages/login.php" class="btn btn-primary">Get Started &rarr;</a>
        <a href="pages/about.php" class="btn btn-outline">Learn More</a>
      </div>
    </div>

    <!-- Right: Dashboard Preview Card -->
    <div class="hero-visual">
      <div class="hero-card-stack">
        <div class="stat-card stat-card-main">

          <div class="card-header">
            <span class="card-title-text">Lab Dashboard</span>
            <span class="card-live">Live</span>
          </div>

          <div class="stat-row">
            <div class="stat-item">
              <div class="stat-number">42</div>
              <div class="stat-label">Active Now</div>
            </div>
            <div class="stat-item">
              <div class="stat-number">08</div>
              <div class="stat-label">Available</div>
            </div>
            <div class="stat-item">
              <div class="stat-number">03</div>
              <div class="stat-label">Pending</div>
            </div>
          </div>

          <div class="session-list">
            <div class="session-item">
              <span class="session-dot dot-active"></span>
              <span class="session-name">Lab Room 1 — Sit-in</span>
              <span class="session-time">2h 15m</span>
            </div>
            <div class="session-item">
              <span class="session-dot dot-active"></span>
              <span class="session-name">Lab Room 2 — Class</span>
              <span class="session-time">1h 40m</span>
            </div>
            <div class="session-item">
              <span class="session-dot dot-idle"></span>
              <span class="session-name">Lab Room 3 — Idle</span>
              <span class="session-time">0h 05m</span>
            </div>
            <div class="session-item">
              <span class="session-dot dot-vacant"></span>
              <span class="session-name">Lab Room 4 — Vacant</span>
              <span class="session-time">—</span>
            </div>
          </div>

        </div><!-- /.stat-card-main -->

        <!-- Floating mini card -->
        <div class="mini-card">
          <div class="mini-icon">📊</div>
          <div class="mini-info">
            <div class="mini-val">98.4%</div>
            <div class="mini-lbl">Uptime Today</div>
          </div>
        </div>

      </div><!-- /.hero-card-stack -->
    </div><!-- /.hero-visual -->

  </div>
</section>

<!-- =====================
     FEATURES SECTION
     ===================== -->
<section class="features">
  <div class="section-container">
    <div class="section-header reveal">
      <div class="section-tag">What We Offer</div>
      <h2 class="section-title">Everything You Need<br>to Manage Your Lab</h2>
      <p class="section-desc">Designed specifically for university computer laboratories, our system simplifies day-to-day management tasks.</p>
    </div>

    <div class="features-grid">

      <div class="feature-card reveal">
        <div class="feature-icon">🖥️</div>
        <h3 class="feature-title">Sit-In Monitoring</h3>
        <p class="feature-desc">Record and track student sit-in sessions in real time. Know exactly who is using which workstation and for how long, with automatic time logging.</p>
      </div>

      <div class="feature-card reveal">
        <div class="feature-icon">📋</div>
        <h3 class="feature-title">Session Logging</h3>
        <p class="feature-desc">Maintain detailed logs of every laboratory session. Generate reports by date, student, course, or laboratory room for administrative review.</p>
      </div>

      <div class="feature-card reveal">
        <div class="feature-icon">👤</div>
        <h3 class="feature-title">Student Records</h3>
        <p class="feature-desc">Maintain a complete student directory with academic information, visit history, and usage statistics all linked to their university ID.</p>
      </div>

      <div class="feature-card reveal">
        <div class="feature-icon">📅</div>
        <h3 class="feature-title">Reservation System</h3>
        <p class="feature-desc">Allow students and faculty to reserve laboratory slots in advance. Reduce wait times and optimize seat utilization across all lab rooms.</p>
      </div>

      <div class="feature-card reveal">
        <div class="feature-icon">📊</div>
        <h3 class="feature-title">Analytics & Reports</h3>
        <p class="feature-desc">Gain insights through usage analytics. Visualize peak hours, popular workstations, and student attendance trends to inform lab policy decisions.</p>
      </div>

      <div class="feature-card reveal">
        <div class="feature-icon">🔒</div>
        <h3 class="feature-title">Secure Access Control</h3>
        <p class="feature-desc">Role-based access for administrators, instructors, and students. Each user sees only what they need, keeping sensitive data protected.</p>
      </div>

    </div>
  </div>
</section>

<!-- =====================
     HOW IT WORKS
     ===================== -->
<section class="how-it-works">
  <div class="section-container">
    <div class="section-header reveal">
      <div class="section-tag">Simple Process</div>
      <h2 class="section-title">How It Works</h2>
      <p class="section-desc">Getting started is straightforward — students and faculty can be up and running in minutes.</p>
    </div>

    <div class="steps-grid">

      <div class="step-item reveal">
        <div class="step-number">01</div>
        <div class="step-label">Register Your Account</div>
        <p class="step-desc">Students sign up using their university ID and course information to create a verified account in the system.</p>
      </div>

      <div class="step-item reveal">
        <div class="step-number">02</div>
        <div class="step-label">Log In to the Lab</div>
        <p class="step-desc">Upon arrival, students log their sit-in session through the system, selecting the lab room and workstation they are using.</p>
      </div>

      <div class="step-item reveal">
        <div class="step-number">03</div>
        <div class="step-label">Work & Track Progress</div>
        <p class="step-desc">The system records your session automatically, tracking time and workstation usage throughout your stay in the laboratory.</p>
      </div>

      <div class="step-item reveal">
        <div class="step-number">04</div>
        <div class="step-label">Log Out & Review</div>
        <p class="step-desc">When finished, log out to close the session. View your history and session summaries anytime through your dashboard.</p>
      </div>

    </div>
  </div>
</section>

<!-- =====================
     STATS BANNER
     ===================== -->
<section class="stats-banner">
  <div class="section-container">
    <div class="stats-row">
      <div class="stat-banner-item reveal">
        <div class="stat-banner-num counter" data-target="1200" data-suffix="+">0</div>
        <div class="stat-banner-lbl">Registered Students</div>
      </div>
      <div class="stat-banner-item reveal">
        <div class="stat-banner-num counter" data-target="6" data-suffix="">0</div>
        <div class="stat-banner-lbl">Laboratory Rooms</div>
      </div>
      <div class="stat-banner-item reveal">
        <div class="stat-banner-num counter" data-target="180" data-suffix="+">0</div>
        <div class="stat-banner-lbl">Workstations</div>
      </div>
      <div class="stat-banner-item reveal">
        <div class="stat-banner-num counter" data-target="98" data-suffix="%">0</div>
        <div class="stat-banner-lbl">System Uptime</div>
      </div>
    </div>
  </div>
</section>

<!-- =====================
     CTA SECTION
     ===================== -->
<section class="cta-section">
  <div class="section-container">
    <div class="cta-card reveal">
      <h2>Ready to Get Started?</h2>
      <p>Join students and faculty already using the UC Computer Laboratory Management System to make lab sessions more organized and productive.</p>
      <div class="cta-actions">
        <a href="pages/login.php" class="btn btn-primary">Login Now</a>
        <a href="pages/about.php" class="btn btn-outline">Learn More</a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>