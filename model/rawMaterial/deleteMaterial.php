<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php'; // ✅ Make sure path is correct
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$material_id = intval($_POST['material_id'] ?? 0);

if ($material_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid material ID.']);
    exit;
}

try {
    // Check if material exists
    $checkStmt = $pdo->prepare("SELECT material_id FROM raw_materials WHERE material_id = ?");
    $checkStmt->execute([$material_id]);

    if (!$checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Material not found.']);
        exit;
    }

    // Delete material
    $stmt = $pdo->prepare("DELETE FROM raw_materials WHERE material_id = ?");
    $stmt->execute([$material_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Material deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No material was deleted.']);
    }
} catch (PDOException $e) {
    error_log("Delete Material Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred. Please try again.'
    ]);
}
