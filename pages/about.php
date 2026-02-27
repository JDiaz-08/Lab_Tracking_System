<?php
$pageTitle = 'About';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page Hero -->
<section class="page-hero">
  <div class="section-container page-hero-content">
    <div class="hero-badge" style="margin: 0 auto 1.25rem; display: inline-flex; color: var(--light); background: rgba(189,232,245,0.15); border: 1px solid rgba(189,232,245,0.30); font-size: 0.78rem; font-weight: 600; letter-spacing: 1.2px; text-transform: uppercase; padding: 6px 16px; border-radius: 100px;">About the System</div>
    <h1>UC Computer Laboratory<br>Management System</h1>
    <p>A centralized platform for the College of Computer Studies to manage laboratory sessions, sit-ins, and student activity with ease and efficiency.</p>
  </div>
</section>

<!-- About Content -->
<section class="about-section">
  <div class="section-container">
    <div class="about-grid">

      <div class="about-text reveal">
        <h2>Purpose-Built for UC CCS</h2>
        <p>
          The University of Cebu Computer Laboratory Management System was developed to address the growing need for a reliable, centralized way to monitor and manage student activities within the College of Computer Studies laboratories.
        </p>
        <p>
          Previously managed through manual logbooks and paper-based records, laboratory sit-in tracking was time-consuming and error-prone. This system replaces that process with a fast, accurate, and accessible digital solution.
        </p>
        <p>
          Whether you are a student checking in for a personal coding session, an instructor supervising a class, or an administrator reviewing usage statistics — the system provides each role with the tools they need.
        </p>
      </div>

      <div class="about-visual reveal">
        <h3>System Highlights</h3>
        <div class="value-list">

          <div class="value-item">
            <div class="value-icon">🎯</div>
            <div class="value-info">
              <h4>Accurate Tracking</h4>
              <p>Every sit-in session is time-stamped and linked to a verified student account.</p>
            </div>
          </div>

          <div class="value-item">
            <div class="value-icon">⚡</div>
            <div class="value-info">
              <h4>Real-Time Updates</h4>
              <p>Lab occupancy and session data update instantly across the system.</p>
            </div>
          </div>

          <div class="value-item">
            <div class="value-icon">📁</div>
            <div class="value-info">
              <h4>Complete Records</h4>
              <p>Full session history available for students, instructors, and administrators.</p>
            </div>
          </div>

          <div class="value-item">
            <div class="value-icon">🔐</div>
            <div class="value-info">
              <h4>Role-Based Access</h4>
              <p>Secure, tiered access control for students, faculty, and lab administrators.</p>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- Features Recap -->
<section class="features" style="padding: 5rem 0;">
  <div class="section-container">
    <div class="section-header reveal">
      <div class="section-tag">Key Features</div>
      <h2 class="section-title">Built for Students & Staff</h2>
      <p class="section-desc">The system is designed to serve every person involved in the computer laboratory ecosystem at UC CCS.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card reveal">
        <div class="feature-icon">🧑‍🎓</div>
        <h3 class="feature-title">For Students</h3>
        <p class="feature-desc">Log sit-in sessions quickly, view your session history, and check lab availability before heading over — saving you time every visit.</p>
      </div>
      <div class="feature-card reveal">
        <div class="feature-icon">🧑‍🏫</div>
        <h3 class="feature-title">For Instructors</h3>
        <p class="feature-desc">Monitor class lab sessions, verify student attendance, and access session logs for your courses directly through the system.</p>
      </div>
      <div class="feature-card reveal">
        <div class="feature-icon">🛠️</div>
        <h3 class="feature-title">For Administrators</h3>
        <p class="feature-desc">Full visibility over all laboratory rooms and sessions. Export reports, manage user accounts, and configure system settings with ease.</p>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>