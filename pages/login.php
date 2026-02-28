<?php
$pageTitle = 'Login';
$base = '../';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-page">
  <div class="auth-wrapper">

    <!-- LEFT PANEL — CCS Branding -->
    <div class="auth-left">
      <div class="ccs-logo-wrap">
        <img src="<?= $base ?>assets/images/ccs-logo.png" alt="CCS Logo" class="ccs-real-logo" />
        <span class="ccs-label">College of Computer Studies</span>
      </div>
      <h2>UC Computer Laboratory System</h2>
      <p>
        Log in to access the laboratory management portal. Track your sit-in sessions, check lab availability, and manage your academic computer laboratory usage — all in one place.
      </p>
    </div>

    <!-- RIGHT PANEL — Auth Forms -->
    <div class="auth-right">

      <!-- Tabs -->
      <div class="auth-tabs">
        <button class="tab-btn active" data-tab="login">Login</button>
        <button class="tab-btn" data-tab="register">Register</button>
        <button class="tab-btn" data-tab="forgot">Forgot</button>
      </div>

      <!-- ========================
           LOGIN PANEL
           ======================== -->
      <div class="auth-panel active" id="panel-login">
        <h3>Welcome Back</h3>
        <p class="sub-text">Sign in to your account to continue.</p>

        <form method="POST" action="#">

          <div class="form-group">
            <label for="login-id">University ID / Email</label>
            <input
              type="text"
              id="login-id"
              name="username"
              placeholder="e.g. 22-12345 or you@uc.edu.ph"
              autocomplete="username"
              required
            />
          </div>

          <div class="form-group">
            <label for="login-pass">Password</label>
            <div class="input-wrapper">
              <input
                type="password"
                id="login-pass"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
              />
              <span class="input-icon toggle-pass">👁️</span>
            </div>
          </div>

          <div class="form-footer">
            <label class="remember-me">
              <input type="checkbox" name="remember" />
              Remember me
            </label>
            <span class="forgot-link">Forgot password?</span>
          </div>

          <button type="submit" class="btn-submit">Sign In</button>

          <div class="divider">or</div>

          <p class="switch-auth">
            Don't have an account?
            <a data-switch="register">Create one</a>
          </p>

        </form>
      </div>

      <!-- ========================
           REGISTER PANEL
           ======================== -->
      <div class="auth-panel" id="panel-register">
        <h3>Create Account</h3>
        <p class="sub-text">Register using your university details.</p>

        <form method="POST" action="#">

          <!-- Row 1: First Name + Last Name -->
          <div class="form-row">
            <div class="form-group">
              <label for="reg-fname">First Name</label>
              <input type="text" id="reg-fname" name="first_name" placeholder="Juan" required />
            </div>
            <div class="form-group">
              <label for="reg-lname">Last Name</label>
              <input type="text" id="reg-lname" name="last_name" placeholder="Dela Cruz" required />
            </div>
          </div>

          <!-- Middle Name -->
          <div class="form-group">
            <label for="reg-mname">Middle Name</label>
            <input type="text" id="reg-mname" name="middle_name" placeholder="Santos (leave blank if none)" />
          </div>

          <!-- University ID -->
          <div class="form-group">
            <label for="reg-id">University ID Number</label>
            <input type="text" id="reg-id" name="student_id" placeholder="e.g. 22-12345" required />
          </div>

          <!-- Row 2: Course + Course Level -->
          <div class="form-row">
            <div class="form-group">
              <label for="reg-course">Course / Program</label>
              <input type="text" id="reg-course" name="course" placeholder="e.g. BSIT, BSCS, ACT" required />
            </div>
            <div class="form-group">
              <label for="reg-level">Course Level</label>
              <select id="reg-level" name="course_level" required>
                <option value="" disabled selected>Select year</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
                <option value="5">5th Year</option>
              </select>
            </div>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="reg-email">Email Address</label>
            <input type="email" id="reg-email" name="email" placeholder="e.g. juandelacruz@gmail.com" required />
          </div>

          <!-- Address -->
          <div class="form-group">
            <label for="reg-address">Address</label>
            <input
              type="text"
              id="reg-address"
              name="address"
              placeholder="House No., Street, Barangay, City/Municipality"
              required
            />
          </div>

          <!-- Password -->
          <div class="form-group">
            <label for="reg-pass">Password</label>
            <div class="input-wrapper">
              <input
                type="password"
                id="reg-pass"
                name="password"
                placeholder="Create a strong password"
                autocomplete="new-password"
                required
              />
              <span class="input-icon toggle-pass">👁️</span>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group">
            <label for="reg-confirm">Confirm Password</label>
            <div class="input-wrapper">
              <input
                type="password"
                id="reg-confirm"
                name="confirm_password"
                placeholder="Re-enter your password"
                autocomplete="new-password"
                required
              />
              <span class="input-icon toggle-pass">👁️</span>
            </div>
          </div>

          <button type="submit" class="btn-submit">Create Account</button>

          <p class="terms-text">
            By registering, you agree to the
            <a href="#">Terms of Use</a> and
            <a href="#">Privacy Policy</a> of UC CCS Laboratory System.
          </p>

          <div class="divider">or</div>

          <p class="switch-auth">
            Already have an account?
            <a data-switch="login">Sign in</a>
          </p>

        </form>
      </div>

      <!-- ========================
           FORGOT PASSWORD PANEL
           ======================== -->
      <div class="auth-panel" id="panel-forgot">
        <h3>Reset Password</h3>
        <p class="sub-text">We'll send a reset link to your registered email.</p>

        <div class="alert alert-info">
          Enter your university email address below and we will send you instructions to reset your password.
        </div>

        <form method="POST" action="#">

          <div class="form-group">
            <label for="forgot-email">Email Address</label>
            <input
              type="email"
              id="forgot-email"
              name="email"
              placeholder="e.g. juandelacruz@gmail.com"
              required
            />
          </div>

          <button type="submit" class="btn-submit">Send Reset Link</button>

          <div class="divider">or</div>

          <p class="switch-auth">
            Remember your password?
            <a data-switch="login">Back to Login</a>
          </p>

        </form>
      </div>

    </div><!-- /.auth-right -->
  </div><!-- /.auth-wrapper -->
</div><!-- /.auth-page -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>