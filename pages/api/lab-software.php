<?php
/**
 * API: GET /pages/api/lab-software.php?lab=524
 * Returns JSON array of software installed in the given lab room.
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
    SELECT software_name, version, icon
    FROM lab_software
    WHERE lab_room = ?
    ORDER BY software_name ASC
");
$stmt->execute([$lab]);
$software = $stmt->fetchAll();

echo json_encode($software);
