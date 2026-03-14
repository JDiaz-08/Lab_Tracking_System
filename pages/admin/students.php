<?php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash = '';
$flashType = 'success';

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $fn  = trim($_POST['first_name']  ?? '');
        $ln  = trim($_POST['last_name']   ?? '');
        $mn  = trim($_POST['middle_name'] ?? '');
        $sid = trim($_POST['student_id']  ?? '');
        $crs = trim($_POST['course']      ?? '');
        $lvl = (int)($_POST['course_level'] ?? 1);
        $em  = trim($_POST['email']       ?? '');
        $adr = trim($_POST['address']     ?? '');
        $pw  = trim($_POST['password']    ?? 'password123');
        $rem = (int)($_POST['remaining_sessions'] ?? 30);

        $dup = $db->prepare("SELECT id FROM users WHERE student_id=? OR email=?");
        $dup->execute([$sid, $em]);
        if ($dup->fetch()) {
            $flash = 'Student ID or Email already exists.'; $flashType='error';
        } else {
            $db->prepare("INSERT INTO users (first_name,last_name,middle_name,student_id,course,course_level,email,address,password,remaining_sessions) VALUES (?,?,?,?,?,?,?,?,?,?)")
               ->execute([$fn,$ln,$mn,$sid,$crs,$lvl,$em,$adr,password_hash($pw,PASSWORD_DEFAULT),$rem]);
            $flash = "Student {$fn} {$ln} added successfully.";
        }
    }

    elseif ($action === 'edit') {
        $id  = (int)$_POST['user_id'];
        $fn  = trim($_POST['first_name']  ?? '');
        $ln  = trim($_POST['last_name']   ?? '');
        $mn  = trim($_POST['middle_name'] ?? '');
        $lvl = (int)($_POST['course_level'] ?? 1);
        $em  = trim($_POST['email']       ?? '');
        $adr = trim($_POST['address']     ?? '');
        $rem = (int)($_POST['remaining_sessions'] ?? 30);

        $db->prepare("UPDATE users SET first_name=?,last_name=?,middle_name=?,course_level=?,email=?,address=?,remaining_sessions=? WHERE id=?")
           ->execute([$fn,$ln,$mn,$lvl,$em,$adr,$rem,$id]);
        $flash = 'Student updated.';
    }

    elseif ($action === 'delete') {
        $id = (int)$_POST['user_id'];
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $flash = 'Student deleted.';
    }

    elseif ($action === 'reset_all') {
        $db->exec("UPDATE users SET remaining_sessions = 30");
        $flash = 'All sessions reset to 30.';
    }

    header('Location: students.php' . ($flash ? '?flash=' . urlencode($flash) . '&ft=' . $flashType : ''));
    exit;
}

if (isset($_GET['flash'])) {
    $flash = $_GET['flash'];
    $flashType = $_GET['ft'] ?? 'success';
}

// ---- Fetch students ----
$search = trim($_GET['q'] ?? '');
if ($search) {
    $s = $db->prepare("
        SELECT * FROM users
        WHERE first_name LIKE ? OR last_name LIKE ? OR student_id LIKE ? OR course LIKE ? OR email LIKE ?
        ORDER BY student_id ASC
    ");
    $s->execute(array_fill(0, 5, "%{$search}%"));
} else {
    $s = $db->query("SELECT * FROM users ORDER BY student_id ASC");
}
$students = $s->fetchAll();

$yearMap = [1=>'1st',2=>'2nd',3=>'3rd',4=>'4th',5=>'5th'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Students — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">Students Information</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <?= $flashType === 'error' ? '❌' : '✅' ?> <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <div class="a-card">
      <div class="a-card-body">

        <!-- Top action buttons -->
        <div style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
          <button class="a-btn a-btn-primary" data-open-modal="addStudentModal">
            Add Students
          </button>
          <form method="POST" action="" style="display:inline;"
                onsubmit="return confirm('Reset ALL students to 30 sessions?')">
            <input type="hidden" name="action" value="reset_all">
            <button type="submit" class="a-btn a-btn-red">Reset All Session</button>
          </form>
        </div>

        <!-- Table controls -->
        <div class="a-table-controls">
          <div class="a-entries-wrap">
            <select id="stuSelect" class="a-entries-select">
              <option>10</option><option>25</option><option>50</option><option>100</option>
            </select>
            <span>entries per page</span>
          </div>
          <div class="a-search-wrap">
            <label>Search:</label>
            <input type="text" id="stuSearch" class="a-search-box"
                   value="<?= htmlspecialchars($search) ?>" placeholder="Search..." />
          </div>
        </div>

        <!-- Table -->
        <div class="a-table-wrap">
          <table class="a-table" id="stuTable">
            <thead>
              <tr>
                <th class="a-sortable" data-col="0">ID Number <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="1">Name <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="2">Year Level <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="3">Course <span class="a-sort-icon">⇅</span></th>
                <th class="a-sortable" data-col="4">Remaining Session <span class="a-sort-icon">⇅</span></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="stuBody">
              <?php if (empty($students)): ?>
                <tr class="a-table-empty"><td colspan="6">No data available</td></tr>
              <?php else: ?>
                <?php foreach ($students as $s): ?>
                  <tr class="a-data-row">
                    <td><?= htmlspecialchars($s['student_id']) ?></td>
                    <td><?= htmlspecialchars($s['first_name'].' '.($s['middle_name']?$s['middle_name'][0].'. ':'').$s['last_name']) ?></td>
                    <td><?= $s['course_level'] ?></td>
                    <td><?= htmlspecialchars($s['course']) ?></td>
                    <td><?= (int)($s['remaining_sessions'] ?? 30) ?></td>
                    <td>
                      <button class="a-btn a-btn-primary a-btn-sm"
                        onclick="openEdit(<?= htmlspecialchars(json_encode($s)) ?>)">Edit</button>
                      <form method="POST" action="" style="display:inline;"
                            onsubmit="return confirm('Delete this student?')">
                        <input type="hidden" name="action"  value="delete">
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="a-btn a-btn-red a-btn-sm">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="a-table-footer">
          <div class="a-table-info" id="stuInfo"></div>
          <div class="a-pagination"  id="stuPag"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Add Student Modal -->
<div class="a-modal-overlay" id="addStudentModal">
  <div class="a-modal" style="max-width:500px;">
    <div class="a-modal-header">
      Add Student
      <button class="a-modal-close" data-close-modal="addStudentModal">×</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="add">
      <div class="a-modal-body" style="display:grid;gap:0.6rem;">
        <div class="a-mrow"><label class="a-mlabel">First Name *</label>
          <input type="text" name="first_name" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Last Name *</label>
          <input type="text" name="last_name"  class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Middle Name</label>
          <input type="text" name="middle_name" class="a-minput" /></div>
        <div class="a-mrow"><label class="a-mlabel">Student ID *</label>
          <input type="text" name="student_id" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Course *</label>
          <input type="text" name="course" class="a-minput" placeholder="BSIT" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Year Level *</label>
          <select name="course_level" class="a-minput">
            <?php for($y=1;$y<=5;$y++): ?>
              <option value="<?=$y?>"><?=$y?>th Year</option>
            <?php endfor; ?>
          </select></div>
        <div class="a-mrow"><label class="a-mlabel">Email *</label>
          <input type="email" name="email" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Address *</label>
          <input type="text" name="address" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Password</label>
          <input type="text" name="password" class="a-minput" placeholder="Default: password123"/></div>
        <div class="a-mrow"><label class="a-mlabel">Sessions</label>
          <input type="number" name="remaining_sessions" class="a-minput" value="30" min="0" max="30"/></div>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" data-close-modal="addStudentModal">Cancel</button>
        <button type="submit" class="a-btn a-btn-primary">Add Student</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Student Modal -->
<div class="a-modal-overlay" id="editStudentModal">
  <div class="a-modal" style="max-width:500px;">
    <div class="a-modal-header">
      Edit Student
      <button class="a-modal-close" data-close-modal="editStudentModal">×</button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action"  value="edit">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="a-modal-body" style="display:grid;gap:0.6rem;">
        <div class="a-mrow"><label class="a-mlabel">First Name *</label>
          <input type="text"  name="first_name"  id="editFN" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Last Name *</label>
          <input type="text"  name="last_name"   id="editLN" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Middle Name</label>
          <input type="text"  name="middle_name" id="editMN" class="a-minput" /></div>
        <div class="a-mrow"><label class="a-mlabel">Student ID</label>
          <input type="text"  id="editSID" class="a-minput" disabled /></div>
        <div class="a-mrow"><label class="a-mlabel">Course</label>
          <input type="text"  id="editCRS" class="a-minput" disabled /></div>
        <div class="a-mrow"><label class="a-mlabel">Year Level *</label>
          <select name="course_level" id="editLVL" class="a-minput">
            <?php for($y=1;$y<=5;$y++): ?>
              <option value="<?=$y?>"><?=$y?>th Year</option>
            <?php endfor; ?>
          </select></div>
        <div class="a-mrow"><label class="a-mlabel">Email *</label>
          <input type="email" name="email"   id="editEM" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Address *</label>
          <input type="text"  name="address" id="editADR" class="a-minput" required /></div>
        <div class="a-mrow"><label class="a-mlabel">Sessions</label>
          <input type="number" name="remaining_sessions" id="editREM" class="a-minput" min="0" max="30"/></div>
      </div>
      <div class="a-modal-footer">
        <button type="button" class="a-btn a-btn-gray" data-close-modal="editStudentModal">Cancel</button>
        <button type="submit" class="a-btn a-btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(s) {
  document.getElementById('editUserId').value = s.id;
  document.getElementById('editFN').value  = s.first_name;
  document.getElementById('editLN').value  = s.last_name;
  document.getElementById('editMN').value  = s.middle_name || '';
  document.getElementById('editSID').value = s.student_id;
  document.getElementById('editCRS').value = s.course;
  document.getElementById('editLVL').value = s.course_level;
  document.getElementById('editEM').value  = s.email;
  document.getElementById('editADR').value = s.address;
  document.getElementById('editREM').value = s.remaining_sessions ?? 30;
  openModal('editStudentModal');
}
</script>
<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
initAdminTable({ tableId:'stuTable', bodyId:'stuBody', infoId:'stuInfo', pagId:'stuPag', searchId:'stuSearch', selectId:'stuSelect' });
</script>
</body></html>