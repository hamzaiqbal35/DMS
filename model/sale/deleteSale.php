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

$sale_id = intval($_POST['sale_id'] ?? 0);

if ($sale_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sale ID.']);
    exit;
}

try {
    // Check if the sale exists
    $checkStmt = $pdo->prepare("SELECT sale_id FROM sales WHERE sale_id = ?");
    $checkStmt->execute([$sale_id]);

    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Sale not found.']);
        exit;
    }

    // Delete the sale (sale_details will auto-delete due to ON DELETE CASCADE)
    $deleteStmt = $pdo->prepare("DELETE FROM sales WHERE sale_id = ?");
    $deleteStmt->execute([$sale_id]);

    echo json_encode(['status' => 'success', 'message' => 'Sale deleted successfully.']);
} catch (PDOException $e) {
    error_log("Delete Sale Error: " . $e->getMessage(), 3, '../../error_log.log');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred while deleting the sale.'
    ]);
}
