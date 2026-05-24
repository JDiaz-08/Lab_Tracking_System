<?php
// FILE: pages/admin/pc-control.php
session_start();
$base = '../../';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$db = getDB();

$flash     = '';
$flashType = 'success';
$labRooms  = ['524','526','528','530','542','Mac Laboratory'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_pc') {
        $pcId   = (int)($_POST['pc_id'] ?? 0);
        $newSt  = trim($_POST['new_status'] ?? '');
        if ($pcId > 0 && in_array($newSt, ['available', 'disabled', 'unavailable', 'maintenance'])) {
            $db->prepare("UPDATE pcs SET status = ?, occupied_by = NULL WHERE id = ?")->execute([$newSt, $pcId]);
            $flash = "PC status updated to " . ucfirst($newSt) . ".";
        }
    }

    if ($action === 'bulk_status') {
        $lab = trim($_POST['lab_room'] ?? '');
        $newSt = trim($_POST['new_status'] ?? '');
        if (in_array($lab, $labRooms) && in_array($newSt, ['available', 'unavailable', 'maintenance'])) {
            if ($newSt === 'available') {
                // Set all disabled, unavailable, and maintenance PCs in this lab to available
                $db->prepare("
                    UPDATE pcs 
                    SET status = 'available' 
                    WHERE lab_room = ? AND status IN ('disabled', 'unavailable', 'maintenance')
                ")->execute([$lab]);
                $flash = "All disabled, unavailable, and maintenance PCs in Lab $lab are now Available.";
            } elseif ($newSt === 'unavailable') {
                // Set all available PCs in this lab to disabled
                $db->prepare("
                    UPDATE pcs 
                    SET status = 'disabled' 
                    WHERE lab_room = ? AND status = 'available'
                ")->execute([$lab]);
                $flash = "All available PCs in Lab $lab have been set to Unavailable.";
            } elseif ($newSt === 'maintenance') {
                // Set all available PCs in this lab to maintenance
                $db->prepare("
                    UPDATE pcs 
                    SET status = 'maintenance' 
                    WHERE lab_room = ? AND status = 'available'
                ")->execute([$lab]);
                $flash = "All available PCs in Lab $lab have been set to Maintenance.";
            }
        }
    }

    if ($action === 'force_release') {
        $pcId = (int)($_POST['pc_id'] ?? 0);
        if ($pcId > 0) {
            // Get the PC info
            $pcInfo = $db->prepare("SELECT * FROM pcs WHERE id = ?");
            $pcInfo->execute([$pcId]);
            $pc = $pcInfo->fetch();

            if ($pc && $pc['status'] === 'occupied' && $pc['occupied_by']) {
                // End the sit-in session
                $db->prepare("
                    UPDATE sit_in_logs SET logout_time = ?, status = 'done'
                    WHERE user_id = ? AND logout_time IS NULL AND lab_room = ?
                ")->execute([date('Y-m-d H:i:s'), $pc['occupied_by'], $pc['lab_room']]);

                // Deduct session
                $db->prepare("UPDATE users SET remaining_sessions = MAX(0, remaining_sessions - 1) WHERE id = ?")->execute([$pc['occupied_by']]);

                // Release PC
                $db->prepare("UPDATE pcs SET status = 'available', occupied_by = NULL WHERE id = ?")->execute([$pcId]);

                // Notify student
                $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([
                    $pc['occupied_by'],
                    "Your sit-in session on PC-" . str_pad($pc['pc_number'],2,'0',STR_PAD_LEFT) . " in Lab {$pc['lab_room']} has been ended by the administrator."
                ]);

                $flash = "Session ended and PC released.";
            } else {
                $flash = "PC not found or not occupied.";
                $flashType = 'error';
            }
        }
    }

    if ($action === 'disable_reserved') {
        $pcId = (int)($_POST['pc_id'] ?? 0);
        if ($pcId > 0) {
            $pcInfo = $db->prepare("SELECT * FROM pcs WHERE id = ?");
            $pcInfo->execute([$pcId]);
            $pc = $pcInfo->fetch();

            if ($pc && $pc['status'] === 'reserved') {
                // Find active/pending/approved reservations for this PC
                $resStmt = $db->prepare("SELECT * FROM reservations WHERE lab_room = ? AND pc_number = ? AND status IN ('pending', 'approved')");
                $resStmt->execute([$pc['lab_room'], $pc['pc_number']]);
                $reservationsToCancel = $resStmt->fetchAll();

                $rejectNote = 'PC Under Maintenance (Disabled by Admin)';
                foreach ($reservationsToCancel as $r) {
                    $db->prepare("UPDATE reservations SET status = 'rejected', reject_note = ? WHERE id = ?")->execute([$rejectNote, $r['id']]);
                    $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([
                        $r['user_id'],
                        "❌ Your reservation for Lab {$r['lab_room']} PC-{$r['pc_number']} has been rejected because the PC was disabled by the administrator."
                    ]);
                }

                // Set PC status to disabled
                $db->prepare("UPDATE pcs SET status = 'disabled', occupied_by = NULL WHERE id = ?")->execute([$pcId]);
                $flash = "PC-" . str_pad($pc['pc_number'], 2, '0', STR_PAD_LEFT) . " disabled and associated reservations canceled.";
            } else {
                $flash = "PC not found or not reserved.";
                $flashType = 'error';
            }
        }
    }

    header('Location: pc-control.php?flash=' . urlencode($flash) . '&ft=' . urlencode($flashType));
    exit;
}

if (isset($_GET['flash'])) {
    $flash     = $_GET['flash'];
    $flashType = $_GET['ft'] ?? 'success';
}

/* Fetch all PCs with student info */
$allPcs = [];
foreach ($labRooms as $lab) {
    $stmt = $db->prepare("
        SELECT p.*, u.first_name || ' ' || u.last_name AS student_name, u.student_id AS stu_id
        FROM pcs p
        LEFT JOIN users u ON u.id = p.occupied_by
        WHERE p.lab_room = ?
        ORDER BY p.pc_number ASC
    ");
    $stmt->execute([$lab]);
    $allPcs[$lab] = $stmt->fetchAll();
}

/* Stats per lab */
$labStats = [];
foreach ($labRooms as $lab) {
    $avail    = 0; $occupied = 0; $disabled = 0; $reserved = 0; $maint = 0;
    foreach ($allPcs[$lab] as $pc) {
        if ($pc['status'] === 'available') $avail++;
        elseif ($pc['status'] === 'occupied') $occupied++;
        elseif ($pc['status'] === 'reserved') $reserved++;
        elseif ($pc['status'] === 'maintenance') $maint++;
        else $disabled++;
    }
    $labStats[$lab] = [
        'available' => $avail,
        'occupied' => $occupied,
        'disabled' => $disabled,
        'reserved' => $reserved,
        'maintenance' => $maint
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>PC Control — UC CompLab Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/admin.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <style>
    /* Lab tabs */
    .pc-tabs {
      display: flex; gap: 6px; flex-wrap: wrap;
      margin-bottom: 1.25rem;
    }
    .pc-tab {
      padding: 0.5rem 1.1rem; border-radius: 8px;
      background: #fff; color: #475569; border: 1.5px solid #e2e8f0;
      font-size: 0.80rem; font-weight: 600;
      cursor: pointer; transition: all 0.15s;
    }
    .pc-tab:hover { background: #f0f4f8; border-color: #cbd5e1; }
    .pc-tab.active {
      background: #1a3a6b; color: #fff; border-color: #1a3a6b;
    }

    /* Stats strip */
    .pc-stats-strip {
      display: flex; gap: 0.75rem; margin-bottom: 1rem;
      flex-wrap: wrap;
    }
    .pc-stat-chip {
      display: flex; align-items: center; gap: 6px;
      padding: 0.4rem 0.875rem; border-radius: 8px;
      font-size: 0.75rem; font-weight: 700;
      border: 1px solid;
    }
    .pc-stat-avail   { background: rgba(22,163,74,0.06); border-color: rgba(22,163,74,0.18); color: #15803d; }
    .pc-stat-occ     { background: rgba(220,38,38,0.06); border-color: rgba(220,38,38,0.18); color: #dc2626; }
    .pc-stat-res     { background: rgba(217,119,6,0.06); border-color: rgba(217,119,6,0.18); color: #d97706; }
    .pc-stat-maint   { background: rgba(217,119,6,0.06); border-color: rgba(217,119,6,0.18); color: #d97706; }
    .pc-stat-dis     { background: rgba(100,116,139,0.06); border-color: rgba(100,116,139,0.18); color: #64748b; }
    .pc-stat-chip i  { font-size: 0.68rem; }

    /* PC Grid */
    .pc-grid-admin {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 8px;
    }
    .pc-card {
      background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
      padding: 0.65rem 0.5rem; text-align: center;
      transition: all 0.15s; position: relative;
      min-height: 80px; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
    }
    .pc-card:hover { box-shadow: 0 2px 10px rgba(15,40,84,0.08); transform: translateY(-1px); }
    .pc-card.pc-avail  { border-color: rgba(22,163,74,0.25); }
    .pc-card.pc-occ    { border-color: rgba(220,38,38,0.25); background: rgba(220,38,38,0.02); }
    .pc-card.pc-res    { border-color: rgba(217,119,6,0.25); background: rgba(217,119,6,0.02); }
    .pc-card.pc-maint  { border-color: rgba(217,119,6,0.25); background: rgba(217,119,6,0.02); }
    .pc-card.pc-dis    { border-color: #e2e8f0; background: #f8fafc; opacity: 0.6; }
    .pc-card-icon { font-size: 1.2rem; margin-bottom: 3px; }
    .pc-avail .pc-card-icon { color: #16a34a; }
    .pc-occ   .pc-card-icon { color: #dc2626; }
    .pc-res   .pc-card-icon { color: #d97706; }
    .pc-maint .pc-card-icon { color: #d97706; }
    .pc-dis   .pc-card-icon { color: #94a3b8; }
    .pc-card-num { font-size: 0.62rem; font-weight: 700; color: #64748b; margin-bottom: 3px; }
    .pc-card-stu { font-size: 0.55rem; color: #94a3b8; line-height: 1.3; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pc-card-actions { margin-top: 4px; display: flex; gap: 3px; justify-content: center; }
    .pc-act-btn {
      padding: 2px 6px; border: none; border-radius: 4px;
      font-size: 0.58rem; font-weight: 700; cursor: pointer;
      transition: all 0.12s;
    }
    .pc-act-toggle { background: rgba(37,99,235,0.08); color: #2563EB; }
    .pc-act-toggle:hover { background: #2563EB; color: #fff; }
    .pc-act-release { background: rgba(220,38,38,0.08); color: #dc2626; }
    .pc-act-release:hover { background: #dc2626; color: #fff; }

    /* Panel visibility */
    .pc-panel { display: none; }
    .pc-panel.active { display: block; }

    @media (max-width: 900px) { .pc-grid-admin { grid-template-columns: repeat(7, 1fr); gap: 5px; } .pc-card { padding: 0.4rem 0.3rem; min-height: 60px; } .pc-card-icon { font-size: 0.95rem; } }
    @media (max-width: 550px) { .pc-grid-admin { grid-template-columns: repeat(5, 1fr); } }
  </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/admin-navbar.php'; ?>

<div class="admin-page">
  <div class="admin-inner">

    <h1 class="a-page-title">PC Control</h1>

    <?php if ($flash): ?>
      <div class="a-flash a-flash-<?= $flashType === 'error' ? 'error' : 'success' ?>">
        <i class="bi bi-<?= $flashType === 'error' ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <!-- Lab Tabs -->
    <div class="pc-tabs" id="pcTabs">
      <?php foreach ($labRooms as $i => $lab): ?>
        <div class="pc-tab <?= $i === 0 ? 'active' : '' ?>"
             data-lab="<?= htmlspecialchars($lab) ?>"
             onclick="switchPcTab(this)">
          Lab <?= htmlspecialchars($lab) ?>
          <span style="font-size:0.65rem;opacity:0.7;margin-left:4px;">(<?= $labStats[$lab]['occupied'] ?> in use)</span>
        </div>
      <?php endforeach; ?>
    </div>

    <?php foreach ($labRooms as $i => $lab): ?>
      <div class="pc-panel <?= $i === 0 ? 'active' : '' ?>" id="pcPanel-<?= htmlspecialchars($lab) ?>">
        <div class="a-card">
          <div class="a-card-header">
            <i class="bi bi-display"></i> Lab <?= htmlspecialchars($lab) ?> — PC Overview
          </div>
          <div class="a-card-body">

            <div class="pc-stats-strip">
              <div class="pc-stat-chip pc-stat-avail">
                <i class="bi bi-check-circle"></i> <?= $labStats[$lab]['available'] ?> Available
              </div>
              <div class="pc-stat-chip pc-stat-occ">
                <i class="bi bi-person-fill"></i> <?= $labStats[$lab]['occupied'] ?> Occupied
              </div>
              <div class="pc-stat-chip pc-stat-res">
                <i class="bi bi-bookmark-fill"></i> <?= $labStats[$lab]['reserved'] ?> Reserved
              </div>
              <div class="pc-stat-chip pc-stat-maint">
                <i class="bi bi-tools"></i> <?= $labStats[$lab]['maintenance'] ?> Maintenance
              </div>
              <div class="pc-stat-chip pc-stat-dis">
                <i class="bi bi-slash-circle"></i> <?= $labStats[$lab]['disabled'] ?> Disabled / Unavailable
              </div>
            </div>

            <!-- Bulk Actions button group -->
            <div class="pc-bulk-actions" style="margin-bottom: 1.25rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 0.6rem 0.85rem; border-radius: 10px;">
              <span style="font-size: 0.72rem; font-weight: 700; color: #475569; margin-right: 0.5rem;"><i class="bi bi-gear-fill"></i> BULK ACTIONS (Lab <?= htmlspecialchars($lab) ?>):</span>
              
              <form method="POST" style="display:inline;" onsubmit="return confirm('Set all disabled, unavailable, and maintenance PCs in Lab <?= htmlspecialchars($lab) ?> to Available?')">
                <input type="hidden" name="action" value="bulk_status">
                <input type="hidden" name="lab_room" value="<?= htmlspecialchars($lab) ?>">
                <input type="hidden" name="new_status" value="available">
                <button type="submit" class="a-btn a-btn-sm a-btn-green">
                  <i class="bi bi-check-circle-fill"></i> Set All Available
                </button>
              </form>

              <form method="POST" style="display:inline;" onsubmit="return confirm('Set all currently available PCs in Lab <?= htmlspecialchars($lab) ?> to Unavailable?')">
                <input type="hidden" name="action" value="bulk_status">
                <input type="hidden" name="lab_room" value="<?= htmlspecialchars($lab) ?>">
                <input type="hidden" name="new_status" value="unavailable">
                <button type="submit" class="a-btn a-btn-sm a-btn-red">
                  <i class="bi bi-slash-circle-fill"></i> Set All Unavailable
                </button>
              </form>

              <form method="POST" style="display:inline;" onsubmit="return confirm('Set all currently available PCs in Lab <?= htmlspecialchars($lab) ?> to Maintenance?')">
                <input type="hidden" name="action" value="bulk_status">
                <input type="hidden" name="lab_room" value="<?= htmlspecialchars($lab) ?>">
                <input type="hidden" name="new_status" value="maintenance">
                <button type="submit" class="a-btn a-btn-sm a-btn-yellow">
                  <i class="bi bi-tools"></i> Set All Maintenance
                </button>
              </form>
            </div>

            <div class="pc-grid-admin">
              <?php foreach ($allPcs[$lab] as $pc):
                $num = str_pad($pc['pc_number'], 2, '0', STR_PAD_LEFT);
                $cls = $pc['status'] === 'available' ? 'pc-avail'
                     : ($pc['status'] === 'occupied' ? 'pc-occ' 
                     : ($pc['status'] === 'reserved' ? 'pc-res' 
                     : ($pc['status'] === 'maintenance' ? 'pc-maint' : 'pc-dis')));
              ?>
                <div class="pc-card <?= $cls ?>">
                  <?php if ($pc['status'] === 'maintenance'): ?>
                    <i class="bi bi-tools pc-card-icon" style="color: #d97706;" title="Under Maintenance"></i>
                  <?php else: ?>
                    <i class="bi bi-display pc-card-icon"></i>
                  <?php endif; ?>
                  <div class="pc-card-num">PC-<?= $num ?></div>
                  <?php if (($pc['status'] === 'occupied' || $pc['status'] === 'reserved') && $pc['student_name']): ?>
                    <div class="pc-card-stu" title="<?= htmlspecialchars($pc['student_name']) ?>"><?= htmlspecialchars($pc['student_name']) ?></div>
                  <?php endif; ?>
                  <div class="pc-card-actions">
                    <?php if ($pc['status'] === 'available'): ?>
                      <div style="display: flex; gap: 3px;">
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="toggle_pc">
                          <input type="hidden" name="pc_id" value="<?= $pc['id'] ?>">
                          <input type="hidden" name="new_status" value="disabled">
                          <button type="submit" class="pc-act-btn pc-act-toggle" title="Disable" style="background: rgba(220,38,38,0.08); color: #dc2626;">
                            <i class="bi bi-slash-circle"></i> Disable
                          </button>
                        </form>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="toggle_pc">
                          <input type="hidden" name="pc_id" value="<?= $pc['id'] ?>">
                          <input type="hidden" name="new_status" value="maintenance">
                          <button type="submit" class="pc-act-btn pc-act-toggle" title="Maintenance" style="background: rgba(217,119,6,0.08); color: #d97706;">
                            <i class="bi bi-tools"></i> Maint
                          </button>
                        </form>
                      </div>
                    <?php elseif (in_array($pc['status'], ['disabled', 'unavailable', 'maintenance'])): ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_pc">
                        <input type="hidden" name="pc_id" value="<?= $pc['id'] ?>">
                        <input type="hidden" name="new_status" value="available">
                        <button type="submit" class="pc-act-btn pc-act-toggle" title="Enable">
                          <i class="bi bi-check-circle"></i> Enable
                        </button>
                      </form>
                    <?php elseif ($pc['status'] === 'occupied'): ?>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('End this session and release PC-<?= $num ?>?')">
                        <input type="hidden" name="action" value="force_release">
                        <input type="hidden" name="pc_id" value="<?= $pc['id'] ?>">
                        <button type="submit" class="pc-act-btn pc-act-release" title="Force Release">
                          <i class="bi bi-door-open"></i> Release
                        </button>
                      </form>
                    <?php elseif ($pc['status'] === 'reserved'): ?>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Disable PC-<?= $num ?> and cancel the active reservation?')">
                        <input type="hidden" name="action" value="disable_reserved">
                        <input type="hidden" name="pc_id" value="<?= $pc['id'] ?>">
                        <button type="submit" class="pc-act-btn pc-act-release" title="Disable">
                          <i class="bi bi-slash-circle"></i> Disable
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</div>

<script src="<?= $base ?>assets/js/admin.js"></script>
<script>
function switchPcTab(el) {
  document.querySelectorAll('.pc-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.pc-panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  const lab = el.dataset.lab;
  document.getElementById('pcPanel-' + lab)?.classList.add('active');
}
</script>
</body>
</html>
