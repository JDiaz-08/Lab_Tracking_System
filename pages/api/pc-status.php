<?php
/**
 * API: GET /pages/api/pc-status.php?lab=524
 * Returns JSON array of PC statuses for the given lab room.
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

$lab = trim($_GET['lab'] ?? '');
if (!$lab) {
    echo json_encode(['error' => 'Missing lab parameter']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT p.pc_number, p.status, p.occupied_by,
           u.first_name || ' ' || u.last_name AS student_name,
           u.student_id
    FROM pcs p
    LEFT JOIN users u ON u.id = p.occupied_by
    WHERE p.lab_room = ?
    ORDER BY p.pc_number ASC
");
$stmt->execute([$lab]);
$pcs = $stmt->fetchAll();

echo json_encode($pcs);
