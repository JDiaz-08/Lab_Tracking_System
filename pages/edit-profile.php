<?php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireUser();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

$errors  = [];
$success = '';

/* ── Upload photo ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_photo') {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['profile_pic'];
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Image must be under 2MB.';
        } else {
            $src = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file['tmp_name']));
            $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")->execute([$src, $user['id']]);
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
            $_SESSION['user'] = $user;
            $success = 'Profile photo updated.';
        }
    } else {
        $errors[] = 'Please select a valid image file.';
    }
}

/* ── Update personal info ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_info') {
    $firstName   = trim($_POST['first_name']   ?? '');
    $lastName    = trim($_POST['last_name']    ?? '');
    $middleName  = trim($_POST['middle_name']  ?? '');
    $courseLevel = (int)($_POST['course_level'] ?? 0);
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
            $db->prepare("UPDATE users SET first_name=?,last_name=?,middle_name=?,course_level=?,email=?,address=? WHERE id=?")
               ->execute([$firstName,$lastName,$middleName,$courseLevel,$email,$address,$user['id']]);
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
            $_SESSION['user'] = $user;
            $success = 'Profile updated successfully.';
        }
    }
}

/* ── Change password ── */
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
        $success = 'Password changed successfully.';
    }
}

$yearMap  = ['1'=>'1st Year','2'=>'2nd Year','3'=>'3rd Year','4'=>'4th Year','5'=>'5th Year'];
$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));
$hasPic   = !empty($user['profile_picture']);

$pageTitle = 'Edit Profile';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css?v=' . filemtime(__DIR__ . '/../assets/css/user.css') . '">';
require_once __DIR__ . '/../includes/user-navbar.php';
?>

<style>
/* ════════════════════════════════════════
   EDIT PROFILE PAGE
════════════════════════════════════════ */
.ep-page { min-height: 100vh; background: var(--gray-50); padding-top: 66px; }
.ep-inner { max-width: 1100px; margin: 0 auto; padding: 2.25rem 2rem; }

/* Page header */
.ep-header { margin-bottom: 1.75rem; }
.ep-header h1 { font-family: var(--font-display); font-size: 1.55rem; font-weight: 800; color: var(--navy); margin-bottom: 0.2rem; }
.ep-header p  { font-size: 0.875rem; color: var(--gray-600); font-weight: 300; }

/* Flash messages */
.ep-flash { margin-bottom: 1.25rem; }

/* Layout grid */
.ep-grid { display: grid; grid-template-columns: 256px 1fr; gap: 1.5rem; align-items: start; }
.ep-sidebar { display: flex; flex-direction: column; gap: 1rem; }
.ep-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* Base card */
.ep-card {
  background: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(15,40,84,0.06);
  overflow: hidden;
  transition: box-shadow 0.22s;
}
.ep-card:hover { box-shadow: 0 4px 18px rgba(15,40,84,0.09); }

/* ── PHOTO CARD ── */
.ep-photo-card { padding: 1.5rem 1.25rem; text-align: center; }
.ep-photo-wrap {
  position: relative; width: 88px; height: 88px;
  border-radius: 50%; margin: 0 auto 0.875rem; cursor: pointer;
}
.ep-photo-img {
  width: 88px; height: 88px; border-radius: 50%;
  object-fit: cover; border: 2.5px solid rgba(73,136,196,0.25); display: block;
}
.ep-photo-initials {
  width: 88px; height: 88px; border-radius: 50%;
  background: linear-gradient(135deg, var(--navy), var(--blue));
  border: 2.5px solid rgba(73,136,196,0.25);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; color: var(--light);
}
.ep-photo-overlay {
  position: absolute; inset: 0; border-radius: 50%;
  background: rgba(15,40,84,0.55);
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
  opacity: 0; transition: opacity 0.18s; cursor: pointer;
}
.ep-photo-wrap:hover .ep-photo-overlay { opacity: 1; }
.ep-overlay-icon  { font-size: 1rem; color: #fff; }
.ep-overlay-label { font-size: 0.58rem; font-weight: 700; color: #fff; letter-spacing: 0.8px; text-transform: uppercase; }

.ep-photo-name { font-family: var(--font-display); font-size: 0.93rem; font-weight: 700; color: var(--navy); margin-bottom: 0.15rem; }
.ep-photo-meta { font-size: 0.72rem; color: var(--gray-400); margin-bottom: 0.875rem; }
.ep-photo-hint { font-size: 0.68rem; color: var(--gray-400); line-height: 1.5; margin-bottom: 0.75rem; }

.ep-upload-btn {
  width: 100%; padding: 0.55rem 0.875rem;
  background: linear-gradient(135deg, var(--navy), var(--blue));
  color: #fff; border: none; border-radius: 8px;
  font-family: var(--font-body); font-size: 0.81rem; font-weight: 600;
  cursor: pointer; transition: all 0.22s;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
.ep-upload-btn:hover { opacity: 0.90; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(15,40,84,0.20); }

/* ── QUICK INFO CARD ── */
.ep-info-card { padding: 1.1rem 1.25rem; }
.ep-info-label-top {
  font-size: 0.63rem; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: var(--mid);
  margin-bottom: 0.875rem; padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--gray-100);
}
.ep-info-list { display: flex; flex-direction: column; gap: 0.75rem; }
.ep-info-item { display: flex; align-items: flex-start; gap: 9px; }
.ep-info-icon {
  width: 28px; height: 28px; border-radius: 6px;
  background: rgba(15,40,84,0.05);
  display: flex; align-items: center; justify-content: center;
  color: var(--navy); font-size: 0.80rem; flex-shrink: 0;
}
.ep-info-key { font-size: 0.63rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; color: var(--gray-400); margin-bottom: 1px; }
.ep-info-val { font-size: 0.83rem; font-weight: 500; color: var(--navy); }

/* ── FORM CARDS ── */
.ep-form-card-header {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 1.25rem 1.5rem 1.1rem;
  border-bottom: 1px solid var(--gray-100);
}
.ep-form-header-icon {
  width: 34px; height: 34px; border-radius: 8px;
  background: rgba(15,40,84,0.06);
  display: flex; align-items: center; justify-content: center;
  color: var(--navy); font-size: 0.88rem; flex-shrink: 0; margin-top: 1px;
}
.ep-form-header-title { font-family: var(--font-display); font-size: 0.98rem; font-weight: 800; color: var(--navy); margin-bottom: 0.15rem; }
.ep-form-header-desc  { font-size: 0.78rem; color: var(--gray-400); font-weight: 300; line-height: 1.5; }
.ep-form-body { padding: 1.35rem 1.5rem; }

/* Section divider label */
.ep-section-label {
  font-size: 0.63rem; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: var(--mid);
  margin: 1.1rem 0 0.875rem;
  display: flex; align-items: center; gap: 8px;
}
.ep-section-label::after { content: ''; flex: 1; height: 1px; background: var(--gray-100); }

/* Field group */
.ep-field { margin-bottom: 0.875rem; }
.ep-field label {
  display: block; font-size: 0.80rem; font-weight: 600;
  color: var(--navy); margin-bottom: 5px; letter-spacing: 0.1px;
}
.ep-field input,
.ep-field select {
  width: 100%; padding: 0.62rem 0.875rem;
  border: 1.5px solid var(--gray-200); border-radius: 8px;
  font-family: var(--font-body); font-size: 0.875rem;
  color: var(--navy); background: #fff;
  outline: none; transition: border-color 0.18s, box-shadow 0.18s;
  appearance: none;
}
.ep-field input:focus,
.ep-field select:focus { border-color: var(--mid); box-shadow: 0 0 0 3px rgba(73,136,196,0.10); }
.ep-field input:disabled,
.ep-field select:disabled {
  background: var(--gray-50); color: var(--gray-400);
  cursor: not-allowed; border-color: var(--gray-200);
}
.ep-field-hint { font-size: 0.70rem; color: var(--gray-400); margin-top: 4px; }

/* Two-column row */
.ep-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem; }

/* Locked field icon */
.ep-locked { position: relative; }
.ep-locked input { padding-right: 2.2rem; }
.ep-lock-ico {
  position: absolute; right: 0.7rem; top: 50%;
  transform: translateY(-50%);
  font-size: 0.76rem; color: var(--gray-400); pointer-events: none;
}

/* Password field */
.ep-pw-field { position: relative; }
.ep-pw-field input { padding-right: 2.5rem; }
.ep-pw-toggle {
  position: absolute; right: 0.65rem; top: 50%;
  transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  font-size: 0.85rem; color: var(--gray-400); padding: 0;
  line-height: 1; transition: color 0.18s;
  display: flex; align-items: center;
}
.ep-pw-toggle:hover { color: var(--navy); }

/* Strength bar */
.ep-strength { display: flex; align-items: center; gap: 9px; margin: 0.4rem 0 0.2rem; }
.ep-strength-track { flex: 1; height: 4px; background: var(--gray-200); border-radius: 100px; overflow: hidden; }
.ep-strength-fill  { height: 100%; border-radius: 100px; transition: width 0.28s, background 0.28s; width: 0%; }
.ep-strength-lbl   { font-size: 0.68rem; font-weight: 700; white-space: nowrap; min-width: 70px; text-align: right; }

/* Req / optional markers */
.ep-req      { color: #dc2626; font-size: 0.72rem; }
.ep-optional { font-size: 0.72rem; color: var(--gray-400); font-weight: 400; }

/* Select arrow */
.ep-field select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23475569' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.875rem center; padding-right: 2.4rem;
}

/* Save button */
.ep-save-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 0.72rem 1.75rem;
  background: linear-gradient(135deg, var(--navy), var(--blue));
  color: #fff; border: none; border-radius: 9px;
  font-family: var(--font-body); font-size: 0.875rem; font-weight: 600;
  cursor: pointer; transition: all 0.22s; margin-top: 0.5rem; letter-spacing: 0.15px;
}
.ep-save-btn:hover { opacity: 0.92; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(15,40,84,0.20); }
.ep-save-btn i { font-size: 0.85rem; }

/* Responsive */
@media (max-width: 900px) {
  .ep-grid    { grid-template-columns: 1fr; }
  .ep-sidebar { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
}
@media (max-width: 580px) {
  .ep-sidebar   { grid-template-columns: 1fr; }
  .ep-row       { grid-template-columns: 1fr; }
  .ep-form-body { padding: 1.1rem; }
  .ep-form-card-header { padding: 1rem 1.1rem 0.875rem; }
}
</style>

<div class="ep-page">
  <div class="ep-inner">

    <div class="ep-header">
      <h1>Edit Profile</h1>
      <p>Manage your personal information, profile photo, and account security.</p>
    </div>

    <!-- Flash messages -->
    <?php if ($success): ?>
      <div class="flash-msg flash-success ep-flash">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="flash-msg flash-error ep-flash">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <div class="ep-grid">

      <!-- ── SIDEBAR ── -->
      <div class="ep-sidebar">

        <!-- Photo card -->
        <div class="ep-card ep-photo-card">
          <div class="ep-photo-wrap" id="photoWrap">
            <?php if ($hasPic): ?>
              <img src="<?= htmlspecialchars($user['profile_picture']) ?>"
                   alt="Profile" class="ep-photo-img" id="photoPreview" />
            <?php else: ?>
              <div class="ep-photo-initials" id="photoInitials"><?= $initials ?></div>
              <img src="" alt="" class="ep-photo-img" id="photoPreview" style="display:none;" />
            <?php endif; ?>
            <label for="photoInput" class="ep-photo-overlay" title="Change photo">
              <i class="bi bi-camera ep-overlay-icon"></i>
              <span class="ep-overlay-label">Change</span>
            </label>
          </div>

          <div class="ep-photo-name">
            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
          </div>
          <div class="ep-photo-meta">
            <?= htmlspecialchars($user['student_id']) ?> &middot;
            <?= htmlspecialchars($user['course']) ?>
          </div>

          <form method="POST" action="" enctype="multipart/form-data" id="photoForm">
            <input type="hidden" name="action" value="upload_photo">
            <input type="file" name="profile_pic" id="photoInput"
                   accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" />
          </form>

          <div class="ep-photo-hint">JPG, PNG, GIF or WEBP — max 2MB</div>

          <button type="button" class="ep-upload-btn"
                  onclick="document.getElementById('photoInput').click()">
            <i class="bi bi-upload"></i> Upload New Photo
          </button>
        </div>

        <!-- Quick info card -->
        <div class="ep-card ep-info-card">
          <div class="ep-info-label-top">Account Details</div>
          <div class="ep-info-list">
            <div class="ep-info-item">
              <div class="ep-info-icon"><i class="bi bi-mortarboard"></i></div>
              <div>
                <div class="ep-info-key">Course</div>
                <div class="ep-info-val"><?= htmlspecialchars($user['course']) ?></div>
              </div>
            </div>
            <div class="ep-info-item">
              <div class="ep-info-icon"><i class="bi bi-layers"></i></div>
              <div>
                <div class="ep-info-key">Year Level</div>
                <div class="ep-info-val"><?= $yearMap[$user['course_level']] ?? '—' ?></div>
              </div>
            </div>
            <div class="ep-info-item">
              <div class="ep-info-icon"><i class="bi bi-person-badge"></i></div>
              <div>
                <div class="ep-info-key">Student ID</div>
                <div class="ep-info-val"><?= htmlspecialchars($user['student_id']) ?></div>
              </div>
            </div>
            <div class="ep-info-item">
              <div class="ep-info-icon"><i class="bi bi-hourglass-split"></i></div>
              <div>
                <div class="ep-info-key">Sessions Remaining</div>
                <div class="ep-info-val"><?= (int)($user['remaining_sessions'] ?? 30) ?> / 30</div>
              </div>
            </div>
            <div class="ep-info-item">
              <div class="ep-info-icon"><i class="bi bi-calendar-event"></i></div>
              <div>
                <div class="ep-info-key">Member Since</div>
                <div class="ep-info-val"><?= date('F Y', strtotime($user['created_at'])) ?></div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ── MAIN CONTENT ── -->
      <div class="ep-content">

        <!-- Personal Information -->
        <div class="ep-card">
          <div class="ep-form-card-header">
            <div class="ep-form-header-icon"><i class="bi bi-person"></i></div>
            <div>
              <div class="ep-form-header-title">Personal Information</div>
              <div class="ep-form-header-desc">Update your name, contact details, and year level.</div>
            </div>
          </div>
          <form method="POST" action="" class="ep-form-body">
            <input type="hidden" name="action" value="update_info">

            <div class="ep-section-label">Name</div>

            <div class="ep-row">
              <div class="ep-field">
                <label>First Name <span class="ep-req">*</span></label>
                <input type="text" name="first_name"
                       value="<?= htmlspecialchars($user['first_name']) ?>" required />
              </div>
              <div class="ep-field">
                <label>Last Name <span class="ep-req">*</span></label>
                <input type="text" name="last_name"
                       value="<?= htmlspecialchars($user['last_name']) ?>" required />
              </div>
            </div>

            <div class="ep-field">
              <label>Middle Name <span class="ep-optional">(optional)</span></label>
              <input type="text" name="middle_name"
                     value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>"
                     placeholder="Leave blank if none" />
            </div>

            <div class="ep-section-label">Academic</div>

            <div class="ep-row">
              <div class="ep-field">
                <label>Student ID</label>
                <div class="ep-locked">
                  <input type="text"
                         value="<?= htmlspecialchars($user['student_id']) ?>" disabled />
                  <i class="bi bi-lock-fill ep-lock-ico"></i>
                </div>
                <div class="ep-field-hint">Cannot be changed</div>
              </div>
              <div class="ep-field">
                <label>Course / Program</label>
                <div class="ep-locked">
                  <input type="text"
                         value="<?= htmlspecialchars($user['course']) ?>" disabled />
                  <i class="bi bi-lock-fill ep-lock-ico"></i>
                </div>
                <div class="ep-field-hint">Cannot be changed</div>
              </div>
            </div>

            <div class="ep-field" style="max-width:260px;">
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

            <div class="ep-field">
              <label>Email Address <span class="ep-req">*</span></label>
              <input type="email" name="email"
                     value="<?= htmlspecialchars($user['email']) ?>" required />
            </div>

            <div class="ep-field">
              <label>Home Address <span class="ep-req">*</span></label>
              <input type="text" name="address"
                     value="<?= htmlspecialchars($user['address']) ?>"
                     placeholder="House No., Street, Barangay, City" required />
            </div>

            <button type="submit" class="ep-save-btn">
              <i class="bi bi-floppy"></i> Save Changes
            </button>
          </form>
        </div>

        <!-- Change Password -->
        <div class="ep-card">
          <div class="ep-form-card-header">
            <div class="ep-form-header-icon"><i class="bi bi-shield-lock"></i></div>
            <div>
              <div class="ep-form-header-title">Change Password</div>
              <div class="ep-form-header-desc">Keep your account secure with a strong, unique password.</div>
            </div>
          </div>
          <form method="POST" action="" class="ep-form-body">
            <input type="hidden" name="action" value="change_password">

            <div class="ep-field">
              <label>Current Password <span class="ep-req">*</span></label>
              <div class="ep-pw-field">
                <input type="password" name="current_password" id="pwCurrent"
                       placeholder="Enter your current password" required />
                <button type="button" class="ep-pw-toggle" data-target="pwCurrent">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <div class="ep-row">
              <div class="ep-field">
                <label>New Password <span class="ep-req">*</span></label>
                <div class="ep-pw-field">
                  <input type="password" name="new_password" id="pwNew"
                         placeholder="At least 6 characters" required />
                  <button type="button" class="ep-pw-toggle" data-target="pwNew">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <!-- Strength bar -->
                <div class="ep-strength" id="strengthWrap" style="display:none;">
                  <div class="ep-strength-track">
                    <div class="ep-strength-fill" id="strengthFill"></div>
                  </div>
                  <span class="ep-strength-lbl" id="strengthLbl"></span>
                </div>
              </div>
              <div class="ep-field">
                <label>Confirm New Password <span class="ep-req">*</span></label>
                <div class="ep-pw-field">
                  <input type="password" name="confirm_password" id="pwConfirm"
                         placeholder="Re-enter new password" required />
                  <button type="button" class="ep-pw-toggle" data-target="pwConfirm">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
            </div>

            <button type="submit" class="ep-save-btn" style="background:linear-gradient(135deg,#1a3a6b,var(--mid));">
              <i class="bi bi-key"></i> Update Password
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
/* ── Photo preview & auto-upload ── */
document.getElementById('photoInput').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const preview  = document.getElementById('photoPreview');
    const initials = document.getElementById('photoInitials');
    preview.src           = e.target.result;
    preview.style.display = 'block';
    if (initials) initials.style.display = 'none';
  };
  reader.readAsDataURL(file);
  setTimeout(() => document.getElementById('photoForm').submit(), 300);
});

/* ── Password visibility toggle ── */
document.querySelectorAll('.ep-pw-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp  = document.getElementById(btn.dataset.target);
    const icon = btn.querySelector('i');
    if (!inp) return;
    inp.type       = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  });
});

/* ── Password strength meter ── */
document.getElementById('pwNew').addEventListener('input', function () {
  const val   = this.value;
  const wrap  = document.getElementById('strengthWrap');
  const fill  = document.getElementById('strengthFill');
  const lbl   = document.getElementById('strengthLbl');
  if (!val) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'flex';
  let score = 0;
  if (val.length >= 6)          score++;
  if (val.length >= 10)         score++;
  if (/[A-Z]/.test(val))        score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    { pct: '20%', color: '#dc2626', text: 'Very Weak' },
    { pct: '40%', color: '#d97706', text: 'Weak'      },
    { pct: '60%', color: '#ca8a04', text: 'Fair'      },
    { pct: '80%', color: '#16a34a', text: 'Strong'    },
    { pct: '100%',color: '#15803d', text: 'Very Strong'},
  ];
  const lvl = levels[Math.min(score, 4)];
  fill.style.width      = lvl.pct;
  fill.style.background = lvl.color;
  lbl.textContent       = lvl.text;
  lbl.style.color       = lvl.color;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>