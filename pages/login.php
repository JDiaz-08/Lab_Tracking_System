<?php
session_start();

// If already logged in, redirect
if (!empty($_SESSION['user_id']))  { header('Location: dashboard.php'); exit; }
if (!empty($_SESSION['admin_id'])) { header('Location: admin/index.php'); exit; }

$base = '../';
require_once __DIR__ . '/../config/database.php';

$errors   = [];
$success  = '';
$activeTab = 'login';

/* ============================================================
   HANDLE LOGIN
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    /* ---- LOGIN ---- */
    if ($action === 'login') {
        $activeTab  = 'login';
        $identifier = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (!$identifier || !$password) {
            $errors[] = 'Please fill in all fields.';
        } else {
            $db = getDB();

            // Check admin first
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$identifier]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];
                header('Location: admin/index.php');
                exit;
            }

            // Check student (by student_id OR email)
            $stmt = $db->prepare(
                "SELECT * FROM users WHERE student_id = ? OR email = ?"
            );
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = $user;
                header('Location: dashboard.php');
                exit;
            }

            $errors[] = 'Invalid credentials. Please check your ID/email and password.';
        }
    }

    /* ---- REGISTER ---- */
    elseif ($action === 'register') {
        $activeTab = 'register';

        $firstName   = trim($_POST['first_name']   ?? '');
        $lastName    = trim($_POST['last_name']    ?? '');
        $middleName  = trim($_POST['middle_name']  ?? '');
        $studentId   = trim($_POST['student_id']   ?? '');
        $course      = trim($_POST['course']       ?? '');
        $courseLevel = (int) ($_POST['course_level'] ?? 0);
        $email       = trim($_POST['email']        ?? '');
        $address     = trim($_POST['address']      ?? '');
        $password    = $_POST['password']          ?? '';
        $confirm     = $_POST['confirm_password']  ?? '';

        // Validation
        if (!$firstName || !$lastName || !$studentId || !$course || !$courseLevel || !$email || !$address || !$password) {
            $errors[] = 'All required fields must be filled.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $db = getDB();

            // Check duplicates
            $dup = $db->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
            $dup->execute([$studentId, $email]);

            if ($dup->fetch()) {
                $errors[] = 'An account with that Student ID or email already exists.';
            } else {
                $db->prepare("
                    INSERT INTO users
                        (first_name, last_name, middle_name, student_id, course, course_level, email, address, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    $firstName, $lastName, $middleName, $studentId,
                    $course, $courseLevel, $email, $address,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                $activeTab = 'login';
                $success   = 'Account created successfully! You can now log in.';
            }
        }
    }

    /* ---- FORGOT PASSWORD ---- */
    elseif ($action === 'forgot') {
        $activeTab = 'forgot';
        $email = trim($_POST['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // In a real system, send a reset email.
            // For now, just confirm the request was received.
            $success   = 'If that email is registered, a reset link has been sent.';
            $activeTab = 'login';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Link user.css for the flash messages (minimal)
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
?>

<div class="auth-page">
  <div class="auth-wrapper">

    <!-- LEFT PANEL -->
    <div class="auth-left">
      <div class="ccs-logo-wrap">
        <img src="<?= $base ?>assets/images/ccs-logo.png" alt="CCS Logo" class="ccs-real-logo" />
        <span class="ccs-label">College of Computer Studies</span>
      </div>
      <h2>UC Computer Laboratory System</h2>
      <p>
        Log in to access the laboratory management portal. Track your sit-in sessions,
        check lab availability, and manage your academic computer laboratory usage —
        all in one place.
      </p>
    </div>

    <!-- RIGHT PANEL -->
    <div class="auth-right">

      <!-- Flash messages -->
      <?php if ($success): ?>
        <div class="flash-msg flash-success">✅ <?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="flash-msg flash-error">
          ❌ <?= implode('<br>❌ ', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="auth-tabs">
        <button class="tab-btn <?= $activeTab === 'login'    ? 'active' : '' ?>" data-tab="login">Login</button>
        <button class="tab-btn <?= $activeTab === 'register' ? 'active' : '' ?>" data-tab="register">Register</button>
        <button class="tab-btn <?= $activeTab === 'forgot'   ? 'active' : '' ?>" data-tab="forgot">Forgot</button>
      </div>

      <!-- ======================== LOGIN ======================== -->
      <div class="auth-panel <?= $activeTab === 'login' ? 'active' : '' ?>" id="panel-login">
        <h3>Welcome Back</h3>
        <p class="sub-text">Sign in with your University ID or email.</p>

        <form method="POST" action="">
          <input type="hidden" name="action" value="login">

          <div class="form-group">
            <label for="login-id">University ID / Email</label>
            <input type="text" id="login-id" name="username"
              placeholder="e.g. 22-12345 or you@uc.edu.ph"
              autocomplete="username" required />
          </div>

          <div class="form-group">
            <label for="login-pass">Password</label>
            <div class="input-wrapper">
              <input type="password" id="login-pass" name="password"
                placeholder="Enter your password"
                autocomplete="current-password" required />
              <span class="input-icon toggle-pass">👁️</span>
            </div>
          </div>

          <div class="form-footer">
            <label class="remember-me">
              <input type="checkbox" name="remember" /> Remember me
            </label>
            <span class="forgot-link">Forgot password?</span>
          </div>

          <button type="submit" class="btn-submit">Sign In</button>
          <div class="divider">or</div>
          <p class="switch-auth">
            Don't have an account? <a data-switch="register">Create one</a>
          </p>
        </form>
      </div>

      <!-- ======================== REGISTER ======================== -->
      <div class="auth-panel <?= $activeTab === 'register' ? 'active' : '' ?>" id="panel-register">
        <h3>Create Account</h3>
        <p class="sub-text">Register using your university details.</p>

        <form method="POST" action="">
          <input type="hidden" name="action" value="register">

          <div class="form-row">
            <div class="form-group">
              <label for="reg-fname">First Name *</label>
              <input type="text" id="reg-fname" name="first_name" placeholder="Juan" required />
            </div>
            <div class="form-group">
              <label for="reg-lname">Last Name *</label>
              <input type="text" id="reg-lname" name="last_name" placeholder="Dela Cruz" required />
            </div>
          </div>

          <div class="form-group">
            <label for="reg-mname">Middle Name</label>
            <input type="text" id="reg-mname" name="middle_name" placeholder="Santos (optional)" />
          </div>

          <div class="form-group">
            <label for="reg-id">University ID Number *</label>
            <input type="text" id="reg-id" name="student_id" placeholder="e.g. 22-12345" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="reg-course">Course / Program *</label>
              <select id="reg-course" name="course" required>
                <option value="" disabled selected>Select course</option>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSCA">BSCA</option>
                <option value="BSBA">BSBA</option>
              </select>
            </div>
            <div class="form-group">
              <label for="reg-level">Year Level *</label>
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

          <div class="form-group">
            <label for="reg-email">Email Address *</label>
            <input type="email" id="reg-email" name="email" placeholder="juandelacruz@gmail.com" required />
          </div>

          <div class="form-group">
            <label for="reg-address">Address *</label>
            <input type="text" id="reg-address" name="address"
              placeholder="House No., Street, Barangay, City" required />
          </div>

          <div class="form-group">
            <label for="reg-pass">Password *</label>
            <div class="input-wrapper">
              <input type="password" id="reg-pass" name="password"
                placeholder="At least 6 characters"
                autocomplete="new-password" required />
              <span class="input-icon toggle-pass">👁️</span>
            </div>
          </div>

          <div class="form-group">
            <label for="reg-confirm">Confirm Password *</label>
            <div class="input-wrapper">
              <input type="password" id="reg-confirm" name="confirm_password"
                placeholder="Re-enter your password"
                autocomplete="new-password" required />
              <span class="input-icon toggle-pass">👁️</span>
            </div>
          </div>

          <button type="submit" class="btn-submit">Create Account</button>

          <p class="terms-text">
            By registering, you agree to the
            <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.
          </p>
          <div class="divider">or</div>
          <p class="switch-auth">
            Already have an account? <a data-switch="login">Sign in</a>
          </p>
        </form>
      </div>

      <!-- ======================== FORGOT ======================== -->
      <div class="auth-panel <?= $activeTab === 'forgot' ? 'active' : '' ?>" id="panel-forgot">
        <h3>Reset Password</h3>
        <p class="sub-text">We'll send a reset link to your registered email.</p>

        <div class="alert alert-info">
          Enter your university email address and we'll send you instructions to reset your password.
        </div>

        <form method="POST" action="">
          <input type="hidden" name="action" value="forgot">

          <div class="form-group">
            <label for="forgot-email">Email Address</label>
            <input type="email" id="forgot-email" name="email"
              placeholder="juandelacruz@gmail.com" required />
          </div>

          <button type="submit" class="btn-submit">Send Reset Link</button>
          <div class="divider">or</div>
          <p class="switch-auth">
            Remember your password? <a data-switch="login">Back to Login</a>
          </p>
        </form>
      </div>

    </div><!-- /.auth-right -->
  </div><!-- /.auth-wrapper -->
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>