<?php
session_start();
$base = '../';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireUser();
$db = getDB();

// Refresh user from DB
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

$errors  = [];
$success = '';

/* ============================================================
   HANDLE PROFILE PICTURE UPLOAD
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_photo') {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['profile_pic'];
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxBytes = 2 * 1024 * 1024; // 2MB

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errors[] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
        } elseif ($file['size'] > $maxBytes) {
            $errors[] = 'Image must be under 2MB.';
        } else {
            $data = base64_encode(file_get_contents($file['tmp_name']));
            $src  = 'data:' . $mime . ';base64,' . $data;

            $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")
               ->execute([$src, $user['id']]);

            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
            $_SESSION['user'] = $user;
            $success = 'Profile photo updated!';
        }
    } else {
        $errors[] = 'Please select a valid image file.';
    }
}

/* ============================================================
   HANDLE PROFILE INFO UPDATE
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {
    $firstName   = trim($_POST['first_name']   ?? '');
    $lastName    = trim($_POST['last_name']    ?? '');
    $middleName  = trim($_POST['middle_name']  ?? '');
    $courseLevel = (int) ($_POST['course_level'] ?? 0);
    $email       = trim($_POST['email']        ?? '');
    $address     = trim($_POST['address']      ?? '');

    if (!$firstName || !$lastName || !$courseLevel || !$email || !$address) {
        $errors[] = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $dup = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $dup->execute([$email, $user['id']]);
        if ($dup->fetch()) {
            $errors[] = 'That email is already in use by another account.';
        } else {
            $db->prepare("
                UPDATE users SET first_name=?, last_name=?, middle_name=?,
                                 course_level=?, email=?, address=?
                WHERE id=?
            ")->execute([$firstName, $lastName, $middleName,
                         $courseLevel, $email, $address, $user['id']]);

            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
            $_SESSION['user'] = $user;
            $success = 'Profile updated successfully!';
        }
    }
}

/* ============================================================
   HANDLE PASSWORD CHANGE
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$newPass || !$confirm) {
        $errors[] = 'All password fields are required.';
    } elseif (!password_verify($current, $user['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPass) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    } elseif ($newPass !== $confirm) {
        $errors[] = 'New passwords do not match.';
    } else {
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")
           ->execute([password_hash($newPass, PASSWORD_DEFAULT), $user['id']]);
        $success = 'Password changed successfully!';
    }
}

$yearMap   = ['1' => '1st Year', '2' => '2nd Year', '3' => '3rd Year',
              '4' => '4th Year', '5' => '5th Year'];
$initials  = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));
$hasPic    = !empty($user['profile_picture']);

$pageTitle = 'Edit Profile';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css">';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/edit-profile.css">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<div class="user-page">
  <div class="user-page-inner">

    <!-- Page heading -->
    <div class="ep-page-header">
      <div>
        <h1 class="ep-title">Edit Profile</h1>
        <p class="ep-subtitle">Manage your personal information, photo, and account security.</p>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="flash-msg flash-success ep-flash">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="flash-msg flash-error ep-flash">
        ❌ <?= implode('<br>❌ ', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <div class="ep-grid">

      <!-- ============ LEFT COLUMN ============ -->
      <div class="ep-left">

        <!-- Profile Photo Card -->
        <div class="ep-card ep-photo-card">
          <div class="ep-photo-wrap" id="photoWrap">
            <?php if ($hasPic): ?>
              <img src="<?= htmlspecialchars($user['profile_picture']) ?>"
                   alt="Profile Photo" class="ep-photo-img" id="photoPreview" />
            <?php else: ?>
              <div class="ep-photo-initials" id="photoInitials"><?= $initials ?></div>
              <img src="" alt="" class="ep-photo-img" id="photoPreview"
                   style="display:none;" />
            <?php endif; ?>

            <!-- Overlay trigger -->
            <label for="photoInput" class="ep-photo-overlay" title="Change photo">
              <span class="ep-camera-icon">📷</span>
              <span class="ep-camera-label">Change Photo</span>
            </label>
          </div>

          <div class="ep-photo-info">
            <div class="ep-photo-name">
              <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
            </div>
            <div class="ep-photo-meta">
              <?= htmlspecialchars($user['student_id']) ?> &nbsp;·&nbsp;
              <?= htmlspecialchars($user['course']) ?>
            </div>
          </div>

          <!-- Hidden upload form -->
          <form method="POST" action="" enctype="multipart/form-data" id="photoForm">
            <input type="hidden" name="action" value="upload_photo">
            <input type="file" name="profile_pic" id="photoInput"
                   accept="image/jpeg,image/png,image/gif,image/webp"
                   style="display:none;" />
          </form>

          <div class="ep-photo-hint">JPG, PNG, GIF or WEBP &mdash; max 2MB</div>

          <button type="button" class="ep-upload-btn" onclick="document.getElementById('photoInput').click()">
            Upload New Photo
          </button>
        </div>

        <!-- Quick Info Card -->
        <div class="ep-card ep-quick-card">
          <div class="ep-quick-title">Account Details</div>
          <div class="ep-quick-list">
            <div class="ep-quick-item">
              <span class="ep-quick-icon">🎓</span>
              <div>
                <div class="ep-quick-label">Course</div>
                <div class="ep-quick-val"><?= htmlspecialchars($user['course']) ?></div>
              </div>
            </div>
            <div class="ep-quick-item">
              <span class="ep-quick-icon">📅</span>
              <div>
                <div class="ep-quick-label">Year Level</div>
                <div class="ep-quick-val"><?= $yearMap[$user['course_level']] ?? '—' ?></div>
              </div>
            </div>
            <div class="ep-quick-item">
              <span class="ep-quick-icon">🪪</span>
              <div>
                <div class="ep-quick-label">Student ID</div>
                <div class="ep-quick-val"><?= htmlspecialchars($user['student_id']) ?></div>
              </div>
            </div>
            <div class="ep-quick-item">
              <span class="ep-quick-icon">⏱️</span>
              <div>
                <div class="ep-quick-label">Sessions Remaining</div>
                <div class="ep-quick-val"><?= (int)($user['remaining_sessions'] ?? 30) ?> / 30</div>
              </div>
            </div>
            <div class="ep-quick-item">
              <span class="ep-quick-icon">🗓️</span>
              <div>
                <div class="ep-quick-label">Member Since</div>
                <div class="ep-quick-val"><?= date('F Y', strtotime($user['created_at'])) ?></div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /.ep-left -->

      <!-- ============ RIGHT COLUMN ============ -->
      <div class="ep-right">

        <!-- Personal Information -->
        <div class="ep-card ep-form-card">
          <div class="ep-form-card-header">
            <span class="ep-form-icon">👤</span>
            <div>
              <h2 class="ep-form-title">Personal Information</h2>
              <p class="ep-form-desc">Update your name, contact details, and year level.</p>
            </div>
          </div>

          <form method="POST" action="" class="ep-form">
            <input type="hidden" name="action" value="update_info">

            <div class="ep-section-label">Name</div>
            <div class="uf-row">
              <div class="uf-group">
                <label>First Name <span class="ep-req">*</span></label>
                <input type="text" name="first_name"
                  value="<?= htmlspecialchars($user['first_name']) ?>" required />
              </div>
              <div class="uf-group">
                <label>Last Name <span class="ep-req">*</span></label>
                <input type="text" name="last_name"
                  value="<?= htmlspecialchars($user['last_name']) ?>" required />
              </div>
            </div>

            <div class="uf-group">
              <label>Middle Name <span class="ep-optional">(optional)</span></label>
              <input type="text" name="middle_name"
                value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>"
                placeholder="Leave blank if none" />
            </div>

            <div class="ep-section-label">Academic</div>
            <div class="uf-row">
              <div class="uf-group">
                <label>Student ID</label>
                <div class="ep-locked-field">
                  <input type="text" value="<?= htmlspecialchars($user['student_id']) ?>" disabled />
                  <span class="ep-lock-icon">🔒</span>
                </div>
                <div class="uf-hint">Cannot be changed</div>
              </div>
              <div class="uf-group">
                <label>Course / Program</label>
                <div class="ep-locked-field">
                  <input type="text" value="<?= htmlspecialchars($user['course']) ?>" disabled />
                  <span class="ep-lock-icon">🔒</span>
                </div>
                <div class="uf-hint">Cannot be changed</div>
              </div>
            </div>

            <div class="uf-group">
              <label>Year Level <span class="ep-req">*</span></label>
              <select name="course_level" required>
                <?php for ($y = 1; $y <= 5; $y++): ?>
                  <option value="<?= $y ?>" <?= $user['course_level'] == $y ? 'selected' : '' ?>>
                    <?= $yearMap[$y] ?>
                  </option>
                <?php endfor; ?>
              </select>
            </div>

            <div class="ep-section-label">Contact</div>
            <div class="uf-group">
              <label>Email Address <span class="ep-req">*</span></label>
              <input type="email" name="email"
                value="<?= htmlspecialchars($user['email']) ?>" required />
            </div>

            <div class="uf-group">
              <label>Address <span class="ep-req">*</span></label>
              <input type="text" name="address"
                value="<?= htmlspecialchars($user['address']) ?>"
                placeholder="House No., Street, Barangay, City" required />
            </div>

            <button type="submit" class="ep-save-btn">
              <span>💾</span> Save Changes
            </button>
          </form>
        </div>

        <!-- Change Password -->
        <div class="ep-card ep-form-card">
          <div class="ep-form-card-header">
            <span class="ep-form-icon">🔐</span>
            <div>
              <h2 class="ep-form-title">Change Password</h2>
              <p class="ep-form-desc">Keep your account secure with a strong password.</p>
            </div>
          </div>

          <form method="POST" action="" class="ep-form">
            <input type="hidden" name="action" value="change_password">

            <div class="uf-group">
              <label>Current Password <span class="ep-req">*</span></label>
              <div class="ep-pw-wrap">
                <input type="password" name="current_password" id="pwCurrent"
                  placeholder="Enter your current password" required />
                <button type="button" class="ep-pw-toggle" data-target="pwCurrent">👁️</button>
              </div>
            </div>

            <div class="uf-row">
              <div class="uf-group">
                <label>New Password <span class="ep-req">*</span></label>
                <div class="ep-pw-wrap">
                  <input type="password" name="new_password" id="pwNew"
                    placeholder="At least 6 characters" required />
                  <button type="button" class="ep-pw-toggle" data-target="pwNew">👁️</button>
                </div>
              </div>
              <div class="uf-group">
                <label>Confirm New Password <span class="ep-req">*</span></label>
                <div class="ep-pw-wrap">
                  <input type="password" name="confirm_password" id="pwConfirm"
                    placeholder="Re-enter new password" required />
                  <button type="button" class="ep-pw-toggle" data-target="pwConfirm">👁️</button>
                </div>
              </div>
            </div>

            <!-- Password strength bar -->
            <div class="ep-strength-wrap" id="strengthWrap" style="display:none;">
              <div class="ep-strength-bar">
                <div class="ep-strength-fill" id="strengthFill"></div>
              </div>
              <span class="ep-strength-label" id="strengthLabel"></span>
            </div>

            <button type="submit" class="ep-save-btn ep-save-btn-pw">
              <span>🔑</span> Update Password
            </button>
          </form>
        </div>

      </div><!-- /.ep-right -->
    </div><!-- /.ep-grid -->
  </div>
</div>

<script>
// ---- Live photo preview ----
document.getElementById('photoInput').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const preview  = document.getElementById('photoPreview');
    const initials = document.getElementById('photoInitials');
    preview.src          = e.target.result;
    preview.style.display = 'block';
    if (initials) initials.style.display = 'none';
  };
  reader.readAsDataURL(file);

  // Auto-submit after preview loaded
  setTimeout(() => document.getElementById('photoForm').submit(), 300);
});

// ---- Password toggle ----
document.querySelectorAll('.ep-pw-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById(btn.dataset.target);
    inp.type        = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
  });
});

// ---- Password strength ----
document.getElementById('pwNew').addEventListener('input', function () {
  const val   = this.value;
  const wrap  = document.getElementById('strengthWrap');
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');

  if (!val) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'flex';

  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { pct: '20%', color: '#dc2626', text: 'Very Weak' },
    { pct: '40%', color: '#d97706', text: 'Weak' },
    { pct: '60%', color: '#ca8a04', text: 'Fair' },
    { pct: '80%', color: '#16a34a', text: 'Strong' },
    { pct: '100%',color: '#15803d', text: 'Very Strong' },
  ];
  const lvl = levels[Math.min(score, 4)];
  fill.style.width      = lvl.pct;
  fill.style.background = lvl.color;
  label.textContent     = lvl.text;
  label.style.color     = lvl.color;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>