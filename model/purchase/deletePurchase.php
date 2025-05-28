<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../inc/config/database.php';
require_once '../../inc/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$purchase_id = intval($_POST['purchase_id'] ?? 0);

if ($purchase_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid purchase ID.']);
    exit;
}

try {
    // Check if the purchase exists
    $checkStmt = $pdo->prepare("SELECT purchase_id FROM purchases WHERE purchase_id = ?");
    $checkStmt->execute([$purchase_id]);

    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Purchase not found.']);
        exit;
    }

    // Delete the purchase (purchase_details will be deleted due to ON DELETE CASCADE)
    $deleteStmt = $pdo->prepare("DELETE FROM purchases WHERE purchase_id = ?");
    $deleteStmt->execute([$purchase_id]);

    echo json_encode(['status' => 'success', 'message' => 'Purchase deleted successfully.']);
} catch (PDOException $e) {
    error_log("Delete Purchase Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while deleting the purchase.'
    ]);
}
